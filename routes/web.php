<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OtpController;
use App\Http\Controllers\Admin\MailAccountController;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('admin')->group(function () {
    // Mail Accounts Management
    Route::resource('mail-accounts', MailAccountController::class)->names([
        'index' => 'admin.mail-accounts.index',
        'create' => 'admin.mail-accounts.create',
        'store' => 'admin.mail-accounts.store',
        'edit' => 'admin.mail-accounts.edit',
        'update' => 'admin.mail-accounts.update',
        'destroy' => 'admin.mail-accounts.destroy',
    ]);
    Route::post('/mail-accounts/{mailAccount}/toggle-status', [MailAccountController::class, 'toggleStatus'])
        ->name('admin.mail-accounts.toggle-status');
    Route::get('/mail-accounts/{mailAccount}/test-connection', [MailAccountController::class, 'testConnection'])
        ->name('admin.mail-accounts.test-connection');
    Route::post('/mail-accounts/{mailAccount}/refresh-info', [MailAccountController::class, 'refreshAccountInfo'])
        ->name('admin.mail-accounts.refresh-info');
    
    // OTP Monitor
    Route::get('/otp', [OtpController::class, 'index'])->name('admin.otp');
    Route::get('/otp/data', [OtpController::class, 'data'])->name('admin.otp.data');
    Route::get('/otp/test-api', [OtpController::class, 'testApi'])->name('admin.otp.test');
    Route::get('/otp/debug', function() {
        try {
            $checks = [
                'database_connected' => false,
                'messages_table_exists' => false,
                'otp_codes_table_exists' => false,
                'bearer_token_exists' => false,
                'api_reachable' => false,
                'errors' => []
            ];
            
            // Check database connection
            try {
                DB::connection()->getPdo();
                $checks['database_connected'] = true;
            } catch (\Exception $e) {
                $checks['errors'][] = 'Database: ' . $e->getMessage();
            }
            
            // Check tables
            try {
                $tables = DB::select("SHOW TABLES");
                $tableNames = array_map(function($t) {
                    return array_values((array)$t)[0];
                }, $tables);
                
                $checks['messages_table_exists'] = in_array('messages', $tableNames);
                $checks['otp_codes_table_exists'] = in_array('otp_codes', $tableNames);
                $checks['all_tables'] = $tableNames;
            } catch (\Exception $e) {
                $checks['errors'][] = 'Tables: ' . $e->getMessage();
            }
            
            // Check bearer token
            $bearerToken = config('services.mailtm.bearer_token');
            $checks['bearer_token_exists'] = !empty($bearerToken);
            $checks['bearer_token_preview'] = substr($bearerToken, 0, 30) . '...';
            
            // Check API
            try {
                $response = Http::timeout(5)->withHeaders([
                    'accept' => '*/*',
                    'authorization' => "Bearer {$bearerToken}",
                ])->get('https://api.mail.tm/messages');
                
                $checks['api_reachable'] = $response->successful();
                $checks['api_status'] = $response->status();
            } catch (\Exception $e) {
                $checks['errors'][] = 'API: ' . $e->getMessage();
            }
            
            return response()->json($checks);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
    Route::get('/otp/setup-db', function() {
        try {
            DB::statement('
                CREATE TABLE IF NOT EXISTS `messages` (
                  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `message_id` varchar(255) NOT NULL,
                  `to_address` varchar(255) DEFAULT NULL,
                  `subject` varchar(255) DEFAULT NULL,
                  `intro` text DEFAULT NULL,
                  `raw_json` json DEFAULT NULL,
                  `created_at_api` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `messages_message_id_unique` (`message_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
            
            DB::statement('
                CREATE TABLE IF NOT EXISTS `otp_codes` (
                  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `message_id` varchar(255) NOT NULL,
                  `to_address` varchar(255) NOT NULL,
                  `otp` varchar(255) DEFAULT NULL,
                  `source` varchar(255) DEFAULT NULL,
                  `status` varchar(255) DEFAULT "pending",
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `otp_codes_message_id_unique` (`message_id`),
                  KEY `otp_codes_to_address_index` (`to_address`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
            
            $tables = DB::select('SHOW TABLES');
            return response()->json([
                'success' => true,
                'message' => 'Database tables created successfully!',
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
});