<?php
/**
 * Manage Categories & Subcategories – Admin Panel
 * Add, view, and delete categories/subcategories
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// ─── AJAX handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ADD CATEGORY
    if ($action === 'add_category') {
        $name_en = trim($_POST['name_en'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        if (!$name_en) { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
        $stmt = $conn->prepare("INSERT INTO categories (name_en, name_ar) VALUES (?, ?)");
        $stmt->bind_param('ss', $name_en, $name_ar);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $new_id, 'name_en' => $name_en, 'name_ar' => $name_ar]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
            $stmt->close();
        }
        exit();
    }

    // ADD SUBCATEGORY
    if ($action === 'add_subcategory') {
        $name_en     = trim($_POST['name_en'] ?? '');
        $name_ar     = trim($_POST['name_ar'] ?? '');
        $icon        = trim($_POST['icon'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        if (!$name_en)     { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
        if (!$category_id) { echo json_encode(['success' => false, 'error' => 'Parent category is required']); exit(); }
        $stmt = $conn->prepare("INSERT INTO subcategories (name_en, name_ar, icon, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $name_en, $name_ar, $icon, $category_id);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $new_id, 'name_en' => $name_en, 'name_ar' => $name_ar, 'icon' => $icon, 'category_id' => $category_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
            $stmt->close();
        }
        exit();
    }

    // DELETE CATEGORY
    if ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }
        // Check if any products are linked through subcategories
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM products p JOIN subcategories s ON p.subcategory_id = s.id WHERE s.category_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
        if ($row['cnt'] > 0) {
            echo json_encode(['success' => false, 'error' => "Cannot delete: {$row['cnt']} product(s) are linked to this category's subcategories. Remove the products first."]);
            exit();
        }
        // Delete subcategories first
        $conn->prepare("DELETE FROM subcategories WHERE category_id = ?")->execute() || true;
        $del_subs = $conn->prepare("DELETE FROM subcategories WHERE category_id = ?");
        $del_subs->bind_param('i', $id);
        $del_subs->execute();
        $del_subs->close();
        // Delete category
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { echo json_encode(['success' => true]); }
        else { echo json_encode(['success' => false, 'error' => 'Delete failed']); }
        $stmt->close();
        exit();
    }

    // DELETE SUBCATEGORY
    if ($action === 'delete_subcategory') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }
        // Check products
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM products WHERE subcategory_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
        if ($row['cnt'] > 0) {
            echo json_encode(['success' => false, 'error' => "Cannot delete: {$row['cnt']} product(s) are linked to this subcategory."]);
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { echo json_encode(['success' => true]); }
        else { echo json_encode(['success' => false, 'error' => 'Delete failed']); }
        $stmt->close();
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit();
}

// ─── Load categories & subcategories ─────────────────────────────────────────
$categories = [];
$result = $conn->query("SELECT c.id AS cid, c.name_en AS cen, c.name_ar AS car,
    s.id AS sid, s.name_en AS sen, s.name_ar AS sar, s.icon AS sicon,
    (SELECT COUNT(*) FROM products WHERE subcategory_id = s.id) AS product_count
    FROM categories c
    LEFT JOIN subcategories s ON s.category_id = c.id
    ORDER BY c.id ASC, s.id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cid = $row['cid'];
        if (!isset($categories[$cid])) {
            $categories[$cid] = ['id' => $cid, 'name_en' => $row['cen'], 'name_ar' => $row['car'], 'subcategories' => []];
        }
        if ($row['sid']) {
            $categories[$cid]['subcategories'][] = [
                'id'            => $row['sid'],
                'name_en'       => $row['sen'],
                'name_ar'       => $row['sar'],
                'icon'          => $row['sicon'] ?? '',
                'product_count' => intval($row['product_count']),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories – Admin Panel</title>
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
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); min-height: 100vh; display: flex; color: var(--text-dark); }

        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary-dark), var(--secondary-dark)); color: var(--text-light); display: flex; flex-direction: column; position: fixed; left: 0; top: 0; bottom: 0; overflow-y: auto; z-index: 1000; box-shadow: 0 20px 40px rgba(0,0,0,.2); }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .admin-badge { display: inline-block; background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue)); color: #fff; font-size: .7rem; padding: .25rem .75rem; border-radius: 20px; margin-top: .5rem; font-weight: 600; }
        .sidebar-nav { flex: 1; padding: 1.5rem 0; }
        .nav-item { padding: .75rem 1.5rem; display: flex; align-items: center; gap: 1rem; color: var(--text-gray); cursor: pointer; transition: all .3s; border-left: 3px solid transparent; text-decoration: none; }
        .nav-item:hover { background: rgba(255,255,255,.05); color: var(--text-light); }
        .nav-item.active { background: rgba(79,158,255,.1); color: var(--accent-blue); border-left-color: var(--accent-blue); }
        .nav-item i { font-size: 1.1rem; width: 24px; text-align: center; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255,255,255,.1); }
        .logout-btn { width: 100%; background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; border: none; padding: .875rem; border-radius: 10px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: .5rem; transition: all .3s; text-decoration: none; }

        .main-content { flex: 1; margin-left: 280px; padding: 2rem; overflow-x: hidden; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        .cards-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .form-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); }
        .form-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .4rem; font-size: .875rem; }
        .form-group input, .form-group select { width: 100%; padding: .65rem .9rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: .9rem; font-family: inherit; transition: border-color .3s; background: var(--bg-light); }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(79,158,255,.15); }
        .form-group .help-text { font-size: .78rem; color: var(--text-gray); margin-top: .3rem; }
        .btn { padding: .65rem 1.25rem; border: none; border-radius: 10px; font-weight: 600; font-size: .9rem; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #3b82f6); color: #fff; box-shadow: 0 4px 12px rgba(79,158,255,.3); }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; }
        .btn-sm { padding: .35rem .75rem; font-size: .78rem; border-radius: 8px; }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); border: 2px solid var(--border-color); }

        /* Category list */
        .categories-section { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); }
        .categories-section h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .category-block { border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 1.25rem; overflow: hidden; }
        .category-header { display: flex; justify-content: space-between; align-items: center; padding: .9rem 1.25rem; background: linear-gradient(135deg, rgba(79,158,255,.06), rgba(0,212,170,.04)); border-bottom: 1px solid var(--border-color); }
        .category-header .cat-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: .5rem; }
        .category-header .cat-ar { color: var(--text-gray); font-size: .85rem; display: block; }
        .subcategory-list { padding: .75rem 1.25rem; }
        .subcategory-item { display: flex; justify-content: space-between; align-items: center; padding: .5rem .75rem; border-radius: 8px; margin-bottom: .35rem; background: var(--bg-light); }
        .subcategory-item:last-child { margin-bottom: 0; }
        .subcategory-item .sub-info { display: flex; align-items: center; gap: .6rem; }
        .subcategory-item .sub-icon { color: var(--accent-blue); width: 20px; text-align: center; }
        .subcategory-item .sub-name { font-weight: 600; font-size: .9rem; }
        .subcategory-item .sub-ar { color: var(--text-gray); font-size: .8rem; }
        .subcategory-item .prod-badge { background: rgba(79,158,255,.12); color: var(--accent-blue); font-size: .72rem; padding: .2rem .55rem; border-radius: 20px; font-weight: 600; }
        .empty-sub { color: var(--text-gray); font-style: italic; font-size: .88rem; padding: .5rem 0; text-align: center; }

        .toast { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 12px; color: #fff; font-weight: 600; z-index: 9999; transform: translateX(120%); transition: transform .4s; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, var(--success), #059669); }
        .toast-error   { background: linear-gradient(135deg, var(--danger), #dc2626); }

        @media (max-width: 1024px) {
            .cards-row { grid-template-columns: 1fr; }
            .sidebar { width: 70px; }
            .sidebar .logo, .sidebar .admin-badge, .sidebar .nav-item span, .sidebar .logout-btn span { display: none; }
            .sidebar-header { padding: 1rem .5rem; text-align: center; }
            .nav-item { justify-content: center; padding: .75rem; }
            .main-content { margin-left: 70px; }
        }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
        <div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div>
    </div>
    <div class="sidebar-nav">
        <a href="admin_panel.php"        class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="admin_panel.php"        class="nav-item"><i class="fas fa-tag"></i><span>Products & Pricing</span></a>
        <a href="add_product.php"        class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php"     class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_categories.php"  class="nav-item active"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="daily_reports.php"      class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php"        class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-layer-group"></i> Manage Categories</h1>
        <a href="admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Add Category + Add Subcategory forms -->
    <div class="cards-row">

        <!-- Add Category -->
        <div class="form-card">
            <h2><i class="fas fa-folder-plus"></i> Add New Category</h2>
            <form id="addCategoryForm">
                <div class="form-group">
                    <label>Category Name (English) <span style="color:red">*</span></label>
                    <input type="text" name="name_en" id="catNameEn" placeholder="e.g. Skincare" required>
                </div>
                <div class="form-group">
                    <label>Category Name (Arabic)</label>
                    <input type="text" name="name_ar" id="catNameAr" placeholder="مثال: العناية بالبشرة" dir="rtl">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </form>
        </div>

        <!-- Add Subcategory -->
        <div class="form-card">
            <h2><i class="fas fa-tag"></i> Add New Subcategory</h2>
            <form id="addSubcategoryForm">
                <div class="form-group">
                    <label>Parent Category <span style="color:red">*</span></label>
                    <select name="category_id" id="subCategoryParent" required>
                        <option value="">-- Select Parent Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory Name (English) <span style="color:red">*</span></label>
                    <input type="text" name="name_en" id="subNameEn" placeholder="e.g. Moisturizers" required>
                </div>
                <div class="form-group">
                    <label>Subcategory Name (Arabic)</label>
                    <input type="text" name="name_ar" id="subNameAr" placeholder="مثال: المرطبات" dir="rtl">
                </div>
                <div class="form-group">
                    <label>Icon (FontAwesome class)</label>
                    <input type="text" name="icon" id="subIcon" placeholder="e.g. fas fa-tint">
                    <div class="help-text">Optional. Use any FontAwesome 6 class, e.g. <code>fas fa-leaf</code>.</div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center">
                    <i class="fas fa-plus"></i> Add Subcategory
                </button>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="categories-section" id="categoriesSection">
        <h2><i class="fas fa-list"></i> Current Categories & Subcategories</h2>

        <?php if (empty($categories)): ?>
            <p style="color: var(--text-gray); text-align:center; padding: 2rem;">No categories found. Add one above.</p>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <div class="category-block" id="catBlock-<?= $cat['id'] ?>">
                <div class="category-header">
                    <div>
                        <div class="cat-title">
                            <i class="fas fa-folder" style="color: var(--warning);"></i>
                            <?= htmlspecialchars($cat['name_en']) ?>
                            <span style="color:var(--text-gray);font-size:.8rem;">(ID: <?= $cat['id'] ?>)</span>
                        </div>
                        <?php if ($cat['name_ar']): ?>
                            <span class="cat-ar"><?= htmlspecialchars($cat['name_ar']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?= $cat['id'] ?>, this)" title="Delete category and all its subcategories">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
                <div class="subcategory-list" id="subList-<?= $cat['id'] ?>">
                    <?php if (empty($cat['subcategories'])): ?>
                        <div class="empty-sub" id="emptySub-<?= $cat['id'] ?>">No subcategories yet.</div>
                    <?php else: ?>
                        <?php foreach ($cat['subcategories'] as $sub): ?>
                        <div class="subcategory-item" id="subItem-<?= $sub['id'] ?>">
                            <div class="sub-info">
                                <?php if ($sub['icon']): ?>
                                    <i class="<?= htmlspecialchars($sub['icon']) ?> sub-icon"></i>
                                <?php else: ?>
                                    <i class="fas fa-tag sub-icon"></i>
                                <?php endif; ?>
                                <div>
                                    <div class="sub-name"><?= htmlspecialchars($sub['name_en']) ?></div>
                                    <?php if ($sub['name_ar']): ?>
                                        <div class="sub-ar"><?= htmlspecialchars($sub['name_ar']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="prod-badge"><?= $sub['product_count'] ?> product<?= $sub['product_count'] != 1 ? 's' : '' ?></span>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="deleteSubcategory(<?= $sub['id'] ?>, <?= $cat['id'] ?>, this)" title="Delete subcategory">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 4000);
}

function post(data) {
    const fd = new FormData();
    for (const k in data) fd.append(k, data[k]);
    return fetch('manage_categories.php', { method: 'POST', body: fd }).then(r => r.json());
}

// ─── Add Category ─────────────────────────────────────────────────────────────
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const name_en = document.getElementById('catNameEn').value.trim();
    const name_ar = document.getElementById('catNameAr').value.trim();
    if (!name_en) { showToast('English name is required', 'error'); return; }

    post({ action: 'add_category', name_en, name_ar }).then(data => {
        if (data.success) {
            showToast('Category added!');
            this.reset();
            // Append to DOM
            appendCategoryBlock(data.id, data.name_en, data.name_ar);
            // Also add to parent select in subcategory form
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = data.name_en;
            document.getElementById('subCategoryParent').appendChild(opt);
        } else {
            showToast(data.error || 'Failed', 'error');
        }
    });
});

