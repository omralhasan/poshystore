import re

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Find the block building $slides
match = re.search(r'(// Build slides array.*?\$slide_count = count\(\$slides\);)', content, re.DOTALL)
if match:
    slides_block = match.group(1)
    # Remove it from its original place
    content = content.replace("    <?php\n    " + slides_block + "\n    ?>\n", "")
    content = content.replace("    <?php\n    " + slides_block + "\n    ?>", "")
    
    # Let's cleanly replace the exact exact string
    # Actually, simpler:
    content = content.replace(slides_block, "")
    # Wait, replace(slides_block, "") leaves `<?php \n \n ?>` which is harmless.
    
    # 2. Insert it before `<!DOCTYPE html>`
    # We will search for `// Security headers` block and put it before that.
    content = content.replace("// Security headers", slides_block + "\n\n// Security headers")

# 3. Add Preload link in <head>
preload_str = """    <link rel="stylesheet" href="assets/css/home.min.css">
    <?php if (!empty($slides) && !empty($slides[0]['image'])): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars(prefer_webp_relative_path((string)($slides[0]['image'] ?? ''), ROOT_DIR)) ?>">
    <?php endif; ?>"""
content = content.replace('<link rel="stylesheet" href="assets/css/home.min.css">', preload_str)

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Preload injection done.")
