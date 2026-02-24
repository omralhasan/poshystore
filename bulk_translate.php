<?php
/**
 * Bulk translate all product Arabic fields that still contain English text.
 * Run once: php bulk_translate.php
 */
require __DIR__ . '/config.php';
require __DIR__ . '/includes/db_connect.php';
require __DIR__ . '/includes/auto_translate.php';

$result = $conn->query("SELECT id, name_en, name_ar, short_description_en, short_description_ar FROM products");
$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $changes = [];
    $vals    = [];

    // name_ar
    if (!isArabicText($row['name_ar']) && !empty($row['name_en'])) {
        $t = autoTranslate(cleanTranslationPrefixes($row['name_en']));
        if (isArabicText($t)) {
            $changes[] = 'name_ar = ?';
            $vals[]    = $t;
            echo "name: " . substr($row['name_en'], 0, 40) . "  =>  $t\n";
        }
    }

    // short_description_ar
    if (!isArabicText($row['short_description_ar']) && !empty($row['short_description_en'])) {
        $t = autoTranslate(cleanTranslationPrefixes($row['short_description_en']));
        if (isArabicText($t)) {
            $changes[] = 'short_description_ar = ?';
            $vals[]    = $t;
        }
    }

    if ($changes) {
        $vals[] = $row['id'];
        $sql    = 'UPDATE products SET ' . implode(', ', $changes) . ' WHERE id = ?';
        $types  = str_repeat('s', count($vals) - 1) . 'i';
        $stmt   = $conn->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt->close();
        $updated++;
    } else {
        $skipped++;
    }
}

echo "\nDone: $updated translated, $skipped skipped\n";
