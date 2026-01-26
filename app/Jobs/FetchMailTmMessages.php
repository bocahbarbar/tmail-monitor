<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Message;

class FetchMailTmMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $response = Http::withHeaders([
            'accept'        => '*/*',
            'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NjkzNTI4NTEsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoicmhlQHBvd2Vyc2NyZXdzLmNvbSIsImlkIjoiNjhjMjZmZDJlNzJlOWEyOTMxMDNmZjk1IiwibWVyY3VyZSI6eyJzdWJzY3JpYmUiOlsiL2FjY291bnRzLzY4YzI2ZmQyZTcyZTlhMjkzMTAzZmY5NSJdfX0.I1mbPe1xqRBAlvLKashDY-_sWKgtCSlGWo7-PRMJQtu39OmGK0NX91GyqBDFcjvqBigktHeCQqjIAtS-nYDP4Q',        ])->get('https://api.mail.tm/messages');

        if (!$response->successful()) {
            logger()->error('Mail.tm fetch failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return;
        }

        $data = $response->json();

        foreach ($data['hydra:member'] ?? [] as $item) {

            // anti duplicate (idempotent)
            Message::updateOrCreate(
                ['message_id' => $item['id']],
                [
                    'to_address'     => $item['to'][0]['address'] ?? null,
                    'subject'        => $item['subject'] ?? null,
                    'intro'          => $item['intro'] ?? null,
                    'created_at_api' => $item['createdAt'] ?? null,
                    'raw_json'       => $item,
                ]
            );
        }
    }
}