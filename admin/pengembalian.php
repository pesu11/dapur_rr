<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = intval($_POST['id']);
        $status = trim($_POST['status']);
        $keteranganAdmin = trim($_POST['keterangan_admin']);
        
        $allowedStatus = ['pending', 'disetujui', 'ditolak', 'selesai'];
        if (!in_array($status, $allowedStatus)) {
            $_SESSION['flash_message'] = 'Status tidak valid';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $tanggalField = '';
        $tanggalValue = date('Y-m-d H:i:s');
        
        if ($status === 'disetujui' || $status === 'ditolak') {
            $tanggalField = ', tanggal_diproses = ?';
        } elseif ($status === 'selesai') {
            $tanggalField = ', tanggal_selesai = ?';
        }
        
        $query = "UPDATE pengembalian SET status = ?, keterangan_admin = ?" . $tanggalField . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if ($tanggalField) {
            $stmt->bind_param("sssi", $status, $keteranganAdmin, $tanggalValue, $id);
        } else {
            $stmt->bind_param("ssi", $status, $keteranganAdmin, $id);
        }
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['flash_message'] = 'Status pengembalian berhasil diupdate';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Tidak ada perubahan dilakukan';
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'Gagal mengupdate status: ' . $stmt->error;
            $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE p.status = ?" : "";

$query = "SELECT p.*, ps.no_pesanan, ps.total_bayar, u.nama, u.email 
          FROM pengembalian p
          JOIN pesanan ps ON p.pesanan_id = ps.id
          JOIN users u ON ps.user_id = u.id
          $where
          ORDER BY p.created_at DESC";

if ($statusFilter) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $statusFilter);
    $stmt->execute();
    $pengembalian = $stmt->get_result();
} else {
    $pengembalian = $conn->query($query);
}

