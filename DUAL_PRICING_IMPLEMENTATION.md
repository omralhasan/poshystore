# Dual Pricing System Implementation

## Overview
The Poshy Lifestyle e-commerce system has been updated with a dual pricing structure that supports different pricing for suppliers and customers.

## Changes Made

### 1. Database Schema Updates
- **Location**: `/var/www/html/poshy_store/sql/update_dual_pricing.sql`
- Added three new columns to the `products` table:
  - `supplier_cost` (DECIMAL 10,3) - Cost price for supplier invoices
  - `public_price_min` (DECIMAL 10,3) - Minimum customer price
  - `public_price_max` (DECIMAL 10,3) - Maximum customer price
- Existing `price_jod` column now defaults to minimum public price

### 2. Product Import from Item.pdf
- **Location**: `/var/www/html/poshy_store/import_products.php`
- Parsed all 42 products from Item.pdf
- Each product includes:
  - Product name (English)
  - Supplier cost (from "Cost" column)
  - Public price range (from "Public Price" column)
- Successfully imported all items with proper pricing structure

### 3. Admin Panel Enhancement
- **Location**: `/var/www/html/poshy_store/pages/admin/admin_panel.php`
- Added invoice type selector dropdown for each order
- Two options available:
  - **Customer Invoice** - Shows public customer prices
  - **Supplier Invoice** - Shows supplier cost prices
- Returns invoice type as URL parameter to print_invoice.php

### 4. Invoice System Update
- **Location**: `/var/www/html/poshy_store/pages/admin/print_invoice.php`
- Modified to accept `invoice_type` parameter (customer/supplier)
- Pricing logic:
  - **Customer invoices**: Display `public_price_min` (minimum customer price)
  - **Supplier invoices**: Display `supplier_cost` (wholesale price)
- Invoice total calculated based on selected type
- Invoice header displays "CUSTOMER INVOICE" or "SUPPLIER INVOICE"

## Product Data Summary
- **Total Products**: 42
- **Product Categories**: Cosmetics and skincare items
- **Brands Included**:
  - EQQUAL BERRY
  - Beauty of Joseon
  - Axis-y
  - Anua
  - Dr. Althea
  - The Ordinary
  - Medicube
  - COSRX
  - Celimax
  - Paula's Choice
  - PanOxyl
  - Crest

## Price Examples
| Product | Supplier Cost | Customer Price Range |
|---------|--------------|---------------------|
| EQQUAL BERRY BAKUCHIOL Plumping Serum | 19 JOD | 25-30 JOD |
| The Ordinary Niacinamide 10% + Zinc 1% | 11 JOD | 15-18 JOD |
| PAULA'S CHOICE BHA Liquid Exfoliant | 33 JOD | 42-49 JOD |
| Crest 3D WHITESTRIPS | 35 JOD | 55-60 JOD |

## How to Use

### For Admin Users:
1. Go to Admin Panel → Orders tab
2. Find the order you want to invoice
3. Select invoice type from dropdown:
   - "Customer Invoice" for retail customers
   - "Supplier Invoice" for wholesale/supplier orders
4. Click "Print" button to generate invoice with selected pricing

### Running Import Again:
If you need to re-import products:
```bash
cd /var/www/html/poshy_store
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle < sql/update_dual_pricing.sql
php import_products.php
```

## Technical Notes
- All prices stored in Jordanian Dinars (JOD)
- Price precision: 3 decimal places (DECIMAL 10,3)
- Default stock quantity: 50 units per product
- Default category: Cosmetics (category_id = 1)
- Placeholder image: `images/placeholder-cosmetics.svg`

## Files Modified
1. `/var/www/html/poshy_store/sql/update_dual_pricing.sql` (new)
2. `/var/www/html/poshy_store/import_products.php` (new)
3. `/var/www/html/poshy_store/pages/admin/admin_panel.php` (modified)
4. `/var/www/html/poshy_store/pages/admin/print_invoice.php` (modified)

## Database Impact
- Products table: All previous products cleared and replaced with 42 new items
- Pricing columns: Extended to support dual pricing structure
- Foreign key constraints: Properly handled during migration
