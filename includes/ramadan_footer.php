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

<!-- ======== CHATBOT WIDGET ======== -->
<div id="chatbot-widget">
    <!-- Toggle Button -->
    <button id="chatbot-toggle" onclick="toggleChatbot()" aria-label="Chat with us">
        <i class="fas fa-comments" id="chatbot-icon-open"></i>
        <i class="fas fa-times" id="chatbot-icon-close" style="display:none;"></i>
    </button>

    <!-- Chat Window -->
    <div id="chatbot-window" style="display:none;">
        <div id="chatbot-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-headset" style="font-size:1rem;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:0.95rem;">Poshy Store</div>
                    <div style="font-size:0.7rem;opacity:0.8;"><i class="fas fa-circle" style="color:#4ade80;font-size:6px;margin-right:3px;"></i>Online</div>
                </div>
            </div>
            <button onclick="toggleChatbot()" style="background:none;border:none;color:white;font-size:1.1rem;cursor:pointer;padding:4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="chatbot-body">
            <div class="chat-msg bot-msg">
                <div class="chat-bubble bot">👋 Hi! How can we help you today?<br>Choose a topic below:</div>
            </div>
            <div id="chatbot-options">
                <button class="chatbot-option-btn" onclick="chatbotAnswer('discount')">
                    <i class="fas fa-tag"></i> Discounts & Offers
                </button>
                <button class="chatbot-option-btn" onclick="chatbotAnswer('instagram')">
                    <i class="fab fa-instagram"></i> Instagram Page
                </button>
                <button class="chatbot-option-btn" onclick="chatbotAnswer('delivery')">
                    <i class="fas fa-truck"></i> Delivery Info
                </button>
                <button class="chatbot-option-btn" onclick="chatbotAnswer('support')">
                    <i class="fas fa-phone-alt"></i> Customer Service
                </button>
            </div>
            <div id="chatbot-answers"></div>
        </div>
    </div>
</div>

<style>
#chatbot-toggle {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    width: 56px; height: 56px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, #6c3fa0, #c9a86a);
    color: white; font-size: 1.5rem; cursor: pointer;
    box-shadow: 0 4px 20px rgba(108,63,160,0.4);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex; align-items: center; justify-content: center;
}
#chatbot-toggle:hover { transform: scale(1.1); box-shadow: 0 6px 25px rgba(108,63,160,0.5); }

#chatbot-window {
    position: fixed; bottom: 90px; right: 24px; z-index: 9998;
    width: 340px; max-height: 450px; border-radius: 16px;
    background: #fff; box-shadow: 0 8px 40px rgba(0,0,0,0.18);
    overflow: hidden; display: flex; flex-direction: column;
    animation: chatSlideUp 0.3s ease-out;
}
@keyframes chatSlideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

#chatbot-header {
    background: linear-gradient(135deg, #6c3fa0, #8b5fc7);
    color: white; padding: 12px 16px;
    display: flex; justify-content: space-between; align-items: center;
}

#chatbot-body {
    padding: 16px; overflow-y: auto; max-height: 340px; flex: 1;
    display: flex; flex-direction: column; gap: 10px;
}

