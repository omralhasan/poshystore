<?php
/**
 * Product Options Frontend Component
 * Renders Size / Color selectors on the product detail page.
 * Each value can have its own price and image.
 * When customer clicks a value, the displayed price and main image update.
 */

function getProductOptionsForDisplay($product_id, $conn) {
    $options = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order, id");
        if (!$stmt) return [];
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($opt = $result->fetch_assoc()) {
            $val_stmt = $conn->prepare("SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order, id");
            $val_stmt->bind_param('i', $opt['id']);
            $val_stmt->execute();
            $val_result = $val_stmt->get_result();
            $opt['values'] = [];
            while ($val = $val_result->fetch_assoc()) {
                $opt['values'][] = $val;
            }
            $val_stmt->close();
            if (!empty($opt['values'])) {
                $options[] = $opt;
            }
        }
        $stmt->close();
    } catch (\Exception $e) {
        error_log('product_options_display: ' . $e->getMessage());
    }
    return $options;
}

function renderProductOptions($options, $current_lang, $base_price) {
    if (empty($options)) return;
    ?>
    <div class="product-options-section" id="productOptionsSection" style="margin-bottom: 1.5rem;">
        <?php foreach ($options as $opt): ?>
        <div class="product-option-group" data-option-id="<?= $opt['id'] ?>" style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 600; color: var(--purple-color); margin-bottom: 0.5rem; font-size: 0.95rem;">
                <?php if ($opt['option_type'] === 'color'): ?>
                    <i class="fas fa-palette me-1"></i>
                <?php else: ?>
                    <i class="fas fa-ruler me-1"></i>
                <?php endif; ?>
                <?= htmlspecialchars($current_lang === 'ar' ? ($opt['option_name_ar'] ?: $opt['option_name_en']) : $opt['option_name_en']) ?>
                <?php if ($opt['is_required']): ?>
                    <span style="color: #dc3545; font-size: 0.8rem;">*</span>
                <?php endif; ?>
            </label>

            <?php if ($opt['option_type'] === 'color'): ?>
                <!-- Color Swatches -->
                <div class="color-swatches" style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                    <?php foreach ($opt['values'] as $i => $val): ?>
                    <button type="button"
                            class="color-swatch-btn<?= $i === 0 ? ' selected' : '' ?>"
                            data-value-id="<?= $val['id'] ?>"
                            data-price="<?= $val['price_jod'] ?? '' ?>"
                            data-image="<?= htmlspecialchars($val['image'] ?? '') ?>"
                            onclick="selectOption(this, <?= $opt['id'] ?>)"
                            title="<?= htmlspecialchars($current_lang === 'ar' ? ($val['value_ar'] ?: $val['value_en']) : $val['value_en']) ?>"
                            style="width: 38px; height: 38px; border-radius: 50%; border: 3px solid <?= $i === 0 ? 'var(--purple-color)' : '#ddd' ?>; background: <?= htmlspecialchars($val['color_hex'] ?: '#ccc') ?>; cursor: pointer; transition: all 0.2s; position: relative;">
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="selected-color-name" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem;">
                    <?php $first = $opt['values'][0]; ?>
                    <?= htmlspecialchars($current_lang === 'ar' ? ($first['value_ar'] ?: $first['value_en']) : $first['value_en']) ?>
                </div>

            <?php else: ?>
                <!-- Size Pills -->
                <div class="option-pills" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($opt['values'] as $i => $val): ?>
                    <button type="button"
                            class="option-pill-btn<?= $i === 0 ? ' selected' : '' ?>"
                            data-value-id="<?= $val['id'] ?>"
                            data-price="<?= $val['price_jod'] ?? '' ?>"
                            data-image="<?= htmlspecialchars($val['image'] ?? '') ?>"
                            onclick="selectOption(this, <?= $opt['id'] ?>)"
                            style="padding: 0.5rem 1.2rem; border-radius: 25px; border: 2px solid <?= $i === 0 ? 'var(--purple-color)' : '#ddd' ?>; background: <?= $i === 0 ? 'linear-gradient(135deg, var(--purple-color), var(--purple-dark))' : '#fff' ?>; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; color: <?= $i === 0 ? '#fff' : '#333' ?>; font-weight: 500;">
                        <?= htmlspecialchars($current_lang === 'ar' ? ($val['value_ar'] ?: $val['value_en']) : $val['value_en']) ?>
                        <?php if ($val['price_jod'] !== null): ?>
                            <span style="font-size: 0.78rem; opacity: .85; margin-left: 2px;">
                                (<?= number_format($val['price_jod'], 3) ?>)
                            </span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <style>
        .option-pill-btn.selected {
            border-color: var(--purple-color) !important;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(139,92,246,.3);
        }
        .option-pill-btn:hover:not(.selected) {
            border-color: var(--gold-color);
            transform: translateY(-1px);
        }
        .color-swatch-btn.selected {
            border-color: var(--purple-color) !important;
            box-shadow: 0 0 0 3px rgba(139,92,246,.3);
            transform: scale(1.1);
        }
        .color-swatch-btn:hover:not(.selected) {
            transform: scale(1.15);
        }
        .color-swatch-btn.selected::after {
            content: '✓'; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); color: #fff;
            font-size: 0.85rem; text-shadow: 0 1px 3px rgba(0,0,0,0.6);
        }
    </style>

    <script>
    window.selectedOptions = {};
    const basePrice = <?= $base_price ?>;

    // Auto-select first value for each option
    document.querySelectorAll('.product-option-group').forEach(group => {
        const first = group.querySelector('.option-pill-btn, .color-swatch-btn');
        if (first) {
            window.selectedOptions[group.dataset.optionId] = {
                valueId: first.dataset.valueId,
                price: first.dataset.price || null,
                image: first.dataset.image || null
            };
        }
    });
    // Set initial price from first selected option
    updateDisplayedPrice();

    function selectOption(btn, optionId) {
        // Deselect siblings
        btn.parentElement.querySelectorAll('.selected').forEach(b => {
            b.classList.remove('selected');
            if (b.classList.contains('option-pill-btn')) {
                b.style.background = '#fff';
                b.style.color = '#333';
                b.style.borderColor = '#ddd';
            } else {
                b.style.borderColor = '#ddd';
            }
        });

        // Select this
        btn.classList.add('selected');
        if (btn.classList.contains('option-pill-btn')) {
            btn.style.background = 'linear-gradient(135deg, var(--purple-color), var(--purple-dark))';
            btn.style.color = '#fff';
            btn.style.borderColor = 'var(--purple-color)';
        } else {
            btn.style.borderColor = 'var(--purple-color)';
        }

        // Update color name label
        const nameLabel = btn.closest('.product-option-group')?.querySelector('.selected-color-name');
        if (nameLabel) nameLabel.textContent = btn.title;

        // Store selection
        window.selectedOptions[optionId] = {
            valueId: btn.dataset.valueId,
            price: btn.dataset.price || null,
            image: btn.dataset.image || null
        };

        updateDisplayedPrice();
        updateDisplayedImage();
    }

    function updateDisplayedPrice() {
        let price = basePrice;
        Object.values(window.selectedOptions).forEach(sel => {
            if (sel.price && sel.price !== '') price = parseFloat(sel.price);
        });
        // Update all price elements
        document.querySelectorAll('[data-product-price]').forEach(el => {
            el.textContent = price.toFixed(3) + ' JOD';
        });
        // Also update main price text if there is a sale-price or current-price span
        const mainPrice = document.querySelector('.product-price-amount, .detail-current-price');
        if (mainPrice) mainPrice.textContent = price.toFixed(3) + ' JOD';
    }

    function updateDisplayedImage() {
        let img = null;
        Object.values(window.selectedOptions).forEach(sel => {
            if (sel.image && sel.image !== '') img = sel.image;
        });
        if (!img) return;

        // Update main product image (carousel first slide or single image)
        const mainImg = document.querySelector('#productCarousel img, .product-image-large img');
        if (mainImg) {
            mainImg.src = img;
            mainImg.style.objectFit = 'contain';
        }
    }
    </script>
    <?php
}
