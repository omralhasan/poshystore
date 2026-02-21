<?php
/**
 * Authentication Functions for Poshy Lifestyle E-Commerce
 * 
 * Handles user registration, login, and session management
 * Connects to: users table (id, firstname, lastname, email, password, role)
 */

// Load central config (DB, SITE_URL, error logging, session)
if (!defined('POSHY_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

// Configure session cookie parameters for better compatibility
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/db_connect.php';

/**
 * Register a new user
 * 
 * @param string $firstname User's first name
 * @param string $lastname User's last name
 * @param string $email User's email address
 * @param string $password User's password (will be hashed)
 * @param string $role User role (default: 'customer')
 * @return array Response with success status and message
 */
function registerUser($firstname, $lastname, $email, $password, $role = 'customer') {
    global $conn;
    
    // Validate inputs
    $errors = [];
    
    if (empty($firstname)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($lastname)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password) || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if (!in_array($role, ['customer', 'admin', 'manager'])) {
        $role = 'customer';
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Email already registered'
        ];
    }
    $check_stmt->close();
    
    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user into database
    $sql = "INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Registration prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Registration failed. Please try again.'
        ];
    }
    
    $stmt->bind_param('sssss', $firstname, $lastname, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $user_id
        ];
    } else {
        error_log("Registration execute failed: " . $stmt->error);
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Registration failed. Please try again.'
        ];
    }
}

/**
 * Login user with email and password
 * 
 * @param string $email User's email
 * @param string $password User's password
 * @return array Response with success status and user data
 */
function loginUser($email, $password) {
    global $conn;
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        return [
            'success' => false,
            'error' => 'Email and password are required'
        ];
    }
    
    // Query user by email - connects to users table
    $sql = "SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Login prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Login failed. Please try again.'
        ];
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password against hashed password
        if (password_verify($password, $user['password'])) {
            // Password is correct - create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            $stmt->close();
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } else {
            // Incorrect password
            $stmt->close();
            return [
                'success' => false,
                'error' => 'Invalid email or password'
            ];
        }
    } else {
        // User not found
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Invalid email or password'
        ];
    }
}

/**
 * Check if user is logged in and session is valid
 * 
 * @param bool $redirect Whether to redirect to login page if not logged in
 * @return bool True if logged in, false otherwise
 */
function checkSession($redirect = false) {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        if ($redirect) {
            header('Location: signin.php?error=' . urlencode('Please login to continue'));
            exit();
        }
        return false;
    }
    
    // Check session timeout (24 hours)
    $timeout = 24 * 60 * 60; // 24 hours in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        logoutUser();
        if ($redirect) {
            header('Location: signin.php?error=' . urlencode('Session expired. Please login again.'));
            exit();
        }
        return false;
    }
    
    return true;
}

/**
 * Check if user has specific role
 * 
 * @param string|array $required_role Required role(s)
 * @return bool True if user has required role
 */
function hasRole($required_role) {
    if (!checkSession()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    
    if (is_array($required_role)) {
        return in_array($user_role, $required_role);
    }
    
    return $user_role === $required_role;
}

/**
 * Check if current user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Get current logged in user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return checkSession() ? ($_SESSION['user_id'] ?? null) : null;
}

/**
 * Get current user data
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!checkSession()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'firstname' => $_SESSION['firstname'] ?? '',
        'lastname' => $_SESSION['lastname'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'customer'
    ];
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * JSON response for API endpoints
 * 
 * @param array $data Data to return as JSON
 * @param int $status_code HTTP status code
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}
?>
