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
 * Read currently selected database name from server session.
 */
function getCurrentDbName(mysqli $conn) {
    $res = $conn->query("SELECT DATABASE() AS db_name");
    if (!$res) {
        return '';
    }
    $row = $res->fetch_assoc();
    return isset($row['db_name']) ? (string)$row['db_name'] : '';
}

/**
 * Returns true if a table exists inside the provided database.
 */
function dbHasTable(mysqli $conn, $database, $table) {
    if (!isSafeDbName($database) || !isSafeDbName($table)) {
        return false;
    }

    $originalDb = getCurrentDbName($conn);
    if (!$conn->select_db($database)) {
        return false;
    }

    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    if (!$stmt) {
        if ($originalDb !== '' && $originalDb !== $database) {
            @$conn->select_db($originalDb);
        }
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();

    if ($originalDb !== '' && $originalDb !== $database) {
        @$conn->select_db($originalDb);
    }

    return $ok;
}

/**
 * Returns products count for a candidate DB (0 if unavailable).
 */
function dbProductsCount(mysqli $conn, $database) {
    if (!isSafeDbName($database) || !dbHasTable($conn, $database, 'products')) {
        return 0;
    }

    $originalDb = getCurrentDbName($conn);
    if (!$conn->select_db($database)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS c FROM `products`";
    $res = $conn->query($sql);
    if ($originalDb !== '' && $originalDb !== $database) {
        @$conn->select_db($originalDb);
    }

    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return isset($row['c']) ? (int)$row['c'] : 0;
}

/**
 * Score how much a candidate database looks like the app database.
 */
function dbCoreTableScore(mysqli $conn, $database) {
    if (!isSafeDbName($database)) {
        return 0;
    }

    $core = ['products', 'categories', 'subcategories', 'orders', 'users'];
    $score = 0;
    foreach ($core as $table) {
        if (dbHasTable($conn, $database, $table)) {
            $score++;
        }
    }
    return $score;
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

    // Discover DBs dynamically when account is allowed to list them.
    $showDbs = $conn->query('SHOW DATABASES');
    if ($showDbs) {
        while ($row = $showDbs->fetch_row()) {
            $dbName = isset($row[0]) ? (string)$row[0] : '';
            if ($dbName === '') {
                continue;
            }
            // Ignore mysql internal schemas.
            if (in_array(strtolower($dbName), ['information_schema', 'mysql', 'performance_schema', 'sys'], true)) {
                continue;
            }
            $candidates[] = $dbName;
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
    $currentCoreScore = ($configuredDb !== '') ? dbCoreTableScore($conn, $configuredDb) : 0;

    $bestDb = $configuredDb;
    $bestCount = $currentProductsCount;
    $bestCoreScore = $currentCoreScore;

    foreach ($ordered as $dbName) {
        $coreScore = dbCoreTableScore($conn, $dbName);
        $count = dbProductsCount($conn, $dbName);
        if ($coreScore > $bestCoreScore || ($coreScore === $bestCoreScore && ($count > $bestCount || $bestDb === ''))) {
            $bestDb = $dbName;
            $bestCount = $count;
            $bestCoreScore = $coreScore;
        }
    }

    // Switch only when needed:
    // 1) configured DB missing products table, or
    // 2) configured has 0 products while another candidate has >0.
    $shouldSwitch = false;
    if (!$currentHasProductsTable && $bestDb !== '' && $bestDb !== $configuredDb && $bestCoreScore > 0) {
        $shouldSwitch = true;
    } elseif ($currentHasProductsTable && $currentProductsCount === 0 && $bestDb !== '' && $bestDb !== $configuredDb && $bestCount > 0) {
        $shouldSwitch = true;
    } elseif ($bestDb !== '' && $bestDb !== $configuredDb && $bestCoreScore > $currentCoreScore) {
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
if (!defined('POSHY_ACTIVE_DB')) {
    define('POSHY_ACTIVE_DB', $activeDatabase ?: getCurrentDbName($conn));
}

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
