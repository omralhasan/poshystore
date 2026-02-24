<?php
/**
 * Auto Translation Helper for Poshy Store
 *
 * Automatically translates product content from English to Arabic
 * using the Google Translate unofficial API (no API key required).
 * Translated results are cached back to the database to avoid
 * repeated API calls.
 */

/**
 * Translate text from one language to another via Google Translate.
 *
 * @param string $text  The text to translate.
 * @param string $from  Source language code (default 'en').
 * @param string $to    Target language code (default 'ar').
 * @return string       Translated text, or original on failure.
 */
function autoTranslate(string $text, string $from = 'en', string $to = 'ar'): string {
    $text = trim($text);
    if ($text === '') return '';

    // Respect a sensible maximum to stay within free-tier limits (Google
    // unoffical endpoint) — split longer texts into chunks if needed.
    if (mb_strlen($text) > 4500) {
        // Split by paragraph / newline and translate each piece separately
        $chunks = preg_split('/(\r?\n[\r\n]+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($chunks as $chunk) {
            $result .= (trim($chunk) === '') ? $chunk : autoTranslate($chunk, $from, $to);
        }
        return $result;
    }

    $url = 'https://translate.googleapis.com/translate_a/single'
        . '?client=gtx'
        . '&sl=' . urlencode($from)
        . '&tl=' . urlencode($to)
        . '&dt=t'
        . '&q='  . urlencode($text);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PoshyStore/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $curlerr  = curl_errno($ch);
    curl_close($ch);

    if ($response === false || $curlerr !== 0) {
        error_log("autoTranslate: cURL error $curlerr for text: " . substr($text, 0, 100));
        return $text; // fallback: return original
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0])) {
        return $text;
    }

    // data[0] is an array of [translated_segment, original_segment, ...]
    $translated = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) {
            $translated .= $segment[0];
        }
    }

    return $translated !== '' ? $translated : $text;
}

/**
 * Ensure that all Arabic content fields for a product are populated.
 *
 * When Arabic fields are empty (or null), the corresponding English field is
 * translated via autoTranslate() and the result is saved back to the
 * `products` table so the next page load uses the cached value.
 *
 * The $product array is modified in-place.
 *
 * @param mysqli $conn    Active database connection.
 * @param array  $product Product row by reference.
 */
function ensureArabicContent(mysqli $conn, array &$product): void {
    // Map: ar_field => en_field (source)
    $fields_map = [
        'short_description_ar' => 'short_description_en',
        'description_ar'       => 'description',
        'product_details_ar'   => 'product_details',
        'how_to_use_ar'        => 'how_to_use_en',
    ];

    $update_fields  = [];
    $update_values  = [];

    foreach ($fields_map as $ar_field => $en_field) {
        $ar_val = isset($product[$ar_field]) ? trim($product[$ar_field]) : '';
        $en_val = isset($product[$en_field]) ? trim($product[$en_field]) : '';

        // Also try legacy 'how_to_use' column if how_to_use_en is empty
        if ($ar_field === 'how_to_use_ar' && $en_val === '') {
            $en_val = isset($product['how_to_use']) ? trim($product['how_to_use']) : '';
        }

        if ($ar_val === '' && $en_val !== '') {
            $translated = autoTranslate($en_val);
            if ($translated !== '' && $translated !== $en_val) {
                $product[$ar_field]  = $translated;
                $update_fields[]     = "$ar_field = ?";
                $update_values[]     = $translated;
            }
        }
    }

    if (empty($update_fields)) return;

    // Persist translated values to DB (cache for future requests)
    $update_values[] = (int)($product['id'] ?? 0);
    $sql   = 'UPDATE products SET ' . implode(', ', $update_fields) . ' WHERE id = ?';
    $types = str_repeat('s', count($update_values) - 1) . 'i';
    $stmt  = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$update_values);
        $stmt->execute();
        $stmt->close();
    }
}
