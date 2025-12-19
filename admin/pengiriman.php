<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $namaMetode = trim($_POST['nama_metode']);
        $biaya = floatval($_POST['biaya']);
        $estimasi = trim($_POST['estimasi']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO metode_pengiriman (nama_metode, biaya, estimasi, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $namaMetode, $biaya, $estimasi, $isActive);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pengiriman berhasil ditambahkan');
        }
        redirect('pengiriman.php');
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $namaMetode = trim($_POST['nama_metode']);
        $biaya = floatval($_POST['biaya']);
        $estimasi = trim($_POST['estimasi']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE metode_pengiriman SET nama_metode = ?, biaya = ?, estimasi = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sdsii", $namaMetode, $biaya, $estimasi, $isActive, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pengiriman berhasil diupdate');
        }
        redirect('pengiriman.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM metode_pengiriman WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pengiriman berhasil dihapus');
        }
        redirect('pengiriman.php');
    }
    
    if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE metode_pengiriman SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        setFlashMessage('success', 'Status berhasil diubah');
        redirect('pengiriman.php');
    }
}

$metodePengiriman = $conn->query("SELECT * FROM metode_pengiriman ORDER BY created_at DESC");
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pengiriman - Admin</title>
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
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s; font-size: 0.95rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .content-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-title { font-size: 1.3rem; margin-bottom: 1.5rem; color: #333; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .shipping-list { display: flex; flex-direction: column; gap: 1rem; }
        .shipping-item { padding: 1.5rem; border: 1px solid #eee; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .shipping-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .shipping-info h3 { color: #333; margin-bottom: 0.5rem; }
        .shipping-info p { color: #666; font-size: 0.9rem; }
        .shipping-actions { display: flex; gap: 0.5rem; }
        .badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
        .modal-close { cursor: pointer; font-size: 1.5rem; color: #999; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2>🧁 Dapur RR</h2><p>Admin Panel</p></div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="produk.php" class="menu-item">🛍️ Produk</a>
            <a href="pesanan.php" class="menu-item">📦 Pesanan</a>
            <a href="pembayaran.php" class="menu-item">💳 Pembayaran</a>
            <a href="pengiriman.php" class="menu-item active">🚚 Pengiriman</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Metode Pengiriman</h1>
            <span>👤 <?php echo getUserName(); ?></span>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h2 class="card-title">Tambah Metode Baru</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Nama Metode *</label>
                        <input type="text" name="nama_metode" placeholder="JNE Regular" required>
                    </div>
                    <div class="form-group">
                        <label>Biaya (Rp) *</label>
                        <input type="number" name="biaya" min="0" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label>Estimasi *</label>
                        <input type="text" name="estimasi" placeholder="2-3 hari" required>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active">Aktif</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">➕ Tambah</button>
                </form>
            </div>

            <div class="card">
                <h2 class="card-title">Daftar Metode (<?php echo $metodePengiriman->num_rows; ?>)</h2>
                <div class="shipping-list">
                    <?php if ($metodePengiriman->num_rows > 0): ?>
                        <?php while($m = $metodePengiriman->fetch_assoc()): ?>
                        <div class="shipping-item">
                            <div class="shipping-info">
                                <h3><?php echo htmlspecialchars($m['nama_metode']); ?></h3>
                                <p>💰 <?php echo formatRupiah($m['biaya']); ?> | ⏱️ <?php echo htmlspecialchars($m['estimasi']); ?></p>
                                <span class="badge <?php echo $m['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $m['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </div>
                            <div class="shipping-actions">
                                <button onclick="edit(<?php echo $m['id']; ?>, '<?php echo addslashes($m['nama_metode']); ?>', <?php echo $m['biaya']; ?>, '<?php echo addslashes($m['estimasi']); ?>', <?php echo $m['is_active']; ?>)" class="btn btn-warning btn-sm">✏️</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm"><?php echo $m['is_active'] ? '👁️' : '🚫'; ?></button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin?')">🗑️</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">Belum ada metode</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Metode</h3><span class="modal-close" onclick="close()">&times;</span></div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Metode *</label>
                    <input type="text" name="nama_metode" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Biaya (Rp) *</label>
                    <input type="number" name="biaya" id="edit_biaya" min="0" step="1000" required>
                </div>
                <div class="form-group">
                    <label>Estimasi *</label>
                    <input type="text" name="estimasi" id="edit_estimasi" required>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_active" value="1">
                        <label>Aktif</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Update</button>
            </form>
        </div>
    </div>

    <script>
        function edit(id, nama, biaya, estimasi, active) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_biaya').value = biaya;
            document.getElementById('edit_estimasi').value = estimasi;
            document.getElementById('edit_active').checked = active == 1;
            document.getElementById('editModal').classList.add('show');
        }
        
        function close() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        window.onclick = function(e) {
            if (e.target.id == 'editModal') close();
        }
    </script>
</body>
</html>