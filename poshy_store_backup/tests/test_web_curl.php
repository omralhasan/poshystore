<?php
// Test DNS and connectivity from web server context
header('Content-Type: text/plain');

echo "Web Server PHP Environment Test\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: DNS Resolution
echo "Test 1: DNS Resolution\n";
echo str_repeat("-", 50) . "\n";
$host = 'oauth2.googleapis.com';
$ip = gethostbyname($host);
echo "Host: $host\n";
echo "Resolved IP: $ip\n";
echo "DNS Status: " . ($ip !== $host ? "SUCCESS" : "FAILED") . "\n\n";

// Test 2: Check if curl can resolve DNS
echo "Test 2: CURL DNS Resolution\n";
echo str_repeat("-", 50) . "\n";
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
$result = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

echo "CURL errno: $errno\n";
echo "CURL error: $error\n";
echo "HTTP code: " . $info['http_code'] . "\n";
echo "Primary IP: " . ($info['primary_ip'] ?? 'N/A') . "\n";
echo "Total time: " . ($info['total_time'] ?? 'N/A') . "s\n";
echo "Name lookup time: " . ($info['namelookup_time'] ?? 'N/A') . "s\n";
echo "Connect time: " . ($info['connect_time'] ?? 'N/A') . "s\n\n";

// Test 3: Try with IP directly (if DNS worked)
if ($ip !== $host) {
    echo "Test 3: Connect using direct IP\n";
    echo str_repeat("-", 50) . "\n";
    $ch = curl_init("https://$ip/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: oauth2.googleapis.com']);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "CURL errno: $errno\n";
    echo "CURL error: $error\n";
    echo "HTTP code: $code\n\n";
}

// Test 4: Check network functions availability
echo "Test 4: PHP Network Configuration\n";
echo str_repeat("-", 50) . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'YES' : 'NO') . "\n";
echo "file_get_contents enabled: " . (function_exists('file_get_contents') ? 'YES' : 'NO') . "\n";
echo "curl enabled: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo "openssl enabled: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n\n";

// Test 5: Try file_get_contents as alternative
echo "Test 5: file_get_contents method\n";
echo str_repeat("-", 50) . "\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => 'test=1',
        'timeout' => 5
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true
    ]
]);

$result = @file_get_contents('https://oauth2.googleapis.com/token', false, $context);
$error = error_get_last();
echo "Status: " . ($result ? "SUCCESS" : "FAILED") . "\n";
if ($error) {
    echo "Error: " . $error['message'] . "\n";
}
if ($result) {
    echo "Response: " . substr($result, 0, 200) . "\n";
}
echo "\n";

// Test 6: System information
echo "Test 6: System Information\n";
echo str_repeat("-", 50) . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "User: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n";
echo "Open basedir: " . (ini_get('open_basedir') ?: 'Not restricted') . "\n";
echo "Disable functions: " . (ini_get('disable_functions') ?: 'None') . "\n";
?>
