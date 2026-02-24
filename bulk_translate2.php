<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auto_translate.php';

set_time_limit(300);
$result = $conn->query("SELECT id, name_en, name_ar FROM products ORDER BY id");
$total = 0; $done = 0; $skipped = 0;
while ($row = $result->fetch_assoc()) {
    $total++;
    if (isArabicText($row['name_ar'])) { $skipped++; continue; }
    $arabic = autoTranslate($row['name_en']);
    if ($arabic && isArabicText($arabic)) {
        $safe = $conn->real_escape_string($arabic);
        $conn->query("UPDATE products SET name_ar='$safe' WHERE id=".$row['id']);
        echo "OK id=".$row['id'].": ".$arabic."\n";
        $done++;
        usleep(200000);
    } else {
        echo "FAIL id=".$row['id']."\n";
    }
}
echo "\nDone: $done translated, $skipped skipped, $total total\n";
