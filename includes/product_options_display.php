<?php
/**
 * Product Options Frontend Component
 * Include this in product_detail.php to show variant selectors (size, color)
 * Only renders if the product has options (has_options = 1)
 * 
 * Required: $product_id, $conn, $current_lang, $base_url must be in scope
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
        // Table might not exist yet — degrade gracefully
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
                <div class="color-swatches" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($opt['values'] as $val): ?>
                    <button type="button" 
                            class="color-swatch-btn<?= $val['is_default'] ? ' selected' : '' ?>" 
                            data-value-id="<?= $val['id'] ?>"
                            data-price="<?= $val['price_jod'] ?? '' ?>"
                            data-price-adj="<?= $val['price_adjustment'] ?? 0 ?>"
                            onclick="selectOption(this, <?= $opt['id'] ?>)"
                            title="<?= htmlspecialchars($current_lang === 'ar' ? ($val['value_ar'] ?: $val['value_en']) : $val['value_en']) ?>"
                            style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #ddd; background: <?= htmlspecialchars($val['color_hex'] ?: '#ccc') ?>; cursor: pointer; transition: all 0.2s; position: relative;">
                    </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Dropdown / Pill Buttons -->
                <div class="option-pills" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($opt['values'] as $val): ?>
                    <button type="button" 
                            class="option-pill-btn<?= $val['is_default'] ? ' selected' : '' ?>"
                            data-value-id="<?= $val['id'] ?>"
                            data-price="<?= $val['price_jod'] ?? '' ?>"
                            data-price-adj="<?= $val['price_adjustment'] ?? 0 ?>"
                            onclick="selectOption(this, <?= $opt['id'] ?>)"
                            style="padding: 0.4rem 1rem; border-radius: 20px; border: 2px solid #ddd; background: #fff; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; color: #333;">
                        <?= htmlspecialchars($current_lang === 'ar' ? ($val['value_ar'] ?: $val['value_en']) : $val['value_en']) ?>
                        <?php if ($val['price_jod'] !== null && $val['price_jod'] != $base_price): ?>
                            <span style="font-size: 0.8rem; color: var(--gold-color); font-weight: 600;">
                                (<?= number_format($val['price_jod'], 3) ?> JOD)
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
        .option-pill-btn.selected, .color-swatch-btn.selected {
            border-color: var(--purple-color) !important;
            box-shadow: 0 0 0 2px var(--purple-color);
        }
        .option-pill-btn.selected {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)) !important;
            color: #fff !important;
        }
        .option-pill-btn:hover {
            border-color: var(--gold-color);
            transform: translateY(-1px);
        }
        .color-swatch-btn:hover {
            transform: scale(1.15);
        }
        .color-swatch-btn.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 0.9rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
    </style>
    
    <script>
    // Track selected options
    window.selectedOptions = {};
    const basePrice = <?= $base_price ?>;
    
    // Initialize defaults
    document.querySelectorAll('.option-pill-btn.selected, .color-swatch-btn.selected').forEach(btn => {
        const group = btn.closest('.product-option-group');
        if (group) {
            window.selectedOptions[group.dataset.optionId] = {
                valueId: btn.dataset.valueId,
                price: btn.dataset.price || null,
                priceAdj: parseFloat(btn.dataset.priceAdj) || 0
            };
        }
    });
    
    function selectOption(btn, optionId) {
        // Deselect siblings
        btn.parentElement.querySelectorAll('.selected').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        
        // Store selection
        window.selectedOptions[optionId] = {
            valueId: btn.dataset.valueId,
            price: btn.dataset.price || null,
            priceAdj: parseFloat(btn.dataset.priceAdj) || 0
        };
        
        // Update displayed price if variant has override price
        updateDisplayedPrice();
    }
    
    function updateDisplayedPrice() {
        // Find if any selected option has an override price
        let finalPrice = basePrice;
        let hasOverride = false;
        
        Object.values(window.selectedOptions).forEach(sel => {
            if (sel.price && sel.price !== '') {
                finalPrice = parseFloat(sel.price);
                hasOverride = true;
            } else if (sel.priceAdj) {
                finalPrice += sel.priceAdj;
            }
        });
        
        // Update the price display on the page
        const priceEls = document.querySelectorAll('[data-product-price]');
        priceEls.forEach(el => {
            el.textContent = finalPrice.toFixed(3) + ' JOD';
        });
    }
    </script>
    <?php
}
