const PRODUCTS = [
  { id: 1, name: 'Hydra Glow Serum', price: 29, oldPrice: 39, image: 'product-1.jpg', image2: 'product-1-alt.jpg', brand: 'Anua', skinType: 'Dry', concern: 'Hydration', category: 'Skincare' },
  { id: 2, name: 'Velvet Clean Cleanser', price: 21, oldPrice: 0, image: 'product-2.jpg', image2: 'product-2-alt.jpg', brand: 'Cosrx', skinType: 'Combination', concern: 'Acne', category: 'Skincare' },
  { id: 3, name: 'Silk Repair Cream', price: 34, oldPrice: 45, image: 'product-3.jpg', image2: 'product-3-alt.jpg', brand: 'Beauty of Joseon', skinType: 'Normal', concern: 'Anti-Aging', category: 'Skincare' },
  { id: 4, name: 'Golden Lift Eye Gel', price: 26, oldPrice: 32, image: 'product-4.jpg', image2: 'product-4-alt.jpg', brand: 'Anua', skinType: 'All', concern: 'Dark Circles', category: 'Skincare' },
  { id: 5, name: 'Nourish Hair Mask', price: 24, oldPrice: 0, image: 'product-5.jpg', image2: 'product-5-alt.jpg', brand: 'K18', skinType: 'All', concern: 'Repair', category: 'Hair' },
  { id: 6, name: 'Cloud Matte Tint', price: 19, oldPrice: 25, image: 'product-6.jpg', image2: 'product-6-alt.jpg', brand: 'Rare Beauty', skinType: 'All', concern: 'Makeup', category: 'Makeup' },
  { id: 7, name: 'Velvet Body Lotion', price: 23, oldPrice: 29, image: 'product-2.jpg', image2: 'product-2-alt.jpg', brand: 'Sol de Janeiro', skinType: 'All', concern: 'Hydration', category: 'Body' },
  { id: 8, name: 'Silk Smooth Shampoo', price: 22, oldPrice: 27, image: 'product-5.jpg', image2: 'product-5-alt.jpg', brand: 'K18', skinType: 'All', concern: 'Repair', category: 'Hair' },
  { id: 9, name: 'Luminous Lip Velvet', price: 18, oldPrice: 22, image: 'product-6.jpg', image2: 'product-6-alt.jpg', brand: 'Rare Beauty', skinType: 'All', concern: 'Makeup', category: 'Makeup' },
  { id: 10, name: 'Silky Body Scrub', price: 20, oldPrice: 0, image: 'product-4.jpg', image2: 'product-4-alt.jpg', brand: 'Tree Hut', skinType: 'All', concern: 'Exfoliation', category: 'Body' }
];

const HOME_CATEGORIES = ['Skincare', 'Hair', 'Makeup', 'Body'];

const CART_KEY = 'poshy_cart';
const LANG_KEY = 'poshy_lang';

function getCart() {
  try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }
  catch { return []; }
}

function setCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartCounters();
  renderCartDrawerItems();
}

function cartCount() {
  return getCart().reduce((sum, item) => sum + item.qty, 0);
}

function updateCartCounters() {
  const count = cartCount();
  document.querySelectorAll('[data-cart-count]').forEach(el => {
    el.textContent = count;
    el.classList.toggle('hidden', count === 0);
  });
}

function addToCart(productId, qty = 1) {
  const product = PRODUCTS.find(p => p.id === productId);
  if (!product) return;
  const cart = getCart();
  const found = cart.find(i => i.id === productId);
  if (found) found.qty += qty;
  else cart.push({ id: product.id, name: product.name, price: product.price, image: product.image, qty });
  setCart(cart);

  const icon = document.querySelector('[data-cart-icon]');
  if (icon) {
    icon.classList.remove('cart-pop');
    requestAnimationFrame(() => icon.classList.add('cart-pop'));
  }

  openCartDrawer();
}

function removeFromCart(productId) {
  const cart = getCart().filter(i => i.id !== productId);
  setCart(cart);
}

function changeQty(productId, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === productId);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) return removeFromCart(productId);
  setCart(cart);
}

function cartTotals() {
  const cart = getCart();
  const subtotal = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
  return { subtotal, total: subtotal };
}

