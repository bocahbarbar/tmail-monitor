<?php

$host = '127.0.0.1';
$db = 'ltmail';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: $db\n\n";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_tables.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "✓ Tables created successfully!\n\n";
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}