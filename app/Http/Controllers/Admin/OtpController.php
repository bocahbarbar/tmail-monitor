<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchMailTmMessages;
use App\Jobs\ResolveMailTmOtp;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OtpController extends Controller
{
    public function index()
    {
        return view('admin.otp.index');
    }

    public function data()
    {
        try {
            // Fetch messages dari semua mail accounts yang aktif
            $messageCount = $this->fetchAllAccountMessages();
            
            // Get OTP codes
            $otps = OtpCode::orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $otps,
                'message_count' => $messageCount,
                'otp_count' => count($otps),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Database error (table not found, etc)
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database tables not found. Please run setup first.',
                    'setup_url' => url('/admin/otp/setup-db'),
                    'data' => [],
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ], 500);
            }
            
            logger()->error('Database error in OTP data', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'data' => [],
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
            
        } catch (\Exception $e) {
            logger()->error('Error fetching mail.tm data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function testApi()
    {
        try {
            $bearerToken = config('services.mailtm.bearer_token');
            
            $response = Http::timeout(10)->withHeaders([
                'accept' => '*/*',
                'authorization' => "Bearer {$bearerToken}",
            ])->get('https://api.mail.tm/messages');

            $statusCode = $response->status();
            $body = $response->json();

            return response()->json([
                'success' => $response->successful(),
                'status_code' => $statusCode,
                'bearer_token' => substr($bearerToken, 0, 50) . '...',
                'response' => $body,
                'total_messages' => isset($body['hydra:member']) ? count($body['hydra:member']) : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function fetchAllAccountMessages()
    {
        $accounts = \App\Models\MailAccount::active()->get();
        
        if ($accounts->isEmpty()) {
            logger()->warning('No active mail accounts found');
            return 0;
        }
        
        $totalProcessed = 0;
        
        foreach ($accounts as $account) {
            try {
                $count = $this->fetchMailTmMessages($account);
                $totalProcessed += $count;
                
                // Update account stats
                $account->update([
                    'message_count' => $account->message_count + $count,
                    'last_fetch_at' => now(),
                ]);
                
                logger()->info("Account {$account->email} fetched {$count} messages");
            } catch (\Exception $e) {
                logger()->error("Failed to fetch messages for {$account->email}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        return $totalProcessed;
    }

    private function fetchMailTmMessages($account = null)
    {
        // Support untuk backward compatibility
        if ($account === null) {
            // Fallback ke config jika tidak ada account
            $bearerToken = config('services.mailtm.bearer_token');
        } else {
            $bearerToken = $account->bearer_token;
        }

        // Step 1: Fetch list of messages (get IDs only)
        $response = Http::timeout(10)->withHeaders([
            'accept' => '*/*',
            'authorization' => "Bearer {$bearerToken}",
        ])->get('https://api.mail.tm/messages');

        if (!$response->successful()) {
            logger()->error('Mail.TM fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch messages list: HTTP ' . $response->status());
        }

        $data = $response->json();
        
        if (!isset($data['hydra:member'])) {
            throw new \Exception('Invalid response format from Mail.TM API');
        }
        
        $messagesList = $data['hydra:member'];
        
        // Get existing message IDs from database
        $existingMessageIds = \App\Models\Message::pluck('message_id')->toArray();
        
        // Filter only new messages
        $newMessages = array_filter($messagesList, function($item) use ($existingMessageIds) {
            return !in_array($item['id'], $existingMessageIds);
        });
        
        $processedCount = 0;
        $skippedCount = count($messagesList) - count($newMessages);

        logger()->info('Messages check', [
            'total_from_api' => count($messagesList),
            'existing_in_db' => count($existingMessageIds),
            'new_to_fetch' => count($newMessages),
            'skipped' => $skippedCount
        ]);

        // Step 2: Loop only NEW message IDs and fetch detail
        foreach ($newMessages as $messageItem) {
            $messageId = $messageItem['id'];
            
            try {
                // Step 3: Fetch detail message
                $detailResponse = Http::timeout(10)->withHeaders([
                    'accept' => '*/*',
                    'authorization' => "Bearer {$bearerToken}",
                ])->get("https://api.mail.tm/messages/{$messageId}");

                if ($detailResponse->successful()) {
                    $detailData = $detailResponse->json();
                    
                    // Get all content
                    $intro = $detailData['intro'] ?? '';
                    $textContent = $detailData['text'] ?? '';
                    
                    // Handle html - could be string or array
                    $htmlRaw = $detailData['html'] ?? '';
                    if (is_array($htmlRaw)) {
                        $htmlContent = implode(' ', $htmlRaw);
                    } else {
                        $htmlContent = $htmlRaw;
                    }
                    $htmlContent = strip_tags($htmlContent);
                    
                    // Combine all content for OTP extraction
                    $fullContent = $intro . ' ' . $textContent . ' ' . $htmlContent;
                    
                    // Extract OTP
                    $otp = $this->extractOtp($fullContent);
                    
                    // Save to database
                    \App\Models\Message::create([
                        'message_id' => $messageId,
                        'to_address' => $detailData['to'][0]['address'] ?? null,
                        'subject' => $detailData['subject'] ?? null,
                        'intro' => $intro,
                        'created_at_api' => $detailData['createdAt'] ?? null,
                        'raw_json' => $detailData,
                    ]);
                    
                    // Save OTP if found
                    if ($otp) {
                        OtpCode::create([
                            'message_id' => $messageId,
                            'to_address' => $detailData['to'][0]['address'] ?? null,
                            'otp' => $otp,
                            'source' => $detailData['subject'] ?? 'Unknown',
                            'status' => 'active',
                        ]);
                        
                        logger()->info('✓ OTP extracted', [
                            'message_id' => $messageId,
                            'otp' => $otp
                        ]);
                    } else {
                        logger()->info('✗ No OTP found', [
                            'message_id' => $messageId
                        ]);
                    }
                    
                    $processedCount++;
                }
                
            } catch (\Exception $e) {
                logger()->error('Failed to fetch message detail', [
                    'message_id' => $messageId,
                    'error' => $e->getMessage()
                ]);
                // Continue to next message
                continue;
            }
        }

        logger()->info('Mail.TM fetch completed', [
            'total_messages' => count($messagesList),
            'new_fetched' => $processedCount,
            'skipped_existing' => $skippedCount
        ]);

        return $processedCount;
    }

    private function processOtpCodes()
    {
        // This method is no longer needed as we process OTP in fetchMailTmMessages
        // But keeping it for compatibility
        return true;
    }

    private function extractOtp($text)
    {
        if (!$text) return null;

        // Clean text
        $text = strip_tags($text);
        $text = html_entity_decode($text);

        // Pattern untuk extract OTP (dari yang paling spesifik ke general)
        $patterns = [
            '/(?:code|otp|verification|pin|token)[:\s]*[#]?\s*[:\-]?\s*(\d{6})/i',  // "code: 123456" atau "OTP #123456"
            '/(?:your|the|is)[:\s]+(\d{6})\s*(?:is|$)/i',                          // "your code is 123456"
            '/\b(\d{6})\b(?!\d)/',                                                  // 6 digit standalone
            '/(?:code|otp|verification|pin|token)[:\s]*[#]?\s*[:\-]?\s*(\d{4,8})/i', // 4-8 digit dengan keyword
            '/\b(\d{4})\b(?!\d)/',                                                  // 4 digit standalone
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $otp = $matches[1];
                // Validasi: OTP harus 4-8 digit
                if (strlen($otp) >= 4 && strlen($otp) <= 8) {
                    return $otp;
                }
            }
        }

        return null;
    }
}