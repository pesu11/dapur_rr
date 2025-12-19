<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    
    // Check if customer has orders
    $checkOrders = $conn->prepare("SELECT COUNT(*) as total FROM pesanan WHERE user_id = ?");
    $checkOrders->bind_param("i", $id);
    $checkOrders->execute();
    $orderCount = $checkOrders->get_result()->fetch_assoc()['total'];
    $checkOrders->close();
    
    if ($orderCount > 0) {
        setFlashMessage('Tidak dapat menghapus pelanggan yang memiliki riwayat pesanan!', 'error');
    } else {
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            setFlashMessage('Pelanggan berhasil dihapus!', 'success');
        } else {
            setFlashMessage('Gagal menghapus pelanggan!', 'error');
        }
        $deleteStmt->close();
    }
    header('Location: pelanggan.php');
}

// Handle Update Status
if (isset($_POST['toggle_status'])) {
    $id = (int)$_POST['user_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $updateStmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'user'");
    $updateStmt->bind_param("ii", $newStatus, $id);
    
    if ($updateStmt->execute()) {
        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        setFlashMessage("Status pelanggan berhasil $statusText!", 'success');
    } else {
        setFlashMessage('Gagal mengubah status pelanggan!', 'error');
    }
    $updateStmt->close();
    header('Location: pelanggan.php');
}

// Handle Edit
if (isset($_POST['edit_pelanggan'])) {
    $id = (int)$_POST['id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    
    if (empty($nama) || empty($email)) {
        setFlashMessage('Nama dan email harus diisi!', 'error');
    } else {
        // Check if email already exists for other users
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmail->bind_param("si", $email, $id);
        $checkEmail->execute();
        $emailExists = $checkEmail->get_result()->num_rows > 0;
        $checkEmail->close();
        
        if ($emailExists) {
            setFlashMessage('Email sudah digunakan oleh pelanggan lain!', 'error');
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, alamat = ? WHERE id = ? AND role = 'user'");
            $updateStmt->bind_param("ssssi", $nama, $email, $no_telepon, $alamat, $id);
            
            if ($updateStmt->execute()) {
                setFlashMessage('Data pelanggan berhasil diperbarui!', 'success');
            } else {
                setFlashMessage('Gagal memperbarui data pelanggan!', 'error');
            }
            $updateStmt->close();
        }
    }
    header('Location: pelanggan.php');
}

// Filters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with proper parameter handling
$where = "WHERE u.role = 'user'";
$countWhere = $where;

if (!empty($search)) {
    $where .= " AND (u.nama LIKE ? OR u.email LIKE ? OR u.no_telepon LIKE ?)";
    $countWhere .= " AND (u.nama LIKE ? OR u.email LIKE ? OR u.no_telepon LIKE ?)";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM users u $countWhere";
if (!empty($search)) {
    $searchParam = "%$search%";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $result = $conn->query($countQuery);
    $total = $result->fetch_assoc()['total'];
}

$totalPages = ceil($total / $limit);

// Get customers with stats
$query = "SELECT u.*, 
          COUNT(DISTINCT p.id) as total_pesanan,
          COALESCE(SUM(CASE WHEN p.status != 'dibatalkan' THEN p.total_harga ELSE 0 END), 0) as total_belanja
          FROM users u
          LEFT JOIN pesanan p ON u.id = p.user_id
          $where
          GROUP BY u.id
          ORDER BY u.created_at DESC
          LIMIT ? OFFSET ?";

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
    $stmt->execute();
    $pelanggan = $stmt->get_result();
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $pelanggan = $stmt->get_result();
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Admin Dapur RR</title>
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
            color: #991B1B;
            border-left-color: #EF4444;
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
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .filter-group input {
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
        
        .filter-group input:focus {
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
        
        .btn-reset {
            background: var(--light);
            color: var(--dark);
            border: 2px solid rgba(255, 105, 180, 0.2);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-reset:hover {
            background: rgba(255, 105, 180, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: var(--info);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-edit:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-delete:hover {
            background: #DC2626;
            transform: translateY(-2px);
        }
        
        .btn-toggle {
            background: var(--warning);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-toggle:hover {
            background: #D97706;
            transform: translateY(-2px);
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light);
        }
        
        .table-header strong {
            color: var(--primary);
            font-size: 1.1rem;
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
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            color: var(--dark);
            vertical-align: middle;
        }
        
        tbody tr:hover {
            background: var(--light);
            transform: scale(1.002);
            transition: all 0.3s;
        }
        
        /* Customer Avatar */
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        
        .customer-info:hover .customer-avatar {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.2rem;
            font-size: 1.1rem;
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
            background: linear-gradient(135deg, #D4EDDA 0%, #A7F3D0 100%);
            color: #065F46;
            border: 2px solid #86EFAC;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #FFF3CD 0%, #FFEAA7 100%);
            color: #856404;
            border: 2px solid #FFE082;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFB6D9 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }
        
        .contact-info {
            font-size: 0.9rem;
            color: #666;
        }
        
        .contact-info div {
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--success);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 3rem auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.3s;
            max-height: calc(100vh - 6rem);
            overflow-y: auto;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }
        
        .modal-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        /* Pagination */
        .pagination {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            background: var(--light);
            border-top: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .pagination a, .pagination span {
            padding: 0.6rem 1.2rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s;
            background: white;
            font-weight: 600;
        }
        
        .pagination a:hover {
            background: var(--gradient-1);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .pagination a.active {
            background: var(--gradient-1);
            color: white;
            border-color: transparent;
            box-shadow: var(--shadow-sm);
        }
        
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
            border-color: #eee;
            background: #f9f9f9;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            line-height: 1.6;
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
            
            table {
                font-size: 0.85rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
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
            <a href="pelanggan.php" class="menu-item active">
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
            <h1 class="page-title">Kelola Pelanggan</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span>🚪</span>
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

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Cari Pelanggan</label>
                        <input type="text" name="search" placeholder="Cari berdasarkan nama, email, atau telepon..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if (!empty($search)): ?>
                    <a href="pelanggan.php" class="btn btn-reset">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <strong><?php echo $total; ?></strong> Pelanggan Terdaftar
                    <?php if ($search): ?>
                        <span style="color: var(--dark); font-size: 0.9rem; margin-left: 1rem; opacity: 0.7;">
                            (Hasil pencarian)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($pelanggan->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="25%">Pelanggan</th>
                        <th width="20%">Kontak</th>
                        <th width="12%">Total Pesanan</th>
                        <th width="13%">Total Belanja</th>
                        <th width="12%">Bergabung</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $pelanggan->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="customer-info">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($p['nama'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="customer-name"><?php echo htmlspecialchars($p['nama']); ?></div>
                                    <?php if ($p['is_active']): ?>
                                    <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">⏸Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="contact-info">
                                <div> <?php echo htmlspecialchars($p['email']); ?></div>
                                <?php if (!empty($p['no_telepon'])): ?>
                                <div> <?php echo htmlspecialchars($p['no_telepon']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary">
                                 <?php echo $p['total_pesanan']; ?> pesanan
                            </span>
                        </td>
                        <td>
                            <div class="stat-value">
                                <?php echo formatRupiah($p['total_belanja']); ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo date('d M Y', strtotime($p['created_at'])); ?></strong><br>
                            <small style="color: #999;">
                                 <?php echo date('H:i', strtotime($p['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="btn btn-edit">
                                    Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin <?php echo $p['is_active'] ? 'menonaktifkan' : 'mengaktifkan'; ?> pelanggan ini?');">
                                    <input type="hidden" name="user_id" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $p['is_active']; ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-toggle">
                                        <?php echo $p['is_active'] ? 'Nonaktifkan' : ' Aktifkan'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus pelanggan ini? Aksi ini tidak dapat dibatalkan!');">
                                    <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-delete">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <h3>Tidak Ada Pelanggan</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Tidak ada pelanggan yang cocok dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        Belum ada pelanggan yang terdaftar
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                    ← Sebelumnya
                </a>
                <?php else: ?>
                <span class="disabled">← Sebelumnya</span>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($start > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>">1</a>
                    <?php if ($start > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $totalPages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                    Selanjutnya →
                </a>
                <?php else: ?>
                <span class="disabled">Selanjutnya →</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Pelanggan</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telepon" id="edit_telepon">
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" id="edit_alamat"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="edit_pelanggan" class="btn btn-primary">💾 Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_telepon').value = data.no_telepon || '';
            document.getElementById('edit_alamat').value = data.alamat || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        
        // Smooth animations for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>