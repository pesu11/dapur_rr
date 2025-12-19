<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setFlashMessage('error', 'ID pesanan tidak valid');
    redirect('pesanan.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $status = trim($_POST['status']);
        $noResi = isset($_POST['no_resi']) ? trim($_POST['no_resi']) : '';
        
        // Validasi status
        $allowedStatus = ['pending', 'dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
        if (!in_array($status, $allowedStatus)) {
            setFlashMessage('error', 'Status tidak valid');
            redirect('pesanan-detail.php?id=' . $id);
        }
        
        // Jika status dikirim, resi wajib diisi
        if ($status === 'dikirim' && empty($noResi)) {
            setFlashMessage('error', 'Nomor resi wajib diisi untuk status Dikirim');
            redirect('pesanan-detail.php?id=' . $id);
        }
        
        // Update dengan resi jika ada
        if (!empty($noResi)) {
            $stmt = $conn->prepare("UPDATE pesanan SET status = ?, resi_pengiriman = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $noResi, $id);
        } else {
            $stmt = $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
        }
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Status pesanan berhasil diupdate menjadi: ' . ucfirst($status));
        } else {
            setFlashMessage('error', 'Gagal mengupdate status pesanan');
        }
        
        header("Location: pesanan-detail.php?id=" . $id);
        exit();
    }
}

