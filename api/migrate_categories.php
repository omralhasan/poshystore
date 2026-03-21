<?php
require_once '/home/omar/poshystore/config.php';
require_once '/home/omar/poshystore/includes/db_connect.php';

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
    if ($conn->query("ALTER TABLE categories ADD COLUMN image_path VARCHAR(500) DEFAULT NULL")) {
        echo "Successfully added image_path column to subcategories table.\n";
    }
}
