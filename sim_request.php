<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Mock a valid admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_order_status';
$_POST['order_id'] = 27;
$_POST['status'] = 'shipped';

// Load directly bypassing the top script if it has session redirects
require 'pages/admin/admin_panel.php';
