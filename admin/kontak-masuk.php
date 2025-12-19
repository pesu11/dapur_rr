<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM kontak_masuk WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Pesan berhasil dihapus');
        }
        $stmt->close();
        
        // Redirect dengan mempertahankan parameter
        $redirect_url = 'kontak-masuk.php?';
        $params = [];
        if (isset($_POST['current_page'])) {
            $params[] = 'page=' . urlencode($_POST['current_page']);
        }
        if (isset($_POST['current_status']) && $_POST['current_status'] !== '') {
            $params[] = 'status=' . urlencode($_POST['current_status']);
        }
        if (!empty($params)) {
            $redirect_url .= implode('&', $params);
        } else {
            $redirect_url = 'kontak-masuk.php';
        }
        header('Location: ' . $redirect_url);
        exit;
    }
    
    if ($action === 'mark_read') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE kontak_masuk SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        setFlashMessage('success', 'Pesan ditandai sudah dibaca');
        
        // Redirect dengan mempertahankan parameter
        $redirect_url = 'kontak-masuk.php?';
        $params = [];
        if (isset($_POST['current_page'])) {
            $params[] = 'page=' . urlencode($_POST['current_page']);
        }
        if (isset($_POST['current_status']) && $_POST['current_status'] !== '') {
            $params[] = 'status=' . urlencode($_POST['current_status']);
        }
        if (!empty($params)) {
            $redirect_url .= implode('&', $params);
        } else {
            $redirect_url = 'kontak-masuk.php';
        }
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where = "WHERE is_read = ?";
    $params[] = intval($statusFilter);
    $types .= 'i';
}

$countQuery = "SELECT COUNT(*) as total FROM kontak_masuk $where";
$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

