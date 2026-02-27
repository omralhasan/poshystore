<?php
/**
 * One-time migration: Ensure users.role supports 'supplier' value.
 * Access once via browser then DELETE this file.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: application/json');

// Check current column type
$result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
$col = $result->fetch_assoc();
$before = $col['Type'];

// Alter to VARCHAR(50) to support 'supplier' and any future roles
$ok = $conn->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'customer'");

$result2 = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
$col2 = $result2->fetch_assoc();
$after = $col2['Type'];

echo json_encode([
    'success' => $ok,
    'before' => $before,
    'after' => $after,
    'message' => $ok ? 'Migration complete. DELETE this file now.' : 'Migration failed: ' . $conn->error
], JSON_PRETTY_PRINT);
