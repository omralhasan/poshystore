import re

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Find the script block
script_match = re.search(r'<script>(.*?)</script>', content, re.DOTALL)
if script_match:
    js_content = script_match.group(1)
    
    # Define our replacement logic for JS
    # Keep the const declarations but point them to AppConfig
    js_content = re.sub(r"const CURRENT_LANG = '<\?= \$lang \?>';", "const CURRENT_LANG = window.AppConfig.lang;", js_content)
    js_content = re.sub(r"const IS_LOGGED_IN = <\?= \$is_logged_in \? 'true' : 'false' \?>;", "const IS_LOGGED_IN = window.AppConfig.isLoggedIn;", js_content)
    js_content = re.sub(r"const CURRENCY_TEXT = '<\?= addslashes\(t\(\"currency\"\)\) \?>';", "const CURRENCY_TEXT = window.AppConfig.currencyText;", js_content)
    js_content = re.sub(r"const ADD_TO_CART_TEXT = '<\?= addslashes\(t\(\"add_to_cart\"\)\) \?>';", "const ADD_TO_CART_TEXT = window.AppConfig.addToCartText;", js_content)
    js_content = re.sub(r"const LOGIN_TEXT = '<\?= addslashes\(t\(\"login\"\)\) \?>';", "const LOGIN_TEXT = window.AppConfig.loginText;", js_content)
    js_content = re.sub(r"const DETAILS_TEXT = '<\?= addslashes\(t\(\"details\"\)\) \?>';", "const DETAILS_TEXT = window.AppConfig.detailsText;", js_content)
    js_content = re.sub(r"const FEATURED_TEXT = '<\?= addslashes\(t\(\"featured_products\"\)\) \?>';", "const FEATURED_TEXT = window.AppConfig.featuredText;", js_content)
    js_content = re.sub(r"const VIEW_ALL_TEXT = '<\?= addslashes\(t\(\"view_all_products\"\)\) \?>';", "const VIEW_ALL_TEXT = window.AppConfig.viewAllText;", js_content)
    js_content = re.sub(r"const VIEW_ALL_LINK_TEXT = '<\?= addslashes\(t\(\"view_all\"\)\) \?>';", "const VIEW_ALL_LINK_TEXT = window.AppConfig.viewAllLinkText;", js_content)
    js_content = re.sub(r"const NO_PRODUCTS_TEXT = '<\?= addslashes\(t\(\"no_products_found\"\)\) \?>';", "const NO_PRODUCTS_TEXT = window.AppConfig.noProductsText;", js_content)
    js_content = re.sub(r"const TRY_SEARCHING_TEXT = '<\?= addslashes\(t\(\"try_searching_else\"\)\) \?>';", "const TRY_SEARCHING_TEXT = window.AppConfig.trySearchingText;", js_content)
    
    js_content = re.sub(r"let currentFilter = { subcategory: <\?= \$active_subcategory \?>, show_all: <\?= \$show_all \? 'true' : 'false' \?> };", "let currentFilter = window.AppConfig.currentFilter;", js_content)
    js_content = re.sub(r"'<\?= \$lang === \"ar\" \? \"تمت الإضافة للسلة\" : \"Added to cart!\" \?>'", "window.AppConfig.toastAdded", js_content)
    js_content = re.sub(r"const LANG\s*=\s*'<\?= \$lang \?>';", "const LANG = window.AppConfig.lang;", js_content)
    js_content = re.sub(r"<\?= \$lang === 'ar' \? 'اضغط Enter للبحث الكامل' : 'Press Enter for full results' \?>", "${window.AppConfig.searchFooter}", js_content)

    # LCP Fix: Remove the setTimeout around initHeroSlider
    # It looks like: runWhenIdle(() => { ... initHeroSlider ... }, 800);
    # Let's just find the initHeroSlider parts and make sure we remove the timeout constraint.
    # Actually, simpler: replace `runWhenIdle(function() {` that encapsulates initHeroSlider.
    # Wait, the code is:
    # runWhenIdle(function() {
    #     const initHeroSlider = function() { ... }
    #     if (...) { ... }
    # }, 800);
    
    # We will just write JS content directly since we have it in memory.
    
    with open('assets/js/home.js', 'w', encoding='utf-8') as f2:
        f2.write(js_content.strip())
    
    # The actual minification
    # A simple minify for JS: remove single line comments, empty lines.
    lines = js_content.split('\n')
    min_lines = []
    for line in lines:
        line = line.strip()
        if not line or line.startswith('//'): continue
        min_lines.append(line)
    
    min_js = '\n'.join(min_lines)
    
    # Write minified JS
    with open('assets/js/home.min.js', 'w', encoding='utf-8') as f2:
        f2.write(min_js)
    
    # New Config Block
    config_block = """<script>
    window.AppConfig = {
        lang: '<?= $lang ?>',
        isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
        currencyText: '<?= addslashes(t("currency")) ?>',
        addToCartText: '<?= addslashes(t("add_to_cart")) ?>',
        loginText: '<?= addslashes(t("login")) ?>',
        detailsText: '<?= addslashes(t("details")) ?>',
        featuredText: '<?= addslashes(t("featured_products")) ?>',
        viewAllText: '<?= addslashes(t("view_all_products")) ?>',
        viewAllLinkText: '<?= addslashes(t("view_all")) ?>',
        noProductsText: '<?= addslashes(t("no_products_found")) ?>',
        trySearchingText: '<?= addslashes(t("try_searching_else")) ?>',
        currentFilter: { subcategory: <?= $active_subcategory ?>, show_all: <?= $show_all ? 'true' : 'false' ?> },
        toastAdded: '<?= $lang === "ar" ? "تمت الإضافة للسلة" : "Added to cart!" ?>',
        searchFooter: '<?= $lang === "ar" ? "اضغط Enter للبحث الكامل" : "Press Enter for full results" ?>'
    };
    </script>
    <script src="assets/js/home.min.js" defer></script>"""
    
    new_content = content.replace(script_match.group(0), config_block)
    
    with open('index.php', 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print("Extracted and minified JS.")

