<?php
/**
 * Redirect helper for OAuth callback
 * Redirects OAuth providers to the correct callback location
 */
header('Location: pages/auth/oauth_callback.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
?>