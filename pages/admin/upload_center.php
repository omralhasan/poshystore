<?php
/**
 * Upload Center – Admin Dashboard
 * Multi-file banner upload + product image upload with live preview
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// ─── Upload directories ──────────────────────────────────────────────────
$banner_dir  = __DIR__ . '/../../assets/img/banners/';
$product_dir = __DIR__ . '/../../uploads/products/';
if (!is_dir($banner_dir))  mkdir($banner_dir, 0755, true);
if (!is_dir($product_dir)) mkdir($product_dir, 0755, true);

$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
$max_size    = 10 * 1024 * 1024; // 10 MB

// ─── AJAX Handlers ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ──── UPLOAD BANNERS (multiple) ────
    if ($action === 'upload_banners') {
        if (empty($_FILES['banner_files'])) {
            echo json_encode(['success' => false, 'error' => 'No files selected']);
            exit();
        }

        $results   = [];
        $files     = $_FILES['banner_files'];
        $file_count = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $file_count; $i++) {
            $name  = $files['name'][$i];
            $tmp   = $files['tmp_name'][$i];
            $size  = $files['size'][$i];
            $error = $files['error'][$i];
            $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK) {
                $results[] = ['name' => $name, 'success' => false, 'error' => 'Upload error code ' . $error];
                continue;
            }
            if (!in_array($ext, $allowed_ext)) {
                $results[] = ['name' => $name, 'success' => false, 'error' => 'Invalid file type'];
                continue;
            }
            if ($size > $max_size) {
                $results[] = ['name' => $name, 'success' => false, 'error' => 'File exceeds 10MB limit'];
                continue;
            }

            $filename = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $dest     = $banner_dir . $filename;

            if (move_uploaded_file($tmp, $dest)) {
                $relative = 'assets/img/banners/' . $filename;
                // Save to DB as hero banner by default
                $stmt = $conn->prepare("INSERT INTO homepage_banners (title, image_path, position, is_active) VALUES (?, ?, 0, 1)");
                $clean_name = pathinfo($name, PATHINFO_FILENAME);
                $stmt->bind_param('ss', $clean_name, $relative);
                $stmt->execute();
                $new_id = $stmt->insert_id;
                $stmt->close();

                $results[] = [
                    'name'    => $name,
                    'success' => true,
                    'id'      => $new_id,
                    'path'    => $relative,
                    'url'     => '../../' . $relative,
                ];
            } else {
                $results[] = ['name' => $name, 'success' => false, 'error' => 'Server write failed'];
            }
        }
        echo json_encode(['success' => true, 'results' => $results, 'count' => count(array_filter($results, fn($r) => $r['success']))]);
        exit();
    }

    // ──── UPLOAD PRODUCT IMAGE (single, with preview) ────
    if ($action === 'upload_product_image') {
        $product_id = intval($_POST['product_id'] ?? 0);

        if (empty($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No valid file uploaded']);
            exit();
        }

        $file = $_FILES['product_image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_ext)]);
            exit();
        }
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'File exceeds 10MB limit']);
            exit();
        }

        $filename = 'product_' . ($product_id > 0 ? $product_id . '_' : '') . time() . '.' . $ext;
        $dest     = $product_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'error' => 'Server write failed']);
            exit();
        }

        $relative = 'uploads/products/' . $filename;

        // If product_id provided, update the product's image_url
        if ($product_id > 0) {
            $stmt = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
            $stmt->bind_param('si', $relative, $product_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode([
            'success'    => true,
            'path'       => $relative,
            'url'        => '../../' . $relative,
            'product_id' => $product_id,
        ]);
        exit();
    }

    // ──── DELETE BANNER FILE ────
    if ($action === 'delete_banner_file') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit();
        }

        $lookup = $conn->prepare("SELECT image_path FROM homepage_banners WHERE id = ?");
        if (!$lookup) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare banner lookup']);
            exit();
        }
        $lookup->bind_param('i', $id);
        $lookup->execute();
        $row = $lookup->get_result()->fetch_assoc();
        $lookup->close();

        if ($row) {
            $full = __DIR__ . '/../../' . $row['image_path'];
            if (file_exists($full)) @unlink($full);

            $delete = $conn->prepare("DELETE FROM homepage_banners WHERE id = ?");
            if (!$delete) {
                echo json_encode(['success' => false, 'error' => 'Failed to prepare banner deletion']);
                exit();
            }
            $delete->bind_param('i', $id);
            if ($delete->execute() && $delete->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete banner record']);
            }
            $delete->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Not found']);
        }
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit();
}

// ─── Load existing banners ───────────────────────────────────────────────
$banners = [];
$res = $conn->query("SELECT * FROM homepage_banners WHERE is_active = 1 ORDER BY id DESC LIMIT 30");
if ($res) {
    while ($r = $res->fetch_assoc()) $banners[] = $r;
}

// ─── Load products for dropdown ──────────────────────────────────────────
$products = [];
$pres = $conn->query("SELECT id, name_en, image_url FROM products ORDER BY name_en ASC LIMIT 200");
if ($pres) {
    while ($p = $pres->fetch_assoc()) $products[] = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Center – Poshy Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand:  { 50: '#fdf8f0', 100: '#f8e8d4', 200: '#f0d0a8', 300: '#e5b074', 400: '#d9924a', 500: '#C5A059', 600: '#a8792e', 700: '#8a5f20', 800: '#6d4916', 900: '#523710' },
                    panel:  { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db', 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151', 800: '#1f2937', 900: '#111827' },
                    accent: { blue: '#4f9eff', teal: '#00d4aa', purple: '#a855f7' },
                },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,.03); }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 3px; }

        /* Drag-over state */
        .drop-zone.drag-over { border-color: #4f9eff !important; background: rgba(79,158,255,.06) !important; }
        .drop-zone.drag-over .drop-icon { transform: scale(1.12) translateY(-4px); color: #4f9eff; }

        /* Animate cards in */
        @keyframes cardIn { from { opacity: 0; transform: translateY(12px) scale(.97); } to { opacity: 1; transform: none; } }
        .card-animate { animation: cardIn .35s ease-out both; }

        /* Progress bar shimmer */
        @keyframes shimmer { to { background-position: 200% 0; } }
        .progress-shimmer {
            background: linear-gradient(90deg, #4f9eff 0%, #00d4aa 50%, #4f9eff 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s ease infinite;
        }

        /* Pulse glow on success */
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.4); } 50% { box-shadow: 0 0 0 10px rgba(16,185,129,0); } }
        .success-glow { animation: pulseGlow 1.5s ease 1; }

        /* Sidebar transition on mobile */
        @media (max-width: 1024px) {
            .sidebar-panel { transform: translateX(-100%); }
            .sidebar-panel.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-panel-50 font-sans min-h-screen flex">

<!-- ═══════ SIDEBAR ═══════ -->
<aside class="sidebar-panel fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-panel-900 to-panel-800 text-white flex flex-col transition-transform duration-300 lg:translate-x-0 shadow-2xl">
    <div class="p-6 border-b border-white/10">
        <div class="text-2xl font-extrabold bg-gradient-to-r from-accent-blue to-accent-teal bg-clip-text text-transparent">
            <i class="fas fa-shopping-bag mr-1"></i> POSHY
        </div>
        <span class="inline-block mt-2 text-[0.68rem] font-semibold tracking-wider bg-gradient-to-r from-accent-purple to-accent-blue text-white px-3 py-1 rounded-full">ADMIN PANEL</span>
    </div>
    <nav class="flex-1 py-5 space-y-0.5 overflow-y-auto">
        <a href="/pages/admin/admin_panel.php"       class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-box w-5 text-center"></i><span>Orders</span></a>
        <a href="/pages/admin/manage_products.php"   class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-tag w-5 text-center"></i><span>Products</span></a>
        <a href="add_product.php"       class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-plus-circle w-5 text-center"></i><span>Add Product</span></a>
        <a href="/pages/admin/manage_coupons.php"    class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-ticket-alt w-5 text-center"></i><span>Coupons</span></a>
        <a href="/pages/admin/manage_categories.php" class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-layer-group w-5 text-center"></i><span>Categories</span></a>
        <a href="/pages/admin/manage_brands.php"     class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-copyright w-5 text-center"></i><span>Brands</span></a>
        <a href="/pages/admin/manage_banners.php"    class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-images w-5 text-center"></i><span>Banners</span></a>
        <a href="/pages/admin/upload_center.php"     class="flex items-center gap-3 px-6 py-3 text-accent-blue bg-accent-blue/10 border-l-[3px] border-accent-blue font-semibold"><i class="fas fa-cloud-upload-alt w-5 text-center"></i><span>Upload Center</span></a>
        <a href="/pages/admin/daily_reports.php"     class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-chart-line w-5 text-center"></i><span>Reports</span></a>
        <a href="/index.php"       class="flex items-center gap-3 px-6 py-3 text-panel-400 hover:text-white hover:bg-white/5 transition-all border-l-[3px] border-transparent"><i class="fas fa-store w-5 text-center"></i><span>Visit Store</span></a>
    </nav>
    <div class="p-4 border-t border-white/10">
        <a href="/pages/auth/logout.php" class="flex items-center justify-center gap-2 w-full py-3 bg-gradient-to-r from-red-500 to-red-600 rounded-xl font-semibold text-sm shadow-lg shadow-red-500/30 hover:-translate-y-0.5 transition-all"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</aside>

<!-- Mobile sidebar toggle -->
<button onclick="document.querySelector('.sidebar-panel').classList.toggle('open')" class="lg:hidden fixed top-4 left-4 z-[60] w-10 h-10 bg-panel-800 text-white rounded-lg flex items-center justify-center shadow-lg">
    <i class="fas fa-bars"></i>
</button>

<!-- ═══════ MAIN CONTENT ═══════ -->
<main class="flex-1 lg:ml-72 p-4 md:p-8 overflow-x-hidden">

    <!-- Page Header -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold bg-gradient-to-r from-accent-blue to-accent-teal bg-clip-text text-transparent tracking-tight">
                <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Center
            </h1>
            <p class="text-panel-400 text-sm mt-1">Upload store banners and product images in one place</p>
        </div>
        <a href="/pages/admin/admin_panel.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-panel-100 text-panel-600 rounded-xl text-sm font-semibold hover:bg-panel-200 transition-all">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- ═══════ SECTION 1: STORE BANNERS (MULTI-UPLOAD) ═══════ -->
    <section class="bg-white rounded-2xl shadow-md p-6 md:p-8 mb-8 card-animate">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                <i class="fas fa-images"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-panel-800">Store Banners</h2>
                <p class="text-xs text-panel-400">Upload multiple banner images at once — drag & drop supported</p>
            </div>
        </div>

        <!-- Size recommendation -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl px-4 py-3 mb-6 flex items-center gap-3 text-sm text-amber-800">
            <i class="fas fa-lightbulb text-amber-500 text-lg"></i>
            <span>Recommended size: <strong>1600 × 500 px</strong> (landscape). Accepted: JPG, PNG, WebP, GIF, SVG. Max <strong>10 MB</strong> per file.</span>
        </div>

        <!-- Drop zone -->
        <div id="bannerDropZone" class="drop-zone relative border-2 border-dashed border-panel-200 rounded-2xl p-10 text-center cursor-pointer transition-all duration-300 hover:border-accent-blue/50 group">
            <input type="file" id="bannerFileInput" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            <div class="drop-icon transition-all duration-300">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-accent-blue/10 to-accent-teal/10 flex items-center justify-center mb-4 group-hover:from-accent-blue/20 group-hover:to-accent-teal/20 transition-all">
                    <i class="fas fa-cloud-upload-alt text-3xl text-accent-blue"></i>
                </div>
                <p class="text-panel-700 font-semibold text-lg mb-1">Drop banner images here</p>
                <p class="text-panel-400 text-sm">or <span class="text-accent-blue font-medium underline underline-offset-2">browse files</span></p>
            </div>
        </div>

        <!-- Preview grid (before upload) -->
        <div id="bannerPreviewGrid" class="hidden mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-panel-700"><i class="fas fa-eye mr-1"></i> Preview <span id="bannerPreviewCount" class="text-accent-blue">0</span> files</h3>
                <div class="flex gap-2">
                    <button onclick="clearBannerPreviews()" class="px-4 py-2 text-sm font-medium bg-panel-100 text-panel-600 rounded-xl hover:bg-panel-200 transition-all">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                    <button onclick="uploadAllBanners()" id="uploadBannersBtn" class="px-5 py-2 text-sm font-semibold bg-gradient-to-r from-accent-blue to-blue-500 text-white rounded-xl shadow-lg shadow-blue-500/30 hover:-translate-y-0.5 transition-all">
                        <i class="fas fa-upload mr-1"></i> Upload All
                    </button>
                </div>
            </div>

            <!-- Progress bar -->
            <div id="bannerProgress" class="hidden mb-4">
                <div class="w-full bg-panel-100 rounded-full h-2.5 overflow-hidden">
                    <div id="bannerProgressBar" class="progress-shimmer h-full rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
                <p id="bannerProgressText" class="text-xs text-panel-400 mt-1.5 text-center">Uploading...</p>
            </div>

            <div id="bannerPreviewItems" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"></div>
        </div>

        <!-- Existing banners -->
        <?php if (!empty($banners)): ?>
        <div class="mt-8 pt-6 border-t border-panel-100">
            <h3 class="font-semibold text-panel-700 mb-4"><i class="fas fa-folder-open mr-1"></i> Existing Banners <span class="text-panel-400 font-normal">(<?= count($banners) ?>)</span></h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($banners as $b): ?>
                <div class="group relative rounded-xl overflow-hidden border border-panel-100 shadow-sm hover:shadow-md transition-all" id="existingBanner-<?= $b['id'] ?>">
                    <img src="../../<?= htmlspecialchars($b['image_path']) ?>" alt="<?= htmlspecialchars($b['title'] ?? 'Banner') ?>" class="w-full aspect-[21/9] object-cover lg:object-contain lg:bg-panel-50" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                        <span class="text-white text-xs font-medium truncate flex-1"><?= htmlspecialchars($b['title'] ?: 'Banner #' . $b['id']) ?></span>
                        <button onclick="deleteBannerFile(<?= $b['id'] ?>, this)" class="w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center text-xs hover:bg-red-600 transition-colors shrink-0 ml-2">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ═══════ SECTION 2: PRODUCT IMAGE UPLOAD ═══════ -->
    <section class="bg-white rounded-2xl shadow-md p-6 md:p-8 card-animate" style="animation-delay:.1s">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                <i class="fas fa-box-open"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-panel-800">Product Image</h2>
                <p class="text-xs text-panel-400">Upload a product image with live preview — optionally link to a product</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left: Upload form -->
            <div>
                <!-- Product selector -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-panel-700 mb-1.5">Link to Product <span class="text-panel-400 font-normal">(optional)</span></label>
                    <div class="relative">
                        <select id="productSelect" class="w-full appearance-none pl-4 pr-10 py-3 border-2 border-panel-200 rounded-xl text-sm bg-panel-50 focus:outline-none focus:border-accent-blue focus:ring-2 focus:ring-accent-blue/10 transition-all">
                            <option value="0">— Standalone upload (no product linked) —</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name_en']) ?> (#<?= $p['id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-panel-400 pointer-events-none"><i class="fas fa-chevron-down text-xs"></i></div>
                    </div>
                </div>

                <!-- Upload zone -->
                <div id="productDropZone" class="drop-zone relative border-2 border-dashed border-panel-200 rounded-2xl p-8 text-center cursor-pointer transition-all duration-300 hover:border-purple-400/50 group">
                    <input type="file" id="productFileInput" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="drop-icon transition-all duration-300">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-purple-50 to-violet-50 flex items-center justify-center mb-3 group-hover:from-purple-100 group-hover:to-violet-100 transition-all">
                            <i class="fas fa-image text-2xl text-purple-500"></i>
                        </div>
                        <p class="text-panel-700 font-semibold mb-0.5">Drop product image here</p>
                        <p class="text-panel-400 text-sm">or <span class="text-purple-500 font-medium underline underline-offset-2">browse</span></p>
                    </div>
                </div>

                <!-- Upload button -->
                <button id="uploadProductBtn" onclick="uploadProductImage()" disabled class="mt-4 w-full py-3 bg-gradient-to-r from-purple-500 to-violet-600 text-white font-semibold rounded-xl shadow-lg shadow-purple-500/30 hover:-translate-y-0.5 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:translate-y-0">
                    <i class="fas fa-save mr-2"></i> Save Product Image
                </button>

                <!-- Upload result -->
                <div id="productUploadResult" class="hidden mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"></div>
            </div>

            <!-- Right: Live preview -->
            <div>
                <label class="block text-sm font-semibold text-panel-700 mb-1.5">Live Preview</label>
                <div id="productPreviewContainer" class="border-2 border-panel-100 rounded-2xl overflow-hidden bg-gradient-to-br from-panel-50 to-panel-100 flex items-center justify-center min-h-[320px] transition-all">
                    <div id="productPreviewEmpty" class="text-center p-8">
                        <div class="w-20 h-20 mx-auto rounded-full bg-panel-200/70 flex items-center justify-center mb-4">
                            <i class="fas fa-image text-3xl text-panel-300"></i>
                        </div>
                        <p class="text-panel-400 font-medium">No image selected</p>
                        <p class="text-panel-300 text-sm mt-1">Select a file to see the preview</p>
                    </div>
                    <img id="productPreviewImg" src="" alt="Product preview" class="hidden w-full h-auto object-contain max-h-[400px]">
                </div>
                <div id="productPreviewMeta" class="hidden mt-3 flex items-center justify-between bg-panel-50 rounded-xl px-4 py-2.5 border border-panel-100">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fas fa-file-image text-purple-400"></i>
                        <span id="productFileName" class="text-sm text-panel-600 truncate"></span>
                    </div>
                    <span id="productFileSize" class="text-xs text-panel-400 font-mono shrink-0 ml-3"></span>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Toast -->
<div id="toast" class="fixed top-6 right-6 z-[100] transform translate-x-[120%] transition-transform duration-400 px-5 py-3.5 rounded-xl shadow-xl text-white font-semibold text-sm"></div>

<script>
// ─── Utils ───────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `fixed top-6 right-6 z-[100] transform transition-transform duration-400 px-5 py-3.5 rounded-xl shadow-xl text-white font-semibold text-sm translate-x-0 ${type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600'}`;
    setTimeout(() => t.classList.replace('translate-x-0', 'translate-x-[120%]'), 4000);
}