$query = "SELECT * FROM kontak_masuk $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$pesan = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kontak - Admin Dapur RR</title>
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
            min-height: 100vh;
        }
        
        /* Top Bar */
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
        
        /* Alerts */
        .alert {
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid;
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
        
        /* Filters */
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
        
        /* Buttons */
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
        
        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            background: #0DA875;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        /* Cards */
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
        
        /* Messages List */
        .pesan-list {
            display: flex;
            flex-direction: column;
        }
        
        .pesan-item {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .pesan-item:hover {
            background: var(--light);
            transform: scale(1.002);
        }
        
        .pesan-item.unread {
            background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
            font-weight: 600;
            border-left: 4px solid var(--info);
        }
        
        .pesan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .pesan-from {
            flex: 1;
        }
        
        .pesan-from h3 {
            color: var(--dark);
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }
        
        .pesan-from p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pesan-date {
            color: #999;
            font-size: 0.85rem;
        }
        
        .pesan-subject {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .pesan-preview {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .pesan-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        /* Badges */
        .badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        /* Pagination */
        .pagination {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            border-top: 1px solid rgba(255, 105, 180, 0.1);
            background: var(--light);
        }
        
        .pagination a {
            padding: 0.6rem 1.2rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            transition: all 0.3s;
            background: white;
        }
        
        .pagination a:hover,
        .pagination a.active {
            background: var(--gradient-1);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Modal */
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
            align-items: center;
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
        
        .modal-body {
            line-height: 1.6;
        }
        
        /* Info Rows */
        .info-row {
            display: flex;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 120px;
            color: var(--dark);
            font-weight: 600;
        }
        
        .info-value {
            flex: 1;
            color: #666;
        }
        
        /* Message Content */
        .message-content {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .message-content strong {
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .message-text {
            margin-top: 1rem;
            white-space: pre-wrap;
            line-height: 1.6;
            color: var(--dark);
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        /* Empty State */
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
        
        /* Responsive */
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
            
            .pesan-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .pesan-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-row {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .info-label {
                flex: none;
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .card {
                margin: 0 -1rem;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            
            .pesan-item {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            <a href="pengembalian.php" class="menu-item">
                <span>↩️</span> 
                <span>Pengembalian</span>
            </a>
            <a href="kontak-masuk.php" class="menu-item active">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">Kontak Masuk</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.2rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua Pesan</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Belum Dibaca</option>
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Sudah Dibaca</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
        </div>

        <!-- Messages -->
        <div class="card">
            <div class="card-header">
                <strong style="color: var(--primary); font-size: 1.2rem;"><?php echo $total; ?></strong> Pesan
                <?php if ($statusFilter !== ''): ?>
                    <span style="color: var(--dark); font-size: 0.9rem; margin-left: 1rem; opacity: 0.7;">
                        (Hasil filter)
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($pesan->num_rows > 0): ?>
            <div class="pesan-list">
                <?php while($p = $pesan->fetch_assoc()): 
                    // Sanitasi data untuk JavaScript dengan pengecekan null/empty
                    $nama = isset($p['nama']) ? $p['nama'] : '';
                    $email = isset($p['email']) ? $p['email'] : '';
                    $no_telepon = isset($p['no_telepon']) ? $p['no_telepon'] : '-';
                    $subjek = isset($p['subjek']) ? $p['subjek'] : '';
                    $pesan_text = isset($p['pesan']) ? $p['pesan'] : '';
                    $tanggal = date('d M Y H:i', strtotime($p['created_at']));
                    
                    // Escape untuk onclick - encode untuk JavaScript
                    $js_nama = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
                    $js_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                    $js_telepon = htmlspecialchars($no_telepon, ENT_QUOTES, 'UTF-8');
                    $js_subjek = htmlspecialchars($subjek, ENT_QUOTES, 'UTF-8');
                    $js_pesan = htmlspecialchars($pesan_text, ENT_QUOTES, 'UTF-8');
                ?>
                <div class="pesan-item <?php echo !$p['is_read'] ? 'unread' : ''; ?>" 
                     onclick="viewMessage(<?php echo $p['id']; ?>, '<?php echo $js_nama; ?>', '<?php echo $js_email; ?>', '<?php echo $js_telepon; ?>', '<?php echo $js_subjek; ?>', '<?php echo $js_pesan; ?>', '<?php echo $tanggal; ?>')">
                    <div class="pesan-header">
                        <div class="pesan-from">
                            <h3><?php echo htmlspecialchars($nama); ?></h3>
                            <p><?php echo htmlspecialchars($email); ?> |  <?php echo htmlspecialchars($no_telepon); ?></p>
                        </div>
                        <div class="pesan-date">
                            <?php echo $tanggal; ?>
                        </div>
                    </div>
                    <div class="pesan-subject">
                        <?php echo htmlspecialchars($subjek); ?>
                    </div>
                    <div class="pesan-preview">
                        <?php echo htmlspecialchars(substr($pesan_text, 0, 150)); ?>...
                    </div>
                    <div class="pesan-actions" onclick="event.stopPropagation();">
                        <?php if (!$p['is_read']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="current_page" value="<?php echo $page; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $statusFilter; ?>">
                            <button type="submit" class="btn btn-success btn-sm">✓ Tandai Dibaca</button>
                        </form>
                        <?php else: ?>
                        <span class="badge badge-success">✓ Sudah Dibaca</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="current_page" value="<?php echo $page; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $statusFilter; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus pesan ini?')">🗑️ Hapus</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3 style="color: var(--dark); margin-bottom: 1rem;">Tidak Ada Pesan</h3>
                <p style="color: #666;">
                    <?php if ($statusFilter !== ''): ?>
                        Tidak ada pesan dengan status "<?php echo $statusFilter === '0' ? 'Belum Dibaca' : 'Sudah Dibaca'; ?>"
                    <?php else: ?>
                        Belum ada pesan masuk
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal View -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Pesan</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-row">
                    <div class="info-label">Dari:</div>
                    <div class="info-value" id="view_nama"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="view_email"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Telepon:</div>
                    <div class="info-value" id="view_telepon"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Subjek:</div>
                    <div class="info-value" id="view_subjek"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal:</div>
                    <div class="info-value" id="view_tanggal"></div>
                </div>
                <div class="message-content">
                    <strong>Pesan:</strong>
                    <div class="message-text" id="view_pesan"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewMessage(id, nama, email, telepon, subjek, pesan, tanggal) {
            document.getElementById('view_nama').textContent = nama;
            document.getElementById('view_email').textContent = email;
            document.getElementById('view_telepon').textContent = telepon;
            document.getElementById('view_subjek').textContent = subjek;
            document.getElementById('view_tanggal').textContent = tanggal;
            document.getElementById('view_pesan').textContent = pesan;
            document.getElementById('viewModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>