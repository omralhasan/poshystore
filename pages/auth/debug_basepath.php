<?php
// Debug test for BASE_PATH in pages/auth/
require_once __DIR__ . '/../../includes/language.php';

echo "BASE_PATH constant defined: " . (defined('BASE_PATH') ? 'YES' : 'NO') . "\n";
echo "BASE_PATH value: '" . (defined('BASE_PATH') ? BASE_PATH : 'UNDEFINED') . "'\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_url = 'signin.php?test=' . urlencode('value');
    echo "Test relative redirect: " . $test_url . "\n";
    header('Location: ' . $test_url);
    exit();
}
?>
