<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil artikel yang dipublikasikan dengan query yang diperbaiki
$artikelQuery = "SELECT a.*, u.nama as author 
                FROM artikel a 
                LEFT JOIN users u ON a.author_id = u.id 
                WHERE a.is_published = 1 
                ORDER BY a.created_at DESC";
$artikel = $conn->query($artikelQuery);

// Debug: Cek jumlah artikel
// echo "Jumlah artikel: " . $artikel->num_rows;

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
    <title>Blog & Artikel - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
        
        /* HEADER SAMA PERSIS DENGAN INDEX.PHP */
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

        /* STYLING KONTEN ARTIKEL */
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
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            text-shadow: 2px 4px 10px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }
        
        .container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .artikel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
        }
        
        .artikel-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.4s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            border: 2px solid transparent;
            position: relative;
        }
        
        .artikel-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .artikel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
            z-index: 2;
        }
        
        .artikel-image {
            width: 100%;
            height: 220px;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
        }

        .artikel-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        .artikel-content {
            padding: 2rem;
        }
        
        .artikel-meta {
            display: flex;
            gap: 1.2rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }
        
        .artikel-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            background: var(--light);
            border-radius: 8px;
            font-weight: 500;
        }
        
        .artikel-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .artikel-excerpt {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }
        
        .read-more {
            color: var(--primary);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .artikel-card:hover .read-more {
            color: var(--accent);
            transform: translateX(5px);
        }
        
        .empty-state {
            background: white;
            padding: 4rem 2rem;
            border-radius: 25px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
        }

        .empty-state h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #666;
            font-size: 1.1rem;
        }
        
        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 4rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
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

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }
            
            .artikel-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .container {
                padding: 0 20px;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero {
                padding: 3rem 20px;
            }

            .artikel-content {
                padding: 1.5rem;
            }

            .artikel-title {
                font-size: 1.4rem;
            }

            .artikel-meta {
                gap: 0.8rem;
            }

            .artikel-meta span {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
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

/* CSS untuk badge dan icon (jika belum ada) */
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
                    <li><a href="artikel.php" class="active">Blog</a></li>
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
        <h1>Blog & Artikel</h1>
        <p>Tips, resep, dan cerita seputar kue</p>
    </div>

    <div class="container">
        <?php if (!$artikel || $artikel->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-icon"></div>
            <h2>Belum Ada Artikel</h2>
            <p>Artikel akan segera hadir. Pantau terus halaman ini!</p>
        </div>
        <?php else: ?>
        <div class="artikel-grid">
            <?php while($a = $artikel->fetch_assoc()): ?>
            <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($a['slug']); ?>" class="artikel-card">
                <div class="artikel-image" 
                     <?php if (!empty($a['gambar'])): ?>
                     style="background-image: url('uploads/<?php echo htmlspecialchars($a['gambar']); ?>');"
                     <?php endif; ?>>
                    <?php if (empty($a['gambar'])): ?>
                        
                    <?php endif; ?>
                </div>
                <div class="artikel-content">
                    <div class="artikel-meta">
                        <span>✍️ <?php echo htmlspecialchars($a['author'] ?? 'Admin'); ?></span>
                        <span>📅 <?php echo date('d M Y', strtotime($a['created_at'])); ?></span>
                        <span>👁️ <?php echo number_format($a['views']); ?></span>
                    </div>
                    <h2 class="artikel-title"><?php echo htmlspecialchars($a['judul']); ?></h2>
                    <div class="artikel-excerpt">
                        <?php 
                        $excerpt = strip_tags($a['konten']);
                        echo htmlspecialchars(mb_substr($excerpt, 0, 150, 'UTF-8')); 
                        ?>...
                    </div>
                    <span class="read-more">Baca Selengkapnya →</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. Dibuat dengan ❤️</p>
        </div>
    </footer>
</body>
</html>