#!/bin/bash
# Quick Fix: Use placeholder images for all products
# This makes the website show images immediately while you download real ones

echo "=== Quick Fix: Applying Placeholder Images ==="
echo ""

# Create products directory
mkdir -p /var/www/html/poshy_store/images/products

# Check if placeholder exists
if [ ! -f "/var/www/html/poshy_store/images/placeholder-cosmetics.svg" ]; then
    echo "✗ Error: Placeholder image not found"
    exit 1
fi

# Create a generic JPG placeholder from SVG or use a simple approach
echo "Creating placeholder images for all products..."

count=0

# Get all image links from database and create placeholder copies
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -N -e "SELECT image_link FROM products WHERE image_link IS NOT NULL" | while read image_link; do
    
    full_path="/var/www/html/poshy_store/$image_link"
    
    # Skip if already exists
    if [ -f "$full_path" ]; then
        continue
    fi
    
    # Create directory if needed
    mkdir -p "$(dirname "$full_path")"
    
    # Copy placeholder
    cp /var/www/html/poshy_store/images/placeholder-cosmetics.svg "$full_path" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "✓ Created: $image_link"
        ((count++))
    fi
done

echo ""
echo "=== Complete ==="
echo "Created $count placeholder images"
echo ""
echo "✅ Your website will now show placeholder images for all products!"
echo ""
echo "Next steps:"
echo "1. Open: http://your-server/poshy_store/download_images_guide.php"
echo "2. Download real product images one by one"
echo "3. Images will automatically replace placeholders"
