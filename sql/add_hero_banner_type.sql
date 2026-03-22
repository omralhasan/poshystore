-- Add banner_type column to distinguish hero slider banners from section banners
-- 'hero' = appears in the top hero slider
-- 'section' = appears between category sections (existing behavior)
ALTER TABLE homepage_banners
    ADD COLUMN IF NOT EXISTS banner_type ENUM('hero', 'section') NOT NULL DEFAULT 'section' AFTER title_ar,
    ADD COLUMN IF NOT EXISTS subtitle VARCHAR(500) DEFAULT NULL AFTER banner_type,
    ADD COLUMN IF NOT EXISTS subtitle_ar VARCHAR(500) DEFAULT NULL AFTER subtitle,
    ADD COLUMN IF NOT EXISTS cta_text VARCHAR(100) DEFAULT NULL AFTER subtitle_ar,
    ADD COLUMN IF NOT EXISTS cta_text_ar VARCHAR(100) DEFAULT NULL AFTER cta_text,
    ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER cta_text_ar;

-- Index for fast hero banner queries
ALTER TABLE homepage_banners ADD INDEX idx_banner_type_active (banner_type, is_active, sort_order);
