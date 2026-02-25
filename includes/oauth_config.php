<?php
/**
 * OAuth Configuration for Poshy Lifestyle
 * 
 * Set up your OAuth credentials for Google, Facebook, and Apple
 * Get credentials from:
 * - Google: https://console.cloud.google.com/
 * - Facebook: https://developers.facebook.com/
 * - Apple: https://developer.apple.com/
 */

// Use central config for SITE_URL
if (!defined('POSHY_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

// Always use canonical domain for OAuth — redirect_uri MUST exactly match
// what is registered in Google Cloud Console / Facebook App Dashboard.
$oauth_domain = 'https://poshystore.com';

// Runtime SITE_URL used for non-OAuth links only
$site = defined('SITE_URL') ? SITE_URL : $oauth_domain;
$bp   = defined('BASE_PATH') ? BASE_PATH : '';

return [
    'google' => [
        'client_id' => '110749343273-9q2uvdsf6v86uqsuuodsmf6q0kccddr0.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri' => $oauth_domain . '/oauth_callback.php',  // must match Google Cloud Console
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scope' => 'openid email profile'
    ],
    
    'facebook' => [
        'app_id' => '1257016736289462',
        'app_secret' => '9754bc68520be46d248623117a3bb854',
        'redirect_uri' => $oauth_domain . '/oauth_callback.php',  // must match Facebook App Dashboard
        'auth_url' => 'https://www.facebook.com/v22.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v22.0/oauth/access_token',
        'user_info_url' => 'https://graph.facebook.com/v22.0/me',
        'scope' => 'email,public_profile'
    ],
    
    'apple' => [
        'client_id' => 'YOUR_APPLE_SERVICE_ID',
        'team_id' => 'YOUR_APPLE_TEAM_ID',
        'key_id' => 'YOUR_APPLE_KEY_ID',
        'private_key_path' => __DIR__ . '/apple_private_key.p8',
        'redirect_uri' => $oauth_domain . '/oauth_callback.php',
        'auth_url' => 'https://appleid.apple.com/auth/authorize',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'scope' => 'name email'
    ],
    
    // Session configuration
    'session_lifetime' => 3600 * 24 * 30, // 30 days
    
    // Security
    'enable_state_verification' => true, // Re-enable for security
];
