<?php
session_start();
require_once '../config/database.php';

// Check admin login
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$conn = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_promo' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE produk SET is_promo = NOT is_promo WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Status promo berhasil diubah'
            ];
        }
        header('Location: produk.php');
        exit();
    }
}

// Ambil filter
$kategoriFilter = $_GET['kategori'] ?? '';
$promoFilter = $_GET['promo'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];
$types = '';

if ($kategoriFilter) {
    $where[] = "p.kategori_id = ?";
    $params[] = $kategoriFilter;
    $types .= 'i';
}

if ($promoFilter !== '') {
    $where[] = "p.is_promo = ?";
    $params[] = $promoFilter;
    $types .= 'i';
}

if ($search) {
    $where[] = "p.nama_produk LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$countQuery = "SELECT COUNT(*) as total FROM produk p $whereClause";
if ($params) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($countQuery)->fetch_assoc()['total'];
}
$totalPages = ceil($total / $limit);

// Get products
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          LEFT JOIN kategori k ON p.kategori_id = k.id 
          $whereClause 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (count($params) > 2) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$produk = $stmt->get_result();

// Get kategori untuk filter
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

$flash = getFlashMessage();

// Helper function untuk mendapatkan path gambar yang benar
function getProductImage($imagePath) {
    if (empty($imagePath)) {
        return false;
    }
    
    // Coba beberapa kemungkinan path
    $possiblePaths = [
        $imagePath, // Path asli dari database
        '../' . $imagePath, // Dengan prefix ../ jika relative path
        'uploads/produk/' . basename($imagePath), // Dari folder uploads/produk
        '../uploads/produk/' . basename($imagePath) // Dari folder uploads/produk dengan ../
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin Dapur RR</title>
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
        
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-warning:hover {
            background: #E69500;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
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
        
        /* Product Info - DIUBAH untuk menampilkan gambar */
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .product-image {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 2px solid rgba(255, 105, 180, 0.2);
            background: white;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-image:hover img {
            transform: scale(1.1);
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
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: var(--danger);
            border: 2px solid #FCA5A5;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            color: var(--warning);
            border: 2px solid #FCD34D;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
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
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
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
            
            .actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            th, td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .product-image {
                width: 50px;
                height: 50px;
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
            <a href="produk.php" class="menu-item active">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">Kelola Produk</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span></span>
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
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php while($k = $kategori->fetch_assoc()): ?>
                            <option value="<?php echo $k['id']; ?>" <?php echo $kategoriFilter == $k['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($k['nama_kategori']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status Promo</label>
                        <select name="promo">
                            <option value="">Semua</option>
                            <option value="1" <?php echo $promoFilter === '1' ? 'selected' : ''; ?>>Promo</option>
                            <option value="0" <?php echo $promoFilter === '0' ? 'selected' : ''; ?>>Normal</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari Produk</label>
                        <input type="text" name="search" placeholder="Nama produk..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="produk.php" class="btn" style="background: var(--light); color: var(--dark); border: 2px solid rgba(255, 105, 180, 0.2); margin-left: 0.5rem;">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <strong style="color: var(--primary); font-size: 1.1rem;"><?php echo $total; ?></strong> Produk
                    <?php if ($kategoriFilter || $promoFilter !== '' || $search): ?>
                        <span style="color: var(--dark); font-size: 0.9rem; margin-left: 1rem; opacity: 0.7;">
                            (Hasil filter)
                        </span>
                    <?php endif; ?>
                </div>
                <a href="produk-tambah.php" class="btn btn-primary">Tambah Produk</a>
            </div>

            <?php if ($produk->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="35%">Produk</th>
                        <th width="12%">Kategori</th>
                        <th width="15%">Harga</th>
                        <th width="10%">Stok</th>
                        <th width="10%">Status</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $produk->fetch_assoc()): 
                        // Gunakan fungsi helper untuk mendapatkan path gambar
                        $imagePath = getProductImage($p['gambar']);
                    ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-image">
                                    <?php if ($imagePath): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                             alt="<?php echo htmlspecialchars($p['nama_produk']); ?>"
                                             loading="lazy"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <!-- Fallback untuk produk tanpa gambar -->
                                        <div style="width: 100%; height: 100%; background: var(--gradient-1); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem;">
                                            🧁
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong style="color: var(--dark); font-size: 1rem;"><?php echo htmlspecialchars($p['nama_produk']); ?></strong>
                                    <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                                        <?php echo htmlspecialchars(substr($p['deskripsi'], 0, 50)); ?>...
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary">
                                <?php echo htmlspecialchars($p['nama_kategori']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['is_promo']): ?>
                                <div>
                                    <strong style="color: var(--danger); font-size: 1.1rem;"><?php echo formatRupiah($p['harga_promo']); ?></strong>
                                    <div style="text-decoration: line-through; font-size: 0.85rem; color: #999;">
                                        <?php echo formatRupiah($p['harga']); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <strong style="color: var(--dark); font-size: 1.1rem;"><?php echo formatRupiah($p['harga']); ?></strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['stok'] > 10): ?>
                                <span class="badge badge-success"><?php echo $p['stok']; ?> pcs</span>
                            <?php elseif ($p['stok'] > 0): ?>
                                <span class="badge badge-warning"> <?php echo $p['stok']; ?> pcs</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Habis</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['is_promo']): ?>
                                <span class="badge badge-danger">🔥 PROMO</span>
                            <?php else: ?>
                                <span class="badge badge-success">✅ Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="produk-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm">
                                    Edit
                                </a>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="action" value="toggle_promo" class="btn btn-success btn-sm" title="Toggle Promo">
                                        <?php echo $p['is_promo'] ? '❌' : '⭐'; ?>
                                    </button>
                                </form>
                                <a href="hapus-produk.php?id=<?php echo $p['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin ingin menghapus produk \'<?php echo addslashes($p['nama_produk']); ?>\'?\n\nProduk yang sudah dihapus tidak dapat dikembalikan!')">
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🛍️</div>
                <h3 style="color: var(--dark); margin-bottom: 1rem;">Tidak Ada Produk</h3>
                <p style="color: #666;">
                    <?php if (!empty($search) || !empty($kategoriFilter) || $promoFilter !== ''): ?>
                        Tidak ada produk yang sesuai dengan filter yang dipilih
                    <?php else: ?>
                        Belum ada produk. Klik tombol "Tambah Produk" untuk menambahkan produk baru
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&kategori=<?php echo $kategoriFilter; ?>&promo=<?php echo $promoFilter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Debug untuk melihat path gambar
        console.log('Debug: Checking image paths...');
        <?php 
        $produk->data_seek(0); // Reset pointer untuk debugging
        while($p = $produk->fetch_assoc()): 
            $imagePath = getProductImage($p['gambar']);
        ?>
        console.log('Product: <?php echo addslashes($p['nama_produk']); ?>');
        console.log('Original path: <?php echo addslashes($p['gambar']); ?>');
        console.log('Found path: <?php echo addslashes($imagePath ?: 'NOT FOUND'); ?>');
        <?php endwhile; ?>
    </script>
</body>
</html>