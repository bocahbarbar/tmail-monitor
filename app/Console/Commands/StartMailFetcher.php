<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchMailTmMessages;

class StartMailFetcher extends Command
{
    protected $signature   = 'mailtm:start';
    protected $description = 'Dispatch job FetchMailTmMessages pertama kali ke queue. Job akan terus self-dispatch setiap 5 detik.';

    public function handle()
    {
        FetchMailTmMessages::dispatch();
        $this->info('✅ FetchMailTmMessages job berhasil di-dispatch!');
        $this->info('   Job akan berjalan terus setiap 5 detik di background.');
        $this->info('   Pastikan `php artisan queue:work` sudah berjalan di terminal lain.');
    }
}
