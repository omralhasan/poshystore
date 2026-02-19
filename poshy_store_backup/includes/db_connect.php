<?php
/**
 * Database Connection File for Poshy Lifestyle E-Commerce
 * 
 * This file establishes a secure connection to the MySQL database
 * using mysqli with prepared statement support.
 * 
 * Database: poshy_lifestyle
 * User: poshy_user
 * Password: Poshy_Lifestyle_2026!
 */

// Load database configuration
$db_config = require_once __DIR__ . '/db_config.php';

// Create mysqli connection
$conn = new mysqli(
    $db_config['host'],
    $db_config['user'],
    $db_config['password'],
    $db_config['database']
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

// Set charset to UTF-8 for Arabic support
$conn->set_charset($db_config['charset']);

// Set timezone to Jordan
$conn->query("SET time_zone = '+03:00'");

/**
 * Function to safely close database connection
 */
function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
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
    return number_format($amount, 3, '.', ',') . ' JOD';
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
