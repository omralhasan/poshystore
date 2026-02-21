-- ============================================================
-- Poshy Store - Bilingual Product Columns Migration
-- Adds all Arabic-language columns needed for full translation
-- Safe to run multiple times (uses IF NOT EXISTS)
-- 
-- Run on production:
--   mysql -u poshy_user -p poshy_db < sql/migrate_bilingual_columns.sql
-- ============================================================

ALTER TABLE products
    -- Arabic description (long)
    ADD COLUMN IF NOT EXISTS description_ar    TEXT         DEFAULT NULL AFTER description,
    -- Arabic product details / ingredients
    ADD COLUMN IF NOT EXISTS product_details_ar TEXT        DEFAULT NULL AFTER product_details,
    -- Bilingual how-to-use (the old how_to_use column stays for legacy data)
    ADD COLUMN IF NOT EXISTS how_to_use_en     TEXT         DEFAULT NULL AFTER product_details_ar,
    ADD COLUMN IF NOT EXISTS how_to_use_ar     TEXT         DEFAULT NULL AFTER how_to_use_en;

-- Migrate existing how_to_use data into how_to_use_en for backward compatibility
UPDATE products
    SET how_to_use_en = how_to_use
    WHERE how_to_use IS NOT NULL
      AND how_to_use != ''
      AND (how_to_use_en IS NULL OR how_to_use_en = '');

-- Verify
SELECT
    COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'products'
  AND COLUMN_NAME IN (
      'description', 'description_ar',
      'product_details', 'product_details_ar',
      'how_to_use', 'how_to_use_en', 'how_to_use_ar'
  )
ORDER BY ORDINAL_POSITION;
