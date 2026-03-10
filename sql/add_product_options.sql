-- Product Options / Variants System
-- Allows admin to add optional attributes like size (30ml/60ml) and color
-- Each option value can have its own price adjustment

-- 1. Product option groups (e.g., "Size", "Color")
CREATE TABLE IF NOT EXISTS product_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    option_name_en VARCHAR(100) NOT NULL COMMENT 'e.g. Size, Color',
    option_name_ar VARCHAR(100) DEFAULT NULL,
    option_type ENUM('select', 'color') DEFAULT 'select' COMMENT 'select = dropdown, color = color swatches',
    sort_order INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0 COMMENT 'Must the customer choose?',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Option values (e.g., "30ml" → 5.000 JOD, "60ml" → 8.500 JOD)
CREATE TABLE IF NOT EXISTS product_option_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    value_en VARCHAR(100) NOT NULL COMMENT 'e.g. 30ml, 60ml, Red, Blue',
    value_ar VARCHAR(100) DEFAULT NULL,
    color_hex VARCHAR(7) DEFAULT NULL COMMENT '#FF0000 for color swatches',
    price_jod DECIMAL(10,3) DEFAULT NULL COMMENT 'Override price when this value is selected (NULL = no change)',
    price_adjustment DECIMAL(10,3) DEFAULT 0 COMMENT 'Add/subtract from base price (alternative to override)',
    stock_quantity INT DEFAULT NULL COMMENT 'Per-variant stock (NULL = use product stock)',
    sort_order INT DEFAULT 0,
    is_default TINYINT(1) DEFAULT 0 COMMENT 'Default selected value',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add has_options flag to products for quick filtering
ALTER TABLE products
ADD COLUMN has_options TINYINT(1) DEFAULT 0 COMMENT 'Whether this product has variant options'
AFTER has_discount;