function renderCartDrawerItems() {
  const list = document.getElementById('cartDrawerItems');
  const subtotalEl = document.getElementById('cartSubtotal');
  if (!list || !subtotalEl) return;

  const cart = getCart();
  if (!cart.length) {
    list.innerHTML = '<p class="text-sm text-black/60">Your cart is empty.</p>';
    subtotalEl.textContent = '$0.00';
    return;
  }

  list.innerHTML = cart.map(item => `
    <div class="flex gap-3 border-b border-black/10 pb-3">
      <img src="${item.image}" alt="${item.name}" class="w-16 h-16 rounded-lg object-cover" />
      <div class="flex-1">
        <p class="text-sm font-medium">${item.name}</p>
        <p class="text-sm text-black/70">$${item.price.toFixed(2)}</p>
        <div class="mt-2 flex items-center gap-2">
          <button class="qty-btn border border-black/20 rounded" data-cart-minus="${item.id}">-</button>
          <span class="text-sm w-6 text-center">${item.qty}</span>
          <button class="qty-btn border border-black/20 rounded" data-cart-plus="${item.id}">+</button>
          <button class="text-xs text-red-600 ms-auto" data-cart-remove="${item.id}">Remove</button>
        </div>
      </div>
    </div>
  `).join('');

  const totals = cartTotals();
  subtotalEl.textContent = `$${totals.subtotal.toFixed(2)}`;

  list.querySelectorAll('[data-cart-minus]').forEach(btn => btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartMinus), -1)));
  list.querySelectorAll('[data-cart-plus]').forEach(btn => btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartPlus), 1)));
  list.querySelectorAll('[data-cart-remove]').forEach(btn => btn.addEventListener('click', () => removeFromCart(Number(btn.dataset.cartRemove))));
}

function renderCartPage() {
  const list = document.getElementById('cartPageItems');
  const subtotalEl = document.getElementById('cartPageSubtotal');
  if (!list || !subtotalEl) return;

  const cart = getCart();
  if (!cart.length) {
    list.innerHTML = '<p class="text-sm text-black/60">Your cart is empty.</p>';
    subtotalEl.textContent = '$0.00';
    return;
  }

  list.innerHTML = cart.map(item => `
    <div class="flex gap-3 border-b border-black/10 pb-3">
      <img src="${item.image}" alt="${item.name}" class="w-16 h-16 rounded-lg object-cover" />
      <div class="flex-1">
        <p class="text-sm font-medium">${item.name}</p>
        <p class="text-sm text-black/70">$${item.price.toFixed(2)}</p>
        <div class="mt-2 flex items-center gap-2">
          <button class="qty-btn border border-black/20 rounded" data-cart-minus="${item.id}">-</button>
          <span class="text-sm w-6 text-center">${item.qty}</span>
          <button class="qty-btn border border-black/20 rounded" data-cart-plus="${item.id}">+</button>
          <button class="text-xs text-red-600 ms-auto" data-cart-remove="${item.id}">Remove</button>
        </div>
      </div>
    </div>
  `).join('');

  const totals = cartTotals();
  subtotalEl.textContent = `$${totals.subtotal.toFixed(2)}`;

  list.querySelectorAll('[data-cart-minus]').forEach(btn => btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartMinus), -1)));
  list.querySelectorAll('[data-cart-plus]').forEach(btn => btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartPlus), 1)));
  list.querySelectorAll('[data-cart-remove]').forEach(btn => btn.addEventListener('click', () => removeFromCart(Number(btn.dataset.cartRemove))));
}

function openCartDrawer() {
  const drawer = document.getElementById('cartDrawer');
  const overlay = document.getElementById('cartOverlay');
  if (!drawer || !overlay) return;
  drawer.classList.add('open');
  overlay.classList.add('open');
}

function closeCartDrawer() {
  const drawer = document.getElementById('cartDrawer');
  const overlay = document.getElementById('cartOverlay');
  if (!drawer || !overlay) return;
  drawer.classList.remove('open');
  overlay.classList.remove('open');
}

function initSearchSuggestions() {
  const input = document.getElementById('siteSearch');
  const box = document.getElementById('searchSuggestions');
  if (!input || !box) return;

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    if (!q) {
      box.classList.add('hidden');
      return;
    }
    const result = PRODUCTS.filter(p => p.name.toLowerCase().includes(q)).slice(0, 6);
    box.innerHTML = result.length ? result.map(p => `<a href="product.html?id=${p.id}" class="block px-3 py-2 hover:bg-black/5 text-sm">${p.name}</a>`).join('') : '<p class="px-3 py-2 text-sm text-black/60">No results</p>';
    box.classList.remove('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#searchWrapper')) box.classList.add('hidden');
  });
}