$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = [
        'message' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type']
    ];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian - Admin Dapur RR</title>
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
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .admin-name {
            background: var(--light);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 105, 180, 0.2);
            color: var(--dark);
            font-weight: 600;
        }
        
        .btn-logout {
            padding: 0.8rem 1.8rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-logout:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .alert {
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left-color: #10B981;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #7F1D1D;
            border-left-color: var(--danger);
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }
        
        .filters {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
            color: var(--dark);
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.95rem;
            font-weight: 600;
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
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-reset {
            background: var(--light);
            color: var(--dark);
            border: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .btn-reset:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            font-size: 1.1rem;
            background: var(--light);
        }
        
        .return-list {
            display: flex;
            flex-direction: column;
        }
        
        .return-item {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
        }
        
        .return-item:hover {
            background: var(--light);
            transform: scale(1.002);
        }
        
        .return-item:last-child {
            border-bottom: none;
        }
        
        .return-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .return-info h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .return-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            color: var(--warning);
            border: 2px solid #FCD34D;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: var(--danger);
            border: 2px solid #FCA5A5;
        }
        
        .reason-box {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            margin-bottom: 1rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .reason-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .admin-note {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid var(--warning);
            margin-bottom: 1rem;
            border: 2px solid rgba(245, 158, 11, 0.1);
        }
        
        .admin-note strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #92400E;
        }

        .bukti-foto-box {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 2px solid rgba(59, 130, 246, 0.2);
        }

        .bukti-foto-box strong {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--dark);
        }

        .bukti-foto-box img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .bukti-foto-box img:hover {
            transform: scale(1.05);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .modal-header h3 {
            color: var(--dark);
            font-size: 1.5rem;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 2rem;
            color: #999;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .info-row {
            display: flex;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 150px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .info-value {
            flex: 1;
            color: #666;
        }

        .info-value img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 0.5rem;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s;
        }

        .info-value img:hover {
            transform: scale(1.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

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
            cursor: pointer;
        }

        .image-modal.show {
            display: flex;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 10px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
        }

        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .image-modal-close:hover {
            transform: scale(1.2);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .menu-item span:last-child {
                display: none;
            }
            
            .menu-item {
                padding: 1rem;
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .return-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .info-row {
                flex-direction: column;
                gap: 0.5rem;
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
            <h2>Dapur RR</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="produk.php" class="menu-item">
                <span>🛒</span>
                <span>Produk</span>
            </a>
            <a href="kategori.php" class="menu-item">
                <span>📂</span>
                <span>Kategori</span>
            </a>
            <a href="pesanan.php" class="menu-item">
                <span>📋</span>
                <span>Pesanan</span>
            </a>
            <a href="pengeluaran.php" class="menu-item">
                <span>💰</span>
                <span>Pengeluaran</span>
            </a>
            <a href="pelanggan.php" class="menu-item">
                <span>👥</span>
                <span>Pelanggan</span>
            </a>
            <a href="artikel2.php" class="menu-item">
                <span>📰</span>
                <span>Artikel</span>
            </a>
            <a href="banner.php" class="menu-item">
                <span>🖼️</span>
                <span>Banner</span>
            </a>
            <a href="pengembalian.php" class="menu-item active">
                <span>↩️</span> 
                <span>Pengembalian</span>
            </a>
            <a href="kontak-masuk.php" class="menu-item">
                <span>📨</span>
                <span>Kontak Masuk</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <span>📈</span>
                <span>Laporan</span>
            </a>
            <a href="analitik.php" class="menu-item">
                <span>📊</span>
                <span>Analitik</span>
            </a>
            <a href="pembayaran.php" class="menu-item">
                <span>💳</span>
                <span>Pembayaran</span>
            </a>
            <a href="pengaturan.php" class="menu-item">
                <span>⚙️</span>
                <span>Pengaturan</span>
            </a>
            <a href="../logout.php" class="menu-item">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">↩️ Pengembalian Produk</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.2rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>🔍 Filter Status</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                            <option value="disetujui" <?php echo $statusFilter === 'disetujui' ? 'selected' : ''; ?>>✅ Disetujui</option>
                            <option value="ditolak" <?php echo $statusFilter === 'ditolak' ? 'selected' : ''; ?>>❌ Ditolak</option>
                            <option value="selesai" <?php echo $statusFilter === 'selesai' ? 'selected' : ''; ?>>✔️ Selesai</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <?php if ($statusFilter): ?>
                    <a href="pengembalian.php" class="btn btn-reset">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <strong style="color: var(--primary); font-size: 1.2rem;"><?php echo $pengembalian->num_rows; ?></strong> Request Pengembalian
            </div>

            <?php if ($pengembalian->num_rows > 0): ?>
            <div class="return-list">
                <?php while($r = $pengembalian->fetch_assoc()): ?>
                <div class="return-item" 
                     data-id="<?php echo $r['id']; ?>"
                     data-no-pesanan="<?php echo htmlspecialchars($r['no_pesanan']); ?>"
                     data-total="<?php echo formatRupiah($r['total_bayar']); ?>"
                     data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                     data-email="<?php echo htmlspecialchars($r['email']); ?>"
                     data-alasan="<?php echo htmlspecialchars($r['alasan']); ?>"
                     data-bukti="<?php echo htmlspecialchars($r['bukti_foto'] ?? ''); ?>"
                     data-status="<?php echo $r['status']; ?>"
                     data-keterangan="<?php echo htmlspecialchars($r['keterangan_admin'] ?? ''); ?>">
                    <div class="return-header">
                        <div class="return-info">
                            <h3> Pesanan #<?php echo htmlspecialchars($r['no_pesanan']); ?></h3>
                            <p> Total : <?php echo formatRupiah($r['total_bayar']); ?></p>
                            <p>Nama : <?php echo htmlspecialchars($r['nama']); ?></p>
                            <p>Email : <?php echo htmlspecialchars($r['email']); ?></p>
                            <p> <?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></p>
                        </div>
                        <div>
                            <?php
                            $badgeClass = 'badge-warning';
                            $statusText = 'Pending';
                            if ($r['status'] === 'disetujui') {
                                $badgeClass = 'badge-success';
                                $statusText = 'Disetujui';
                            }
                            if ($r['status'] === 'ditolak') {
                                $badgeClass = 'badge-danger';
                                $statusText = 'Ditolak';
                            }
                            if ($r['status'] === 'selesai') {
                                $badgeClass = 'badge-success';
                                $statusText = 'Selesai';
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="reason-box">
                        <strong>Alasan Pengembalian:</strong>
                        <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($r['alasan']); ?></p>
                    </div>

                    <?php if ($r['bukti_foto'] && file_exists('../' . $r['bukti_foto'])): ?>
                    <div class="bukti-foto-box">
                        <strong>Bukti Foto:</strong>
                        <img src="../<?php echo htmlspecialchars($r['bukti_foto']); ?>" 
                             alt="Bukti Foto" 
                             onclick="openImageModal(this.src)"
                             title="Klik untuk memperbesar">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($r['keterangan_admin']): ?>
                    <div class="admin-note">
                        <strong>Keterangan Admin:</strong>
                        <p style="color: #92400E; margin: 0;"><?php echo htmlspecialchars($r['keterangan_admin']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <button onclick="viewReturn(<?php echo $r['id']; ?>)" 
                            class="btn btn-primary btn-sm" style="margin-top: 1rem;">
                        Detail & Update Status
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"></div>
                <h3 style="color: var(--dark); margin-bottom: 1rem;">Tidak Ada Request Pengembalian</h3>
                <p style="color: #666;">
                    <?php if ($statusFilter): ?>
                        Tidak ada request dengan status "<?php echo htmlspecialchars($statusFilter); ?>"
                    <?php else: ?>
                        Belum ada request pengembalian produk
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Pengembalian</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div style="margin-bottom: 2rem;">
                <div class="info-row">
                    <div class="info-label">No Pesanan:</div>
                    <div class="info-value" id="view_no_pesanan"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total:</div>
                    <div class="info-value" id="view_total"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pelanggan:</div>
                    <div class="info-value" id="view_nama"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="view_email"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Alasan:</div>
                    <div class="info-value" id="view_alasan"></div>
                </div>
                <div class="info-row" id="bukti-row" style="display: none;">
                    <div class="info-label">Bukti Foto:</div>
                    <div class="info-value" id="view_bukti"></div>
                </div>
            </div>
            
            <form method="POST" style="padding-top: 2rem; border-top: 2px solid rgba(255, 105, 180, 0.1);">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="view_id">
                
                <div class="form-group">
                    <label>Status Pengembalian *</label>
                    <select name="status" id="view_status" required>
                        <option value="pending">Pending (Menunggu Review)</option>
                        <option value="disetujui">Disetujui (Terima Pengembalian)</option>
                        <option value="ditolak">Ditolak (Tolak Pengembalian)</option>
                        <option value="selesai">Selesai (Proses Selesai)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Keterangan Admin</label>
                    <textarea name="keterangan_admin" id="view_keterangan" placeholder="Tambahkan keterangan atau alasan keputusan..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
            </form>
        </div>
    </div>

    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <span class="image-modal-close">&times;</span>
        <img id="modalImage" src="" alt="Bukti Foto">
    </div>

    <script>
        function viewReturn(id) {
            const returnItem = document.querySelector(`.return-item[data-id="${id}"]`);
            
            if (!returnItem) {
                console.error('Return item not found for ID:', id);
                return;
            }
            
            const noPesanan = returnItem.dataset.noPesanan;
            const total = returnItem.dataset.total;
            const nama = returnItem.dataset.nama;
            const email = returnItem.dataset.email;
            const alasan = returnItem.dataset.alasan;
            const bukti = returnItem.dataset.bukti;
            const status = returnItem.dataset.status;
            const keterangan = returnItem.dataset.keterangan;
            
            document.getElementById('view_id').value = id;
            document.getElementById('view_no_pesanan').textContent = noPesanan;
            document.getElementById('view_total').textContent = total;
            document.getElementById('view_nama').textContent = nama;
            document.getElementById('view_email').textContent = email;
            document.getElementById('view_alasan').textContent = alasan;
            document.getElementById('view_status').value = status;
            document.getElementById('view_keterangan').value = keterangan;
            
            if (bukti) {
                document.getElementById('bukti-row').style.display = 'flex';
                document.getElementById('view_bukti').innerHTML = `
                    <img src="../${bukti}" alt="Bukti Foto" onclick="openImageModal('../${bukti}')" title="Klik untuk memperbesar">
                `;
            } else {
                document.getElementById('bukti-row').style.display = 'none';
            }
            
            document.getElementById('viewModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('show');
        }

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('show');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('show');
        }
        
        window.onclick = function(e) {
            if (e.target.id == 'viewModal') {
                closeModal();
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeImageModal();
            }
        });

        const alert = document.querySelector('.alert');
        if(alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(-20px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>