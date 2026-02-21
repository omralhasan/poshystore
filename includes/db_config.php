<?php

/**
 * Database Configuration for Poshy Lifestyle Store
 * 
 * Reads from environment variables loaded by config.php.
 * Fallback values match the DigitalOcean VPS production setup.
 *   DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
 */

// Ensure central config (which loads .env) has been included
if (!defined('POSHY_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

return [
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'user'     => getenv('DB_USER')     ?: 'poshy_user',
    'password' => getenv('DB_PASS')     ?: 'Poshy2026secure',
    'database' => getenv('DB_NAME')     ?: 'poshy_db',
    'charset'  => 'utf8mb4'
];
