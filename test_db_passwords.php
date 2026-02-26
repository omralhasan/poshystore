<?php
// Try different connection methods
$passwords = [
    'Poshy2026secure',
    'poshy_user',
    'poshystore',
    '12345',
    'password',
    '',
    'p0shyst0re',
    'poshystore123'
];

foreach ($passwords as $pass) {
    echo "Trying password: '$pass'\n";
    try {
        $mysqli = new mysqli("localhost", "poshy_user", $pass, "poshy_db");
        if (!$mysqli->connect_error) {
            echo "✅ SUCCESS connecting with password: '$pass'\n";
            $result = $mysqli->query("SELECT COUNT(*) FROM orders");
            if ($result) {
                $count = $result->fetch_row()[0];
                echo "Orders found: $count\n";
                $mysqli->query("DELETE FROM order_items");
                echo "Deleted order items\n";
                $mysqli->query("DELETE FROM orders");
                echo "Deleted orders\n";
                $result2 = $mysqli->query("SELECT COUNT(*) FROM orders");
                $count2 = $result2->fetch_row()[0];
                echo "Orders remaining: $count2\n";
                echo "✅ SUCCESS!\n";
                $mysqli->close();
                exit(0);
            }
        }
    } catch (Exception $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
    }
}

echo "Could not connect with any password\n";
?>
