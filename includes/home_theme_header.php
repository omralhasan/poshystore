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

<style id="baby-pink-global-theme">
	:root {
		--baby-pink-bg: #fff5f8 !important;
		--baby-pink-surface: #ffeaf1 !important;
		--baby-pink-accent: #f6a9c2 !important;
		--baby-pink-accent-dark: #e67ca3 !important;
		--baby-pink-text: #4b2d39 !important;
		--baby-pink-border: rgba(230, 124, 163, 0.28) !important;

		--bb-bg: var(--baby-pink-bg) !important;
		--bb-surface: var(--baby-pink-surface) !important;
		--bb-charcoal: var(--baby-pink-text) !important;
		--bb-text: var(--baby-pink-text) !important;
		--bb-text-secondary: #6a4553 !important;
		--bb-text-muted: #8f6977 !important;
		--bb-border: var(--baby-pink-border) !important;
		--bb-border-hover: rgba(230, 124, 163, 0.45) !important;
		--bb-gold: var(--baby-pink-accent-dark) !important;
		--bb-gold-soft: var(--baby-pink-accent) !important;
		--bb-gold-bg: rgba(246, 169, 194, 0.2) !important;
		--bb-rose: var(--baby-pink-accent) !important;
		--bb-rose-soft: var(--baby-pink-surface) !important;

		--primary: var(--baby-pink-accent-dark) !important;
		--primary-light: var(--baby-pink-accent) !important;
		--primary-dark: #d96595 !important;
		--secondary-dark: #f9bfd4 !important;

		--accent: var(--baby-pink-accent) !important;
		--accent-light: #ffd4e4 !important;
		--accent-dark: var(--baby-pink-accent-dark) !important;
		--accent-blue: var(--baby-pink-accent-dark) !important;
		--accent-teal: #f9bfd4 !important;
		--accent-purple: #e186ad !important;
		--accent-gold: var(--baby-pink-accent-dark) !important;
		--accent-rose: var(--baby-pink-accent) !important;

		--surface: #fff9fc !important;
		--surface-alt: var(--baby-pink-surface) !important;
		--surface-hover: #ffe2ee !important;
		--bg-light: #fff2f8 !important;

		--text-primary: var(--baby-pink-text) !important;
		--text-secondary: #6a4553 !important;
		--text-muted: #8f6977 !important;
		--text-dark: var(--baby-pink-text) !important;
		--text-gray: #8f6977 !important;

		--border: var(--baby-pink-border) !important;
		--border-light: rgba(230, 124, 163, 0.18) !important;
		--border-color: rgba(230, 124, 163, 0.32) !important;

		--purple-color: var(--baby-pink-accent-dark) !important;
		--purple-dark: #cf5f91 !important;
		--deep-purple: #d46c99 !important;
		--gold-color: var(--baby-pink-accent-dark) !important;
		--gold-light: #f8b8d0 !important;
	}

	html,
	body {
		background: linear-gradient(180deg, #fff8fc 0%, #ffe8f2 100%) !important;
		color: var(--baby-pink-text) !important;
	}

	.ramadan-navbar,
	.mobile-bottom-nav,
	.sidebar,
	.card,
	.card-ramadan,
	.table-container,
	.form-section,
	.stats-card,
	.stat-card {
		border-color: var(--baby-pink-border) !important;
	}

	.btn-ramadan,
	.btn-cart,
	.rewards-btn,
	.cat-chip.active {
		background: linear-gradient(135deg, var(--baby-pink-accent) 0%, var(--baby-pink-accent-dark) 100%) !important;
		border-color: var(--baby-pink-accent-dark) !important;
	}
</style>