function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
}

// ═══════ SECTION 1: BANNER MULTI-UPLOAD ═══════

let bannerFiles = [];

function setupDropZone(zoneId, inputId, onFiles) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);

    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        onFiles(e.dataTransfer.files);
    });
    input.addEventListener('change', function() { if (this.files.length) onFiles(this.files); });
}

setupDropZone('bannerDropZone', 'bannerFileInput', handleBannerFiles);

function handleBannerFiles(fileList) {
    const grid     = document.getElementById('bannerPreviewGrid');
    const items    = document.getElementById('bannerPreviewItems');
    const countEl  = document.getElementById('bannerPreviewCount');

    for (const file of fileList) {
        if (!file.type.startsWith('image/')) continue;
        bannerFiles.push(file);

        const reader = new FileReader();
        reader.onload = e => {
            const card = document.createElement('div');
            card.className = 'relative rounded-xl overflow-hidden border border-panel-100 shadow-sm card-animate';
            card.innerHTML = `
                <img src="${e.target.result}" class="w-full aspect-[21/9] object-cover lg:object-contain lg:bg-panel-50" alt="Preview">
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent px-3 py-2">
                    <p class="text-white text-[0.7rem] font-medium truncate">${file.name}</p>
                    <p class="text-white/60 text-[0.65rem]">${formatBytes(file.size)}</p>
                </div>
                <button onclick="removeBannerPreview(this, ${bannerFiles.length - 1})" class="absolute top-2 right-2 w-6 h-6 bg-red-500/90 text-white rounded-full flex items-center justify-center text-[0.65rem] hover:bg-red-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            `;
            items.appendChild(card);
        };
        reader.readAsDataURL(file);
    }

    countEl.textContent = bannerFiles.length;
    grid.classList.remove('hidden');
}

