<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
logoutUser();
header('Location: ../../index.php');
exit;
?>
