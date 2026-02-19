<?php
// Start session and redirect if already logged in
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /poshy_store/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
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
    // Display success or error messages in fixed position
    if (isset($_GET['success'])) {
        echo '<div class="alert-message-container">';
        echo '<div class="alert-ramadan alert-success"><i class="fas fa-check-circle me-2"></i>✅ Registration successful! <a href="signin.php" style="color: #155724; font-weight: bold; text-decoration: underline;">Sign in now</a> to start shopping.</div>';
        echo '</div>';
        echo '<script>setTimeout(function(){ document.querySelector(".alert-message-container").style.display="none"; }, 7000);</script>';
    }
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
                <div class="col-md-7">
                    <div class="card-ramadan p-4">
                        <h2 class="section-title-ramadan text-center mb-4">
                            <i class="fas fa-user-plus me-2"></i>Sign Up
                        </h2>
                        
                        <form action="process_signup.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstname" class="form-label" style="color: var(--gold-color); font-weight: 500;">First Name</label>
                                    <input type="text" id="firstname" name="firstname" class="form-control-ramadan" required placeholder="Enter your first name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label" style="color: var(--gold-color); font-weight: 500;">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" class="form-control-ramadan" required placeholder="Enter your last name">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phonenumber" class="form-label" style="color: var(--gold-color); font-weight: 500;">Phone Number</label>
                                    <input type="tel" id="phonenumber" name="phonenumber" class="form-control-ramadan" required placeholder="Enter your phone number">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label" style="color: var(--gold-color); font-weight: 500;">Email</label>
                                    <input type="email" id="email" name="email" class="form-control-ramadan" required placeholder="Enter your email address">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label" style="color: var(--gold-color); font-weight: 500;">Password</label>
                                    <input type="password" id="password" name="password" class="form-control-ramadan" required placeholder="Enter your password" minlength="6">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label" style="color: var(--gold-color); font-weight: 500;">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control-ramadan" required placeholder="Confirm your password" minlength="6">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-ramadan w-100">
                                <i class="fas fa-user-plus me-2"></i>Sign Up
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">
                                Already have an account? <a href="signin.php" class="auth-link">Sign In</a>
                            </p>
                            <p class="mb-0 mt-2">
                                <a href="../../index.php" class="auth-link">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Store
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
