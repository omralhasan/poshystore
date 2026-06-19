<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
if ($review_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid review ID']);
    exit;
}

try {
    $stmt = $conn->prepare('DELETE FROM product_reviews WHERE id = ?');
    $stmt->bind_param('i', $review_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'Review not found or already deleted']);
    }
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
