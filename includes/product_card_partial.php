<?php
/**
 * Product Card Partial - Reusable product card component
 * 
 * Expected variables in scope:
 *   $product - product array with all fields
 *   $idx     - card index (for animation delay)
 *   $lang    - current language ('en' or 'ar')
 */
?>
<div class="p-card fade-in<?= ($product['stock_quantity'] <= 0) ? ' out-of-stock-card' : '' ?>" style="animation-delay: <?= $idx * 0.05 ?>s;">
    <div class="p-card-img">
        <?php if ($product['stock_quantity'] <= 0): ?>
            <span class="out-of-stock-tag" style="position:absolute;top:10px;left:10px;z-index:5;background:rgba(239,68,68,0.92);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;"><?= $lang === 'ar' ? 'نفذت الكمية' : 'Out of Stock' ?></span>
        <?php endif; ?>

        <?php if (!empty($product['is_best_seller'])): ?>
            <span style="position:absolute;top:<?= ($product['stock_quantity'] <= 0) ? '38px' : '10px' ?>;left:10px;z-index:5;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="fas fa-fire"></i> <?= $lang === 'ar' ? 'الأكثر مبيعاً' : 'Best Seller' ?></span>
        <?php endif; ?>

        <?php if (!empty($product['is_recommended'])): ?>
            <span style="position:absolute;bottom:10px;left:10px;z-index:5;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="fas fa-star"></i> <?= $lang === 'ar' ? 'موصى به' : 'Recommended' ?></span>
        <?php endif; ?>

        <?php if (!empty($product['has_discount']) && $product['discount_percentage'] > 0): ?>
            <span class="discount-tag">-<?= intval($product['discount_percentage']) ?>%</span>
        <?php endif; ?>

        <?php if (!empty($product['subcategory_en'])): ?>
            <span class="cat-tag">
                <?= $lang === 'ar' ? htmlspecialchars($product['subcategory_ar'] ?? '') : htmlspecialchars($product['subcategory_en']) ?>
            </span>
        <?php endif; ?>

        <?php
            $image_src = get_product_thumbnail(
                trim($product['name_en']),
                $product['image_link'] ?? '',
                __DIR__ . '/..'
            );
        ?>
        <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>">
            <img 
                src="<?= htmlspecialchars($image_src) ?>" 
                alt="<?= htmlspecialchars($product['name_en']) ?>"
                loading="lazy"
                onerror="this.onerror=null; this.src='images/placeholder-cosmetics.svg';"
            >
        </a>
    </div>

    <div class="p-card-body">
        <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>" style="text-decoration:none; color:inherit;">
            <div class="p-card-name"><?= htmlspecialchars($lang === 'ar' ? ($product['name_ar'] ?: $product['name_en']) : $product['name_en']) ?></div>
            <div class="p-card-name-ar"><?= htmlspecialchars($lang === 'ar' ? $product['name_en'] : ($product['name_ar'] ?? '')) ?></div>
        </a>
        
        <?php if (!empty($product['short_description_en']) || !empty($product['short_description_ar'])): ?>
        <div class="p-card-short-desc" style="font-size: 0.85rem; color: #666; font-style: italic; margin-bottom: 0.5rem; line-height: 1.4;">
            <?php if ($lang === 'ar' && !empty($product['short_description_ar'])): ?>
                <?= htmlspecialchars($product['short_description_ar']) ?>
            <?php elseif (!empty($product['short_description_en'])): ?>
                <?= htmlspecialchars($product['short_description_en']) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="p-card-price">
            <?php 
                $display_price = $product['price_jod'];
                if (function_exists('isSupplier') && isSupplier() && !empty($product['supplier_cost']) && $product['supplier_cost'] > 0) {
                    $display_price = $product['supplier_cost'];
                }
            ?>
            <span class="price-now"><?= number_format($display_price, 3) ?> <?= t('currency') ?></span>
            <?php if (!empty($product['has_discount']) && $product['original_price'] > 0): ?>
                <span class="price-was"><?= number_format($product['original_price'], 3) ?></span>
            <?php endif; ?>
        </div>

        <div class="p-card-actions">
            <?php if ($product['stock_quantity'] <= 0): ?>
                <button class="btn-cart" disabled style="opacity:0.6;cursor:not-allowed;background:#999;">
                    <i class="fas fa-ban"></i>
                    <span><?= $lang === 'ar' ? 'نفذت الكمية' : 'Out of Stock' ?></span>
                </button>
            <?php else: ?>
                <button class="btn-cart" onclick="addToCart(<?= (int)$product['id'] ?>, this)">
                    <i class="fas fa-cart-plus"></i>
                    <span><?= t('add_to_cart') ?></span>
                </button>
            <?php endif; ?>
            <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>" class="btn-view" title="<?= t('details') ?>">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
</div>
