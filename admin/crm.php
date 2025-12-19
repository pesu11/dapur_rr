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
    
    if ($action === 'add_note') {
        $userId = intval($_POST['user_id']);
        $catatan = trim($_POST['catatan']);
        
        $stmt = $conn->prepare("INSERT INTO interaksi_crm (user_id, catatan) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $catatan);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Catatan berhasil ditambahkan');
        }
        redirect('crm.php');
    }
}

// Customer segmentation
$segment = $_GET['segment'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = ["u.role = 'customer'"];
if ($segment === 'vip') {
    $where[] = "total_belanja >= 1000000";
} elseif ($segment === 'active') {
    $where[] = "total_pesanan >= 3";
} elseif ($segment === 'inactive') {
    $where[] = "total_pesanan = 0";
}

if ($search) {
    $where[] = "(u.nama LIKE '%" . $conn->real_escape_string($search) . "%' OR u.email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$whereClause = "WHERE " . implode(" AND ", $where);

$query = "SELECT u.*, 
    COUNT(DISTINCT p.id) as total_pesanan,
    COALESCE(SUM(p.total_harga), 0) as total_belanja,
    MAX(p.created_at) as last_order,
    (SELECT COUNT(*) FROM interaksi_crm WHERE user_id = u.id) as total_notes
    FROM users u
    LEFT JOIN pesanan p ON u.id = p.user_id
    $whereClause
    GROUP BY u.id
    ORDER BY total_belanja DESC";
$customers = $conn->query($query);

// Stats
$stats = $conn->query("SELECT 
    COUNT(*) as total_customer,
    SUM(CASE WHEN total_belanja >= 1000000 THEN 1 ELSE 0 END) as vip_customer,
    SUM(CASE WHEN total_pesanan >= 3 THEN 1 ELSE 0 END) as active_customer,
    SUM(CASE WHEN total_pesanan = 0 THEN 1 ELSE 0 END) as inactive_customer
    FROM (
        SELECT u.id,
        COALESCE(SUM(p.total_harga), 0) as total_belanja,
        COUNT(p.id) as total_pesanan
        FROM users u
        LEFT JOIN pesanan p ON u.id = p.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id
    ) as customer_stats")->fetch_assoc();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI'; background: #f5f7fa; display: flex; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; height: 100vh; position: fixed; overflow-y: auto; }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .sidebar-menu { padding: 1rem 0; }
        .menu-item { padding: 1rem 1.5rem; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.8rem; transition: all 0.3s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left: 4px solid white; }
        .main-content { margin-left: 250px; flex: 1; padding: 2rem; }
        .top-bar { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; }
        .page-title { font-size: 1.8rem; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2rem; font-weight: bold; color: #667eea; margin: 0.5rem 0; }
        .stat-label { color: #666; font-size: 0.9rem; }
        .filters { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .filter-row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .filter-group select, .filter-group input { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .table-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 1rem; text-align: left; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .customer-avatar { width: 50px; height: 50px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; }
        .customer-info { display: flex; align-items: center; gap: 1rem; }
        .badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 10px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #667eea; }
        .modal-close { cursor: pointer; font-size: 1.5rem; color: #999; }
        .info-row { display: flex; padding: 0.8rem 0; border-bottom: 1px solid #eee; }
        .info-label { flex: 0 0 150px; font-weight: 500; color: #666; }
        .info-value { flex: 1; color: #333; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; min-height: 100px; resize: vertical; }
        .notes-list { margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #eee; }
        .note-item { padding: 1rem; background: #f8f9fa; border-radius: 5px; margin-bottom: 1rem; }
        .note-date { font-size: 0.85rem; color: #999; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2>🧁 Dapur RR</h2><p>Admin Panel</p></div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="crm.php" class="menu-item active">👥 CRM</a>
            <a href="pelanggan.php" class="menu-item">🛍️ Pelanggan</a>
            <a href="pesanan.php" class="menu-item">📦 Pesanan</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Customer Relationship Management</h1>
            <span>👤 <?php echo getUserName(); ?></span>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">👥 Total Customer</div>
                <div class="stat-value"><?php echo number_format($stats['total_customer']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">⭐ VIP Customer (>1jt)</div>
                <div class="stat-value" style="color: #dc3545;"><?php echo number_format($stats['vip_customer']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">✅ Active (≥3 orders)</div>
                <div class="stat-value" style="color: #28a745;"><?php echo number_format($stats['active_customer']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">😴 Inactive (0 orders)</div>
                <div class="stat-value" style="color: #ffc107;"><?php echo number_format($stats['inactive_customer']); ?></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Segmentasi</label>
                        <select name="segment">
                            <option value="all" <?php echo $segment === 'all' ? 'selected' : ''; ?>>Semua Customer</option>
                            <option value="vip" <?php echo $segment === 'vip' ? 'selected' : ''; ?>>VIP (>1jt)</option>
                            <option value="active" <?php echo $segment === 'active' ? 'selected' : ''; ?>>Active (≥3 orders)</option>
                            <option value="inactive" <?php echo $segment === 'inactive' ? 'selected' : ''; ?>>Inactive (0 orders)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari Customer</label>
                        <input type="text" name="search" placeholder="Nama atau email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <strong><?php echo $customers->num_rows; ?></strong> Customer
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Total Pesanan</th>
                        <th>Total Belanja</th>
                        <th>Last Order</th>
                        <th>Segment</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers->num_rows > 0): ?>
                        <?php while($c = $customers->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(substr($c['nama'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($c['nama']); ?></strong>
                                        <div style="font-size: 0.9rem; color: #666;"><?php echo htmlspecialchars($c['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $c['total_pesanan']; ?> pesanan</td>
                            <td><strong><?php echo formatRupiah($c['total_belanja']); ?></strong></td>
                            <td><?php echo $c['last_order'] ? date('d M Y', strtotime($c['last_order'])) : '-'; ?></td>
                            <td>
                                <?php if ($c['total_belanja'] >= 1000000): ?>
                                    <span class="badge badge-danger">⭐ VIP</span>
                                <?php elseif ($c['total_pesanan'] >= 3): ?>
                                    <span class="badge badge-success">✅ Active</span>
                                <?php elseif ($c['total_pesanan'] == 0): ?>
                                    <span class="badge badge-warning">😴 Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="viewCustomer(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nama']); ?>', '<?php echo addslashes($c['email']); ?>', '<?php echo $c['no_telepon']; ?>', <?php echo $c['total_pesanan']; ?>, <?php echo $c['total_belanja']; ?>, '<?php echo $c['last_order']; ?>', <?php echo $c['total_notes']; ?>)" 
                                        class="btn btn-primary btn-sm">
                                    👁️ Detail
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #999;">
                                Tidak ada customer ditemukan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Customer</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="info-row">
                <div class="info-label">Nama:</div>
                <div class="info-value" id="cust_nama"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value" id="cust_email"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon:</div>
                <div class="info-value" id="cust_telepon"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Pesanan:</div>
                <div class="info-value" id="cust_pesanan"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Belanja:</div>
                <div class="info-value" id="cust_belanja"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Last Order:</div>
                <div class="info-value" id="cust_last"></div>
            </div>
            
            <form method="POST" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #eee;">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="user_id" id="cust_id">
                <div class="form-group">
                    <label>Tambah Catatan</label>
                    <textarea name="catatan" placeholder="Tulis catatan tentang customer ini..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Simpan Catatan</button>
            </form>

            <div class="notes-list" id="notesList">
                <h4>📝 Catatan (<span id="notesCount">0</span>)</h4>
                <div id="notesContent"></div>
            </div>
        </div>
    </div>

    <script>
        function viewCustomer(id, nama, email, telepon, pesanan, belanja, lastOrder, notesCount) {
            document.getElementById('cust_id').value = id;
            document.getElementById('cust_nama').textContent = nama;
            document.getElementById('cust_email').textContent = email;
            document.getElementById('cust_telepon').textContent = telepon || '-';
            document.getElementById('cust_pesanan').textContent = pesanan + ' pesanan';
            document.getElementById('cust_belanja').textContent = 'Rp ' + belanja.toLocaleString('id-ID');
            document.getElementById('cust_last').textContent = lastOrder ? new Date(lastOrder).toLocaleDateString('id-ID') : '-';
            document.getElementById('notesCount').textContent = notesCount;
            
            // Load notes via AJAX (simplified - you'd implement this)
            document.getElementById('notesContent').innerHTML = '<p style="color: #999; padding: 1rem;">Catatan akan dimuat di sini...</p>';
            
            document.getElementById('customerModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('customerModal').classList.remove('show');
        }
        
        window.onclick = function(e) {
            if (e.target.id == 'customerModal') closeModal();
        }
    </script>
</body>
</html>