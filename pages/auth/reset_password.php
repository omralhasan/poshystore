<?php
/**
 * Reset Password – Verify token & set new password
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

$success = '';
$error = '';
$valid_token = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    // Validate token
    $stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.email, u.firstname 
                            FROM password_resets pr 
                            JOIN users u ON u.id = pr.user_id 
                            WHERE pr.token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        $error = 'Invalid reset link. Please request a new one.';
    } elseif ($reset['used']) {
        $error = 'This reset link has already been used. Please request a new one.';
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = 'This reset link has expired. Please request a new one.';
    } else {
        $valid_token = true;

        // Handle password update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            $password = $_POST['password'];
            $confirm = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
                $valid_token = true;
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
                $valid_token = true;
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Update password
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param('si', $hashed, $reset['user_id']);
                $upd->execute();
                $upd->close();

                // Mark token as used
                $conn->query("UPDATE password_resets SET used = 1 WHERE id = {$reset['id']}");

                $success = 'Your password has been reset successfully! You can now sign in.';
                $valid_token = false;
            }
        }
    }
}

$current_lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $current_lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Poshy Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple-color: #6c3fa0;
            --purple-dark: #483670;
            --gold-color: #c9a86a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #2d132c 0%, #483670 50%, #1a0a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo-text { text-align: center; margin-bottom: 1.5rem; }
        .logo-text h1 { color: var(--purple-color); font-size: 1.8rem; font-weight: 700; }
        .logo-text h1 span { display: block; font-size: 0.55rem; letter-spacing: 6px; font-weight: 300; color: var(--gold-color); }
        .logo-text p { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
        .form-control-ramadan {
            border: 2px solid rgba(108,63,160,0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        .form-control-ramadan:focus {
            border-color: var(--purple-color);
            box-shadow: 0 0 0 3px rgba(108,63,160,0.1);
            outline: none;
        }
        .btn-ramadan {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            color: white; border: none; border-radius: 10px;
            padding: 0.8rem; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s; width: 100%;
        }
        .btn-ramadan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108,63,160,0.4);
            color: white;
        }
        .alert-box {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        .back-link { text-align: center; margin-top: 1.2rem; }
        .back-link a { color: var(--purple-color); text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .back-link a:hover { text-decoration: underline; }
        .password-strength { height: 4px; border-radius: 2px; margin-top: 6px; transition: all 0.3s; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-text">
            <h1>Poshy<span>STORE</span></h1>
            <p><i class="fas fa-key me-1"></i> Create new password</p>
        </div>

        <?php if ($success): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
            </div>
            <div class="back-link">
                <a href="signin.php" class="btn-ramadan" style="display:inline-block;text-decoration:none;text-align:center;margin-top:0.5rem;">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Sign In
                </a>
            </div>
        <?php elseif ($error && !$valid_token): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
            <div class="back-link">
                <a href="forgot_password.php"><i class="fas fa-redo me-1"></i> Request New Reset Link</a>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <?php if ($error): ?>
                <div class="alert-box alert-error">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="mb-3">
                    <label for="password" class="form-label" style="color: var(--gold-color); font-weight: 500;">New Password</label>
                    <input type="password" name="password" id="password" class="form-control-ramadan" required 
                           placeholder="Minimum 6 characters" minlength="6"
                           oninput="checkStrength(this.value)">
                    <div class="password-strength" id="strengthBar"></div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label" style="color: var(--gold-color); font-weight: 500;">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-ramadan" required 
                           placeholder="Re-enter password">
                </div>
                <button type="submit" class="btn-ramadan">
                    <i class="fas fa-save me-2"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>

        <?php if (!$success && $valid_token): ?>
        <div class="back-link">
            <a href="signin.php"><i class="fas fa-arrow-left me-1"></i> Back to Sign In</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function checkStrength(pw) {
        const bar = document.getElementById('strengthBar');
        let score = 0;
        if (pw.length >= 6) score++;
        if (pw.length >= 10) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
        const widths = ['20%', '40%', '60%', '80%', '100%'];
        bar.style.width = widths[Math.min(score, 4)];
        bar.style.background = colors[Math.min(score, 4)];
    }
    </script>
</body>
</html>
