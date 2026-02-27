<?php
/**
 * Forgot Password – Send reset link via email
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/language.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, firstname FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Create password_resets table if it doesn't exist
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id)
            )");

            // Delete any existing unused tokens for this user
            $conn->query("DELETE FROM password_resets WHERE user_id = {$user['id']}");

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param('iss', $user['id'], $token, $expires);
            $ins->execute();
            $ins->close();

            // Build reset link
            $reset_link = 'https://poshystore.com/pages/auth/reset_password.php?token=' . $token;

            // Send email
            $to = $email;
            $subject = 'Poshy Store – Reset Your Password';
            $firstname = htmlspecialchars($user['firstname']);

            $html_body = "
            <div style='font-family:Arial,sans-serif;max-width:560px;margin:0 auto;background:#faf8ff;border-radius:16px;overflow:hidden;border:1px solid #e8e0f0;'>
                <div style='background:linear-gradient(135deg,#6c3fa0,#483670);padding:28px;text-align:center;'>
                    <h1 style='color:#c9a86a;margin:0;font-size:1.6rem;'>Poshy Store</h1>
                </div>
                <div style='padding:32px 28px;'>
                    <p style='color:#333;font-size:1rem;'>Hi <strong>{$firstname}</strong>,</p>
                    <p style='color:#555;font-size:0.95rem;line-height:1.6;'>
                        We received a request to reset your password. Click the button below to create a new password.
                        This link will expire in <strong>1 hour</strong>.
                    </p>
                    <div style='text-align:center;margin:28px 0;'>
                        <a href='{$reset_link}' style='display:inline-block;background:linear-gradient(135deg,#6c3fa0,#8b5fc7);color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem;box-shadow:0 4px 15px rgba(108,63,160,0.3);'>
                            Reset Password
                        </a>
                    </div>
                    <p style='color:#888;font-size:0.85rem;line-height:1.5;'>
                        If you didn't request this, you can safely ignore this email.<br>
                        Or copy this link: <a href='{$reset_link}' style='color:#6c3fa0;'>{$reset_link}</a>
                    </p>
                </div>
                <div style='background:#f0ecf5;padding:16px;text-align:center;'>
                    <small style='color:#888;'>© " . date('Y') . " Poshy Store. All rights reserved.</small>
                </div>
            </div>";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Poshy Store <noreply@poshystore.com>\r\n";
            $headers .= "Reply-To: info@poshystore.com\r\n";

            $sent = @mail($to, $subject, $html_body, $headers);

            if ($sent) {
                $success = 'Password reset link has been sent to your email!';
            } else {
                $error = 'Failed to send email. Please try again or contact support.';
            }
        } else {
            // Don't reveal if email exists — show same success message
            $success = 'If this email is registered, a reset link has been sent.';
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
    <title>Forgot Password - Poshy Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple-color: #6c3fa0;
            --purple-dark: #483670;
            --gold-color: #c9a86a;
            --gold-light: #e8d5b7;
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
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-text">
            <h1>Poshy<span>STORE</span></h1>
            <p><i class="fas fa-lock me-1"></i> Reset your password</p>
        </div>

        <?php if ($success): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label" style="color: var(--gold-color); font-weight: 500;">Email Address</label>
                <input type="email" name="email" id="email" class="form-control-ramadan" required 
                       placeholder="Enter your registered email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-ramadan">
                <i class="fas fa-paper-plane me-2"></i> Send Reset Link
            </button>
        </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="signin.php"><i class="fas fa-arrow-left me-1"></i> Back to Sign In</a>
        </div>
    </div>
</body>
</html>
