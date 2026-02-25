<?php
/**
 * Poshy Lifestyle Store – Central Configuration
 * 
 * Include this file at the top of every entry-point PHP file.
 * It loads .env, sets constants, and configures error handling.
 * 
 * Usage:  require_once __DIR__ . '/config.php';          (from project root)
 *         require_once __DIR__ . '/../../config.php';     (from pages/shop/)
 *         require_once __DIR__ . '/../config.php';        (from pages/auth/)
 */

// ─── Prevent double-include ────────────────────────────────────────────
if (defined('POSHY_CONFIG_LOADED')) return;
define('POSHY_CONFIG_LOADED', true);

// ─── Load .env ─────────────────────────────────────────────────────────
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// ─── Site Constants ────────────────────────────────────────────────────
// SITE_URL: Full URL without trailing slash  (http://159.223.180.154)
// BASE_PATH: Path prefix for links          (empty string when at doc root)
// ROOT_DIR: Absolute filesystem path        (/var/www/html)
define('SITE_URL',  getenv('SITE_URL')  ?: 'https://poshystore.com');
define('BASE_PATH', getenv('BASE_PATH') ?: '');
define('ROOT_DIR',  __DIR__);

// ─── Error Handling (Production) ───────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors',  '0');       // Never show errors to visitors
ini_set('display_startup_errors', '0');
ini_set('log_errors',      '1');       // Log everything to file
ini_set('error_log', ROOT_DIR . '/logs/error.log');

// Ensure logs directory exists
if (!is_dir(ROOT_DIR . '/logs')) {
    @mkdir(ROOT_DIR . '/logs', 0755, true);
}

// ─── Session (start once) ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
