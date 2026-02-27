<?php
// Start session FIRST before any output
// Configure session for OAuth compatibility
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_start();

// Include language system
require_once __DIR__ . '/../../includes/language.php';

// Handle logout request BEFORE any output
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: signin.php');
    exit();
}

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit();
}

// Process login form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    // Load database connection
    require_once __DIR__ . '/../../includes/db_connect.php';
    
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        header('Location: signin.php?error=' . urlencode(t('email_password_required')));
        exit();
    }
    
    // Check connection
    if (!$conn || $conn->connect_error) {
        header('Location: signin.php?error=' . urlencode(t('database_connection_failed')));
        exit();
    }
    
    // Prepare SELECT statement
    $stmt = $conn->prepare('SELECT id, firstname, lastname, phonenumber, email, password, role, oauth_provider FROM users WHERE email = ?');
    
    if (!$stmt) {
        $conn->close();
        header('Location: signin.php?error=' . urlencode(t('database_error')));
        exit();
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user signed up via OAuth and has no password
        if ($user['password'] === null || empty($user['password'])) {
            $stmt->close();
            $conn->close();
            $oauth_provider = ucfirst($user['oauth_provider'] ?? t('social_media'));
            $error_msg = sprintf(t('account_created_with_oauth'), $oauth_provider, $oauth_provider);
            header('Location: signin.php?error=' . urlencode($error_msg));
            exit();
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phonenumber'] = $user['phonenumber'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            $stmt->close();
            $conn->close();
            
            // Redirect based on user role - use absolute paths from root
            $redirect = ($user['role'] === 'admin') ? BASE_PATH . '/pages/admin/admin_panel.php' : BASE_PATH . '/index.php';
            header('Location: ' . $redirect);
            exit();
        } else {
            // Incorrect password
            $stmt->close();
            $conn->close();
            header('Location: signin.php?error=' . urlencode(t('invalid_email_or_password')));
            exit();
        }
    } else {
        // User not found
        $stmt->close();
        $conn->close();
        header('Location: signin.php?error=' . urlencode(t('invalid_email_or_password')));
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('login') ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        /* Social Login Buttons */
        .social-login {
            margin-top: 20px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(201, 168, 106, 0.3);
        }
        
        .divider span {
            padding: 0 15px;
            color: var(--gold-color);
            font-size: 14px;
            font-weight: 500;
        }
        
        .social-btn {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .google-btn {
            background-color: white;
            color: #444;
            border: 2px solid #4285f4;
        }
        
        .google-btn:hover {
            background-color: #4285f4;
            color: white;
        }
        
        .facebook-btn {
            background-color: #1877f2;
            color: white;
            border: 2px solid #1877f2;
        }
        
        .facebook-btn:hover {
            background-color: #0c63d4;
        }
        
        .social-icon {
            width: 20px;
            height: 20px;
        }
        
        .auth-link {
            color: var(--gold-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .auth-link:hover {
            color: var(--gold-light);
        }
        
        /* Alert Message Positioning */
        .alert-message-container {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 600px;
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        .alert-ramadan.alert-error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.05));
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 1rem 1.5rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }
        
        .alert-ramadan.alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.05));
            border: 2px solid #28a745;
            color: #155724;
            padding: 1rem 1.5rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        
        /* Input Fields Enhancement */
        .form-control-ramadan {
            width: 100%;
            border: 2px solid rgba(201, 168, 106, 0.4) !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-size: 15px;
            background: #ffffff;
            color: #2c2c2c;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control-ramadan:focus {
            border-color: var(--royal-gold) !important;
            box-shadow: 0 0 0 3px rgba(201, 168, 106, 0.15) !important;
            outline: none !important;
            background: #fffef9;
        }
        
        .form-control-ramadan:hover {
            border-color: rgba(201, 168, 106, 0.6);
        }
        
        .form-control-ramadan::placeholder {
            color: #999;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <?php
    // Display error messages in fixed position
    if (isset($_GET['error'])) {
        $error = htmlspecialchars($_GET['error']);
        echo '<div class="alert-message-container">';
        echo '<div class="alert-ramadan alert-error"><i class="fas fa-exclamation-circle me-2"></i>' . $error . '</div>';
        echo '</div>';
        echo '<script>setTimeout(function(){ document.querySelector(".alert-message-container").style.display="none"; }, 5000);</script>';
    }
    ?>
    
    <div class="page-container">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card-ramadan p-4">
                        <h2 class="section-title-ramadan text-center mb-4">
                            <i class="fas fa-user-circle me-2"></i><?= t('sign_in') ?>
                        </h2>
                        
                        <?php
                        // Include OAuth functions for social login
                        require_once __DIR__ . '/../../includes/oauth_functions.php';
                        $google_url = getOAuthURL('google');
                        $facebook_url = getOAuthURL('facebook');
                        ?>
                        
                        <!-- Social Login Buttons -->
                        <div class="social-login">
                            <a href="<?= htmlspecialchars($google_url) ?>" class="social-btn google-btn">
                                <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <?= t('continue_with_google') ?>
                            </a>
                            
                            <a href="<?= htmlspecialchars($facebook_url) ?>" class="social-btn facebook-btn">
                                <svg class="social-icon" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <?= t('continue_with_facebook') ?>
                            </a>
                        </div>
                        
                        <div class="divider">
                            <span><?= t('or') ?></span>
                        </div>
                        
                        <!-- Email/Password Login Form -->
                        <form action="signin.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label" style="color: var(--gold-color); font-weight: 500;"><?= t('email') ?></label>
                                <input type="email" id="email" name="email" class="form-control-ramadan" required placeholder="<?= t('enter_email') ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label" style="color: var(--gold-color); font-weight: 500;"><?= t('password') ?></label>
                                <input type="password" id="password" name="password" class="form-control-ramadan" required placeholder="<?= t('enter_password') ?>">
                                <div style="text-align: right; margin-top: 6px;">
                                    <a href="forgot_password.php" style="color: var(--gold-color); font-size: 0.85rem; text-decoration: none; font-weight: 500;">
                                        <i class="fas fa-lock" style="font-size: 0.75rem;"></i> Forgot password?
                                    </a>
                                </div>
                            </div>
                            
                            <button type="submit" name="signin" class="btn-ramadan w-100">
                                <i class="fas fa-sign-in-alt me-2"></i><?= t('sign_in_with_email') ?>
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">
                                <?= t('dont_have_account') ?> <a href="signup.php" class="auth-link"><?= t('sign_up') ?></a>
                            </p>
                            <p class="mb-0 mt-2">
                                <a href="../../index.php" class="auth-link">
                                    <i class="fas fa-arrow-left me-1"></i><?= t('back_to_store') ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
