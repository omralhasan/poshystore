<?php

/**
 * Database Configuration for Poshy Lifestyle Store
 * 
 * Reads from environment variables with local fallbacks.
 * Set these env vars in .env or your hosting platform:
 *   DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
 */

// Load .env file if it exists (for Apache/web context where shell env isn't available)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

return [
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'user'     => getenv('DB_USER')     ?: 'poshy_user',
    'password' => getenv('DB_PASS')     ?: 'Poshy2026',
    'database' => getenv('DB_NAME')     ?: 'poshy_lifestyle',
    'charset'  => 'utf8mb4'
];
