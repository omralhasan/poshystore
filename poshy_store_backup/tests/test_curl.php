<?php
// Test PHP curl connectivity to Google
header('Content-Type: text/plain');

echo "Testing PHP CURL connectivity to Google OAuth...\n\n";

// Test 1: Simple GET request
echo "Test 1: GET request to Google\n";
echo str_repeat("-", 50) . "\n";
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status: " . ($result ? "SUCCESS" : "FAILED") . "\n";
echo "HTTP Code: " . $info['http_code'] . "\n";
if ($error) echo "Error: $error\n";
echo "\n";

// Test 2: Connect to OAuth endpoint
echo "Test 2: OAuth token endpoint\n";
echo str_repeat("-", 50) . "\n";
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
$result = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status: " . ($result ? "SUCCESS" : "FAILED") . "\n";
echo "HTTP Code: " . $info['http_code'] . "\n";
if ($error) echo "Error: $error\n";
if ($result) echo "Response: " . substr($result, 0, 200) . "\n";
echo "\n";

// Test 3: With SSL verification disabled (NOT recommended for production)
echo "Test 3: With SSL verification disabled\n";
echo str_repeat("-", 50) . "\n";
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status: " . ($result ? "SUCCESS" : "FAILED") . "\n";
echo "HTTP Code: " . $info['http_code'] . "\n";
if ($error) echo "Error: $error\n";
if ($result) echo "Response: " . substr($result, 0, 200) . "\n";
echo "\n";

// Show PHP curl info
echo "PHP CURL Information:\n";
echo str_repeat("-", 50) . "\n";
$curl_info = curl_version();
echo "Version: " . $curl_info['version'] . "\n";
echo "SSL Version: " . $curl_info['ssl_version'] . "\n";
echo "OpenSSL support: " . (isset($curl_info['features']) && ($curl_info['features'] & CURL_VERSION_SSL) ? 'YES' : 'NO') . "\n";

// Check CA bundle
$ca_bundle = ini_get('curl.cainfo');
echo "\nCA Bundle location: " . ($ca_bundle ?: 'Not set') . "\n";
if ($ca_bundle && file_exists($ca_bundle)) {
    echo "CA Bundle exists: YES\n";
} else {
    echo "CA Bundle exists: NO (This is the problem!)\n";
}

echo "\nAlternative CA path: " . (ini_get('openssl.cafile') ?: 'Not set') . "\n";
?>
