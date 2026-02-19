<?php
/**
 * Review Submission API
 * Handles adding/updating product reviews
 */

require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/product_manager.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to submit a review'
    ]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
$user_id = $_SESSION['user_id'];

// Validate inputs
if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid product ID'
    ]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'error' => 'Rating must be between 1 and 5'
    ]);
    exit;
}

if (empty($review_text)) {
    echo json_encode([
        'success' => false,
        'error' => 'Review text is required'
    ]);
    exit;
}

// Add/update review
$result = addProductReview($product_id, $user_id, $rating, $review_text);

echo json_encode($result);
