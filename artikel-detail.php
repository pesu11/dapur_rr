<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Slug artikel tidak valid'];
    header('Location: artikel.php');
    exit();
}

// Ambil detail artikel
$stmt = $conn->prepare("SELECT a.*, u.nama as author 
                       FROM artikel a 
                       LEFT JOIN users u ON a.author_id = u.id 
                       WHERE a.slug = ? AND a.is_published = 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Artikel tidak ditemukan'];
    header('Location: artikel.php');
    exit();
}

$artikel = $result->fetch_assoc();

// Update views
$updateStmt = $conn->prepare("UPDATE artikel SET views = views + 1 WHERE slug = ?");
$updateStmt->bind_param("s", $slug);
$updateStmt->execute();

// Artikel terkait
$artikelTerkait = $conn->query("SELECT * FROM artikel 
                                WHERE slug != '" . $conn->real_escape_string($slug) . "' 
                                AND is_published = 1 
                                ORDER BY created_at DESC 
                                LIMIT 3");

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
    <title><?php echo htmlspecialchars($artikel['judul']); ?> - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($artikel['konten']), 0, 150)); ?>">
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

        /* STYLING KONTEN ARTIKEL (SAMA SEBELUMNYA) */
        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 15px;
            text-decoration: none;
            margin-bottom: 2rem;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .back-link:hover {
            background: var(--gradient-1);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .artikel-container {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            margin-bottom: 3rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .artikel-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }
        
        .artikel-container:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .artikel-header {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .artikel-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        
        .artikel-meta {
            display: flex;
            gap: 2.5rem;
            color: #666;
            font-size: 1rem;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 10px;
            font-weight: 500;
        }
        
        .artikel-image {
            width: 100%;
            height: 400px;
            background: var(--gradient-2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
            margin-bottom: 2.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            position: relative;
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
        
        .artikel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 2;
        }
        
        .artikel-content {
            font-size: 1.1rem;
            color: var(--dark);
            line-height: 1.8;
        }
        
        .artikel-content p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .artikel-content h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-top: 2.5rem;
            margin-bottom: 1.2rem;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .artikel-content h3 {
            font-family: 'Playfair Display', serif;
            color: var(--accent);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .artikel-content ul, .artikel-content ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .artikel-content li {
            margin-bottom: 0.7rem;
            position: relative;
        }
        
        .artikel-content ul li::before {
            content: '🌸';
            position: absolute;
            left: -2rem;
            color: var(--primary);
        }
        
        .share-section {
            margin-top: 3rem;
            padding-top: 2.5rem;
            border-top: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .share-section h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .share-buttons {
            display: flex;
            gap: 1.2rem;
            flex-wrap: wrap;
        }
        
        .share-btn {
            padding: 1rem 2rem;
            border-radius: 15px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            box-shadow: var(--shadow-sm);
        }
        
        .share-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-facebook {
            background: linear-gradient(135deg, #3b5998 0%, #2d4373 100%);
        }
        
        .btn-twitter {
            background: linear-gradient(135deg, #1da1f2 0%, #0d8bd9 100%);
        }
        
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366 0%, #128C7E 100%);
        }
        
        .related-articles {
            margin-top: 4rem;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2.5rem;
            text-align: center;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .related-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: inherit;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
        }
        
        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .related-image {
            width: 100%;
            height: 180px;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            overflow: hidden;
            position: relative;
        }
        
        .related-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 2;
        }
        
        .related-content {
            padding: 1.5rem;
        }
        
        .related-title {
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: var(--dark);
            font-size: 1.1rem;
            line-height: 1.4;
            font-family: 'Playfair Display', serif;
        }
        
        .related-date {
            color: #999;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            margin-top: 2rem;
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
            
            .artikel-container {
                padding: 2rem 1.5rem;
            }
            
            .artikel-title {
                font-size: 2.2rem;
            }
            
            .artikel-image {
                height: 250px;
                font-size: 3rem;
            }
            
            .artikel-meta {
                gap: 1rem;
            }
            
            .meta-item {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .share-buttons {
                flex-direction: column;
            }
            
            .share-btn {
                justify-content: center;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .artikel-title {
                font-size: 1.8rem;
            }
            
            .artikel-container {
                padding: 1.5rem 1rem;
            }
            
            .artikel-image {
                height: 200px;
                font-size: 2.5rem;
            }
            
            .artikel-content p {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
        }

        /* Animasi untuk konten artikel */
        .artikel-content {
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
                    <li><a href="artikel.php" class="active">Blog</a></li>
                    <li><a href="tentang.php">Tentang</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                </ul>
            </nav>

            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="keranjang.php" class="btn btn-secondary">🛒 Keranjang</a>
                    <a href="akun.php" class="btn btn-primary">👤 Akun</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="artikel.php" class="back-link">← Kembali ke Blog</a>

        <div class="artikel-container">
            <div class="artikel-header">
                <h1 class="artikel-title"><?php echo htmlspecialchars($artikel['judul']); ?></h1>
                <div class="artikel-meta">
                    <div class="meta-item">
                        <span>✍️</span>
                        <span><?php echo htmlspecialchars($artikel['author'] ?? 'Admin'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        <span><?php echo date('d F Y', strtotime($artikel['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>👁️</span>
                        <span><?php echo number_format($artikel['views']); ?> views</span>
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
            <?php if (!empty($artikel['gambar'])): ?>
                <?php 
                // Path gambar - sesuaikan dengan folder upload di admin
                $imagePath = 'uploads/' . $artikel['gambar'];
                ?>
                <?php if (file_exists($imagePath)): ?>
                    <div class="artikel-image" style="background: none;">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                             alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                    </div>
                <?php else: ?>
                    <!-- Fallback jika file tidak ditemukan -->
                    <div class="artikel-image">
                        
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Fallback jika tidak ada gambar -->
                <div class="artikel-image">
                    
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="artikel-content">
                <?php echo nl2br(htmlspecialchars($artikel['konten'])); ?>
            </div>

            <!-- Share Section -->
            <div class="share-section">
                <h3> Bagikan Artikel Ini</h3>
                <div class="share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://localhost/dapur_rr/artikel-detail.php?slug=' . $artikel['slug']); ?>" 
                       target="_blank" class="share-btn btn-facebook">
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://localhost/dapur_rr/artikel-detail.php?slug=' . $artikel['slug']); ?>&text=<?php echo urlencode($artikel['judul']); ?>" 
                       target="_blank" class="share-btn btn-twitter">
                        Twitter
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode($artikel['judul'] . ' - http://localhost/dapur_rr/artikel-detail.php?slug=' . $artikel['slug']); ?>" 
                       target="_blank" class="share-btn btn-whatsapp">
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Articles -->
        <?php if ($artikelTerkait->num_rows > 0): ?>
        <div class="related-articles">
            <h2 class="section-title"> Artikel Terkait</h2>
            <div class="related-grid">
                <?php while($related = $artikelTerkait->fetch_assoc()): ?>
                <a href="artikel-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="related-card">
                    <?php if (!empty($related['gambar'])): ?>
                        <?php 
                        $relatedImagePath = 'uploads/' . $related['gambar'];
                        ?>
                        <?php if (file_exists($relatedImagePath)): ?>
                            <div class="related-image" style="background: none;">
                                <img src="<?php echo htmlspecialchars($relatedImagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($related['judul']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="related-image"></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="related-image"></div>
                    <?php endif; ?>
                    <div class="related-content">
                        <div class="related-title"><?php echo htmlspecialchars($related['judul']); ?></div>
                        <div class="related-date">
                            📅 <?php echo date('d M Y', strtotime($related['created_at'])); ?> • 
                            👁️ <?php echo number_format($related['views']); ?> views
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
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