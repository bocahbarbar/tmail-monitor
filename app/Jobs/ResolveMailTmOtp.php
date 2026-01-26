<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\OtpCode;
use App\Support\OtpExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ResolveMailTmOtp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // ambil message yang belum punya OTP
        $messages = Message::whereNotIn(
            'message_id',
            OtpCode::pluck('message_id')
        )->limit(20)->get();

        foreach ($messages as $message) {

            // 1️⃣ coba dari intro
            $otp = OtpExtractor::extract($message->intro);

            if ($otp) {
                OtpCode::create([
                    'message_id' => $message->message_id,
                    'to_address' => $message->to_address,
                    'otp'        => $otp,
                    'source'     => 'intro',
                    'status'     => 'success',
                ]);
                continue;
            }

            // 2️⃣ fallback ke detail message
            $detail = Http::withHeaders([
                'authorization' => config('services.mailtm.token'),
            ])->get('https://api.mail.tm/messages/' . $message->message_id);

            if (!$detail->successful()) {
                OtpCode::create([
                    'message_id' => $message->message_id,
                    'to_address' => $message->to_address,
                    'status'     => 'failed',
                ]);
                continue;
            }

            $body = $detail->json()['text'] ?? null;
            $otp  = OtpExtractor::extract($body);

            OtpCode::create([
                'message_id' => $message->message_id,
                'to_address' => $message->to_address,
                'otp'        => $otp,
                'source'     => $otp ? 'detail' : null,
                'status'     => $otp ? 'success' : 'failed',
            ]);
        }
    }
}