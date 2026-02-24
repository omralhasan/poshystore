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
 * Check whether a string contains a meaningful amount of Arabic text.
 * Returns false when a field is empty, null, or only contains ASCII
 * (meaning it was never properly translated or was mirrored from English).
 */
function isArabicText(?string $text): bool {
    if ($text === null || trim($text) === '') return false;
    $arabicCount = preg_match_all('/[\x{0600}-\x{06FF}]/u', $text);
    return ($arabicCount !== false && $arabicCount > 3);
}

/**
 * Clean unwanted English/Arabic label prefixes added by some import scripts.
 * e.g. "**English:** some text" → "some text"
 */
function cleanTranslationPrefixes(string $text): string {
    $text = preg_replace('/^\*{0,2}(?:English|Arabic|EN|AR)\*{0,2}\s*:\s*/ui', '', trim($text));
    $text = preg_replace('/^(?:English|Arabic)\s*:\s*/ui', '', $text);
    return trim($text);
}

/**
 * Translate text from English to Arabic via Google Translate (free, no key).
 */
function autoTranslate(string $text, string $from = 'en', string $to = 'ar'): string {
    $text = trim($text);
    if ($text === '') return '';

    if (mb_strlen($text) > 4500) {
        $chunks = preg_split('/(\r?\n\r?\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
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
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PoshyStore/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $curlerr  = curl_errno($ch);
    curl_close($ch);

    if ($response === false || $curlerr !== 0) {
        error_log("autoTranslate: cURL error $curlerr");
        return $text;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0])) {
        return $text;
    }

    $translated = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) {
            $translated .= $segment[0];
        }
    }

    return $translated !== '' ? $translated : $text;
}

/**
 * Ensure all Arabic content fields for a product are populated.
 *
 * - If Arabic field is empty/null → translate from English.
 * - If Arabic field contains no Arabic characters (was mirrored to English) → translate.
 * - If Arabic field already has real Arabic text → skip.
 *
 * Saves cached translations back to DB. Modifies $product in-place.
 */
function ensureArabicContent(mysqli $conn, array &$product): void {
    $fields_map = [
        'name_ar'              => 'name_en',
        'short_description_ar' => 'short_description_en',
        'description_ar'       => 'description',
        'product_details_ar'   => 'product_details',
        'how_to_use_ar'        => 'how_to_use_en',
    ];

    $update_fields = [];
    $update_values = [];

    foreach ($fields_map as $ar_field => $en_field) {
        $ar_val = isset($product[$ar_field]) ? (string)$product[$ar_field] : '';
        $en_val = isset($product[$en_field]) ? trim((string)$product[$en_field]) : '';

        // Fallback for legacy how_to_use column
        if ($ar_field === 'how_to_use_ar' && $en_val === '') {
            $en_val = isset($product['how_to_use']) ? trim((string)$product['how_to_use']) : '';
        }

        if ($en_val === '') continue;

        // Skip if Arabic field already has real Arabic text
        if (isArabicText($ar_val)) continue;

        // Clean source text
        $clean_en = cleanTranslationPrefixes($en_val);
        if ($clean_en === '') continue;

        $translated = autoTranslate($clean_en);

        // Only save if translation came back as actual Arabic
        if ($translated !== '' && $translated !== $clean_en && isArabicText($translated)) {
            $product[$ar_field]  = $translated;
            $update_fields[]     = "`$ar_field` = ?";
            $update_values[]     = $translated;
        }
    }

    if (empty($update_fields)) return;

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
