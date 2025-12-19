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
    
    if ($action === 'approve') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE testimoni SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Testimoni berhasil disetujui');
        }
        redirect('testimoni.php');
    }
    
    if ($action === 'reject') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE testimoni SET is_approved = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Testimoni berhasil ditolak');
        }
        redirect('testimoni.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM testimoni WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Testimoni berhasil dihapus');
        }
        redirect('testimoni.php');
    }
}

// Get testimonials
$statusFilter = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where = "WHERE t.is_approved = ?";
    $params[] = intval($statusFilter);
    $types .= 'i';
}

$countQuery = "SELECT COUNT(*) as total FROM testimoni t $where";
$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

$query = "SELECT t.*, u.nama, u.email 
          FROM testimoni t
          JOIN users u ON t.user_id = u.id
          $where
          ORDER BY t.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$testimoni = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Testimoni - Admin Dapur RR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid white;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: #333;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .testimoni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .testimoni-card {
            padding: 1.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .testimoni-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .testimoni-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .testimoni-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .testimoni-user h3 {
            font-size: 1rem;
            color: #333;
        }
        
        .rating {
            color: #ffc107;
        }
        
        .testimoni-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .testimoni-meta {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 1rem;
        }
        
        .testimoni-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .pagination {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            border-top: 1px solid #eee;
        }
        
        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #667eea;
        }
        
        .pagination a:hover,
        .pagination a.active {
            background: #667eea;
            color: white;
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
            <a href="pesanan.php" class="menu-item">📦 Pesanan</a>
            <a href="pelanggan.php" class="menu-item">👥 Pelanggan</a>
            <a href="artikel2.php" class="menu-item">📝 Artikel</a>
            <a href="banner.php" class="menu-item">🖼️ Banner</a>
            <a href="testimoni.php" class="menu-item active">⭐ Testimoni</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Kelola Testimoni</h1>
            <div class="user-info">
                <span>👤 <?php echo getUserName(); ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
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
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                </div>
            </form>
        </div>

        <!-- Testimoni Cards -->
        <div class="card">
            <div class="card-header">
                <strong><?php echo $total; ?></strong> Testimoni
            </div>

            <?php if ($testimoni->num_rows > 0): ?>
            <div class="testimoni-grid">
                <?php while($t = $testimoni->fetch_assoc()): ?>
                <div class="testimoni-card">
                    <div class="testimoni-header">
                        <div class="testimoni-avatar">
                            <?php echo strtoupper(substr($t['nama'], 0, 1)); ?>
                        </div>
                        <div class="testimoni-user">
                            <h3><?php echo htmlspecialchars($t['nama']); ?></h3>
                            <div class="rating">
                                <?php for($i = 0; $i < $t['rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <div class="testimoni-content">
                        "<?php echo htmlspecialchars($t['komentar']); ?>"
                    </div>
                    <div class="testimoni-meta">
                        📅 <?php echo date('d M Y H:i', strtotime($t['created_at'])); ?> |
                        <span class="badge <?php echo $t['is_approved'] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $t['is_approved'] ? 'Disetujui' : 'Pending'; ?>
                        </span>
                    </div>
                    <div class="testimoni-actions">
                        <?php if (!$t['is_approved']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                ✅ Setujui
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                ❌ Tolak
                            </button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Yakin hapus testimoni ini?')">
                                🗑️ Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #999;">
                Tidak ada testimoni ditemukan
            </div>
            <?php endif; ?>

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
        </div>
    </div>
</body>
</html>