<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('isRTL')) { require_once __DIR__ . '/language.php'; }
$lang = $_SESSION['language'] ?? 'en';
$baseHref = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/' : '/';
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminPage = str_contains($scriptPath, '/pages/admin/');
?>
<?php if (!$isAdminPage): ?>
<base href="<?= htmlspecialchars($baseHref) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">

<?php if ($lang === 'ar'): ?>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" as="style">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<?php else: ?>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<?php if ($lang === 'ar'): ?>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@300;400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<?php else: ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<?php endif; ?>

<link rel="stylesheet" href="<?= rtrim($base_path, '/') . "/assets/css/theme.min.css" ?>">
