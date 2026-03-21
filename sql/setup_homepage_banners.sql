-- Homepage banners table - banners displayed between category sections on homepage
CREATE TABLE IF NOT EXISTS homepage_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) DEFAULT NULL,
    title_ar VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(500) NOT NULL,
    position INT DEFAULT 0 COMMENT 'Sort order / which section gap to place in (0=after first section, 1=after second, etc.)',
    link_url VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
