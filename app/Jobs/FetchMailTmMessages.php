<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\MailAccount;
use App\Models\Message;
use App\Models\OtpCode;

class FetchMailTmMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function handle(): void
    {
        $accounts = MailAccount::active()->get();

        if ($accounts->isEmpty()) {
            logger()->warning('[MailFetcher] Tidak ada akun aktif. Job berhenti.');
            // Tetap reschedule agar jalan lagi saat akun ditambah
            self::dispatch()->delay(now()->addSeconds(30));
            return;
        }

        foreach ($accounts as $account) {
            try {
                $count = $this->fetchMessages($account);
                $account->update([
                    'message_count' => $account->message_count + $count,
                    'last_fetch_at' => now(),
                ]);
                logger()->info("[MailFetcher] {$account->email} → {$count} pesan baru");
            } catch (\Exception $e) {
                logger()->error("[MailFetcher] Gagal fetch {$account->email}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Self-dispatch dengan delay 5 detik → berjalan terus di background
        self::dispatch()->delay(now()->addSeconds(5));
    }

    private function fetchMessages(MailAccount $account): int
    {
        $response = Http::timeout(10)->withHeaders([
            'accept'        => '*/*',
            'authorization' => "Bearer {$account->bearer_token}",
        ])->get('https://api.mail.tm/messages');

        if (!$response->successful()) {
            throw new \Exception('Fetch gagal: HTTP ' . $response->status());
        }

        $messages     = $response->json()['hydra:member'] ?? [];
        $existingIds  = Message::pluck('message_id')->toArray();
        $newMessages  = array_filter($messages, fn($m) => !in_array($m['id'], $existingIds));
        $processed    = 0;

        foreach ($newMessages as $item) {
            try {
                $detail = Http::timeout(10)->withHeaders([
                    'accept'        => '*/*',
                    'authorization' => "Bearer {$account->bearer_token}",
                ])->get("https://api.mail.tm/messages/{$item['id']}");

                if (!$detail->successful()) continue;

                $data = $detail->json();

                // Ambil konten untuk ekstrak OTP
                $htmlRaw = $data['html'] ?? '';
                $html    = is_array($htmlRaw) ? implode(' ', $htmlRaw) : $htmlRaw;
                $content = ($data['intro'] ?? '') . ' ' . ($data['text'] ?? '') . ' ' . strip_tags($html);

                Message::create([
                    'message_id'     => $item['id'],
                    'to_address'     => $data['to'][0]['address'] ?? null,
                    'subject'        => $data['subject'] ?? null,
                    'intro'          => $data['intro'] ?? null,
                    'created_at_api' => $data['createdAt'] ?? null,
                    'raw_json'       => $data,
                ]);

                $otp = $this->extractOtp($content);
                if ($otp) {
                    OtpCode::create([
                        'message_id' => $item['id'],
                        'to_address' => $data['to'][0]['address'] ?? null,
                        'otp'        => $otp,
                        'source'     => $data['subject'] ?? 'Unknown',
                        'status'     => 'active',
                    ]);
                    logger()->info("[MailFetcher] ✓ OTP: {$otp} untuk {$data['to'][0]['address']}");
                }

                $processed++;
            } catch (\Exception $e) {
                logger()->error("[MailFetcher] Gagal proses pesan {$item['id']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    private function extractOtp(string $text): ?string
    {
        if (!$text) return null;

        $text = strip_tags($text);
        $text = html_entity_decode($text);

        $patterns = [
            '/(?:code|otp|verification|pin|token)[\:\s]*[#]?\s*[\:\-]?\s*(\d{6})/i',
            '/(?:your|the|is)[\:\s]+(\d{6})\s*(?:is|$)/i',
            '/\b(\d{6})\b(?!\d)/',
            '/(?:code|otp|verification|pin|token)[\:\s]*[#]?\s*[\:\-]?\s*(\d{4,8})/i',
            '/\b(\d{4})\b(?!\d)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                if (strlen($m[1]) >= 4 && strlen($m[1]) <= 8) {
                    return $m[1];
                }
            }
        }

        return null;
    }
}