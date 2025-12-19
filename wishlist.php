<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil data wishlist
$query = "SELECT w.*, p.nama_produk, p.harga, p.is_promo, p.harga_promo, p.stok, p.gambar, k.nama_kategori
          FROM wishlist w
          JOIN produk p ON w.produk_id = p.id
          LEFT JOIN kategori k ON p.kategori_id = k.id
          WHERE w.user_id = ?
          ORDER BY w.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$wishlist = $stmt->get_result();

$flash = getFlashMessage();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
            overflow-x: hidden;
        }

        /* Header dengan animasi - SAMA PERSIS DENGAN HOME.PHP */
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
            font-size: 2rem;
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

        nav a:hover::before {
            width: 100%;
        }

        nav a:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        nav a.active {
            color: var(--accent);
        }

        nav a.active::before {
            width: 100%;
        }

        /* User menu di header - SAMA PERSIS DENGAN HOME.PHP STYLE */
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

        /* User info styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
        }

        .icon-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            color: var(--dark);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.15);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }

        .icon-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.3);
            background: var(--gradient-1);
            color: white;
        }

        .icon-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            border: 2px solid white;
        }

        .btn-logout {
            background: var(--gradient-1);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 6rem 40px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        .page-title::before {
            content: '❤️';
            position: absolute;
            left: -60px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            animation: heartbeat 1.5s ease-in-out infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: translateY(-50%) scale(1); }
            50% { transform: translateY(-50%) scale(1.2); }
        }

        /* Alert */
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

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2.5rem;
        }

        .wishlist-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
        }

        .wishlist-card::before {
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

        .wishlist-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 15px 50px rgba(255, 27, 141, 0.3);
            border-color: var(--primary);
        }

        .wishlist-card:hover::before {
            opacity: 0.08;
        }

        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #FFE5EC 0%, #FFF9E6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
            position: relative;
            z-index: 1;
        }

        .wishlist-card:hover .product-image img {
            transform: scale(1.2) rotate(3deg);
        }

        .product-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--gradient-2);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 800;
            z-index: 2;
            box-shadow: 0 5px 20px rgba(255, 27, 141, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .remove-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: white;
            color: var(--accent);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            font-size: 1.8rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 2;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: var(--accent);
            color: white;
            transform: scale(1.1) rotate(90deg);
        }

        .product-info {
            padding: 2rem;
            position: relative;
            z-index: 1;
            background: white;
        }

        .product-category {
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: inline-block;
            padding: 0.4rem 1rem;
            background: linear-gradient(135deg, #FFE5EC 0%, #FFF0F5 100%);
            border-radius: 10px;
        }

        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: var(--dark);
            line-height: 1.3;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .price-current {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .product-stock {
            color: var(--success);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .out-of-stock {
            color: #EF4444;
        }

        .product-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn-cart {
            flex: 1;
            background: var(--gradient-1);
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 15px;
            cursor: pointer;
            border: none;
            font-weight: 700;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 20, 147, 0.3);
        }

        .btn-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-detail {
            background: white;
            color: var(--primary);
            padding: 1rem 1.2rem;
            border-radius: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary);
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-detail:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Empty Wishlist */
        .empty-wishlist {
            text-align: center;
            padding: 5rem 3rem;
            background: var(--gradient-1);
            border-radius: 40px;
            margin: 4rem 0;
            box-shadow: 0 15px 50px rgba(255, 27, 141, 0.4);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .empty-wishlist::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        .empty-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .empty-wishlist h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .empty-wishlist p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }

        .btn-explore {
            background: white;
            color: var(--primary);
            padding: 1rem 3rem;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-explore:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .wishlist-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 992px) {
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-title {
                font-size: 2.8rem;
            }

            .page-title::before {
                position: relative;
                left: 0;
                margin-right: 1rem;
                transform: none;
                display: inline-block;
            }

            .container {
                padding: 3rem 30px;
            }

            .header-top, nav ul {
                padding: 0 30px;
            }
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .header-top {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1rem 20px;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
                padding: 0 20px;
            }

            .page-title {
                font-size: 2.2rem;
            }

            .container {
                padding: 3rem 20px;
            }
        }

        @media (max-width: 480px) {
            .wishlist-card {
                margin: 0 1rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .empty-wishlist {
                padding: 3rem 1.5rem;
            }

            .empty-icon {
                font-size: 4rem;
            }

            .empty-wishlist h2 {
                font-size: 2rem;
            }

            .product-actions {
                flex-direction: column;
            }

            .btn-cart, .btn-detail {
                width: 100%;
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
                echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: var(--gradient-2); font-size: 2rem;">🧁</div>';
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
                <li><a href="riwayat-pesanan.php">Pesanan</a></li>
                <li><a href="promo.php">Promo</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
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
        </div>
    </div>
</header>
    <div class="container">
        <h1 class="page-title">Wishlist Saya</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if ($wishlist->num_rows === 0): ?>
        <div class="empty-wishlist">
            <div class="empty-icon">❤️</div>
            <h2>Wishlist Anda Kosong</h2>
            <p>Tambahkan produk favorit Anda ke wishlist untuk menyimpannya nanti</p>
            <a href="produk.php" class="btn-explore">🛍️ Jelajahi Produk</a>
        </div>
        <?php else: ?>
        <div class="wishlist-grid">
            <?php while($item = $wishlist->fetch_assoc()): ?>
            <div class="wishlist-card">
                <div class="product-image">
                    <?php if (!empty($item['gambar']) && file_exists($item['gambar'])): ?>
                        <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                    <?php else: ?>
                        <span style="font-size: 4rem;">🧁</span>
                    <?php endif; ?>
                    <?php if ($item['is_promo']): ?>
                    <span class="product-badge">PROMO</span>
                    <?php endif; ?>
                    <form method="POST" action="wishlist-action.php" style="display: inline;">
                        <input type="hidden" name="produk_id" value="<?php echo $item['produk_id']; ?>">
                        <button type="submit" name="action" value="delete" class="remove-btn" title="Hapus dari wishlist">×</button>
                    </form>
                </div>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($item['nama_kategori']); ?></div>
                    <h3 class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?></h3>
                    <div class="product-price">
                        <?php if ($item['is_promo']): ?>
                            <span class="price-current"><?php echo formatRupiah($item['harga_promo']); ?></span>
                            <span class="price-old"><?php echo formatRupiah($item['harga']); ?></span>
                        <?php else: ?>
                            <span class="price-current"><?php echo formatRupiah($item['harga']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="product-stock <?php echo $item['stok'] > 0 ? '' : 'out-of-stock'; ?>">
                        <?php if ($item['stok'] > 0): ?>
                            ✅ Stok: <?php echo $item['stok']; ?> tersedia
                        <?php else: ?>
                            ❌ Stok habis
                        <?php endif; ?>
                    </p>
                    <div class="product-actions">
                        <form method="POST" action="keranjang-action.php" style="flex: 1;">
                            <input type="hidden" name="produk_id" value="<?php echo $item['produk_id']; ?>">
                            <button type="submit" name="action" value="add" class="btn-cart" <?php echo $item['stok'] > 0 ? '' : 'disabled'; ?>>
                                🛒 Tambah
                            </button>
                        </form>
                        <a href="detail-produk.php?id=<?php echo $item['produk_id']; ?>" class="btn-detail">Detail</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add animation to wishlist cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.wishlist-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.transitionDelay = `${index * 0.1}s`;
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    alert.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 5000);
            });

            // Add 3D hover effect to cards
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const rotateX = (y - centerY) / 20;
                    const rotateY = (centerX - x) / 20;
                    
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-15px) scale(1.03)`;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(-15px) scale(1.03)';
                });
            });

            // Smooth page transition
            window.addEventListener('load', () => {
                document.body.style.opacity = '0';
                setTimeout(() => {
                    document.body.style.transition = 'opacity 0.5s ease';
                    document.body.style.opacity = '1';
                }, 100);
            });

            console.log('❤️ Wishlist page loaded with ' + cards.length + ' items');
        });

        // Highlight current page in navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = 'wishlist.php';
            const navLinks = document.querySelectorAll('nav a');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes('wishlist')) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>