<?php
/**
 * Database Connection File for Poshy Lifestyle E-Commerce
 * 
 * This file establishes a secure connection to the MySQL database
 * using mysqli with prepared statement support.
 * 
 * Credentials loaded from .env via config.php → db_config.php
 */

// Prevent double-include
if (defined('POSHY_DB_LOADED')) return;
define('POSHY_DB_LOADED', true);

// Load database configuration
$db_config = require_once __DIR__ . '/db_config.php';

// Create mysqli connection
$conn = new mysqli(
    $db_config['host'],
    $db_config['user'],
    $db_config['password'],
    $db_config['database'],
    $db_config['port']
);

// Check connection
if ($conn->connect_error) {
    // Log error (in production, use proper logging)
    error_log("Database Connection Failed: " . $conn->connect_error);
    
    // Return JSON error for API responses
    if (defined('API_REQUEST')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed. Please try again later.'
        ]);
        exit();
    }
    
    die("Connection failed: " . $conn->connect_error);
}

/**
 * Validate identifier-style DB names.
 */
function isSafeDbName($name) {
    return is_string($name) && preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

/**
 * Returns true if a table exists inside the provided database.
 */
function dbHasTable(mysqli $conn, $database, $table) {
    if (!isSafeDbName($database) || !isSafeDbName($table)) {
        return false;
    }

    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $database, $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();
    return $ok;
}

/**
 * Returns products count for a candidate DB (0 if unavailable).
 */
function dbProductsCount(mysqli $conn, $database) {
    if (!isSafeDbName($database) || !dbHasTable($conn, $database, 'products')) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $database . "`.`products`";
    $res = $conn->query($sql);
    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return isset($row['c']) ? (int)$row['c'] : 0;
}

/**
 * Auto-select the best available application database when configured DB is
 * missing core tables or has no product rows while another candidate has data.
 */
function selectBestApplicationDatabase(mysqli $conn, $configuredDb) {
    $configuredDb = isSafeDbName($configuredDb) ? $configuredDb : '';

    $candidates = [];
    if ($configuredDb !== '') {
        $candidates[] = $configuredDb;
    }

    // Known project DB names (legacy + current)
    $candidates[] = 'poshy_db';
    $candidates[] = 'poshy_lifestyle';

    // Optional custom candidates from env: DB_FALLBACKS=db1,db2
    $fallbacksEnv = getenv('DB_FALLBACKS') ?: '';
    if (!empty($fallbacksEnv)) {
        $parts = explode(',', $fallbacksEnv);
        foreach ($parts as $dbName) {
            $dbName = trim($dbName);
            if ($dbName !== '') {
                $candidates[] = $dbName;
            }
        }
    }

    // Keep first occurrence only
    $seen = [];
    $ordered = [];
    foreach ($candidates as $dbName) {
        if (!isSafeDbName($dbName) || isset($seen[$dbName])) {
            continue;
        }
        $seen[$dbName] = true;
        $ordered[] = $dbName;
    }

    if (empty($ordered)) {
        return $configuredDb;
    }

    $currentHasProductsTable = ($configuredDb !== '') ? dbHasTable($conn, $configuredDb, 'products') : false;
    $currentProductsCount = ($configuredDb !== '') ? dbProductsCount($conn, $configuredDb) : 0;

    $bestDb = $configuredDb;
    $bestCount = $currentProductsCount;

    foreach ($ordered as $dbName) {
        if (!dbHasTable($conn, $dbName, 'products')) {
            continue;
        }
        $count = dbProductsCount($conn, $dbName);
        if ($count > $bestCount || $bestDb === '') {
            $bestDb = $dbName;
            $bestCount = $count;
        }
    }

    // Switch only when needed:
    // 1) configured DB missing products table, or
    // 2) configured has 0 products while another candidate has >0.
    $shouldSwitch = false;
    if (!$currentHasProductsTable && $bestDb !== '' && $bestDb !== $configuredDb) {
        $shouldSwitch = true;
    } elseif ($currentHasProductsTable && $currentProductsCount === 0 && $bestDb !== '' && $bestDb !== $configuredDb && $bestCount > 0) {
        $shouldSwitch = true;
    }

    if ($shouldSwitch) {
        if ($conn->select_db($bestDb)) {
            error_log("DB auto-switch: using '{$bestDb}' instead of '{$configuredDb}'");
            return $bestDb;
        }
    }

    return $configuredDb;
}

$activeDatabase = selectBestApplicationDatabase($conn, $db_config['database']);

// Set charset to UTF-8 for Arabic support
$conn->set_charset($db_config['charset']);

// Set timezone to Jordan
$conn->query("SET time_zone = '+03:00'");

/**
 * Function to safely close database connection
 */
function closeConnection() {
    global $conn;
    if (!($conn instanceof mysqli)) {
        return;
    }

    try {
        @$conn->close();
    } catch (Throwable $e) {
        // Ignore shutdown-time close errors (already closed/invalid handle)
    } finally {
        $conn = null;
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeConnection');

/**
 * Helper function to execute prepared statements safely
 * 
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
 * @param array $params Array of parameters
 * @return mysqli_stmt|false Prepared statement or false on failure
 */
function prepareAndBind($sql, $types, $params) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

/**
 * Helper function to sanitize input
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Currency formatter for JOD (Jordanian Dinar)
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function formatJOD($amount) {
    // Check if translation function exists, otherwise use default
    $currency = function_exists('t') ? t('currency') : 'JOD';
    return number_format($amount, 3, '.', ',') . ' ' . $currency;
}

/**
 * Convert price to fils (smallest unit of JOD)
 * 1 JOD = 1000 fils
 * 
 * @param float $jod Amount in JOD
 * @return int Amount in fils
 */
function jodToFils($jod) {
    return (int)round($jod * 1000);
}

/**
 * Convert fils to JOD
 * 
 * @param int $fils Amount in fils
 * @return float Amount in JOD
 */
function filsToJOD($fils) {
    return $fils / 1000;
}

// Success - connection established
// The $conn variable is now available globally throughout the application
?>
