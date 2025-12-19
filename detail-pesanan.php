<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pesananId = $_GET['id'] ?? 0;

// Ambil data pesanan
$query = "SELECT p.*, mp.nama_metode as nama_pembayaran, mp.no_rekening, mp.atas_nama,
          mg.nama_metode as nama_pengiriman, mg.biaya as ongkir_metode
          FROM pesanan p
          LEFT JOIN metode_pembayaran mp ON p.metode_pembayaran_id = mp.id
          LEFT JOIN metode_pengiriman mg ON p.metode_pengiriman_id = mg.id
          WHERE p.id = ? AND p.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pesananId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Pesanan tidak ditemukan');
    redirect('riwayat-pesanan.php');
}

$pesanan = $result->fetch_assoc();

// Ambil detail items dengan status rating DAN gambar produk
$detailQuery = "SELECT dp.*, pr.gambar, pr.nama_produk,
                CASE WHEN rp.id IS NOT NULL THEN 1 ELSE 0 END as sudah_rating,
                rp.rating, rp.ulasan
                FROM detail_pesanan dp
                JOIN produk pr ON dp.produk_id = pr.id
                LEFT JOIN rating_produk rp ON dp.produk_id = rp.produk_id AND rp.user_id = ? AND rp.pesanan_id = ?
                WHERE dp.pesanan_id = ?";
$stmt = $conn->prepare($detailQuery);
$stmt->bind_param("iii", $userId, $pesananId, $pesananId);
$stmt->execute();
$details = $stmt->get_result();

$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
$flash = getFlashMessage();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();

$statusColors = [
    'pending' => '#FFB347',
    'dibayar' => '#FF85A1',
    'diproses' => '#FF69B4',
    'dikirim' => '#FF1493',
    'selesai' => '#2E7D32',
    'dibatalkan' => '#D32F2F'
];