// Get order data
$stmt = $conn->prepare("SELECT p.*, u.nama, u.email, u.no_telepon,
                        mp.nama_metode as metode_pembayaran,
                        ms.nama_metode as metode_pengiriman
                        FROM pesanan p
                        JOIN users u ON p.user_id = u.id
                        LEFT JOIN metode_pembayaran mp ON p.metode_pembayaran_id = mp.id
                        LEFT JOIN metode_pengiriman ms ON p.metode_pengiriman_id = ms.id
                        WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {
    setFlashMessage('error', 'Pesanan tidak ditemukan');
    redirect('pesanan.php');
}

// Get order items
$stmt = $conn->prepare("SELECT dp.*, p.nama_produk 
                        FROM detail_pesanan dp
                        JOIN produk p ON dp.produk_id = p.id
                        WHERE dp.pesanan_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo htmlspecialchars($pesanan['no_pesanan']); ?> - Admin Dapur RR</title>
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
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --dark: #2C1810;
            --light: #FFF5F8;
            --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            --gradient-2: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
            --gradient-3: linear-gradient(135deg, #FFC1E3 0%, #FFB6D9 100%);
            --shadow-sm: 0 2px 15px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 25px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 40px rgba(255, 105, 180, 0.2);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            display: flex;
            color: var(--dark);
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            border-right: 2px solid rgba(255, 105, 180, 0.1);
            box-shadow: var(--shadow-sm);
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            background: var(--gradient-1);
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .sidebar-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .menu-item {
            padding: 1rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
        }
        
        .menu-item:hover {
            background: var(--light);
            color: var(--accent);
            border-left-color: var(--accent);
            transform: translateX(5px);
        }
        
        .menu-item.active {
            background: var(--light);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background: #FFB6D9;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background: #FF8DC6;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #0da271;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: var(--success);
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: var(--danger);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 105, 180, 0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .card-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.8rem;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .info-label {
            flex: 0 0 150px;
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            flex: 1;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        tr:hover {
            background: var(--light);
        }
        
        .badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            box-shadow: var(--shadow-sm);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #FDE68A 0%, #FBBF24 100%);
            color: #92400e;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #A7F3D0 0%, #10B981 100%);
            color: #065f46;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #FECACA 0%, #EF4444 100%);
            color: #991b1b;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #BFDBFE 0%, #3B82F6 100%);
            color: #1e40af;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
            color: #831843;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            transition: all 0.3s;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
            text-align: right;
            padding-top: 1rem;
            border-top: 2px solid var(--primary);
            margin-top: 1rem;
        }

        /* Bukti Pembayaran Styles */
        .payment-proof {
            margin-top: 1rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 10px;
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .payment-proof-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
            font-size: 1.1rem;
        }

        .proof-image-container {
            position: relative;
            max-width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }

        .proof-image {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .proof-image:hover {
            transform: scale(1.03);
        }

        .no-proof {
            text-align: center;
            padding: 3rem;
            color: #999;
            background: #fafafa;
            border-radius: 10px;
            border: 2px dashed rgba(255, 105, 180, 0.3);
        }

        .no-proof-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .view-full-btn {
            display: inline-block;
            margin: 0.5rem 0.25rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .view-full-btn:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 80%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1001;
        }

        .modal-close:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        .debug-box {
            background: #FFF3CD;
            border: 2px solid #FFC107;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .debug-box strong {
            color: #856404;
        }
        
        /* WhatsApp Button */
        .whatsapp-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #25D366;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s;
            margin-left: 1rem;
            font-size: 0.9rem;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .info-row {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .info-label {
                flex: none;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🧁 Dapur RR</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="produk.php" class="menu-item">🛍️ Produk</a>
            <a href="kategori.php" class="menu-item">📁 Kategori</a>
            <a href="pesanan.php" class="menu-item active">📦 Pesanan</a>
            <a href="pelanggan.php" class="menu-item">👥 Pelanggan</a>
            <a href="artikel2.php" class="menu-item">📝 Artikel</a>
            <a href="banner.php" class="menu-item">🖼️ Banner</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Detail Pesanan #<?php echo htmlspecialchars($pesanan['no_pesanan']); ?></h1>
            <a href="pesanan.php" class="btn btn-secondary">← Kembali ke Daftar Pesanan</a>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div>
                <!-- Customer Info -->
                <div class="card">
                    <h2 class="card-title">👤 Informasi Pelanggan</h2>
                    <div class="info-row">
                        <div class="info-label">Nama:</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($pesanan['nama']); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($pesanan['email']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Telepon:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($pesanan['no_telepon']); ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pesanan['no_telepon']); ?>" 
                               target="_blank" 
                               class="whatsapp-btn">
                                💬 WhatsApp
                            </a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Alamat:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?></div>
                    </div>
                    <?php if (!empty($pesanan['catatan'])): ?>
                    <div class="info-row">
                        <div class="info-label">Catatan:</div>
                        <div class="info-value"><em><?php echo nl2br(htmlspecialchars($pesanan['catatan'])); ?></em></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <h2 class="card-title">📦 Item Pesanan</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                <td><?php echo formatRupiah($item['harga']); ?></td>
                                <td><?php echo $item['jumlah']; ?>x</td>
                                <td><?php echo formatRupiah($item['subtotal']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid var(--light);">
                        <div style="display: flex; justify-content: space-between; padding: 0.8rem 0;">
                            <span style="font-weight: 500;">Subtotal Produk:</span>
                            <span><?php echo formatRupiah($pesanan['total_harga']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.8rem 0;">
                            <span style="font-weight: 500;">Ongkos Kirim:</span>
                            <span><?php echo formatRupiah($pesanan['ongkir']); ?></span>
                        </div>
                    </div>
                    <div class="total-row">
                        Total: <?php echo formatRupiah($pesanan['total_bayar']); ?>
                    </div>
                </div>

                <!-- Bukti Pembayaran -->
                <div class="card">
                    <h2 class="card-title">💳 Bukti Pembayaran</h2>
                    
                    <?php 
                    // Debug mode - set false setelah berhasil
                    $debugMode = false;
                    
                    if ($debugMode) {
                        echo "<div class='debug-box'>";
                        echo "<strong>🔍 DEBUG INFO:</strong><br>";
                        echo "bukti_pembayaran: " . var_export($pesanan['bukti_pembayaran'] ?? 'KOLOM_TIDAK_ADA', true) . "<br>";
                        echo "is null: " . (is_null($pesanan['bukti_pembayaran'] ?? null) ? 'YES' : 'NO') . "<br>";
                        echo "is empty: " . (empty($pesanan['bukti_pembayaran']) ? 'YES' : 'NO') . "<br>";
                        
                       if (isset($pesanan['bukti_pembayaran']) && !empty($pesanan['bukti_pembayaran'])) {
    $testPath = '../uploads/' . $pesanan['bukti_pembayaran'];
                            echo "Expected path: $testPath<br>";
                            echo "File exists: " . (file_exists($testPath) ? '<span style="color:green">✅ YES</span>' : '<span style="color:red">❌ NO</span>') . "<br>";
                            
                            if (file_exists($testPath)) {
                                echo "Real path: " . realpath($testPath) . "<br>";
                                echo "File size: " . number_format(filesize($testPath)/1024, 2) . " KB<br>";
                            }
                        }
                        echo "</div>";
                    }
                    
                    if (isset($pesanan['bukti_pembayaran']) && !empty($pesanan['bukti_pembayaran'])): 
    $buktiFile = $pesanan['bukti_pembayaran'];
    $buktiPath = '../uploads/' . $buktiFile;  // ✅ INI yang benar
                        
                        if (file_exists($buktiPath)): 
                    ?>
                        <div class="payment-proof">
                            <span class="payment-proof-label">📷 Foto Bukti Transfer:</span>
                            <div class="proof-image-container">
                                <img src="<?php echo htmlspecialchars($buktiPath); ?>" 
                                     alt="Bukti Pembayaran" 
                                     class="proof-image"
                                     onclick="openModal('<?php echo htmlspecialchars($buktiPath); ?>')">
                            </div>
                            
                            <div style="margin-top: 1rem; text-align: center;">
                                <a href="<?php echo htmlspecialchars($buktiPath); ?>" 
                                   target="_blank" 
                                   class="view-full-btn">
                                    🔍 Lihat Ukuran Penuh
                                </a>
                                
                                <a href="<?php echo htmlspecialchars($buktiPath); ?>" 
                                   download="bukti_<?php echo $pesanan['no_pesanan']; ?>.<?php echo pathinfo($buktiFile, PATHINFO_EXTENSION); ?>"
                                   class="view-full-btn" 
                                   style="background: var(--success);">
                                    📥 Download
                                </a>
                            </div>
                            
                            <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 8px; font-size: 0.9rem; border: 1px solid rgba(255, 105, 180, 0.1);">
                                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; color: #666;">
                                    <strong>Nama File:</strong>
                                    <span><?php echo htmlspecialchars($buktiFile); ?></span>
                                    
                                    <strong>Ukuran:</strong>
                                    <span><?php echo number_format(filesize($buktiPath)/1024, 2); ?> KB</span>
                                    
                                    <strong>Upload:</strong>
                                    <span><?php echo date('d M Y H:i', filemtime($buktiPath)); ?></span>
                                    
                                    <?php if (!empty($pesanan['tanggal_pembayaran'])): ?>
                                    <strong>Tanggal Bayar:</strong>
                                    <span><?php echo date('d M Y H:i', strtotime($pesanan['tanggal_pembayaran'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="no-proof" style="background: #FFF3CD; border-color: #FFC107;">
                            <div class="no-proof-icon">⚠️</div>
                            <p><strong style="color: #856404;">File Tidak Ditemukan</strong></p>
                            <div style="text-align: left; margin: 1rem auto; max-width: 500px; background: white; padding: 1rem; border-radius: 8px; font-size: 0.85rem; border: 1px solid rgba(255, 193, 7, 0.3);">
                                <p><strong>Nama file:</strong> <?php echo htmlspecialchars($buktiFile); ?></p>
                                <p><strong>Path:</strong> <?php echo htmlspecialchars($buktiPath); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <div class="no-proof">
                            <div class="no-proof-icon">📄</div>
                            <p><strong>Belum Ada Bukti Pembayaran</strong></p>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #666;">
                                Customer belum mengupload bukti transfer
                            </p>
                            
                            <?php if ($pesanan['status'] === 'pending'): ?>
                            <div style="margin-top: 1.5rem;">
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pesanan['no_telepon']); ?>" 
                                   target="_blank" 
                                   class="btn btn-success">
                                    💬 Hubungi Customer
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <!-- Order Status -->
                <div class="card">
                    <h2 class="card-title">📊 Status Pesanan</h2>
                    <div class="info-row">
                        <div class="info-label">Tanggal Pesan:</div>
                        <div class="info-value"><?php echo date('d M Y H:i', strtotime($pesanan['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <?php
                            $badgeClass = 'badge-warning';
                            $statusText = 'Pending';
                            
                            switch($pesanan['status']) {
                                case 'pending':
                                    $badgeClass = 'badge-warning';
                                    $statusText = 'Pending';
                                    break;
                                case 'dikonfirmasi':
                                    $badgeClass = 'badge-info';
                                    $statusText = 'Dikonfirmasi';
                                    break;
                                case 'diproses':
                                    $badgeClass = 'badge-primary';
                                    $statusText = 'Diproses';
                                    break;
                                case 'dikirim':
                                    $badgeClass = 'badge-primary';
                                    $statusText = 'Dikirim';
                                    break;
                                case 'selesai':
                                    $badgeClass = 'badge-success';
                                    $statusText = 'Selesai';
                                    break;
                                case 'dibatalkan':
                                    $badgeClass = 'badge-danger';
                                    $statusText = 'Dibatalkan';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Pembayaran:</div>
                        <div class="info-value"><?php echo htmlspecialchars($pesanan['metode_pembayaran']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Pengiriman:</div>
                        <div class="info-value"><?php echo htmlspecialchars($pesanan['metode_pengiriman']); ?></div>
                    </div>
                    <?php if (!empty($pesanan['no_resi'])): ?>
                    <div class="info-row">
                        <div class="info-label">No. Resi:</div>
                        <div class="info-value"><strong style="color: var(--primary);"><?php echo htmlspecialchars($pesanan['no_resi']); ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($pesanan['tanggal_pembayaran'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tanggal Bayar:</div>
                        <div class="info-value"><?php echo date('d M Y H:i', strtotime($pesanan['tanggal_pembayaran'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Update Status -->
                <div class="card">
                    <h2 class="card-title">Update Status</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <div class="form-group">
                            <label>Status Pesanan:</label>
                            <select name="status" required id="status-select" class="form-control">
                                <option value="pending" <?php echo $pesanan['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="dikonfirmasi" <?php echo $pesanan['status'] === 'dikonfirmasi' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                <option value="diproses" <?php echo $pesanan['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="dikirim" <?php echo $pesanan['status'] === 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="selesai" <?php echo $pesanan['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="dibatalkan" <?php echo $pesanan['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="form-group" id="resi-group" style="<?php echo $pesanan['status'] === 'dikirim' ? 'display: block;' : 'display: none;'; ?>">
                            <label>No. Resi <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="no_resi" id="no_resi" placeholder="Masukkan nomor resi..." value="<?php echo isset($pesanan['no_resi']) ? htmlspecialchars($pesanan['no_resi']) : ''; ?>" <?php echo $pesanan['status'] === 'dikirim' ? 'required' : ''; ?>>
                            <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.3rem;">Wajib diisi untuk status Dikirim</small>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">
                            💾 Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk zoom image -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <div style="text-align: center; margin-top: 1rem;">
            <a id="modalDownload" href="" download class="view-full-btn" onclick="event.stopPropagation();" style="font-size: 1rem;">
                📥 Download Gambar
            </a>
        </div>
    </div>

    <script>
        // Show/hide resi input based on status selection
        document.getElementById('status-select').addEventListener('change', function() {
            const resiGroup = document.getElementById('resi-group');
            const resiInput = document.getElementById('no_resi');
            
            if (this.value === 'dikirim') {
                resiGroup.style.display = 'block';
                resiInput.required = true;
            } else {
                resiGroup.style.display = 'none';
                resiInput.required = false;
            }
        });

        // Modal functions
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const downloadLink = document.getElementById('modalDownload');
            
            modal.classList.add('show');
            modalImg.src = imageSrc;
            downloadLink.href = imageSrc;
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Close modal when clicking outside image
        document.getElementById('imageModal').addEventListener('click', function(event) {
            if (event.target === this || event.target.classList.contains('modal-close')) {
                closeModal();
            }
        });
    </script>
</body>
</html>