// ─── Add Subcategory ──────────────────────────────────────────────────────────
document.getElementById('addSubcategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const category_id = document.getElementById('subCategoryParent').value;
    const name_en     = document.getElementById('subNameEn').value.trim();
    const name_ar     = document.getElementById('subNameAr').value.trim();
    const icon        = document.getElementById('subIcon').value.trim();
    if (!name_en)     { showToast('English name is required', 'error'); return; }
    if (!category_id) { showToast('Select a parent category', 'error'); return; }

    post({ action: 'add_subcategory', name_en, name_ar, icon, category_id }).then(data => {
        if (data.success) {
            showToast('Subcategory added!');
            this.reset();
            appendSubcategoryItem(data.id, data.category_id, data.name_en, data.name_ar, data.icon);
        } else {
            showToast(data.error || 'Failed', 'error');
        }
    });
});

// ─── Delete Category ──────────────────────────────────────────────────────────
function deleteCategory(id, btn) {
    if (!confirm('Delete this category and ALL its subcategories? This cannot be undone.')) return;
    btn.disabled = true;
    post({ action: 'delete_category', id }).then(data => {
        if (data.success) {
            document.getElementById('catBlock-' + id)?.remove();
            showToast('Category deleted.');
        } else {
            showToast(data.error || 'Failed', 'error');
            btn.disabled = false;
        }
    });
}