function initMegaMenu() {
  const btn = document.getElementById('megaMenuBtn');
  const menu = document.getElementById('megaMenu');
  if (!btn || !menu) return;
  btn.addEventListener('mouseenter', () => menu.classList.add('open'));
  btn.addEventListener('click', () => menu.classList.toggle('open'));
  menu.addEventListener('mouseleave', () => menu.classList.remove('open'));
  document.addEventListener('click', e => {
    if (!e.target.closest('#megaMenu') && !e.target.closest('#megaMenuBtn')) menu.classList.remove('open');
  });
}

function productCardTemplate(p) {
  return `
  <article class="product-card group rounded-2xl bg-white shadow-sm overflow-hidden border border-black/5">
    <div class="relative">
      ${p.oldPrice > p.price ? '<span class="absolute z-10 left-3 top-3 bg-black text-white text-xs px-2 py-1 rounded-full">Sale</span>' : ''}
      <img src="${p.image}" alt="${p.name}" class="p-image-primary w-full aspect-[3/4] object-cover" />
      <img src="${p.image2}" alt="${p.name}" class="p-image-secondary absolute inset-0 w-full h-full object-cover opacity-0" />
      <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-end p-3 gap-2">
        <button class="flex-1 bg-white text-black text-sm py-2 rounded-lg" data-quick-view="${p.id}">Quick View</button>
        <button class="flex-1 bg-black text-white text-sm py-2 rounded-lg" data-add-cart="${p.id}">Add to Cart</button>
      </div>
    </div>
    <div class="p-4">
      <h3 class="font-medium text-sm">${p.name}</h3>
      <p class="text-xs text-black/60 mt-1">${p.brand}</p>
      <div class="mt-2 flex items-center gap-2">
        <span class="font-semibold">$${p.price.toFixed(2)}</span>
        ${p.oldPrice ? `<span class="text-xs line-through text-black/45">$${p.oldPrice.toFixed(2)}</span>` : ''}
      </div>
    </div>
  </article>`;
}

function bindCardActions(container) {
  container.querySelectorAll('[data-add-cart]').forEach(btn => btn.addEventListener('click', () => addToCart(Number(btn.dataset.addCart))));
  container.querySelectorAll('[data-quick-view]').forEach(btn => btn.addEventListener('click', () => window.location.href = `product.html?id=${btn.dataset.quickView}`));
}

function renderHomeProducts() {
  const grid = document.getElementById('homeProductGrid');
  if (!grid) return;
  grid.innerHTML = PRODUCTS.slice(0, 6).map(productCardTemplate).join('');
  bindCardActions(grid);
}

function renderHomeCategorySections() {
  const container = document.getElementById('homeCategorySections');
  if (!container) return;

  container.innerHTML = HOME_CATEGORIES.map(category => {
    const categoryProducts = PRODUCTS.filter(product => product.category === category).slice(0, 4);
    return `
      <section id="cat-${category.toLowerCase()}" class="pt-4 first:pt-0">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-heading text-3xl">${category}</h3>
          <a href="shop.html" class="text-sm hover:text-gold">View All</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
          ${categoryProducts.map(productCardTemplate).join('')}
        </div>
      </section>
    `;
  }).join('');

  bindCardActions(container);
}

function renderShopProducts() {
  const grid = document.getElementById('shopProductGrid');
  if (!grid) return;

  let list = [...PRODUCTS];
  const sort = document.getElementById('sortProducts')?.value || 'newest';

  if (sort === 'low-high') list.sort((a,b) => a.price - b.price);
  if (sort === 'best') list.sort((a,b) => b.oldPrice - a.oldPrice);

  const active = [...document.querySelectorAll('[data-filter-input]:checked')].map(i => i.value);
  if (active.length) {
    list = list.filter(p => active.includes(p.brand) || active.includes(p.skinType) || active.includes(p.concern));
  }

  grid.innerHTML = list.map(productCardTemplate).join('');
  bindCardActions(grid);
}

