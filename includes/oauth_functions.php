<?php
/**
 * OAuth Functions for Social Login
 * Handles Google and Facebook authentication
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth_functions.php';

// Enable error logging to custom file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/oauth_debug.log');

/**
 * Write OAuth debug info without breaking the authentication flow.
 */
function appendOAuthDebug($file_path, $content) {
    if (@file_put_contents($file_path, $content, FILE_APPEND) === false) {
        error_log("appendOAuthDebug: Unable to write debug file: $file_path");
    }
}

/**
 * Generate OAuth authorization URL
 */
function getOAuthURL($provider) {
    $config = require __DIR__ . '/oauth_config.php';
    
    if (!isset($config[$provider])) {
        return null;
    }
    
    $params = [
        'response_type' => 'code',
        'state' => generateStateToken($provider)
    ];
    
    switch ($provider) {
        case 'google':
            $params['client_id'] = $config['google']['client_id'];
            $params['redirect_uri'] = $config['google']['redirect_uri'];
            $params['scope'] = $config['google']['scope'];
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
            return $config['google']['auth_url'] . '?' . http_build_query($params);
            
        case 'facebook':
            $params['client_id'] = $config['facebook']['app_id'];
            $params['redirect_uri'] = $config['facebook']['redirect_uri'];
            $params['scope'] = $config['facebook']['scope'];
            return $config['facebook']['auth_url'] . '?' . http_build_query($params);
            
        /*
        // Apple Sign-In (disabled)
        case 'apple':
            $params['client_id'] = $config['apple']['client_id'];
            $params['redirect_uri'] = $config['apple']['redirect_uri'];
            $params['scope'] = $config['apple']['scope'];
            $params['response_mode'] = 'form_post';
            return $config['apple']['auth_url'] . '?' . http_build_query($params);
        */
    }
    
    return null;
}

/**
 * Generate and store state token for CSRF protection
 */
function generateStateToken($provider) {
    $random = bin2hex(random_bytes(32));
    // Encode provider into the state token: provider|random
    $state = base64_encode($provider . '|' . $random);
    
    // Store multiple provider states in session
    if (!isset($_SESSION['oauth_states'])) {
        $_SESSION['oauth_states'] = [];
    }
    $_SESSION['oauth_states'][$state] = [
        'provider' => $provider,
        'timestamp' => time()
    ];
    
    error_log("Generated state token for $provider: $state");
    return $state;
}

/**
 * Verify state token
 */
