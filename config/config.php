<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'daechang_shipping');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Sistema de Control de Embarques - DAECHANG');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost');

// Security configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('HASH_ALGO', 'sha256');
define('SALT', 'daechang_salt_2024');

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Timezone
date_default_timezone_set('America/Mexico_City');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>