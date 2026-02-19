<?php
/**
 * Quick Password Reset Tool
 * Updates admin password to a known value for testing
 */

require_once __DIR__ . '/includes/db_connect.php';

$email = 'admin@poshylifestyle.com';
$new_password = 'admin123';  // Test password

// Hash the password
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param('ss', $hashed, $email);

if ($stmt->execute()) {
    echo "✅ Password reset successful!\n\n";
    echo "Email: $email\n";
    echo "Password: $new_password\n\n";
    echo "You can now login with these credentials.\n";
    echo "<a href='pages/auth/signin.php'>Go to Sign In</a> | ";
    echo "<a href='test_signin_debug.php'>Test Login Debug</a>\n";
} else {
    echo "❌ Failed to reset password: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