function verifyStateToken($state) {
    $config = require __DIR__ . '/oauth_config.php';
    
    // Debug logging
    error_log("=== OAuth State Verification ===");
    error_log("Received state: $state");
    error_log("Session states: " . json_encode($_SESSION['oauth_states'] ?? []));
    error_log("Session ID: " . session_id());
    
    // If state verification is disabled in config, skip check
    if (!$config['enable_state_verification']) {
        // Still need to extract provider from state
        $decoded = base64_decode($state);
        if ($decoded && strpos($decoded, '|') !== false) {
            list($provider, $random) = explode('|', $decoded, 2);
            $_SESSION['oauth_provider'] = $provider;
            error_log("State verification DISABLED - extracted provider: $provider");
        }
        return true;
    }
    
    if (empty($state) || !isset($_SESSION['oauth_states'][$state])) {
        error_log("State verification FAILED - state not found in session");
        return false;
    }
    
    // Extract provider from stored state data
    $_SESSION['oauth_provider'] = $_SESSION['oauth_states'][$state]['provider'];
    
    error_log("State verification PASSED - provider: " . $_SESSION['oauth_provider']);
    
    // Clean up old states (older than 10 minutes)
    $now = time();
    foreach ($_SESSION['oauth_states'] as $key => $data) {
        if ($now - $data['timestamp'] > 600) {
            unset($_SESSION['oauth_states'][$key]);
        }
    }
    
    return true;
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken($provider, $code) {
    $config = require __DIR__ . '/oauth_config.php';
    
    error_log("=== Token Exchange for $provider ===");
    
    if (!isset($config[$provider])) {
        error_log("ERROR: Provider $provider not found in config");
        return null;
    }
    
    $post_data = [
        'code' => $code,
        'grant_type' => 'authorization_code'
    ];
    
    switch ($provider) {
        case 'google':
            $post_data['client_id'] = $config['google']['client_id'];
            $post_data['client_secret'] = $config['google']['client_secret'];
            $post_data['redirect_uri'] = $config['google']['redirect_uri'];
            $token_url = $config['google']['token_url'];
            error_log("Google token URL: $token_url");
            error_log("Google redirect_uri: " . $config['google']['redirect_uri']);
            break;
            
        case 'facebook':
            $post_data['client_id'] = $config['facebook']['app_id'];
            $post_data['client_secret'] = $config['facebook']['app_secret'];
            $post_data['redirect_uri'] = $config['facebook']['redirect_uri'];
            $token_url = $config['facebook']['token_url'];
            break;
            
        /*
        // Apple Sign-In (disabled)
        case 'apple':
            $post_data['client_id'] = $config['apple']['client_id'];
            $post_data['client_secret'] = generateAppleClientSecret($config['apple']);
            $post_data['redirect_uri'] = $config['apple']['redirect_uri'];
            $token_url = $config['apple']['token_url'];
            break;
        */
            
        default:
            error_log("ERROR: Unknown provider $provider");
            return null;
    }
    
    error_log("Sending token request...");
    
    $response = '';
    $http_code = 0;
    $curl_error = '';
    $curl_errno = 0;

    // Try cURL first when available.
    if (function_exists('curl_init')) {
        $ch = curl_init($token_url);

        if ($ch !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
        } else {
            $curl_errno = -1;
            $curl_error = 'Failed to initialize cURL';
        }
    } else {
        $curl_errno = -2;
        $curl_error = 'cURL extension not available';
        error_log("Token exchange: $curl_error. Trying stream fallback if available.");
    }

    error_log("HTTP response code: $http_code");
    error_log("Response body: " . ($response === false ? 'false' : $response));

    // If cURL failed (or unavailable), try file_get_contents as fallback.
    if (($curl_errno !== 0 || $response === false || $response === '') && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
        error_log("Primary token request failed, trying file_get_contents fallback...");

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                           "Accept: application/json\r\n",
                'content' => http_build_query($post_data),
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        $fallback_response = @file_get_contents($token_url, false, $context);
        if ($fallback_response !== false) {
            $response = $fallback_response;
            $http_code = 0;

            if (isset($http_response_header[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches)) {
                $http_code = (int)$matches[1];
            }

            error_log("file_get_contents succeeded. HTTP code: $http_code");
            $curl_error = '';
            $curl_errno = 0;
        } else {
            $last_error = error_get_last();
            $curl_error = $last_error ? $last_error['message'] : 'Unknown error with file_get_contents';
            error_log("file_get_contents fallback failed: $curl_error");
        }
    }
    
    if ($curl_error) {
        error_log("CURL Error ($curl_errno): $curl_error");
    }
    
    // Validate response
    if (empty($response)) {
        error_log("ERROR: Empty response from token endpoint (HTTP $http_code, curl errno: $curl_errno)");
        return ['error' => 'empty_response', 'error_description' => 'Empty response from OAuth provider'];
    }
    
    // Try to decode JSON response
    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("ERROR: Invalid JSON response from token endpoint: " . json_last_error_msg());
        error_log("Raw response: " . substr($response, 0, 500)); // Log first 500 chars
        return ['error' => 'invalid_json', 'error_description' => 'Invalid JSON from OAuth provider'];
    }
    
    // Write detailed debug info to a separate file
    $debug_file = __DIR__ . '/oauth_token_debug.txt';
    $debug_info = date('Y-m-d H:i:s') . " - Token Exchange Debug\n";
    $debug_info .= "Provider: $provider\n";
    $debug_info .= "Token URL: $token_url\n";
    $debug_info .= "HTTP Code: $http_code\n";
    $debug_info .= "CURL errno: $curl_errno\n";
    $debug_info .= "POST Data: " . json_encode($post_data, JSON_PRETTY_PRINT) . "\n";
    $debug_info .= "Response (first 1000 chars): " . substr($response, 0, 1000) . "\n";
    $debug_info .= "CURL Error: $curl_error\n";
    $debug_info .= str_repeat("-", 80) . "\n\n";
    appendOAuthDebug($debug_file, $debug_info);
    
    return $decoded;
}

/**
 * Get user info from OAuth provider
 */
function getOAuthUserInfo($provider, $access_token) {
    $config = require __DIR__ . '/oauth_config.php';
    
    switch ($provider) {
        case 'google':
            $url = $config['google']['user_info_url'];
            $headers = ['Authorization: Bearer ' . $access_token];
            break;
            
        case 'facebook':
            $url = $config['facebook']['user_info_url'] . '?fields=id,name,email,picture&access_token=' . $access_token;
            $headers = [];
            break;
            
        /*
        // Apple Sign-In (disabled)
        case 'apple':
            // Apple sends user info with the initial response
            // Decode the id_token JWT
            return decodeAppleIdToken($access_token);
        */
            
        default:
            error_log("getOAuthUserInfo: Unknown provider - $provider");
            return null;
    }
    
    try {
        $response = '';
        $http_code = 0;
        $curl_error = '';
        $curl_errno = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                $curl_errno = curl_errno($ch);
                curl_close($ch);
            } else {
                $curl_errno = -1;
                $curl_error = 'Failed to initialize cURL';
            }
        } else {
            $curl_errno = -2;
            $curl_error = 'cURL extension not available';
        }

        // Fallback for servers without cURL.
        if (($curl_errno !== 0 || $response === false || $response === '') && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
            $header_lines = "Accept: application/json\r\n";
            foreach ($headers as $header) {
                $header_lines .= $header . "\r\n";
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => $header_lines,
                    'timeout' => 30,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true
                ]
            ]);

            $fallback_response = @file_get_contents($url, false, $context);
            if ($fallback_response !== false) {
                $response = $fallback_response;
                $http_code = 0;

                if (isset($http_response_header[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches)) {
                    $http_code = (int)$matches[1];
                }

                $curl_error = '';
                $curl_errno = 0;
            } else {
                $last_error = error_get_last();
                $curl_error = $last_error ? $last_error['message'] : $curl_error;
            }
        }

        if ($http_code === 0 && !empty($response)) {
            $http_code = 200;
        }

        if ($curl_errno !== 0) {
            error_log("getOAuthUserInfo request error ($curl_errno): $curl_error for $provider");
            return null;
        }

        if ($http_code !== 200) {
            error_log("getOAuthUserInfo: HTTP $http_code from $provider - Response: " . substr((string)$response, 0, 500));
            return null;
        }

        if (empty($response)) {
            error_log("getOAuthUserInfo: Empty response from $provider");
            return null;
        }

        $user_data = json_decode($response, true);
        if ($user_data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("getOAuthUserInfo: Invalid JSON from $provider - Error: " . json_last_error_msg());
            return null;
        }

        return $user_data;
    } catch (Throwable $e) {
        error_log("getOAuthUserInfo Exception: " . $e->getMessage());
        return null;
    }
}

