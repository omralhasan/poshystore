CREATE DATABASE IF NOT EXISTS poshy_lifestyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON poshy_lifestyle.* TO 'poshy_user'@'localhost';
FLUSH PRIVILEGES;
USE poshy_lifestyle;
CREATE TABLE IF NOT EXISTS homepage_banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  title_ar VARCHAR(255) DEFAULT NULL,
  image_url VARCHAR(500) DEFAULT NULL,
  link_url VARCHAR(500) DEFAULT NULL,
  position INT DEFAULT 0,
  is_active TINYINT DEFAULT 1,
  banner_type ENUM('hero', 'section') NOT NULL DEFAULT 'section',
  subtitle VARCHAR(500) DEFAULT NULL,
  subtitle_ar VARCHAR(500) DEFAULT NULL,
  cta_text VARCHAR(100) DEFAULT NULL,
  cta_text_ar VARCHAR(100) DEFAULT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_banner_type_active (banner_type, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SELECT 'Homepage banners table created successfully' as status;
