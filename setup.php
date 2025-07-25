#!/usr/bin/env php
<?php
/**
 * Database Setup Script for DAECHANG Shipping Control System
 * 
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Create all required tables
 * 3. Insert default data
 * 4. Create the default admin user
 */

require_once __DIR__ . '/config/config.php';

echo "=== DAECHANG Shipping Control System - Database Setup ===\n\n";

// Check if we can connect to MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL server\n";
} catch (PDOException $e) {
    die("✗ Error connecting to MySQL: " . $e->getMessage() . "\n");
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '" . DB_NAME . "' created/verified\n";
} catch (PDOException $e) {
    die("✗ Error creating database: " . $e->getMessage() . "\n");
}

// Connect to the specific database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database '" . DB_NAME . "'\n\n";
} catch (PDOException $e) {
    die("✗ Error connecting to database: " . $e->getMessage() . "\n");
}

// Read and execute the schema
$schemaFile = __DIR__ . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    die("✗ Schema file not found: $schemaFile\n");
}

$schema = file_get_contents($schemaFile);
if (!$schema) {
    die("✗ Could not read schema file\n");
}

echo "Installing database schema...\n";

// Split the schema into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    function($statement) {
        return !empty($statement) && !preg_match('/^(--|\/\*|\*)/', $statement);
    }
);

$successCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        $successCount++;
        
        // Show progress for table creation
        if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $statement, $matches)) {
            echo "  ✓ Created table: {$matches[1]}\n";
        } elseif (preg_match('/INSERT INTO (\w+)/', $statement, $matches)) {
            echo "  ✓ Inserted data into: {$matches[1]}\n";
        } elseif (preg_match('/CREATE INDEX (\w+)/', $statement, $matches)) {
            echo "  ✓ Created index: {$matches[1]}\n";
        }
    } catch (PDOException $e) {
        $errorCount++;
        echo "  ✗ Error executing statement: " . $e->getMessage() . "\n";
        echo "  Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n=== Setup Summary ===\n";
echo "Successful operations: $successCount\n";
echo "Errors: $errorCount\n";

if ($errorCount === 0) {
    echo "\n✓ Database setup completed successfully!\n";
    echo "\nDefault admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nYou can now access the system at: " . BASE_URL . "\n";
    echo "\n⚠️  Remember to change the default password after first login!\n";
} else {
    echo "\n✗ Setup completed with errors. Please check the error messages above.\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Configure your web server to point to the 'public' directory\n";
echo "2. Ensure proper file permissions for the 'uploads' directory\n";
echo "3. Update the configuration in config/config.php if needed\n";
echo "4. Access the system and change the default admin password\n";

echo "\nSetup completed.\n";
?>