<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\MailAccount;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class OtpApiController extends Controller
{
    /**
     * POST /api/otp
     * Ambil OTP terbaru yang:
     *  1. Dibuat dalam 1 menit terakhir
     *  2. Belum pernah dibaca (read_at IS NULL)
     * Setelah diambil, langsung ditandai read_at = now()
     */
    public function getLatestOtp(Request $request)
    {
        $email = $request->input('email');
        $cutoff = Carbon::now()->subMinute(); // 1 menit yang lalu

        $otp = OtpCode::where('to_address', $email)
            ->whereNull('read_at')                    // belum pernah dibaca
            ->where('created_at', '>=', $cutoff)      // maks 1 menit lalu
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'email' => $email,
                'message' => 'No fresh OTP found (either expired >1min or already read)',
            ], 404);
        }

        // Tandai sudah dibaca
        $otp->update(['read_at' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'email' => $email,
            'otp' => $otp->otp,
            'source' => $otp->source,
            'status' => $otp->status,
            'created_at' => $otp->created_at->format('Y-m-d H:i:s'),
            'read_at' => $otp->read_at->format('Y-m-d H:i:s'),
            'age_seconds' => $otp->created_at->diffInSeconds(Carbon::now()),
        ]);
    }

    /**
     * POST /api/otp/all
     * Ambil 10 OTP terbaru (termasuk yang sudah dibaca), untuk keperluan debug/log.
     */
    public function getAllOtp(Request $request)
    {
        $email = $request->input('email');

        $otps = OtpCode::where('to_address', $email)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['otp', 'source', 'status', 'created_at', 'read_at']);

        return response()->json([
            'success' => true,
            'email' => $email,
            'count' => $otps->count(),
            'data' => $otps,
        ]);
    }

    /**
     * GET /api/otp/data
     * Fetch messages dari semua mail accounts aktif, return OTP 2 menit terakhir.
     * Digunakan oleh dashboard OTP monitor (menggantikan /admin/otp/data).
     */
    public function fetchData()
    {
        try {
            $messageCount = $this->fetchAllAccountMessages();

            $fiveMinutesAgo = now()->subMinutes(2);
            $otps = OtpCode::where('created_at', '>=', $fiveMinutesAgo)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $otps,
                'message_count' => $messageCount,
                'otp_count' => count($otps),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database tables not found. Please run setup first.',
                    'setup_url' => url('/admin/otp/setup-db'),
                    'data' => [],
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 500);
            }
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'data' => [],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    private function fetchAllAccountMessages()
    {
        $accounts = MailAccount::active()->get();
        if ($accounts->isEmpty())
            return 0;

        $totalProcessed = 0;
        foreach ($accounts as $account) {
            try {
                $count = $this->fetchMailTmMessages($account);
                $totalProcessed += $count;
                $account->update([
                    'message_count' => $account->message_count + $count,
                    'last_fetch_at' => now(),
                ]);
            } catch (\Exception $e) {
                logger()->error("Failed to fetch messages for {$account->email}", ['error' => $e->getMessage()]);
                continue;
            }
        }
        return $totalProcessed;
    }

    private function fetchMailTmMessages($account)
    {
        $bearerToken = $account->bearer_token;

        $response = Http::timeout(10)->withHeaders([
            'accept' => '*/*',
            'authorization' => "Bearer {$bearerToken}",
        ])->get('https://api.mail.tm/messages');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch messages list: HTTP ' . $response->status());
        }

        $data = $response->json();
        if (!isset($data['hydra:member'])) {
            throw new \Exception('Invalid response format from Mail.TM API');
        }

        $messagesList = $data['hydra:member'];
        $existingMessageIds = Message::pluck('message_id')->toArray();
        $newMessages = array_filter($messagesList, fn($item) => !in_array($item['id'], $existingMessageIds));
        $processedCount = 0;

        foreach ($newMessages as $messageItem) {
            $messageId = $messageItem['id'];
            $detailResponse = Http::timeout(10)->withHeaders([
                'accept' => '*/*',
                'authorization' => "Bearer {$bearerToken}",
            ])->get("https://api.mail.tm/messages/{$messageId}");

            if (!$detailResponse->successful())
                continue;

            $detailData = $detailResponse->json();
            $intro = $detailData['intro'] ?? '';
            $textContent = $detailData['text'] ?? '';
            $htmlRaw = $detailData['html'] ?? '';
            $htmlContent = strip_tags(is_array($htmlRaw) ? implode(' ', $htmlRaw) : $htmlRaw);
            $fullContent = $intro . ' ' . $textContent . ' ' . $htmlContent;
            $otp = $this->extractOtp($fullContent);

            Message::create([
                'message_id' => $messageId,
                'to_address' => $detailData['to'][0]['address'] ?? null,
                'subject' => $detailData['subject'] ?? null,
                'intro' => $intro,
                'created_at_api' => $detailData['createdAt'] ?? null,
                'raw_json' => $detailData,
            ]);

            if ($otp) {
                OtpCode::create([
                    'message_id' => $messageId,
                    'to_address' => $detailData['to'][0]['address'] ?? null,
                    'otp' => $otp,
                    'source' => $detailData['subject'] ?? 'Unknown',
                    'status' => 'active',
                ]);
            }
            $processedCount++;
        }
        return $processedCount;
    }

    private function extractOtp($text)
    {
        if (!$text)
            return null;
        $text = strip_tags(html_entity_decode($text));
        $patterns = [
            '/(?:code|otp|verification|pin|token)[:\s]*[#]?\s*[:\-]?\s*(\d{6})/i',
            '/(?:your|the|is)[:\s]+(\d{6})\s*(?:is|$)/i',
            '/\b(\d{6})\b(?!\d)/',
            '/(?:code|otp|verification|pin|token)[:\s]*[#]?\s*[:\-]?\s*(\d{4,8})/i',
            '/\b(\d{4})\b(?!\d)/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $otp = $matches[1];
                if (strlen($otp) >= 4 && strlen($otp) <= 8)
                    return $otp;
            }
        }
        return null;
    }
}
