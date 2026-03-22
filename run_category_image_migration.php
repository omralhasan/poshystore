<?php
/**
 * Migration: Add image_url to categories table
 * Run once via browser: https://poshystore.com/run_category_image_migration.php
 */
require_once __DIR__ . '/includes/db_connect.php';

echo "<h2>Category Image Migration</h2><pre>";

// Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM categories LIKE 'image_url'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'image_url' already exists in categories table.\n";
} else {
    $sql = "ALTER TABLE categories ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER name_ar";
    if ($conn->query($sql)) {
        echo "✅ Added 'image_url' column to categories table.\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

echo "</pre><p><a href='pages/admin/manage_categories.php'>Go to Manage Categories →</a></p>";
