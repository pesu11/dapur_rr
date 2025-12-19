<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $pesananId = intval($_POST['pesanan_id']);
        $status = $_POST['status'];
        $resi = isset($_POST['no_resi']) ? trim($_POST['no_resi']) : '';
        
        $allowedStatus = ['pending', 'dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
        
        if (in_array($status, $allowedStatus)) {
            if ($status === 'dikirim' && !empty($resi)) {
                $stmt = $conn->prepare("UPDATE pesanan SET status = ?, no_resi = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $resi, $pesananId);
            } else {
                $stmt = $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $pesananId);
            }
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Status pesanan berhasil diupdate');
            } else {
                setFlashMessage('error', 'Gagal mengupdate status');
            }
        }
        redirect('pesanan.php');
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];
$types = '';

if ($statusFilter) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($search) {
    $where[] = "(p.no_pesanan LIKE ? OR u.nama LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$countQuery = "SELECT COUNT(*) as total FROM pesanan p 
               JOIN users u ON p.user_id = u.id $whereClause";
$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get orders
$query = "SELECT p.*, u.nama as nama_pelanggan, u.email, 
          COUNT(dp.id) as jumlah_item
          FROM pesanan p
          JOIN users u ON p.user_id = u.id
          LEFT JOIN detail_pesanan dp ON p.id = dp.pesanan_id
          $whereClause
          GROUP BY p.id
          ORDER BY p.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$pesanan = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Dapur RR</title>
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
           
    --primary: #FF69B4;
    --secondary: #FFB6D9;
    --accent: #FF1493;
    --light: #FFF5F8;
    --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
}
        
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            display: flex;
            color: var(--dark);
        }
        
        /* Sidebar - SAMA DENGAN PRODUK.PHP */
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
        
        /* Top Bar - SAMA DENGAN PRODUK.PHP */
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
        
        .filter-group input,
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
        
        .filter-group input:focus,
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
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
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
        
        /* Badges dengan tema pink */
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
            background: linear-gradient(135deg, #FFF3CD 0%, #FFEAA7 100%);
            color: #856404;
            border: 2px solid #FFE082;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #D1ECF1 0%, #B3E0F2 100%);
            color: #0C5460;
            border: 2px solid #A5D8F0;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFB6D9 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #D4EDDA 0%, #A7F3D0 100%);
            color: #065F46;
            border: 2px solid #86EFAC;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #721C24;
            border: 2px solid #FCA5A5;
        }
        
        /* Pagination */
        .pagination {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            background: var(--light);
            border-top: 1px solid rgba(255, 105, 180, 0.1);
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
            
            table {
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
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
            <a href="pesanan.php" class="menu-item active">
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
            <h1 class="page-title">Kelola Pesanan</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span></span>
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
                        <label>Status Pesanan</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="dikonfirmasi" <?php echo $statusFilter === 'dikonfirmasi' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                            <option value="diproses" <?php echo $statusFilter === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="dikirim" <?php echo $statusFilter === 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                            <option value="selesai" <?php echo $statusFilter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="dibatalkan" <?php echo $statusFilter === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari Pesanan</label>
                        <input type="text" name="search" placeholder="No pesanan atau nama pelanggan..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <strong><?php echo $total; ?></strong> Pesanan
                    <?php if ($statusFilter || $search): ?>
                        <span style="color: var(--dark); font-size: 0.9rem; margin-left: 1rem; opacity: 0.7;">
                            (Hasil filter)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pesanan->num_rows > 0): ?>
                        <?php while($p = $pesanan->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary);"><?php echo htmlspecialchars($p['no_pesanan']); ?></strong>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <?php echo $p['jumlah_item']; ?> item
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['nama_pelanggan']); ?></strong>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <?php echo htmlspecialchars($p['email']); ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo date('d M Y', strtotime($p['created_at'])); ?></strong>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <?php echo date('H:i', strtotime($p['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <strong style="color: var(--success); font-size: 1.1rem;"><?php echo formatRupiah($p['total_harga']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'badge-warning';
                                $badgeIcon = '⏳';
                                
                                switch($p['status']) {
                                    case 'dikonfirmasi':
                                        $badgeClass = 'badge-info';
                                        $badgeIcon = '✅';
                                        break;
                                    case 'diproses':
                                        $badgeClass = 'badge-primary';
                                        $badgeIcon = '👨‍🍳';
                                        break;
                                    case 'dikirim':
                                        $badgeClass = 'badge-primary';
                                        $badgeIcon = '🚚';
                                        break;
                                    case 'selesai':
                                        $badgeClass = 'badge-success';
                                        $badgeIcon = '🎉';
                                        break;
                                    case 'dibatalkan':
                                        $badgeClass = 'badge-danger';
                                        $badgeIcon = '❌';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $badgeIcon; ?> <?php echo ucfirst($p['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="pesanan-detail.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                                <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></div>
                                <h3 style="color: var(--dark); margin-bottom: 0.5rem;">Tidak ada pesanan</h3>
                                <p>
                                    <?php if ($statusFilter || $search): ?>
                                        Tidak ada pesanan yang sesuai dengan filter yang dipilih
                                    <?php else: ?>
                                        Belum ada pesanan yang masuk
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>