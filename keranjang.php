<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil data keranjang
$query = "SELECT k.*, p.nama_produk, p.harga, p.is_promo, p.harga_promo, p.stok, p.gambar, kat.nama_kategori
          FROM keranjang k
          JOIN produk p ON k.produk_id = p.id
          LEFT JOIN kategori kat ON p.kategori_id = kat.id
          WHERE k.user_id = ?
          ORDER BY k.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$keranjang = $stmt->get_result();

// Hitung total
$totalHarga = 0;
$items = [];
while($item = $keranjang->fetch_assoc()) {
    $hargaSatuan = $item['is_promo'] ? $item['harga_promo'] : $item['harga'];
    $item['harga_satuan'] = $hargaSatuan;
    $item['subtotal'] = $hargaSatuan * $item['jumlah'];
    $totalHarga += $item['subtotal'];
    $items[] = $item;
}

$flash = getFlashMessage();
$cartCount = count($items);
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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

        /* User menu di header */
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
            content: '🛒';
            position: absolute;
            left: -60px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            animation: cartBounce 1.5s ease-in-out infinite;
        }

        @keyframes cartBounce {
            0%, 100% { transform: translateY(-50%); }
            50% { transform: translateY(-60%); }
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

        /* Cart Layout */
        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
        }

        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .empty-cart-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .empty-cart h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .empty-cart p {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .cart-item {
            display: flex;
            gap: 2rem;
            padding: 2rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 105, 180, 0.05), transparent);
            transition: left 0.5s;
        }

        .cart-item:hover::before {
            left: 100%;
        }

        .cart-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 246, 249, 0.5);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #FFE5EC 0%, #FFF9E6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 3px solid rgba(255, 105, 180, 0.2);
            position: relative;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .cart-item:hover .item-image img {
            transform: scale(1.1) rotate(2deg);
        }

        .item-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gradient-2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(255, 27, 141, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .item-details {
            flex: 1;
        }

        .item-category {
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

        .item-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: var(--dark);
            line-height: 1.3;
        }

        .item-price-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .item-price {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .item-price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .item-stock {
            color: var(--success);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .quantity-control button {
            width: 45px;
            height: 45px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-control button:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .quantity-control input {
            width: 80px;
            text-align: center;
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.3);
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            background: rgba(255, 246, 249, 0.8);
        }

        .btn-delete {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #dc2626;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 2px solid rgba(220, 38, 38, 0.2);
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.2);
        }

        .item-subtotal {
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(255, 105, 180, 0.1);
        }

        .subtotal-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .subtotal-amount {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid rgba(255, 105, 180, 0.2);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
            font-weight: 600;
        }

        .summary-value {
            font-weight: 700;
            color: var(--dark);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.8rem;
            font-weight: 900;
            padding-top: 1.5rem;
            margin-top: 1rem;
            border-top: 3px solid rgba(255, 105, 180, 0.2);
        }

        .total-label {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .total-amount {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-checkout {
             text-decoration: none !important;
            width: 100%;
            padding: 1.2rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-top: 2rem;
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.3);
        }

        .btn-checkout::before {
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

        .btn-checkout:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-checkout:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.5);
        }

        .btn-continue {
            width: 100%;
            padding: 1.2rem;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 1rem;
        }

        .btn-continue:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.3);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .cart-wrapper {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .cart-summary {
                position: static;
                order: -1;
            }
        }

        @media (max-width: 992px) {
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
            .cart-item {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .item-image {
                width: 100%;
                height: 250px;
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

            .cart-items {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }

            .empty-cart {
                padding: 2rem 1rem;
            }

            .empty-cart-icon {
                font-size: 4rem;
            }

            .empty-cart h2 {
                font-size: 1.8rem;
            }

            .item-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .quantity-control {
                width: 100%;
            }

            .quantity-control input {
                flex: 1;
            }

            .btn-delete {
                width: 100%;
                justify-content: center;
            }
        }
        /* Ganti atau tambahkan style berikut */

.auth-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
    animation: fadeInRight 0.8s ease;
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
        <h1 class="page-title">Keranjang Belanja</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
        <div class="cart-items">
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Keranjang Belanja Kosong</h2>
                <p>Tambahkan produk favorit Anda ke keranjang untuk memulai belanja</p>
                <a href="produk.php" class="btn-checkout" style="display: inline-block; width: auto; padding: 1rem 3rem; margin-top: 2rem;">
                    Mulai Belanja
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="cart-wrapper">
            <div class="cart-items">
                <?php foreach($items as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <?php if (!empty($item['gambar']) && file_exists($item['gambar'])): ?>
                            <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                        <?php else: ?>
                            <span style="font-size: 4rem;">🧁</span>
                        <?php endif; ?>
                        <?php if ($item['is_promo']): ?>
                        <span class="item-badge">PROMO</span>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <div class="item-category"><?php echo htmlspecialchars($item['nama_kategori']); ?></div>
                        <h3 class="item-name"><?php echo htmlspecialchars($item['nama_produk']); ?></h3>
                        <div class="item-price-wrapper">
                            <span class="item-price"><?php echo formatRupiah($item['harga_satuan']); ?></span>
                            <?php if ($item['is_promo']): ?>
                            <span class="item-price-old"><?php echo formatRupiah($item['harga']); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="item-stock">Stok tersedia: <?php echo $item['stok']; ?></p>
                        <div class="item-actions">
                            <div class="quantity-control">
                                <form method="POST" action="keranjang-action.php" style="display: inline;">
                                    <input type="hidden" name="produk_id" value="<?php echo $item['produk_id']; ?>">
                                    <input type="hidden" name="jumlah" value="<?php echo max(1, $item['jumlah'] - 1); ?>">
                                    <button type="submit" name="action" value="update">-</button>
                                </form>
                                <input type="number" value="<?php echo $item['jumlah']; ?>" min="1" max="<?php echo $item['stok']; ?>" readonly>
                                <form method="POST" action="keranjang-action.php" style="display: inline;">
                                    <input type="hidden" name="produk_id" value="<?php echo $item['produk_id']; ?>">
                                    <input type="hidden" name="jumlah" value="<?php echo min($item['stok'], $item['jumlah'] + 1); ?>">
                                    <button type="submit" name="action" value="update">+</button>
                                </form>
                            </div>
                            <form method="POST" action="keranjang-action.php" style="display: inline;">
                                <input type="hidden" name="produk_id" value="<?php echo $item['produk_id']; ?>">
                                <button type="submit" name="action" value="delete" class="btn-delete" onclick="return confirm('Hapus produk dari keranjang?')">
                                    Hapus
                                </button>
                            </form>
                        </div>
                        <div class="item-subtotal">
                            <div class="subtotal-label">Subtotal:</div>
                            <div class="subtotal-amount"><?php echo formatRupiah($item['subtotal']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2 class="summary-title">Ringkasan Belanja</h2>
                <div class="summary-row">
                    <span class="summary-label">Total Produk:</span>
                    <span class="summary-value"><?php echo count($items); ?> item</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Harga:</span>
                    <span class="summary-value"><?php echo formatRupiah($totalHarga); ?></span>
                </div>
                <div class="summary-total">
                    <span class="total-label">Total:</span>
                    <span class="total-amount"><?php echo formatRupiah($totalHarga); ?></span>
                </div>
                <br>
                <br>
                <a href="checkout.php" class="btn-checkout">Lanjut ke Checkout</a>
                <br>
                <br>
                <a href="produk.php" class="btn-continue">Lanjut Belanja</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add animation to cart items on load
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.cart-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                item.style.transitionDelay = `${index * 0.1}s`;
                
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
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

            // Add smooth hover effect to delete button
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    button.querySelector('span').textContent = 'Hapus';
                });
                button.addEventListener('mouseleave', () => {
                    button.querySelector('span').textContent = 'Hapus';
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

            console.log('🛒 Cart page loaded with ' + items.length + ' items');
        });

        // Highlight current page in navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('nav a');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes('keranjang')) {
                    link.classList.add('active');
                }
            });
        });

        // Add quantity validation
        document.addEventListener('DOMContentLoaded', function() {
            const quantityButtons = document.querySelectorAll('.quantity-control button');
            
            quantityButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const form = this.closest('form');
                    const hiddenInput = form.querySelector('input[name="jumlah"]');
                    const currentQty = parseInt(form.parentElement.querySelector('input[type="number"]').value);
                    const maxStock = parseInt(form.parentElement.querySelector('input[type="number"]').max);
                    
                    if (this.textContent === '+' && currentQty >= maxStock) {
                        e.preventDefault();
                        alert('Stok tidak mencukupi!');
                    }
                });
            });
        });
    </script>
</body>
</html>