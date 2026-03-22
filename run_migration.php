<?php
// Let's use db_connect.php from the app since it knows how to read .env
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/db_connect.php';

$queries = [
    "ALTER TABLE subcategories ADD COLUMN image_url VARCHAR(500) DEFAULT NULL;",
    "ALTER TABLE products ADD COLUMN is_new_arrival TINYINT(1) DEFAULT 0;"
];

foreach ($queries as $q) {
    if ($conn->query($q) === TRUE) {
        echo "Success: $q\n";
    } else {
        echo "Error: $q -> " . $conn->error . "\n";
    }
}
