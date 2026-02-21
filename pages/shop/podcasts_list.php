<?php
/**
 * Podcasts Listing Page
 * Public page showing all published podcasts as cards
 */

require_once __DIR__ . '/../../includes/db_connect.php';

$page_title = 'Podcasts';
$page_description = 'Browse our latest podcasts from Poshy Lifestyle Store';

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS podcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    url_path VARCHAR(255) NOT NULL,
    main_photo VARCHAR(500),
    status ENUM('draft','published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_url_path (url_path),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS podcast_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    podcast_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (podcast_id) REFERENCES podcasts(id) ON DELETE CASCADE,
    INDEX idx_podcast_id (podcast_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get all published podcasts
$podcasts = [];
$result = $conn->query("SELECT p.*, 
    (SELECT COUNT(*) FROM podcast_images WHERE podcast_id = p.id) AS image_count 
    FROM podcasts p 
    WHERE p.status = 'published' 
    ORDER BY p.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $podcasts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Poshy Lifestyle Store</title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a1d2e; --accent-blue: #4f9eff; --accent-teal: #00d4aa;
            --text-dark: #1f2937; --text-gray: #6b7280; --bg-light: #f9fafb;
            --border-color: #e5e7eb; --shadow: 0 4px 6px rgba(0,0,0,.07);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: var(--text-dark); min-height: 100vh; }

        /* Header */
        .page-header-bar {
            background: linear-gradient(135deg, var(--primary-dark), #2d3148);
            color: #fff; padding: 3rem 2rem; text-align: center;
        }
        .page-header-bar h1 {
            font-size: 2.5rem; font-weight: 800; margin-bottom: .5rem;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .page-header-bar p { color: rgba(255,255,255,.7); font-size: 1.1rem; }
        .back-link { display: inline-flex; align-items: center; gap: .5rem; color: rgba(255,255,255,.6); text-decoration: none; margin-bottom: 1rem; font-size: .9rem; transition: color .3s; }
        .back-link:hover { color: #fff; }

        /* Podcast Grid */
        .container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; }
        .podcast-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem; }
        .podcast-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: var(--shadow); transition: transform .3s, box-shadow .3s;
        }
        .podcast-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.12); }
        .podcast-card a { text-decoration: none; color: inherit; }
        .podcast-image {
            width: 100%; height: 220px; object-fit: cover;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        }
        .podcast-placeholder {
            width: 100%; height: 220px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: var(--accent-blue); font-size: 4rem;
        }
        .podcast-body { padding: 1.5rem; }
        .podcast-body h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: .5rem; line-height: 1.4; }
        .podcast-body p {
            color: var(--text-gray); font-size: .9rem; line-height: 1.6;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }
        .podcast-meta {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);
            font-size: .8rem; color: var(--text-gray);
        }
        .podcast-meta .photos { display: flex; align-items: center; gap: .4rem; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 5rem 2rem;
        }
        .empty-state i { font-size: 5rem; color: var(--border-color); margin-bottom: 1rem; }
        .empty-state h2 { font-size: 1.5rem; color: var(--text-gray); margin-bottom: .5rem; }
        .empty-state p { color: var(--text-gray); }

        @media (max-width: 768px) {
            .podcast-grid { grid-template-columns: 1fr; }
            .page-header-bar h1 { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="page-header-bar">
        <a href="../../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Store</a>
        <h1><i class="fas fa-podcast"></i> Podcasts</h1>
        <p>Explore our latest podcasts and lifestyle content</p>
    </div>

    <div class="container">
        <?php if (empty($podcasts)): ?>
            <div class="empty-state">
                <i class="fas fa-podcast"></i>
                <h2>No Podcasts Yet</h2>
                <p>Check back soon for exciting new content!</p>
            </div>
        <?php else: ?>
            <div class="podcast-grid">
                <?php foreach ($podcasts as $p): ?>
                    <div class="podcast-card">
                        <a href="../../podcast/<?php echo htmlspecialchars($p['url_path']); ?>">
                            <?php if ($p['main_photo']): ?>
                                <img src="../../<?php echo htmlspecialchars($p['main_photo']); ?>" class="podcast-image" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="podcast-placeholder"><i class="fas fa-podcast"></i></div>
                            <?php endif; ?>
                            <div class="podcast-body">
                                <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                                <?php if ($p['description']): ?>
                                    <p><?php echo htmlspecialchars($p['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="podcast-meta">
                            <span><i class="far fa-calendar"></i> <?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                            <?php if ($p['image_count'] > 0): ?>
                                <span class="photos"><i class="far fa-images"></i> <?php echo $p['image_count']; ?> photos</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
