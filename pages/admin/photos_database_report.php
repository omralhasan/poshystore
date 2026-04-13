<?php
/**
 * Photos Database Report (Arabic)
 * Comprehensive admin report for all image-related data in the database.
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);

function safe_identifier(string $name): bool
{
    return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

function table_exists(mysqli $conn, string $table): bool
{
    if (!safe_identifier($table)) {
        return false;
    }

    // Safe because identifier is restricted to [A-Za-z0-9_]
    $sql = "SHOW TABLES LIKE '" . $table . "'";
    $res = $conn->query($sql);
    if (!$res) {
        return false;
    }

    return $res->num_rows > 0;
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    if (!safe_identifier($table) || !safe_identifier($column)) {
        return false;
    }

    if (!table_exists($conn, $table)) {
        return false;
    }

    // Safe because identifier is restricted to [A-Za-z0-9_]
    $sql = "SHOW COLUMNS FROM `$table` LIKE '" . $column . "'";
    $res = $conn->query($sql);
    if (!$res) {
        return false;
    }

    return $res->num_rows > 0;
}

function scalar_int(mysqli $conn, string $sql): int
{
    $res = $conn->query($sql);
    if (!$res) {
        return 0;
    }

    $row = $res->fetch_row();
    return isset($row[0]) ? (int)$row[0] : 0;
}

function non_empty_count(mysqli $conn, string $table, string $column): int
{
    if (!column_exists($conn, $table, $column)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` IS NOT NULL AND TRIM(`$column`) <> ''";
    return scalar_int($conn, $sql);
}

function normalize_relative_path(string $path): string
{
    $path = rawurldecode($path);
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $path = preg_replace('#/+#', '/', $path);
    return (string)$path;
}

function choose_primary_filename(array $files): ?string
{
    foreach ($files as $file) {
        $base = pathinfo($file, PATHINFO_FILENAME);
        if ($base === '1') {
            return $file;
        }
    }
    return $files[0] ?? null;
}

function sync_product_photos_to_database(mysqli $conn, string $images_dir): array
{
    $stats = [
        'ok' => true,
        'message' => 'تمت مزامنة الصور بنجاح.',
        'products_scanned' => 0,
        'products_with_folder' => 0,
        'gallery_rows_inserted' => 0,
        'image_link_updated' => 0,
        'errors' => [],
    ];

    if (!table_exists($conn, 'products')) {
        $stats['ok'] = false;
        $dbName = '';
        $dbRes = $conn->query('SELECT DATABASE() AS db_name');
        if ($dbRes && ($dbRow = $dbRes->fetch_assoc())) {
            $dbName = (string)($dbRow['db_name'] ?? '');
        }
        $stats['message'] = 'جدول products غير موجود' . ($dbName !== '' ? (' في قاعدة البيانات: ' . $dbName) : '.');
        return $stats;
    }

    if (!is_dir($images_dir)) {
        $stats['ok'] = false;
        $stats['message'] = 'مجلد الصور غير موجود على السيرفر: ' . $images_dir;
        return $stats;
    }

    if (!function_exists('find_product_image_folder') || !function_exists('get_png_files')) {
        $stats['ok'] = false;
        $stats['message'] = 'دوال product_image_helper غير متاحة.';
        return $stats;
    }

    $create_sql = "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        sort_order INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product_id (product_id)
    )";
    if (!$conn->query($create_sql)) {
        $stats['ok'] = false;
        $stats['message'] = 'فشل إنشاء/التحقق من جدول product_images: ' . $conn->error;
        return $stats;
    }

    $existing = [];
    $max_sort = [];
    $first_image_by_product = [];

    $existing_res = $conn->query("SELECT product_id, image_path, sort_order FROM product_images ORDER BY product_id ASC, sort_order ASC, id ASC");
    if ($existing_res) {
        while ($row = $existing_res->fetch_assoc()) {
            $pid = (int)$row['product_id'];
            if ($pid <= 0) {
                continue;
            }
            $rel = normalize_relative_path((string)$row['image_path']);
            if ($rel === '') {
                continue;
            }

            $existing[$pid][$rel] = true;
            $sort = (int)($row['sort_order'] ?? 0);
            if (!isset($max_sort[$pid]) || $sort > $max_sort[$pid]) {
                $max_sort[$pid] = $sort;
            }
            if (!isset($first_image_by_product[$pid])) {
                $first_image_by_product[$pid] = $rel;
            }
        }
    }

    $insert_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
    $update_stmt = $conn->prepare("UPDATE products SET image_link = ? WHERE id = ?");

    if (!$insert_stmt || !$update_stmt) {
        $stats['ok'] = false;
        $stats['message'] = 'فشل تجهيز أوامر الإدخال/التحديث.';
        return $stats;
    }

    $products_res = $conn->query("SELECT id, name_en, image_link FROM products ORDER BY id ASC");
    if (!$products_res) {
        $stats['ok'] = false;
        $stats['message'] = 'فشل جلب المنتجات: ' . $conn->error;
        return $stats;
    }

    while ($product = $products_res->fetch_assoc()) {
        $stats['products_scanned']++;

        $pid = (int)($product['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }

        $name_en = trim((string)($product['name_en'] ?? ''));
        $current_image_link = trim((string)($product['image_link'] ?? ''));

        $folder = ($name_en !== '') ? find_product_image_folder($name_en, $images_dir) : null;
        $product_files = [];

        if (!empty($folder)) {
            $stats['products_with_folder']++;
            $folder_path = rtrim($images_dir, '/') . '/' . $folder;
            $files = get_png_files($folder_path);
            if (!empty($files)) {
                $product_files = $files;
                foreach ($files as $file) {
                    $rel = normalize_relative_path('images/' . $folder . '/' . $file);
                    if ($rel === '') {
                        continue;
                    }

                    if (!isset($existing[$pid][$rel])) {
                        $next_sort = ($max_sort[$pid] ?? 0) + 1;
                        $insert_stmt->bind_param('isi', $pid, $rel, $next_sort);
                        if ($insert_stmt->execute()) {
                            $stats['gallery_rows_inserted']++;
                            $existing[$pid][$rel] = true;
                            $max_sort[$pid] = $next_sort;
                            if (!isset($first_image_by_product[$pid])) {
                                $first_image_by_product[$pid] = $rel;
                            }
                        } else {
                            $stats['errors'][] = 'Insert product_images failed for product #' . $pid;
                        }
                    }
                }
            }
        }

        $is_empty_link = ($current_image_link === '' || strtolower($current_image_link) === 'null');
        if ($is_empty_link) {
            $new_primary = null;

            if (!empty($product_files) && !empty($folder)) {
                $primary_file = choose_primary_filename($product_files);
                if ($primary_file !== null) {
                    $new_primary = normalize_relative_path('images/' . $folder . '/' . $primary_file);
                }
            }

            if ($new_primary === null && isset($first_image_by_product[$pid])) {
                $new_primary = $first_image_by_product[$pid];
            }

            if (!empty($new_primary)) {
                $update_stmt->bind_param('si', $new_primary, $pid);
                if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                    $stats['image_link_updated']++;
                }
            }
        }
    }

    $insert_stmt->close();
    $update_stmt->close();

    return $stats;
}

require_once __DIR__ . '/../../includes/product_image_helper.php';

$available_databases = [];
$show_dbs = $conn->query('SHOW DATABASES');
if ($show_dbs) {
    while ($row = $show_dbs->fetch_row()) {
        $dbName = isset($row[0]) ? (string)$row[0] : '';
        if ($dbName === '') {
            continue;
        }
        $dbLower = strtolower($dbName);
        if (in_array($dbLower, ['information_schema', 'mysql', 'performance_schema', 'sys'], true)) {
            continue;
        }
        if (safe_identifier($dbName)) {
            $available_databases[] = $dbName;
        }
    }
}

$db_switch_message = '';
$requested_db = trim((string)($_REQUEST['db'] ?? ''));
if ($requested_db !== '' && safe_identifier($requested_db)) {
    if ($conn->select_db($requested_db)) {
        $db_switch_message = 'تم التبديل يدويًا إلى قاعدة البيانات: ' . $requested_db;
    } else {
        $db_switch_message = 'تعذر التبديل إلى قاعدة البيانات: ' . $requested_db;
    }
}

$active_database = '';
$db_name_res = $conn->query('SELECT DATABASE() AS db_name');
if ($db_name_res && ($db_row = $db_name_res->fetch_assoc())) {
    $active_database = (string)($db_row['db_name'] ?? '');
}

$sync_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_product_images'])) {
    $sync_result = sync_product_photos_to_database($conn, __DIR__ . '/../../images');
}

$schema_map = [
    ['table' => 'products', 'column' => 'image_link', 'desc' => 'الصورة الرئيسية للمنتج'],
    ['table' => 'products', 'column' => 'image_url', 'desc' => 'حقل قديم/بديل في بعض الصفحات الإدارية'],
    ['table' => 'product_images', 'column' => 'image_path', 'desc' => 'صور المعرض (Gallery) لكل منتج'],
    ['table' => 'product_option_values', 'column' => 'image', 'desc' => 'صورة خيار المنتج (Variant)'],
    ['table' => 'homepage_banners', 'column' => 'image_path', 'desc' => 'صورة بانر الصفحة الرئيسية (النمط الحالي)'],
    ['table' => 'homepage_banners', 'column' => 'image_url', 'desc' => 'صورة بانر بالنمط القديم'],
    ['table' => 'categories', 'column' => 'image_url', 'desc' => 'صورة التصنيف'],
    ['table' => 'subcategories', 'column' => 'image_url', 'desc' => 'صورة التصنيف الفرعي'],
    ['table' => 'podcasts', 'column' => 'main_photo', 'desc' => 'الصورة الرئيسية للبودكاست'],
    ['table' => 'podcast_images', 'column' => 'image_path', 'desc' => 'صور معرض البودكاست'],
];

foreach ($schema_map as &$entry) {
    $entry['exists'] = column_exists($conn, $entry['table'], $entry['column']);
    $entry['filled'] = $entry['exists'] ? non_empty_count($conn, $entry['table'], $entry['column']) : 0;
}
unset($entry);

$total_products = table_exists($conn, 'products') ? scalar_int($conn, "SELECT COUNT(*) FROM `products`") : 0;
$products_with_image_link = non_empty_count($conn, 'products', 'image_link');
$products_without_image_link = max(0, $total_products - $products_with_image_link);

$product_images_total = column_exists($conn, 'product_images', 'image_path')
    ? scalar_int($conn, "SELECT COUNT(*) FROM `product_images`")
    : 0;

$product_images_products = table_exists($conn, 'product_images')
    ? scalar_int($conn, "SELECT COUNT(DISTINCT `product_id`) FROM `product_images`")
    : 0;

$variant_images_count = non_empty_count($conn, 'product_option_values', 'image');

$banner_total = table_exists($conn, 'homepage_banners') ? scalar_int($conn, "SELECT COUNT(*) FROM `homepage_banners`") : 0;
$banner_active = column_exists($conn, 'homepage_banners', 'is_active')
    ? scalar_int($conn, "SELECT COUNT(*) FROM `homepage_banners` WHERE `is_active` = 1")
    : $banner_total;

$banner_image_col = '';
if (column_exists($conn, 'homepage_banners', 'image_path')) {
    $banner_image_col = 'image_path';
} elseif (column_exists($conn, 'homepage_banners', 'image_url')) {
    $banner_image_col = 'image_url';
}

$banner_with_images = ($banner_image_col !== '')
    ? scalar_int($conn, "SELECT COUNT(*) FROM `homepage_banners` WHERE `$banner_image_col` IS NOT NULL AND TRIM(`$banner_image_col`) <> ''")
    : 0;

$hero_active = (column_exists($conn, 'homepage_banners', 'banner_type') && column_exists($conn, 'homepage_banners', 'is_active'))
    ? scalar_int($conn, "SELECT COUNT(*) FROM `homepage_banners` WHERE `banner_type` = 'hero' AND `is_active` = 1")
    : 0;

$section_active = (column_exists($conn, 'homepage_banners', 'banner_type') && column_exists($conn, 'homepage_banners', 'is_active'))
    ? scalar_int($conn, "SELECT COUNT(*) FROM `homepage_banners` WHERE `banner_type` = 'section' AND `is_active` = 1")
    : 0;

$categories_with_image = non_empty_count($conn, 'categories', 'image_url');
$subcategories_with_image = non_empty_count($conn, 'subcategories', 'image_url');

$total_podcasts = table_exists($conn, 'podcasts') ? scalar_int($conn, "SELECT COUNT(*) FROM `podcasts`") : 0;
$podcasts_with_main_photo = non_empty_count($conn, 'podcasts', 'main_photo');
$podcast_gallery_count = non_empty_count($conn, 'podcast_images', 'image_path');

$coverage_pct = 0.0;
if ($total_products > 0) {
    $coverage_pct = round(($products_with_image_link / $total_products) * 100, 1);
}

$missing_rows = [];
if (table_exists($conn, 'products')) {
    if (table_exists($conn, 'product_images')) {
        $sql_missing = "
            SELECT
                p.id,
                p.name_en,
                p.name_ar,
                p.image_link,
                COUNT(pi.id) AS gallery_count
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id
            GROUP BY p.id, p.name_en, p.name_ar, p.image_link
            HAVING (p.image_link IS NULL OR TRIM(p.image_link) = '')
               AND COUNT(pi.id) = 0
            ORDER BY p.id ASC
            LIMIT 50
        ";
        $res_missing = $conn->query($sql_missing);
    } else {
        $sql_missing = "
            SELECT
                p.id,
                p.name_en,
                p.name_ar,
                p.image_link,
                0 AS gallery_count
            FROM products p
            WHERE p.image_link IS NULL OR TRIM(p.image_link) = ''
            ORDER BY p.id ASC
            LIMIT 50
        ";
        $res_missing = $conn->query($sql_missing);
    }

    if (!empty($res_missing)) {
        while ($r = $res_missing->fetch_assoc()) {
            $missing_rows[] = $r;
        }
    }
}

$notes = [];
$notes[] = 'عرض الصور في المنتجات يعتمد فعليا على products.image_link مع fallback إلى مجلد الصور وplaceholder.';

if (!column_exists($conn, 'products', 'image_url')) {
    $notes[] = 'تم رصد صفحات إدارية تستخدم products.image_url رغم أن هذا الحقل غير موجود/غير معتمد حاليا.';
}

$has_banner_image_path = column_exists($conn, 'homepage_banners', 'image_path');
$has_banner_image_url = column_exists($conn, 'homepage_banners', 'image_url');
if ($has_banner_image_path && $has_banner_image_url) {
    $notes[] = 'جدول homepage_banners يحتوي image_path وimage_url معا؛ يفضل التوحيد على image_path.';
} elseif (!$has_banner_image_path && $has_banner_image_url) {
    $notes[] = 'جدول homepage_banners يعتمد image_url فقط (نسخة قديمة)، وبعض الواجهات تتوقع image_path.';
}

if (table_exists($conn, 'product_option_values') && !column_exists($conn, 'product_option_values', 'image')) {
    $notes[] = 'API خيارات المنتج يتعامل مع عمود image في product_option_values لكنه غير موجود في هذا الـ schema.';
}

$notes[] = 'الأرقام الموثقة سابقا في التقارير الداخلية تشير إلى 39/40 منتج بصور (97.5%)، بينما تقرير أقدم أشار إلى 32/40 في image_link.';

if ($total_products === 0) {
    $notes[] = 'لا توجد منتجات في قاعدة البيانات الحالية داخل هذا التنفيذ. غالبا تحتاج التأكد من DB_NAME في ملف .env أو بيئة السيرفر.';
}

$generated_at = date('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الصور في قاعدة البيانات</title>
    <style>
        :root {
            --bg: #f3f5fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --primary: #1d4ed8;
            --good: #059669;
            --bad: #dc2626;
            --shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #eef2ff 0%, var(--bg) 240px);
        }

        .container {
            max-width: 1280px;
            margin: 22px auto;
            padding: 0 16px 40px;
        }

        .hero {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow);
            margin-bottom: 14px;
        }

        .hero h1 {
            margin: 0 0 6px;
            font-size: 28px;
            color: #1e3a8a;
        }

        .hero p {
            margin: 0;
            color: #334155;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border: 1px solid #93c5fd;
            color: #1e40af;
            background: #ffffff;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .btn-sync {
            border-color: #10b981;
            color: #065f46;
            background: #ecfdf5;
            cursor: pointer;
        }

        .alert {
            border-radius: 12px;
            padding: 10px 12px;
            margin-top: 10px;
            font-size: 14px;
        }

        .alert-success {
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .alert-error {
            border: 1px solid #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .metric {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            box-shadow: var(--shadow);
            min-height: 84px;
        }

        .metric .label {
            color: var(--muted);
            font-size: 12px;
        }

        .metric .value {
            margin-top: 6px;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .section {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 12px;
            overflow: hidden;
        }

        .section h2 {
            margin: 0;
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            font-size: 18px;
            color: var(--primary);
            background: #f8fafc;
        }

        .section .body {
            padding: 12px 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 9px 8px;
            text-align: right;
            vertical-align: top;
            font-size: 14px;
        }

        th {
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .good {
            color: var(--good);
            font-weight: 700;
        }

        .bad {
            color: var(--bad);
            font-weight: 700;
        }

        ul {
            margin: 0;
            padding-right: 20px;
            line-height: 1.95;
        }

        .small {
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 1024px) {
            .metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .metrics {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="hero">
        <h1>تقرير شامل لصور قاعدة البيانات</h1>
        <p>هذا التقرير يعرض حالة جميع حقول الصور والجداول المرتبطة بها بشكل مباشر من قاعدة البيانات.</p>
        <p class="small" style="margin-top:6px;">قاعدة البيانات الحالية: <strong><?php echo htmlspecialchars($active_database !== '' ? $active_database : '(unknown)', ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <?php if ($db_switch_message !== ''): ?>
            <p class="small" style="margin-top:6px; color:#0f766e;"><?php echo htmlspecialchars($db_switch_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($available_databases)): ?>
            <p class="small" style="margin-top:6px;">
                قواعد متاحة للتبديل:
                <?php foreach ($available_databases as $i => $dbName): ?>
                    <?php if ($i > 0): ?> | <?php endif; ?>
                    <a href="?db=<?php echo urlencode($dbName); ?>" style="color:#1e40af;text-decoration:none;font-weight:700;"><?php echo htmlspecialchars($dbName, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>

        <div class="actions">
            <a class="btn" href="/pages/admin/admin_panel.php">العودة للوحة الإدارة</a>
            <a class="btn" href="/pages/admin/add_product.php">إضافة منتج جديد</a>
            <form method="post" style="display:inline; margin:0;">
                <input type="hidden" name="db" value="<?php echo htmlspecialchars($active_database, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn btn-sync" type="submit" name="sync_product_images" value="1" onclick="return confirm('سيتم إضافة كل الصور المفقودة فقط إلى قاعدة البيانات بدون تعديل القيم الموجودة. هل تريد المتابعة؟');">
                    مزامنة صور المنتجات إلى قاعدة البيانات
                </button>
            </form>
        </div>
        <p class="small" style="margin-top: 8px;">وقت التوليد: <?php echo htmlspecialchars($generated_at, ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if ($sync_result !== null): ?>
            <div class="alert <?php echo !empty($sync_result['ok']) ? 'alert-success' : 'alert-error'; ?>">
                <strong><?php echo htmlspecialchars((string)$sync_result['message'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                تم فحص المنتجات: <?php echo (int)($sync_result['products_scanned'] ?? 0); ?> |
                منتجات لها مجلد صور: <?php echo (int)($sync_result['products_with_folder'] ?? 0); ?> |
                صور جاليري تمت إضافتها: <?php echo (int)($sync_result['gallery_rows_inserted'] ?? 0); ?> |
                image_link تم تعبئته: <?php echo (int)($sync_result['image_link_updated'] ?? 0); ?>
                <?php if (!empty($sync_result['errors'])): ?>
                    <br>ملاحظات: <?php echo htmlspecialchars(implode(' | ', (array)$sync_result['errors']), ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="metrics">
        <div class="metric"><div class="label">إجمالي المنتجات</div><div class="value"><?php echo $total_products; ?></div></div>
        <div class="metric"><div class="label">منتجات لديها image_link</div><div class="value"><?php echo $products_with_image_link; ?></div></div>
        <div class="metric"><div class="label">منتجات بدون image_link</div><div class="value"><?php echo $products_without_image_link; ?></div></div>
        <div class="metric"><div class="label">نسبة التغطية (image_link)</div><div class="value"><?php echo number_format($coverage_pct, 1); ?>%</div></div>

        <div class="metric"><div class="label">إجمالي صور product_images</div><div class="value"><?php echo $product_images_total; ?></div></div>
        <div class="metric"><div class="label">منتجات لديها جاليري</div><div class="value"><?php echo $product_images_products; ?></div></div>
        <div class="metric"><div class="label">صور خيارات المنتجات</div><div class="value"><?php echo $variant_images_count; ?></div></div>
        <div class="metric"><div class="label">بانرات نشطة</div><div class="value"><?php echo $banner_active; ?></div></div>

        <div class="metric"><div class="label">بانرات تحتوي صور</div><div class="value"><?php echo $banner_with_images; ?></div></div>
        <div class="metric"><div class="label">Hero نشط</div><div class="value"><?php echo $hero_active; ?></div></div>
        <div class="metric"><div class="label">Section نشط</div><div class="value"><?php echo $section_active; ?></div></div>
        <div class="metric"><div class="label">إجمالي البودكاست</div><div class="value"><?php echo $total_podcasts; ?></div></div>

        <div class="metric"><div class="label">بودكاست لديها صورة رئيسية</div><div class="value"><?php echo $podcasts_with_main_photo; ?></div></div>
        <div class="metric"><div class="label">صور جاليري البودكاست</div><div class="value"><?php echo $podcast_gallery_count; ?></div></div>
        <div class="metric"><div class="label">تصنيفات بصور</div><div class="value"><?php echo $categories_with_image; ?></div></div>
        <div class="metric"><div class="label">تصنيفات فرعية بصور</div><div class="value"><?php echo $subcategories_with_image; ?></div></div>
    </div>

    <div class="section">
        <h2>خريطة حقول الصور في الجداول</h2>
        <div class="body">
            <table>
                <thead>
                <tr>
                    <th>الجدول</th>
                    <th>الحقل</th>
                    <th>الحالة</th>
                    <th>عدد السجلات الممتلئة</th>
                    <th>الوصف</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($schema_map as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['table'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($entry['column'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="<?php echo $entry['exists'] ? 'good' : 'bad'; ?>">
                            <?php echo $entry['exists'] ? 'موجود' : 'غير موجود'; ?>
                        </td>
                        <td><?php echo (int)$entry['filled']; ?></td>
                        <td><?php echo htmlspecialchars($entry['desc'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <h2>منتجات بدون صورة رئيسية ولا جاليري (أول 50)</h2>
        <div class="body">
            <?php if (empty($missing_rows)): ?>
                <p class="good" style="margin:0;">لا توجد منتجات ناقصة حسب شروط الفحص الحالية.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>الاسم (EN)</th>
                        <th>الاسم (AR)</th>
                        <th>image_link</th>
                        <th>عدد صور الجاليري</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($missing_rows as $row): ?>
                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars((string)$row['name_en'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['name_ar'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['image_link'] ?: '(empty)'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$row['gallery_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>ملاحظات مهمة (تناسق البيانات)</h2>
        <div class="body">
            <ul>
                <?php foreach ($notes as $note): ?>
                    <li><?php echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
