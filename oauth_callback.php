<?php
/**
 * Redirect helper for OAuth callback
 * Redirects OAuth providers to the correct callback location
 */

require_once __DIR__ . '/config.php';

$base_path = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$target = $base_path . '/pages/auth/oauth_callback.php';
$query = $_SERVER['QUERY_STRING'] ?? '';

if ($query !== '') {
	$target .= '?' . $query;
}

header('Location: ' . $target);
exit;
?>