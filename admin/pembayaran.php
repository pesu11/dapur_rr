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
    
    if ($action === 'add') {
        $namaMetode = trim($_POST['nama_metode']);
        $nomorRekening = trim($_POST['no_rekening']);
        $atasNama = trim($_POST['atas_nama']);
        $deskripsi = trim($_POST['deskripsi']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO metode_pembayaran (nama_metode, no_rekening, atas_nama, deskripsi, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $namaMetode, $nomorRekening, $atasNama, $deskripsi, $isActive);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pembayaran berhasil ditambahkan');
        } else {
            setFlashMessage('error', 'Gagal menambahkan metode pembayaran');
        }
        header('Location: pembayaran.php');
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $namaMetode = trim($_POST['nama_metode']);
        $nomorRekening = trim($_POST['no_rekening']);
        $atasNama = trim($_POST['atas_nama']);
        $deskripsi = trim($_POST['deskripsi']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE metode_pembayaran SET nama_metode = ?, no_rekening = ?, atas_nama = ?, deskripsi = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $namaMetode, $nomorRekening, $atasNama, $deskripsi, $isActive, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pembayaran berhasil diupdate');
        } else {
            setFlashMessage('error', 'Gagal mengupdate metode pembayaran');
        }
        header('Location: pembayaran.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM metode_pembayaran WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Metode pembayaran berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus metode pembayaran');
        }
       header('Location: pembayaran.php');
    }
    
    if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE metode_pembayaran SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Status metode pembayaran berhasil diubah');
        }
       header('Location: pembayaran.php');
    }
}

$metodePembayaran = $conn->query("SELECT * FROM metode_pembayaran ORDER BY created_at DESC");
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - Admin Dapur RR</title>
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            display: flex;
            color: var(--dark);
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
        
        .menu-item:hover, .menu-item.active {
            background: var(--light);
            color: var(--accent);
            border-left-color: var(--accent);
            transform: translateX(5px);
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
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-logout {
            background: var(--danger);
            color: white;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid var(--danger);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .card-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
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
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .payment-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .payment-item {
            padding: 1.5rem;
            border: 1px solid rgba(255, 105, 180, 0.1);
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            background: white;
        }
        
        .payment-item:hover {
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        
        .payment-info h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .payment-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .payment-meta {
            color: #999;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
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
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
            font-size: 1.3rem;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 2rem;
            color: #999;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
            
            .main-content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .payment-item {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .payment-actions {
                width: 100%;
                justify-content: flex-start;
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
            <a href="pengeluaran.php" class="menu-item">
                <span>💰</span>
                <span>Pengeluaran</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <span>📈</span>
                <span>Laporan</span>
            </a>
            <a href="analitik.php" class="menu-item">
                <span>📊</span>
                <span>Analitik</span>
            </a>
            <a href="pembayaran.php" class="menu-item active">
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
            <h1 class="page-title">Metode Pembayaran</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
            <?php echo $flash['type'] === 'success' ? '✓' : '✗'; ?>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h2 class="card-title">Tambah Metode Baru</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="nama_metode">Nama Metode *</label>
                        <input type="text" id="nama_metode" name="nama_metode" placeholder="Contoh: Transfer Bank BCA" required>
                    </div>
                    <div class="form-group">
                        <label for="no_rekening">Nomor Rekening</label>
                        <input type="text" id="no_rekening" name="no_rekening" placeholder="Contoh: 1234567890 (opsional)">
                    </div>
                    <div class="form-group">
                        <label for="atas_nama">Atas Nama</label>
                        <input type="text" id="atas_nama" name="atas_nama" placeholder="Contoh: Dapur RR (opsional)">
                    </div>
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" placeholder="Contoh: Transfer ke rekening BCA (opsional)"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active">Aktifkan metode ini</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Tambah Metode</button>
                </form>
            </div>

            <div class="card">
                <h2 class="card-title">Daftar Metode (<?php echo $metodePembayaran->num_rows; ?>)</h2>
                <div class="payment-list">
                    <?php if ($metodePembayaran->num_rows > 0): ?>
                        <?php while($m = $metodePembayaran->fetch_assoc()): ?>
                        <div class="payment-item">
                            <div class="payment-info">
                                <h3><?php echo htmlspecialchars($m['nama_metode']); ?></h3>
                                <?php if (!empty($m['no_rekening'])): ?>
                                <p> <?php echo htmlspecialchars($m['no_rekening']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($m['atas_nama'])): ?>
                                <p> <?php echo htmlspecialchars($m['atas_nama']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($m['deskripsi'])): ?>
                                <p> <?php echo htmlspecialchars($m['deskripsi']); ?></p>
                                <?php endif; ?>
                                <div class="payment-meta">
                                    <span class="badge <?php echo $m['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $m['is_active'] ? '✓ Aktif' : '✗ Nonaktif'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="payment-actions">
                                <button onclick='editPayment(<?php echo json_encode($m); ?>)' class="btn btn-warning btn-sm">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <?php echo $m['is_active'] ? '🚫 Nonaktifkan' : '✓ Aktifkan'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus metode pembayaran ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"> Hapus</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">💳</div>
                            <p>Belum ada metode pembayaran</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Metode Pembayaran</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_nama_metode">Nama Metode *</label>
                    <input type="text" id="edit_nama_metode" name="nama_metode" required>
                </div>
                <div class="form-group">
                    <label for="edit_no_rekening">Nomor Rekening</label>
                    <input type="text" id="edit_no_rekening" name="no_rekening">
                </div>
                <div class="form-group">
                    <label for="edit_atas_nama">Atas Nama</label>
                    <input type="text" id="edit_atas_nama" name="atas_nama">
                </div>
                <div class="form-group">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <textarea id="edit_deskripsi" name="deskripsi"></textarea>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label for="edit_is_active">Aktifkan metode ini</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Update Metode</button>
            </form>
        </div>
    </div>

    <script>
        function editPayment(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama_metode').value = data.nama_metode;
            document.getElementById('edit_no_rekening').value = data.no_rekening || '';
            document.getElementById('edit_atas_nama').value = data.atas_nama || '';
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('edit_is_active').checked = data.is_active == 1;
            document.getElementById('editModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>