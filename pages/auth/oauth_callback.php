<?php
/**
 * OAuth Callback Handler
 * Handles callbacks from Google and Facebook OAuth
 */

// Configure session for OAuth compatibility
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log non-fatal warnings, but do not break OAuth flow for recoverable issues.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $fatal_like_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
    if (in_array($errno, $fatal_like_errors, true)) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    error_log("OAuth Callback Warning [$errno]: $errstr in $errfile:$errline");
    return true;
});

// Exception handler to catch thrown exceptions
set_exception_handler(function($exception) {
    error_log("OAuth Callback Exception: " . $exception->getMessage());
    error_log("Stack trace: " . $exception->getTraceAsString());

    if (!headers_sent()) {
        header('Location: signin.php?error=' . urlencode('Authentication service error. Please try again.'));
    } else {
        echo 'Authentication service error. Please try again.';
    }
    exit();
});

try {
    require_once __DIR__ . '/../../includes/oauth_functions.php';
    require_once __DIR__ . '/../../includes/auth_functions.php';

    // For Apple, the response comes as POST
    $code = $_GET['code'] ?? $_POST['code'] ?? '';
    $state = $_GET['state'] ?? $_POST['state'] ?? '';
    $error = $_GET['error'] ?? $_POST['error'] ?? '';

    // Check for OAuth errors
    if ($error) {
        $error_description = $_GET['error_description'] ?? 'OAuth authentication failed';
        error_log("OAuth provider returned error: $error - $error_description");
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
        error_log("ERROR: No authorization code received from $provider");
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
    error_log("Fetching user info from $provider");
    $user_info = getOAuthUserInfo($provider, $access_token);

    if (!$user_info) {
        error_log("Failed to get user info from $provider");
        header('Location: signin.php?error=' . urlencode('Failed to get user information from ' . ucfirst($provider)));
        exit();
    }

    error_log("User info received from $provider: " . json_encode($user_info));

    // Process user (create or update)
    error_log("Processing OAuth user...");
    $result = processOAuthUser($provider, $user_info);

    if (!is_array($result) || !array_key_exists('success', $result)) {
        error_log("Invalid response from processOAuthUser");
        header('Location: signin.php?error=' . urlencode('Authentication service error. Please try again.'));
        exit();
    }

    if (!$result['success']) {
        error_log("Failed to process OAuth user: " . $result['error']);
        header('Location: signin.php?error=' . urlencode($result['error']));
        exit();
    }

    // Login user
    error_log("Logging in user with OAuth...");
    loginWithOAuth($result['user']);

    // Redirect to home page
    $redirect = ($result['user']['role'] === 'admin') ? '../admin/admin_panel.php' : '../../index.php';

    if ($result['is_new_user']) {
        error_log("New user created, redirecting to: $redirect?welcome=1");
        header('Location: ' . $redirect . '?welcome=1');
    } else {
        error_log("Existing user logged in, redirecting to: $redirect");
        header('Location: ' . $redirect);
    }
    exit();

} catch (Throwable $e) {
    error_log("Caught exception in OAuth callback: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    header('Location: signin.php?error=' . urlencode('Authentication service error. Please try again.'));
    exit();
}
