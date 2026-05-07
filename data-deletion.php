<?php
/**
 * Facebook Data Deletion Callback
 * Returns a confirmation code and status URL when signed_request is provided.
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$request_id = bin2hex(random_bytes(8));
$timestamp = gmdate('c');

$site_url = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$status_url = $site_url !== '' ? $site_url . '/data-deletion.php?code=' . $request_id : '';

// Lightweight request log (non-blocking).
$log_line = $timestamp . " request_id=" . $request_id . " method=" . $method . " ip=" . $remote_addr . "\n";
@file_put_contents(__DIR__ . '/logs/data_deletion.log', $log_line, FILE_APPEND);

function base64UrlDecode(string $input): string {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/')) ?: '';
}

// Handle Facebook signed_request callback.
$signed_request = $_POST['signed_request'] ?? $_GET['signed_request'] ?? '';
if ($signed_request !== '') {
    $parts = explode('.', $signed_request, 2);
    if (count($parts) === 2) {
        $encoded_sig = $parts[0];
        $payload = $parts[1];

        $decoded_sig = base64UrlDecode($encoded_sig);
        $data_json = base64UrlDecode($payload);
        $data = json_decode($data_json, true);

        $config = require __DIR__ . '/includes/oauth_config.php';
        $app_secret = $config['facebook']['app_secret'] ?? '';

        $expected_sig = '';
        if ($app_secret !== '') {
            $expected_sig = hash_hmac('sha256', $payload, $app_secret, true);
        }

        $is_valid = $app_secret !== '' && hash_equals($expected_sig, $decoded_sig);
        if ($is_valid) {
            $response = [
                'url' => $status_url !== '' ? $status_url : '/data-deletion.php?code=' . $request_id,
                'confirmation_code' => $request_id
            ];
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($response);
            exit;
        }
    }
}

// Fallback HTML for manual testing or when signed_request is missing.
header('Content-Type: text/html; charset=UTF-8');
$code = htmlspecialchars($_GET['code'] ?? $request_id, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Deletion - Poshy Store</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #222; }
        .card { max-width: 720px; border: 1px solid #e5e5e5; border-radius: 8px; padding: 24px; }
        h1 { margin-top: 0; }
        code { background: #f6f6f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Data Deletion Request</h1>
        <p>Your request has been received. If you are here from Facebook's callback verification, this page is active.</p>
        <p>Confirmation code: <code><?= $code ?></code></p>
        <p>If you have questions, please contact support through the website.</p>
    </div>
</body>
</html>
