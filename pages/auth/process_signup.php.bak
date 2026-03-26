<?php
/**
 * Process Sign Up - Poshy Lifestyle
 * Handles user registration with secure password hashing
 */

// Start session for storing messages
session_start();

// Include language system
require_once __DIR__ . '/../../includes/language.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit();
}

// Load database connection
require_once __DIR__ . '/../../includes/db_connect.php';

// Get form data and sanitize
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$phonenumber = trim($_POST['phonenumber'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = trim($_POST['role'] ?? 'customer'); // Default to customer role

// Validation
$errors = [];

if (empty($firstname)) {
    $errors[] = t('first_name_required');
}

if (empty($lastname)) {
    $errors[] = t('last_name_required');
}

if (empty($phonenumber)) {
    $errors[] = t('phone_number_required');
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = t('valid_email_required');
}

if (empty($password)) {
    $errors[] = t('password_required');
} elseif (strlen($password) < 6) {
    $errors[] = t('password_min_length');
}

if ($password !== $confirm_password) {
    $errors[] = t('passwords_do_not_match');
}

// If there are validation errors, redirect back
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: signup.php?error=' . urlencode($error_message));
    exit();
}

// Check database connection
if (!$conn || $conn->connect_error) {
    header('Location: signup.php?error=' . urlencode(t('database_connection_failed')));
    exit();
}

// Check if email already exists
$check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$check_stmt->bind_param('s', $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    $conn->close();
    header('Location: signup.php?error=' . urlencode(t('email_already_registered')));
    exit();
}
$check_stmt->close();

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare INSERT statement
$stmt = $conn->prepare('INSERT INTO users (firstname, lastname, phonenumber, email, password, role) VALUES (?, ?, ?, ?, ?, ?)');

if (!$stmt) {
    $conn->close();
    header('Location: signup.php?error=' . urlencode(t('database_error') . ': ' . $conn->error));
    exit();
}

// Bind parameters
$stmt->bind_param('ssssss', $firstname, $lastname, $phonenumber, $email, $hashed_password, $role);

// Execute the statement
if ($stmt->execute()) {
    // Get the new user's ID
    $new_user_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    // Auto-login the user after successful registration
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['firstname'] = $firstname;
    $_SESSION['lastname'] = $lastname;
    $_SESSION['email'] = $email;
    $_SESSION['phonenumber'] = $phonenumber;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Redirect based on user role
    $redirect = ($role === 'admin') ? '../admin/admin_panel.php' : '../../index.php?welcome=1';
    header('Location: ' . $redirect);
    exit();
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header('Location: signup.php?error=' . urlencode(t('registration_failed') . ': ' . $error));
    exit();
}
?>
