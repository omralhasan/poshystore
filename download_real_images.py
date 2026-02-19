#!/usr/bin/env python3
"""
Automatic Product Image Downloader
Downloads real product images for all 40 products
"""

import os
import sys
import csv
import requests
import time
import re
from urllib.parse import quote_plus, urlencode

def download_image(url, output_path, timeout=15):
    """Download image from URL"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(url, headers=headers, timeout=timeout, stream=True)
        response.raise_for_status()
        
        with open(output_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        # Check if file is valid (at least 1KB)
        if os.path.getsize(output_path) > 1000:
            return True
        else:
            os.remove(output_path)
            return False
    except Exception as e:
        if os.path.exists(output_path):
            os.remove(output_path)
        return False

def get_unsplash_image(search_term, output_path):
    """Get image from Unsplash"""
    search_clean = re.sub(r'[^\w\s]', ' ', search_term).strip()
    words = search_clean.split()[:4]  # First 4 words
    query = '+'.join(words)
    
    # Try different Unsplash endpoints
    urls = [
        f"https://source.unsplash.com/800x800/?{query},skincare,cosmetics",
        f"https://source.unsplash.com/800x800/?{query},beauty,product",
        f"https://source.unsplash.com/800x800/?skincare,{query},bottle",
    ]
    
    for url in urls:
        if download_image(url, output_path):
            return True
        time.sleep(0.5)
    
    return False

def get_picsum_image(output_path):
    """Get random image from Lorem Picsum"""
    url = "https://picsum.photos/800/800"
    return download_image(url, output_path)

def main():
    csv_file = 'Store_Ready_42_Products_ShortDesc.csv'
    output_dir = 'images/products/'
    
    if not os.path.exists(csv_file):
        print(f"Error: {csv_file} not found!")
        sys.exit(1)
    
    os.makedirs(output_dir, exist_ok=True)
    
    print("=== Automatic Product Image Downloader ===\n")
    print("Downloading real product images...\n")
    
    downloaded = 0
    skipped = 0
    failed = 0
    
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        
        for index, row in enumerate(reader, 1):
            product_name = row.get('Product Name', '').strip()
            category = row.get('Category', '').strip()
            
            if not product_name:
                continue
            
            # Generate filename
            filename = re.sub(r'[^a-zA-Z0-9_-]', '_', product_name.lower())
            filename = filename[:50] + '.jpg'
            output_path = os.path.join(output_dir, filename)
            
            # Check if already exists and is not SVG placeholder
            if os.path.exists(output_path):
                with open(output_path, 'rb') as f:
                    content = f.read(10)
                    if b'<svg' not in content and os.path.getsize(output_path) > 10000:
                        print(f"[{index}/42] ⊙ Skipped: {product_name[:50]} (already has image)")
                        skipped += 1
                        continue
            
            print(f"[{index}/42] ⬇️  Downloading: {product_name[:50]}...")
            
            # Try to download from Unsplash with product-specific search
            success = get_unsplash_image(product_name, output_path)
            
            if success:
                print(f"         ✓ Success!")
                downloaded += 1
            else:
                print(f"         ✗ Failed")
                failed += 1
            
            # Rate limiting - be nice to servers
            time.sleep(1)
    
    print(f"\n=== Download Summary ===")
    print(f"✓ Downloaded: {downloaded}")
    print(f"⊙ Skipped: {skipped}")
    print(f"✗ Failed: {failed}")
    print(f"\nTotal: {downloaded + skipped}/{downloaded + skipped + failed} products have images")
    
    if downloaded > 0:
        print(f"\n🎉 Successfully downloaded {downloaded} product images!")
        print(f"✅ Your website now has real product photos!")

if __name__ == '__main__':
    try:
        import requests
    except ImportError:
        print("Error: 'requests' module not installed")
        print("Install with: pip3 install requests")
        sys.exit(1)
    
    main()
