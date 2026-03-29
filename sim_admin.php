<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 is admin
$_SESSION['role'] = 'admin';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_order_status';
$_POST['order_id'] = 27;
$_POST['status'] = 'shipped';

require 'pages/admin/admin_panel.php';