$statusText = [
    'pending' => 'Menunggu Pembayaran',
    'dibayar' => 'Sudah Dibayar',
    'diproses' => 'Sedang Diproses',
    'dikirim' => 'Sedang Dikirim',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $pesanan['no_pesanan']; ?> - <?php echo $pengaturan['nama_toko']; ?></title>
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
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* HEADER - Sama seperti index.php */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
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
            color: var(--primary);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        nav {
            background: transparent;
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
        
        nav a:hover {
            color: var(--accent);
            transform: translateY(-2px);
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
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .cart-icon, .wishlist-icon {
            position: relative;
            color: var(--accent);
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 12px;
            background: rgba(255, 182, 217, 0.1);
        }
        
        .cart-icon:hover, .wishlist-icon:hover {
            transform: translateY(-3px);
            background: rgba(255, 182, 217, 0.2);
        }
        
        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--gradient-1);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
            box-shadow: var(--shadow-sm);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-name {
            color: var(--accent);
            font-weight: 600;
            background: rgba(255, 182, 217, 0.2);
            padding: 0.5rem 1.2rem;
            border-radius: 15px;
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
        
        .btn-logout {
            background: rgba(255, 182, 217, 0.2);
            color: var(--accent);
            border: 2px solid rgba(255, 105, 180, 0.3);
            padding: 0.7rem 1.5rem;
            font-size: 0.9rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 40px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            background: var(--accent);
            color: white;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
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
        
        .alert-success {
            background: linear-gradient(135deg, #E5FFEC 0%, #D1FFDC 100%);
            color: #2E7D32;
            border-left: 5px solid #4CAF50;
            box-shadow: var(--shadow-md);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFD1DC 100%);
            color: #D32F2F;
            border-left: 5px solid var(--accent);
            box-shadow: var(--shadow-md);
        }
        
        .order-status-header {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-number {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .status-badge {
            padding: 1rem 2rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1.1rem;
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .order-date {
            color: #666;
            margin-top: 0.5rem;
            font-size: 1rem;
        }
        
        .timeline {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .timeline-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid rgba(255, 105, 180, 0.3);
        }
        
        .timeline-item {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            padding: 0.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover {
            background: rgba(255, 182, 217, 0.1);
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 24px;
            top: 50px;
            bottom: -20px;
            width: 2px;
            background: rgba(255, 105, 180, 0.3);
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            z-index: 1;
            box-shadow: var(--shadow-sm);
        }
        
        .timeline-icon.active {
            background: var(--gradient-1);
            color: white;
        }
        
        .timeline-icon.inactive {
            background: #e0e0e0;
            color: #999;
        }
        
        .timeline-content h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .timeline-content p {
            color: #666;
            font-size: 1rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2.5rem;
        }
        
        .detail-card {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid rgba(255, 105, 180, 0.3);
        }
        
        .product-item {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem 0;
            border-bottom: 2px dashed rgba(255, 105, 180, 0.2);
            align-items: flex-start;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        /* STYLE PRODUCT IMAGE - SAMA SEPERTI INDEX.PHP */
        .product-image {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow-md);
            border: 3px solid rgba(255, 105, 180, 0.2);
            background: linear-gradient(135deg, #FFE5EC 0%, #FFF9E6 100%);
            flex-shrink: 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .product-item:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-info {
            flex: 1;
            min-width: 0; /* Untuk mencegah overflow */
        }
        
        .product-name {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-family: 'Playfair Display', serif;
        }
        
        .product-qty {
            color: var(--primary);
            font-weight: 600;
            font-size: 1rem;
            background: rgba(255, 182, 217, 0.2);
            padding: 0.3rem 1rem;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            font-weight: 800;
            color: var(--accent);
            font-size: 1.3rem;
            white-space: nowrap;
            margin-top: 0.5rem;
        }
        
        .btn-rating {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #FFD166 0%, #FFB347 100%);
            color: white;
            border: none;
            border-radius: 15px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-rating:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 179, 71, 0.4);
        }
        
        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            color: #2E7D32;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-top: 1rem;
            border: 2px solid #4CAF50;
        }
        
        .user-rating {
            margin-top: 1rem;
            padding: 1.2rem;
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            border-radius: 15px;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .user-rating-stars {
            color: #FFD700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1.2rem 0;
            border-bottom: 2px dashed rgba(255, 105, 180, 0.2);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: var(--dark);
            font-weight: 600;
            text-align: right;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.8rem;
            font-weight: 800;
            padding-top: 1.5rem;
            border-top: 3px solid rgba(255, 105, 180, 0.3);
            margin-top: 1rem;
            color: var(--dark);
        }
        
        .total-row span:last-child {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-payment {
            width: 100%;
            padding: 1.2rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-payment::before {
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
        
        .btn-payment:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-payment:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.4);
        }
        
        .btn-tracking {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #6A11CB 0%, #2575FC 100%);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-tracking:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }
        
        .bukti-pembayaran {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            border-radius: 15px;
            border: 3px solid rgba(255, 105, 180, 0.3);
        }
        
        .bukti-pembayaran img {
            max-width: 100%;
            border-radius: 15px;
            margin-top: 1rem;
            box-shadow: var(--shadow-sm);
            border: 3px solid white;
        }
        
        .resi-info {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255, 182, 217, 0.2);
            border-radius: 10px;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .no-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-3);
            color: white;
            font-size: 3rem;
        }
        
        .product-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .rating-section {
            margin-top: 1rem;
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
                margin: 1.5rem auto;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-status-header,
            .timeline,
            .detail-card {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .timeline-title,
            .card-title {
                font-size: 1.5rem;
            }
            
            .product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .product-image {
                width: 100%;
                height: 200px;
            }
            
            .product-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .product-price {
                align-self: flex-start;
            }
            
            .status-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .timeline-item {
                flex-direction: column;
                gap: 1rem;
            }
            
            .timeline-item::before {
                left: 24px;
                top: 50px;
                bottom: -20px;
            }
            
            .product-image {
                height: 150px;
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
                echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: var(--gradient-2); color: white; font-size: 2rem;">🧁</div>';
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
</header>

    <div class="container">
        <a href="riwayat-pesanan.php" class="back-link">← Kembali ke Riwayat Pesanan</a>

        <h1 class="page-title">Detail Pesanan</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '🎉' : '⚠️'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="order-status-header">
            <div class="status-row">
                <div>
                    <div class="order-number">Pesanan #<?php echo htmlspecialchars($pesanan['no_pesanan']); ?></div>
                    <div class="order-date"><?php echo date('d F Y H:i', strtotime($pesanan['created_at'])); ?></div>
                </div>
                <div class="status-badge" style="background: <?php echo $statusColors[$pesanan['status']]; ?>">
                    <?php echo $statusText[$pesanan['status']]; ?>
                </div>
            </div>
        </div>

        <div class="timeline">
            <h2 class="timeline-title">Status Pesanan</h2>
            
            <div class="timeline-item">
                <div class="timeline-icon active">✓</div>
                <div class="timeline-content">
                    <h4>Pesanan Dibuat</h4>
                    <p><?php echo date('d F Y H:i', strtotime($pesanan['created_at'])); ?></p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-icon <?php echo in_array($pesanan['status'], ['dibayar', 'diproses', 'dikirim', 'selesai']) ? 'active' : 'inactive'; ?>">
                    <?php echo in_array($pesanan['status'], ['dibayar', 'diproses', 'dikirim', 'selesai']) ? '✓' : '○'; ?>
                </div>
                <div class="timeline-content">
                    <h4>Pembayaran Dikonfirmasi</h4>
                    <p><?php echo $pesanan['tanggal_pembayaran'] ? date('d F Y H:i', strtotime($pesanan['tanggal_pembayaran'])) : 'Menunggu konfirmasi'; ?></p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-icon <?php echo in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai']) ? 'active' : 'inactive'; ?>">
                    <?php echo in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai']) ? '✓' : '○'; ?>
                </div>
                <div class="timeline-content">
                    <h4>Pesanan Diproses</h4>
                    <p><?php echo $pesanan['status'] == 'diproses' || $pesanan['status'] == 'dikirim' || $pesanan['status'] == 'selesai' ? 'Sedang dikemas' : 'Menunggu proses'; ?></p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-icon <?php echo in_array($pesanan['status'], ['dikirim', 'selesai']) ? 'active' : 'inactive'; ?>">
                    <?php echo in_array($pesanan['status'], ['dikirim', 'selesai']) ? '✓' : '○'; ?>
                </div>
                <div class="timeline-content">
                    <h4>Pesanan Dikirim</h4>
                    <p><?php echo $pesanan['tanggal_kirim'] ? date('d F Y H:i', strtotime($pesanan['tanggal_kirim'])) : 'Menunggu pengiriman'; ?></p>
                    <?php if ($pesanan['resi_pengiriman']): ?>
                    <div class="resi-info">
                        <strong>No. Resi:</strong> <?php echo htmlspecialchars($pesanan['resi_pengiriman']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-icon <?php echo $pesanan['status'] == 'selesai' ? 'active' : 'inactive'; ?>">
                    <?php echo $pesanan['status'] == 'selesai' ? '✓' : '○'; ?>
                </div>
                <div class="timeline-content">
                    <h4>✅ Pesanan Selesai</h4>
                    <p><?php echo $pesanan['tanggal_selesai'] ? date('d F Y H:i', strtotime($pesanan['tanggal_selesai'])) : 'Belum selesai'; ?></p>
                </div>
            </div>
        </div>

        <div class="order-details">
            <div>
                <div class="detail-card">
                    <h2 class="card-title">Detail Produk</h2>
                    <?php while($item = $details->fetch_assoc()): ?>
                    <div class="product-item">
                        <div class="product-image">
                            <?php if (!empty($item['gambar']) && file_exists($item['gambar'])): ?>
                                <img src="<?php echo htmlspecialchars($item['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['nama_produk']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="product-qty"><?php echo $item['jumlah']; ?>x <?php echo formatRupiah($item['harga']); ?></div>
                            
                            <div class="product-details">
                                <div class="rating-section">
                                    <?php if ($pesanan['status'] == 'selesai'): ?>
                                        <?php if ($item['sudah_rating']): ?>
                                            <div class="rating-badge">
                                                <span>⭐</span> Sudah Dirating
                                            </div>
                                            <div class="user-rating">
                                                <div class="user-rating-stars">
                                                    <?php for($i = 0; $i < $item['rating']; $i++): ?>⭐<?php endfor; ?>
                                                </div>
                                                <div style="color: #666; font-size: 0.95rem;"><?php echo htmlspecialchars($item['ulasan']); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <a href="beri-rating.php?pesanan_id=<?php echo $pesananId; ?>&produk_id=<?php echo $item['produk_id']; ?>" class="btn-rating">
                                                ⭐ Beri Rating
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="product-price"><?php echo formatRupiah($item['subtotal']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="detail-card" style="margin-top: 2rem;">
                    <h2 class="card-title">📍 Informasi Pengiriman</h2>
                    <div class="info-row">
                        <span class="info-label">Nama Penerima:</span>
                        <span class="info-value"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Telepon:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pesanan['no_telepon']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Alamat:</span>
                        <span class="info-value" style="text-align: right; max-width: 300px;"><?php echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Metode Pengiriman:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pesanan['nama_pengiriman']); ?></span>
                    </div>
                    <?php if ($pesanan['catatan']): ?>
                    <div class="info-row">
                        <span class="info-label">Catatan:</span>
                        <span class="info-value"><?php echo htmlspecialchars($pesanan['catatan']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="detail-card">
                    <h2 class="card-title">Ringkasan Pembayaran</h2>
                    <div class="info-row">
                        <span class="info-label">Subtotal:</span>
                        <span class="info-value"><?php echo formatRupiah($pesanan['total_harga']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ongkos Kirim:</span>
                        <span class="info-value"><?php echo formatRupiah($pesanan['ongkir']); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Total:</span>
                        <span><?php echo formatRupiah($pesanan['total_bayar']); ?></span>
                    </div>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 3px solid rgba(255, 105, 180, 0.3);">
                        <div class="info-row">
                            <span class="info-label">Metode Pembayaran:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pesanan['nama_pembayaran']); ?></span>
                        </div>
                        
                        <?php if ($pesanan['bukti_pembayaran']): ?>
                        <div class="bukti-pembayaran">
                            <strong>📸 Bukti Pembayaran:</strong>
                            <img src="<?php echo BASE_URL . 'uploads/' . $pesanan['bukti_pembayaran']; ?>" alt="Bukti Pembayaran">
                        </div>
                        <?php elseif ($pesanan['status'] == 'pending' && $pesanan['no_rekening'] !== '-'): ?>
                        <a href="payment.php?pesanan_id=<?php echo $pesanan['id']; ?>" class="btn-payment">
                            Upload Bukti Pembayaran
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($pesanan['status'] == 'dikirim' && $pesanan['resi_pengiriman']): ?>
                    <a href="lacak-pesanan.php?id=<?php echo $pesanan['id']; ?>" class="btn-tracking">
                        📍 Lacak Pengiriman
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image zoom effect
        document.querySelectorAll('.product-image img').forEach(img => {
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.transition = 'transform 0.6s ease';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Add loading animation for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
                this.style.transition = 'opacity 0.3s ease';
            });
            
            if (!img.complete) {
                img.style.opacity = '0';
            }
        });
    </script>
</body>
</html>