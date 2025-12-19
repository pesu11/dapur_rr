<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil FAQ
$faq = $conn->query("SELECT * FROM faq WHERE is_active = 1 ORDER BY urutan ASC");

$isLoggedIn = isLoggedIn();
if ($isLoggedIn) {
    $cartCount = getCartCount();
    $wishlistCount = getWishlistCount();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #FF69B4;
            --secondary: #FFB6D9;
            --accent: #FF1493;
            --success: #10B981;
            --info: #3B82F6;
            --dark: #2C1810;
            --light: #FFF5F8;
            --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            --gradient-2: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
            --gradient-3: linear-gradient(135deg, #FFC1E3 0%, #FFB6D9 100%);
            --gradient-4: linear-gradient(135deg, #FF85A1 0%, #FF69B4 100%);
            --shadow-sm: 0 2px 15px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 25px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 40px rgba(255, 105, 180, 0.2);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            overflow-x: hidden;
        }

        /* Header seperti index.php */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-100%);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-top {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.2rem 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
            animation: fadeInLeft 0.8s ease;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .logo:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .logo-image {
            width: 65px;
            height: 65px;
            border-radius: 20px;
            object-fit: cover;
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.3);
            border: 3px solid rgba(255, 105, 180, 0.3);
            transition: all 0.4s ease;
            animation: rotate-scale 3s ease-in-out infinite;
        }

        @keyframes rotate-scale {
            0%, 100% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(5deg) scale(1.05);
            }
        }

        .logo:hover .logo-image {
            box-shadow: 0 8px 30px rgba(255, 105, 180, 0.5);
            transform: rotate(-5deg) scale(1.1);
            border-color: var(--accent);
        }

        .logo-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            letter-spacing: -0.5px;
            animation: gradient-shift 3s ease infinite;
        }

        @keyframes gradient-shift {
            0%, 100% {
                filter: hue-rotate(0deg);
            }
            50% {
                filter: hue-rotate(10deg);
            }
        }

        .logo-text p {
            font-size: 0.9rem;
            color: #FF69B4;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        nav {
            background: transparent;
            animation: fadeIn 1s ease 0.3s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        nav ul {
            display: flex;
            gap: 2.8rem;
            list-style: none;
            align-items: center;
        }

        nav a {
            color: #4B5563;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        nav a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: var(--gradient-1);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        nav a::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 0;
            height: 0;
            background: rgba(255, 105, 180, 0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: -1;
        }

        nav a:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        nav a:hover::before {
            width: 100%;
        }

        nav a:hover::after {
            width: 120%;
            height: 150%;
        }

        nav a.active {
            color: var(--accent);
        }

        nav a.active::before {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            animation: fadeInRight 0.8s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .btn {
            padding: 0.85rem 2rem;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.3);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-icon, .wishlist-icon {
            position: relative;
            color: var(--dark);
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .cart-icon:hover, .wishlist-icon:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .btn-logout {
            background: transparent;
            color: var(--dark);
            border: 2px solid var(--dark);
            padding: 0.7rem 1.5rem;
        }

        .btn-logout:hover {
            background: var(--dark);
            color: white;
        }
        
        .container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            text-align: center;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        .page-subtitle {
            text-align: center;
            color: #4B5563;
            margin-bottom: 4rem;
            font-size: 1.2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .faq-list {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            margin-bottom: 3rem;
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            padding-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            border-color: var(--primary);
        }
        
        .faq-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 1.5rem;
            background: linear-gradient(135deg, #FFF5F8 0%, #FFF0F5 100%);
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .faq-question:hover {
            background: var(--gradient-2);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .faq-question.active {
            background: var(--gradient-1);
            color: white;
            border-color: var(--accent);
        }
        
        .faq-question h3 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .faq-icon {
            font-size: 1.8rem;
            transition: transform 0.3s ease;
            color: var(--primary);
        }

        .faq-question:hover .faq-icon,
        .faq-question.active .faq-icon {
            color: white;
        }

        .faq-question.active .faq-icon {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            border-radius: 0 0 15px 15px;
        }
        
        .faq-answer.active {
            max-height: 500px;
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, #FFF9FB 0%, #FFF5F8 100%);
            border: 2px solid rgba(255, 105, 180, 0.1);
            border-top: none;
            border-radius: 0 0 15px 15px;
            margin-top: -2px;
        }
        
        .faq-answer p {
            color: #4B5563;
            line-height: 1.8;
            font-size: 1rem;
            margin: 0;
        }
        
        .contact-cta {
            background: var(--gradient-1);
            color: white;
            padding: 4rem 3rem;
            border-radius: 25px;
            text-align: center;
            margin-top: 3rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .contact-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.6;
        }
        
        .contact-cta h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
            font-weight: 800;
        }
        
        .contact-cta p {
            margin-bottom: 2.5rem;
            font-size: 1.2rem;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .btn-contact {
            padding: 1.2rem 3rem;
            background: white;
            color: var(--accent);
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }
        
        .btn-contact:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
            background: var(--accent);
            color: white;
        }
        
        footer {
            background: var(--dark);
            color: white;
            padding: 5rem 0 2rem;
            margin-top: 6rem;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            text-align: center;
        }

        .footer-content p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .page-title {
                font-size: 2.8rem;
            }

            .faq-list {
                padding: 2.5rem;
            }

            .contact-cta {
                padding: 3rem 2rem;
            }

            .contact-cta h2 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1rem 20px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .container {
                padding: 0 20px;
            }

            .page-title {
                font-size: 2.2rem;
            }

            .page-subtitle {
                font-size: 1.1rem;
                margin-bottom: 3rem;
            }

            .faq-list {
                padding: 2rem;
            }

            .faq-question {
                padding: 1.2rem;
            }

            .faq-question h3 {
                font-size: 1.1rem;
                gap: 1rem;
            }

            .faq-answer.active {
                padding: 1.5rem 1.2rem;
            }

            .contact-cta {
                padding: 2.5rem 1.5rem;
            }

            .contact-cta h2 {
                font-size: 1.8rem;
            }

            .contact-cta p {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.8rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .faq-list {
                padding: 1.5rem;
            }

            .faq-question {
                padding: 1rem;
            }

            .faq-question h3 {
                font-size: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .faq-icon {
                align-self: flex-end;
                margin-top: -2rem;
            }

            .contact-cta {
                padding: 2rem 1rem;
            }

            .contact-cta h2 {
                font-size: 1.6rem;
            }

            .contact-cta p {
                font-size: 1rem;
            }

            .btn-contact {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }
        .user-menu {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.user-menu a.user-name {
    color: var(--dark);
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 15px;
    transition: all 0.3s ease;
    background: transparent;
}

.user-menu a.user-name:hover {
    color: var(--accent);
    background: rgba(255, 105, 180, 0.1);
    transform: translateY(-2px);
}

.user-menu a.user-name.active {
    color: var(--accent);
    background: rgba(255, 105, 180, 0.1);
}

.btn-logout {
    background: transparent;
    color: var(--dark);
    border: 2px solid var(--dark);
    padding: 0.7rem 1.5rem;
    border-radius: 15px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}

.btn-logout:hover {
    background: var(--dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44, 24, 16, 0.3);
}
    </style>
</head>
<body>
   <header>
    <div class="header-top">
        <div class="logo">
            <?php 
            $logo_paths = [
                'uploads/logo/logo-dapurr.png',
                'uploads/logo/logo.png',
                'images/logo.png',
                'assets/logo.png',
                'logo.png'
            ];
            
            $logo_found = false;
            foreach($logo_paths as $path) {
                if(file_exists($path)) {
                    echo '<img src="' . $path . '" alt="Logo" class="logo-image">';
                    $logo_found = true;
                    break;
                }
            }
            
            if(!$logo_found) {
                echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #FF6B9D 0%, #FFC93C 100%); font-size: 2rem;">🧁</div>';
            }
            ?>
            <div class="logo-text">
                <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
                <p><?php echo htmlspecialchars($pengaturan['tagline']); ?></p>
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="<?php echo $isLoggedIn ? 'home.php' : 'index.php'; ?>">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                
                <?php if ($isLoggedIn): ?>
                    <!-- Menu untuk user yang sudah login -->
                    <li><a href="riwayat-pesanan.php">Pesanan</a></li>
                    <li><a href="promo.php">Promo</a></li>
                <?php else: ?>
                    <!-- Menu untuk guest (belum login) -->
                    <li><a href="testimoni.php">Testimoni</a></li>
                    <li><a href="tentang.php">Tentang</a></li>
                    <li><a href="artikel.php">Blog</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <!-- NAMA USER BISA DIKLIK UNTUK KE HALAMAN AKUN -->
                    <a href="akun.php" class="user-name">
                        👤 <?php echo htmlspecialchars($_SESSION['nama']); ?>
                    </a>
                    <a href="wishlist.php" class="wishlist-icon">
                        ❤️
                        <?php if ($wishlistCount > 0): ?>
                        <span class="badge"><?php echo $wishlistCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="keranjang.php" class="cart-icon">
                        🛒
                        <?php if ($cartCount > 0): ?>
                        <span class="badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

    <div class="container">
        <h1 class="page-title">❓ Frequently Asked Questions</h1>
        <p class="page-subtitle">Temukan jawaban atas pertanyaan yang sering diajukan tentang produk dan layanan kami</p>

        <div class="faq-list">
            <?php while($item = $faq->fetch_assoc()): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h3>
                        <span class="faq-icon">❓</span>
                        <?php echo htmlspecialchars($item['pertanyaan']); ?>
                    </h3>
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    <p><?php echo nl2br(htmlspecialchars($item['jawaban'])); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="contact-cta">
            <h2>Masih Ada Pertanyaan?</h2>
            <p>Jangan ragu untuk menghubungi kami. Tim customer service kami siap membantu Anda dengan senang hati!</p>
            <a href="kontak.php" class="btn-contact">📞 Hubungi Kami</a>
        </div>
    </div>

   <footer>
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. All Rights Reserved.</p>
        <p style="margin-top: 1rem;">
            <a href="faq.php" style="color: rgba(255,255,255,0.8); margin: 0 1rem;">FAQ</a>
            <a href="syarat-ketentuan.php" style="color: rgba(255,255,255,0.8); margin: 0 1rem;">Syarat & Ketentuan</a>
            <a href="kebijakan-privasi.php" style="color: rgba(255,255,255,0.8); margin: 0 1rem;">Kebijakan Privasi</a>
        </p>
    </div>
</footer>

    <script>
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('.faq-icon:last-child');
            
            // Toggle current FAQ
            element.classList.toggle('active');
            answer.classList.toggle('active');
            
            // Close all other FAQs
            document.querySelectorAll('.faq-question').forEach(item => {
                if (item !== element) {
                    item.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.faq-answer').forEach(item => {
                if (item !== answer) {
                    item.classList.remove('active');
                }
            });
        }

        // Smooth scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.faq-item, .contact-cta').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>