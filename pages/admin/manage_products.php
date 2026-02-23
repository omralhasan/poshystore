<?php
/**
 * Manage Products – Admin Panel
 * Full product list with search, filter, edit & delete
 */
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_manager.php';

if (!isAdmin()) { header('Location: ../../index.php'); exit(); }

// ─── AJAX delete handler ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_product') {
        $pid = intval($_POST['product_id'] ?? 0);
        if (!$pid) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }

        // Clean up all related rows first
        $conn->query("DELETE FROM product_tags WHERE product_id = $pid");
        $conn->query("DELETE FROM cart_items WHERE product_id = $pid");
        $conn->query("DELETE FROM cart WHERE product_id = $pid");
        $conn->query("DELETE FROM product_reviews WHERE product_id = $pid");

        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $pid);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Delete failed: ' . $conn->error]);
        }
        $stmt->close();
        exit();
    }

    if ($action === 'quick_update') {
        $pid   = intval($_POST['product_id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';

        if (!$pid) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }

        $allowed = ['price_jod', 'stock_quantity', 'discount_percentage'];
        if (!in_array($field, $allowed)) { echo json_encode(['success' => false, 'error' => 'Invalid field']); exit(); }

        if ($field === 'discount_percentage') {
            $disc = floatval($value);
            $has_disc = ($disc > 0) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE products SET discount_percentage = ?, has_discount = ? WHERE id = ?");
            $stmt->bind_param('dii', $disc, $has_disc, $pid);
        } elseif ($field === 'price_jod') {
            $price = floatval($value);
            $stmt = $conn->prepare("UPDATE products SET price_jod = ? WHERE id = ?");
            $stmt->bind_param('di', $price, $pid);
        } else {
            $qty = intval($value);
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->bind_param('ii', $qty, $pid);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $stmt->close();
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']); exit();
}

// ─── Load data ───────────────────────────────────────────────────────────────
$search   = trim($_GET['q'] ?? '');
$cat_filter  = intval($_GET['cat'] ?? 0);
$brand_filter = intval($_GET['brand'] ?? 0);
$stock_filter = $_GET['stock'] ?? '';

// Build filters
$filters = [];
if ($search)       $filters['search']    = $search;
if ($cat_filter)   $filters['category_id'] = $cat_filter;
if ($brand_filter) $filters['brand_id']  = $brand_filter;
if ($stock_filter === 'in')  $filters['in_stock'] = true;

$products_result = getAllProducts($filters, 500);
$products = $products_result['products'] ?? [];

// If stock filter is "out", filter client-side (no dedicated DB filter for out-of-stock)
if ($stock_filter === 'out') {
    $products = array_filter($products, fn($p) => $p['stock_quantity'] <= 0);
    $products = array_values($products);
}

// Load categories for filter
$categories = [];
$cat_res = $conn->query("SELECT c.id, c.name_en FROM categories c ORDER BY c.name_en");
if ($cat_res) { while ($r = $cat_res->fetch_assoc()) $categories[] = $r; }

// Load brands for filter
$brands = [];
$br_res = $conn->query("SELECT id, name_en FROM brands ORDER BY name_en");
if ($br_res) { while ($r = $br_res->fetch_assoc()) $brands[] = $r; }

$total = count($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Products – Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary-dark:#1a1d2e;--secondary-dark:#242838;--accent-blue:#4f9eff;--accent-teal:#00d4aa;--accent-purple:#a855f7;--text-light:#fff;--text-gray:#9ca3af;--text-dark:#1f2937;--success:#10b981;--warning:#f59e0b;--danger:#ef4444;--bg-light:#f9fafb;--border-color:#e5e7eb;--shadow-md:0 4px 6px rgba(0,0,0,.1);}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:var(--bg-light);min-height:100vh;display:flex;color:var(--text-dark);}
/* Sidebar */
.sidebar{width:280px;background:linear-gradient(180deg,var(--primary-dark),var(--secondary-dark));color:var(--text-light);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;overflow-y:auto;z-index:1000;box-shadow:0 20px 40px rgba(0,0,0,.2);}
.sidebar-header{padding:2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1);}
.logo{font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,var(--accent-blue),var(--accent-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.admin-badge{display:inline-block;background:linear-gradient(135deg,var(--accent-purple),var(--accent-blue));color:#fff;font-size:.7rem;padding:.25rem .75rem;border-radius:20px;margin-top:.5rem;font-weight:600;}
.sidebar-nav{flex:1;padding:1.5rem 0;}
.nav-item{padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;color:var(--text-gray);transition:all .3s;border-left:3px solid transparent;text-decoration:none;}
.nav-item:hover{background:rgba(255,255,255,.05);color:var(--text-light);}
.nav-item.active{background:rgba(79,158,255,.1);color:var(--accent-blue);border-left-color:var(--accent-blue);}
.nav-item i{font-size:1.1rem;width:24px;text-align:center;}
.sidebar-footer{padding:1.5rem;border-top:1px solid rgba(255,255,255,.1);}
.logout-btn{width:100%;background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;border:none;padding:.875rem;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;}
/* Main */
.main-content{flex:1;margin-left:280px;padding:2rem;}
.page-header{background:#fff;padding:1.75rem 2rem;border-radius:16px;margin-bottom:1.5rem;box-shadow:var(--shadow-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.page-header h1{font-size:1.875rem;font-weight:700;background:linear-gradient(135deg,var(--accent-blue),var(--accent-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.header-actions{display:flex;gap:.75rem;align-items:center;}
.stat-badges{display:flex;gap:.75rem;flex-wrap:wrap;}
.stat-badge{padding:.5rem 1rem;border-radius:10px;font-weight:600;font-size:.85rem;display:flex;align-items:center;gap:.4rem;}
.stat-total{background:rgba(79,158,255,.1);color:var(--accent-blue);}
.stat-instock{background:rgba(16,185,129,.1);color:var(--success);}
.stat-out{background:rgba(239,68,68,.1);color:var(--danger);}
/* Filter bar */
.filter-bar{background:#fff;padding:1.25rem 1.75rem;border-radius:12px;margin-bottom:1.5rem;box-shadow:var(--shadow-md);display:flex;gap:1rem;flex-wrap:wrap;align-items:center;}
.filter-bar input,.filter-bar select{padding:.6rem 1rem;border:2px solid var(--border-color);border-radius:8px;font-size:.875rem;font-family:inherit;background:var(--bg-light);transition:border-color .3s;}
.filter-bar input:focus,.filter-bar select:focus{outline:none;border-color:var(--accent-blue);}
.filter-bar input[type="search"]{min-width:250px;}
.filter-bar select{min-width:150px;}
.btn{padding:.65rem 1.25rem;border:none;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:all .3s;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,var(--accent-blue),#3b82f6);color:#fff;}
.btn-success{background:linear-gradient(135deg,var(--success),#059669);color:#fff;}
.btn-danger{background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;}
.btn-secondary{background:var(--bg-light);color:var(--text-dark);border:2px solid var(--border-color);}
.btn-sm{padding:.4rem .8rem;font-size:.8rem;border-radius:8px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15);}
/* Table */
.card{background:#fff;border-radius:16px;padding:0;box-shadow:var(--shadow-md);overflow:hidden;}
.table-wrapper{overflow-x:auto;}
table{width:100%;border-collapse:collapse;min-width:900px;}
th{background:linear-gradient(135deg,var(--primary-dark),var(--secondary-dark));color:var(--text-gray);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;padding:.875rem 1rem;text-align:left;white-space:nowrap;}
td{padding:.875rem 1rem;border-bottom:1px solid var(--border-color);vertical-align:middle;font-size:.9rem;}
tr:hover{background:rgba(79,158,255,.03);}
.product-img{width:50px;height:50px;border-radius:8px;object-fit:cover;background:#f3f4f6;border:2px solid var(--border-color);}
.product-name{font-weight:600;color:var(--text-dark);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.product-name-ar{font-size:.8rem;color:var(--text-gray);direction:rtl;}
.price-current{font-weight:700;color:var(--text-dark);font-size:.95rem;}
.price-original{text-decoration:line-through;color:var(--text-gray);font-size:.8rem;}
.discount-badge{background:linear-gradient(135deg,var(--warning),#d97706);color:#fff;padding:.2rem .6rem;border-radius:6px;font-size:.75rem;font-weight:700;}
.stock-badge{padding:.25rem .6rem;border-radius:6px;font-size:.8rem;font-weight:600;white-space:nowrap;}
.stock-in{background:rgba(16,185,129,.1);color:var(--success);}
.stock-out{background:rgba(239,68,68,.1);color:var(--danger);}
.stock-low{background:rgba(245,158,11,.1);color:var(--warning);}
.actions-cell{display:flex;gap:.4rem;flex-wrap:wrap;}
.brand-tag{background:rgba(168,85,247,.1);color:var(--accent-purple);padding:.15rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;white-space:nowrap;}
.cat-tag{background:rgba(0,212,170,.1);color:#059669;padding:.15rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;white-space:nowrap;}
.quick-edit-input{width:80px;padding:.35rem .5rem;border:2px solid var(--border-color);border-radius:6px;font-size:.85rem;text-align:center;transition:border-color .3s;}
.quick-edit-input:focus{border-color:var(--accent-blue);outline:none;}
.quick-save{background:var(--success);color:#fff;border:none;border-radius:6px;padding:.35rem .5rem;cursor:pointer;font-size:.75rem;transition:all .2s;}
.quick-save:hover{background:#059669;}
/* Toast */
.toast{position:fixed;top:2rem;right:2rem;padding:1rem 1.5rem;border-radius:12px;color:#fff;font-weight:600;z-index:9999;transform:translateX(120%);transition:transform .4s;box-shadow:0 10px 30px rgba(0,0,0,.2);}
.toast.show{transform:translateX(0);}
.toast-success{background:linear-gradient(135deg,var(--success),#059669);}
.toast-error{background:linear-gradient(135deg,var(--danger),#dc2626);}
.empty-state{text-align:center;padding:4rem 2rem;color:var(--text-gray);}
.empty-state i{font-size:4rem;margin-bottom:1rem;opacity:.4;}
.empty-state p{font-size:1.1rem;}
/* Responsive */
@media(max-width:1024px){.sidebar{width:70px;}.sidebar .logo,.sidebar .admin-badge,.sidebar .nav-item span,.sidebar .logout-btn span{display:none;}.nav-item{justify-content:center;padding:.75rem;}.main-content{margin-left:70px;}.filter-bar{flex-direction:column;}.filter-bar input[type="search"]{min-width:100%;}}
@media(max-width:768px){.sidebar{display:none;}.main-content{margin-left:0;padding:1rem;}.page-header{flex-direction:column;text-align:center;}}
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
        <a href="manage_products.php"    class="nav-item active"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="add_product.php"        class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php"     class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_categories.php"  class="nav-item"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="manage_brands.php"      class="nav-item"><i class="fas fa-copyright"></i><span>Brands</span></a>
        <a href="daily_reports.php"      class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php"        class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer"><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</div>

<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-boxes-stacked"></i> Manage Products</h1>
            <div class="stat-badges" style="margin-top:.75rem;">
                <span class="stat-badge stat-total"><i class="fas fa-box"></i> <?= $total ?> products</span>
                <span class="stat-badge stat-instock"><i class="fas fa-check"></i> <?= count(array_filter($products, fn($p) => $p['stock_quantity'] > 0)) ?> in stock</span>
                <span class="stat-badge stat-out"><i class="fas fa-exclamation"></i> <?= count(array_filter($products, fn($p) => $p['stock_quantity'] <= 0)) ?> out of stock</span>
            </div>
        </div>
        <div class="header-actions">
            <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" id="filterForm" style="display:contents;">
            <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search products or tags...">
            <select name="cat" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="brand" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Brands</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $brand_filter == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stock" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Stock</option>
                <option value="in" <?= $stock_filter === 'in' ? 'selected' : '' ?>>In Stock</option>
                <option value="out" <?= $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($search || $cat_filter || $brand_filter || $stock_filter): ?>
                <a href="manage_products.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Table -->
    <div class="card">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No products found<?= $search ? " for \"" . htmlspecialchars($search) . "\"" : '' ?>.</p>
                <a href="add_product.php" class="btn btn-primary" style="margin-top:1rem;"><i class="fas fa-plus"></i> Add New Product</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Brand / Category</th>
                            <th>Price (JOD)</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr id="product-row-<?= $p['id'] ?>">
                            <td><strong style="color:var(--accent-blue);">#<?= $p['id'] ?></strong></td>
                            <td>
                                <?php if (!empty($p['image_link'])): ?>
                                    <img src="../../<?= htmlspecialchars($p['image_link']) ?>" class="product-img" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="product-img" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-image" style="color:#ccc;"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-name" title="<?= htmlspecialchars($p['name_en']) ?>"><?= htmlspecialchars($p['name_en']) ?></div>
                                <?php if (!empty($p['name_ar'])): ?>
                                    <div class="product-name-ar"><?= htmlspecialchars($p['name_ar']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['brand_en'])): ?>
                                    <span class="brand-tag"><i class="fas fa-copyright"></i> <?= htmlspecialchars($p['brand_en']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['category_en'])): ?>
                                    <span class="cat-tag" style="margin-top:.25rem;display:inline-block;"><i class="fas fa-folder"></i> <?= htmlspecialchars($p['category_en']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="price-current"><?= $p['price_formatted'] ?></div>
                                <?php if ($p['has_discount'] && !empty($p['original_price'])): ?>
                                    <div class="price-original"><?= formatJOD($p['original_price']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['has_discount']): ?>
                                    <span class="discount-badge"><i class="fas fa-tag"></i> <?= number_format($p['discount_percentage'], 0) ?>%</span>
                                <?php else: ?>
                                    <span style="color:var(--text-gray);font-size:.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $sq = $p['stock_quantity'];
                                if ($sq <= 0) $sc = 'stock-out';
                                elseif ($sq <= 5) $sc = 'stock-low';
                                else $sc = 'stock-in';
                                ?>
                                <span class="stock-badge <?= $sc ?>"><?= $sq ?> units</span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" title="Edit all details">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="../../<?= htmlspecialchars($p['slug'] ?? '') ?>" target="_blank" class="btn btn-secondary btn-sm" title="View on store">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name_en'])) ?>', this)" title="Delete product">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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

async function deleteProduct(id, name, btn) {
    if (!confirm('Delete "' + name + '"?\n\nThis will permanently remove the product and all its reviews, cart entries, and tags. This cannot be undone.')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'delete_product');
    fd.append('product_id', id);

    try {
        const r = await fetch('manage_products.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) {
            const row = document.getElementById('product-row-' + id);
            if (row) { row.style.transition = 'opacity .4s'; row.style.opacity = '0'; setTimeout(() => row.remove(), 400); }
            showToast('Product deleted successfully!');
        } else {
            showToast(d.error || 'Delete failed', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i>';
        }
    } catch (e) {
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash"></i>';
    }
}
</script>
</body>
</html>
