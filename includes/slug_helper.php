<?php
/**
 * Slug Helper for Poshy Lifestyle Store
 * 
 * Generates URL-friendly slugs from product names.
 * Example: "The Ordinary Niacinamide 10% + Zinc 1%" → "the-ordinary-niacinamide-10-zinc-1"
 */

/**
 * Convert any string into a URL-safe slug.
 *
 * @param string $text  The original text (product name, title, etc.)
 * @return string       Lowercase, hyphen-separated slug
 */
function slugify(string $text): string {
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');

    // Replace common symbols with words or hyphens
    $text = str_replace(
        ['&', '+', '%', '™', '®', '©', ':', '.', ',', "'", '"', '–', '—'],
        ['-and-', '-plus-', '-percent-', '', '', '', '', '', '', '', '', '-', '-'],
        $text
    );

    // Replace any non-alphanumeric character with a hyphen
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Collapse consecutive hyphens
    $text = preg_replace('/-{2,}/', '-', $text);

    // Trim leading/trailing hyphens
    return trim($text, '-');
}

/**
 * Generate a unique slug for a product, appending -2, -3, etc. if a collision exists.
 *
 * @param mysqli $conn        Database connection
 * @param string $name        Product name to slugify
 * @param int|null $exclude_id  Product ID to exclude from collision check (for updates)
 * @return string               Unique slug
 */
function generateUniqueSlug(mysqli $conn, string $name, ?int $exclude_id = null): string {
    $base = slugify($name);
    $slug = $base;
    $counter = 1;

    while (true) {
        $sql = "SELECT id FROM products WHERE slug = ?";
        if ($exclude_id !== null) {
            $sql .= " AND id != ?";
        }
        $stmt = $conn->prepare($sql);
        if ($exclude_id !== null) {
            $stmt->bind_param('si', $slug, $exclude_id);
        } else {
            $stmt->bind_param('s', $slug);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $slug;
        }

        $stmt->close();
        $counter++;
        $slug = $base . '-' . $counter;
    }
}
