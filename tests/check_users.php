<?php
require_once 'db_connect.php';

header('Content-Type: text/plain');

echo "=== USER DATABASE STATUS ===\n\n";

$result = $conn->query("SELECT id, email, firstname, lastname, oauth_provider, 
                        CASE WHEN password IS NULL THEN 'NULL' ELSE 'SET' END as password_status 
                        FROM users ORDER BY id");

if ($result) {
    printf("%-4s %-30s %-20s %-15s %-10s\n", "ID", "Email", "Name", "OAuth", "Password");
    echo str_repeat("-", 85) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-4s %-30s %-20s %-15s %-10s\n", 
            $row['id'], 
            $row['email'], 
            $row['firstname'] . ' ' . $row['lastname'],
            $row['oauth_provider'] ?? 'none',
            $row['password_status']
        );
    }
    
    echo "\n" . str_repeat("=", 85) . "\n\n";
    echo "Legend:\n";
    echo "- OAuth 'none' + Password 'SET' = Regular user (can use email/password)\n";
    echo "- OAuth 'google/facebook' + Password 'NULL' = OAuth-only user (must use social login)\n";
    echo "- OAuth 'google/facebook' + Password 'SET' = Hybrid user (can use both methods)\n";
} else {
    echo "Error querying database: " . $conn->error;
}

$conn->close();
?>
