<?php
/**
 * Public Podcast Page
 * 
 * Displays a single podcast by its URL slug.
 * Accessed via: /podcast/my-podcast-slug
 * Rewritten by .htaccess to: podcast.php?slug=my-podcast-slug
 */

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/includes/db_connect.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// If no slug, show podcast listing
if (empty($slug)) {
    require __DIR__ . '/pages/shop/podcasts_list.php';
    exit;
}

// Look up podcast by slug
$stmt = $conn->prepare("SELECT * FROM podcasts WHERE url_path = ? AND status = 'published'");
$stmt->bind_param('s', $slug);
$stmt->execute();
$podcast = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$podcast) {
    http_response_code(404);
    header('Location: index.php');
    exit;
}

// Get gallery images
$gallery = [];
$img_result = $conn->query("SELECT * FROM podcast_images WHERE podcast_id={$podcast['id']} ORDER BY sort_order ASC");
while ($img = $img_result->fetch_assoc()) {
    $gallery[] = $img;
}

$page_title = $podcast['meta_title'] ?: $podcast['title'];
$page_desc  = $podcast['meta_description'] ?: mb_substr(strip_tags($podcast['description']), 0, 160);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Poshy Lifestyle</title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <?php if ($podcast['main_photo']): ?>
    <meta property="og:image" content="<?php echo SITE_URL . '/' . htmlspecialchars($podcast['main_photo']); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/ramadan_theme_header.php'; ?>
    <style>
        .podcast-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .podcast-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .podcast-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: .75rem;
            line-height: 1.3;
        }
        .podcast-date {
            color: #9ca3af;
            font-size: .9rem;
        }
        .podcast-main-image {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .podcast-main-image img {
            width: 100%;
            display: block;
        }
        .podcast-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #374151;
            margin-bottom: 3rem;
            white-space: pre-line;
        }
        .podcast-gallery {
            margin-bottom: 3rem;
        }
        .podcast-gallery h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: #1f2937;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        .gallery-item {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            cursor: pointer;
            transition: transform .3s, box-shadow .3s;
        }
        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
        }
        .gallery-item img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: #4f9eff;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: color .3s;
        }
        .back-link:hover { color: #3b82f6; }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .lightbox.active { display: flex; }
        .lightbox img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
        }
        .lightbox-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            background: rgba(255,255,255,.1);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .3s;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,.25); }
        .lightbox-prev { left: 1.5rem; }
        .lightbox-next { right: 1.5rem; }

        @media (max-width: 768px) {
            .podcast-header h1 { font-size: 1.5rem; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .gallery-item img { height: 160px; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/ramadan_navbar.php'; ?>

    <div class="podcast-container">
        <a href="podcast.php" class="back-link">
            <i class="fas fa-arrow-left"></i> All Podcasts
        </a>

        <div class="podcast-header">
            <h1><?php echo htmlspecialchars($podcast['title']); ?></h1>
            <div class="podcast-date">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('F j, Y', strtotime($podcast['created_at'])); ?>
            </div>
        </div>

        <?php if ($podcast['main_photo']): ?>
            <div class="podcast-main-image">
                <img src="<?php echo htmlspecialchars($podcast['main_photo']); ?>"
                     alt="<?php echo htmlspecialchars($podcast['title']); ?>">
            </div>
        <?php endif; ?>

        <?php if (!empty($podcast['description'])): ?>
            <div class="podcast-description">
                <?php echo nl2br(htmlspecialchars($podcast['description'])); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($gallery)): ?>
            <div class="podcast-gallery">
                <h2><i class="fas fa-images"></i> Gallery</h2>
                <div class="gallery-grid">
                    <?php foreach ($gallery as $i => $img): ?>
                        <div class="gallery-item" onclick="openLightbox(<?php echo $i; ?>)">
                            <img src="<?php echo htmlspecialchars($img['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($podcast['title']); ?> - Image <?php echo $i + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
        <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <button class="lightbox-nav lightbox-prev" onclick="event.stopPropagation(); prevImage()"><i class="fas fa-chevron-left"></i></button>
        <img id="lightboxImg" src="" alt="">
        <button class="lightbox-nav lightbox-next" onclick="event.stopPropagation(); nextImage()"><i class="fas fa-chevron-right"></i></button>
    </div>

    <?php require_once __DIR__ . '/includes/ramadan_footer.php'; ?>

    <script>
    const images = <?php echo json_encode(array_map(fn($img) => $img['image_path'], $gallery)); ?>;
    let currentIdx = 0;

    function openLightbox(idx) {
        currentIdx = idx;
        document.getElementById('lightboxImg').src = images[idx];
        document.getElementById('lightbox').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox(e) {
        if (e && e.target !== e.currentTarget && !e.target.closest('.lightbox-close')) return;
        document.getElementById('lightbox').classList.remove('active');
        document.body.style.overflow = '';
    }
    function prevImage() {
        currentIdx = (currentIdx - 1 + images.length) % images.length;
        document.getElementById('lightboxImg').src = images[currentIdx];
    }
    function nextImage() {
        currentIdx = (currentIdx + 1) % images.length;
        document.getElementById('lightboxImg').src = images[currentIdx];
    }
    document.addEventListener('keydown', e => {
        if (!document.getElementById('lightbox').classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
    });
    </script>
</body>
</html>
