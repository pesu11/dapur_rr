<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// ========== HANDLE ACTION ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah artikel
    if ($action === 'add') {
        $judul = trim($_POST['judul']);
        $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $judul)));
        $konten = trim($_POST['konten']);
        $status = $_POST['status'] ?? 'draft';
        $is_published = ($status === 'published') ? 1 : 0;
        $author_id = $_SESSION['user_id'] ?? 1;
        $excerpt = substr(strip_tags($konten), 0, 100);

        // Upload gambar jika ada
        $gambar = null;
        if (!empty($_FILES['gambar']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . '_' . basename($_FILES['gambar']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile)) {
                $gambar = $fileName;
            }
        }

        $stmt = $conn->prepare("INSERT INTO artikel (judul, slug, konten, gambar, author_id, views, is_published, created_at) 
                                VALUES (?, ?, ?, ?, ?, 0, ?, NOW())");
        $stmt->bind_param("ssssii", $judul, $slug, $konten, $gambar, $author_id, $is_published);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Artikel berhasil ditambahkan.');
        } else {
            setFlashMessage('error', 'Gagal menambahkan artikel.');
        }
        $stmt->close();
        header('Location: artikel2.php');
        exit;
    }

    // Hapus artikel
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Ambil nama file gambar sebelum dihapus
        $stmt = $conn->prepare("SELECT gambar FROM artikel WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Hapus file gambar jika ada
        if ($result && !empty($result['gambar'])) {
            $filePath = "../uploads/" . $result['gambar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM artikel WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Artikel berhasil dihapus.');
        }
        $stmt->close();
        header('Location: artikel2.php');
    }

    // Toggle publish / draft
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE artikel SET is_published = IF(is_published = 1, 0, 1) WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        setFlashMessage('success', 'Status artikel berhasil diubah.');
        header('Location: artikel2.php');
    }
}

// ========== GET DATA ==========
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "judul LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($statusFilter !== '') {
    $where[] = "is_published = ?";
    $params[] = ($statusFilter === 'published') ? 1 : 0;
    $types .= 'i';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$countQuery = "SELECT COUNT(*) as total FROM artikel $whereClause";
$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($total / $limit);

$query = "SELECT * FROM artikel $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$artikel = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - Admin Dapur RR</title>
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
        
        .actions {
            display: flex;
            gap: 0.8rem;
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
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 2rem 0;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            margin: auto;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid var(--gradient-1);
        }
        
        .modal-header h3 {
            color: var(--dark);
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 2rem;
            color: var(--dark);
            transition: all 0.3s;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: var(--accent);
            transform: rotate(90deg);
        }
        
        /* Form */
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1.2rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
            color: var(--dark);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 105, 180, 0.1);
        }
        
        .form-group textarea {
            min-height: 300px;
            resize: vertical;
            line-height: 1.6;
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
            
            .filter-group {
                width: 100%;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-sm {
                width: 100%;
                justify-content: center;
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
            
            .modal-content {
                padding: 2rem;
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
            <a href="artikel2.php" class="menu-item active">
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
            <h1 class="page-title">Kelola Artikel</h1>
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
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari Artikel</label>
                        <input type="text" name="search" placeholder="Judul artikel..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="artikel2.php" class="btn btn-reset">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <strong><?php echo $total; ?></strong> Artikel
                    <?php if ($search || $statusFilter): ?>
                        <span style="color: var(--dark); font-size: 0.9rem; margin-left: 1rem; opacity: 0.7;">
                            (Hasil filter)
                        </span>
                    <?php endif; ?>
                </div>
                <button onclick="showAddModal()" class="btn btn-primary">Tambah Artikel</button>
            </div>

            <?php if ($artikel->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="40%">Judul</th>
                        <th width="10%">Views</th>
                        <th width="15%">Status</th>
                        <th width="15%">Tanggal</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a = $artikel->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--dark); font-size: 1.1rem;"><?php echo htmlspecialchars($a['judul']); ?></strong>
                            <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem; line-height: 1.5;">
                                <?php echo htmlspecialchars(substr(strip_tags($a['konten']), 0, 80)); ?>...
                            </div>
                        </td>
                        <td>
                            <span style="color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                👁️ <?php echo number_format($a['views']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($a['is_published'] == 1): ?>
                            <span class="badge badge-success">Published</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo date('d M Y', strtotime($a['created_at'])); ?></strong><br>
                            <small style="color: #999;">
                                🕐 <?php echo date('H:i', strtotime($a['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="<?php echo $a['is_published'] == 1 ? 'Jadikan Draft' : 'Publish'; ?>">
                                        <?php echo $a['is_published'] == 1 ? 'Draft' : ' Publish'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus artikel ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
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
                <div class="empty-state-icon"></div>
                <h3>Tidak Ada Artikel</h3>
                <p>
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                        Tidak ada artikel yang cocok dengan filter
                    <?php else: ?>
                        Belum ada artikel yang dibuat
                    <?php endif; ?>
                </p>
                <button onclick="showAddModal()" class="btn btn-primary" style="margin-top: 1.5rem;">Buat Artikel Pertama</button>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">
                    ← Sebelumnya
                </a>
                <?php else: ?>
                <span class="disabled">← Sebelumnya</span>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($start > 1): ?>
                    <a href="?page=1&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">1</a>
                    <?php if ($start > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $totalPages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>">
                    Selanjutnya →
                </a>
                <?php else: ?>
                <span class="disabled">Selanjutnya →</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Artikel Baru</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="judul">Judul Artikel <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="judul" name="judul" placeholder="Masukkan judul artikel..." required>
                </div>
                <div class="form-group">
                    <label for="konten">Konten Artikel <span style="color: var(--danger);">*</span></label>
                    <textarea id="konten" name="konten" placeholder="Tulis konten artikel di sini..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="gambar">Upload Gambar</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">Format: JPG, PNG, GIF (Maks. 2MB)</small>
                </div>
                <div class="form-group">
                    <label for="status">Status Publikasi</label>
                    <select id="status" name="status">
                        <option value="draft">Draft (Belum Dipublikasikan)</option>
                        <option value="published">Published (Langsung Terbit)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.2rem; font-size: 1.1rem; font-weight: 700;">
                    Simpan Artikel
                </button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.add('show');
            // Auto focus pada input judul
            setTimeout(() => {
                document.getElementById('judul').focus();
            }, 100);
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('show');
            // Reset form
            document.querySelector('#addModal form').reset();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
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