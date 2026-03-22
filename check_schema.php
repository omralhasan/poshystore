<?php
require 'config.php';
require 'includes/db_connect.php';
$res1 = $conn->query("SHOW COLUMNS FROM subcategories");
while ($row = $res1->fetch_assoc()) {
    echo "Subcategory: " . $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "---\n";
$res2 = $conn->query("SHOW COLUMNS FROM products");
while ($row = $res2->fetch_assoc()) {
    echo "Product: " . $row['Field'] . " - " . $row['Type'] . "\n";
}
