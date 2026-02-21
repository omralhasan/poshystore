<?php
/**
 * Poshy Store - Auto Deploy Webhook
 * 
 * Place this file in /var/www/html/ on the production server.
 * Trigger via: GET /deploy_webhook.php?token=YOUR_SECRET_TOKEN
 * 
 * Setup on production server once:
 *   1. Copy this file to /var/www/html/
 *   2. Make sure /var/www/html is a git repo (git status)
 *   3. Set DEPLOY_TOKEN below to a secret string
 */

// ─── Security ────────────────────────────────────────────────────────────────
define('DEPLOY_TOKEN', 'poshy_deploy_2026_secure');

header('Content-Type: application/json');

// Only allow GET or POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Validate token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== DEPLOY_TOKEN) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid token']));
}

// ─── Run git pull ─────────────────────────────────────────────────────────────
$web_root = '/var/www/html';
$output = [];
$return_code = 0;

// Make sure we're in the right directory
chdir($web_root);

// Run git reset and pull
$commands = [
    "git fetch origin 2>&1",
    "git reset --hard origin/main 2>&1",
];

$all_output = [];
$success = true;

foreach ($commands as $cmd) {
    $result = shell_exec($cmd);
    $all_output[] = ['cmd' => $cmd, 'output' => trim($result)];
    if (strpos($result, 'fatal') !== false || strpos($result, 'error') !== false) {
        $success = false;
    }
}

// Fix file permissions after pull
shell_exec("find $web_root -type f -name '*.php' -exec chmod 644 {} \; 2>&1");
shell_exec("find $web_root -type d -exec chmod 755 {} \; 2>&1");

// ─── Auto-run DB migrations ──────────────────────────────────────────────────
$db_migration_output = 'skipped';
$migration_file = $web_root . '/sql/migrate_bilingual_columns.sql';
if (file_exists($migration_file)) {
    // Load DB credentials from .env / config
    $env_file = $web_root . '/.env';
    $db_host = 'localhost'; $db_user = 'poshy_user';
    $db_pass = 'Poshy2026secure'; $db_name = 'poshy_db';
    if (file_exists($env_file)) {
        foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', trim($line), 2);
            if ($k === 'DB_HOST') $db_host = $v;
            if ($k === 'DB_USER') $db_user = $v;
            if ($k === 'DB_PASS') $db_pass = $v;
            if ($k === 'DB_NAME') $db_name = $v;
        }
    }
    $migration_cmd = "mysql -h$db_host -u$db_user -p$db_pass $db_name < $migration_file 2>&1";
    $db_migration_output = trim(shell_exec($migration_cmd));
    if (empty($db_migration_output)) $db_migration_output = 'migration ran OK';
}

// Get current git HEAD info
$head = trim(shell_exec("git log --oneline -1 2>&1"));

echo json_encode([
    'success'        => $success,
    'deployed'       => $head,
    'timestamp'      => date('Y-m-d H:i:s'),
    'db_migration'   => $db_migration_output,
    'details'        => $all_output,
], JSON_PRETTY_PRINT);
