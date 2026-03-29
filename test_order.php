<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/pages/shop/checkout.php';

$res = $conn->query("SELECT status FROM orders WHERE id=27");
var_dump($res->fetch_assoc());

echo "Updating to processed/cancelled...\n";
$result = updateOrderStatus(27, 'cancelled');
var_dump($result);

$result = updateOrderStatus(27, 'shipped');
var_dump($result);
