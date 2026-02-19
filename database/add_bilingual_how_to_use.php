<?php
/**
 * Migration: Add Bilingual How to Use Columns
 * Adds how_to_use_en and how_to_use_ar columns to products table
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "Starting migration for bilingual 'How to Use' columns...\n\n";

// Check if columns already exist
$check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_NAME = 'products' AND COLUMN_NAME IN ('how_to_use_en', 'how_to_use_ar')";
$check_result = $conn->query($check_sql);
$existing_columns = [];
while ($row = $check_result->fetch_assoc()) {
    $existing_columns[] = $row['COLUMN_NAME'];
}

// Add how_to_use_en if not exists
if (!in_array('how_to_use_en', $existing_columns)) {
    echo "Adding how_to_use_en column...\n";
    $sql = "ALTER TABLE products ADD COLUMN how_to_use_en TEXT NULL AFTER how_to_use";
    if ($conn->query($sql)) {
        echo "✓ how_to_use_en column added\n";
    } else {
        echo "✗ Error adding how_to_use_en: " . $conn->error . "\n";
    }
} else {
    echo "⚠ how_to_use_en already exists\n";
}

// Add how_to_use_ar if not exists
if (!in_array('how_to_use_ar', $existing_columns)) {
    echo "Adding how_to_use_ar column...\n";
    $sql = "ALTER TABLE products ADD COLUMN how_to_use_ar TEXT NULL AFTER how_to_use_en";
    if ($conn->query($sql)) {
        echo "✓ how_to_use_ar column added\n";
    } else {
        echo "✗ Error adding how_to_use_ar: " . $conn->error . "\n";
    }
} else {
    echo "⚠ how_to_use_ar already exists\n";
}

// Verify columns
$verify_sql = "SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'products' AND COLUMN_NAME LIKE 'how_to_use%'
              ORDER BY ORDINAL_POSITION";
$verify_result = $conn->query($verify_sql);

echo "\n=== Final Column Structure ===\n";
while ($row = $verify_result->fetch_assoc()) {
    echo $row['COLUMN_NAME'] . " (" . $row['COLUMN_TYPE'] . ")\n";
}

echo "\nMigration completed!\n";
?>
