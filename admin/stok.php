<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_stock') {
        $id = intval($_POST['id']);
        $stok = intval($_POST['stok']);
        
        $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE id = ?");
        $stmt->bind_param("ii", $stok, $id);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Stok berhasil diupdate'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Gagal mengupdate stok'
            ];
        }
        
        // Redirect ke halaman yang sama
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = [];
if ($filter === 'low') {
    $where[] = "p.stok < 10";
} elseif ($filter === 'out') {
    $where[] = "p.stok = 0";
}

if ($search) {
    $where[] = "p.nama_produk LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT p.*, k.nama_kategori,
          (SELECT COUNT(*) FROM detail_pesanan dp WHERE dp.produk_id = p.id) as total_terjual
          FROM produk p
          LEFT JOIN kategori k ON p.kategori_id = k.id
          $whereClause
          ORDER BY p.stok ASC, p.nama_produk ASC";
$produk = $conn->query($query);

// Statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_produk,
    SUM(CASE WHEN stok < 10 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as out_stock,
    SUM(stok) as total_stok
    FROM produk")->fetch_assoc();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - Admin Dapur RR</title>
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
            min-height: 100vh;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
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
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background: #e0a800;
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
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
        }
        
        .modal-header h3 {
            color: #333;
            font-size: 1.5rem;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 1.5rem;
            color: #999;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header p,
            .menu-item span:last-child {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
            <a href="dashboard.php" class="menu-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="produk.php" class="menu-item">
                <span>🛍️</span>
                <span>Produk</span>
            </a>
            <a href="kategori.php" class="menu-item">
                <span>📁</span>
                <span>Kategori</span>
            </a>
            <a href="pesanan.php" class="menu-item">
                <span>📦</span>
                <span>Pesanan</span>
            </a>
            <a href="pelanggan.php" class="menu-item">
                <span>👥</span>
                <span>Pelanggan</span>
            </a>
            <a href="stok.php" class="menu-item active">
                <span>📦</span>
                <span>Stok</span>
            </a>
            <a href="artikel2.php" class="menu-item">
                <span>📝</span>
                <span>Artikel</span>
            </a>
            <a href="banner.php" class="menu-item">
                <span>🖼️</span>
                <span>Banner</span>
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
            <h1 class="page-title">Manajemen Stok Produk</h1>
            <div>
                <span>👤 <?php echo htmlspecialchars(getUserName()); ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">📊 Total Produk</div>
                <div class="stat-value"><?php echo number_format($stats['total_produk']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">⚠️ Stok Menipis (&lt;10)</div>
                <div class="stat-value" style="color: #ffc107;">
                    <?php echo number_format($stats['low_stock']); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">🚫 Stok Habis</div>
                <div class="stat-value" style="color: #dc3545;">
                    <?php echo number_format($stats['out_stock']); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">📦 Total Stok Keseluruhan</div>
                <div class="stat-value" style="color: #28a745;">
                    <?php echo number_format($stats['total_stok']); ?>
                </div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Filter Status</label>
                        <select name="filter" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>
                                Semua Produk
                            </option>
                            <option value="low" <?php echo $filter === 'low' ? 'selected' : ''; ?>>
                                Stok Menipis (&lt;10)
                            </option>
                            <option value="out" <?php echo $filter === 'out' ? 'selected' : ''; ?>>
                                Stok Habis
                            </option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>🔍 Cari Produk</label>
                        <input type="text" name="search" placeholder="Nama produk..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <strong style="color: #667eea; font-size: 1.1rem;">
                    <?php echo $produk->num_rows; ?>
                </strong> Produk Ditemukan
            </div>

            <?php if ($produk->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="30%">Produk</th>
                        <th width="15%">Kategori</th>
                        <th width="15%">Stok Saat Ini</th>
                        <th width="15%">Total Terjual</th>
                        <th width="10%">Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $produk->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($p['nama_produk']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($p['nama_kategori']); ?></td>
                        <td>
                            <strong style="font-size: 1.2rem; color: <?php 
                                if ($p['stok'] == 0) echo '#dc3545';
                                elseif ($p['stok'] < 10) echo '#ffc107';
                                else echo '#28a745';
                            ?>;">
                                <?php echo number_format($p['stok']); ?> pcs
                            </strong>
                        </td>
                        <td>
                            <span style="color: #666;">
                                <?php echo number_format($p['total_terjual']); ?> pcs
                            </span>
                        </td>
                        <td>
                            <?php if ($p['stok'] == 0): ?>
                                <span class="badge badge-danger">Habis</span>
                            <?php elseif ($p['stok'] < 10): ?>
                                <span class="badge badge-warning">Menipis</span>
                            <?php else: ?>
                                <span class="badge badge-success">Aman</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="updateStock(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nama_produk']); ?>', <?php echo $p['stok']; ?>)" 
                                    class="btn btn-warning btn-sm">
                                ✏️ Update
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>Tidak Ada Produk</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Tidak ada produk yang cocok dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        Belum ada produk dengan filter yang dipilih
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Stok Produk</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="stok.php">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="id" id="update_id">
                
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" id="update_nama" readonly>
                </div>
                
                <div class="form-group">
                    <label>Stok Baru <span style="color: red;">*</span></label>
                    <input type="number" name="stok" id="update_stok" min="0" required 
                           placeholder="Masukkan jumlah stok baru">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <script>
        function updateStock(id, nama, stok) {
            document.getElementById('update_id').value = id;
            document.getElementById('update_nama').value = nama;
            document.getElementById('update_stok').value = stok;
            document.getElementById('updateModal').classList.add('show');
            
            // Auto focus ke input stok
            setTimeout(() => {
                const input = document.getElementById('update_stok');
                input.focus();
                input.select();
            }, 100);
        }
        
        function closeModal() {
            document.getElementById('updateModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(e) {
            const modal = document.getElementById('updateModal');
            if (e.target === modal) {
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