function initFilters() {
  document.querySelectorAll('.filter-accordion-toggle').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.filter-accordion')?.classList.toggle('open'));
  });

  document.querySelectorAll('[data-filter-input]').forEach(i => i.addEventListener('change', renderShopProducts));
  document.getElementById('sortProducts')?.addEventListener('change', renderShopProducts);
}

function initPdp() {
  const qtyInput = document.getElementById('pdpQty');
  if (!qtyInput) return;

  document.getElementById('qtyMinus')?.addEventListener('click', () => {
    qtyInput.value = Math.max(1, Number(qtyInput.value) - 1);
  });

  document.getElementById('qtyPlus')?.addEventListener('click', () => {
    qtyInput.value = Number(qtyInput.value) + 1;
  });

  document.getElementById('pdpAddToCart')?.addEventListener('click', () => {
    const id = Number(new URLSearchParams(window.location.search).get('id') || 1);
    addToCart(id, Number(qtyInput.value));
  });

  document.querySelectorAll('[data-tab-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.tabBtn;
      document.querySelectorAll('[data-tab-btn]').forEach(b => b.classList.remove('border-black'));
      btn.classList.add('border-black');
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      document.getElementById(id)?.classList.add('active');
    });
  });
}

function renderCheckoutSummary() {
  const list = document.getElementById('checkoutSummary');
  const subtotalEl = document.getElementById('checkoutSubtotal');
  const totalEl = document.getElementById('checkoutTotal');
  if (!list || !subtotalEl || !totalEl) return;

  const cart = getCart();
  list.innerHTML = cart.map(i => `<div class="flex justify-between text-sm"><span>${i.name} × ${i.qty}</span><span>$${(i.price*i.qty).toFixed(2)}</span></div>`).join('');
  const t = cartTotals();
  subtotalEl.textContent = `$${t.subtotal.toFixed(2)}`;
  totalEl.textContent = `$${t.total.toFixed(2)}`;
}

function initLanguageToggle() {
  const btn = document.getElementById('langToggle');
  if (!btn) return;

  const saved = localStorage.getItem(LANG_KEY) || 'en';
  setLang(saved);

  btn.addEventListener('click', () => {
    const next = document.documentElement.dir === 'rtl' ? 'en' : 'ar';
    setLang(next);
    localStorage.setItem(LANG_KEY, next);
  });
}

function setLang(lang) {
  const html = document.documentElement;
  const btn = document.getElementById('langToggle');
  if (lang === 'ar') {
    html.lang = 'ar';
    html.dir = 'rtl';
    if (btn) btn.textContent = 'English';
  } else {
    html.lang = 'en';
    html.dir = 'ltr';
    if (btn) btn.textContent = 'العربية';
  }
}

function initCommon() {
  updateCartCounters();
  renderCartDrawerItems();
  renderCartPage();
  initSearchSuggestions();
  initMegaMenu();
  initLanguageToggle();

  document.getElementById('openCartBtn')?.addEventListener('click', openCartDrawer);
  document.getElementById('closeCartBtn')?.addEventListener('click', closeCartDrawer);
  document.getElementById('cartOverlay')?.addEventListener('click', closeCartDrawer);

  document.querySelectorAll('[data-add-cart]').forEach(btn => btn.addEventListener('click', () => addToCart(Number(btn.dataset.addCart))));
}

document.addEventListener('DOMContentLoaded', () => {
  initCommon();
  renderHomeProducts();
  renderHomeCategorySections();
  initFilters();
  renderShopProducts();
  initPdp();
  renderCheckoutSummary();

  if (window.Swiper) {
    new Swiper('.hero-swiper', {
      loop: true,
      autoplay: { delay: 4500 },
      pagination: { el: '.swiper-pagination', clickable: true }
    });

    new Swiper('.related-swiper', {
      slidesPerView: 1.2,
      spaceBetween: 14,
      breakpoints: {
        768: { slidesPerView: 3, spaceBetween: 20 },
        1024: { slidesPerView: 4, spaceBetween: 24 }
      }
    });

    new Swiper('.pdp-main-swiper', {
      spaceBetween: 10,
      thumbs: { swiper: new Swiper('.pdp-thumbs-swiper', { spaceBetween: 8, slidesPerView: 4, watchSlidesProgress: true }) }
    });
  }
});
