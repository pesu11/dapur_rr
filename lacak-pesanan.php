<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pesananId = $_GET['id'] ?? 0;

// Ambil data pesanan
$query = "SELECT p.*, mp.nama_metode as nama_pembayaran, 
          mg.nama_metode as nama_pengiriman
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
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - <?php echo $pengaturan['nama_toko']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-top {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            font-size: 2rem;
        }
        
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 2rem;
        }
        
        .tracking-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .tracking-header h1 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .order-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .status-current {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: #28a745;
            color: white;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .tracking-map {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 2rem;
            color: #667eea;
        }
        
        .tracking-timeline {
            position: relative;
            padding-left: 3rem;
        }
        
        .timeline-line {
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e0e0e0;
        }
        
        .timeline-step {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -2.8rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            z-index: 1;
        }
        
        .timeline-dot.completed {
            background: #28a745;
            color: white;
        }
        
        .timeline-dot.active {
            background: #667eea;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .timeline-dot.pending {
            background: #e0e0e0;
            color: #999;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
        }
        
        .timeline-content {
            background: #f8f9ff;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .timeline-content h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .timeline-content p {
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .timeline-content .time {
            color: #999;
            font-size: 0.9rem;
        }
        
        .resi-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .resi-info h3 {
            margin-bottom: 1rem;
        }
        
        .resi-number {
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
            background: rgba(255,255,255,0.2);
            padding: 1rem 2rem;
            border-radius: 10px;
            display: inline-block;
        }
        
        .delivery-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
            text-align: right;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-top">
            <div class="logo">
                <h1><?php echo $pengaturan['nama_toko']; ?></h1>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="detail-pesanan.php?id=<?php echo $pesanan['id']; ?>" class="back-link">← Kembali ke Detail Pesanan</a>

        <div class="tracking-header">
            <h1>Lacak Pesanan</h1>
            <div class="order-number">Pesanan #<?php echo htmlspecialchars($pesanan['no_pesanan']); ?></div>
            <div class="status-current"><?php echo ucfirst($pesanan['status']); ?></div>
        </div>

        <?php if ($pesanan['resi_pengiriman']): ?>
        <div class="resi-info">
            <h3>Nomor Resi Pengiriman</h3>
            <div class="resi-number"><?php echo htmlspecialchars($pesanan['resi_pengiriman']); ?></div>
            <p style="margin-top: 1rem;">Kurir: <?php echo htmlspecialchars($pesanan['nama_pengiriman']); ?></p>
        </div>
        <?php endif; ?>

        <div class="tracking-map">
            <h2 class="section-title">Status Pengiriman</h2>
            <div class="tracking-timeline">
                <div class="timeline-line"></div>
                
                <div class="timeline-step">
                    <div class="timeline-dot completed">✓</div>
                    <div class="timeline-content">
                        <h3>Pesanan Dibuat</h3>
                        <p>Pesanan Anda telah berhasil dibuat</p>
                        <p class="time"><?php echo date('d F Y H:i', strtotime($pesanan['created_at'])); ?></p>
                    </div>
                </div>

                <div class="timeline-step">
                    <div class="timeline-dot <?php echo in_array($pesanan['status'], ['dibayar', 'diproses', 'dikirim', 'selesai']) ? 'completed' : 'pending'; ?>">
                        <?php echo in_array($pesanan['status'], ['dibayar', 'diproses', 'dikirim', 'selesai']) ? '✓' : '○'; ?>
                    </div>
                    <div class="timeline-content">
                        <h3>Pembayaran Dikonfirmasi</h3>
                        <p>Pembayaran Anda telah kami terima</p>
                        <p class="time"><?php echo $pesanan['tanggal_pembayaran'] ? date('d F Y H:i', strtotime($pesanan['tanggal_pembayaran'])) : 'Menunggu konfirmasi'; ?></p>
                    </div>
                </div>

                <div class="timeline-step">
                    <div class="timeline-dot <?php echo in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai']) ? 'completed' : 'pending'; ?>">
                        <?php echo in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai']) ? '✓' : '○'; ?>
                    </div>
                    <div class="timeline-content">
                        <h3>Pesanan Diproses</h3>
                        <p>Pesanan Anda sedang dikemas</p>
                        <p class="time"><?php echo in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai']) ? 'Sedang diproses' : 'Belum diproses'; ?></p>
                    </div>
                </div>

                <div class="timeline-step">
                    <div class="timeline-dot <?php echo in_array($pesanan['status'], ['dikirim', 'selesai']) ? 'active' : 'pending'; ?>">
                        <?php echo in_array($pesanan['status'], ['dikirim', 'selesai']) ? '🚚' : '○'; ?>
                    </div>
                    <div class="timeline-content">
                        <h3>Dalam Pengiriman</h3>
                        <p>Pesanan dalam perjalanan ke alamat Anda</p>
                        <?php if ($pesanan['resi_pengiriman']): ?>
                        <p>Resi: <strong><?php echo htmlspecialchars($pesanan['resi_pengiriman']); ?></strong></p>
                        <?php endif; ?>
                        <p class="time"><?php echo $pesanan['tanggal_kirim'] ? date('d F Y H:i', strtotime($pesanan['tanggal_kirim'])) : 'Belum dikirim'; ?></p>
                    </div>
                </div>

                <div class="timeline-step">
                    <div class="timeline-dot <?php echo $pesanan['status'] == 'selesai' ? 'completed' : 'pending'; ?>">
                        <?php echo $pesanan['status'] == 'selesai' ? '✓' : '○'; ?>
                    </div>
                    <div class="timeline-content">
                        <h3>Pesanan Diterima</h3>
                        <p>Pesanan telah sampai di tujuan</p>
                        <p class="time"><?php echo $pesanan['tanggal_selesai'] ? date('d F Y H:i', strtotime($pesanan['tanggal_selesai'])) : 'Belum diterima'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="delivery-info">
            <h2 class="section-title">Informasi Pengiriman</h2>
            <div class="info-row">
                <span class="info-label">Alamat Tujuan:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['alamat_pengiriman']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Penerima:</span>
                <span class="info-value"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">No. Telepon:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['no_telepon']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Kurir:</span>
                <span class="info-value"><?php echo htmlspecialchars($pesanan['nama_pengiriman']); ?></span>
            </div>
            <?php if ($pesanan['resi_pengiriman']): ?>
            <div class="info-row">
                <span class="info-label">No. Resi:</span>
                <span class="info-value"><strong><?php echo htmlspecialchars($pesanan['resi_pengiriman']); ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>