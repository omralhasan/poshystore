<?php
/**
 * PHPMailer test – delete after testing
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";

// Test 1: Check autoload
echo "1. Autoload: ";
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    echo "OK\n";
} else {
    echo "MISSING ($autoload)\n";
    exit;
}

// Test 2: Check class
echo "2. PHPMailer class: ";
echo class_exists('PHPMailer\PHPMailer\PHPMailer') ? "OK\n" : "NOT FOUND\n";

// Test 3: Try sending
echo "3. SMTP Test:\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'mate7762s@gmail.com';
    $mail->Password   = 'omarabudiak';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 10;
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function($str, $level) { echo "   DEBUG[$level]: " . htmlspecialchars($str); };
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('mate7762s@gmail.com', 'Poshy Store');
    $mail->addAddress('mate7762s@gmail.com', 'Test');

    $mail->isHTML(true);
    $mail->Subject = 'Poshy Store SMTP Test';
    $mail->Body    = '<h1>Test Email</h1><p>If you see this, PHPMailer works!</p>';

    $mail->send();
    echo "\n   RESULT: SUCCESS - Email sent!\n";
} catch (Exception $e) {
    echo "\n   RESULT: FAILED - " . $mail->ErrorInfo . "\n";
}
echo "</pre>";
