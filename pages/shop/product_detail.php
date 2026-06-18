<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_manager.php';
require_once __DIR__ . '/../../includes/meta_catalog.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/../../includes/product_options_display.php';
require_once __DIR__ . '/../../includes/text_formatter.php';

$base_url = BASE_PATH;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . $base_url . '/index.php');
    exit;
}

$product_id = (int)$_GET['id'];
$is_logged_in = isset($_SESSION['user_id']);

$product_result = getProductById($product_id);
if (!$product_result['success']) {
    header('Location: ' . $base_url . '/index.php');
    exit;
}

$product = $product_result['product'];
$meta_catalog_id = get_meta_catalog_id($product);

$detail_display_price = $product['price_jod'];
$detail_display_formatted = $product['price_formatted'];
if (isSupplier() && !empty($product['supplier_cost']) && $product['supplier_cost'] > 0) {
    $detail_display_price = $product['supplier_cost'];
    $detail_display_formatted = formatJOD($product['supplier_cost']);
}

$meta_product_currency = 'USD';

$product_tags = getProductTags($product_id);

$reviews_result = getProductReviews($product_id, 5);
$reviews = $reviews_result['reviews'] ?? [];
$average_rating = $reviews_result['average_rating'] ?? 0;
$review_count = $reviews_result['count'] ?? 0;

$user_review = null;
if ($is_logged_in) {
    $user_review_result = getUserProductReview($product_id, $_SESSION['user_id']);
    if ($user_review_result['has_review']) {
        $user_review = $user_review_result['review'];
    }
}

