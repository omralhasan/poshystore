<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Check which tables reference the products table
$query = "SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME = 'products' 
AND TABLE_SCHEMA = 'poshy_db'";

$result = $conn->query($query);

echo "Tables with foreign keys to products:\n\n";
while ($row = $result->fetch_assoc()) {
    echo "Table: {$row['TABLE_NAME']}\n";
    echo "  Column: {$row['COLUMN_NAME']}\n";
    echo "  Constraint: {$row['CONSTRAINT_NAME']}\n";
    echo "  References: {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n\n";
}

// Check current product IDs
$query2 = "SELECT id FROM products ORDER BY id ASC";
$result2 = $conn->query($query2);

echo "\nCurrent product IDs:\n";
$ids = [];
while ($row = $result2->fetch_assoc()) {
    $ids[] = $row['id'];
}
echo implode(', ', $ids) . "\n";
echo "Total: " . count($ids) . " products\n";
echo "Min ID: " . min($ids) . ", Max ID: " . max($ids) . "\n";

$conn->close();
