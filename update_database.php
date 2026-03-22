<?php
/**
 * One-click Database Migration for Poshy Store
 * Runs the necessary ALTER commands to add the new image_url and is_new_arrival columns.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db_connect.php';

echo "<h1>Running Database Migration...</h1>";

$queries = [
    // 1. Add image_url to subcategories if it doesn't exist
    "ALTER TABLE subcategories ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER icon",
    
    // 2. Add is_new_arrival to products if it doesn't exist
    "ALTER TABLE products ADD COLUMN is_new_arrival TINYINT(1) DEFAULT 0 AFTER has_discount"
];

foreach ($queries as $i => $query) {
    try {
        if ($conn->query($query)) {
            echo "<p style='color: green;'>Success: Query " . ($i + 1) . " executed.</p>";
        } else {
            echo "<p style='color: orange;'>Skipped (maybe already exists): Query " . ($i + 1) . " did not execute. Note: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>Skipped: Column already exists for Query " . ($i + 1) . ".</p>";
        } else {
            echo "<p style='color: red;'>Error on Query " . ($i + 1) . ": " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h2>Migration attempt complete. The 500 error should be fixed now. You can safely delete this file.</h2>";
?>
