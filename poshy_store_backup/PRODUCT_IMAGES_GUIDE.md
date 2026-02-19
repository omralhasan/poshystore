# Product Images Guide

## Current Status
All products currently use the placeholder image: `images/placeholder-cosmetics.svg`

## How to Add Product Images

### Option 1: Manual Upload (Recommended)
1. **Find Product Images**: Search for official product images from:
   - Brand official websites
   - Authorized retailers
   - High-quality product photography (1000x1000px minimum)

2. **Image Requirements**:
   - Format: JPG or PNG
   - Size: 1000x1000px to 2000x2000px (square)
   - File size: Under 500KB (optimize if needed)
   - White or transparent background preferred

3. **Upload Process**:
   ```bash
   # Create product images directory
   mkdir -p /var/www/html/poshy_store/images/products
   
   # Upload images via FTP/SFTP or direct copy
   # Name format: product_[ID].jpg or product_[name].jpg
   ```

4. **Update Database**:
   ```sql
   UPDATE products 
   SET image_link = 'images/products/product_1.jpg' 
   WHERE id = 1;
   ```

### Option 2: Use Image URLs (Quick Method)
If you have product image URLs from suppliers or online sources:

```php
// Update individual product
$image_url = 'https://example.com/product-image.jpg';
$product_id = 1;
$sql = "UPDATE products SET image_link = ? WHERE id = ?";
```

### Option 3: Bulk Update Script
Create a CSV file with product IDs and image URLs, then:

```php
// bulk_update_images.php
$csv = fopen('product_images.csv', 'r');
while (($row = fgetcsv($csv)) !== false) {
    $product_id = $row[0];
    $image_url = $row[1];
    // Update database...
}
```

## Image Sources for Your Products

### 1. The Ordinary Products (IDs: 23-36)
- Official site: https://theordinary.com
- High-quality product shots available
- Consistent branding and packaging

### 2. Beauty of Joseon (IDs: 4-5)
- Official site: https://beautyofjoseon.com
- Traditional Korean packaging design
- Professional product photography

### 3. COSRX (ID: 40)
- Official site: https://cosrx.com
- Clean, minimalist packaging
- White background product images

### 4. Other K-Beauty Brands (Anua, Axis-y, Dr. Althea, etc.)
- Search on authorized retailers:
  - YesStyle.com
  - StyleKorean.com
  - Olive Young

### 5. Western Brands (Paula's Choice, PanOxyl, Crest)
- Official brand websites
- Amazon product listings
- Retailer sites (Ulta, Sephora, drugstore.com)

## Recommended Image Naming Convention

```
images/products/
├── eqqual-berry-bakuchiol-serum.jpg
├── eqqual-berry-glow-filter-serum.jpg
├── beauty-joseon-relief-sun-rice.jpg
├── the-ordinary-niacinamide.jpg
├── cosrx-snail-mucin.jpg
└── ...
```

## Quick Update Examples

### Update Single Product with Local Image:
```bash
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -e "
UPDATE products 
SET image_link = 'images/products/the-ordinary-niacinamide.jpg' 
WHERE id = 25;"
```

### Update Multiple Products:
```sql
UPDATE products SET image_link = 'images/products/eqqual-berry-bakuchiol.jpg' WHERE id = 1;
UPDATE products SET image_link = 'images/products/eqqual-berry-glow-filter.jpg' WHERE id = 2;
UPDATE products SET image_link = 'images/products/beauty-joseon-sun-rice.jpg' WHERE id = 4;
-- ... continue for all products
```

## Image Optimization Tools
- **TinyPNG**: https://tinypng.com (compress images)
- **ImageMagick**: `mogrify -resize 1200x1200 -quality 85 *.jpg`
- **Online converters**: For WebP format (faster loading)

## Copyright Notice
⚠️ **Important**: Ensure you have rights to use product images:
- Use official brand images from authorized sources
- Obtain permission from brands if selling their products
- Credit sources when required
- Never use copyrighted images without authorization

## Next Steps
1. Contact your suppliers for official product images
2. Download high-quality images from brand websites
3. Organize images in `/var/www/html/poshy_store/images/products/`
4. Run batch update to link images to products
5. Test image display on product pages

## Placeholder Image
Current placeholder: `images/placeholder-cosmetics.svg`
- Modern SVG design
- Lightweight (fast loading)
- Professional appearance
- Works until real images are added
