<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();

// Ambil data pengaturan
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil banner aktif
$banners = $conn->query("SELECT * FROM banner WHERE is_active = 1 ORDER BY urutan ASC");

// Ambil produk (limit 8 untuk home)
$produk = $conn->query("SELECT p.*, k.nama_kategori FROM produk p 
                        LEFT JOIN kategori k ON p.kategori_id = k.id 
                        WHERE p.stok > 0
                        ORDER BY p.created_at DESC LIMIT 8");

// Ambil testimoni yang disetujui
$testimoni = $conn->query("SELECT t.*, u.nama FROM testimoni t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.is_approved = 1 
                           ORDER BY t.created_at DESC LIMIT 6");

$flash = getFlashMessage();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pengaturan['nama_toko']); ?> - Beranda</title>
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

        /* Header seperti halaman lainnya */
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

        /* Hero Banner seperti index.php */
        .hero-section {
            position: relative;
            min-height: 600px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: var(--gradient-1);
        }

        .hero-section::before {
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

        /* Floating shapes */
        .hero-section::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.25) 0%, transparent 70%);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            animation: float-big 8s ease-in-out infinite;
        }

        @keyframes float-big {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            50% {
                transform: translate(-50px, 50px) rotate(180deg);
            }
        }

        /* Tambahan floating shapes */
        .floating-shape {
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float-random 6s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            top: 60%;
            left: 80%;
            animation-delay: 2s;
            width: 80px;
            height: 80px;
        }

        .floating-shape:nth-child(3) {
            top: 80%;
            left: 20%;
            animation-delay: 4s;
            width: 40px;
            height: 40px;
        }

        @keyframes float-random {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            50% {
                transform: translateY(-20px) translateX(20px);
            }
        }

         /* Banner Slider Container - DIPERBAIKI */
    .banner-slider {
        position: relative;
        width: 100%;
        height: 400px;
        overflow: hidden;
    }

    .banner-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease-in-out;
        z-index: 1;
    }

    .banner-slide.active {
        opacity: 1;
        z-index: 2;
    }

    .hero-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
        display: grid;
        grid-template-columns: 0.1fr 1fr;
        gap: 3rem;
        align-items: center;
        height: 100%;
        position: relative;
        z-index: 3;
    }

    .hero-text h2 {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        animation: fadeInUp 1s ease;
        text-shadow: 3px 5px 15px rgba(0,0,0,0.3);
        letter-spacing: -1px;
    }

    .hero-text h2 span {
        display: inline-block;
        animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .hero-text p {
        font-size: 1.2rem;
        color: rgba(255,255,255,0.95);
        margin-bottom: 2.5rem;
        line-height: 1.6;
        animation: fadeInUp 1s ease 0.2s both;
        text-shadow: 2px 3px 8px rgba(0,0,0,0.2);
        font-weight: 500;
    }

    .hero-buttons {
        display: flex;
        gap: 1.5rem;
        animation: fadeInUp 1s ease 0.4s both;
        flex-wrap: wrap;
    }

    .hero-image {
        position: relative;
        animation: fadeInRight 1.2s ease;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hero-image img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        border: 4px solid rgba(255,255,255,0.3);
        transition: all 0.4s ease;
        animation: float-image 3s ease-in-out infinite;
    }

    @keyframes float-image {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .hero-image img:hover {
        transform: scale(1.02) rotate(1deg);
        box-shadow: 0 25px 60px rgba(0,0,0,0.5);
    }

    .hero-placeholder {
        width: 100%;
        height: 300px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 4px solid rgba(255,255,255,0.4);
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        animation: pulse-glow 2s ease-in-out infinite;
    }

    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        50% {
            box-shadow: 0 20px 50px rgba(255,105,180,0.5);
        }
    }

    .hero-placeholder span {
        font-size: 6rem;
        filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        animation: rotate-emoji 4s ease-in-out infinite;
    }

    @keyframes rotate-emoji {
        0%, 100% {
            transform: rotate(0deg) scale(1);
        }
        50% {
            transform: rotate(15deg) scale(1.1);
        }
    }

    /* Banner Navigation Dots */
    .banner-dots {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 12px;
        z-index: 10;
    }

    .banner-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .banner-dot:hover {
        background: rgba(255, 255, 255, 0.8);
        transform: scale(1.2);
    }

    .banner-dot.active {
        background: white;
        width: 35px;
        border-radius: 6px;
        border-color: rgba(255, 105, 180, 0.5);
    }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 40px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--dark);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
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

        .product-description {
            color: #4B5563;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            line-height: 1.6;
        }

        .product-stock {
            color: var(--success);
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
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

        .btn-view-all {
            background: var(--gradient-1);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 15px;
            text-decoration: none;
            display: inline-block;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 157, 0.3);
            margin-top: 2rem;
        }

        .btn-view-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 157, 0.4);
        }

        /* TESTIMONI */
        .testimoni-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
        }

        .testimoni-card {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .testimoni-card::before {
            content: '"';
            position: absolute;
            top: -15px;
            left: 25px;
            font-size: 8rem;
            font-family: 'Playfair Display', serif;
            color: rgba(255, 27, 141, 0.15);
            line-height: 1;
        }

        .testimoni-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .testimoni-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .testimoni-avatar {
            width: 65px;
            height: 65px;
            border-radius: 20px;
            background: var(--gradient-2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            box-shadow: 0 5px 15px rgba(255, 27, 141, 0.3);
        }

        .testimoni-info strong {
            display: block;
            color: var(--dark);
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
            font-weight: 700;
        }

        .rating {
            color: #FFD700;
            font-size: 1.1rem;
        }

        .testimoni-card p {
            color: #4B5563;
            line-height: 1.8;
            position: relative;
            z-index: 1;
            font-size: 1rem;
        }

        /* FOOTER */
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
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-section p {
            color: rgba(255,255,255,0.8);
            line-height: 1.8;
            font-size: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            padding: 0.6rem 0;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .footer-section a:hover {
            color: var(--primary);
            padding-left: 15px;
        }

        .footer-bottom {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }

        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }

            .testimoni-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-text h2 {
                font-size: 3.2rem;
            }

            .hero-image img,
            .hero-placeholder {
                height: 400px;
            }
        }

        @media (max-width: 992px) {
            .hero-section {
                min-height: 500px;
            }

            .banner-slider {
                height: 350px;
            }

            .hero-content {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .hero-text h2 {
                font-size: 2.8rem;
            }

            .hero-text p {
                font-size: 1.2rem;
            }

            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-content {
                grid-template-columns: 1fr 1fr;
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

            .hero-section {
                min-height: 400px;
            }

            .banner-slider {
                height: 300px;
            }

            .container {
                padding: 0 20px;
            }

            .hero-content {
                padding: 0 20px;
            }

            .hero-text h2 {
                font-size: 2.2rem;
            }

            .hero-text p {
                font-size: 1.1rem;
            }

            .hero-image img,
            .hero-placeholder {
                height: 300px;
            }

            .hero-placeholder span {
                font-size: 5rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .product-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .testimoni-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 1.8rem;
            }

            .hero-section {
                min-height: 350px;
            }

            .banner-slider {
                height: 250px;
            }

            .hero-text h2 {
                font-size: 1.8rem;
            }

            .hero-text p {
                font-size: 1rem;
            }

            .hero-image img,
            .hero-placeholder {
                height: 250px;
            }

            .hero-placeholder span {
                font-size: 4rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.95rem;
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
                <li><a href="home.php" class="active">Beranda</a></li>
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
        </div>
    </div>
</header>

    <!-- Banner Section seperti index.php -->
    <section class="hero-section">
        <!-- Floating decorative shapes -->
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        
        <div class="banner-slider">
            <?php 
            $banners->data_seek(0); // Reset pointer
            $banner_count = 0;
            if ($banners->num_rows > 0):
                while($banner = $banners->fetch_assoc()): 
                    $banner_count++;
            ?>
            <div class="banner-slide <?php echo $banner_count === 1 ? 'active' : ''; ?>">
                <div class="hero-content">
                    <div class="hero-text">
                        <h2>
                            <?php if (!empty($banner['judul'])): ?>
                                <?php 
                                $words = explode(' ', $banner['judul']);
                                foreach($words as $index => $word): 
                                ?>
                                    <span style="animation-delay: <?php echo $index * 0.1; ?>s"><?php echo htmlspecialchars($word); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="animation-delay: 0s">Selamat</span> 
                                <span style="animation-delay: 0.1s">Datang</span><br>
                                <span style="animation-delay: 0.2s">di</span> 
                                <span style="animation-delay: 0.3s">Dapur RR</span>
                            <?php endif; ?>
                        </h2>
                        <p><?php echo htmlspecialchars($banner['deskripsi'] ?? 'Nikmati kelezatan kue artisan buatan tangan dengan cinta dan bahan premium'); ?></p>
                       
                    </div>
                    <div class="hero-image">
                        <?php if (!empty($banner['gambar']) && file_exists($banner['gambar'])): ?>
                            <img src="<?php echo htmlspecialchars($banner['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($banner['judul']); ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="hero-placeholder">
                                <span>🎂</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else: 
            ?>
            <!-- Default banner jika tidak ada banner -->
            <div class="banner-slide active">
                <div class="hero-content">
                    <div class="hero-text">
                        <h2>
                            <span style="animation-delay: 0s">Selamat</span> 
                            <span style="animation-delay: 0.1s">Datang</span><br>
                            <span style="animation-delay: 0.2s">di</span> 
                            <span style="animation-delay: 0.3s">Dapur RR</span>
                        </h2>
                        <p>Nikmati kelezatan kue artisan buatan tangan dengan bahan premium pilihan dan resep rahasia keluarga</p>
                        <div class="hero-buttons">
                            <a href="produk.php" class="btn btn-primary">🛍️ Jelajahi Produk</a>
                            <a href="promo.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.25); color: white; border: 2px solid rgba(255,255,255,0.4);">
                                💝 Lihat Promo
                            </a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <div class="hero-placeholder">
                            <span>🎂</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Banner Navigation Dots -->
            <?php if ($banner_count > 1): ?>
            <div class="banner-dots">
                <?php for($i = 1; $i <= $banner_count; $i++): ?>
                    <div class="banner-dot <?php echo $i === 1 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="container">
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <section>
            <h2 class="section-title">Produk Terbaru</h2>
            <div class="product-grid">
                <?php while($p = $produk->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($p['gambar']) && file_exists($p['gambar'])): ?>
                            <img src="<?php echo htmlspecialchars($p['gambar']); ?>" alt="<?php echo htmlspecialchars($p['nama_produk']); ?>">
                        <?php else: ?>
                            <span style="font-size: 4rem;">🧁</span>
                        <?php endif; ?>
                        <?php if ($p['is_promo']): ?>
                        <span class="product-badge">PROMO</span>
                        <?php endif; ?>
                        <form method="POST" action="wishlist-action.php" style="display: inline;">
                            <input type="hidden" name="produk_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="action" value="add" class="wishlist-btn" title="Tambah ke Wishlist">♥</button>
                        </form>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?php echo htmlspecialchars($p['nama_kategori']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($p['nama_produk']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($p['deskripsi'], 0, 80)); ?>...</p>
                        <p class="product-stock">Stok: <?php echo $p['stok']; ?> tersedia</p>
                        <div class="product-price">
                            <?php if ($p['is_promo']): ?>
                                <span class="price-current"><?php echo formatRupiah($p['harga_promo']); ?></span>
                                <span class="price-old"><?php echo formatRupiah($p['harga']); ?></span>
                            <?php else: ?>
                                <span class="price-current"><?php echo formatRupiah($p['harga']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <form method="POST" action="keranjang-action.php" style="flex: 1;">
                                <input type="hidden" name="produk_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="action" value="add" class="btn-cart">
                                    🛒 Keranjang
                                </button>
                            </form>
                            <a href="detail-produk.php?id=<?php echo $p['id']; ?>" class="btn-detail">Detail</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="produk.php" class="btn-view-all">Lihat Semua Produk →</a>
            </div>
        </section>

        <?php if ($testimoni->num_rows > 0): ?>
        <section style="margin-top: 6rem;">
            <h2 class="section-title">Testimoni Pelanggan</h2>
            <div class="testimoni-grid">
                <?php while($t = $testimoni->fetch_assoc()): ?>
                <div class="testimoni-card">
                    <div class="testimoni-header">
                        <div class="testimoni-avatar">
                            <?php echo strtoupper(substr($t['nama'], 0, 1)); ?>
                        </div>
                        <div class="testimoni-info">
                            <strong><?php echo htmlspecialchars($t['nama']); ?></strong>
                            <div class="rating">
                                <?php for($i = 0; $i < $t['rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <p><?php echo htmlspecialchars($t['komentar']); ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h3>
                <p><?php echo htmlspecialchars($pengaturan['deskripsi']); ?></p>
            </div>
            <div class="footer-section">
                <h3>Menu</h3>
                <ul>
                    <li><a href="home.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                   <li><a href="testimoni.php">Testimoni</a></li>
                    <li><a href="artikel.php">Blog</a></li>
                    <li><a href="tentang.php">Tentang Kami</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Informasi</h3>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="syarat-ketentuan.php">Syarat & Ketentuan</a></li>
                    <li><a href="kebijakan-privasi.php">Kebijakan Privasi</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Kontak</h3>
               
                <p><?php echo htmlspecialchars($pengaturan['no_telepon']); ?></p>
                <br><p><?php echo htmlspecialchars($pengaturan['email']); ?></p>
                <br><p><?php echo htmlspecialchars($pengaturan['jam_buka']); ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($pengaturan['nama_toko']); ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Banner Slider Functionality
        const bannerSlider = {
            currentSlide: 0,
            slides: document.querySelectorAll('.banner-slide'),
            dots: document.querySelectorAll('.banner-dot'),
            interval: null,
            duration: 5000, // 10 detik
            
            init() {
                if (this.slides.length <= 1) return;
                
                // Klik pada dots
                this.dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        this.goToSlide(index);
                        this.resetInterval();
                    });
                });
                
                // Auto play
                this.startAutoPlay();
                
                // Pause saat hover
                const sliderContainer = document.querySelector('.banner-slider');
                sliderContainer.addEventListener('mouseenter', () => this.stopAutoPlay());
                sliderContainer.addEventListener('mouseleave', () => this.startAutoPlay());
            },
            
            goToSlide(index) {
                // Remove active class
                this.slides[this.currentSlide].classList.remove('active');
                this.dots[this.currentSlide]?.classList.remove('active');
                
                // Add active class
                this.currentSlide = index;
                this.slides[this.currentSlide].classList.add('active');
                this.dots[this.currentSlide]?.classList.add('active');
            },
            
            nextSlide() {
                const next = (this.currentSlide + 1) % this.slides.length;
                this.goToSlide(next);
            },
            
            startAutoPlay() {
                this.interval = setInterval(() => this.nextSlide(), this.duration);
            },
            
            stopAutoPlay() {
                if (this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
            },
            
            resetInterval() {
                this.stopAutoPlay();
                this.startAutoPlay();
            }
        };

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

        document.querySelectorAll('.product-card, .testimoni-card').forEach(el => {
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

        // Initialize banner slider
        document.addEventListener('DOMContentLoaded', () => {
            bannerSlider.init();
        });
    </script>
</body>
</html>