<?php
/**
 * Points Conversion API
 * Handles converting points to wallet balance
 * 
 * POST endpoint that accepts:
 * - points_to_convert: Number of points to convert
 * 
 * Returns JSON response
 */

session_start();
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/points_wallet_handler.php';

header('Content-Type: application/json');

// Check if user is logged in
$user_id = getCurrentUserId();
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to convert points'
    ]);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method. Use POST.'
    ]);
    exit;
}

// Get points amount from POST data
$points_to_convert = isset($_POST['points_to_convert']) ? intval($_POST['points_to_convert']) : 0;

if ($points_to_convert <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a valid number of points to convert'
    ]);
    exit;
}

// Perform the conversion
$result = convertPointsToWallet($user_id, $points_to_convert);

echo json_encode($result);
