<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

$produkId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($produkId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'ID produk tidak valid'];
    header('Location: produk.php');
    exit();
}

// Ambil detail produk
$stmt = $conn->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.id = ?");
$stmt->bind_param("i", $produkId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produk tidak ditemukan'];
    header('Location: produk.php');
    exit();
}

$produk = $result->fetch_assoc();

// Cek apakah produk sudah ada di wishlist user
$isInWishlist = false;
$isLoggedIn = isLoggedIn();
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $wishlistCheck = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND produk_id = ?");
    $wishlistCheck->bind_param("ii", $userId, $produkId);
    $wishlistCheck->execute();
    $isInWishlist = $wishlistCheck->get_result()->num_rows > 0;
}

// Produk terkait
$produkTerkait = $conn->query("SELECT * FROM produk WHERE kategori_id = {$produk['kategori_id']} AND id != $produkId AND stok > 0 LIMIT 4");

$flash = getFlashMessage();
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
    <title><?php echo htmlspecialchars($produk['nama_produk']); ?> - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
            --success: #FF69B4;
            --info: #FF85A1;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            color: #333;
            line-height: 1.6;
        }
        
        /* HEADER - Tampilan baru seperti gambar */
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
            padding: 1rem 40px;
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
            width: 60px;
            height: 60px;
            border-radius: 15px;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
            border: 2px solid rgba(255, 105, 180, 0.3);
            transition: all 0.4s ease;
        }

        .logo:hover .logo-image {
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.5);
            transform: rotate(-5deg) scale(1.1);
            border-color: var(--accent);
        }

        .logo-text {
            text-align: center;
        }

        .logo-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .logo-text p {
            font-size: 0.8rem;
            color: #FF69B4;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        nav {
            background: transparent;
            animation: fadeIn 1s ease 0.3s both;
            flex-grow: 1;
            margin-left: 2rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        nav ul {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            align-items: center;
            justify-content: center;
        }

        nav a {
            color: #4B5563;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        nav a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gradient-1);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        nav a:hover {
            color: var(--accent);
        }

        nav a:hover::before {
            width: 100%;
        }

        nav a.active {
            color: var(--accent);
        }

        nav a.active::before {
            width: 100%;
        }

        /* User section di sebelah kanan */
        .user-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-left: auto;
        }

        .auth-buttons {
            display: flex;
            gap: 0.8rem;
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
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 20, 147, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 20, 147, 0.2);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .user-menu a.user-name {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .user-menu a.user-name:hover {
            color: var(--accent);
            background: rgba(255, 105, 180, 0.1);
        }

        .cart-icon, .wishlist-icon {
            position: relative;
            color: var(--dark);
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 10px;
        }

        .cart-icon:hover, .wishlist-icon:hover {
            color: var(--accent);
            background: rgba(255, 105, 180, 0.1);
        }

        .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
        }

        .btn-logout {
            background: transparent;
            color: var(--dark);
            border: 2px solid var(--dark);
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }

        .btn-logout:hover {
            background: var(--dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 24, 16, 0.2);
        }
        
        /* Hero Section */
        .hero {
            background: var(--gradient-1);
            color: white;
            padding: 2.5rem 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
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
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 3px 10px rgba(0,0,0,0.2);
            letter-spacing: -0.5px;
        }
        
        .hero p {
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.95;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
            color: #666;
            padding: 1rem 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1.2rem 1.8rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.6s ease;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #E5FFEC 0%, #D1FFDC 100%);
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
            box-shadow: var(--shadow-sm);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFD1DC 100%);
            color: #D32F2F;
            border-left: 4px solid var(--accent);
            box-shadow: var(--shadow-sm);
        }
        
        .product-detail {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 3rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }

        .product-detail:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }
        
        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
        }
        
        .product-image-section {
            text-align: center;
        }
        
        .product-image-main {
            width: 100%;
            height: 450px;
            background: var(--gradient-3);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            background-size: cover;
            background-position: center;
        }
        
        .product-image-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .promo-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gradient-1);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            z-index: 1;
            box-shadow: var(--shadow-sm);
        }
        
        .product-info-section {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .product-category {
            color: var(--accent);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            background: rgba(255, 182, 217, 0.1);
            border-radius: 12px;
            display: inline-block;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .price-current {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--accent);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .price-old {
            font-size: 1.3rem;
            text-decoration: line-through;
            color: #999;
        }
        
        .discount-badge {
            background: var(--gradient-2);
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .product-stock {
            font-size: 1rem;
            font-weight: 600;
            padding: 0.7rem 1rem;
            border-radius: 12px;
            display: inline-block;
        }
        
        .product-stock.available {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }
        
        .product-stock.low {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            color: #92400E;
            border: 2px solid rgba(245, 158, 11, 0.3);
        }
        
        .product-stock.out {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        .product-description {
            color: #4B5563;
            line-height: 1.7;
            font-size: 1rem;
            background: #FFF5F8;
            padding: 1.2rem;
            border-radius: 12px;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .rating-summary {
            padding: 1.2rem;
            background: linear-gradient(135deg, #FFF9FA 0%, #FFF5F8 100%);
            border-radius: 12px;
            border: 2px solid rgba(255, 105, 180, 0.3);
            margin: 1.2rem 0;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1.2rem;
        }
        
        .quantity-label {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 45px;
            height: 45px;
            border: 2px solid var(--accent);
            background: white;
            color: var(--accent);
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.3rem;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
        }
        
        .quantity-input {
            width: 70px;
            height: 45px;
            text-align: center;
            border: 2px solid rgba(255, 105, 180, 0.3);
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            background: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-buttons form {
            margin: 0;
            display: flex;
        }
        
        .btn-add-cart {
            flex: 1;
            padding: 1.1rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 8px 25px rgba(255, 20, 147, 0.4);
        }
        
        .btn-wishlist {
            flex: 1;
            padding: 1.1rem;
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-wishlist:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
        }

        .btn-wishlist.added {
            background: var(--accent);
            color: white;
        }

        .btn-wishlist.added:hover {
            background: #ff0062;
        }
        
        .btn-login-required {
            flex: 2;
            padding: 1.1rem;
            background: #E5E7EB;
            color: #6B7280;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: not-allowed;
        }
        
        .login-prompt {
            text-align: center;
            padding: 1.5rem;
            background: var(--gradient-1);
            border-radius: 15px;
            color: white;
            margin-top: 1.5rem;
        }
        
        .login-prompt p {
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
        }
        
        .login-prompt a {
            color: white;
            font-weight: 700;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            margin: 0 0.5rem;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        .login-prompt a:hover {
            background: white;
            color: var(--accent);
            transform: translateY(-2px);
        }
        
        .related-products {
            margin-top: 3rem;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .card-image {
            width: 100%;
            height: 180px;
            background: var(--gradient-3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            overflow: hidden;
            background-size: cover;
            background-position: center;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .card-info {
            padding: 1.2rem;
        }
        
        .card-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .card-price {
            color: var(--accent);
            font-weight: bold;
            font-size: 1.1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .review-card {
            background: white;
            padding: 1.2rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .review-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        
        .review-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--gradient-2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        
        footer {
            background: var(--dark);
            color: white;
            padding: 2.5rem 0 1.5rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            text-align: center;
        }

        .footer-content p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 1.8rem;
            }

            .product-detail {
                padding: 2rem;
            }

            .product-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-image-main {
                height: 350px;
                font-size: 3rem;
            }

            .product-title {
                font-size: 1.8rem;
            }

            .price-current {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 1.2rem;
                padding: 1rem 20px;
            }

            nav {
                margin-left: 0;
                width: 100%;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .user-section {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .hero {
                padding: 2rem 20px;
            }

            .hero h1 {
                font-size: 1.6rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .container {
                padding: 0 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .quantity-selector {
                flex-direction: column;
                align-items: flex-start;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.5rem;
            }

            .hero h1 {
                font-size: 1.4rem;
            }

            .product-detail {
                padding: 1.5rem;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .price-current {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .login-prompt a {
                display: block;
                margin: 0.5rem 0;
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
                    echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #FF6B9D 0%, #FFC93C 100%); font-size: 1.8rem;">🧁</div>';
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
                    <li><a href="produk.php" class="active">Produk</a></li>
                    
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

            <div class="user-section">
                <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <!-- Wishlist dan Cart icons -->
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
                        
                        <!-- Nama user -->
                        <a href="akun.php" class="user-name">
                            👤 <?php echo htmlspecialchars($_SESSION['nama']); ?>
                        </a>
                        
                        <!-- Tombol logout -->
                        <a href="logout.php" class="btn-logout">Logout</a>
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
            <h1><?php echo htmlspecialchars($produk['nama_produk']); ?></h1>
            <p>Detail produk <?php echo htmlspecialchars($produk['nama_produk']); ?> dari <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></p>
        </div>
    </div>

    <div class="container">
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '🎉' : '⚠️'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="breadcrumb">
            <a href="<?php echo $isLoggedIn ? 'home.php' : 'index.php'; ?>">Beranda</a> / 
            <a href="produk.php">Produk</a> / 
            <a href="produk.php?kategori=<?php echo $produk['kategori_id']; ?>"><?php echo htmlspecialchars($produk['nama_kategori']); ?></a> / 
            <?php echo htmlspecialchars($produk['nama_produk']); ?>
        </div>

        <div class="product-detail">
            <div class="product-layout">
                <div class="product-image-section">
                    <div class="product-image-main">
                        <?php if (!empty($produk['gambar']) && file_exists($produk['gambar'])): ?>
                            <img src="<?php echo htmlspecialchars($produk['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>">
                        <?php else: ?>
                            🛍️
                        <?php endif; ?>
                        <?php if ($produk['is_promo']): ?>
                        <span class="promo-badge">🔥 PROMO</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-info-section">
                    <div class="product-category"><?php echo htmlspecialchars($produk['nama_kategori']); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($produk['nama_produk']); ?></h1>
                    
                    <div class="product-price">
                        <?php if ($produk['is_promo']): ?>
                            <span class="price-current"><?php echo formatRupiah($produk['harga_promo']); ?></span>
                            <span class="price-old"><?php echo formatRupiah($produk['harga']); ?></span>
                            <?php 
                            $discount = round((($produk['harga'] - $produk['harga_promo']) / $produk['harga']) * 100);
                            ?>
                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                        <?php else: ?>
                            <span class="price-current"><?php echo formatRupiah($produk['harga']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php 
                    $stockClass = 'available';
                    $stockText = 'Stok tersedia';
                    if ($produk['stok'] == 0) {
                        $stockClass = 'out';
                        $stockText = 'Stok habis';
                    } elseif ($produk['stok'] < 10) {
                        $stockClass = 'low';
                        $stockText = 'Stok menipis';
                    }
                    ?>
                    <p class="product-stock <?php echo $stockClass; ?>">
                        <?php echo $stockText; ?> (<?php echo $produk['stok']; ?> pcs)
                    </p>

                    <div class="product-description">
                        <strong>Deskripsi Produk:</strong><br>
                        <?php echo nl2br(htmlspecialchars($produk['deskripsi'])); ?>
                    </div>
                    
                    <!-- Rating Summary -->
                    <?php
                    $ratingQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                                   FROM rating_produk WHERE produk_id = ?";
                    $ratingStmt = $conn->prepare($ratingQuery);
                    $ratingStmt->bind_param("i", $produkId);
                    $ratingStmt->execute();
                    $ratingData = $ratingStmt->get_result()->fetch_assoc();
                    $avgRating = round($ratingData['avg_rating'], 1);
                    $totalReviews = $ratingData['total_reviews'];
                    ?>
                    
                    <div class="rating-summary">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                            <div style="font-size: 1.8rem; color: #ffc107;">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= round($avgRating) ? '⭐' : '☆';
                                }
                                ?>
                            </div>
                            <div>
                                <div style="font-size: 1.3rem; font-weight: bold; color: var(--accent);">
                                    <?php echo $avgRating > 0 ? $avgRating : '0.0'; ?>/5
                                </div>
                                <div style="color: #666; font-size: 0.85rem;">
                                    <?php echo $totalReviews; ?> Ulasan
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isLoggedIn && $produk['stok'] > 0): ?>
                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <span class="quantity-label">Jumlah:</span>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" onclick="decreaseQty()">-</button>
                            <input type="number" name="jumlah_display" id="jumlah" value="1" min="1" max="<?php echo $produk['stok']; ?>" class="quantity-input" readonly>
                            <button type="button" class="quantity-btn" onclick="increaseQty()">+</button>
                        </div>
                    </div>

                    <!-- Action Buttons - DUA FORM TERPISAH -->
                    <div class="action-buttons">
                        <!-- Form untuk keranjang -->
                        <form method="POST" action="keranjang-action.php" style="flex: 2; margin: 0;">
                            <input type="hidden" name="produk_id" value="<?php echo $produk['id']; ?>">
                            <input type="hidden" name="jumlah" id="jumlah-hidden" value="1">
                            <button type="submit" name="action" value="add" class="btn-add-cart">
                                🛒 Tambah ke Keranjang
                            </button>
                        </form>
                        
                        <!-- Form untuk wishlist -->
                        <form method="POST" action="wishlist-action.php" style="flex: 1; margin: 0;">
                            <input type="hidden" name="produk_id" value="<?php echo $produk['id']; ?>">
                            <input type="hidden" name="action" value="<?php echo $isInWishlist ? 'delete' : 'add'; ?>">
                            <input type="hidden" name="redirect_to" value="detail-produk.php?id=<?php echo $produk['id']; ?>">
                            <button type="submit" class="btn-wishlist <?php echo $isInWishlist ? 'added' : ''; ?>">
                                <?php echo $isInWishlist ? '♥️ Wishlisted' : '♥ Wishlist'; ?>
                            </button>
                        </form>
                    </div>
                    <?php elseif ($produk['stok'] == 0): ?>
                    <div class="action-buttons">
                        <div class="btn-login-required">
                            ❌ Stok Habis
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="action-buttons">
                        <div class="btn-login-required" title="Login untuk membeli">
                            🔒 Login untuk Membeli
                        </div>
                    </div>
                    <div class="login-prompt">
                        <p>Silakan login atau daftar untuk membeli produk ini</p>
                        <div>
                            <a href="login.php">Login</a>
                            <a href="register.php">Daftar</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <?php
            $reviewsQuery = "SELECT rp.*, u.nama, u.email 
                            FROM rating_produk rp
                            JOIN users u ON rp.user_id = u.id
                            WHERE rp.produk_id = ?
                            ORDER BY rp.created_at DESC";
            $reviewsStmt = $conn->prepare($reviewsQuery);
            $reviewsStmt->bind_param("i", $produkId);
            $reviewsStmt->execute();
            $reviews = $reviewsStmt->get_result();
            ?>
            
            <?php if ($reviews->num_rows > 0): ?>
            <div style="margin-top: 2.5rem;">
                <h2 style="font-size: 1.3rem; margin-bottom: 1.2rem; color: var(--dark); background: var(--gradient-1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 800;">Ulasan Produk</h2>
                
                <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-avatar">
                            <?php echo strtoupper(substr($review['nama'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: bold; color: var(--dark); font-size: 0.95rem;"><?php echo htmlspecialchars($review['nama']); ?></div>
                            <div style="color: #ffc107; font-size: 1rem;">
                                <?php for($i = 0; $i < $review['rating']; $i++) echo '⭐'; ?>
                            </div>
                        </div>
                        <div style="color: #999; font-size: 0.8rem;">
                            <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <div style="color: #4B5563; line-height: 1.5; font-size: 0.95rem;">
                        <?php echo nl2br(htmlspecialchars($review['ulasan'])); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($produkTerkait->num_rows > 0): ?>
        <div class="related-products">
            <h2 class="section-title">Produk Terkait</h2>
            <div class="product-grid">
                <?php while($related = $produkTerkait->fetch_assoc()): ?>
                <a href="detail-produk.php?id=<?php echo $related['id']; ?>" class="product-card">
                    <div class="card-image">
                        <?php if (!empty($related['gambar']) && file_exists($related['gambar'])): ?>
                            <img src="<?php echo htmlspecialchars($related['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['nama_produk']); ?>">
                        <?php else: ?>
                            🛍️
                        <?php endif; ?>
                    </div>
                    <div class="card-info">
                        <div class="card-name"><?php echo htmlspecialchars($related['nama_produk']); ?></div>
                        <div class="card-price">
                            <?php echo formatRupiah($related['is_promo'] ? $related['harga_promo'] : $related['harga']); ?>
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
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. Hak Cipta Dilindungi.</p>
            <p style="margin-top: 1rem; font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                <a href="syarat-ketentuan.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 8px;">Syarat & Ketentuan</a> | 
                <a href="kebijakan-privasi.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 8px;">Kebijakan Privasi</a> | 
                <a href="kontak.php" style="color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 8px;">Kontak Kami</a>
            </p>
        </div>
    </footer>

    <script>
        const maxStock = <?php echo $produk['stok']; ?>;
        const quantityInput = document.getElementById('jumlah');
        const hiddenQuantity = document.getElementById('jumlah-hidden');

        function increaseQty() {
            if (quantityInput && parseInt(quantityInput.value) < maxStock) {
                quantityInput.value = parseInt(quantityInput.value) + 1;
                if (hiddenQuantity) {
                    hiddenQuantity.value = quantityInput.value;
                }
            }
        }

        function decreaseQty() {
            if (quantityInput && parseInt(quantityInput.value) > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
                if (hiddenQuantity) {
                    hiddenQuantity.value = quantityInput.value;
                }
            }
        }

        // Inisialisasi nilai hidden quantity
        document.addEventListener('DOMContentLoaded', function() {
            // Sync quantity antara display dan form
            if (quantityInput && hiddenQuantity) {
                hiddenQuantity.value = quantityInput.value;
            }
            
            // Update hidden quantity saat input berubah
            if (quantityInput) {
                quantityInput.addEventListener('change', function() {
                    if (hiddenQuantity) {
                        hiddenQuantity.value = this.value;
                    }
                });
            }
            
            // Animasi untuk konten saat scroll
            const sections = document.querySelectorAll('.product-detail, .review-card, .product-card');
            
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
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'all 0.5s ease-out';
                observer.observe(section);
            });
            
            // Tambahkan class active untuk navigasi
            document.querySelectorAll('nav a').forEach(link => {
                if (link.getAttribute('href') === 'produk.php') {
                    link.classList.add('active');
                }
            });
            
            // Auto hide alert
            const alert = document.querySelector('.alert');
            if(alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 500);
                }, 4000);
            }
        });
    </script>
</body>
</html>