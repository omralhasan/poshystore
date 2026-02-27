<?php
/**
 * Product Image Helper
 * Matches products to their image folders in /images/ directory
 * Uses multiple matching strategies: exact, contains, keyword-based, brand-stripped
 */

// Known brand prefixes (lowercase) used to improve matching
define('BRAND_PREFIXES', [
    'the ordinary.', 'the ordinary', 'somebymi', 'anua', 'dr. althea', 'dr.althea',
    'eqqual berry', 'axis-y', 'beauty of joseon', 'medicube', 'celimax',
    "paula's choice", 'cosrx', 'panoxyl', 'seoul 1988', 'madagascar centella',
    'crest 3d'
]);

/**
 * Normalize a string for comparison
 */
function normalize_product_name($str) {
    $str = strtolower(trim($str));
    // Remove number prefixes like "18 24-28 10 "
    $str = preg_replace('/^\d+\s+\d+-\d+\s+\d+\s+/', '', $str);
    // "The Ordinary." → "the ordinary"
    $str = str_replace('the ordinary.', 'the ordinary', $str);
    // Remove trademark symbols
    $str = str_replace(['™', '®', '©'], '', $str);
    // Collapse multiple spaces
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

/**
 * Strip known brand prefix from a normalized name
 * Returns [brand, remaining] or [null, original]
 */
function strip_brand_prefix($normalized_str) {
    foreach (BRAND_PREFIXES as $brand) {
        if (strpos($normalized_str, $brand) === 0) {
            $rest = trim(substr($normalized_str, strlen($brand)));
            if (!empty($rest)) {
                return [$brand, $rest];
            }
        }
    }
    return [null, $normalized_str];
}

/**
 * Extract meaningful keywords from a string (3+ chars)
 */
function extract_product_keywords($str) {
    $str = normalize_product_name($str);
    $words = preg_split('/[\s\+\-\(\)&,\.]+/', $str);
    return array_values(array_filter($words, function($w) { return strlen($w) >= 3; }));
}

/**
 * Count how many keywords from $source match keywords in $target
 * Uses exact match and substring match (for words >= 4 chars)
 */
function count_keyword_matches($source_keywords, $target_keywords) {
    $matches = 0;
    foreach ($source_keywords as $pw) {
        foreach ($target_keywords as $fw) {
            if ($pw === $fw || (strlen($pw) >= 4 && strlen($fw) >= 4 && (stripos($fw, $pw) !== false || stripos($pw, $fw) !== false))) {
                $matches++;
                break;
            }
        }
    }
    return $matches;
}

/**
 * Check if a directory contains any PNG files (uses scandir, not glob, 
 * to avoid issues with special characters like [], *, ? in folder names)
 */
function dir_has_png($dir_path) {
    $allowed_ext = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $files = @scandir($dir_path);
    if (!$files) return false;
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            return true;
        }
    }
    return false;
}

/**
 * Get all image files in a directory (sorted), supporting png, jpg, jpeg, gif, webp
 */
function get_png_files($dir_path) {
    $allowed_ext = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $images = [];
    $files = @scandir($dir_path);
    if (!$files) return $images;
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $images[] = $file;
        }
    }
    sort($images);
    return $images;
}

/**
 * URL-encode an image path segment by segment (preserving /)
 * e.g. "images/The Ordinary 10% + Zinc/1.png" → "images/The%20Ordinary%2010%25%20%2B%20Zinc/1.png"
 */
function encode_image_path($path) {
    $segments = explode('/', $path);
    $encoded = array_map('rawurlencode', $segments);
    return implode('/', $encoded);
}

/**
 * Find the matching image folder for a product name
 * Returns the folder name (not full path) or null if no match
 * 
 * Strategies (in order of priority):
 *  1. Exact normalized match
 *  2. One normalized name contains the other
 *  3. Brand-stripped contains (e.g., strip "The Ordinary" from both)
 *  4. Full keyword matching (>= 60% with >= 3 matched keywords)
 *  5. Brand-stripped keyword matching (>= 80% with >= 2 matched keywords)
 */
