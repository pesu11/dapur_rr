<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Proses form kontak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $subjek = trim($_POST['subjek']);
    $pesan = trim($_POST['pesan']);
    
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid";
    }
    
    if (empty($pesan)) {
        $errors[] = "Pesan harus diisi";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO kontak_masuk (nama, email, subjek, pesan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Pesan Anda berhasil dikirim. Kami akan segera menghubungi Anda.');
            redirect('kontak.php');
        } else {
            $errors[] = "Gagal mengirim pesan";
        }
    }
}

$flash = getFlashMessage();
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
    <title>Kontak Kami - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
            max-width: 1400px;
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
        
        .alert {
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            animation: slideInDown 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .alert:hover::before {
            left: 100%;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left: 5px solid #10B981;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
            border-left: 5px solid #EF4444;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }
        
        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }
        
        .contact-info {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
        }

        .contact-info:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            color: var(--dark);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .info-item {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            align-items: flex-start;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(10px);
        }
        
        .info-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-2);
            color: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
            transition: all 0.3s ease;
        }

        .info-item:hover .info-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .info-content h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 700;
        }
        
        .info-content p {
            color: #4B5563;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .contact-form {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
        }

        .contact-form:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }
        
        input, textarea {
            width: 100%;
            padding: 1.2rem 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            background: white;
            color: var(--dark);
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1.3rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }
        
        .map-section {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
        }

        .map-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .error-list {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            border: 1px solid #FECACA;
            color: #991B1B;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
        }
        
        .error-list ul {
            margin-left: 1.5rem;
        }

        .error-list li {
            margin-bottom: 0.5rem;
            color: #991B1B;
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
        
        @media (max-width: 992px) {
            .contact-wrapper {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .page-title {
                font-size: 2.8rem;
            }

            .contact-info, .contact-form {
                padding: 2.5rem;
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

            .contact-info, .contact-form, .map-section {
                padding: 2rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .info-item {
                gap: 1rem;
            }

            .info-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.8rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .contact-info, .contact-form, .map-section {
                padding: 1.5rem;
            }

            .info-item {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .info-icon {
                align-self: center;
            }
        }
        /* Tambahkan di bagian user-menu styling */
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

.cart-icon, .wishlist-icon {
    position: relative;
    color: var(--dark);
    text-decoration: none;
    font-size: 1.3rem;
    transition: all 0.3s;
    padding: 0.5rem;
}

.cart-icon:hover, .wishlist-icon:hover {
    color: var(--accent);
    transform: translateY(-2px);
}

.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--gradient-1);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    box-shadow: 0 2px 10px rgba(255, 20, 147, 0.3);
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
                <li><a href="home.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <?php if ($isLoggedIn): ?>
                <li><a href="riwayat-pesanan.php">Pesanan</a></li>
                <li><a href="promo.php">Promo</a></li>
                <?php else: ?>
                <!-- Untuk user tidak login, tampilkan menu berbeda -->
                <li><a href="tentang.php">Tentang</a></li>
                <li><a href="artikel.php">Blog</a></li>
                <?php endif; ?>
                
            </ul>
        </nav>

        <div class="auth-buttons">
            <div class="user-menu">
                <?php if ($isLoggedIn): ?>
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
                <?php else: ?>
                    <!-- Untuk user tidak login -->
                    <a href="login.php" class="btn btn-secondary">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

    <div class="container">
        <h1 class="page-title">📞 Hubungi Kami</h1>
        <p class="page-subtitle">Kami siap membantu Anda. Jangan ragu untuk menghubungi kami melalui form di bawah ini atau informasi kontak yang tersedia!</p>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <strong>Terjadi Kesalahan:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="contact-wrapper">
            <div class="contact-info">
                <h2 class="section-title">Informasi Kontak</h2>
                
                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h3>Alamat</h3>
                        <p><?php echo htmlspecialchars($pengaturan['alamat']); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <div class="info-content">
                        <h3>Telepon</h3>
                        <p><?php echo htmlspecialchars($pengaturan['no_telepon']); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">✉️</div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars($pengaturan['email']); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">💬</div>
                    <div class="info-content">
                        <h3>WhatsApp</h3>
                        <p><?php echo htmlspecialchars($pengaturan['whatsapp'] ?? 'Tersedia melalui WhatsApp'); ?></p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">🕐</div>
                    <div class="info-content">
                        <h3>Jam Operasional</h3>
                        <p><?php echo htmlspecialchars($pengaturan['jam_buka']); ?></p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <h2 class="section-title">Kirim Saran</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" id="nama" name="nama" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="subjek">Subjek</label>
                        <input type="text" id="subjek" name="subjek" value="<?php echo isset($_POST['subjek']) ? htmlspecialchars($_POST['subjek']) : ''; ?>" placeholder="Subjek pesan Anda (opsional)">
                    </div>

                    <div class="form-group">
                        <label for="pesan">Pesan *</label>
                        <textarea id="pesan" name="pesan" required placeholder="Tulis pesan Anda di sini..."><?php echo isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Kirim</button>
                </form>
            </div>
        </div>

        <div class="map-section">
            <h2 class="section-title">Lokasi Kami</h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.538870271983!2d106.82822401531828!3d-6.36991946023176!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ef2670c4d627%3A0x8a1d2ec8fd0f4f58!2sDapur%20R%20R!5e0!3m2!1sid!2sid!4v1697446523456!5m2!1sid!2sid"
                    width="100%"
                    height="400"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
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

        document.querySelectorAll('.contact-info, .contact-form, .map-section, .info-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        // Auto hide alert
        const alert = document.querySelector('.alert');
        if(alert) {
            setTimeout(() => {
                alert.style.animation = 'slideOutUp 0.5s ease forwards';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }

        // Keyframe untuk slideOutUp
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutUp {
                to {
                    opacity: 0;
                    transform: translateY(-30px) scale(0.95);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>