.chat-bubble {
    padding: 10px 14px; border-radius: 14px; font-size: 0.88rem; line-height: 1.5;
    max-width: 90%; word-wrap: break-word;
}
.chat-bubble.bot {
    background: linear-gradient(135deg, #f3f0ff, #f8f5ff);
    color: #333; border-bottom-left-radius: 4px;
    border: 1px solid rgba(108,63,160,0.12);
}
.chat-bubble.user {
    background: linear-gradient(135deg, #6c3fa0, #8b5fc7);
    color: white; border-bottom-right-radius: 4px;
    align-self: flex-end;
}
.chat-msg { display: flex; }
.chat-msg.bot-msg { justify-content: flex-start; }
.chat-msg.user-msg { justify-content: flex-end; }

#chatbot-options { display: flex; flex-direction: column; gap: 6px; margin-top: 4px; }

.chatbot-option-btn {
    background: white; border: 1.5px solid rgba(108,63,160,0.25);
    border-radius: 10px; padding: 10px 14px; text-align: left;
    font-size: 0.85rem; font-weight: 600; color: #6c3fa0;
    cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
}
.chatbot-option-btn:hover {
    background: linear-gradient(135deg, #f3f0ff, #fff9f5);
    border-color: #6c3fa0; transform: translateX(4px);
}
.chatbot-option-btn i { font-size: 1rem; width: 20px; text-align: center; }

.chat-bubble a {
    color: #6c3fa0; font-weight: 700; text-decoration: underline;
}
.chat-bubble.bot a { color: #6c3fa0; }
.back-btn {
    background: none; border: 1.5px solid rgba(108,63,160,0.25);
    border-radius: 8px; padding: 6px 12px; font-size: 0.8rem;
    color: #6c3fa0; cursor: pointer; margin-top: 8px;
    transition: all 0.2s;
}
.back-btn:hover { background: #f3f0ff; }

@media (max-width: 400px) {
    #chatbot-window { width: calc(100vw - 32px); right: 16px; bottom: 84px; }
}
</style>

<script>
function toggleChatbot() {
    const win = document.getElementById('chatbot-window');
    const iconOpen = document.getElementById('chatbot-icon-open');
    const iconClose = document.getElementById('chatbot-icon-close');
    const isOpen = win.style.display !== 'none';
    win.style.display = isOpen ? 'none' : 'flex';
    iconOpen.style.display = isOpen ? 'inline' : 'none';
    iconClose.style.display = isOpen ? 'none' : 'inline';
}

function chatbotAnswer(topic) {
    const answers = {
        discount: {
            q: 'Discounts & Offers',
            a: '🎉 <strong>Welcome Offer!</strong><br>Use code <strong>WELCOME</strong> for a discount on your first order!<br><br>Follow us on social media for exclusive deals and flash sales!'
        },
        instagram: {
            q: 'Instagram Page',
            a: '📸 Follow us on Instagram for the latest products and offers!<br><br><a href="https://www.instagram.com/posh_.lifestyle?igsh=ZWM1MmxkNno3Z3V0&utm_source=qr" target="_blank"><i class="fab fa-instagram"></i> @posh_.lifestyle</a>'
        },
        delivery: {
            q: 'Delivery Info',
            a: '🚚 <strong>Delivery Policy:</strong><br><br>• Orders <strong>above 35 JOD</strong> → <span style="color:#28a745;font-weight:700;">FREE delivery!</span><br>• Orders below 35 JOD → <strong>2 JOD</strong> delivery fee<br><br>We deliver across Jordan! 🇯🇴'
        },
        support: {
            q: 'Customer Service',
            a: '📞 <strong>Contact Us:</strong><br><br>• <a href="https://wa.me/962770058416" target="_blank"><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp: +962 7 7005 8416</a><br>• <a href="tel:+962770058416"><i class="fas fa-phone-alt"></i> Call: +962 7 7005 8416</a><br>• <a href="mailto:info@poshystore.com"><i class="fas fa-envelope"></i> info@poshystore.com</a><br><br>We\'re happy to help! 💜'
        }
    };

    const data = answers[topic];
    if (!data) return;

    const optionsEl = document.getElementById('chatbot-options');
    const answersEl = document.getElementById('chatbot-answers');

    optionsEl.style.display = 'none';
    answersEl.innerHTML = `
        <div class="chat-msg user-msg"><div class="chat-bubble user">${data.q}</div></div>
        <div class="chat-msg bot-msg"><div class="chat-bubble bot">${data.a}</div></div>
        <button class="back-btn" onclick="chatbotReset()"><i class="fas fa-arrow-left"></i> Back to topics</button>
    `;
}

function chatbotReset() {
    document.getElementById('chatbot-options').style.display = 'flex';
    document.getElementById('chatbot-answers').innerHTML = '';
}
</script>
