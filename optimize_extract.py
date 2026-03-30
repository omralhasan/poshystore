import re

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Extract CSS
css_match = re.search(r'<style>(.*?)</style>', content, re.DOTALL)
if css_match:
    css = css_match.group(1)
    # Basic minification
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.DOTALL) # remove comments
    css = re.sub(r'\s+', ' ', css) # replace whitespace with space
    css = re.sub(r'\s*{\s*', '{', css)
    css = re.sub(r'\s*}\s*', '}', css)
    css = re.sub(r'\s*;\s*', ';', css)
    css = re.sub(r'\s*:\s*', ':', css)
    css = re.sub(r'\s*,\s*', ',', css)
    
    with open('assets/css/home.min.css', 'w', encoding='utf-8') as f2:
        f2.write(css.strip())
    
    # Replace style block
    content = content.replace(css_match.group(0), '<link rel="stylesheet" href="assets/css/home.min.css">')
    print("Extracted CSS.")

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(content)

