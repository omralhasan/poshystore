<?php
/**
 * Batch convert JPG/JPEG/PNG assets to WebP.
 *
 * Usage:
 *   php convert_images_to_webp.php
 *   php convert_images_to_webp.php --quality=72
 *   php convert_images_to_webp.php --dirs=images,uploads --dry-run
 *   php convert_images_to_webp.php --force
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI-only.\n";
    exit(1);
}

$options = getopt('', ['quality::', 'dirs::', 'dry-run', 'force', 'help']);

if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  php convert_images_to_webp.php [--quality=75] [--dirs=images,uploads] [--dry-run] [--force]\n";
    echo "\nOptions:\n";
    echo "  --quality=N     WebP quality (40-95), default 75\n";
    echo "  --dirs=list     Comma-separated relative dirs, default: images,uploads\n";
    echo "  --dry-run       Show what would be converted without writing files\n";
    echo "  --force         Reconvert even if .webp already exists and is newer\n";
    exit(0);
}

$quality = isset($options['quality']) ? (int)$options['quality'] : 75;
$quality = max(40, min(95, $quality));

$dirs = ['images', 'uploads'];
if (!empty($options['dirs']) && is_string($options['dirs'])) {
    $dirs = array_values(array_filter(array_map('trim', explode(',', $options['dirs']))));
}

$dry_run = isset($options['dry-run']);
$force = isset($options['force']);
$source_extensions = ['jpg', 'jpeg', 'png'];
$root_dir = __DIR__;

$backend = 'none';
$magick_bin = null;
if (!$dry_run) {
    if (function_exists('imagewebp')) {
        $backend = 'gd';
    } elseif (class_exists('Imagick')) {
        $backend = 'imagick';
    } else {
        $magick_bin = find_binary('magick');
        if ($magick_bin !== null) {
            $backend = 'magick';
        }
    }

    if ($backend === 'none') {
        fwrite(STDERR, "No WebP conversion backend found. Enable PHP GD with WebP, install Imagick, or install ImageMagick (magick).\n");
        exit(1);
    }

    echo "Using backend: {$backend}\n";
}

$stats = [
    'processed' => 0,
    'converted' => 0,
    'skipped' => 0,
    'failed' => 0,
    'bytes_before' => 0,
    'bytes_after' => 0,
];

foreach ($dirs as $rel_dir) {
    $abs_dir = $root_dir . '/' . ltrim($rel_dir, '/');
    if (!is_dir($abs_dir)) {
        echo "Skip missing directory: {$rel_dir}\n";
        continue;
    }

    echo "Scanning: {$rel_dir}\n";

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($abs_dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file_info) {
        if (!$file_info->isFile()) {
            continue;
        }

        $ext = strtolower($file_info->getExtension());
        if (!in_array($ext, $source_extensions, true)) {
            continue;
        }

        $src_path = $file_info->getPathname();
        $dst_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src_path);
        if ($dst_path === null) {
            $stats['failed']++;
            echo "[FAIL] Regex error for: " . to_relative($src_path, $root_dir) . "\n";
            continue;
        }

        if (!$force && file_exists($dst_path) && filemtime($dst_path) >= filemtime($src_path)) {
            $stats['skipped']++;
            continue;
        }

        $stats['processed']++;

        if ($dry_run) {
            echo "[DRY ] " . to_relative($src_path, $root_dir) . " -> " . to_relative($dst_path, $root_dir) . "\n";
            continue;
        }

        $ok = false;
        if ($backend === 'gd') {
            $image = load_source_image($src_path, $ext);
            if ($image) {
                if ($ext === 'png') {
                    if (function_exists('imagepalettetotruecolor')) {
                        @imagepalettetotruecolor($image);
                    }
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                $ok = @imagewebp($image, $dst_path, $quality);
                imagedestroy($image);
            }
        } elseif ($backend === 'imagick') {
            try {
                $img = new Imagick();
                $img->readImage($src_path);
                $img->setImageFormat('webp');
                $img->setImageCompressionQuality($quality);
                $ok = $img->writeImage($dst_path);
                $img->clear();
                $img->destroy();
            } catch (Throwable $e) {
                $ok = false;
            }
        } elseif ($backend === 'magick' && $magick_bin !== null) {
            $cmd = escapeshellarg($magick_bin)
                . ' ' . escapeshellarg($src_path)
                . ' -quality ' . (int)$quality
                . ' ' . escapeshellarg($dst_path)
                . ' 2>/dev/null';
            exec($cmd, $unused_output, $exit_code);
            $ok = ($exit_code === 0 && file_exists($dst_path));
        }

        if (!$ok) {
            $stats['failed']++;
            echo "[FAIL] Could not convert: " . to_relative($src_path, $root_dir) . "\n";
            continue;
        }

        clearstatcache(true, $src_path);
        clearstatcache(true, $dst_path);

        $before = filesize($src_path) ?: 0;
        $after = filesize($dst_path) ?: 0;
        $stats['converted']++;
        $stats['bytes_before'] += $before;
        $stats['bytes_after'] += $after;

        $saved_pct = ($before > 0) ? round((1 - ($after / $before)) * 100, 1) : 0.0;
        echo "[OK  ] " . to_relative($src_path, $root_dir) . " -> " . to_relative($dst_path, $root_dir)
            . " ({$saved_pct}% smaller)\n";
    }
}

echo "\n=== WebP Conversion Summary ===\n";
echo "Processed: {$stats['processed']}\n";
echo "Converted: {$stats['converted']}\n";
echo "Skipped:   {$stats['skipped']}\n";
echo "Failed:    {$stats['failed']}\n";

if (!$dry_run && $stats['converted'] > 0 && $stats['bytes_before'] > 0) {
    $overall_saved = round((1 - ($stats['bytes_after'] / $stats['bytes_before'])) * 100, 1);
    echo "Original size: " . format_bytes($stats['bytes_before']) . "\n";
    echo "WebP size:     " . format_bytes($stats['bytes_after']) . "\n";
    echo "Total saved:   {$overall_saved}%\n";
}

exit($stats['failed'] > 0 ? 1 : 0);

function load_source_image(string $path, string $ext)
{
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            return @imagecreatefromjpeg($path);
        case 'png':
            return @imagecreatefrompng($path);
        default:
            return false;
    }
}

function to_relative(string $path, string $root): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $norm = str_replace('\\', '/', $path);
    if (strpos($norm, $root . '/') === 0) {
        return substr($norm, strlen($root) + 1);
    }
    return $norm;
}

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float)$bytes;
    $unit = 0;

    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }

    return number_format($value, 2) . ' ' . $units[$unit];
}

function find_binary(string $binary): ?string
{
    $path_env = getenv('PATH') ?: '';
    foreach (explode(':', $path_env) as $dir) {
        if ($dir === '') {
            continue;
        }
        $full = rtrim($dir, '/') . '/' . $binary;
        if (is_file($full) && is_executable($full)) {
            return $full;
        }
    }
    return null;
}
