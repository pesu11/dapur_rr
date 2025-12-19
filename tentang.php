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
    <title>Tentang Kami - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
        
        /* Hero Section */
        .hero {
            background: var(--gradient-1);
            color: white;
            padding: 6rem 40px;
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
            font-size: 4rem;
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
            max-width: 1400px;
            margin: 4rem auto;
            padding: 0 40px;
        }
        
        .about-section {
            background: white;
            padding: 4rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            margin-bottom: 3rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
        }

        .about-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin-bottom: 2rem;
            color: var(--dark);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        .section-content {
            color: var(--dark);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .section-content p {
            margin-bottom: 1.5rem;
            color: #4B5563;
        }

        .section-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin: 2.5rem 0 1rem;
            color: var(--primary);
            font-weight: 700;
        }

        .section-content ul {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }

        .section-content li {
            margin-bottom: 0.8rem;
            color: #4B5563;
            position: relative;
            padding-left: 1.5rem;
        }

        .section-content li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
            margin-top: 3rem;
        }
        
        .value-card {
            background: white;
            padding: 3rem 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .value-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-2);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }

        .value-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .value-card:hover::before {
            opacity: 0.05;
        }
        
        .value-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }
        
        .value-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin-bottom: 1.2rem;
            color: var(--dark);
            position: relative;
            z-index: 1;
            font-weight: 700;
        }
        
        .value-card p {
            color: #4B5563;
            line-height: 1.7;
            position: relative;
            z-index: 1;
            font-size: 1rem;
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
        @media (max-width: 1200px) {
            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.2rem;
            }

            .hero p {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2.4rem;
            }

            .about-section {
                padding: 3rem;
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
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .container {
                padding: 0 20px;
            }

            .about-section {
                padding: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .values-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .value-card {
                padding: 2.5rem 2rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.8rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .about-section {
                padding: 2rem;
            }

            .value-card {
                padding: 2rem 1.5rem;
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
                    <li><a href="tentang.php" class="active">Tentang</a></li>
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

    <div class="hero">
        <div class="hero-content">
            <h1>Tentang <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
            <p><?php echo htmlspecialchars($pengaturan['tagline']); ?></p>
        </div>
    </div>

    <div class="container">
        <div class="about-section">
            <h2 class="section-title">Cerita Kami</h2>
            <div class="section-content">
                <p>
                    Sejak didirikan, <?php echo htmlspecialchars($pengaturan['nama_toko']); ?> berdiri pada bulan Juli 2020, berawal dari masa pandemi yang membuat aktivitas banyak dilakukan di rumah. Sebelumnya, sejak tahun 2017, kami telah memiliki usaha berjualan kaos karakter secara offline di berbagai bazar. Namun, saat pandemi COVID-19 melanda, kegiatan bazar terhenti, dan usaha tersebut pun tidak lagi berjalan.
                </p>
                <p>
                    Dari situ, kami mulai berkreasi di dapur dengan membuat berbagai jenis kue rumahan. Awalnya hanya untuk mengisi waktu dan mencoba resep, hingga akhirnya memberanikan diri untuk menjual hasil buatan kami kepada teman-teman. Tak disangka, respon yang datang sangat positif — dan dari situlah lahir Dapur RR.
                </p>
                <p>
                    Berbekal semangat dan cinta dalam setiap adonan, kami terus berkembang hingga saat ini. Dapur RR berkomitmen untuk menghadirkan kue homemade yang lezat, higienis, dan penuh rasa hangat, cocok untuk segala momen kebersamaan.
                </p>
            </div>
        </div>

        <div class="about-section">
            <h2 class="section-title">Visi & Misi</h2>
            <div class="section-content">
                <h3>🎯 Visi</h3>
                <p>Menjadi toko kue terpercaya dan terdepan di Indonesia yang menghadirkan produk berkualitas dengan pelayanan terbaik.</p>
                
                <h3>🎯 Misi</h3>
                <ul>
                    <li>Menghasilkan produk kue berkualitas tinggi dengan cita rasa terbaik</li>
                    <li>Memberikan pelayanan yang ramah dan profesional kepada pelanggan</li>
                    <li>Terus berinovasi dalam menciptakan produk-produk baru</li>
                    <li>Menjaga kepercayaan pelanggan dengan konsistensi kualitas</li>
                </ul>
            </div>
        </div>

        <h2 class="section-title" style="text-align: center; margin-bottom: 3rem;">Nilai-Nilai Kami</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">✨</div>
                <h3>Kualitas</h3>
                <p>Kami selalu mengutamakan kualitas dalam setiap produk yang kami hasilkan dengan bahan-bahan terbaik.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">❤️</div>
                <h3>Kepuasan Pelanggan</h3>
                <p>Kepuasan pelanggan adalah prioritas utama kami. Kami berkomitmen memberikan pelayanan terbaik.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">🌟</div>
                <h3>Inovasi</h3>
                <p>Kami terus berinovasi menciptakan varian produk baru yang sesuai dengan selera pelanggan.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h3>Kepercayaan</h3>
                <p>Kami membangun kepercayaan melalui konsistensi kualitas dan kejujuran dalam berbisnis.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h3>Kecepatan</h3>
                <p>Kami memastikan setiap pesanan diproses dengan cepat tanpa mengorbankan kualitas.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">💯</div>
                <h3>Profesional</h3>
                <p>Tim kami bekerja secara profesional untuk memberikan hasil terbaik kepada pelanggan.</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
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

        document.querySelectorAll('.about-section, .value-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>