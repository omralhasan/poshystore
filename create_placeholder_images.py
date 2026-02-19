#!/usr/bin/env python3
"""
Quick Product Image Placeholder Creator
Creates placeholder images for all products until real images are downloaded
"""

import os
import sys
from PIL import Image, ImageDraw, ImageFont

def create_placeholder_image(product_name, output_path, size=(800, 800)):
    """
    Create a simple placeholder image with product name
    """
    # Create image
    img = Image.new('RGB', size, color=(240, 240, 245))
    draw = ImageDraw.Draw(img)
    
    # Try to use a nice font, fallback to default
    try:
        font_large = ImageFont.truetype("/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf", 40)
        font_small = ImageFont.truetype("/usr/share/fonts/dejavu/DejaVuSans.ttf", 24)
    except:
        font_large = ImageFont.load_default()
        font_small = ImageFont.load_default()
    
    # Draw border
    border_color = (102, 126, 234)
    draw.rectangle([20, 20, size[0]-20, size[1]-20], outline=border_color, width=5)
    
    # Draw icon (simple cosmetics bottle shape)
    bottle_x = size[0] // 2
    bottle_y = size[1] // 3
    draw.ellipse([bottle_x-40, bottle_y-100, bottle_x+40, bottle_y-20], fill=border_color)
    draw.rectangle([bottle_x-30, bottle_y-20, bottle_x+30, bottle_y+100], fill=border_color)
    
    # Draw text
    text = "Product Image"
    text2 = "Coming Soon"
    
    # Calculate text position for centering
    bbox1 = draw.textbbox((0, 0), text, font=font_large)
    text_width1 = bbox1[2] - bbox1[0]
    
    bbox2 = draw.textbbox((0, 0), text2, font=font_small)
    text_width2 = bbox2[2] - bbox2[0]
    
    # Draw centered text
    draw.text(((size[0]-text_width1)//2, size[1]//2 + 100), text, fill=(100, 100, 100), font=font_large)
    draw.text(((size[0]-text_width2)//2, size[1]//2 + 150), text2, fill=(150, 150, 150), font=font_small)
    
    # Save image
    img.save(output_path, 'JPEG', quality=85)

def main():
    import mysql.connector
    
    print("=== Creating Placeholder Images ===\n")
    
    # Database connection
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='poshy_user',
            password='Poshy2026',
            database='poshy_lifestyle'
        )
        cursor = conn.cursor(dictionary=True)
    except Exception as e:
        print(f"✗ Database connection failed: {e}")
        sys.exit(1)
    
    # Get all products
    cursor.execute("SELECT id, name_en, image_link FROM products WHERE image_link IS NOT NULL")
    products = cursor.fetchall()
    
    print(f"Found {len(products)} products\n")
    
    created = 0
    skipped = 0
    failed = 0
    
    for product in products:
        image_path = os.path.join('/var/www/html/poshy_store', product['image_link'])
        
        # Skip if image already exists
        if os.path.exists(image_path):
            skipped += 1
            continue
        
        # Create directory if needed
        os.makedirs(os.path.dirname(image_path), exist_ok=True)
        
        try:
            create_placeholder_image(product['name_en'], image_path)
            print(f"✓ Created placeholder for: {product['name_en'][:50]}...")
            created += 1
        except Exception as e:
            print(f"✗ Failed for {product['name_en'][:50]}: {e}")
            failed += 1
    
    cursor.close()
    conn.close()
    
    print(f"\n=== Summary ===")
    print(f"Created: {created}")
    print(f"Skipped (exists): {skipped}")
    print(f"Failed: {failed}")
    print(f"\nPlaceholder images created! Your website will now show placeholder images.")
    print(f"Next: Download real product images using download_images_guide.php")

if __name__ == '__main__':
    # Check if PIL is available
    try:
        from PIL import Image, ImageDraw, ImageFont
    except ImportError:
        print("Error: PIL (Pillow) not installed")
        print("Install with: pip3 install Pillow mysql-connector-python")
        sys.exit(1)
    
    main()
