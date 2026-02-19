<?php
/**
 * Coupon Management - Admin Panel
 * Allows admins to create, edit, and manage discount coupons
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_purchase = floatval($_POST['min_purchase'] ?? 0);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        
        if (empty($code) || $discount_value <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid coupon data']);
            exit();
        }
        
        $sql = "INSERT INTO coupons (code, discount_type, discount_value, min_purchase, max_discount, usage_limit, valid_until) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdddis', $code, $discount_type, $discount_value, $min_purchase, $max_discount, $usage_limit, $valid_until);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Coupon created successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create coupon: ' . $stmt->error]);
        }
        $stmt->close();
        exit();
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        $sql = "UPDATE coupons SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $is_active, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update status']);
        }
        $stmt->close();
        exit();
    }
    
    if ($action === 'delete_coupon') {
        $id = intval($_POST['id'] ?? 0);
        
        $sql = "DELETE FROM coupons WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete coupon']);
        }
        $stmt->close();
        exit();
    }
}

// Get all coupons
$sql = "SELECT * FROM coupons ORDER BY created_at DESC";
$result = $conn->query($sql);
$coupons = [];
while ($row = $result->fetch_assoc()) {
    $coupons[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2rem;
            color: #1a1d2e;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1d2e;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        
        .table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-percentage {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-fixed {
            background: #fef3c7;
            color: #92400e;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-toggle {
            background: #10b981;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #059669;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-ticket-alt"></i>
                Coupon Management
            </h1>
            <div>
                <a href="admin_panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <!-- Create Coupon Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle"></i> Create New Coupon
                </h2>
            </div>
            
            <div id="createAlert" class="alert"></div>
            
            <form id="createCouponForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Coupon Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., SAVE20" required maxlength="50" style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Discount Type *</label>
                        <select name="discount_type" class="form-control" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (JOD)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Discount Value *</label>
                        <input type="number" name="discount_value" class="form-control" placeholder="e.g., 20" required min="0" step="0.01">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Minimum Purchase (JOD)</label>
                        <input type="number" name="min_purchase" class="form-control" placeholder="0" min="0" step="0.01" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Max Discount (JOD)</label>
                        <input type="number" name="max_discount" class="form-control" placeholder="Optional" min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" placeholder="Unlimited" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="datetime-local" name="valid_until" class="form-control">
                        <small style="color: #6b7280; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-info-circle"></i> Leave empty for no expiration. Coupon is active immediately upon creation.
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Coupon
                </button>
            </form>
        </div>
        
        <!-- Coupons List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list"></i> All Coupons
                </h2>
            </div>
            
            <?php if (empty($coupons)): ?>
                <p style="text-align: center; padding: 2rem; color: #9ca3af;">No coupons found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min Purchase</th>
                            <th>Usage</th>
                            <th>Valid Until</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= $coupon['discount_type'] ?>">
                                        <?= ucfirst($coupon['discount_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        <?= $coupon['discount_value'] ?>%
                                        <?php if ($coupon['max_discount']): ?>
                                            <small>(max <?= formatJOD($coupon['max_discount']) ?>)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= formatJOD($coupon['discount_value']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatJOD($coupon['min_purchase']) ?></td>
                                <td>
                                    <?= $coupon['times_used'] ?> 
                                    <?php if ($coupon['usage_limit']): ?>
                                        / <?= $coupon['usage_limit'] ?>
                                    <?php else: ?>
                                        / ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['valid_until']): ?>
                                        <?= date('M j, Y', strtotime($coupon['valid_until'])) ?>
                                    <?php else: ?>
                                        No expiry
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $coupon['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $coupon['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="toggleStatus(<?= $coupon['id'] ?>, <?= $coupon['is_active'] ? 0 : 1 ?>)" 
                                            class="action-btn btn-toggle">
                                        <i class="fas fa-<?= $coupon['is_active'] ? 'pause' : 'play' ?>"></i>
                                        <?= $coupon['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                    <button onclick="deleteCoupon(<?= $coupon['id'] ?>)" 
                                            class="action-btn btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('createCouponForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_coupon');
            formData.append('ajax', '1');
            
            try {
                const response = await fetch('manage_coupons.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('createAlert', data.message, true);
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('createAlert', data.error, false);
                }
            } catch (error) {
                showAlert('createAlert', 'Network error. Please try again.', false);
            }
        });
        
        async function toggleStatus(id, newStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', id);
            formData.append('is_active', newStatus);
            formData.append('ajax', '1');
            
            try {
                const response = await fetch('manage_coupons.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }
        
        async function deleteCoupon(id) {
            if (!confirm('Are you sure you want to delete this coupon?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_coupon');
            formData.append('id', id);
            formData.append('ajax', '1');
            
            try {
                const response = await fetch('manage_coupons.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }
        
        function showAlert(elementId, message, isSuccess) {
            const alert = document.getElementById(elementId);
            alert.textContent = message;
            alert.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-error');
            alert.style.display = 'block';
            
            if (isSuccess) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 3000);
            }
        }
    </script>
</body>
</html>