function removeBannerPreview(btn, idx) {
    const card = btn.closest('.card-animate') || btn.parentElement;
    card.style.opacity = '0';
    card.style.transform = 'scale(.9)';
    setTimeout(() => card.remove(), 250);
    bannerFiles[idx] = null; // mark as removed
    document.getElementById('bannerPreviewCount').textContent = bannerFiles.filter(Boolean).length;
}

function clearBannerPreviews() {
    bannerFiles = [];
    document.getElementById('bannerPreviewItems').innerHTML = '';
    document.getElementById('bannerPreviewGrid').classList.add('hidden');
    document.getElementById('bannerFileInput').value = '';
}

async function uploadAllBanners() {
    const validFiles = bannerFiles.filter(Boolean);
    if (!validFiles.length) { showToast('No files to upload', 'error'); return; }

    const btn     = document.getElementById('uploadBannersBtn');
    const progEl  = document.getElementById('bannerProgress');
    const bar     = document.getElementById('bannerProgressBar');
    const textEl  = document.getElementById('bannerProgressText');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...';
    progEl.classList.remove('hidden');

    const fd = new FormData();
    fd.append('action', 'upload_banners');
    validFiles.forEach(f => fd.append('banner_files[]', f));

    // Simulated progress (real XHR would do this differently)
    let pct = 0;
    const pTimer = setInterval(() => {
        pct = Math.min(pct + Math.random() * 15, 90);
        bar.style.width = pct + '%';
        textEl.textContent = `Uploading ${validFiles.length} files... ${Math.round(pct)}%`;
    }, 300);

    try {
        const res = await fetch('upload_center.php', { method: 'POST', body: fd });
        const data = await res.json();
        clearInterval(pTimer);
        bar.style.width = '100%';

        if (data.success) {
            textEl.textContent = `✓ ${data.count} of ${validFiles.length} banners uploaded successfully!`;
            bar.classList.remove('progress-shimmer');
            bar.className += ' bg-emerald-500';
            showToast(`${data.count} banners uploaded!`);
            setTimeout(() => location.reload(), 1500);
        } else {
            textEl.textContent = data.error || 'Upload failed';
            showToast(data.error || 'Upload failed', 'error');
        }
    } catch (err) {
        clearInterval(pTimer);
        showToast('Network error', 'error');
        textEl.textContent = 'Network error. Please try again.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Upload All';
    }
}

