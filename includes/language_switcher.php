<!-- Language Switcher Component -->
<style>
.language-switcher {
    position: relative;
    display: inline-block;
}

.lang-btn {
    background: linear-gradient(135deg, var(--royal-gold, #d4af37) 0%, var(--gold-color, #c9a961) 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
}

.lang-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.5);
}

.lang-btn i {
    font-size: 1.1rem;
}

.lang-flag {
    font-size: 1.2rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .lang-btn {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
    
    .lang-btn-text {
        display: none;
    }
}

/* RTL Support */
[dir="rtl"] .lang-btn {
    flex-direction: row-reverse;
}
</style>

<?php
require_once __DIR__ . '/language.php';

$other_lang = getOtherLang();
$other_lang_name = getLangName($other_lang);
$other_lang_flag = $other_lang === 'ar' ? '🇯🇴' : '🇬🇧';
?>

<div class="language-switcher">
    <a href="?lang=<?= $other_lang ?>" class="lang-btn" title="<?= $other_lang_name ?>">
        <span class="lang-flag"><?= $other_lang_flag ?></span>
        <span class="lang-btn-text"><?= $other_lang_name ?></span>
        <i class="fas fa-globe"></i>
    </a>
</div>
