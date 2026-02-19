<?php
/**
 * OAuth Callback Handler
 * Handles callbacks from Google and Facebook OAuth
 */

// Configure session for OAuth compatibility
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_start();
require_once __DIR__ . '/../../includes/oauth_functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// For Apple, the response comes as POST
$code = $_GET['code'] ?? $_POST['code'] ?? '';
$state = $_GET['state'] ?? $_POST['state'] ?? '';
$error = $_GET['error'] ?? $_POST['error'] ?? '';

// Check for OAuth errors
if ($error) {
    $error_description = $_GET['error_description'] ?? 'OAuth authentication failed';
    header('Location: signin.php?error=' . urlencode($error_description));
    exit();
}

// Verify state token (CSRF protection)
if (!verifyStateToken($state)) {
    // Debug: Log session info
    error_log("State verification failed. Received: $state, Session: " . ($_SESSION['oauth_state'] ?? 'not set'));
    header('Location: signin.php?error=' . urlencode('Invalid state token. Please try again.'));
    exit();
}

// Get provider from session (stored when user clicked sign-in button)
$provider = $_SESSION['oauth_provider'] ?? '';

error_log("=== OAuth Callback Received ===");
error_log("Provider from session: $provider");
error_log("Code received: " . (empty($code) ? 'NO' : 'YES'));
error_log("State received: " . (empty($state) ? 'NO' : 'YES'));

if (!in_array($provider, ['google', 'facebook'])) {
    error_log("ERROR: Invalid provider - $provider");
    header('Location: signin.php?error=' . urlencode('Invalid OAuth provider: ' . $provider));
    exit();
}

// Check if code exists
if (empty($code)) {
    header('Location: signin.php?error=' . urlencode('No authorization code received'));
    exit();
}

// Exchange code for access token
error_log("Attempting token exchange for provider: $provider");
$token_response = exchangeCodeForToken($provider, $code);

error_log("Token response: " . json_encode($token_response));

if (!$token_response || !isset($token_response['access_token'])) {
    error_log("ERROR: OAuth token exchange failed for $provider");
    error_log("Response was: " . json_encode($token_response));
    
    // Display more detailed error if available
    $error_detail = '';
    if (isset($token_response['error'])) {
        $error_detail = ' - ' . $token_response['error'];
        if (isset($token_response['error_description'])) {
            $error_detail .= ': ' . $token_response['error_description'];
        }
    }
    
    header('Location: signin.php?error=' . urlencode('Failed to get access token from ' . ucfirst($provider) . $error_detail));
    exit();
}

$access_token = $token_response['access_token'];

// For Apple, use id_token instead
if ($provider === 'apple' && isset($token_response['id_token'])) {
    $access_token = $token_response['id_token'];
}

// Get user info from OAuth provider
$user_info = getOAuthUserInfo($provider, $access_token);

if (!$user_info) {
    error_log("Failed to get user info from $provider");
    header('Location: signin.php?error=' . urlencode('Failed to get user information from ' . ucfirst($provider)));
    exit();
}

// Process user (create or update)
$result = processOAuthUser($provider, $user_info);

if (!$result['success']) {
    header('Location: signin.php?error=' . urlencode($result['error']));
    exit();
}

// Login user
loginWithOAuth($result['user']);

// Redirect to home page
$redirect = ($result['user']['role'] === 'admin') ? '../admin/admin_panel.php' : '../../index.php';

if ($result['is_new_user']) {
    header('Location: ' . $redirect . '?welcome=1');
} else {
    header('Location: ' . $redirect);
}
exit();
