<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pesananId = $_GET['pesanan_id'] ?? 0;

// Ambil data pesanan
$query = "SELECT p.*, mp.nama_metode as nama_pembayaran, mp.no_rekening, mp.atas_nama, 
          mg.nama_metode as nama_pengiriman, mg.estimasi
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

// Ambil detail pesanan
$detailQuery = "SELECT dp.*, p.nama_produk, p.gambar 
                FROM detail_pesanan dp
                JOIN produk p ON dp.produk_id = p.id
                WHERE dp.pesanan_id = ?";
$stmt = $conn->prepare($detailQuery);
$stmt->bind_param("i", $pesananId);
$stmt->execute();
$details = $stmt->get_result();

$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - <?php echo $pengaturan['nama_toko']; ?></title>
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
            min-height: 100vh;
        }
        
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
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .logo p {
            color: var(--accent);
            font-weight: 600;
            background: rgba(255, 182, 217, 0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 40px;
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
        
        .success-message {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="confetti" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="3" fill="%23FF69B4" opacity="0.3"/><circle cx="30" cy="30" r="2" fill="%23FF1493" opacity="0.2"/><path d="M20 0 L25 10 L20 20 L15 10 Z" fill="%23FFB6D9" opacity="0.4"/></pattern></defs><rect width="100" height="100" fill="url(%23confetti)"/></svg>');
            opacity: 0.3;
            z-index: 0;
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
            animation: bounce 2s ease-in-out infinite;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .success-message h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .success-message p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .order-number {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 1.5rem 0;
            padding: 1rem 2rem;
            border: 3px solid rgba(255, 105, 180, 0.3);
            border-radius: 20px;
            display: inline-block;
            position: relative;
            z-index: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.8rem 1.8rem;
            background: linear-gradient(135deg, #FFE5B4 0%, #FFD166 100%);
            color: #8B4513;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            border: 2px solid #FFB347;
        }
        
        .payment-info {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .section-title {
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
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100px;
            height: 3px;
            background: var(--gradient-1);
            border-radius: 3px;
        }
        
        .total-amount {
            background: var(--gradient-1);
            color: white;
            padding: 2.5rem;
            border-radius: 25px;
            text-align: center;
            margin: 2rem 0;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: var(--shadow-lg);
            }
            50% {
                box-shadow: 0 10px 40px rgba(255, 20, 147, 0.4);
            }
        }
        
        .total-amount::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .total-label {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .total-value {
            font-size: 3rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .payment-method {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            padding: 2.5rem;
            border-radius: 25px;
            border: 3px solid rgba(255, 105, 180, 0.3);
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .payment-method::before {
            content: '💳';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 4rem;
            opacity: 0.2;
        }
        
        .payment-method h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .account-info {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            margin-top: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .account-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 2px dashed rgba(255, 105, 180, 0.2);
        }
        
        .account-row:last-child {
            border-bottom: none;
        }
        
        .copy-btn {
            background: var(--gradient-1);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .instructions {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
            border: 3px solid #FFC107;
            padding: 2rem;
            border-radius: 20px;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .instructions::before {
            content: '📝';
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .instructions h4 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #8B4513;
            margin-bottom: 1.2rem;
        }
        
        .instructions ol {
            margin-left: 1.5rem;
        }
        
        .instructions li {
            margin-bottom: 0.8rem;
            color: #8B4513;
            font-weight: 500;
        }
        
        .upload-section {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .upload-form {
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        label {
            display: block;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 1.5rem;
            border: 3px dashed var(--primary);
            border-radius: 20px;
            background: rgba(255, 182, 217, 0.1);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        input[type="file"]:hover {
            background: rgba(255, 182, 217, 0.2);
            border-color: var(--accent);
        }
        
        .btn {
            padding: 1.2rem 2.5rem;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
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
        
        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.4);
        }
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-secondary {
            background: white;
            color: var(--accent);
            border: 3px solid var(--accent);
            margin-left: 1rem;
        }
        
        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .order-items {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 2px dashed rgba(255, 105, 180, 0.2);
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            background: rgba(255, 182, 217, 0.1);
            padding-left: 1rem;
            padding-right: 1rem;
            margin: 0 -1rem;
            border-radius: 15px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex: 1;
        }
        
        .item-image {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            object-fit: cover;
            border: 2px solid rgba(255, 105, 180, 0.3);
            box-shadow: var(--shadow-sm);
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--dark);
        }
        
        .item-qty {
            color: var(--primary);
            font-weight: 600;
            font-size: 1rem;
            background: rgba(255, 182, 217, 0.2);
            padding: 0.3rem 1rem;
            border-radius: 15px;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .item-price {
            font-weight: 800;
            color: var(--accent);
            font-size: 1.3rem;
            white-space: nowrap;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1.2rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            font-size: 1.1rem;
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
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.5rem;
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
        
        .shipping-info {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            padding: 2.5rem;
            border-radius: 25px;
            border: 3px solid rgba(255, 105, 180, 0.3);
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease 0.8s both;
        }
        
        .shipping-info::before {
            content: '🚚';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 3rem;
            opacity: 0.3;
        }
        
        .action-buttons {
            text-align: center;
            margin: 3rem 0;
            animation: fadeInUp 0.8s ease 1s both;
        }
        
        .floating-icon {
            animation: float 3s ease-in-out infinite;
            display: inline-block;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
                margin: 2rem auto;
            }
            
            .header-content {
                padding: 1rem 20px;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .success-message,
            .payment-info,
            .order-items,
            .shipping-info,
            .upload-section {
                padding: 2rem;
            }
            
            .total-value {
                font-size: 2.2rem;
            }
            
            .section-title {
                font-size: 1.6rem;
            }
            
            .btn {
                padding: 1rem 2rem;
                width: 100%;
                justify-content: center;
                margin-bottom: 1rem;
            }
            
            .btn-secondary {
                margin-left: 0;
            }
            
            .account-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .item-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .item-price {
                align-self: flex-end;
            }
        }
        
        @media (max-width: 480px) {
            .success-message h2 {
                font-size: 1.8rem;
            }
            
            .order-number {
                font-size: 1.3rem;
                padding: 0.8rem 1.5rem;
            }
            
            .success-icon {
                font-size: 3.5rem;
            }
            
            .total-value {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
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
                        echo '<img src="' . $path . '" alt="Logo" style="width: 50px; height: 50px; border-radius: 15px; object-fit: cover;">';
                        $logo_found = true;
                        break;
                    }
                }
                ?>
                <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
            </div>
            <p>Halaman Pembayaran</p>
        </div>
    </header>

    <div class="container">
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '🎉' : '⚠️'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="success-message">
            <div class="success-icon floating-icon"></div>
            <h2>Pesanan Berhasil Dibuat!</h2>
            <p>Terima kasih telah berbelanja di <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></p>
            <div class="order-number">
                No. Pesanan: <?php echo htmlspecialchars($pesanan['no_pesanan']); ?>
            </div>
            <div class="status-badge">
                ⌛ Menunggu Pembayaran
            </div>
        </div>

        <div class="payment-info">
            <h2 class="section-title">Informasi Pembayaran</h2>
            
            <div class="total-amount">
                <div class="total-label">Total yang harus dibayar:</div>
                <div class="total-value"><?php echo formatRupiah($pesanan['total_bayar']); ?></div>
            </div>

            <div class="payment-method">
                <h3><span class="floating-icon"></span> Metode Pembayaran: <?php echo htmlspecialchars($pesanan['nama_pembayaran']); ?></h3>
                
                <?php if ($pesanan['no_rekening'] !== '-'): ?>
                <div class="account-info">
                    <div class="account-row">
                        <div>
                            <strong style="color: var(--accent);">No. Rekening:</strong><br>
                            <span id="norek"><?php echo htmlspecialchars($pesanan['no_rekening']); ?></span>
                        </div>
                        <button class="copy-btn" onclick="copyText('norek')">Salin</button>
                    </div>
                    <div class="account-row">
                        <div>
                            <strong style="color: var(--accent);">Atas Nama:</strong><br>
                            <span><?php echo htmlspecialchars($pesanan['atas_nama']); ?></span>
                        </div>
                    </div>
                    <div class="account-row">
                        <div>
                            <strong style="color: var(--accent);">Jumlah Transfer:</strong><br>
                            <span id="jumlah"><?php echo $pesanan['total_bayar']; ?></span>
                        </div>
                        <button class="copy-btn" onclick="copyText('jumlah')">Salin</button>
                    </div>
                </div>

                <div class="instructions">
                    <h4>Petunjuk Pembayaran:</h4>
                    <ol>
                        <li>Transfer <strong>sesuai jumlah yang tertera</strong> (pastikan persis)</li>
                        <li>Gunakan <strong>no. rekening di atas</strong> untuk transfer</li>
                        <li><strong>Simpan bukti transfer</strong> Anda</li>
                        <li>Upload bukti transfer di bawah ini</li>
                        <li>Pesanan akan diproses setelah pembayaran dikonfirmasi</li>
                    </ol>
                    <p style="margin-top: 1rem; font-style: italic; color: #8B4513;">
                        Estimasi proses verifikasi: 1-2 jam di hari kerja
                    </p>
                </div>
                <?php else: ?>
                <div class="instructions">
                    <h4>💵 Cash on Delivery (COD):</h4>
                    <p>Pembayaran dilakukan saat barang diterima. Pastikan uang pas sesuai total pembayaran.</p>
                    <p><strong>Estimasi pengiriman:</strong> <?php echo htmlspecialchars($pesanan['estimasi'] ?? '2-3 hari kerja'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pesanan['no_rekening'] !== '-'): ?>
        <div class="upload-section">
            <h2 class="section-title">Upload Bukti Pembayaran</h2>
            <p style="color: #666; margin-bottom: 1.5rem;">Upload bukti transfer Anda untuk mempercepat proses verifikasi</p>
            
            <form method="POST" action="payment-confirmation.php" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="pesanan_id" value="<?php echo $pesananId; ?>">
                
                <div class="form-group">
                    <label for="bukti">
                        <span style="color: var(--accent);">📷</span> Upload Bukti Transfer 
                        <small>(JPG, PNG, max 2MB)</small>
                    </label>
                    <input type="file" id="bukti" name="bukti_pembayaran" accept="image/*" required>
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <span style="font-size: 1.3rem;"></span> Kirim Bukti Pembayaran
                    </button>
                    <a href="riwayat-pesanan.php" class="btn btn-secondary">
                        <span style="font-size: 1.3rem;"></span> Upload Nanti
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="order-items">
            <h2 class="section-title">Detail Pesanan</h2>
            
            <?php 
            $details->data_seek(0);
            while($detail = $details->fetch_assoc()): 
            ?>
            <div class="order-item">
                <div class="item-info">
                    <?php if (!empty($detail['gambar']) && file_exists($detail['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($detail['gambar']); ?>" 
                         alt="<?php echo htmlspecialchars($detail['nama_produk']); ?>" 
                         class="item-image">
                    <?php else: ?>
                    <div class="item-image" style="display: flex; align-items: center; justify-content: center; background: var(--gradient-3); color: white; font-size: 1.5rem;">
                        🧁
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="item-name"><?php echo htmlspecialchars($detail['nama_produk']); ?></div>
                        <div class="item-qty"><?php echo $detail['jumlah']; ?> x <?php echo formatRupiah($detail['harga']); ?></div>
                    </div>
                </div>
                <div class="item-price"><?php echo formatRupiah($detail['subtotal']); ?></div>
            </div>
            <?php endwhile; ?>

            <div class="info-row">
                <span class="info-label">Subtotal Produk:</span>
                <span class="info-value"><?php echo formatRupiah($pesanan['total_harga']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Ongkos Kirim:</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($pesanan['nama_pengiriman']); ?> - 
                    <?php echo formatRupiah($pesanan['ongkir']); ?>
                </span>
            </div>
            <div class="total-row">
                <span><strong>Total Bayar:</strong></span>
                <span><strong><?php echo formatRupiah($pesanan['total_bayar']); ?></strong></span>
            </div>
        </div>

        <div class="shipping-info">
            <h2 class="section-title">📍 Informasi Pengiriman</h2>
            <div class="info-row">
                <span class="info-label">Alamat:</span>
                <span class="info-value"><?php echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">No. Telepon:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['no_telepon']); ?></span>
            </div>
            <?php if ($pesanan['estimasi']): ?>
            <div class="info-row">
                <span class="info-label">Estimasi Pengiriman:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['estimasi']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($pesanan['catatan']): ?>
            <div class="info-row">
                <span class="info-label">Catatan:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['catatan']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">
                <span class="floating-icon"></span> Kembali ke Beranda
            </a>
            <a href="riwayat-pesanan.php" class="btn btn-secondary">
                <span class="floating-icon"></span> Lihat Pesanan Saya
            </a>
        </div>
    </div>

    <script>
        function copyText(elementId) {
            const text = document.getElementById(elementId).textContent;
            const btn = event.target;
            const originalText = btn.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                btn.innerHTML = '✅ Tersalin!';
                btn.style.background = 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)';
                
                setTimeout(() => {
                    btn.innerHTML = 'Salin';
                    btn.style.background = 'linear-gradient(135deg, #FF69B4 0%, #FF1493 100%)';
                }, 2000);
            });
        }

        // File upload preview
        const fileInput = document.getElementById('bukti');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran file maksimal 2MB!');
                        this.value = '';
                        return;
                    }
                    
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (!validTypes.includes(file.type)) {
                        alert('Hanya file JPG, PNG yang diperbolehkan!');
                        this.value = '';
                        return;
                    }
                    
                    // Change border color to indicate success
                    this.style.borderColor = '#4CAF50';
                    this.style.borderStyle = 'solid';
                }
            });
        }

        // Floating animation for success message
        document.addEventListener('DOMContentLoaded', function() {
            const successIcon = document.querySelector('.success-icon');
            let angle = 0;
            
            function rotateIcon() {
                angle = (angle + 1) % 360;
                successIcon.style.transform = `translateY(-10px) rotate(${angle}deg)`;
                requestAnimationFrame(rotateIcon);
            }
            
            if (successIcon) {
                requestAnimationFrame(rotateIcon);
            }
        });

        // Add confirmation before leaving page
        window.addEventListener('beforeunload', function(e) {
            if (document.querySelector('input[type="file"]').value) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki bukti pembayaran yang belum diupload. Yakin ingin meninggalkan halaman?';
            }
        });
    </script>
</body>
</html>