/**
 * Create or update user from OAuth data
 */
function processOAuthUser($provider, $oauth_data) {
    global $conn;
    
    try {
    // Extract user info based on provider
    switch ($provider) {
        case 'google':
            $oauth_id = $oauth_data['id'] ?? null;
            $email = $oauth_data['email'] ?? null;
            $firstname = $oauth_data['given_name'] ?? '';
            $lastname = $oauth_data['family_name'] ?? '';
            $profile_picture = $oauth_data['picture'] ?? null;
            break;
            
        case 'facebook':
            $oauth_id = $oauth_data['id'];
            $email = $oauth_data['email'] ?? null;
            $name_parts = explode(' ', $oauth_data['name'], 2);
            $firstname = $name_parts[0] ?? '';
            $lastname = $name_parts[1] ?? '';
            $profile_picture = $oauth_data['picture']['data']['url'] ?? null;
            break;
            
        /*
        // Apple Sign-In (disabled)
        case 'apple':
            $oauth_id = $oauth_data['sub'];
            $email = $oauth_data['email'] ?? null;
            $firstname = $oauth_data['given_name'] ?? 'Apple';
            $lastname = $oauth_data['family_name'] ?? 'User';
            $profile_picture = null;
            break;
        */
            
        default:
            error_log("processOAuthUser: Invalid provider - $provider");
            return ['success' => false, 'error' => 'Invalid OAuth provider'];
    }
    
    if (!$oauth_id) {
        error_log("processOAuthUser: No OAuth ID from $provider");
        return ['success' => false, 'error' => 'No OAuth ID received from provider'];
    }
    
    if (!$email) {
        error_log("processOAuthUser: No email from $provider");
        return ['success' => false, 'error' => 'Email not provided by OAuth provider'];
    }
    
    // Validate database connection
    if (!$conn || !$conn->ping()) {
        error_log("processOAuthUser: Database connection lost");
        return ['success' => false, 'error' => 'Database connection error. Please try again.'];
    }
    
    // Check if user exists with this OAuth provider
    $check_sql = "SELECT id, firstname, lastname, email, role, profile_picture 
                  FROM users 
                  WHERE oauth_provider = ? AND oauth_id = ?";
    $stmt = $conn->prepare($check_sql);
    if (!$stmt) {
        error_log("processOAuthUser prepare error: " . $conn->error);
        return ['success' => false, 'error' => 'Database error'];
    }
    
    $stmt->bind_param('ss', $provider, $oauth_id);
    if (!$stmt->execute()) {
        error_log("processOAuthUser execute error: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'error' => 'Database error'];
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, update and login
        $user = $result->fetch_assoc();
        $stmt->close();
        
        error_log("processOAuthUser: Existing user - " . $user['email']);
        
        // Update profile picture if available
        if ($profile_picture) {
            $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param('si', $profile_picture, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        return [
            'success' => true,
            'user' => $user,
            'is_new_user' => false
        ];
    }
    
    $stmt->close();
    
    // Check if email already exists (regular account)
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_sql);
    if (!$stmt) {
        error_log("processOAuthUser email check prepare error: " . $conn->error);
        return ['success' => false, 'error' => 'Database error'];
    }
    
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) {
        error_log("processOAuthUser email check execute error: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'error' => 'Database error'];
    }
    
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        error_log("processOAuthUser: Email already exists - $email");
        return [
            'success' => false,
            'error' => 'An account with this email already exists. Please sign in with your email and password.'
        ];
    }
    $stmt->close();
    
    // Create new user
    error_log("processOAuthUser: Creating new user - $email from $provider");
    $insert_sql = "INSERT INTO users (firstname, lastname, email, oauth_provider, oauth_id, profile_picture, role, password) 
                   VALUES (?, ?, ?, ?, ?, ?, 'customer', NULL)";
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        error_log("processOAuthUser insert prepare error: " . $conn->error);
        return ['success' => false, 'error' => 'Failed to create account'];
    }
    
    $stmt->bind_param('ssssss', $firstname, $lastname, $email, $provider, $oauth_id, $profile_picture);
    
    if (!$stmt->execute()) {
        error_log("processOAuthUser insert execute error: " . $stmt->error);
        $stmt->close();
        return ['success' => false, 'error' => 'Failed to create account'];
    }
    
    $user_id = $stmt->insert_id;
    $stmt->close();
    
    error_log("processOAuthUser: New user created - ID $user_id, Email: $email");
    
    return [
        'success' => true,
        'user' => [
            'id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'role' => 'customer',
            'profile_picture' => $profile_picture
        ],
        'is_new_user' => true
    ];
    
    } catch (Throwable $e) {
        error_log("processOAuthUser Exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'error' => 'Authentication service error'];
    }
}

/**
 * Generate Apple client secret (JWT)
 */
function generateAppleClientSecret($apple_config) {
    // This is a simplified version. In production, use a proper JWT library
    // For now, return a placeholder
    return 'APPLE_CLIENT_SECRET_PLACEHOLDER';
}

/**
 * Decode Apple ID token
 */
function decodeAppleIdToken($id_token) {
    // This is a simplified version. In production, verify the JWT signature
    $parts = explode('.', $id_token);
    if (count($parts) !== 3) {
        return null;
    }
    
    $payload = json_decode(base64_decode($parts[1]), true);
    return $payload;
}

/**
 * Login user with OAuth
 */
function loginWithOAuth($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['firstname'] = $user_data['firstname'];
    $_SESSION['lastname'] = $user_data['lastname'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['profile_picture'] = $user_data['profile_picture'] ?? null;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['oauth_login'] = true;
}
