<?php
/**
 * Run homepage banners migration
 * Creates the homepage_banners table and uploads/banners directory
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Create the homepage_banners table
$sql = file_get_contents(__DIR__ . '/sql/setup_homepage_banners.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "✅ homepage_banners table created successfully.\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

// Create uploads/banners directory
$banners_dir = __DIR__ . '/uploads/banners';
if (!is_dir($banners_dir)) {
    if (mkdir($banners_dir, 0755, true)) {
        echo "✅ uploads/banners/ directory created.\n";
    } else {
        echo "❌ Failed to create uploads/banners/ directory.\n";
    }
} else {
    echo "✅ uploads/banners/ directory already exists.\n";
}

echo "\nDone!\n";
