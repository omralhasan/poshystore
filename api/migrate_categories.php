<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

echo "Migrating DB...\n";

$res1 = $conn->query("SHOW COLUMNS FROM categories LIKE 'image_path'");
if ($res1 && $res1->num_rows === 0) {
    if ($conn->query("ALTER TABLE categories ADD COLUMN image_path VARCHAR(500) DEFAULT NULL")) {
        echo "Successfully added image_path column to categories table.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "Column image_path already exists in categories table.\n";
}

$res2 = $conn->query("SHOW COLUMNS FROM subcategories LIKE 'image_path'");
if ($res2 && $res2->num_rows === 0) {
    if ($conn->query("ALTER TABLE subcategories ADD COLUMN image_path VARCHAR(500) DEFAULT NULL")) {
        echo "Successfully added image_path column to subcategories table.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "Column image_path already exists in subcategories table.\n";
}

// Ensure the category image upload directory exists
$upload_dir = __DIR__ . '/../uploads/categories';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "Created uploads/categories directory.\n";
}

echo "Done.\n";
