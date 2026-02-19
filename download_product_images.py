#!/usr/bin/env python3
"""
Product Image Downloader
Downloads product images from Google Image Search
"""

import os
import sys
import csv
import requests
import re
from urllib.parse import quote_plus, unquote
import time

def extract_image_urls(search_url, max_results=1):
    """
    Extract image URLs from Google Image Search
    """
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }
    
    try:
        response = requests.get(search_url, headers=headers, timeout=10)
        response.raise_for_status()
        
        # Extract image URLs from the HTML
        # Look for image URLs in the page source
        img_urls = []
        
        # Try to find direct image links
        patterns = [
            r'"(https?://[^"]+\.jpg)"',
            r'"(https?://[^"]+\.jpeg)"',
            r'"(https?://[^"]+\.png)"',
            r'"(https?://[^"]+\.webp)"',
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, response.text)
            img_urls.extend(matches)
            if len(img_urls) >= max_results:
                break
        
        # Return unique URLs
        return list(set(img_urls))[:max_results]
    
    except Exception as e:
        print(f"  ✗ Error extracting images: {e}")
        return []

def download_image(url, output_path):
    """
    Download image from URL and save to output path
    """
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        response = requests.get(url, headers=headers, timeout=15, stream=True)
        response.raise_for_status()
        
        with open(output_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        return True
    
    except Exception as e:
        print(f"  ✗ Download failed: {e}")
        return False

def main():
    csv_file = 'Store_Ready_42_Products_ShortDesc.csv'
    output_dir = 'images/products/'
    
    if not os.path.exists(csv_file):
        print(f"Error: CSV file '{csv_file}' not found!")
        sys.exit(1)
    
    # Create output directory
    os.makedirs(output_dir, exist_ok=True)
    
    print("=== Product Image Downloader ===\n")
    print(f"Output directory: {output_dir}\n")
    
    downloaded = 0
    failed = 0
    skipped = 0
    
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        
        for index, row in enumerate(reader, 1):
            product_name = row.get('Product Name', '')
            image_link = row.get('Image Link', '')
            
            if not product_name or not image_link:
                print(f"[{index}] Skipping - missing data")
                skipped += 1
                continue
            
            # Generate filename
            filename = re.sub(r'[^a-zA-Z0-9_-]', '_', product_name.lower())
            filename = filename[:50] + '.jpg'
            output_path = os.path.join(output_dir, filename)
            
            # Skip if already exists
            if os.path.exists(output_path):
                print(f"[{index}] {product_name[:50]}... - Already exists")
                skipped += 1
                continue
            
            print(f"\n[{index}] Processing: {product_name[:60]}...")
            print(f"  Search URL: {image_link}")
            
            # Extract image URLs from Google search
            print(f"  Searching for images...")
            image_urls = extract_image_urls(image_link, max_results=3)
            
            if not image_urls:
                print(f"  ⚠ No images found - manual download required")
                print(f"  Visit: {image_link}")
                failed += 1
                continue
            
            # Try to download the first available image
            success = False
            for img_url in image_urls:
                print(f"  Trying: {img_url[:80]}...")
                if download_image(img_url, output_path):
                    print(f"  ✓ Downloaded: {filename}")
                    downloaded += 1
                    success = True
                    break
                time.sleep(0.5)  # Be nice to servers
            
            if not success:
                print(f"  ✗ All download attempts failed")
                failed += 1
            
            # Rate limiting
            time.sleep(1)
    
    print("\n\n=== Download Summary ===")
    print(f"Successfully downloaded: {downloaded}")
    print(f"Failed/Manual required: {failed}")
    print(f"Skipped (already exists): {skipped}")
    print(f"Total products: {index}")
    
    if failed > 0:
        print("\n=== Manual Download Required ===")
        print(f"For products that failed, please:")
        print(f"1. Visit the Google search link")
        print(f"2. Download the product image manually")
        print(f"3. Save it to: {output_dir}")
        print(f"4. Use the filename format: product_name.jpg")

if __name__ == '__main__':
    main()
