<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Filter
$kategoriId = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'terbaru';

// Query produk dengan rating
$query = "SELECT p.*, k.nama_kategori,
          COALESCE(AVG(rp.rating), 0) as avg_rating,
          COUNT(rp.id) as total_reviews
          FROM produk p 
          LEFT JOIN kategori k ON p.kategori_id = k.id 
          LEFT JOIN rating_produk rp ON p.id = rp.produk_id
          WHERE p.stok > 0";

if ($kategoriId) {
    $query .= " AND p.kategori_id = " . intval($kategoriId);
}

if ($search) {
    $query .= " AND p.nama_produk LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$query .= " GROUP BY p.id";

// Sorting
switch($sort) {
    case 'termurah':
        $query .= " ORDER BY CASE WHEN p.is_promo = 1 THEN p.harga_promo ELSE p.harga END ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY CASE WHEN p.is_promo = 1 THEN p.harga_promo ELSE p.harga END DESC";
        break;
    case 'rating':
        $query .= " ORDER BY avg_rating DESC, total_reviews DESC";
        break;
    case 'nama':
        $query .= " ORDER BY p.nama_produk ASC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

$produk = $conn->query($query);

// Ambil kategori
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

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
    <title>Produk - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            color: var(--dark);
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .search-box {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }
        
        .search-box input {
            padding: 1rem 1.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            width: 350px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            color: var(--dark);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .search-box button {
            padding: 1rem 2.2rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 20, 147, 0.4);
        }
        
        .filter-section {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            margin-bottom: 3rem;
            display: flex;
            gap: 3rem;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        
        .filter-group label {
            font-weight: 700;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .filter-group select {
            padding: 1rem 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            background: white;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .category-pills {
            display: flex;
            gap: 1.2rem;
            flex-wrap: wrap;
        }
        
        .category-pill {
            padding: 1rem 2rem;
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 700;
            font-size: 0.95rem;
        }
        
        .category-pill.active {
            background: var(--gradient-1);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
        }
        
        .category-pill:hover {
            background: var(--gradient-1);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.4);
        }
        
        /* PRODUCT GRID - 4 KOLOM */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2.5rem;
        }
        
        .product-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
        }

        .product-card::before {
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
        
        .product-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .product-card:hover::before {
            opacity: 0.08;
        }
        
        .product-image {
            width: 100%;
            height: 280px;
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
        
        .product-card:hover .product-image img {
            transform: scale(1.2) rotate(2deg);
        }
        
        .product-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--gradient-2);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            font-size: 0.85rem;
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
        
        .wishlist-btn {
            position: absolute;
            top: 16px;
            left: 16px;
            background: white;
            color: var(--primary);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            font-size: 1.5rem;
            z-index: 2;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            transform: scale(1.1);
            background: var(--primary);
            color: white;
        }
        
        .product-info {
            padding: 2rem;
            position: relative;
            z-index: 1;
            background: white;
        }
        
        .product-category {
            color: var(--primary);
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: inline-block;
            padding: 0.4rem 1rem;
            background: linear-gradient(135deg, #FFE5EC 0%, #FFF0F5 100%);
            border-radius: 10px;
        }
        
        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
            line-height: 1.3;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
        }
        
        .stars {
            display: flex;
            gap: 0.2rem;
            font-size: 1.1rem;
        }
        
        .rating-text {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
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
            color: #9CA3AF;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .product-stock {
            color: var(--success);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .product-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .btn-cart {
            flex: 1;
            background: var(--gradient-1);
            color: white;
            padding: 1.2rem;
            text-align: center;
            border-radius: 15px;
            cursor: pointer;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 157, 0.3);
        }
        
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.4);
        }
        
        .btn-cart-disabled {
            flex: 1;
            background: #F3F4F6;
            color: #9CA3AF;
            padding: 1.2rem;
            text-align: center;
            border-radius: 15px;
            cursor: not-allowed;
            font-weight: 600;
            border: 3px dashed #E5E7EB;
            font-size: 0.95rem;
        }
        
        .btn-detail {
            background: #6c757d;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .btn-detail:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .no-products {
            text-align: center;
            padding: 5rem 3rem;
            background: white;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
            margin: 2rem 0;
        }
        
        .no-products h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .no-products p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
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
        
        @media (max-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            }
            
            .search-box input {
                width: 250px;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .page-title {
                font-size: 2.2rem;
            }
            
            .container {
                padding: 0 20px;
            }

            .header-top {
                padding: 1rem 20px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .filter-section {
                padding: 2rem;
                gap: 2rem;
            }

            .category-pills {
                justify-content: center;
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
        <div class="page-header">
            <h1 class="page-title">Produk Kami</h1>
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Cari</button>
            </form>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="filter-section">
            <div class="category-pills">
                <a href="produk.php" class="category-pill <?php echo !$kategoriId ? 'active' : ''; ?>">Semua</a>
                <?php while($kat = $kategori->fetch_assoc()): ?>
                <a href="produk.php?kategori=<?php echo $kat['id']; ?>" class="category-pill <?php echo $kategoriId == $kat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                </a>
                <?php endwhile; ?>
            </div>
            
            <div class="filter-group">
                <label>Urutkan:</label>
                <select onchange="location.href='produk.php?sort=' + this.value + '<?php echo $kategoriId ? '&kategori='.$kategoriId : ''; ?><?php echo $search ? '&search='.$search : ''; ?>'">
                    <option value="terbaru" <?php echo $sort === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
                    <option value="termurah" <?php echo $sort === 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                    <option value="termahal" <?php echo $sort === 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                    <option value="nama" <?php echo $sort === 'nama' ? 'selected' : ''; ?>>Nama A-Z</option>
                </select>
            </div>
        </div>

        <?php if ($produk->num_rows === 0): ?>
        <div class="no-products">
            <div style="font-size: 5rem; margin-bottom: 1rem;">🔍</div>
            <h2>Produk Tidak Ditemukan</h2>
            <p>Tidak ada produk yang sesuai dengan pencarian Anda</p>
            <a href="produk.php" class="btn btn-primary" style="display: inline-block; margin-top: 2rem; text-decoration: none;">Lihat Semua Produk</a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php while($p = $produk->fetch_assoc()): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($p['gambar'] && file_exists($p['gambar'])): ?>
                        <img src="<?php echo htmlspecialchars($p['gambar']); ?>" alt="<?php echo htmlspecialchars($p['nama_produk']); ?>">
                    <?php else: ?>
                        🧁
                    <?php endif; ?>
                    <?php if ($p['is_promo']): ?>
                    <span class="product-badge">PROMO</span>
                    <?php endif; ?>
                    <?php if ($isLoggedIn): ?>
                    <form method="POST" action="wishlist-action.php" style="display: inline;">
                        <input type="hidden" name="produk_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" name="action" value="add" class="wishlist-btn" title="Tambah ke Wishlist">♥</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($p['nama_kategori']); ?></div>
                    <h3 class="product-name"><?php echo htmlspecialchars($p['nama_produk']); ?></h3>
                    
                    <div class="product-rating">
                        <div class="stars">
                            <?php 
                            $avgRating = round($p['avg_rating']);
                            for($i = 1; $i <= 5; $i++): 
                            ?>
                                <span style="color: <?php echo $i <= $avgRating ? '#FFC93C' : '#E0E0E0'; ?>;">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php if ($p['total_reviews'] > 0): ?>
                                <?php echo number_format($p['avg_rating'], 1); ?> (<?php echo $p['total_reviews']; ?> ulasan)
                            <?php else: ?>
                                Belum ada ulasan
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="product-price">
                        <?php if ($p['is_promo']): ?>
                            <span class="price-current"><?php echo formatRupiah($p['harga_promo']); ?></span>
                            <span class="price-old"><?php echo formatRupiah($p['harga']); ?></span>
                        <?php else: ?>
                            <span class="price-current"><?php echo formatRupiah($p['harga']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="product-stock">Stok: <?php echo $p['stok']; ?> tersedia</p>
                    <div class="product-actions">
                        <?php if ($isLoggedIn): ?>
                        <form method="POST" action="keranjang-action.php" style="flex: 1;">
                            <input type="hidden" name="produk_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="action" value="add" class="btn-cart">
                                🛒 Tambah
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="btn-cart-disabled" title="Login untuk menambahkan ke keranjang">
                            🔒 Login untuk Pesan
                        </div>
                        <?php endif; ?>
                        <a href="detail-produk.php?id=<?php echo $p['id']; ?>" class="btn-detail">Detail</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

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

        document.querySelectorAll('.product-card').forEach(el => {
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