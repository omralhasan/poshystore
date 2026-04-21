-- ============================================================
-- Poshy Store - Bilingual Product Columns Migration
-- Adds all Arabic-language columns needed for full translation
-- Safe to run multiple times on MySQL/MariaDB versions that do
-- not support "ADD COLUMN IF NOT EXISTS".
--
-- Run on production:
--   mysql -u poshy_user -p poshy_db < sql/migrate_bilingual_columns.sql
-- ============================================================

SET @schema_name = DATABASE();

-- Arabic description (long)
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = 'description_ar'
    ),
    'SELECT ''description_ar exists''',
    'ALTER TABLE products ADD COLUMN description_ar TEXT DEFAULT NULL AFTER description'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Arabic product details / ingredients
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = 'product_details_ar'
    ),
    'SELECT ''product_details_ar exists''',
    'ALTER TABLE products ADD COLUMN product_details_ar TEXT DEFAULT NULL AFTER product_details'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bilingual how-to-use (the old how_to_use column stays for legacy data)
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = 'how_to_use_en'
    ),
    'SELECT ''how_to_use_en exists''',
    'ALTER TABLE products ADD COLUMN how_to_use_en TEXT DEFAULT NULL AFTER product_details_ar'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = 'how_to_use_ar'
    ),
    'SELECT ''how_to_use_ar exists''',
    'ALTER TABLE products ADD COLUMN how_to_use_ar TEXT DEFAULT NULL AFTER how_to_use_en'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
