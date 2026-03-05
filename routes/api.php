<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OtpApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Prefix otomatis: /api
| Contoh: GET /api/example@mail.com  → OTP terakhir
|         GET /api/example@mail.com/all → 10 OTP terakhir
*/

// POST /api/otp → OTP terakhir, email di request body
Route::post('/otp', [OtpApiController::class, 'getLatestOtp']);

// POST /api/otp/all → 10 OTP terakhir, email di request body
Route::post('/otp/all', [OtpApiController::class, 'getAllOtp']);
