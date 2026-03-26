<?php
/**
 * Migration: Add image_url column to subcategories table
 * Run once via browser: https://poshystore.com/run_subcategory_image_migration.php
 */

require_once 'includes/db_connect.php';

echo "<h2>Subcategory Image Migration</h2><pre>";

$check = $conn->query("SHOW COLUMNS FROM subcategories LIKE 'image_url'");

if ($check && $check->num_rows > 0) {
    echo "✅ Column 'image_url' already exists in subcategories table.\n";
} else {
    $sql = "ALTER TABLE subcategories ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER icon";
    
    if ($conn->query($sql)) {
        echo "✅ Added 'image_url' column to subcategories table.\n";
        echo "✅ Column type: VARCHAR(500)\n";
        echo "✅ Positioned after 'icon' column\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

echo "</pre><p><a href='pages/admin/manage_categories.php'>Go to Manage Categories →</a></p>";