$cart_count = 0;
$product_in_cart_quantity = 0;
if ($is_logged_in) {
    $cart_count = getCartCount($_SESSION['user_id']);
    $cart_check_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($cart_check_sql);
    $stmt->bind_param('ii', $_SESSION['user_id'], $product_id);
    $stmt->execute();
    $cart_check_result = $stmt->get_result();
    if ($cart_check_row = $cart_check_result->fetch_assoc()) {
        $product_in_cart_quantity = (int)$cart_check_row['quantity'];
    }
    $stmt->close();
}
?><!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= htmlspecialchars(strip_tags(mb_substr(($current_lang === 'ar' && !empty($product['short_description_ar']) ? $product['short_description_ar'] : (!empty($product['short_description_en']) ? $product['short_description_en'] : $product['name_en'] . ' - Premium Korean beauty product at Poshy Store')), 0, 155))) ?>">
<title><?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?> | Poshy Store</title>
<?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
<?php renderProductSchema($product, $average_rating, $review_count); ?>
<?php if (!empty($reviews)): foreach ($reviews as $rv): renderReviewSchema($rv, $product); endforeach; endif; ?>
<?php require_once __DIR__ . '/../../includes/meta_pixel.php'; ?>
<script>
window.metaProductContext = {
    id: '<?= (int)$product['id'] ?>',
    catalogId: <?php echo json_encode($meta_catalog_id); ?>,
    price: <?php echo json_encode((float)$detail_display_price); ?>,
    currency: <?php echo json_encode($meta_product_currency); ?>
};
if (window.metaTrackCatalogEvent) {
    var catalogId = window.metaProductContext.catalogId || window.metaProductContext.id;
    window.metaTrackCatalogEvent('ViewContent', [catalogId], {
        value: window.metaProductContext.price,
        currency: window.metaProductContext.currency
    });
}
</script>
<style>
:root{--pd-gap:2.5rem;--pd-radius:16px;--pd-transition:all .3s cubic-bezier(.4,0,.2,1)}
.pd-container{max-width:1260px;margin:0 auto;padding:0 1rem}
.pd-breadcrumb{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;padding:.75rem 0;font-size:.9rem;color:#666;list-style:none;margin:0}
.pd-breadcrumb li+li::before{content:"›";margin:0 .5rem;color:#bbb}
.pd-breadcrumb a{color:var(--gold-color);text-decoration:none;transition:var(--pd-transition)}
.pd-breadcrumb a:hover{color:var(--royal-gold)}
.pd-breadcrumb .active{color:var(--deep-purple);font-weight:500}

.pd-card{background:#fff;border-radius:var(--pd-radius);box-shadow:0 2px 20px rgba(0,0,0,.06);overflow:hidden}
.pd-grid{display:grid;grid-template-columns:1fr 1fr;gap:var(--pd-gap);padding:var(--pd-gap)}

/* Gallery */
.pd-gallery{position:relative}
.pd-main-img{position:relative;width:100%;aspect-ratio:1/1;background:#f8f8f8;border-radius:var(--pd-radius);overflow:hidden;cursor:zoom-in}
.pd-main-img .pd-slide{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .4s ease;padding:1.5rem}
.pd-main-img .pd-slide.active{opacity:1}
.pd-main-img img{max-width:100%;max-height:100%;object-fit:contain;transition:transform .3s ease}
.pd-main-img:hover img{transform:scale(1.05)}
.pd-zoom-hint{position:absolute;top:12px;inset-inline-end:12px;background:rgba(0,0,0,.5);color:#fff;padding:4px 10px;border-radius:20px;font-size:.75rem;opacity:0;transition:var(--pd-transition);pointer-events:none}
.pd-main-img:hover .pd-zoom-hint{opacity:1}
.pd-indicators{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;z-index:2}
.pd-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.5);cursor:pointer;transition:var(--pd-transition)}
.pd-dot.active{background:#fff;transform:scale(1.3)}
.pd-thumbs{display:flex;gap:8px;margin-top:12px;overflow-x:auto;padding:4px 0;scrollbar-width:thin;scrollbar-color:var(--gold-color) transparent}
.pd-thumb{flex:0 0 72px;width:72px;height:72px;border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:var(--pd-transition);background:#f5f5f5;display:flex;align-items:center;justify-content:center}
.pd-thumb:hover{border-color:var(--gold-color);transform:translateY(-2px)}
.pd-thumb.active{border-color:var(--purple-color);box-shadow:0 4px 15px rgba(45,19,44,.25)}
.pd-thumb img{width:100%;height:100%;object-fit:cover}
.pd-thumb .pd-thumb-emoji{font-size:1.6rem}

/* Info */
.pd-brand{font-size:.85rem;font-weight:600;color:var(--gold-color);text-transform:uppercase;letter-spacing:1px;margin-bottom:.25rem}
.pd-name{font-size:1.75rem;font-weight:700;color:#1a1a1a;margin-bottom:.75rem;line-height:1.3}
.pd-name[dir=rtl]{font-family:'Tajawal',sans-serif}
.pd-rating{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.pd-stars{color:#ffc107;font-size:1.15rem;letter-spacing:2px}
.pd-rating-text{color:#666;font-size:.9rem}
.pd-price{display:flex;align-items:baseline;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap}
.pd-price-current{font-size:2rem;font-weight:700;color:var(--purple-color)}
.pd-price-original{font-size:1.3rem;color:#999;text-decoration:line-through}
.pd-price-badge{background:#e74c3c;color:#fff;padding:4px 10px;border-radius:20px;font-size:.85rem;font-weight:700}
.pd-savings{font-size:.9rem;color:var(--gold-color);font-weight:600;width:100%}
.pd-stock{margin-bottom:1.25rem}
.pd-stock-badge{display:inline-flex;align-items:center;gap:6px;padding:.45rem 1rem;border-radius:20px;font-size:.9rem;font-weight:500}
.pd-stock-badge.in{background:#e8f5e9;color:#2e7d32}
.pd-stock-badge.out{background:#fce4ec;color:#c62828}
.pd-desc{color:#555;font-size:.95rem;line-height:1.7;margin-bottom:1.25rem;padding:1rem 1.25rem;background:#fafafa;border-radius:10px;border-inline-start:3px solid var(--gold-color)}
.pd-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:1.25rem}
.pd-tag{display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#f0e6f6,#e8d5f5);color:var(--purple-color);padding:.3rem .8rem;border-radius:20px;font-size:.8rem;text-decoration:none;transition:var(--pd-transition);border:1px solid #d4b5e8}
.pd-tag:hover{background:var(--purple-color);color:#fff;border-color:var(--purple-color)}

/* Options */
.pd-options{margin-bottom:1rem}
.pd-opt-group{margin-bottom:1rem}
.pd-opt-label{display:block;font-weight:600;color:var(--purple-color);font-size:.9rem;margin-bottom:.5rem}
.pd-opt-label .pd-req{color:#dc3545;font-size:.8rem}
.pd-swatches{display:flex;gap:8px;flex-wrap:wrap}
.pd-swatch{width:36px;height:36px;border-radius:50%;border:3px solid #ddd;cursor:pointer;transition:var(--pd-transition)}
.pd-swatch:hover{transform:scale(1.15)}
.pd-swatch.selected{border-color:var(--purple-color);box-shadow:0 0 0 3px rgba(139,92,246,.3);transform:scale(1.1)}
.pd-swatch.selected::after{content:'✓';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:.75rem;text-shadow:0 1px 3px rgba(0,0,0,.6)}
.pd-pills{display:flex;gap:8px;flex-wrap:wrap}
.pd-pill{padding:.45rem 1.1rem;border-radius:25px;border:2px solid #ddd;background:#fff;cursor:pointer;font-size:.85rem;font-weight:500;transition:var(--pd-transition);color:#333}
.pd-pill:hover{border-color:var(--gold-color);transform:translateY(-1px)}
.pd-pill.selected{border-color:var(--purple-color);background:linear-gradient(135deg,var(--purple-color),var(--purple-dark));color:#fff;box-shadow:0 2px 8px rgba(139,92,246,.3)}
.pd-pill .pd-pill-price{font-size:.75rem;opacity:.85}
.pd-sel-name{font-size:.8rem;color:#888;margin-top:4px}

/* Trust badges */
.pd-trust{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:1.25rem}
.pd-trust-badge{display:flex;align-items:center;gap:6px;font-size:.8rem;color:#555;padding:.4rem .8rem;background:#f8f8f8;border-radius:8px;border:1px solid #eee}
.pd-trust-badge i{color:var(--gold-color);font-size:.9rem}

/* CTAs */
.pd-actions{display:flex;flex-direction:column;gap:10px;margin-bottom:1.25rem}
.pd-actions-row{display:flex;gap:10px}
.pd-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:.85rem 1.5rem;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:var(--pd-transition);text-decoration:none;text-align:center}
.pd-btn:disabled{opacity:.5;cursor:not-allowed}
.pd-btn-primary{background:#E02B7D;color:#fff;border:2px solid #E02B7D}
.pd-btn-primary:hover:not(:disabled){background:#c01f6a;border-color:#c01f6a;transform:translateY(-2px);box-shadow:0 6px 20px rgba(224,43,125,.4);color:#fff}
.pd-btn-secondary{background:linear-gradient(135deg,var(--purple-color),var(--purple-dark));color:#fff}
.pd-btn-secondary:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 6px 20px rgba(72,54,112,.3);color:#fff}
.pd-btn-outline{background:transparent;color:var(--purple-color);border:2px solid var(--purple-color)}
.pd-btn-outline:hover:not(:disabled){background:var(--purple-color);color:#fff}
.pd-qty{display:flex;align-items:center;gap:10px;background:#fff;border:2px solid var(--gold-color);border-radius:12px;padding:.4rem .8rem}
.pd-qty-btn{width:40px;height:40px;border:2px solid #333;background:transparent;color:#333;border-radius:50%;font-size:1.2rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:var(--pd-transition)}
.pd-qty-btn:hover{background:rgba(0,0,0,.08);transform:scale(1.1)}
.pd-qty-val{flex:1;text-align:center;font-size:1.4rem;font-weight:700;color:var(--purple-color);min-width:40px}

/* Accordion */
.pd-accordion{margin-top:2rem}
.pd-acc-item{border-bottom:1px solid #eee}
.pd-acc-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 0;cursor:pointer;font-weight:600;font-size:1rem;color:#1a1a1a;transition:var(--pd-transition);user-select:none}
.pd-acc-header:hover{color:var(--purple-color)}
.pd-acc-header i{color:var(--gold-color);margin-inline-end:8px;width:1.2rem;text-align:center}
.pd-acc-icon{transition:transform .3s ease;font-size:.8rem;color:#999}
.pd-acc-item.open .pd-acc-icon{transform:rotate(180deg)}
.pd-acc-body{padding:0 0 1.25rem;line-height:1.8;color:#555;font-size:.95rem;display:none}
.pd-acc-item.open .pd-acc-body{display:block}
.pd-acc-body ul,.pd-acc-body ol{padding-inline-start:1.25rem}
.pd-acc-body li{margin-bottom:.5rem}
.pd-acc-body h1,.pd-acc-body h2,.pd-acc-body h3,.pd-acc-body h4{color:var(--purple-color);margin:1rem 0 .5rem}
.pd-acc-body h1{font-size:1.5rem}
.pd-acc-body h2{font-size:1.3rem}
.pd-acc-body :first-child{margin-top:0}

/* Reviews */
.pd-reviews{margin-top:2rem;padding-top:2rem;border-top:1px solid #eee}
.pd-rev-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.pd-rev-title{font-size:1.3rem;font-weight:700;color:#1a1a1a}
.pd-rev-summary{display:flex;align-items:center;gap:.75rem}
.pd-rev-avg{font-size:1.8rem;font-weight:700;color:var(--purple-color)}
.pd-rev-form{padding:1.5rem;background:#fafafa;border-radius:12px;margin-bottom:1.5rem;border:1px solid #eee}
.pd-rev-form h4{margin-bottom:1rem;font-size:1.05rem;color:var(--purple-color)}
.pd-rev-form textarea{width:100%;padding:.8rem;border:2px solid #e0e0e0;border-radius:8px;font-family:inherit;font-size:.95rem;min-height:100px;resize:vertical;transition:var(--pd-transition)}
.pd-rev-form textarea:focus{outline:none;border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(201,168,106,.15)}
.pd-rev-form .pd-rev-stars{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px;margin-bottom:.75rem}
.pd-rev-form .pd-rev-stars input{display:none}
.pd-rev-form .pd-rev-stars label{font-size:1.6rem;cursor:pointer;color:#ddd;transition:color .15s}
.pd-rev-form .pd-rev-stars input:checked~label,.pd-rev-form .pd-rev-stars label:hover,.pd-rev-form .pd-rev-stars label:hover~label{color:#ffc107}
.pd-rev-list{display:flex;flex-direction:column;gap:1rem}
.pd-rev-card{padding:1.25rem;background:#fff;border-radius:10px;border:1px solid #eee;transition:var(--pd-transition)}
.pd-rev-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.04)}
.pd-rev-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem}
.pd-rev-author{font-weight:600;color:var(--purple-color);font-size:.95rem}
.pd-rev-date{color:#999;font-size:.8rem}
.pd-rev-stars-display{color:#ffc107;font-size:1rem;margin-bottom:.5rem;letter-spacing:2px}
.pd-rev-text{color:#555;line-height:1.6;font-size:.9rem}
.pd-rev-photos{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.pd-rev-photo{width:64px;height:64px;border-radius:8px;object-fit:cover;cursor:pointer;border:1px solid #eee;transition:var(--pd-transition)}
.pd-rev-photo:hover{transform:scale(1.1);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.pd-rev-form .pd-photo-upload{display:flex;align-items:center;gap:8px;margin-top:.75rem;flex-wrap:wrap}
.pd-rev-form .pd-photo-upload input[type=file]{font-size:.85rem}
.pd-no-rev{text-align:center;padding:2rem;color:#999;font-size:1rem}
.pd-rev-login{text-align:center;padding:1.25rem;color:#666}
.pd-rev-login a{color:var(--gold-color);font-weight:700;text-decoration:none}
.pd-rev-login a:hover{text-decoration:underline}

/* Lightbox */
.pd-lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:10000;align-items:center;justify-content:center;cursor:zoom-out}
.pd-lb.active{display:flex}
.pd-lb-img{max-width:90vw;max-height:85vh;object-fit:contain;border-radius:10px;box-shadow:0 15px 60px rgba(0,0,0,.5);animation:pdLbIn .3s ease}
@keyframes pdLbIn{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
.pd-lb-close{position:absolute;top:20px;inset-inline-end:25px;color:#fff;font-size:2rem;cursor:pointer;z-index:10001;width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,.1);transition:var(--pd-transition)}
.pd-lb-close:hover{background:rgba(255,255,255,.25)}
.pd-lb-nav{position:absolute;top:50%;transform:translateY(-50%);color:#fff;font-size:1.5rem;cursor:pointer;width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,.1);transition:var(--pd-transition)}
.pd-lb-nav:hover{background:rgba(255,255,255,.25)}
.pd-lb-prev{inset-inline-start:20px}
.pd-lb-next{inset-inline-end:20px}
.pd-lb-counter{position:absolute;bottom:25px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.7);font-size:.9rem}
.pd-lb img{cursor:default}

/* Cart Drawer — slide-in right on desktop, bottom sheet on mobile */
.pd-drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;opacity:0;transition:opacity .3s ease;backdrop-filter:blur(4px)}
.pd-drawer-overlay.active{display:block;opacity:1}
.pd-drawer{position:fixed;top:0;inset-inline-end:0;width:700px;max-width:94vw;height:100%;background:#faf7f2;z-index:10000;box-shadow:-8px 0 40px rgba(0,0,0,.2);transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;overflow:hidden;box-sizing:border-box}
[dir=rtl] .pd-drawer{transform:translateX(-100%)}
.pd-drawer.active{transform:translateX(0)}
.pd-drawer-hdr{flex-shrink:0;background:linear-gradient(135deg,var(--purple-color),var(--purple-dark));padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:8px}
.pd-drawer-hdr h3{color:#fff;font-size:1rem;margin:0;display:flex;align-items:center;gap:6px;font-weight:600}
.pd-drawer-hdr h3 i{font-size:1.1rem}
.pd-drawer-close{background:rgba(255,255,255,.2);border:none;color:#fff;font-size:1.2rem;width:30px;height:30px;border-radius:50%;cursor:pointer;transition:var(--pd-transition);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.pd-drawer-close:hover{background:rgba(255,255,255,.3);transform:rotate(90deg)}
.pd-drawer-body{flex:1;overflow-y:auto;padding:1rem 1.5rem}
.pd-drawer-body::-webkit-scrollbar{width:4px}
.pd-drawer-body::-webkit-scrollbar-thumb{background:var(--gold-color);border-radius:2px}

/* Added product summary — compact row */
.pd-drawer-product{display:flex;gap:10px;align-items:center;background:#fff;border-radius:10px;padding:.65rem .85rem;margin-bottom:.85rem;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.pd-drawer-product-img{width:52px;height:52px;border-radius:8px;background:linear-gradient(135deg,var(--purple-color),var(--purple-dark));display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;overflow:hidden}
.pd-drawer-product-img img{width:100%;height:100%;object-fit:contain}
.pd-drawer-product-info{flex:1;min-width:0}
.pd-drawer-product-name{font-weight:700;color:var(--purple-color);font-size:.85rem;margin-bottom:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pd-drawer-product-price{color:var(--gold-color);font-weight:700;font-size:.95rem}
.pd-drawer-product-qty{color:#999;font-size:.75rem}

/* Action buttons row */
.pd-drawer-actions{display:flex;gap:8px;margin-bottom:1rem}
.pd-drawer-btn{flex:1;padding:.65rem;border:none;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;transition:var(--pd-transition);text-decoration:none;text-align:center;display:block}
.pd-drawer-btn-cart{background:linear-gradient(135deg,var(--purple-color),var(--purple-dark));color:#fff}
.pd-drawer-btn-cart:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(72,54,112,.25);color:#fff}
.pd-drawer-btn-continue{background:#fff;color:var(--gold-color);border:2px solid var(--gold-color)}
.pd-drawer-btn-continue:hover{background:var(--gold-color);color:#fff}

/* Recommended section — compact, no wasted space */
.pd-drawer-rec{padding-top:.65rem;border-top:1px solid #e8e0d8}
.pd-drawer-rec-title{font-weight:700;color:var(--purple-color);font-size:.9rem;margin-bottom:.7rem;display:flex;align-items:center;gap:5px}
.pd-drawer-rec-title i{color:var(--gold-color)}
.pd-drawer-rec-grid{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin;scrollbar-color:var(--gold-color) transparent}
.pd-drawer-rec-grid::-webkit-scrollbar{height:2px}
.pd-drawer-rec-grid::-webkit-scrollbar-thumb{background:var(--gold-color);border-radius:2px}

/* Rec product card — compact, robust flex column, no overflow leaks */
.pd-drec-card{flex:0 0 170px;background:#fff;border-radius:10px;padding:6px 6px 8px;box-shadow:0 1px 4px rgba(72,54,112,.06);transition:var(--pd-transition);text-align:center;display:flex;flex-direction:column;overflow:hidden;box-sizing:border-box}
.pd-drec-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(72,54,112,.1)}
.pd-drec-img{width:100%;height:110px;border-radius:6px;background:#f5edf8;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.pd-drec-img img{width:100%;height:100%;object-fit:contain;padding:6px;display:block}
.pd-drec-name{font-size:.72rem;font-weight:600;color:var(--purple-color);margin:5px 4px 2px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0;box-sizing:border-box;max-width:100%}
.pd-drec-price-line{display:flex;align-items:center;justify-content:center;gap:3px;margin:0 4px 3px;flex-shrink:0;box-sizing:border-box}
.pd-drec-price{font-size:.78rem;font-weight:700;color:var(--gold-color)}
.pd-drec-orig{font-size:.6rem;color:#aaa;text-decoration:line-through}

/* Pink‑rose Add‑to‑Cart button — impossible to miss, sits at bottom of card */
.pd-drec-btn{width:100%;padding:6px 8px;border:2px solid #d6336c;border-radius:8px;font-size:.7rem;font-weight:700;cursor:pointer;transition:all .2s ease;background:#d6336c;color:#fff!important;display:flex!important;align-items:center;justify-content:center;gap:4px;margin-top:auto;flex-shrink:0;box-sizing:border-box;text-shadow:0 1px 2px rgba(0,0,0,.15);min-height:30px;line-height:1;visibility:visible!important;opacity:1!important}
.pd-drec-btn:hover{background:#c2185b;border-color:#c2185b;transform:translateY(-1px);box-shadow:0 4px 14px rgba(214,51,108,.45)}
.pd-drec-btn:active{transform:scale(.97)}
.pd-drec-btn:disabled{opacity:.5!important;cursor:not-allowed;transform:none;box-shadow:none}

/* Mobile: bottom sheet, larger height, same compact cards */
@media(max-width:768px){
.pd-drawer{width:100%;max-width:100%;height:82vh;top:auto;bottom:0;inset-inline:0;border-radius:18px 18px 0 0;transform:translateY(100%)}
[dir=rtl] .pd-drawer{transform:translateY(100%)}
.pd-drawer.active{transform:translateY(0)}
.pd-drawer-hdr{border-radius:18px 18px 0 0;padding:.85rem 1.25rem}
.pd-drawer-hdr h3{font-size:.9rem}
.pd-drawer-body{padding:.75rem 1rem}
.pd-drawer-actions{flex-direction:column;gap:6px}
.pd-drawer-rec-grid{gap:6px}
.pd-drec-card{flex:0 0 145px}
}

/* Alert */
.pd-alert{position:fixed;top:80px;inset-inline-end:20px;padding:.8rem 1.25rem;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,.15);z-index:1000;animation:pdSlideIn .3s ease-out;font-size:.9rem}
@keyframes pdSlideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
[dir=rtl] .pd-alert{animation-name:pdSlideInRtl}
@keyframes pdSlideInRtl{from{transform:translateX(-100px);opacity:0}to{transform:translateX(0);opacity:1}}
.pd-alert.success{background:linear-gradient(135deg,rgba(201,168,106,.15),rgba(201,168,106,.05));color:#155724;border-inline-start:4px solid var(--gold-color);border:1px solid var(--gold-color)}
.pd-alert.error{background:linear-gradient(135deg,rgba(220,53,69,.15),rgba(220,53,69,.05));color:#721c24;border-inline-start:4px solid #dc3545;border:1px solid #dc3545}

/* Video */
.pd-video-wrap{border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.pd-video-wrap video{width:100%;max-height:500px;display:block;background:#000}
.pd-video-embed{position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.pd-video-embed iframe{position:absolute;top:0;inset-inline-start:0;width:100%;height:100%;border:0}
.pd-no-video{text-align:center;padding:2.5rem;background:linear-gradient(135deg,#f8f9fa,#e9ecef);border-radius:10px;border:2px dashed #dee2e6}
.pd-no-video i{font-size:3rem;color:#adb5bd;margin-bottom:.5rem}
.pd-no-video p{color:#6c757d;margin:0}

/* Sticky Mini Player */
.pd-mini-player{position:fixed;bottom:20px;inset-inline-end:20px;width:300px;z-index:9999;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.3);transition:var(--pd-transition);opacity:0;transform:translateY(20px) scale(.9);pointer-events:none;background:#000}
.pd-mini-player.visible{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.pd-mini-player video{width:100%;display:block}
.pd-mini-close{position:absolute;top:6px;inset-inline-end:6px;width:26px;height:26px;background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;z-index:10;transition:background .2s}
.pd-mini-close:hover{background:rgba(200,0,0,.8)}
.pd-mini-back{position:absolute;bottom:6px;inset-inline-start:6px;background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:10px;padding:3px 8px;z-index:10;transition:background .2s}
.pd-mini-back:hover{background:var(--purple-color)}

/* Responsive */
@media(max-width:992px){
.pd-grid{grid-template-columns:1fr;gap:1.5rem;padding:1.5rem}
.pd-main-img{aspect-ratio:4/5}
}
@media(max-width:576px){
.pd-name{font-size:1.35rem}
.pd-price-current{font-size:1.5rem}
.pd-actions-row{flex-direction:column}
.pd-thumb{flex:0 0 56px;width:56px;height:56px}
.pd-rev-header{flex-direction:column;align-items:flex-start}
.pd-drawer{height:82vh;border-radius:16px 16px 0 0}
.pd-drec-card{flex:0 0 130px}
.pd-drawer-actions{flex-direction:column}
.pd-mini-player{width:220px;bottom:12px;inset-inline-end:12px}
.pd-alert{inset-inline-end:10px;left:10px}
.pd-lb-prev{inset-inline-start:10px}
.pd-lb-next{inset-inline-end:10px}
}
</style>
</head>
<body>
<?php renderGTMNoScript(); ?>
<?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>

<div class="pd-container py-4">
    <nav aria-label="breadcrumb">
        <ol class="pd-breadcrumb">
            <li><a href="<?= $base_url ?>/"><i class="fas fa-home me-1"></i><?= $current_lang === 'ar' ? 'الرئيسية' : 'Home' ?></a></li>
            <?php if (!empty($product['category_en'])): ?>
            <li><a href="<?= $base_url ?>/index.php#products"><?= $current_lang === 'ar' ? htmlspecialchars($product['category_ar']) : htmlspecialchars($product['category_en']) ?></a></li>
            <?php endif; ?>
            <?php if (!empty($product['subcategory_en'])): ?>
            <li><a href="<?= $base_url ?>/index.php?subcategory=<?= $product['subcategory_id'] ?>#products"><?= $current_lang === 'ar' ? htmlspecialchars($product['subcategory_ar']) : htmlspecialchars($product['subcategory_en']) ?></a></li>
            <?php endif; ?>
            <li class="active"><?= htmlspecialchars(mb_strimwidth($product['name_en'], 0, 40, '...')) ?></li>
        </ol>
    </nav>

    <div class="pd-card">
        <div class="pd-grid">
            <!-- Gallery -->
            <div class="pd-gallery">
                <div class="pd-main-img" id="pdMainImg">
                    <?php
                    require_once __DIR__ . '/../../includes/product_image_helper.php';
                    $images_dir = __DIR__ . '/../../images';
                    $gallery_images = get_product_gallery_images(
                        trim($product['name_en']),
                        $product['image_link'] ?? '',
                        $images_dir,
                        $base_url . '/'
                    );
                    $cache_bust = '?v=' . time();
                    if (!empty($gallery_images)) {
                        foreach ($gallery_images as $index => $image_path) {
                            $activeClass = $index === 0 ? ' active' : '';
                            $loadingAttr = $index === 0 ? "loading='eager' fetchpriority='high' decoding='sync'" : "loading='lazy' fetchpriority='low' decoding='async'";
                            echo "<div class='pd-slide$activeClass'>";
                            echo "<img src='" . htmlspecialchars($image_path) . $cache_bust . "' alt='" . htmlspecialchars($product['name_en']) . "' " . $loadingAttr . " onerror=\"this.onerror=null; this.src='" . $base_url . "/images/placeholder-cosmetics.svg';\">";
                            echo "</div>";
                        }
                    } else {
                        $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                        $gradients = [
                            'linear-gradient(135deg, var(--purple-color), var(--purple-dark))',
                            'linear-gradient(135deg, var(--gold-color), var(--gold-light))',
                            'linear-gradient(135deg, #f093fb, #f5576c)',
                            'linear-gradient(135deg, #4facfe, #00f2fe)',
                            'linear-gradient(135deg, #43e97b, #38f9d7)'
                        ];
                        for ($i = 0; $i < 5; $i++) {
                            $activeClass = $i === 0 ? ' active' : '';
                            $icon = $icons[($product['id'] + $i) % count($icons)];
                            $gradient = $gradients[$i % count($gradients)];
                            echo "<div class='pd-slide$activeClass' style='background: $gradient; font-size: 5rem;'>$icon</div>";
                        }
                        $gallery_images = range(1, 5);
                    }
                    $total_slides = is_array($gallery_images) ? count($gallery_images) : 0;
                    ?>
                    <div class="pd-indicators">
                        <?php for ($i = 0; $i < $total_slides; $i++): ?>
                            <div class="pd-dot<?= $i === 0 ? ' active' : '' ?>" onclick="pdGoTo(<?= $i ?>)"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="pd-zoom-hint"><i class="fas fa-search-plus"></i> <?= $current_lang === 'ar' ? 'تكبير' : 'Zoom' ?></div>
                </div>
                <div class="pd-thumbs" id="pdThumbs">
                    <?php if (!empty($gallery_images) && !is_array($gallery_images[0] ?? null) && !is_numeric($gallery_images[0] ?? null)):
                        foreach ($gallery_images as $index => $image_path): ?>
                            <div class="pd-thumb<?= $index === 0 ? ' active' : '' ?>" onclick="pdGoTo(<?= $index ?>)">
                                <img src="<?= htmlspecialchars($image_path) . $cache_bust ?>" alt="<?= htmlspecialchars($product['name_en']) ?> - <?= $index + 1 ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= $base_url ?>/images/placeholder-cosmetics.svg';">
                            </div>
                        <?php endforeach;
                    else:
                        $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                        for ($i = 0; $i < $total_slides; $i++):
                            $icon = $icons[($product['id'] + $i) % count($icons)]; ?>
                            <div class="pd-thumb<?= $i === 0 ? ' active' : '' ?>" onclick="pdGoTo(<?= $i ?>)">
                                <span class="pd-thumb-emoji"><?= $icon ?></span>
                            </div>
                    <?php endfor; endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="pd-info">
                <?php if (!empty($product['brand_en'])): ?>
                    <div class="pd-brand"><?= htmlspecialchars($current_lang === 'ar' ? ($product['brand_ar'] ?: $product['brand_en']) : $product['brand_en']) ?></div>
                <?php endif; ?>
                <h1 class="pd-name" dir="<?= $current_lang === 'ar' ? 'rtl' : 'ltr' ?>"><?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?></h1>

                <?php if ($review_count > 0): ?>
                <div class="pd-rating">
                    <div class="pd-stars"><?php for ($i = 1; $i <= 5; $i++) echo $i <= $average_rating ? '★' : '☆'; ?></div>
                    <span class="pd-rating-text"><?= $average_rating ?> <?= t('out_of_5') ?> (<?= $review_count ?> <?= t('reviews_count') ?>)</span>
                </div>
                <?php endif; ?>

                <div class="pd-price">
                    <?php if ($product['has_discount'] && $product['original_price'] > 0 && !isSupplier()): ?>
                        <span class="pd-price-badge"><i class="fas fa-tag me-1"></i>-<?= number_format($product['discount_percentage'], 0) ?>%</span>
                        <span class="pd-price-original"><?= formatJOD($product['original_price']) ?></span>
                        <span class="pd-price-current" data-product-price><?= $detail_display_formatted ?></span>
                        <span class="pd-savings">💰 <?= t('you_save') ?> <?= formatJOD($product['original_price'] - $detail_display_price) ?>!</span>
                    <?php else: ?>
                        <span class="pd-price-current" data-product-price><?= $detail_display_formatted ?></span>
                    <?php endif; ?>
                </div>

                <div class="pd-stock">
                    <?php if ($product['in_stock']): ?>
                        <span class="pd-stock-badge in"><i class="fas fa-check-circle"></i> <?= t('in_stock') ?><?php if (isAdmin()): ?>: <?= $product['stock_quantity'] ?> <?= t('units_available') ?><?php endif; ?></span>
                    <?php else: ?>
                        <span class="pd-stock-badge out"><i class="fas fa-times-circle"></i> <?= t('out_of_stock') ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['short_description_en']) || !empty($product['short_description_ar'])): ?>
                <div class="pd-desc"><?= htmlspecialchars($current_lang === 'ar' && !empty($product['short_description_ar']) ? $product['short_description_ar'] : ($product['short_description_en'] ?? '')) ?></div>
                <?php endif; ?>

                <?php if (!empty($product_tags)): ?>
                <div class="pd-tags">
                    <?php foreach ($product_tags as $ptag): ?>
                        <a href="<?= $base_url ?>/index.php?tag=<?= urlencode($ptag['slug']) ?>" class="pd-tag"><i class="fas fa-tag"></i><?= htmlspecialchars($current_lang === 'ar' ? ($ptag['name_ar'] ?: $ptag['name_en']) : $ptag['name_en']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php
                $product_options = getProductOptionsForDisplay($product_id, $conn);
                if (!empty($product_options)) {
                    renderProductOptions($product_options, $current_lang, $product['price_jod']);
                }
                ?>

                <!-- Trust badges -->
                <div class="pd-trust">
                    <span class="pd-trust-badge"><i class="fas fa-truck"></i> <?= t('free_shipping_over_30') ?></span>
                    <span class="pd-trust-badge"><i class="fas fa-undo"></i> <?= t('easy_returns') ?></span>
                    <span class="pd-trust-badge"><i class="fas fa-shield-alt"></i> <?= t('secure_checkout') ?></span>
                    <span class="pd-trust-badge"><i class="fas fa-clock"></i> <?= t('same_day_shipping') ?></span>
                </div>

                <!-- Actions -->
                <div class="pd-actions">
                    <div class="pd-actions-row">
                        <?php if ($is_logged_in): ?>
                            <?php if ($product_in_cart_quantity > 0): ?>
                                <div class="pd-qty flex-grow-1" id="pdQtyControls">
                                    <button class="pd-qty-btn" onclick="pdUpdateQty(<?= $product['id'] ?>, 'decrease')"><i class="fas fa-minus"></i></button>
                                    <div>
                                        <div style="font-size:.75rem;color:#888;text-align:center"><?= t('in_cart_label') ?></div>
                                        <div class="pd-qty-val" id="pdCartQty"><?= $product_in_cart_quantity ?></div>
                                    </div>
                                    <button class="pd-qty-btn" onclick="pdUpdateQty(<?= $product['id'] ?>, 'increase')"><i class="fas fa-plus"></i></button>
                                </div>
                            <?php else: ?>
                                <button id="pdAddBtn" class="pd-btn pd-btn-primary flex-grow-1" onclick="pdAddToCart(<?= $product['id'] ?>)" <?= !$product['in_stock'] ? 'disabled' : '' ?>>
                                    <i class="fas fa-shopping-cart"></i> <?= $product['in_stock'] ? t('add_to_cart') : t('out_of_stock') ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button id="pdAddBtn" class="pd-btn pd-btn-primary flex-grow-1" onclick="pdGuestAdd(<?= $product['id'] ?>)" <?= !$product['in_stock'] ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i> <?= $product['in_stock'] ? t('add_to_cart') : t('out_of_stock') ?>
                            </button>
                        <?php endif; ?>
                        <button class="pd-btn pd-btn-secondary" onclick="pdBuyNow(<?= $product['id'] ?>)">
                            <i class="fas fa-bolt"></i>                             <?= t('buy_now') ?>
                        </button>
                    </div>
                </div>

                <!-- Accordion Info Tabs -->
                <div class="pd-accordion">
                    <?php if (!empty($product['video_review_url']) || true): ?>
                    <div class="pd-acc-item open">
                        <div class="pd-acc-header" onclick="pdToggleAcc(this)">
                            <span><i class="fas fa-play-circle"></i><?= t('see_in_action') ?></span>
                            <span class="pd-acc-icon"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="pd-acc-body">
                            <?php if (!empty($product['video_review_url'])): ?>
                                <?php $video_src = $product['video_review_url']; $is_local = (strpos($video_src, 'uploads/') === 0 || strpos($video_src, '/uploads/') === 0); ?>
                                <?php if ($is_local): ?>
                                    <div class="pd-video-wrap"><video id="pdVideo" controls playsinline preload="metadata" poster=""><source src="<?= $base_url . '/' . htmlspecialchars($video_src) ?>" type="video/mp4"><?= $current_lang === 'ar' ? 'متصفحك لا يدعم الفيديو' : 'Your browser does not support video.' ?></video></div>
                                <?php else: ?>
                                    <div class="pd-video-embed"><iframe src="<?= htmlspecialchars($video_src) ?>" allowfullscreen loading="lazy"></iframe></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="pd-no-video"><i class="fas fa-play-circle"></i><p><?= $current_lang === 'ar' ? 'سيتم إضافة الفيديو قريباً' : 'Video coming soon' ?></p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pd-acc-item">
                        <div class="pd-acc-header" onclick="pdToggleAcc(this)">
                            <span><i class="fas fa-info-circle"></i><?= t('details') ?></span>
                            <span class="pd-acc-icon"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="pd-acc-body">
                            <?php
                            if ($current_lang === 'ar' && !empty($product['product_details_ar'])) {
                                $details_content = $product['product_details_ar'];
                            } elseif (!empty($product['product_details'])) {
                                $details_content = $product['product_details'];
                            } else { $details_content = ''; }
                            ?>
                            <?php if (!empty($details_content)): ?>
                                <?= formatRichContent($details_content) ?>
                            <?php else: ?>
                                <ul>
                                    <li><strong><?= t('product_name') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?></li>
                                    <li><strong><?= t('brand') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? ($product['brand_ar'] ?? 'Poshy Store') : ($product['brand_en'] ?? 'Poshy Store')) ?></li>
                                    <li><strong><?= t('category') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? ($product['category_name_ar'] ?? 'مستحضرات التجميل') : ($product['category_name_en'] ?? 'Cosmetics')) ?></li>
                                    <li><strong><?= t('price') ?>:</strong> <?= $detail_display_formatted ?? number_format($detail_display_price, 3) . ' JOD' ?></li>
                                    <li><strong><?= t('stock_status') ?>:</strong> <?= $product['in_stock'] ? t('in_stock') : t('out_of_stock') ?></li>
                                    <?php if ($product['in_stock'] && isAdmin()): ?>
                                    <li><strong><?= t('available_units') ?>:</strong> <?= $product['stock_quantity'] ?></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pd-acc-item">
                        <div class="pd-acc-header" onclick="pdToggleAcc(this)">
                            <span><i class="fas fa-file-alt"></i><?= t('description') ?></span>
                            <span class="pd-acc-icon"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="pd-acc-body">
                            <?php
                            if ($current_lang === 'ar' && !empty($product['description_ar'])) {
                                $desc_content = $product['description_ar'];
                            } elseif (!empty($product['description'])) {
                                $desc_content = $product['description'];
                            } else { $desc_content = ''; }
                            ?>
                            <?php if (!empty($desc_content)): ?>
                                <?= formatRichContent($desc_content) ?>
                            <?php else: ?>
                                <p><?= t('default_description') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pd-acc-item">
                        <div class="pd-acc-header" onclick="pdToggleAcc(this)">
                            <span><i class="fas fa-hand-sparkles"></i><?= t('how_to_use') ?></span>
                            <span class="pd-acc-icon"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="pd-acc-body">
                            <?php
                            $how_to_use_content = '';
                            if ($current_lang === 'ar' && !empty($product['how_to_use_ar'])) {
                                $how_to_use_content = $product['how_to_use_ar'];
                            } elseif (!empty($product['how_to_use_en'])) {
                                $how_to_use_content = $product['how_to_use_en'];
                            } elseif (!empty($product['how_to_use'])) {
                                $how_to_use_content = $product['how_to_use'];
                            }
                            ?>
                            <?php if (!empty($how_to_use_content)): ?>
                                <?= formatRichContent($how_to_use_content) ?>
                            <?php else: ?>
                                <ol>
                                    <li><?= t('howto_step1') ?></li>
                                    <li><?= t('howto_step2') ?></li>
                                    <li><?= t('howto_step3') ?></li>
                                    <li><?= t('howto_step4') ?></li>
                                    <li><?= t('howto_step5') ?></li>
                                </ol>
                                <p style="margin-top:.75rem;color:#888;font-style:italic;font-size:.85rem"><i class="fas fa-info-circle me-1"></i><?= t('refer_packaging') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Shipping tab -->
                    <div class="pd-acc-item">
                        <div class="pd-acc-header" onclick="pdToggleAcc(this)">
                            <span><i class="fas fa-shipping-fast"></i><?= t('shipping_info') ?></span>
                            <span class="pd-acc-icon"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="pd-acc-body">
                            <p><?= t('shipping_detail') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="pd-reviews" id="pdReviews">
                    <div class="pd-rev-header">
                        <div>
                            <div class="pd-rev-title"><?= t('customer_reviews') ?></div>
                            <?php if ($review_count > 0): ?>
                            <div class="pd-rev-summary">
                                <span class="pd-rev-avg"><?= $average_rating ?></span>
                                <div>
                                    <div class="pd-stars" style="font-size:1rem"><?php for ($i = 1; $i <= 5; $i++) echo $i <= $average_rating ? '★' : '☆'; ?></div>
                                    <span style="font-size:.8rem;color:#999"><?= $review_count ?> <?= t('reviews_count') ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($is_logged_in): ?>
                    <div class="pd-rev-form">
                        <h4><?= $user_review ? t('update_review') : t('write_review') ?></h4>
                        <form id="pdReviewForm">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <div class="pd-rev-stars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="pds<?= $i ?>" name="rating" value="<?= $i ?>" <?= $user_review && $user_review['rating'] == $i ? 'checked' : '' ?> <?= !$user_review && $i == 5 ? 'checked' : '' ?>>
                                    <label for="pds<?= $i ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            <textarea name="review_text" placeholder="<?= t('share_experience') ?>" required><?= $user_review ? htmlspecialchars($user_review['review_text']) : '' ?></textarea>
                            <div class="pd-photo-upload">
                                <label style="font-size:.85rem;font-weight:600;color:var(--purple-color)"><i class="fas fa-camera"></i> <?= t('add_photos') ?>:</label>
                                <input type="file" name="review_photos[]" multiple accept="image/jpeg,image/png,image/webp" style="font-size:.8rem">
                                <span style="font-size:.75rem;color:#999"><?= t('max_3_photos') ?></span>
                            </div>
                            <button type="submit" class="pd-btn pd-btn-primary w-100" style="margin-top:.75rem;padding:.7rem">
                                <i class="fas fa-paper-plane me-2"></i><?= $user_review ? t('update_review') : t('submit_review_btn') ?>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="pd-rev-form" style="text-align:center;padding:1rem">
                        <p style="margin:0;font-size:.95rem;color:#666"><a href="<?= $base_url ?>/pages/auth/signin.php" style="color:var(--gold-color);font-weight:700;text-decoration:none"><?= t('login') ?></a> <?= t('sign_in_to_review') ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="pd-rev-list">
                        <?php if (empty($reviews)): ?>
                            <div class="pd-no-rev"><?= t('no_reviews_yet') ?></div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="pd-rev-card">
                                <div class="pd-rev-card-top">
                                    <span class="pd-rev-author"><?= htmlspecialchars($review['user_full_name']) ?></span>
                                    <span class="pd-rev-date"><?= date('F j, Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <div class="pd-rev-stars-display"><?php for ($i = 1; $i <= 5; $i++) echo $i <= $review['rating'] ? '★' : '☆'; ?></div>
                                <div class="pd-rev-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                                <?php if (!empty($review['photos'])): ?>
                                <div class="pd-rev-photos">
                                    <?php foreach (json_decode($review['photos'], true) ?? [] as $photo): ?>
                                    <img class="pd-rev-photo" src="<?= htmlspecialchars($photo) ?>" alt="Review photo" loading="lazy" onclick="window.open(this.src)">
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="pd-lb" id="pdLb" onclick="pdCloseLb(event)">
    <div class="pd-lb-close" onclick="pdCloseLb(event)"><i class="fas fa-times"></i></div>
    <div class="pd-lb-nav pd-lb-prev" onclick="pdLbNav(event, -1)"><i class="fas fa-chevron-<?= $current_lang === 'ar' ? 'right' : 'left' ?>"></i></div>
    <img class="pd-lb-img" id="pdLbImg" src="" alt="">
    <div class="pd-lb-nav pd-lb-next" onclick="pdLbNav(event, 1)"><i class="fas fa-chevron-<?= $current_lang === 'ar' ? 'left' : 'right' ?>"></i></div>
    <div class="pd-lb-counter" id="pdLbCounter"></div>
</div>

<!-- Cart Drawer (Upsell) -->
<div class="pd-drawer-overlay" id="pdDrawerOverlay" onclick="pdCloseDrawer()"></div>
<div class="pd-drawer" id="pdDrawer">
    <div class="pd-drawer-hdr">
        <h3><i class="fas fa-check-circle"></i> <span id="pdDrawerTitle"><?= t('added_to_cart_modal') ?></span></h3>
        <button class="pd-drawer-close" onclick="pdCloseDrawer()">&times;</button>
    </div>
    <div class="pd-drawer-body" id="pdDrawerBody">
        <!-- Added product summary -->
        <div class="pd-drawer-product">
            <div class="pd-drawer-product-img" id="pdDrawerImg">📦</div>
            <div class="pd-drawer-product-info">
                <div class="pd-drawer-product-name" id="pdDrawerName"></div>
                <div class="pd-drawer-product-price" id="pdDrawerPrice"></div>
                <div class="pd-drawer-product-qty" id="pdDrawerQty"></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pd-drawer-actions">
            <a id="pdDrawerCartLink" href="<?= $base_url ?>/pages/shop/<?= $is_logged_in ? 'cart.php' : 'guest_checkout.php' ?>" class="pd-drawer-btn pd-drawer-btn-cart"><i class="fas fa-shopping-cart"></i> <?= t('go_to_cart') ?></a>
            <button class="pd-drawer-btn pd-drawer-btn-continue" onclick="pdCloseDrawer()"><i class="fas fa-shopping-bag"></i> <?= t('continue_shopping_btn') ?></button>
        </div>

        <!-- Recommended Products -->
        <div class="pd-drawer-rec" id="pdDrawerRecSection" style="display:none">
            <div class="pd-drawer-rec-title"><i class="fas fa-heart"></i> <span id="pdDrawerRecTitle"><?= t('you_may_like') ?></span></div>
            <div class="pd-drawer-rec-grid" id="pdDrawerRecs"></div>
        </div>
    </div>
</div>

<?php if (!empty($product['video_review_url']) && (strpos($product['video_review_url'], 'uploads/') === 0 || strpos($product['video_review_url'], '/uploads/') === 0)): ?>
<div class="pd-mini-player" id="pdMini">
    <button class="pd-mini-close" onclick="pdCloseMini()">&times;</button>
    <button class="pd-mini-back" onclick="pdBackToVideo()"><?= $current_lang === 'ar' ? 'العودة' : 'Back' ?></button>
    <video id="pdMiniVideo" playsinline><source src="<?= $base_url . '/' . htmlspecialchars($product['video_review_url']) ?>" type="video/mp4"></video>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>

<script>
const BASE_URL = '<?= $base_url ?>';
const IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;

// Gallery
let pdSlide = 0, pdSlides, pdDots, pdThumbs, pdTotal;
document.addEventListener('DOMContentLoaded', function() {
    pdSlides = document.querySelectorAll('.pd-slide');
    pdDots = document.querySelectorAll('.pd-dot');
    pdThumbs = document.querySelectorAll('.pd-thumb');
    pdTotal = pdSlides.length;
});
function pdGoTo(i) {
    pdSlides.forEach(s => s.classList.remove('active'));
    pdDots.forEach(d => d.classList.remove('active'));
    pdThumbs.forEach(t => t.classList.remove('active'));
    pdSlide = (i + pdTotal) % pdTotal;
    pdSlides[pdSlide].classList.add('active');
    pdDots[pdSlide].classList.add('active');
    if (pdThumbs[pdSlide]) {
        pdThumbs[pdSlide].classList.add('active');
        const c = pdThumbs[pdSlide].parentElement;
        if (c) c.scrollTo({ left: pdThumbs[pdSlide].offsetLeft - c.offsetWidth/2 + pdThumbs[pdSlide].offsetWidth/2, behavior: 'smooth' });
    }
}
let pdTimer = setInterval(() => pdGoTo(pdSlide + 1), 4000);
const pdObs = new IntersectionObserver(e => {
    e.forEach(e => {
        if (e.isIntersecting) { if (!pdTimer) pdTimer = setInterval(() => pdGoTo(pdSlide + 1), 4000); }
        else { if (pdTimer) { clearInterval(pdTimer); pdTimer = null; } }
    });
}, { threshold: .1 });
setTimeout(() => {
    const el = document.getElementById('pdMainImg');
    if (el && typeof IntersectionObserver !== 'undefined') pdObs.observe(el);
}, 0);

// Lightbox
let pdLbImgs = [];
document.addEventListener('DOMContentLoaded', function() {
    pdSlides.forEach(s => { const i = s.querySelector('img'); if (i) pdLbImgs.push(i.src); });
    document.getElementById('pdMainImg').addEventListener('click', function(e) {
        if (e.target.closest('.pd-indicators') || e.target.closest('.pd-dot')) return;
        const img = document.querySelector('.pd-slide.active img');
        if (img) pdOpenLb(img.src);
    });
});
function pdOpenLb(src) {
    const idx = pdLbImgs.indexOf(src);
    pdLbIndex = idx >= 0 ? idx : 0;
    document.getElementById('pdLbImg').src = pdLbImgs[pdLbIndex];
    document.getElementById('pdLb').classList.add('active');
    document.body.style.overflow = 'hidden';
    clearInterval(pdTimer);
    pdLbCounter();
}
let pdLbIndex = 0;
function pdCloseLb(e) { if (e.target.closest('.pd-lb-nav')) return; document.getElementById('pdLb').classList.remove('active'); document.body.style.overflow = ''; pdTimer = setInterval(() => pdGoTo(pdSlide + 1), 4000); }
function pdLbNav(e, d) { e.stopPropagation(); pdLbIndex = (pdLbIndex + d + pdLbImgs.length) % pdLbImgs.length; document.getElementById('pdLbImg').src = pdLbImgs[pdLbIndex]; pdGoTo(pdLbIndex); pdLbCounter(); }
function pdLbCounter() { document.getElementById('pdLbCounter').textContent = pdLbImgs.length > 1 ? (pdLbIndex + 1) + ' / ' + pdLbImgs.length : ''; }
document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('pdLb');
    if (!lb.classList.contains('active')) return;
    if (e.key === 'Escape') pdCloseLb(e);
    else if (e.key === 'ArrowLeft') pdLbNav(e, -1);
    else if (e.key === 'ArrowRight') pdLbNav(e, 1);
});

// Accordion
function pdToggleAcc(h) {
    const item = h.parentElement;
    const wasOpen = item.classList.contains('open');
    // Close all
    document.querySelectorAll('.pd-acc-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
}

// Alert
function pdAlert(type, msg) {
    const a = document.createElement('div');
    a.className = 'pd-alert ' + type;
    a.textContent = msg;
    document.body.appendChild(a);
    setTimeout(() => a.remove(), 3000);
}

// Add to Cart (logged in)
function pdAddToCart(id) {
    const btn = document.getElementById('pdAddBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...'; }
    fetch(BASE_URL + '/api/add_to_cart_api.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + id + '&quantity=1'
    }).then(r => r.json()).then(d => {
        if (d.success) { pdReplaceBtn(id, 1); pdTrackATC(id); pdShowModal(id); }
        else { pdAlert('error', d.error || 'Failed'); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-shopping-cart"></i> <?= t("add_to_cart") ?>'; } }
    }).catch(e => { pdAlert('error', 'Network error'); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-shopping-cart"></i> <?= t("add_to_cart") ?>'; } });
}

// Guest Add to Cart
function pdGuestAdd(id) {
    const btn = document.getElementById('pdAddBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...'; }
    fetch(BASE_URL + '/api/guest_cart_api.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&product_id=' + id + '&quantity=1'
    }).then(r => r.json()).then(d => {
        if (d.success) { pdTrackATC(id); pdShowModal(id); }
        else { pdAlert('error', d.error || 'Failed'); }
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-shopping-cart"></i> <?= t("add_to_cart") ?>'; }
    }).catch(e => { pdAlert('error', 'Network error'); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-shopping-cart"></i> <?= t("add_to_cart") ?>'; } });
}

function pdTrackATC(id) {
    if (!window.metaTrackCatalogEvent) return;
    const ctx = window.metaProductContext || {};
    window.metaTrackCatalogEvent('AddToCart', [String(ctx.catalogId || id)], { value: ctx.price || 0, currency: ctx.currency || 'USD' });
}

function pdBuyNow(id) {
    if (IS_LOGGED_IN) { pdAddToCart(id); setTimeout(() => window.location.href = BASE_URL + '/pages/shop/cart.php', 500); }
    else { pdGuestAdd(id); setTimeout(() => window.location.href = BASE_URL + '/pages/shop/guest_checkout.php', 500); }
}

function pdReplaceBtn(id, qty) {
    const btn = document.getElementById('pdAddBtn');
    if (!btn) return;
    const c = document.createElement('div');
    c.className = 'pd-qty flex-grow-1'; c.id = 'pdQtyControls';
    c.innerHTML = `<button class="pd-qty-btn" onclick="pdUpdateQty(${id},'decrease')"><i class="fas fa-minus"></i></button>
        <div><div style="font-size:.75rem;color:#888;text-align:center"><?= t('in_cart_label') ?></div><div class="pd-qty-val" id="pdCartQty">${qty}</div></div>
        <button class="pd-qty-btn" onclick="pdUpdateQty(${id},'increase')"><i class="fas fa-plus"></i></button>`;
    btn.parentNode.replaceChild(c, btn);
}
function pdRestoreBtn(id, inStock) {
    const c = document.getElementById('pdQtyControls');
    if (!c) return;
    const btn = document.createElement('button');
    btn.id = 'pdAddBtn'; btn.className = 'pd-btn pd-btn-primary flex-grow-1';
    btn.onclick = function() { pdAddToCart(id); };
    if (!inStock) { btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed'; }
    btn.innerHTML = '<i class="fas fa-shopping-cart"></i> ' + (inStock ? '<?= t("add_to_cart") ?>' : '<?= t("out_of_stock") ?>');
    c.parentNode.replaceChild(btn, c);
}

function pdUpdateQty(id, action) {
    fetch(BASE_URL + '/api/update_cart_quantity.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${id}&action=${action}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            if (d.action === 'removed') { pdRestoreBtn(id, true); pdAlert('success', '<?= t("product_removed") ?>'); }
            else { const el = document.getElementById('pdCartQty'); if (el) el.textContent = d.new_quantity; pdAlert('success', d.action === 'increased' ? '<?= t("quantity_increased") ?>' : '<?= t("quantity_decreased") ?>'); }
        } else pdAlert('error', d.error || '<?= t("failed_update_qty") ?>');
    }).catch(e => pdAlert('error', '<?= t("network_error") ?>'));
}

// Cart Drawer (Upsell)
function pdShowModal(id) {
    fetch(BASE_URL + '/api/get_cart_popup_data.php?product_id=' + id)
        .then(r => r.json()).then(d => {
            if (d.success) { pdPopulateDrawer(d); pdOpenDrawer(); }
            else pdAlert('error', d.error || '<?= t("failed_add_to_cart") ?>');
        }).catch(e => pdAlert('error', '<?= t("network_error") ?>'));
}
function pdOpenDrawer() {
    document.getElementById('pdDrawerOverlay').classList.add('active');
    document.getElementById('pdDrawer').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function pdCloseDrawer() {
    document.getElementById('pdDrawerOverlay').classList.remove('active');
    document.getElementById('pdDrawer').classList.remove('active');
    document.body.style.overflow = '';
}
// Refresh all cart count badges in the UI
function pdRefreshCartCount(count) {
    const cartLink = document.getElementById('pdDrawerCartLink');
    if (cartLink) cartLink.innerHTML = '<i class="fas fa-shopping-cart"></i> <?= t("go_to_cart") ?> (' + count + ')';
    document.querySelectorAll('.cart-badge').forEach(el => { el.textContent = count; el.style.display = count > 0 ? '' : 'none'; });
    document.querySelectorAll('.bottom-nav-badge').forEach(el => { el.textContent = count > 9 ? '9+' : count; el.style.display = count > 0 ? '' : 'none'; });
    document.querySelectorAll('.mobile-cart-count').forEach(el => { el.textContent = count; el.style.display = count > 0 ? '' : 'none'; });
}
function pdPopulateDrawer(d) {
    const p = d.added_product;
    const imgEl = document.getElementById('pdDrawerImg');
    if (p.image_path) imgEl.innerHTML = `<img src="${p.image_path}" alt="${(p.name_en||'').replace(/"/g,'&quot;')}" onerror="this.onerror=null;this.parentElement.textContent='📦';">`;
    else imgEl.textContent = '📦';
    document.getElementById('pdDrawerName').textContent = p.name_en;
    document.getElementById('pdDrawerPrice').textContent = p.price;
    document.getElementById('pdDrawerQty').textContent = '<?= t("quantity") ?>: ' + p.quantity;
    document.getElementById('pdDrawerTitle').textContent = '✅ ' + p.name_en + ' — <?= t("added_to_cart_modal") ?>';

    // Update cart link & all nav badges
    pdRefreshCartCount(d.cart_count);

    // Recommended products
    const recSection = document.getElementById('pdDrawerRecSection');
    const recGrid = document.getElementById('pdDrawerRecs');
    const recNames = {};
    recGrid.innerHTML = '';
    if (d.recommended_products && d.recommended_products.length) {
        recSection.style.display = 'block';
        d.recommended_products.forEach(r => {
            const safeName = (r.name_en||'').replace(/[&<>"']/g,'');
            recNames[r.id] = r.name_en || '';
            const card = document.createElement('div'); card.className = 'pd-drec-card';
            const priceHtml = r.has_discount
                ? `<div class="pd-drec-price-line"><span class="pd-drec-orig">${r.original_price_formatted || r.price_formatted}</span><span class="pd-drec-price">${r.discounted_price_formatted || r.final_price_formatted || r.price_formatted}</span></div>`
                : `<div class="pd-drec-price-line"><span class="pd-drec-price">${r.price_formatted}</span></div>`;
            const imgSrc = (r.image_path || '/images/placeholder-cosmetics.svg').replace(/"/g,'&quot;');
            card.innerHTML = `<div class="pd-drec-img"><img src="${imgSrc}" alt="${safeName}" loading="lazy" onerror="this.onerror=null;this.parentElement.innerHTML='<span style=font-size:1.8rem>✨</span>';"></div>
                <div class="pd-drec-name">${safeName}</div>${priceHtml}
                <button class="pd-drec-btn" data-id="${r.id}"><i class="fas fa-plus"></i> <?= t('add_button') ?></button>`;
            card.querySelector('.pd-drec-btn').addEventListener('click', function(e) {
                pdRecOneClick(parseInt(this.dataset.id), this, recNames);
            });
            recGrid.appendChild(card);
        });
    } else recSection.style.display = 'none';
}
// One-click add from drawer - no page reload, drawer stays open, live refresh
function pdRecOneClick(id, btn, names) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const endpoint = IS_LOGGED_IN ? BASE_URL + '/api/add_to_cart_api.php' : BASE_URL + '/api/guest_cart_api.php';
    const body = IS_LOGGED_IN ? 'product_id=' + id + '&quantity=1' : 'action=add&product_id=' + id + '&quantity=1';
    const name = (names && names[id]) || '';
    fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(r => r.json()).then(d => {
            if (d.success) {
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.background = '#4caf50';
                setTimeout(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus"></i> <?= t("add_button") ?>'; btn.style.background = ''; }, 1500);
                // Refresh cart count everywhere
                fetch(BASE_URL + '/api/get_cart_popup_data.php?product_id=' + id)
                    .then(r => r.json()).then(cd => {
                        if (cd.success) pdRefreshCartCount(cd.cart_count);
                    });
                pdAlert('success', name + ' <?= t("added_to_cart_success") ?>');
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus"></i> <?= t("add_button") ?>';
                pdAlert('error', d.error || '<?= t("failed_add_to_cart") ?>');
            }
        }).catch(e => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> <?= t("add_button") ?>';
            pdAlert('error', '<?= t("network_error") ?>');
        });
}

// Review Form
document.getElementById('pdReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(BASE_URL + '/api/submit_review.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) { pdAlert('success', d.message); setTimeout(() => location.reload(), 1500); }
            else pdAlert('error', d.error || '<?= t("failed_submit_review") ?>');
        }).catch(e => pdAlert('error', '<?= t("network_error") ?>'));
});

// Sticky Mini Player
<?php if (!empty($product['video_review_url']) && (strpos($product['video_review_url'], 'uploads/') === 0 || strpos($product['video_review_url'], '/uploads/') === 0)): ?>
(function() {
    const mv = document.getElementById('pdVideo'), mp = document.getElementById('pdMini'), mm = document.getElementById('pdMiniVideo'), vc = document.querySelector('.pd-acc-body');
    if (!mv || !mp || !mm || !vc) return;
    let closed = false, wasPlay = false;
    function check() {
        if (closed) return;
        const r = vc.getBoundingClientRect();
        if (r.bottom < 0 && !mv.paused) { if (!mp.classList.contains('visible')) { mm.currentTime = mv.currentTime; mv.pause(); mm.play(); mp.classList.add('visible'); } }
        else if (r.bottom >= 0 && mp.classList.contains('visible')) { mv.currentTime = mm.currentTime; mm.pause(); mv.play(); mp.classList.remove('visible'); }
    }
    window.addEventListener('scroll', check, { passive: true });
    mm.addEventListener('ended', function() { mv.currentTime = mm.currentTime; mp.classList.remove('visible'); });
    window.pdCloseMini = function() { mm.pause(); mp.classList.remove('visible'); closed = true; mv.pause(); };
    window.pdBackToVideo = function() { mm.pause(); mv.currentTime = mm.currentTime; mp.classList.remove('visible'); vc.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(() => mv.play(), 500); };
    mv.addEventListener('play', function() { closed = false; });
})();
<?php endif; ?>
</script>
</body>
</html>
