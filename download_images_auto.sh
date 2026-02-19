#!/bin/bash
# Quick Product Image Downloader
# Downloads product images from a reliable source

echo "=== Product Image Downloader ==="
echo ""

# Create images directory
mkdir -p /var/www/html/poshy_store/images/products

# Counter
downloaded=0
failed=0

# Get product list from database
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -N -e "SELECT id, name_en, image_link FROM products" | while IFS=$'\t' read -r id name image_link; do
    
    # Full path
    full_path="/var/www/html/poshy_store/$image_link"
    
    # Skip if exists
    if [ -f "$full_path" ]; then
        echo "⊙ Skipping: $name (already exists)"
        continue
    fi
    
    echo "⬇ Downloading for: $name"
    
    # Extract product name for search
    search_query=$(echo "$name" | sed 's/ /+/g')
    
    # Try to download from Unsplash (free stock photos)
    # Using a generic cosmetics/skincare image
    wget -q -O "$full_path" "https://source.unsplash.com/800x800/?cosmetics,skincare,serum" 2>/dev/null
    
    if [ $? -eq 0 ] && [ -s "$full_path" ]; then
        echo "  ✓ Downloaded"
        ((downloaded++))
    else
        echo "  ✗ Failed - will use placeholder"
        ((failed++))
    fi
    
    # Rate limiting - be nice to servers
    sleep 1
done

echo ""
echo "=== Summary ==="
echo "Downloaded: $downloaded"
echo "Failed: $failed"
echo ""
echo "Note: Products without images will show the default placeholder."
