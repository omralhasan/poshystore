<?php
/**
 * Add Product - Admin Panel
 * Allows admins to add new products to the store
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/slug_helper.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// Handle AJAX submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $name_en    = trim($_POST['name_en'] ?? '');
    $name_ar    = trim($_POST['name_ar'] ?? '');
    $short_en   = trim($_POST['short_description_en'] ?? '');
    $short_ar   = trim($_POST['short_description_ar'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $details    = trim($_POST['product_details'] ?? '');
    $how_en     = trim($_POST['how_to_use_en'] ?? '');
    $how_ar     = trim($_POST['how_to_use_ar'] ?? '');
    $video_url  = trim($_POST['video_review_url'] ?? '');
    $price      = floatval($_POST['price_jod'] ?? 0);
    $stock      = intval($_POST['stock_quantity'] ?? 0);
    $subcat_id  = intval($_POST['subcategory_id'] ?? 0) ?: null;
    $sup_cost   = ($_POST['supplier_cost'] ?? '') !== '' ? floatval($_POST['supplier_cost']) : null;
    $pub_min    = ($_POST['public_price_min'] ?? '') !== '' ? floatval($_POST['public_price_min']) : null;
    $pub_max    = ($_POST['public_price_max'] ?? '') !== '' ? floatval($_POST['public_price_max']) : null;
    $orig_price = ($_POST['original_price'] ?? '') !== '' ? floatval($_POST['original_price']) : $price;
    $discount   = floatval($_POST['discount_percentage'] ?? 0);
    $has_disc   = ($discount > 0) ? 1 : 0;

    // Validation
    if (empty($name_en)) { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
    if (empty($name_ar)) { echo json_encode(['success' => false, 'error' => 'Arabic name is required']); exit(); }
    if ($price <= 0)      { echo json_encode(['success' => false, 'error' => 'Price must be greater than 0']); exit(); }

    // Generate unique slug
    $slug = generateUniqueSlug($conn, $name_en);

    // Handle image upload — create folder in /images/{product_name}/
    $image_link = '';
    $images_base = __DIR__ . '/../../images/';
    $folder_name = $name_en;
    $folder_path = $images_base . $folder_name . '/';

    if (!empty($_FILES['product_images']['name'][0])) {
        if (!is_dir($folder_path)) mkdir($folder_path, 0755, true);

        $files = $_FILES['product_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($files['type'][$i], $allowed)) continue;

            $num = $i + 1;
            $dest = $folder_path . $num . '.png';
            move_uploaded_file($files['tmp_name'][$i], $dest);

            if ($num === 1) {
                $image_link = 'images/' . $folder_name . '/1.png';
            }
        }
    }

    // Insert product
    $sql = "INSERT INTO products (name_en, name_ar, slug, short_description_en, short_description_ar,
            description, product_details, how_to_use_en, how_to_use_ar, video_review_url,
            price_jod, stock_quantity, image_link, subcategory_id,
            supplier_cost, public_price_min, public_price_max,
            original_price, discount_percentage, has_discount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param('ssssssssssdisidddddi',
        $name_en, $name_ar, $slug, $short_en, $short_ar,
        $desc, $details, $how_en, $how_ar, $video_url,
        $price, $stock, $image_link, $subcat_id,
        $sup_cost, $pub_min, $pub_max,
        $orig_price, $discount, $has_disc
    );

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully!',
            'id'      => $product_id,
            'slug'    => $slug
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $stmt->error]);
        $stmt->close();
    }
    exit();
}

// Get categories and subcategories for the form
$categories = [];
$cat_result = $conn->query("SELECT c.id AS category_id, c.name_en AS category_en,
    s.id AS subcategory_id, s.name_en AS subcategory_en
    FROM categories c
    LEFT JOIN subcategories s ON s.category_id = c.id
    ORDER BY c.sort_order, s.sort_order");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $cid = $row['category_id'];
        if (!isset($categories[$cid])) {
            $categories[$cid] = ['name' => $row['category_en'], 'subcategories' => []];
        }
        if ($row['subcategory_id']) {
            $categories[$cid]['subcategories'][] = ['id' => $row['subcategory_id'], 'name' => $row['subcategory_en']];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Panel</title>
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

        /* Sidebar */
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

        /* Main Content */
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; overflow-x: hidden; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* Form Cards */
        .form-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); margin-bottom: 2rem; }
        .form-card h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: .5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .5rem; font-size: .875rem; }
        .form-group label .required { color: var(--danger); }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select {
            width: 100%; padding: .75rem 1rem; border: 2px solid var(--border-color);
            border-radius: 10px; font-size: .925rem; font-family: inherit;
            transition: border-color .3s; background: var(--bg-light);
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(79,158,255,.15);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help-text { font-size: .8rem; color: var(--text-gray); margin-top: .35rem; }
        .form-group input[dir="rtl"], .form-group textarea[dir="rtl"] { text-align: right; }

        /* Image Upload */
        .image-upload-area {
            border: 2px dashed var(--border-color); border-radius: 12px; padding: 2rem;
            text-align: center; cursor: pointer; transition: all .3s; position: relative;
            background: var(--bg-light);
        }
        .image-upload-area:hover { border-color: var(--accent-blue); }
        .image-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .image-upload-area i { font-size: 2.5rem; color: var(--accent-blue); margin-bottom: .75rem; }
        .image-upload-area p { color: var(--text-gray); font-size: .9rem; }
        .image-preview { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .image-preview-item {
            width: 120px; height: 120px; border-radius: 10px; overflow: hidden;
            position: relative; box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        .image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .image-preview-item .badge {
            position: absolute; bottom: 4px; left: 4px; background: var(--accent-blue);
            color: #fff; font-size: .65rem; padding: 2px 6px; border-radius: 8px; font-weight: 700;
        }

        /* Buttons */
        .btn { padding: .75rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; font-size: .925rem; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #3b82f6); color: #fff; box-shadow: 0 4px 12px rgba(79,158,255,.3); }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-success { background: linear-gradient(135deg, var(--success), #059669); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,.3); }
        .btn-success:hover { transform: translateY(-2px); }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); border: 2px solid var(--border-color); }
        .btn-sm { padding: .5rem 1rem; font-size: .825rem; }
        .btn-actions { display: flex; gap: .75rem; margin-top: 1.5rem; justify-content: flex-end; }

        /* Price Grid */
        .price-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }

        /* Toast */
        .toast { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 12px; color: #fff; font-weight: 600; z-index: 9999; transform: translateX(120%); transition: transform .4s; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, var(--success), #059669); }
        .toast-error { background: linear-gradient(135deg, var(--danger), #dc2626); }

        /* Loading */
        .loading-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9998; align-items: center; justify-content: center; }
        .loading-overlay.active { display: flex; }
        .spinner { width: 50px; height: 50px; border: 4px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 70px; }
            .sidebar .logo, .sidebar .admin-badge, .sidebar .nav-item span, .sidebar .logout-btn span { display: none; }
            .sidebar-header { padding: 1rem .5rem; text-align: center; }
            .nav-item { justify-content: center; padding: .75rem; }
            .main-content { margin-left: 70px; }
            .form-grid, .price-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
        <div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div>
    </div>
    <div class="sidebar-nav">
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-tag"></i><span>Products & Pricing</span></a>
        <a href="add_product.php" class="nav-item active"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_podcasts.php" class="nav-item"><i class="fas fa-podcast"></i><span>Podcast Management</span></a>
        <a href="daily_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php" class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer"><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
        <a href="admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <form id="addProductForm" enctype="multipart/form-data">

        <!-- Section 1: Basic Information -->
        <div class="form-card">
            <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Product Name (English) <span class="required">*</span></label>
                    <input type="text" name="name_en" id="nameEn" required placeholder="e.g. The Ordinary Niacinamide 10%">
                </div>
                <div class="form-group">
                    <label>Product Name (Arabic) <span class="required">*</span></label>
                    <input type="text" name="name_ar" dir="rtl" required placeholder="اسم المنتج بالعربي">
                </div>
                <div class="form-group">
                    <label>Short Description (English)</label>
                    <input type="text" name="short_description_en" placeholder="Brief one-line description" maxlength="255">
                    <div class="help-text">Max 255 characters. Shown on product cards.</div>
                </div>
                <div class="form-group">
                    <label>Short Description (Arabic)</label>
                    <input type="text" name="short_description_ar" dir="rtl" placeholder="وصف قصير بالعربي" maxlength="255">
                </div>
                <div class="form-group">
                    <label>Category / Subcategory</label>
                    <select name="subcategory_id" id="subcategorySelect">
                        <option value="">-- Select Subcategory --</option>
                        <?php foreach ($categories as $cat): ?>
                            <optgroup label="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Video Review URL</label>
                    <input type="url" name="video_review_url" placeholder="https://youtube.com/watch?v=...">
                    <div class="help-text">YouTube or other video link for product review.</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Pricing & Stock -->
        <div class="form-card">
            <h2><i class="fas fa-money-bill-wave"></i> Pricing & Stock</h2>
            <div class="price-grid">
                <div class="form-group">
                    <label>Selling Price (JOD) <span class="required">*</span></label>
                    <input type="number" name="price_jod" step="0.001" min="0" required placeholder="0.000">
                </div>
                <div class="form-group">
                    <label>Original Price (JOD)</label>
                    <input type="number" name="original_price" step="0.001" min="0" placeholder="0.000">
                    <div class="help-text">Leave empty to use selling price.</div>
                </div>
                <div class="form-group">
                    <label>Stock Quantity <span class="required">*</span></label>
                    <input type="number" name="stock_quantity" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label>Supplier Cost (JOD)</label>
                    <input type="number" name="supplier_cost" step="0.001" min="0" placeholder="0.000">
                </div>
                <div class="form-group">
                    <label>Public Price Min (JOD)</label>
                    <input type="number" name="public_price_min" step="0.001" min="0" placeholder="0.000">
                </div>
                <div class="form-group">
                    <label>Public Price Max (JOD)</label>
                    <input type="number" name="public_price_max" step="0.001" min="0" placeholder="0.000">
                </div>
            </div>
            <div class="form-grid" style="margin-top: 1rem;">
                <div class="form-group">
                    <label>Discount Percentage (%)</label>
                    <input type="number" name="discount_percentage" step="0.01" min="0" max="100" value="0" placeholder="0">
                    <div class="help-text">Set > 0 to automatically mark product as discounted.</div>
                </div>
            </div>
        </div>

        <!-- Section 3: Description & Details -->
        <div class="form-card">
            <h2><i class="fas fa-align-left"></i> Description & Details</h2>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Description</label>
                    <textarea name="description" rows="4" placeholder="Detailed product description..."></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Product Details</label>
                    <textarea name="product_details" rows="4" placeholder="Ingredients, specifications, etc..."></textarea>
                </div>
                <div class="form-group">
                    <label>How to Use (English)</label>
                    <textarea name="how_to_use_en" rows="3" placeholder="Usage instructions in English..."></textarea>
                </div>
                <div class="form-group">
                    <label>How to Use (Arabic)</label>
                    <textarea name="how_to_use_ar" rows="3" dir="rtl" placeholder="طريقة الاستخدام بالعربي..."></textarea>
                </div>
            </div>
        </div>

        <!-- Section 4: Images -->
        <div class="form-card">
            <h2><i class="fas fa-images"></i> Product Images</h2>
            <p style="color: var(--text-gray); margin-bottom: 1rem; font-size: .9rem;">
                Upload product images. The <strong>first image</strong> becomes the main thumbnail (1.png), additional images become gallery photos (2.png, 3.png, etc.).
            </p>
            <div class="image-upload-area">
                <input type="file" name="product_images[]" accept="image/*" multiple id="imageInput">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click or drag to upload images (multiple allowed)</p>
            </div>
            <div class="image-preview" id="imagePreview"></div>
        </div>

        <!-- Submit -->
        <div class="btn-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('addProductForm').reset(); document.getElementById('imagePreview').innerHTML='';">
                <i class="fas fa-undo"></i> Reset Form
            </button>
            <button type="submit" class="btn btn-success" id="submitBtn">
                <i class="fas fa-save"></i> Add Product
            </button>
        </div>
    </form>
</div>

<div class="toast" id="toast"></div>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 4000);
}
function showLoading(on) {
    document.getElementById('loadingOverlay').classList.toggle('active', on);
}

// Image preview
document.getElementById('imageInput').addEventListener('change', function() {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    Array.from(this.files).forEach((f, i) => {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        item.appendChild(img);
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.textContent = i === 0 ? 'Main' : (i + 1) + '.png';
        item.appendChild(badge);
        preview.appendChild(item);
    });
});

// Form submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const nameEn = document.querySelector('[name="name_en"]').value.trim();
    const nameAr = document.querySelector('[name="name_ar"]').value.trim();
    const price  = parseFloat(document.querySelector('[name="price_jod"]').value) || 0;

    if (!nameEn) { showToast('English name is required', 'error'); return; }
    if (!nameAr) { showToast('Arabic name is required', 'error'); return; }
    if (price <= 0) { showToast('Price must be greater than 0', 'error'); return; }

    const fd = new FormData(this);
    fd.append('ajax', '1');

    showLoading(true);
    document.getElementById('submitBtn').disabled = true;

    fetch('add_product.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showLoading(false);
            document.getElementById('submitBtn').disabled = false;
            if (data.success) {
                showToast(data.message);
                setTimeout(() => {
                    if (confirm('Product added! Add another product?')) {
                        this.reset();
                        document.getElementById('imagePreview').innerHTML = '';
                    } else {
                        window.location.href = 'admin_panel.php';
                    }
                }, 500);
            } else {
                showToast(data.error || 'Something went wrong', 'error');
            }
        })
        .catch(err => {
            showLoading(false);
            document.getElementById('submitBtn').disabled = false;
            showToast('Network error: ' + err.message, 'error');
        });
});
</script>
</body>
</html>
