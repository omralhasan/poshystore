<?php
// Calculate proper base path
$current_path = $_SERVER['PHP_SELF'];
$base_path = '';
if (strpos($current_path, '/pages/') !== false) {
    $base_path = '../../';
} else if (strpos($current_path, '/api/') !== false) {
    $base_path = '../';
}
?>

<!-- Footer -->
<footer class="footer-ramadan mt-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="mb-3">
                    Poshy<br>
                    <span style="font-size: 0.7rem; letter-spacing: 5px; font-weight: 300;">STORE</span>
                </h5>
                <p class="mb-2">All what you need in one place with our authentic products</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <h6 class="mb-3">Quick Links</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= $base_path ?>index.php" class="text-decoration-none" style="color: var(--gold-light);">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                    <a href="<?= $base_path ?>pages/shop/shop.php" class="text-decoration-none" style="color: var(--gold-light);">
                        <i class="fas fa-shopping-bag me-2"></i>Shop
                    </a>
                    <a href="<?= $base_path ?>pages/policies/privacy-policy.php" class="text-decoration-none" style="color: var(--gold-light);">
                        <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                    </a>
                    <a href="<?= $base_path ?>pages/policies/terms-of-service.php" class="text-decoration-none" style="color: var(--gold-light);">
                        <i class="fas fa-file-contract me-2"></i>Terms of Service
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <h6 class="mb-3">Connect With Us</h6>
                <div class="d-flex gap-3 mb-3">
                    <a href="https://www.facebook.com/share/1Am5FrXwQU/?mibextid=wwXIfr" target="_blank" rel="noopener" class="text-decoration-none" style="color: var(--gold-light); font-size: 1.5rem;">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/posh_.lifestyle?igsh=ZWM1MmxkNno3Z3V0&utm_source=qr" target="_blank" rel="noopener" class="text-decoration-none" style="color: var(--gold-light); font-size: 1.5rem;">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
                <p class="mb-0">
                    <i class="fas fa-envelope me-2"></i>info@poshystore.com
                </p>
            </div>
        </div>
        
        <div class="row mt-4 pt-4" style="border-top: 1px solid rgba(201, 168, 106, 0.2);">
            <div class="col-12 text-center">
                <p class="mb-0">© <?= date('Y') ?> Poshy Store. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