// ─── Delete Subcategory ───────────────────────────────────────────────────────
function deleteSubcategory(subId, catId, btn) {
    if (!confirm('Delete this subcategory?')) return;
    btn.disabled = true;
    post({ action: 'delete_subcategory', id: subId }).then(data => {
        if (data.success) {
            document.getElementById('subItem-' + subId)?.remove();
            showToast('Subcategory deleted.');
            // Show "empty" placeholder if no subs left
            const list = document.getElementById('subList-' + catId);
            if (list && list.querySelectorAll('.subcategory-item').length === 0) {
                const empty = document.createElement('div');
                empty.className = 'empty-sub';
                empty.id = 'emptySub-' + catId;
                empty.textContent = 'No subcategories yet.';
                list.appendChild(empty);
            }
        } else {
            showToast(data.error || 'Failed', 'error');
            btn.disabled = false;
        }
    });
}

// ─── DOM helpers ──────────────────────────────────────────────────────────────
function appendCategoryBlock(id, name_en, name_ar) {
    const section = document.getElementById('categoriesSection');
    const noCategories = section.querySelector('p');
    if (noCategories) noCategories.remove();

    const block = document.createElement('div');
    block.className = 'category-block';
    block.id = 'catBlock-' + id;
    block.innerHTML = `
        <div class="category-header">
            <div>
                <div class="cat-title">
                    <i class="fas fa-folder" style="color: var(--warning);"></i>
                    ${escHtml(name_en)}
                    <span style="color:var(--text-gray);font-size:.8rem;">(ID: ${id})</span>
                </div>
                ${name_ar ? '<span class="cat-ar">' + escHtml(name_ar) + '</span>' : ''}
            </div>
            <button class="btn btn-danger btn-sm" onclick="deleteCategory(${id}, this)" title="Delete category">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
        </div>
        <div class="subcategory-list" id="subList-${id}">
            <div class="empty-sub" id="emptySub-${id}">No subcategories yet.</div>
        </div>`;
    section.appendChild(block);
}

function appendSubcategoryItem(subId, catId, name_en, name_ar, icon) {
    const list = document.getElementById('subList-' + catId);
    if (!list) return;
    // Remove "empty" placeholder
    const empty = document.getElementById('emptySub-' + catId);
    if (empty) empty.remove();

    const item = document.createElement('div');
    item.className = 'subcategory-item';
    item.id = 'subItem-' + subId;
    const iconHtml = icon ? `<i class="${escHtml(icon)} sub-icon"></i>` : '<i class="fas fa-tag sub-icon"></i>';
    item.innerHTML = `
        <div class="sub-info">
            ${iconHtml}
            <div>
                <div class="sub-name">${escHtml(name_en)}</div>
                ${name_ar ? '<div class="sub-ar">' + escHtml(name_ar) + '</div>' : ''}
            </div>
            <span class="prod-badge">0 products</span>
        </div>
        <button class="btn btn-danger btn-sm" onclick="deleteSubcategory(${subId}, ${catId}, this)">
            <i class="fas fa-times"></i>
        </button>`;
    list.appendChild(item);
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
