<?php
/**
 * Check Products Without Images - Admin Tool
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_image_helper.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

$missing_images = [];
$total_products = 0;
$has_images_count = 0;

$result = $conn->query("SELECT id, name_en, name_ar, image_link FROM products ORDER BY id ASC");
if ($result) {
    while ($p = $result->fetch_assoc()) {
        $total_products++;
        
        $images_dir = __DIR__ . '/../../images';
        $folder = find_product_image_folder($p['name_en'], $images_dir);
        
        $has_folder_img = false;
        if ($folder) {
            $folder_path = $images_dir . '/' . $folder;
            if (is_dir($folder_path)) {
                $imgs = get_png_files($folder_path);
                if (!empty($imgs)) {
                    $has_folder_img = true;
                }
            }
        }
        
        $has_db_image = (!empty($p['image_link']) && strtolower($p['image_link']) !== 'null' && file_exists(__DIR__ . '/../../' . $p['image_link']));
        
        if (!$has_folder_img && !$has_db_image) {
            $missing_images[] = $p;
        } else {
            $has_images_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missing Images Report – Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a1d2e; --secondary-dark: #242838; --accent-blue: #4f9eff;
            --accent-teal: #00d4aa; --accent-purple: #a855f7; --text-light: #fff;
            --text-gray: #9ca3af; --text-dark: #1f2937; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --bg-light: #f9fafb;
            --border-color: #e5e7eb; --shadow-md: 0 4px 6px rgba(0,0,0,.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); display: flex; color: var(--text-dark); }
        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary-dark), var(--secondary-dark)); color: var(--text-light); height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sidebar-nav { padding: 1.5rem 0; }
        .nav-item { padding: .75rem 1.5rem; display: flex; align-items: center; gap: 1rem; color: var(--text-gray); text-decoration: none; transition: .3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,.05); color: var(--text-light); border-left-color: var(--accent-blue); }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; padding: 1.5rem; border-radius: 16px; box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-info h3 { font-size: .85rem; color: var(--text-gray); margin-bottom: .25rem; }
        .stat-info .value { font-size: 1.5rem; font-weight: 700; color: var(--primary-dark); }
        .table-container { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-gray); font-size: .85rem; text-transform: uppercase; letter-spacing: .05em; }
        .btn { padding: .5rem 1rem; border-radius: 8px; text-decoration: none; font-size: .85rem; font-weight: 600; }
        .btn-primary { background: var(--accent-blue); color: #fff; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
    </div>
    <div class="sidebar-nav">
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-box"></i><span>Dashboard</span></a>
        <a href="manage_products.php" class="nav-item"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="check_images.php" class="nav-item active"><i class="fas fa-image"></i><span>Missing Images Check</span></a>
        <a href="../../index.php" class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-image"></i> Missing Product Images</h1>
        <a href="admin_panel.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a>
    </div>

    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(79,158,255,.1); color: var(--accent-blue);"><i class="fas fa-boxes"></i></div>
            <div class="stat-info"><h3>Total Products</h3><div class="value"><?= $total_products ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,.1); color: var(--success);"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3>Images Found</h3><div class="value"><?= $has_images_count ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,68,68,.1); color: var(--danger);"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info"><h3>Missing Images</h3><div class="value" style="color:var(--danger)"><?= count($missing_images) ?></div></div>
        </div>
    </div>

    <div class="table-container">
        <h2 style="font-size:1.1rem;margin-bottom:1.5rem;color:var(--primary-dark);">Products Missing Images</h2>
        <?php if (empty($missing_images)): ?>
            <div style="text-align:center;padding:2rem;color:var(--success);font-weight:600;">
                <i class="fas fa-check-circle" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
                All products have images!
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name (EN)</th>
                        <th>Product Name (AR)</th>
                        <th>Database Links</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missing_images as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($p['name_en']) ?></td>
                            <td dir="rtl"><?= htmlspecialchars($p['name_ar']) ?></td>
                            <td><?= empty($p['image_link']) || strtolower($p['image_link']) === 'null' ? '<span style="color:var(--danger)">No DB Link</span>' : 'Has Link (File Missing or Bad Path)' ?></td>
                            <td style="text-align:right;">
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
