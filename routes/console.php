<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\FetchMailTmMessages;
use App\Jobs\ResolveMailTmOtp;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::job(new FetchMailTmMessages)
    ->everyMinute()
    ->withoutOverlapping();

    Schedule::job(new ResolveMailTmOtp)
    ->everyMinute()
    ->withoutOverlapping();