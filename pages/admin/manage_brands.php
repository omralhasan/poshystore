<?php
/**
 * Manage Brands – Admin Panel
 * Add, view, delete brands
 */
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) { header('Location: ../../index.php'); exit(); }

// ─── AJAX handlers ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_brand') {
        $name_en = trim($_POST['name_en'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        if (!$name_en) { echo json_encode(['success'=>false,'error'=>'Brand name (English) is required']); exit(); }
        // Handle logo upload
        $logo = '';
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../uploads/brands/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $fname = 'brand_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fname);
            $logo = 'uploads/brands/' . $fname;
        }
        $stmt = $conn->prepare("INSERT INTO brands (name_en, name_ar, logo) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name_en, $name_ar, $logo);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'id'=>$stmt->insert_id,'name_en'=>$name_en,'name_ar'=>$name_ar,'logo'=>$logo]);
        } else {
            echo json_encode(['success'=>false,'error'=>'DB error: '.$conn->error]);
        }
        $stmt->close(); exit();
    }

    if ($action === 'delete_brand') {
        $id = intval($_POST['id'] ?? 0);
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM products WHERE brand_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $cnt = $check->get_result()->fetch_assoc()['cnt'];
        $check->close();
        if ($cnt > 0) {
            echo json_encode(['success'=>false,'error'=>"Cannot delete: $cnt product(s) are linked to this brand."]);
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success'=>$ok]); exit();
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action']); exit();
}

// ─── Load data ───────────────────────────────────────────────────────────────
$brands = [];
$res = $conn->query("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id) AS product_count FROM brands b ORDER BY b.sort_order, b.name_en");
if ($res) { while ($r = $res->fetch_assoc()) $brands[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Brands – Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary-dark:#1a1d2e;--secondary-dark:#242838;--accent-blue:#4f9eff;--accent-teal:#00d4aa;--accent-purple:#a855f7;--text-light:#fff;--text-gray:#9ca3af;--text-dark:#1f2937;--success:#10b981;--warning:#f59e0b;--danger:#ef4444;--bg-light:#f9fafb;--border-color:#e5e7eb;--shadow-md:0 4px 6px rgba(0,0,0,.1);}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:var(--bg-light);min-height:100vh;display:flex;color:var(--text-dark);}
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
.main-content{flex:1;margin-left:280px;padding:2rem;}
.page-header{background:#fff;padding:1.75rem 2rem;border-radius:16px;margin-bottom:2rem;box-shadow:var(--shadow-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.page-header h1{font-size:1.875rem;font-weight:700;background:linear-gradient(135deg,var(--accent-blue),var(--accent-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.card{background:#fff;border-radius:16px;padding:2rem;box-shadow:var(--shadow-md);margin-bottom:2rem;}
.card h2{font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;color:var(--primary-dark);padding-bottom:.75rem;border-bottom:2px solid var(--bg-light);}
.form-row{display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;}
.form-group{flex:1;min-width:200px;}
.form-group label{display:block;font-weight:600;margin-bottom:.5rem;font-size:.875rem;}
.form-group input,.form-group select{width:100%;padding:.75rem 1rem;border:2px solid var(--border-color);border-radius:10px;font-size:.925rem;font-family:inherit;transition:border-color .3s;background:var(--bg-light);}
.form-group input:focus{outline:none;border-color:var(--accent-blue);}
.btn{padding:.75rem 1.5rem;border:none;border-radius:10px;font-weight:600;font-size:.925rem;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;transition:all .3s;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,var(--accent-blue),#3b82f6);color:#fff;}
.btn-danger{background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;}
.btn-sm{padding:.5rem 1rem;font-size:.825rem;}
table{width:100%;border-collapse:collapse;}
th,td{padding:.875rem 1rem;text-align:left;border-bottom:1px solid var(--border-color);}
th{background:var(--bg-light);font-weight:700;font-size:.85rem;text-transform:uppercase;color:var(--text-gray);}
.brand-logo{width:40px;height:40px;border-radius:8px;object-fit:contain;background:#f3f4f6;padding:4px;}
.toast{position:fixed;top:2rem;right:2rem;padding:1rem 1.5rem;border-radius:12px;color:#fff;font-weight:600;z-index:9999;transform:translateX(120%);transition:transform .4s;box-shadow:0 10px 30px rgba(0,0,0,.2);}
.toast.show{transform:translateX(0);}
.toast-success{background:linear-gradient(135deg,var(--success),#059669);}
.toast-error{background:linear-gradient(135deg,var(--danger),#dc2626);}
.badge{padding:.25rem .6rem;border-radius:8px;font-size:.75rem;font-weight:600;}
.badge-info{background:rgba(79,158,255,.15);color:var(--accent-blue);}
@media(max-width:768px){.sidebar{display:none;}.main-content{margin-left:0;}.form-row{flex-direction:column;}}
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div><div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div></div>
    <div class="sidebar-nav">
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="manage_products.php" class="nav-item"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="add_product.php" class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_categories.php" class="nav-item"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="manage_brands.php" class="nav-item active"><i class="fas fa-copyright"></i><span>Brands</span></a>
        <a href="daily_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php" class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer"><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</div>

<div class="main-content">
    <div class="page-header"><h1><i class="fas fa-copyright"></i> Manage Brands</h1></div>

    <!-- Add Brand Form -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Add New Brand</h2>
        <form id="addBrandForm" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group"><label>Brand Name (EN) *</label><input type="text" name="name_en" required placeholder="e.g. ANUA"></div>
                <div class="form-group"><label>Brand Name (AR)</label><input type="text" name="name_ar" dir="rtl" placeholder="اسم العلامة التجارية"></div>
                <div class="form-group"><label>Brand Logo</label><input type="file" name="logo" accept="image/*"></div>
                <div class="form-group" style="flex:0;"><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button></div>
            </div>
        </form>
    </div>

    <!-- Brands List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> All Brands (<?= count($brands) ?>)</h2>
        <table>
            <thead><tr><th>#</th><th>Logo</th><th>Name (EN)</th><th>Name (AR)</th><th>Products</th><th>Action</th></tr></thead>
            <tbody id="brandsTableBody">
                <?php foreach ($brands as $b): ?>
                <tr id="brand-<?= $b['id'] ?>">
                    <td><?= $b['id'] ?></td>
                    <td><?php if ($b['logo']): ?><img src="../../<?= htmlspecialchars($b['logo']) ?>" class="brand-logo" alt=""><?php else: ?><i class="fas fa-image" style="color:#ccc;font-size:1.5rem;"></i><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($b['name_en']) ?></strong></td>
                    <td dir="rtl"><?= htmlspecialchars($b['name_ar'] ?? '') ?></td>
                    <td><span class="badge badge-info"><?= $b['product_count'] ?> products</span></td>
                    <td><button class="btn btn-danger btn-sm" onclick="deleteBrand(<?= $b['id'] ?>,this)"><i class="fas fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($brands)): ?><tr><td colspan="6" style="text-align:center;color:var(--text-gray);padding:2rem;">No brands yet. Add your first brand above.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='toast toast-'+type+' show';setTimeout(()=>t.classList.remove('show'),4000);}

document.getElementById('addBrandForm').addEventListener('submit',async function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fd.append('action','add_brand');
    try{
        const r=await fetch('manage_brands.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){showToast('Brand added!');setTimeout(()=>location.reload(),800);}
        else showToast(d.error||'Error','error');
    }catch(err){showToast('Network error','error');}
});

async function deleteBrand(id,btn){
    if(!confirm('Delete this brand?'))return;
    const fd=new FormData();fd.append('action','delete_brand');fd.append('id',id);
    try{
        const r=await fetch('manage_brands.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){btn.closest('tr').style.opacity='0';setTimeout(()=>btn.closest('tr').remove(),400);showToast('Brand deleted!');}
        else showToast(d.error||'Error','error');
    }catch(err){showToast('Network error','error');}
}
</script>
</body>
</html>
