<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts - Arabic Support -->
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
    :root {
        --deep-purple: #2d132c;
        --royal-gold: #c9a86a;
        --creamy-white: #fcf8f2;
        --gold-light: #e4d4b4;
        --purple-dark: #1a0a18;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Tajawal', sans-serif;
        background-color: var(--creamy-white);
        color: var(--deep-purple);
        overflow-x: hidden;
        min-height: 100vh;
    }
    
    /* Floating Ramadan Elements */
    .floating-decorations {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    
    .floating-icon {
        position: absolute;
        color: var(--royal-gold);
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }
    
    .floating-icon:nth-child(1) { top: 10%; left: 10%; font-size: 3rem; animation-delay: 0s; }
    .floating-icon:nth-child(2) { top: 20%; right: 15%; font-size: 2.5rem; animation-delay: 1s; }
    .floating-icon:nth-child(3) { top: 60%; left: 5%; font-size: 2rem; animation-delay: 2s; }
    .floating-icon:nth-child(4) { top: 70%; right: 10%; font-size: 3.5rem; animation-delay: 1.5s; }
    .floating-icon:nth-child(5) { top: 40%; right: 5%; font-size: 2.2rem; animation-delay: 0.5s; }
    .floating-icon:nth-child(6) { top: 85%; left: 20%; font-size: 2.8rem; animation-delay: 2.5s; }
    
    @keyframes float {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    
    /* Navbar */
    .ramadan-navbar {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        box-shadow: 0 4px 20px rgba(45, 19, 44, 0.3);
        padding: 1rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .navbar-brand-ramadan {
        font-family: 'Dancing Script', cursive;
        font-size: 3rem;
        font-weight: 600;
        background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none !important;
        line-height: 1.1;
        transition: all 0.3s ease;
        filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
    }
    
    .navbar-brand-ramadan .logo-accent {
        display: none;
    }
    
    .navbar-brand-ramadan .logo-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 5px;
        display: block;
        margin-top: 0.3rem;
        text-transform: uppercase;
        font-weight: 300;
        background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .navbar-brand-ramadan:hover {
        opacity: 0.85;
        transform: scale(1.02);
    }
    
    .nav-link-ramadan {
        color: var(--creamy-white) !important;
        font-weight: 500;
        margin: 0 0.5rem;
        transition: all 0.3s;
        text-decoration: none;
        padding: 0.5rem 1rem;
    }
    
    .nav-link-ramadan:hover {
        color: var(--royal-gold) !important;
        transform: translateY(-2px);
    }
    
    .nav-icon-ramadan {
        color: var(--royal-gold);
        font-size: 1.3rem;
        margin-left: 1rem;
        transition: all 0.3s;
        position: relative;
    }
    
    .nav-icon-ramadan:hover {
        color: var(--gold-light);
        transform: scale(1.1);
    }
    
    .cart-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: linear-gradient(135deg, #ff4757, #dc3545);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.5);
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    /* Page Container */
    .page-container {
        min-height: calc(100vh - 200px);
        padding: 2rem 0;
        position: relative;
        z-index: 2;
    }
    
    /* Buttons */
    .btn-ramadan {
        background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
        color: var(--deep-purple);
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
    }
    
    .btn-ramadan:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(201, 168, 106, 0.5);
        color: var(--deep-purple);
    }
    
    .btn-ramadan-secondary {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        color: var(--royal-gold);
        border: 2px solid var(--royal-gold);
        padding: 0.8rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-ramadan-secondary:hover {
        background: var(--royal-gold);
        color: var(--deep-purple);
        transform: translateY(-3px);
    }
    
    /* Cards */
    .card-ramadan {
        background: white;
        border-radius: 15px;
        border: 2px solid transparent;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(45, 19, 44, 0.1);
    }
    
    .card-ramadan:hover {
        border-color: var(--royal-gold);
        box-shadow: 0 10px 30px rgba(201, 168, 106, 0.3);
        transform: translateY(-5px);
    }
    
    /* Section Titles */
    .section-title-ramadan {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--deep-purple);
        margin-bottom: 2rem;
        position: relative;
        text-align: center;
    }
    
    .section-title-ramadan::after {
        content: '';
        display: block;
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        margin: 1rem auto 0;
    }
    
    /* Footer */
    .footer-ramadan {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        color: var(--creamy-white);
        padding: 2rem 0 1rem;
        margin-top: 4rem;
    }
    
    .footer-ramadan h5 {
        font-family: 'Dancing Script', cursive;
        font-weight: 600;
        font-size: 2.5rem;
        background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.1;
        filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
    }
    
    .footer-ramadan h5 span:not(.logo-accent) {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 5px;
        text-transform: uppercase;
        font-weight: 300;
        background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .footer-ramadan .logo-accent {
        display: none;
    }
    
    .footer-ramadan a {
        color: var(--gold-light);
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer-ramadan a:hover {
        color: var(--royal-gold);
    }
    
    /* Forms */
    .form-control-ramadan {
        border: 2px solid var(--gold-light);
        border-radius: 10px;
        padding: 0.8rem;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-control-ramadan:focus {
        border-color: var(--royal-gold);
        box-shadow: 0 0 15px rgba(201, 168, 106, 0.3);
        outline: none;
    }
    
    textarea.form-control-ramadan {
        border: 2px solid var(--gold-light);
        border-radius: 12px;
        padding: 1rem;
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        min-height: 80px;
        background: linear-gradient(to bottom, #ffffff 0%, #fefdfb 100%);
        transition: all 0.3s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    textarea.form-control-ramadan:hover {
        border-color: var(--gold-color);
        box-shadow: 0 2px 8px rgba(201, 168, 106, 0.2);
    }
    
    textarea.form-control-ramadan:focus {
        border-color: var(--royal-gold);
        box-shadow: 0 0 20px rgba(201, 168, 106, 0.4), inset 0 2px 4px rgba(201, 168, 106, 0.1);
        outline: none;
        background: #ffffff;
        transform: translateY(-1px);
    }
    
    textarea.form-control-ramadan::placeholder {
        color: #999;
        font-style: italic;
        opacity: 0.7;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar {
        width: 8px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-thumb {
        background: var(--gold-light);
        border-radius: 10px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-thumb:hover {
        background: var(--gold-color);
    }
    
    /* Alerts */
    .alert-ramadan {
        border-radius: 10px;
        border: 2px solid var(--royal-gold);
        background: rgba(201, 168, 106, 0.1);
        color: var(--deep-purple);
    }
</style>
