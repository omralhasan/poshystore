<?php
/**
 * Test Translation Keys
 * This file tests if all translation keys are working properly
 */

require_once __DIR__ . '/includes/language.php';

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n<title>Translation Test</title>\n";
echo "<meta charset='UTF-8'>\n";
echo "<style>body{font-family:Arial;padding:20px;line-height:2;}h2{color:#48366e;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #ddd;padding:10px;text-align:left;}th{background:#48366e;color:white;}.ar{direction:rtl;text-align:right;}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>Translation Keys Test</h1>\n";

$test_keys = [
    'default_description',
    'howto_step1',
    'howto_step2',
    'howto_step3',
    'howto_step4',
    'howto_step5',
    'refer_packaging'
];

echo "<h2>Current Language: " . $current_lang . "</h2>\n";

echo "<table>\n";
echo "<tr><th>Key</th><th>Arabic (ar)</th><th>English (en)</th></tr>\n";

foreach ($test_keys as $key) {
    // Test Arabic
    $_SESSION['language'] = 'ar';
    $current_lang = 'ar';
    $ar_text = t($key);
    
    // Test English
    $_SESSION['language'] = 'en';
    $current_lang = 'en';
    $en_text = t($key);
    
    echo "<tr>";
    echo "<td><strong>$key</strong></td>";
    echo "<td class='ar'>$ar_text</td>";
    echo "<td>$en_text</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Restore original language
$_SESSION['language'] = 'ar';
$current_lang = 'ar';

echo "\n<h2>Test Result: ";
$all_working = true;
foreach ($test_keys as $key) {
    if (t($key) === $key) {
        $all_working = false;
        echo "<span style='color:red;'>❌ FAILED - Key '$key' not found!</span>";
        break;
    }
}

if ($all_working) {
    echo "<span style='color:green;'>✅ ALL KEYS WORKING!</span>";
}
echo "</h2>\n";

echo "</body>\n</html>";
?>
