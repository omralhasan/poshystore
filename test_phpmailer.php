<?php
/**
 * Brevo Email API test – delete after testing
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';

echo "<pre>\n";

$brevo_api_key = getenv('BREVO_API_KEY') ?: '';

$payload = json_encode([
    'sender' => ['name' => 'Poshy Store', 'email' => 'mate7762s@gmail.com'],
    'to' => [['email' => 'mate7762s@gmail.com', 'name' => 'Test']],
    'subject' => 'Poshy Store - Test Email',
    'htmlContent' => '<h1>Test Email</h1><p>If you see this, Brevo API works!</p>',
    'textContent' => 'Test email from Poshy Store'
]);

echo "Sending via Brevo API...\n";

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'api-key: ' . $brevo_api_key,
        'content-type: application/json'
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response: $response\n";
if ($curl_err) echo "cURL Error: $curl_err\n";

if ($http_code >= 200 && $http_code < 300) {
    echo "\nRESULT: SUCCESS - Email sent!\n";
} else {
    echo "\nRESULT: FAILED\n";
}
echo "</pre>";
