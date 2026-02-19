<?php
/**
 * Test Login Types - Shows which users can login with email/password vs OAuth
 */
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Type Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        .oauth { background: #e3f2fd; }
        .email { background: #f1f8e9; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-google { background: #4285f4; color: white; }
        .badge-facebook { background: #1877f2; color: white; }
        .badge-email { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 User Login Types</h1>
        <p>This shows which login method each user should use:</p>
        
        <table>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Login Method</th>
                <th>Can Use Email/Password?</th>
            </tr>
            
            <?php
            $result = $conn->query("SELECT id, email, firstname, lastname, oauth_provider, password IS NOT NULL as has_password FROM users ORDER BY id");
            
            while ($row = $result->fetch_assoc()) {
                $login_method = $row['oauth_provider'] ? ucfirst($row['oauth_provider']) : 'Email/Password';
                $can_use_password = $row['has_password'] ? '✅ Yes' : '❌ No - Must use ' . ucfirst($row['oauth_provider']);
                $row_class = $row['oauth_provider'] ? 'oauth' : 'email';
                $badge_class = $row['oauth_provider'] ? 'badge-' . $row['oauth_provider'] : 'badge-email';
                
                echo "<tr class='$row_class'>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>";
                echo "<td><span class='badge $badge_class'>$login_method</span></td>";
                echo "<td>$can_use_password</td>";
                echo "</tr>";
            }
            ?>
        </table>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-top: 20px;">
            <h3>📝 Important Notes:</h3>
            <ul>
                <li><strong>Green rows (Email/Password):</strong> Can login with email and password</li>
                <li><strong>Blue rows (OAuth):</strong> Must use Google or Facebook button to login</li>
                <li>If an OAuth user tries to login with email/password, they'll see a helpful error message</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="../pages/auth/signin.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">← Back to Sign In</a>
        </div>
    </div>
</body>
</html>
