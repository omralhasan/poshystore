<?php
/**
 * Rich Text Formatter for Poshy Store
 * Converts plain text with simple Markdown-like syntax to styled HTML.
 *
 * Supported syntax:
 *   # Heading 1       →  <h1>
 *   ## Heading 2      →  <h2>
 *   ### Heading 3     →  <h3>
 *   #### Heading 4    →  <h4>
 *   ##### Heading 5   →  <h5>
 *   **bold**          →  <strong>
 *   *italic*          →  <em>
 *   - list item       →  <ul><li>
 *   1. list item      →  <ol><li>
 *   Empty line        →  paragraph break
 *
 * Also accepts safe HTML tags directly.
 */

function formatRichContent(string $text): string {
    if (empty(trim($text))) {
        return '';
    }

    // If text already contains HTML block tags, sanitize and return
    if (preg_match('/<(h[1-5]|p|ul|ol|li|div|table|tr|td|th|thead|tbody)\b/i', $text)) {
        return sanitizeHtml($text);
    }

    // Process as Markdown-light
    return markdownToHtml($text);
}

/**
 * Allow only safe HTML tags, strip everything else
 */
function sanitizeHtml(string $html): string {
    $allowed = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><u>'
             . '<ul><ol><li><a><span><div><table><tr><td><th><thead><tbody>'
             . '<blockquote><hr><img><sub><sup>';
    $clean = strip_tags($html, $allowed);

    // Sanitize attributes – only allow href, src, alt, class, style (basic)
    $clean = preg_replace_callback('/<(a|img)\s[^>]*>/i', function ($m) {
        $tag = $m[0];
        // Keep only safe attributes
        $safeTags = [];
        preg_match_all('/\b(href|src|alt|title|class|target)\s*=\s*("[^"]*"|\'[^\']*\')/i', $tag, $attrs);
        $attrStr = '';
        if (!empty($attrs[0])) {
            $attrStr = ' ' . implode(' ', $attrs[0]);
        }
        // Rebuild
        preg_match('/<(\w+)/i', $tag, $tagName);
        $tn = strtolower($tagName[1]);
        $selfClose = ($tn === 'img') ? ' /' : '';
        return "<{$tn}{$attrStr}{$selfClose}>";
    }, $clean);

    return $clean;
}

/**
 * Convert Markdown-light text to HTML
 */
function markdownToHtml(string $text): string {
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $lines = explode("\n", $text);

    $html = '';
    $inUl = false;
    $inOl = false;
    $paragraph = [];

    $flushParagraph = function () use (&$paragraph, &$html) {
        if (!empty($paragraph)) {
            $pText = implode('<br>', $paragraph);
            $pText = formatInline($pText);
            $html .= "<p>{$pText}</p>\n";
            $paragraph = [];
        }
    };

    $closeLists = function () use (&$inUl, &$inOl, &$html) {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Empty line → end paragraph / list
        if ($trimmed === '') {
            $flushParagraph();
            $closeLists();
            continue;
        }

        // Headings: # through #####
        if (preg_match('/^(#{1,5})\s+(.+)$/', $trimmed, $m)) {
            $flushParagraph();
            $closeLists();
            $level = strlen($m[1]);
            $headingText = htmlspecialchars(trim($m[2]));
            $html .= "<h{$level}>{$headingText}</h{$level}>\n";
            continue;
        }

        // Unordered list: - item or * item
        if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
            $flushParagraph();
            if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
            if (!$inUl) { $html .= "<ul>\n"; $inUl = true; }
            $html .= "  <li>" . formatInline(htmlspecialchars($m[1])) . "</li>\n";
            continue;
        }

        // Ordered list: 1. item, 2. item, etc.
        if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
            $flushParagraph();
            if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
            if (!$inOl) { $html .= "<ol>\n"; $inOl = true; }
            $html .= "  <li>" . formatInline(htmlspecialchars($m[1])) . "</li>\n";
            continue;
        }

        // Horizontal rule: --- or ***
        if (preg_match('/^[-*]{3,}$/', $trimmed)) {
            $flushParagraph();
            $closeLists();
            $html .= "<hr>\n";
            continue;
        }

        // Regular text → accumulate in paragraph
        $closeLists();
        $paragraph[] = htmlspecialchars($trimmed);
    }

    // Flush remaining
    $flushParagraph();
    $closeLists();

    return $html;
}

/**
 * Format inline elements: **bold**, *italic*
 */
function formatInline(string $text): string {
    // Bold: **text**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    // Italic: *text*
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    return $text;
}