function find_product_image_folder($product_name, $images_dir) {
    static $folder_cache = null;
    
    // Cache the folder list (scan once, reuse) — uses scandir to avoid glob special char issues
    if ($folder_cache === null) {
        $folder_cache = [];
        if (is_dir($images_dir)) {
            $dir = new DirectoryIterator($images_dir);
            foreach ($dir as $f) {
                if ($f->isDir() && !$f->isDot() && $f->getFilename() !== 'products') {
                    $name = $f->getFilename();
                    if (dir_has_png($f->getPathname())) {
                        $folder_cache[] = $name;
                    }
                }
            }
        }
    }
    
    $p_norm = normalize_product_name($product_name);
    list($p_brand, $p_rest) = strip_brand_prefix($p_norm);
    
    $best_folder = null;
    $best_score = 0;
    
    foreach ($folder_cache as $folder) {
        $f_norm = normalize_product_name($folder);
        
        // Strategy 1: Exact normalized match (score = 1.0)
        if ($f_norm === $p_norm) {
            return $folder;
        }
        
        // Strategy 2: One contains the other (score = 0.95)
        if (stripos($f_norm, $p_norm) !== false || stripos($p_norm, $f_norm) !== false) {
            if (0.95 > $best_score) {
                $best_folder = $folder;
                $best_score = 0.95;
            }
            continue;
        }
        
        // Strategy 3: Brand-stripped contains (score = 0.85)
        if ($p_brand !== null) {
            list($f_brand, $f_rest) = strip_brand_prefix($f_norm);
            // If both have same brand, compare the rest
            if ($f_brand === $p_brand && !empty($f_rest)) {
                if (stripos($f_rest, $p_rest) !== false || stripos($p_rest, $f_rest) !== false) {
                    if (0.85 > $best_score) {
                        $best_folder = $folder;
                        $best_score = 0.85;
                    }
                    continue;
                }
            }
            // Also try: product rest contained in folder (even without same brand)
            if (strlen($p_rest) >= 8 && stripos($f_norm, $p_rest) !== false) {
                if (0.85 > $best_score) {
                    $best_folder = $folder;
                    $best_score = 0.85;
                }
                continue;
            }
        }
        
        // Strategy 4: Full keyword matching (need >= 60% match and at least 3 matched keywords)
        $p_kw = extract_product_keywords($product_name);
        $f_kw = extract_product_keywords($folder);
        $matched = count_keyword_matches($p_kw, $f_kw);
        $score = empty($p_kw) ? 0 : $matched / count($p_kw);
        
        if ($score > $best_score && $score >= 0.6 && $matched >= 3) {
            $best_folder = $folder;
            $best_score = $score;
        }
        
        // Strategy 5: Brand-stripped keyword matching (higher threshold, fewer required)
        // Strip brand from product and compare remaining keywords against folder
        if ($p_brand !== null && $best_score < 0.75) {
            $rest_kw = extract_product_keywords($p_rest);
            if (count($rest_kw) >= 2) {
                $rest_matched = count_keyword_matches($rest_kw, $f_kw);
                $rest_score = $rest_matched / count($rest_kw);
                // Need >= 80% of remaining keywords to match, at least 2
                $effective_score = 0.75; // Between brand-stripped contains and full keyword
                if ($rest_score >= 0.8 && $rest_matched >= 2 && $effective_score > $best_score) {
                    $best_folder = $folder;
                    $best_score = $effective_score;
                }
            }
        }
    }
    
    return $best_folder;
}

/**
 * Get the 1.png path for homepage display
 * Returns relative path from site root (e.g., "images/FolderName/1.png")
 * 
 * @param string $product_name The product name
 * @param string $image_link The database image_link fallback
 * @param string $base_dir The site root directory (__DIR__ from calling file)
 * @return string The image path to use
 */
function get_product_thumbnail($product_name, $image_link, $base_dir) {
    $images_dir = $base_dir . '/images';
    $folder = find_product_image_folder($product_name, $images_dir);
    
    if ($folder) {
        $folder_path = $images_dir . '/' . $folder;
        // Check for 1.* (any image extension)
        $allowed_ext = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        foreach ($allowed_ext as $ext) {
            $one_img = $folder_path . '/1.' . $ext;
            if (file_exists($one_img)) {
                $mtime = filemtime($one_img);
                return encode_image_path('images/' . $folder . '/1.' . $ext) . '?v=' . $mtime;
            }
        }
        // If no 1.*, use first available image
        $imgs = get_png_files($folder_path);
        if (!empty($imgs)) {
            $mtime = filemtime($folder_path . '/' . $imgs[0]);
            return encode_image_path('images/' . $folder . '/' . $imgs[0]) . '?v=' . $mtime;
        }
    }
    
    // Fallback to database image_link
    if (!empty($image_link) && $image_link !== 'NULL') {
        $full = $base_dir . '/' . $image_link;
        $mtime = file_exists($full) ? filemtime($full) : time();
        return encode_image_path($image_link) . '?v=' . $mtime;
    }
    
    return 'images/placeholder-cosmetics.svg';
}

/**
 * Get ALL product images for gallery/carousel (product detail page)
 * Returns array of relative paths from the calling file location
 * 
 * @param string $product_name The product name
 * @param string $image_link The database image_link fallback
 * @param string $images_dir Absolute path to the images directory
 * @param string $path_prefix Prefix for relative paths (e.g., "../../" for pages/shop/)
 * @return array Array of image paths
 */
function get_product_gallery_images($product_name, $image_link, $images_dir, $path_prefix = '') {
    $folder = find_product_image_folder($product_name, $images_dir);
    $gallery = [];
    $allowed_ext = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    
    if ($folder) {
        $folder_path = $images_dir . '/' . $folder;
        $all_imgs = get_png_files($folder_path);
        
        $enc_folder = rawurlencode($folder);
        
        // 1. First add 1.* (main image - any extension)
        foreach ($all_imgs as $img) {
            $base = pathinfo($img, PATHINFO_FILENAME);
            if ($base === '1') {
                $gallery[] = $path_prefix . 'images/' . $enc_folder . '/' . rawurlencode($img);
                break;
            }
        }
        
        // 2. Add img-*.* files (sorted)
        foreach ($all_imgs as $img) {
            if (strpos($img, 'img-') === 0) {
                $path = $path_prefix . 'images/' . $enc_folder . '/' . rawurlencode($img);
                if (!in_array($path, $gallery)) {
                    $gallery[] = $path;
                }
            }
        }
        
        // 3. Add numbered files 2.*, 3.*, etc.
        for ($i = 2; $i <= 20; $i++) {
            foreach ($all_imgs as $img) {
                $base = pathinfo($img, PATHINFO_FILENAME);
                if ($base === (string)$i) {
                    $path = $path_prefix . 'images/' . $enc_folder . '/' . rawurlencode($img);
                    if (!in_array($path, $gallery)) {
                        $gallery[] = $path;
                    }
                    break;
                }
            }
        }
        
        // 4. Add any remaining image files not already included
        foreach ($all_imgs as $img) {
            $path = $path_prefix . 'images/' . $enc_folder . '/' . rawurlencode($img);
            if (!in_array($path, $gallery)) {
                $gallery[] = $path;
            }
        }
    }
    
    // Fallback to database image
    if (empty($gallery) && !empty($image_link) && $image_link !== 'NULL') {
        $gallery[] = $path_prefix . encode_image_path($image_link);
    }
    
    return $gallery;
}
