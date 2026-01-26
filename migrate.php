<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Running migrations...\n";

try {
    $kernel->call('migrate:fresh', ['--force' => true]);
    echo "✓ Migrations completed successfully!\n";
    
    // Show tables
    $tables = DB::select('SHOW TABLES');
    echo "\nTables in database:\n";
    foreach ($tables as $table) {
        $tableArray = (array) $table;
        echo "  - " . array_values($tableArray)[0] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}