<?php
/**
 * Temporary audit view for recent orders.
 * Remove this file after completing the investigation.
 */
require_once __DIR__ . '/includes/db_connect.php';

function get_columns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) {
        return $cols;
    }
    while ($row = $res->fetch_assoc()) {
        $cols[$row['Field']] = true;
    }
    return $cols;
}

function pick_column(array $columns, array $candidates): ?string {
    foreach ($candidates as $name) {
        if (isset($columns[$name])) {
            return $name;
        }
    }
    return null;
}

$orders_cols = get_columns($conn, 'orders');
$users_cols = get_columns($conn, 'users');

// Date column
if (isset($orders_cols['order_date'])) {
    $date_expr = 'o.`order_date`';
} elseif (isset($orders_cols['created_at'])) {
    $date_expr = 'o.`created_at`';
} elseif (isset($orders_cols['created'])) {
    $date_expr = 'o.`created`';
} else {
    $date_expr = 'o.`id`';
}

// User name expression
$user_name_expr = 'NULL';
$user_name_col = pick_column($users_cols, ['name', 'full_name', 'fullname', 'username']);
if ($user_name_col) {
    $user_name_expr = "NULLIF(u.`{$user_name_col}`, '')";
} elseif (isset($users_cols['firstname']) || isset($users_cols['lastname'])) {
    $first = isset($users_cols['firstname']) ? 'u.`firstname`' : "''";
    $last = isset($users_cols['lastname']) ? 'u.`lastname`' : "''";
    $user_name_expr = "NULLIF(TRIM(CONCAT_WS(' ', {$first}, {$last})), '')";
}

// Customer name expression (prefer guest name when present)
if (isset($orders_cols['guest_name'])) {
    $customer_name_expr = "COALESCE(NULLIF(o.`guest_name`, ''), {$user_name_expr}, 'Guest')";
} else {
    $customer_name_expr = "COALESCE({$user_name_expr}, 'Guest')";
}

// Email expression
if (isset($orders_cols['guest_email'])) {
    $email_expr = "COALESCE(NULLIF(o.`guest_email`, ''), NULLIF(u.`email`, ''), '')";
} elseif (isset($users_cols['email'])) {
    $email_expr = "COALESCE(NULLIF(u.`email`, ''), '')";
} else {
    $email_expr = "''";
}

// Phone expression
$user_phone_col = pick_column($users_cols, ['phonenumber', 'phone', 'mobile', 'mobile_number', 'phone_number']);
$user_phone_expr = $user_phone_col ? "NULLIF(u.`{$user_phone_col}`, '')" : "''";
if (isset($orders_cols['phone'])) {
    $phone_expr = "COALESCE(NULLIF(o.`phone`, ''), {$user_phone_expr}, '')";
} else {
    $phone_expr = "COALESCE({$user_phone_expr}, '')";
}

// Address expression
$address_col = pick_column($orders_cols, ['shipping_address', 'address', 'delivery_address', 'address_line1', 'shipping']);
$address_expr = $address_col ? "COALESCE(NULLIF(o.`{$address_col}`, ''), '')" : "''";

// IP address expression
$ip_col = pick_column($orders_cols, ['ip_address', 'ip', 'customer_ip', 'order_ip', 'remote_ip', 'request_ip']);
$ip_expr = $ip_col ? "o.`{$ip_col}`" : "NULL";

$sql = "SELECT\n"
     . "  o.`id` AS order_id,\n"
     . "  {$customer_name_expr} AS customer_name,\n"
     . "  {$phone_expr} AS phone,\n"
     . "  {$email_expr} AS email,\n"
     . "  {$address_expr} AS address_str,\n"
     . "  {$date_expr} AS order_datetime,\n"
     . "  {$ip_expr} AS ip_address\n"
     . "FROM `orders` o\n"
     . "LEFT JOIN `users` u ON o.`user_id` = u.`id`\n"
     . "ORDER BY {$date_expr} DESC\n"
     . "LIMIT 60";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Orders Audit</title></head><body>';
    echo '<h1>Orders Audit</h1>';
    echo '<p>Query failed: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit;
}

function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders Audit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1 { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; }
        tr:nth-child(even) { background: #fafafa; }
        .muted { color: #666; font-size: 12px; margin-top: 6px; }
        .wrap { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <h1>Recent Orders Audit (Last 60)</h1>
    <div class="muted">Temporary audit page. Remove view_orders_audit.php after review.</div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Phone &amp; Email</th>
                <th>Malicious Address String</th>
                <th>Created Date</th>
                <th>Attacker IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()):
                $order_id = (string)($row['order_id'] ?? '');
                $name = trim((string)($row['customer_name'] ?? ''));
                $phone = trim((string)($row['phone'] ?? ''));
                $email = trim((string)($row['email'] ?? ''));
                $address = trim((string)($row['address_str'] ?? ''));
                $created = trim((string)($row['order_datetime'] ?? ''));
                $ip = trim((string)($row['ip_address'] ?? ''));

                $contact = '';
                if ($phone !== '' && $email !== '') {
                    $contact = $phone . ' / ' . $email;
                } elseif ($phone !== '') {
                    $contact = $phone;
                } elseif ($email !== '') {
                    $contact = $email;
                }

                if ($contact === '') { $contact = '-'; }
                if ($name === '') { $name = 'Guest'; }
                if ($address === '') { $address = '-'; }
                if ($created === '') { $created = '-'; }
                if ($ip === '') { $ip = '-'; }
            ?>
            <tr>
                <td><?php echo esc($order_id); ?></td>
                <td><?php echo esc($name); ?></td>
                <td><?php echo esc($contact); ?></td>
                <td class="wrap"><?php echo esc($address); ?></td>
                <td><?php echo esc($created); ?></td>
                <td><?php echo esc($ip); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