function deleteBannerFile(id, btn) {
    if (!confirm('Delete this banner?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_banner_file');
    fd.append('id', id);
    btn.disabled = true;

    fetch('upload_center.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const el = document.getElementById('existingBanner-' + id);
                if (el) { el.style.opacity = '0'; el.style.transform = 'scale(.9)'; setTimeout(() => el.remove(), 250); }
                showToast('Banner deleted');
            } else {
                showToast(data.error || 'Delete failed', 'error');
                btn.disabled = false;
            }
        });
}


// ═══════ SECTION 2: PRODUCT IMAGE UPLOAD ═══════

let selectedProductFile = null;

setupDropZone('productDropZone', 'productFileInput', handleProductFile);

function handleProductFile(fileList) {
    const file = fileList[0];
    if (!file || !file.type.startsWith('image/')) { showToast('Please select an image file', 'error'); return; }

    selectedProductFile = file;

    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('productPreviewEmpty').classList.add('hidden');
        const img = document.getElementById('productPreviewImg');
        img.src = e.target.result;
        img.classList.remove('hidden');

        // Meta
        document.getElementById('productPreviewMeta').classList.remove('hidden');
        document.getElementById('productFileName').textContent = file.name;
        document.getElementById('productFileSize').textContent = formatBytes(file.size);

        // Enable save button
        document.getElementById('uploadProductBtn').disabled = false;
    };
    reader.readAsDataURL(file);
}

async function uploadProductImage() {
    if (!selectedProductFile) { showToast('No image selected', 'error'); return; }

    const btn = document.getElementById('uploadProductBtn');
    const productId = document.getElementById('productSelect').value;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    const fd = new FormData();
    fd.append('action', 'upload_product_image');
    fd.append('product_image', selectedProductFile);
    fd.append('product_id', productId);

    try {
        const res = await fetch('upload_center.php', { method: 'POST', body: fd });
        const data = await res.json();

        const resultEl = document.getElementById('productUploadResult');
        if (data.success) {
            resultEl.classList.remove('hidden');
            resultEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Image saved to <code class="bg-emerald-100 px-2 py-0.5 rounded text-xs">${data.path}</code>` +
                (data.product_id > 0 ? ` — linked to product #${data.product_id}` : '');

            document.getElementById('productPreviewContainer').classList.add('success-glow');
            showToast('Product image saved!');
        } else {
            resultEl.classList.remove('hidden');
            resultEl.className = 'mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm';
            resultEl.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${data.error}`;
            showToast(data.error, 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Product Image';
    }
}
</script>
</body>
</html>
