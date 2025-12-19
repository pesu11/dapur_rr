<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

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
    <title>Kebijakan Privasi - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
            font-family: 'Inter', 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            min-height: 100vh;
        }
        
        /* HEADER SAMA PERSIS DENGAN SYARAT-KETENTUAN.PHP */
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
        
        /* Hero Section */
        .hero {
            background: var(--gradient-1);
            color: white;
            padding: 5rem 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.6;
            animation: moveGrid 20s linear infinite;
        }

        @keyframes moveGrid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 3px 5px 15px rgba(0,0,0,0.3);
            letter-spacing: -1px;
        }
        
        .hero p {
            font-size: 1.4rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.95;
            text-shadow: 2px 3px 8px rgba(0,0,0,0.2);
            font-weight: 500;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 4rem auto;
            padding: 0 40px;
        }
        
        .last-updated {
            color: var(--accent);
            font-style: italic;
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #FFF5F8 0%, #FFF0F5 100%);
            border-radius: 15px;
            text-align: center;
            font-weight: 600;
            border: 2px solid rgba(255, 105, 180, 0.2);
            box-shadow: var(--shadow-sm);
        }
        
        .content-box {
            background: white;
            padding: 4rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
        }

        .content-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .section {
            margin-bottom: 3rem;
            padding-bottom: 2.5rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 3px solid var(--gradient-1);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        .section h3 {
            font-size: 1.4rem;
            color: var(--primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .section h3::before {
            content: '🔒';
            font-size: 1.2rem;
        }
        
        .section p {
            color: #4B5563;
            margin-bottom: 1.2rem;
            line-height: 1.8;
            font-size: 1.1rem;
        }
        
        .section ul, .section ol {
            margin-left: 2.5rem;
            color: #4B5563;
            margin-bottom: 1.5rem;
        }
        
        .section li {
            margin-bottom: 1rem;
            padding-left: 0.8rem;
            line-height: 1.7;
        }
        
        .section ul li::before {
            content: '✅';
            margin-right: 1rem;
        }
        
        .section ol {
            counter-reset: item;
        }
        
        .section ol li {
            counter-increment: item;
            position: relative;
        }
        
        .section ol li::before {
            content: counter(item) '.';
            position: absolute;
            left: -2.5rem;
            color: var(--primary);
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #E8F4FD 0%, #D6E9FF 100%);
            border-left: 5px solid #3B82F6;
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
        }
        
        .highlight-box strong {
            color: #1E40AF;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        
        .important-box {
            background: linear-gradient(135deg, #FFF3CD 0%, #FFE5B4 100%);
            border-left: 5px solid #FFC107;
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.1);
        }
        
        .important-box strong {
            color: #856404;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .security-box {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            border-left: 5px solid #10B981;
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);
        }
        
        .security-box strong {
            color: #065F46;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        
        .contact-box {
            background: var(--gradient-1);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            margin-top: 4rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .contact-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.6;
        }
        
        .contact-box h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
            font-weight: 800;
        }
        
        .contact-box p {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
            opacity: 0.95;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .contact-box a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1.5rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .contact-box a:hover {
            background: white;
            color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
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
            .hero h1 {
                font-size: 2.8rem;
            }

            .content-box {
                padding: 3rem;
            }

            .section h2 {
                font-size: 1.8rem;
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

            .hero {
                padding: 4rem 20px;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .container {
                padding: 0 20px;
            }

            .content-box {
                padding: 2.5rem;
            }

            .section h2 {
                font-size: 1.6rem;
            }

            .section h3 {
                font-size: 1.2rem;
            }

            .contact-box {
                padding: 2.5rem;
            }

            .contact-box h3 {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.8rem;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .content-box {
                padding: 2rem;
            }

            .section h2 {
                font-size: 1.4rem;
            }

            .section ul, .section ol {
                margin-left: 1.5rem;
            }

            .section li {
                margin-bottom: 0.8rem;
            }

            .contact-box {
                padding: 2rem 1.5rem;
            }

            .contact-box h3 {
                font-size: 1.4rem;
            }

            .contact-box p {
                font-size: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
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
                        <?php if (isset($wishlistCount)): ?>
                        <a href="wishlist.php" class="wishlist-icon">
                            ❤️
                            <?php if ($wishlistCount > 0): ?>
                            <span class="badge"><?php echo $wishlistCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        <?php if (isset($cartCount)): ?>
                        <a href="keranjang.php" class="cart-icon">
                            🛒
                            <?php if ($cartCount > 0): ?>
                            <span class="badge"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
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

    <div class="hero">
        <div class="hero-content">
            <h1>Kebijakan Privasi</h1>
            <p>Perlindungan data pribadi Anda adalah prioritas kami</p>
        </div>
    </div>

    <div class="container">
        <div class="last-updated">
            🔒 <strong>Terakhir diperbarui:</strong> <?php echo date('d F Y'); ?>
        </div>

        <div class="content-box">
            <div class="section">
                <h2>1. Pendahuluan</h2>
                <p>
                    Selamat datang di <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. Kami sangat menghargai privasi Anda dan berkomitmen 
                    untuk melindungi data pribadi Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, 
                    mengungkapkan, dan melindungi informasi Anda ketika Anda menggunakan layanan kami.
                </p>
                <p>
                    Dengan menggunakan situs web kami, Anda menyetujui pengumpulan dan penggunaan informasi sesuai dengan 
                    kebijakan ini.
                </p>
            </div>

            <div class="section">
                <h2>2. Informasi yang Kami Kumpulkan</h2>
                
                <h3>2.1 Informasi Pribadi</h3>
                <p>Kami dapat mengumpulkan informasi pribadi yang Anda berikan secara langsung, termasuk:</p>
                <ul>
                    <li><strong>Informasi Akun:</strong> Nama lengkap, alamat email, nomor telepon, password (terenkripsi)</li>
                    <li><strong>Informasi Pengiriman:</strong> Alamat lengkap, kode pos, nomor telepon penerima</li>
                    <li><strong>Informasi Pembayaran:</strong> Data transaksi, bukti pembayaran (tanpa menyimpan nomor kartu kredit)</li>
                    <li><strong>Informasi Pesanan:</strong> Riwayat pembelian, preferensi produk</li>
                </ul>

                <h3>2.2 Informasi yang Dikumpulkan Secara Otomatis</h3>
                <ul>
                    <li>Alamat IP dan lokasi geografis</li>
                    <li>Jenis browser dan perangkat</li>
                    <li>Halaman yang dikunjungi dan waktu kunjungan</li>
                    <li>Aktivitas browsing di situs kami</li>
                    <li>Cookies dan teknologi pelacakan serupa</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Bagaimana Kami Menggunakan Informasi Anda</h2>
                <p>Kami menggunakan informasi yang dikumpulkan untuk:</p>
                <ol>
                    <li><strong>Memproses Pesanan:</strong> Memproses, mengkonfirmasi, dan mengirimkan pesanan Anda</li>
                    <li><strong>Komunikasi:</strong> Mengirimkan konfirmasi pesanan, update status pengiriman, dan notifikasi penting</li>
                    <li><strong>Layanan Pelanggan:</strong> Menanggapi pertanyaan, keluhan, dan permintaan dukungan</li>
                    <li><strong>Personalisasi:</strong> Menyesuaikan pengalaman berbelanja dan memberikan rekomendasi produk</li>
                    <li><strong>Marketing:</strong> Mengirimkan promosi, penawaran khusus, dan newsletter (dengan persetujuan Anda)</li>
                    <li><strong>Keamanan:</strong> Mencegah penipuan dan aktivitas tidak sah</li>
                    <li><strong>Analisis:</strong> Memahami bagaimana layanan kami digunakan untuk peningkatan</li>
                    <li><strong>Kepatuhan Hukum:</strong> Mematuhi kewajiban hukum dan peraturan yang berlaku</li>
                </ol>
            </div>

            <div class="section">
                <h2>4. Pembagian Informasi</h2>
                <p>Kami tidak menjual atau menyewakan informasi pribadi Anda kepada pihak ketiga. Namun, kami dapat membagikan 
                informasi dengan:</p>
                
                <h3>4.1 Penyedia Layanan</h3>
                <ul>
                    <li><strong>Kurir Pengiriman:</strong> Untuk mengirimkan pesanan Anda</li>
                    <li><strong>Payment Gateway:</strong> Untuk memproses pembayaran</li>
                    <li><strong>Layanan IT:</strong> Untuk hosting dan maintenance website</li>
                </ul>

                <h3>4.2 Kewajiban Hukum</h3>
                <p>Kami dapat mengungkapkan informasi jika diwajibkan oleh hukum, perintah pengadilan, atau proses hukum lainnya.</p>

                <h3>4.3 Transfer Bisnis</h3>
                <p>Jika terjadi merger, akuisisi, atau penjualan aset, informasi Anda dapat ditransfer ke pemilik baru.</p>
            </div>

            <div class="section">
                <h2>5. Keamanan Data</h2>
                <p>Kami menerapkan langkah-langkah keamanan teknis dan organisasi yang sesuai untuk melindungi data Anda:</p>
                <ul>
                    <li>Enkripsi data sensitif menggunakan teknologi SSL/TLS</li>
                    <li>Password terenkripsi dengan algoritma hashing yang kuat (bcrypt)</li>
                    <li>Akses terbatas ke data pribadi hanya untuk karyawan yang berwenang</li>
                    <li>Pemantauan sistem secara berkala untuk mendeteksi aktivitas mencurigakan</li>
                    <li>Backup data secara rutin</li>
                    <li>Firewall dan sistem keamanan jaringan</li>
                </ul>

                <div class="security-box">
                    <strong>🛡️ Catatan Keamanan:</strong> Meskipun kami berusaha keras melindungi data Anda, tidak ada metode 
                    transmisi internet atau penyimpanan elektronik yang 100% aman. Kami tidak dapat menjamin keamanan absolut.
                </div>
            </div>

            <div class="section">
                <h2>6. Cookies dan Teknologi Pelacakan</h2>
                <p>Kami menggunakan cookies dan teknologi serupa untuk:</p>
                <ul>
                    <li>Mengingat preferensi dan pengaturan Anda</li>
                    <li>Memahami bagaimana Anda menggunakan situs kami</li>
                    <li>Menyimpan item di keranjang belanja Anda</li>
                    <li>Menyediakan fitur keamanan</li>
                    <li>Menganalisis kinerja situs</li>
                </ul>
                <p>Anda dapat mengatur browser Anda untuk menolak cookies, namun beberapa fitur situs mungkin tidak berfungsi dengan baik.</p>
            </div>

            <div class="section">
                <h2>7. Hak Anda</h2>
                <p>Anda memiliki hak untuk:</p>
                <ul>
                    <li><strong>Akses:</strong> Meminta salinan data pribadi yang kami simpan tentang Anda</li>
                    <li><strong>Koreksi:</strong> Meminta kami memperbaiki data yang tidak akurat atau tidak lengkap</li>
                    <li><strong>Penghapusan:</strong> Meminta penghapusan data pribadi Anda (dengan batasan tertentu)</li>
                    <li><strong>Pembatasan:</strong> Meminta pembatasan pemrosesan data Anda</li>
                    <li><strong>Portabilitas:</strong> Menerima data Anda dalam format yang dapat dibaca mesin</li>
                    <li><strong>Keberatan:</strong> Menolak pemrosesan data untuk tujuan marketing</li>
                    <li><strong>Penarikan Persetujuan:</strong> Menarik persetujuan kapan saja tanpa mempengaruhi legalitas pemrosesan sebelumnya</li>
                </ul>
                <p>Untuk menggunakan hak-hak ini, silakan hubungi kami melalui informasi kontak di bawah.</p>
            </div>

            <div class="section">
                <h2>8. Penyimpanan Data</h2>
                <p>Kami menyimpan data pribadi Anda selama:</p>
                <ul>
                    <li>Akun Anda aktif</li>
                    <li>Diperlukan untuk menyediakan layanan kepada Anda</li>
                    <li>Diperlukan untuk mematuhi kewajiban hukum</li>
                    <li>Diperlukan untuk menyelesaikan sengketa</li>
                    <li>Diperlukan untuk menegakkan perjanjian kami</li>
                </ul>
                <p>Setelah periode penyimpanan berakhir, kami akan menghapus atau menganonimkan data Anda dengan aman.</p>
            </div>

            <div class="section">
                <h2>9. Privasi Anak-anak</h2>
                <p>
                    Layanan kami tidak ditujukan untuk anak-anak di bawah usia 13 tahun. Kami tidak dengan sengaja mengumpulkan 
                    informasi pribadi dari anak-anak di bawah 13 tahun. Jika Anda adalah orang tua atau wali dan menyadari bahwa 
                    anak Anda telah memberikan informasi pribadi kepada kami, silakan hubungi kami.
                </p>
            </div>

            <div class="section">
                <h2>10. Link ke Situs Pihak Ketiga</h2>
                <p>
                    Situs kami mungkin berisi link ke situs web pihak ketiga. Kami tidak bertanggung jawab atas praktik privasi 
                    atau konten situs tersebut. Kami mendorong Anda untuk membaca kebijakan privasi setiap situs yang Anda kunjungi.
                </p>
            </div>

            <div class="section">
                <h2>11. Perubahan Kebijakan Privasi</h2>
                <p>
                    Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Kami akan memberi tahu Anda tentang 
                    perubahan dengan memposting kebijakan baru di halaman ini dan memperbarui tanggal "Terakhir diperbarui" 
                    di atas.
                </p>
                <p>
                    Perubahan signifikan akan kami beritahukan melalui email atau pemberitahuan yang jelas di situs kami. 
                    Anda disarankan untuk meninjau Kebijakan Privasi ini secara berkala.
                </p>
            </div>

            <div class="section">
                <h2>12. Yurisdiksi</h2>
                <p>
                    Kebijakan Privasi ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia. Setiap sengketa 
                    yang timbul dari kebijakan ini akan diselesaikan di pengadilan yang berwenang di Indonesia.
                </p>
            </div>

            <div class="contact-box">
                <h3>📧 Pertanyaan tentang Privasi?</h3>
                <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami:</p>
                <p>📞 Telepon: <?php echo htmlspecialchars($pengaturan['no_telepon']); ?></p>
                <p>✉️ Email: <?php echo htmlspecialchars($pengaturan['email']); ?></p>
                <p>📍 Alamat: <?php echo htmlspecialchars($pengaturan['alamat']); ?></p>
                <a href="kontak.php">
                    <span>📩 Hubungi Tim Privasi Kami</span>
                    <span>→</span>
                </a>
            </div>

            <div class="important-box" style="margin-top: 3rem; text-align: center;">
                <p style="color: #856404; font-weight: 600;">
                    Dengan menggunakan layanan <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>, Anda menyetujui Kebijakan Privasi ini.
                </p>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. Hak Cipta Dilindungi.</p>
            <p style="margin-top: 1rem; font-size: 0.9rem; color: rgba(255,255,255,0.6);">
                <a href="syarat-ketentuan.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 10px;">Syarat & Ketentuan</a> | 
                <a href="kebijakan-privasi.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 10px;">Kebijakan Privasi</a> | 
                <a href="kontak.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 10px;">Kontak Kami</a>
            </p>
        </div>
    </footer>

    <script>
        // Animasi untuk konten saat scroll
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = 'all 0.6s ease-out';
                observer.observe(section);
            });
            
            // Highlight section yang sedang dilihat
            const sectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.background = 'linear-gradient(135deg, #FFF9FA 0%, #FFF5F8 100%)';
                        entry.target.style.borderRadius = '15px';
                        entry.target.style.padding = '2.5rem';
                        entry.target.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            entry.target.style.background = '';
                            entry.target.style.borderRadius = '';
                            entry.target.style.padding = '';
                        }, 2000);
                    }
                });
            }, { threshold: 0.3 });
            
            sections.forEach(section => {
                sectionObserver.observe(section);
            });
            
            // Smooth scroll untuk anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            window.scrollTo({
                                top: targetElement.offsetTop - 100,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
            
            // Efek hover untuk kotak kontak
            const contactBox = document.querySelector('.contact-box');
            if (contactBox) {
                contactBox.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                    this.style.boxShadow = '0 15px 50px rgba(255, 20, 147, 0.3)';
                });
                
                contactBox.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = 'var(--shadow-lg)';
                });
            }
            
            // Update tanggal terakhir diubah secara dinamis
            const lastUpdated = document.querySelector('.last-updated');
            if (lastUpdated) {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                const formattedDate = now.toLocaleDateString('id-ID', options);
                lastUpdated.innerHTML = `🔒 <strong>Terakhir diperbarui:</strong> ${formattedDate}`;
            }
            
            // Tambahkan ikon dinamis untuk setiap poin
            const liItems = document.querySelectorAll('.section ul li');
            liItems.forEach(li => {
                const text = li.textContent;
                if (text.includes('Akses:')) {
                    li.innerHTML = li.innerHTML.replace('Akses:', '👁️ <strong>Akses:</strong>');
                } else if (text.includes('Koreksi:')) {
                    li.innerHTML = li.innerHTML.replace('Koreksi:', '✏️ <strong>Koreksi:</strong>');
                } else if (text.includes('Penghapusan:')) {
                    li.innerHTML = li.innerHTML.replace('Penghapusan:', '🗑️ <strong>Penghapusan:</strong>');
                } else if (text.includes('Pembatasan:')) {
                    li.innerHTML = li.innerHTML.replace('Pembatasan:', '🚫 <strong>Pembatasan:</strong>');
                } else if (text.includes('Portabilitas:')) {
                    li.innerHTML = li.innerHTML.replace('Portabilitas:', '📤 <strong>Portabilitas:</strong>');
                } else if (text.includes('Keberatan:')) {
                    li.innerHTML = li.innerHTML.replace('Keberatan:', '✋ <strong>Keberatan:</strong>');
                } else if (text.includes('Penarikan Persetujuan:')) {
                    li.innerHTML = li.innerHTML.replace('Penarikan Persetujuan:', '↩️ <strong>Penarikan Persetujuan:</strong>');
                }
            });
        });
        
        // Tambahkan class active untuk navigasi
        const currentPage = 'kebijakan-privasi.php';
        document.querySelectorAll('nav a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>