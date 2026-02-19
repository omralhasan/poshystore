# 🎥 See in Action Feature Guide

## Overview
The "See in Action" feature allows you to add product demonstration videos to your product pages. Videos appear as the **first tab** on product pages, allowing customers to immediately see the product in use before reading details.

## Setup Instructions

### 1. Database Migration
First, you need to add the `video_review_url` column to your products table.

**Option A - Using PHP Migration Script:**
```
http://yourdomain.com/database/add_video_review_column.php
```

**Option B - Using SQL File:**
```bash
mysql -u your_username -p your_database < sql/add_video_review_column.sql
```

### 2. Adding Videos to Products

#### Via Admin Panel:
1. Log in to your admin panel
2. Navigate to **Products** → **Edit Product Info**
3. Select the product you want to add a video to
4. Scroll down to the **"See in Action Video URL"** field
5. Enter the video embed URL
6. Click **"Save Changes"**

#### Supported Video Platforms:

**YouTube:**
- Format: `https://www.youtube.com/embed/VIDEO_ID`
- Example: `https://www.youtube.com/embed/dQw4w9WgXcQ`
- To get the embed URL:
  1. Go to your YouTube video
  2. Click "Share" → "Embed"
  3. Copy the URL from the iframe src attribute

**Vimeo:**
- Format: `https://player.vimeo.com/video/VIDEO_ID`
- Example: `https://player.vimeo.com/video/123456789`

**Direct Video URLs:**
- You can also use direct video file URLs (.mp4, .webm, etc.)
- Example: `https://yourdomain.com/videos/product-review.mp4`

### 3. Video Display Features

✅ **First Tab Priority**: Video appears as the first tab, showing immediately  
✅ **Responsive Design**: Videos automatically adjust to screen size  
✅ **16:9 Aspect Ratio**: Maintains professional video format  
✅ **Lazy Loading**: Videos load only when needed for better performance  
✅ **Placeholder**: Shows elegant placeholder if no video is added yet  
✅ **Bilingual Support**: Tab labels in both Arabic (شاهد المنتج) and English (See in Action)  

## User Experience

### When Video is Available:
- Users see "See in Action" as the **first active tab** on the product page
- Video displays immediately when page loads
- Video plays directly on the page without redirecting
- Customers can watch the product demonstration before checking other details

### When No Video is Added:
- Tab still appears first but shows a placeholder message:
  - English: "Video will be added soon"
  - Arabic: "سيتم إضافة الفيديو قريباً"

## Technical Details

### Files Modified:
1. `includes/language.php` - Added translations for "see_in_action"
2. `pages/shop/product_detail.php` - Added tab (first position) and content section
3. `pages/admin/edit_product_info.php` - Added admin form field
4. `database/add_video_review_column.php` - PHP migration script
5. `sql/add_video_review_column.sql` - SQL migration file

### Database Schema:
```sql
ALTER TABLE products 
ADD COLUMN video_review_url VARCHAR(500) DEFAULT NULL 
AFTER how_to_use;
```

### Security:
- All URLs are sanitized using `htmlspecialchars()`
- Input type is set to "url" for validation
- Empty values are handled gracefully

## Best Practices

### Video Content:
- Keep videos between 1-3 minutes for best engagement
- Show product in action: usage, unboxing, or demonstration
- Ensure good lighting and clear audio
- Add captions if possible for accessibility
- Focus on showing the product benefits and features

### Video Quality:
- Minimum 720p (HD) resolution recommended
- Use landscape orientation (16:9)
- Compress videos to reduce loading time
- Test playback on mobile devices

### SEO Tips:
- Add descriptive video titles on platform (YouTube/Vimeo)
- Include product name in video description
- Use relevant keywords and hashtags
- Enable embedding permissions on video platform

## Troubleshooting

### Video Not Showing:
1. Check if migration was run successfully
2. Verify the URL format is correct
3. Ensure the video is set to "Public" or "Unlisted" (not Private)
4. Check if embedding is enabled on the video platform
5. Clear browser cache and refresh the page

### Video Not Playing:
1. Verify the embed URL (not the watch URL)
2. Check video privacy settings on hosting platform
3. Test the URL in a separate browser tab
4. Ensure HTTPS is used (not HTTP)

### Admin Field Not Appearing:
1. Clear browser cache
2. Check if you have admin permissions
3. Refresh the edit product page

## Examples

### Complete Workflow:
```
1. Upload video to YouTube
2. Get embed URL: https://www.youtube.com/embed/ABC123
3. Go to Admin → Edit Product Info
4. Select product (e.g., "Luxury Face Cream")
5. Paste URL in "See in Action Video URL" field
6. Save changes
7. View product page - video shows in first tab "See in Action"
```

## Future Enhancements

Potential features to add later:
- Multiple videos per product
- Video gallery/playlist
- Video thumbnails
- Video upload directly to server
- Video analytics tracking
- Video comments section

## Support

If you encounter any issues:
1. Check the browser console for errors
2. Verify database table structure
3. Test with a simple YouTube video first
4. Ensure all files are properly uploaded

---

**Created:** February 2026  
**Version:** 1.0  
**Status:** ✅ Ready to Use
