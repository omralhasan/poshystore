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

    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();

    return $ok;
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    if (!safe_identifier($table) || !safe_identifier($column)) {
        return false;
    }

    if (!table_exists($conn, $table)) {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `$table` LIKE ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();

    return $ok;
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
        <div class="actions">
            <a class="btn" href="/pages/admin/admin_panel.php">العودة للوحة الإدارة</a>
            <a class="btn" href="/pages/admin/add_product.php">إضافة منتج جديد</a>
        </div>
        <p class="small" style="margin-top: 8px;">وقت التوليد: <?php echo htmlspecialchars($generated_at, ENT_QUOTES, 'UTF-8'); ?></p>
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
