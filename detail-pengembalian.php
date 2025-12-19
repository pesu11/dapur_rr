<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

$pengembalianId = $_GET['id'] ?? 0;

// Ambil data pengembalian
$query = "SELECT pg.*, p.no_pesanan, p.total_bayar, p.created_at as tanggal_pesanan
          FROM pengembalian pg
          JOIN pesanan p ON pg.pesanan_id = p.id
          WHERE pg.id = ? AND pg.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pengembalianId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('riwayat-pesanan.php');
}

$pengembalian = $result->fetch_assoc();

// Fix path bukti foto jika ada
if (!empty($pengembalian['bukti_foto'])) {
    // Bersihkan path yang double
    if (strpos($pengembalian['bukti_foto'], 'uploads/pengembalian/uploads/pengembalian/') !== false) {
        $pengembalian['bukti_foto'] = str_replace('uploads/pengembalian/uploads/pengembalian/', 'uploads/pengembalian/', $pengembalian['bukti_foto']);
    }
}

$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengembalian - <?php echo $pengaturan['nama_toko']; ?></title>
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
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #2C1810;
            --light: #FFF5F8;
            --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            --gradient-2: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
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
        
        /* HEADER SAMA */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
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

        /* STYLING KONTEN DETAIL PENGEMBALIAN */
        .container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            letter-spacing: -1px;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 25px;
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            background: var(--accent);
            color: white;
            transform: translateX(-5px) translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .detail-card {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-lg);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.2);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: var(--shadow-md);
        }

        .status-pending {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }

        .status-disetujui {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            color: var(--info);
            border: 2px solid #93C5FD;
        }

        .status-ditolak {
            background: linear-gradient(135deg, #FEF2F2 0%, #FECACA 100%);
            color: var(--danger);
            border: 2px solid #FCA5A5;
        }

        .status-selesai {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .info-section {
            background: var(--light);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }

        .info-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .info-item {
            margin-bottom: 1.2rem;
        }

        .info-label {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value {
            color: var(--dark);
            font-weight: 500;
            padding-left: 1.5rem;
        }

        .alasan-box {
            background: #F9FAFB;
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid var(--accent);
            margin-top: 1rem;
        }

        .admin-note-box {
            background: #FFFBEB;
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid var(--warning);
            margin-top: 1rem;
        }

        /* STYLE UNTUK BUKTI FOTO */
        .bukti-foto-section {
            grid-column: 1 / -1;
            background: var(--light);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }

        .bukti-foto-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .image-preview {
            max-width: 400px;
            max-height: 400px;
            border-radius: 15px;
            border: 2px solid rgba(255, 105, 180, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .image-preview:hover {
            transform: scale(1.02);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .image-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-download {
            background: var(--gradient-1);
            color: white;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 20, 147, 0.3);
        }

        .btn-view {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .no-image-box {
            background: #F9FAFB;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            border: 2px dashed rgba(255, 105, 180, 0.3);
        }

        .no-image-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* MODAL UNTUK GAMBAR BESAR */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .image-modal.show {
            display: flex;
        }

        .image-modal-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .image-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 10px;
        }

        .image-modal-close {
            position: absolute;
            top: -40px;
            right: -40px;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .image-modal-close:hover {
            background: rgba(255, 20, 147, 0.8);
            transform: rotate(90deg);
        }

        .timeline {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(255, 105, 180, 0.2);
        }

        .timeline h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .timeline-item {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-left: 1rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--accent);
        }

        .timeline-dot {
            width: 20px;
            height: 20px;
            background: var(--accent);
            border-radius: 50%;
            position: absolute;
            left: -9px;
            top: 5px;
            z-index: 1;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-date {
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .timeline-text {
            color: var(--dark);
        }

        .contact-box {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            padding: 2rem;
            border-radius: 20px;
            margin-top: 3rem;
            text-align: center;
        }

        .contact-box h3 {
            color: var(--info);
            margin-bottom: 1rem;
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

            .detail-card {
                padding: 2rem;
            }

            .status-header {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .image-preview {
                max-width: 100%;
            }
            
            .image-modal-close {
                top: -30px;
                right: -30px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }

            .detail-card {
                padding: 1.5rem;
            }
            
            .image-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
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
                    <li><a href="home.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="riwayat-pesanan.php">Pesanan</a></li>
                    <li><a href="promo.php">Promo</a></li>
                </ul>
            </nav>

            <div class="user-menu">
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
    </header>

    <div class="container">
        <a href="riwayat-pesanan.php" class="btn-back">← Kembali ke Riwayat Pesanan</a>

        <h1 class="page-title">Detail Pengembalian</h1>

        <div class="detail-card">
            <div class="status-header">
                <div>
                    <h2 style="font-family: 'Playfair Display', serif; color: var(--dark); margin-bottom: 0.5rem;">
                        Pesanan #<?php echo htmlspecialchars($pengembalian['no_pesanan']); ?>
                    </h2>
                    <p style="color: #666;">ID Pengembalian: #<?php echo $pengembalian['id']; ?></p>
                </div>
                <div class="status-badge status-<?php echo $pengembalian['status']; ?>">
                    <?php 
                    $statusTexts = [
                        'pending' => '⏳ Diproses',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                        'selesai' => '✔️ Selesai'
                    ];
                    echo $statusTexts[$pengembalian['status']] ?? 'Status Tidak Diketahui';
                    ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h3>Informasi Pesanan</h3>
                    <div class="info-item">
                        <div class="info-label">Nomor Pesanan:</div>
                        <div class="info-value">#<?php echo htmlspecialchars($pengembalian['no_pesanan']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Pembelian:</div>
                        <div class="info-value"><?php echo formatRupiah($pengembalian['total_bayar']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Pesanan:</div>
                        <div class="info-value"><?php echo date('d F Y H:i', strtotime($pengembalian['tanggal_pesanan'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Pengajuan:</div>
                        <div class="info-value"><?php echo date('d F Y H:i', strtotime($pengembalian['created_at'])); ?></div>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Alasan Pengembalian</h3>
                    <div class="alasan-box">
                        <?php echo nl2br(htmlspecialchars($pengembalian['alasan'])); ?>
                    </div>
                </div>

                <?php if (!empty($pengembalian['keterangan_admin'])): ?>
                <div class="info-section">
                    <h3>Keterangan Admin</h3>
                    <div class="admin-note-box">
                        <?php echo nl2br(htmlspecialchars($pengembalian['keterangan_admin'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SEKSI BUKTI FOTO -->
                <div class="bukti-foto-section">
                    <h3>Bukti Foto Pengembalian</h3>
                    <div class="bukti-foto-container">
                        <?php if (!empty($pengembalian['bukti_foto'])): 
                            // Tentukan path gambar
                            $imagePath = $pengembalian['bukti_foto'];
                            if (!str_starts_with($imagePath, 'http') && !str_starts_with($imagePath, '/')) {
                                $imagePath = (str_starts_with($imagePath, 'uploads/')) ? $imagePath : 'uploads/pengembalian/' . $imagePath;
                            }
                        ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="Bukti Foto Pengembalian" 
                                 class="image-preview"
                                 onclick="openImageModal('<?php echo htmlspecialchars($imagePath); ?>')"
                                 onerror="this.onerror=null; this.src='assets/no-image.jpg'; this.alt='Gambar tidak ditemukan';">
                            
                            <div class="image-actions">
                                <a href="<?php echo htmlspecialchars($imagePath); ?>" 
                                   download 
                                   class="btn-action btn-download">
                                    Download Bukti
                                </a>
                                <button onclick="openImageModal('<?php echo htmlspecialchars($imagePath); ?>')" 
                                        class="btn-action btn-view">
                                    Lihat Full Size
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="no-image-box">
                                <div class="no-image-icon"></div>
                                <p style="color: #666; margin-bottom: 0.5rem;">Tidak ada bukti foto yang diupload</p>
                                <p style="font-size: 0.9rem; color: #999;">Anda belum mengupload bukti foto untuk pengembalian ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="timeline">
                <h3>Timeline Pengembalian</h3>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?php echo date('d F Y H:i', strtotime($pengembalian['created_at'])); ?></div>
                        <div class="timeline-text">Pengajuan pengembalian dikirim</div>
                    </div>
                </div>

                <?php if ($pengembalian['status'] === 'disetujui'): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?php echo date('d F Y'); ?></div>
                        <div class="timeline-text">Pengembalian disetujui oleh admin</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($pengembalian['status'] === 'ditolak'): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?php echo date('d F Y'); ?></div>
                        <div class="timeline-text">Pengembalian ditolak oleh admin</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($pengembalian['status'] === 'selesai'): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?php echo date('d F Y'); ?></div>
                        <div class="timeline-text">Proses pengembalian selesai</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="contact-box">
                <h3>❓ Butuh Bantuan?</h3>
                <p style="color: var(--dark); margin-bottom: 1rem;">
                    Jika ada pertanyaan mengenai pengembalian ini, hubungi customer service kami:
                </p>
                <p style="font-weight: 700; color: var(--info);">
                    📧 <?php echo htmlspecialchars($pengaturan['email']); ?> <br>
                    📞 <?php echo htmlspecialchars($pengaturan['no_telepon']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Modal untuk gambar besar -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content">
            <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="Bukti Foto">
        </div>
    </div>

    <script>
        function openImageModal(imageSrc) {
            console.log('Opening image modal with src:', imageSrc);
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            document.getElementById('imageModal').classList.add('show');
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('show');
        }
        
        // Close modal ketika klik di luar gambar
        window.onclick = function(e) {
            if (e.target === document.getElementById('imageModal')) {
                closeImageModal();
            }
        }
        
        // Close modal dengan ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
        
        // Handler untuk gambar error
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.image-preview');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = 'assets/no-image.jpg';
                    this.alt = 'Gambar tidak ditemukan';
                });
            });
        });
    </script>
</body>
</html>