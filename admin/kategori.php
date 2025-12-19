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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $namaKategori = trim($_POST['nama_kategori']);
        $deskripsi = trim($_POST['deskripsi']);
        
        if (!empty($namaKategori)) {
            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
            $stmt->bind_param("ss", $namaKategori, $deskripsi);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil ditambahkan'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal menambahkan kategori'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Nama kategori wajib diisi'];
        }
        header('Location: kategori.php');
        exit();
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $namaKategori = trim($_POST['nama_kategori']);
        $deskripsi = trim($_POST['deskripsi']);
        
        if ($id > 0 && !empty($namaKategori)) {
            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id = ?");
            $stmt->bind_param("ssi", $namaKategori, $deskripsi, $id);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil diupdate'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengupdate kategori'];
            }
        }
        header('Location: kategori.php');
        exit();
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Check if category has products
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $produkCount = $stmt->get_result()->fetch_assoc()['total'];
        
        if ($produkCount > 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Tidak dapat menghapus kategori yang masih memiliki produk (' . $produkCount . ' produk)'];
        } else {
            $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil dihapus'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal menghapus kategori'];
            }
        }
        header('Location: kategori.php');
        exit();
    }
}

// Get all categories with product count
$query = "SELECT k.*, COUNT(p.id) as jumlah_produk 
          FROM kategori k 
          LEFT JOIN produk p ON k.id = p.kategori_id 
          GROUP BY k.id 
          ORDER BY k.nama_kategori ASC";
$kategori = $conn->query($query);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin Dapur RR</title>
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
    --shadow-sm: 0 2px 15px rgba(255, 105, 180, 0.1);
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
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        /* Cards */
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .card-title {
            font-size: 1.6rem;
            margin-bottom: 1.8rem;
            color: var(--dark);
            font-weight: 700;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--gradient-1);
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1.2rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            color: var(--dark);
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 105, 180, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Buttons - STYLE BARU SESUAI DASHBOARD */
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
            width: 100%;
            padding: 1.2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
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
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        /* Kategori List */
        .kategori-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .kategori-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .kategori-list::-webkit-scrollbar-track {
            background: rgba(255, 105, 180, 0.1);
            border-radius: 10px;
        }
        
        .kategori-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }
        
        .kategori-item {
            padding: 1.8rem;
            background: white;
            border: 2px solid rgba(255, 105, 180, 0.1);
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .kategori-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        
        .kategori-info h3 {
            color: var(--dark);
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .kategori-info p {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 0.8rem;
        }
        
        .kategori-meta {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .kategori-actions {
            display: flex;
            gap: 0.8rem;
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
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease-out;
            border: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        @keyframes slideIn {
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
            margin-bottom: 2rem;
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
        
        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
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
        }
        
        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
            
            .kategori-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .kategori-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .modal-content {
                padding: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .btn-warning,
            .btn-danger {
                padding: 0.7rem 1.2rem;
                font-size: 0.85rem;
            }
        }
        .kategori-actions {
    display: flex;
    flex-direction: column; 
    gap: 10px; /* jarak antar tombol */
    align-items: flex-start; /* biar kiri semua */
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
            <a href="kategori.php" class="menu-item active">
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

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Kelola Kategori Produk</h1>
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

        <div class="content-grid">
            <!-- Form Tambah -->
            <div class="card">
                <h2 class="card-title">Tambah Kategori Baru</h2>
                <form method="POST" action="kategori.php">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="nama_kategori" name="nama_kategori" 
                               placeholder="Contoh: Kue Ulang Tahun" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" 
                                  placeholder="Deskripsi kategori (opsional)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Simpan Kategori
                    </button>
                </form>
            </div>

            <!-- List Kategori -->
            <div class="card">
                <h2 class="card-title">Daftar Kategori (<?php echo $kategori->num_rows; ?>)</h2>
                <div class="kategori-list">
                    <?php if ($kategori->num_rows > 0): ?>
                        <?php while($k = $kategori->fetch_assoc()): ?>
                        <div class="kategori-item">
                            <div class="kategori-info">
                                <h3><span></span> <?php echo htmlspecialchars($k['nama_kategori']); ?></h3>
                                <?php if (!empty($k['deskripsi'])): ?>
                                <p><?php echo htmlspecialchars($k['deskripsi']); ?></p>
                                <?php endif; ?>
                                <div class="kategori-meta">
                                    <span></span> <?php echo $k['jumlah_produk']; ?> produk terdaftar
                                </div>
                            </div>
                           <div class="kategori-actions">
    <button onclick="editKategori(
        <?php echo $k['id']; ?>, 
        '<?php echo addslashes($k['nama_kategori']); ?>', 
        '<?php echo addslashes($k['deskripsi']); ?>'
    )" class="btn btn-warning">
        Edit
    </button>

    <form method="POST" action="kategori.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $k['id']; ?>">

        <button type="submit" class="btn btn-danger"
            onclick="return confirm('Yakin ingin menghapus kategori \'<?php echo addslashes($k['nama_kategori']); ?>\'?\n\n<?php echo $k['jumlah_produk'] > 0 ? 'Kategori ini memiliki ' . $k['jumlah_produk'] . ' produk dan tidak dapat dihapus!' : 'Kategori ini akan dihapus permanen.'; ?>')">
            Hapus
        </button>
    </form>
</div>

                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"></div>
                            <h3>Belum Ada Kategori</h3>
                            <p>Tambahkan kategori pertama Anda menggunakan form di samping</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Kategori</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_nama_kategori">Nama Kategori <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="edit_nama_kategori" name="nama_kategori" required>
                </div>
                <div class="form-group">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <textarea id="edit_deskripsi" name="deskripsi"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    Update Kategori
                </button>
            </form>
        </div>
    </div>

    <script>
        function editKategori(id, nama, deskripsi) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama_kategori').value = nama;
            document.getElementById('edit_deskripsi').value = deskripsi;
            document.getElementById('editModal').classList.add('show');
            
            // Auto focus
            setTimeout(() => {
                document.getElementById('edit_nama_kategori').focus();
            }, 100);
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
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
        
        // Smooth animations for kategori items
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.kategori-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>