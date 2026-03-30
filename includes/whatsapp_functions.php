<?php
/**
 * WhatsApp Integration Functions for Poshy Lifestyle
 * FREE Solution - No API costs!
 * 
 * Uses file-based bridge to Node.js whatsapp-web.js bot
 */

/**
 * Send WhatsApp order confirmation
 * 
 * @param string $phone Customer phone number
 * @param array $order_details Order information
 * @return bool Success status
 */
function sendWhatsAppOrderConfirmation($phone, $order_details) {
    // Validate phone number
    if (empty($phone)) {
        error_log("WhatsApp: No phone number provided");
        return false;
    }
    
    // Normalize phone number format.
    $phone = wa_normalize_phone($phone);
    if ($phone === '') {
        error_log("WhatsApp: Invalid phone format");
        return false;
    }
    
    // Build message with Ramadan theme
    $message = buildOrderConfirmationMessage($order_details);
    
    // Create JSON file for bot to process
    $json_data = [
        'phone' => $phone,
        'message' => $message,
        'order_id' => $order_details['order_id'] ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'order_confirmation',
        'sender_number' => getenv('WHATSAPP_SENDER_NUMBER') ?: '962770058416'
    ];
    
    // Save to pending_sms directory
    $pending_dir = wa_pending_dir();
    if (!is_dir($pending_dir) && !@mkdir($pending_dir, 0755, true)) {
        error_log("WhatsApp: Failed to create pending directory: {$pending_dir}");
        return false;
    }
    
    $filename = sprintf(
        '%s/order_%d_%d.json',
        $pending_dir,
        $order_details['order_id'] ?? 0,
        time()
    );
    
    $result = file_put_contents($filename, json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result) {
        error_log("WhatsApp: Order confirmation queued for {$phone}");
        return true;
    } else {
        error_log("WhatsApp: Failed to queue message for {$phone}");
        return false;
    }
}

/**
 * Build order confirmation message with Ramadan theme
 * 
 * @param array $order Order details
 * @return string Formatted WhatsApp message
 */
function buildOrderConfirmationMessage($order) {
    $order_id = $order['order_id'] ?? 'N/A';
    $customer_name = $order['customer_name'] ?? 'عزيزنا العميل';
    $total_amount = $order['total_amount_formatted'] ?? ($order['total_amount'] ?? '0.000') . ' JOD';
    $items_list = $order['items'] ?? [];
    $points_earned = $order['points_earned'] ?? 0;
    $status = $order['status'] ?? 'pending';
    $support_phone = getenv('WHATSAPP_SUPPORT_PHONE') ?: '+962 7 7005 8416';
    
    // Ramadan greeting (rotates based on time of day)
    $hour = (int)date('H');
    if ($hour >= 5 && $hour < 12) {
        $greeting = "صباح الخير والبركة 🌅";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting = "نهارك مبارك 🌞";
    } elseif ($hour >= 17 && $hour < 20) {
        $greeting = "مساء الخير ✨";
    } else {
        $greeting = "مساء الخير والبركة 🌙";
    }
    
    // Build message
    $message = "🌙 ═══ Poshy Lifestyle ═══ 🌙\n\n";
    $message .= "{$greeting}\n";
    $message .= "رمضان كريم من عائلة بوشي 💜\n\n";
    $message .= "✅ *تم تأكيد طلبك بنجاح!*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "📦 *تفاصيل الطلب:*\n";
    $message .= "• رقم الطلب: *#{$order_id}*\n";
    $message .= "• العميل: {$customer_name}\n";
    $message .= "• المبلغ الإجمالي: *{$total_amount}*\n";
    
    // Add items list if available
    if (!empty($items_list)) {
        $message .= "\n🛍️ *المنتجات:*\n";
        $count = 0;
        foreach ($items_list as $item) {
            $count++;
            if ($count > 5) {
                $remaining = count($items_list) - 5;
                $message .= "   ... و {$remaining} منتجات أخرى\n";
                break;
            }
            $item_name = $item['name_ar'] ?? $item['name_en'] ?? 'منتج';
            $quantity = $item['quantity'] ?? 1;
            $message .= "   {$count}. {$item_name} × {$quantity}\n";
        }
    }
    
    $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
    
    // Points earned
    if ($points_earned > 0) {
        $message .= "🎁 *مكافأة النقاط:*\n";
        $message .= "لقد حصلت على *{$points_earned} نقطة* من هذا الطلب! 🎉\n";
        $message .= "يمكنك تحويلها لرصيد محفظتك\n\n";
    }
    
    // Status
    $status_ar = [
        'pending' => 'قيد المعالجة',
        'processing' => 'قيد التحضير',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي'
    ];
    $status_text = $status_ar[$status] ?? 'قيد المعالجة';
    $message .= "📋 الحالة: *{$status_text}*\n\n";
    
    // Delivery info
    $message .= "🚚 سيتم التواصل معك قريباً لتأكيد موعد التوصيل\n\n";
    
    // Footer with contact info
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "📞 للاستفسار:\n";
    $message .= "• واتساب: {$support_phone}\n";
    $message .= "• الموقع: www.poshystore.com\n\n";
    
    // Ramadan blessing
    $message .= "✨ *تقبل الله صيامكم وقيامكم* ✨\n";
    $message .= "🌙 *رمضان كريم* 🌙\n\n";
    $message .= "مع حبنا،\n";
    $message .= "فريق Poshy Lifestyle 💜";
    
    return $message;
}

/**
 * Send custom WhatsApp message
 * 
 * @param string $phone Phone number
 * @param string $message Custom message
 * @return bool Success status
 */
function sendWhatsAppMessage($phone, $message) {
    $phone = wa_normalize_phone($phone);
    if ($phone === '') {
        return false;
    }

    $pending_dir = wa_pending_dir();
    if (!is_dir($pending_dir) && !@mkdir($pending_dir, 0755, true)) {
        return false;
    }
    
    $json_data = [
        'phone' => $phone,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'custom'
    ];
    
    $filename = sprintf(
        '%s/custom_%d.json',
        $pending_dir,
        time()
    );
    
    return file_put_contents($filename, json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Resolve queue directory for pending WhatsApp messages.
 */
function wa_pending_dir() {
    $configured = trim((string)getenv('WHATSAPP_PENDING_DIR'));
    if ($configured !== '') {
        return $configured;
    }

    return __DIR__ . '/../pending_sms';
}

/**
 * Normalize recipient phone number to numeric international format.
 */
function wa_normalize_phone($phone) {
    $digits = preg_replace('/[^0-9]/', '', (string)$phone);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, '0') && strlen($digits) === 10) {
        return '962' . substr($digits, 1);
    }

    if (!str_starts_with($digits, '962') && strlen($digits) === 9) {
        return '962' . $digits;
    }

    return $digits;
}
