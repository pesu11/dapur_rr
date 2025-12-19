<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'tambah') {
            $tanggal = $_POST['tanggal'];
            $kategori = $_POST['kategori_pengeluaran'];
            $keterangan = $_POST['keterangan'];
            $jumlah = $_POST['jumlah'];
            $userId = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO pengeluaran (tanggal, kategori_pengeluaran, keterangan, jumlah, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdi", $tanggal, $kategori, $keterangan, $jumlah, $userId);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Pengeluaran berhasil ditambahkan!');
            } else {
                setFlashMessage('error', 'Gagal menambahkan pengeluaran!');
            }
            header('Location: pengeluaran.php');
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $tanggal = $_POST['tanggal'];
            $kategori = $_POST['kategori_pengeluaran'];
            $keterangan = $_POST['keterangan'];
            $jumlah = $_POST['jumlah'];
            
            $stmt = $conn->prepare("UPDATE pengeluaran SET tanggal=?, kategori_pengeluaran=?, keterangan=?, jumlah=? WHERE id=?");
            $stmt->bind_param("sssdi", $tanggal, $kategori, $keterangan, $jumlah, $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Pengeluaran berhasil diupdate!');
            } else {
                setFlashMessage('error', 'Gagal mengupdate pengeluaran!');
            }
            header('Location: pengeluaran.php');
        } elseif ($_POST['action'] === 'hapus') {
            $id = $_POST['id'];
            
            $stmt = $conn->prepare("DELETE FROM pengeluaran WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Pengeluaran berhasil dihapus!');
            } else {
                setFlashMessage('error', 'Gagal menghapus pengeluaran!');
            }
            header('Location: pengeluaran.php');
        }
    }
}

// Filter
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Get pengeluaran data
$query = "SELECT p.*, u.nama as created_by_name 
          FROM pengeluaran p 
          LEFT JOIN users u ON p.created_by = u.id 
          WHERE MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?
          ORDER BY p.tanggal DESC, p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$pengeluaran = $stmt->get_result();

// Summary by category
$summaryQuery = "SELECT 
    kategori_pengeluaran,
    SUM(jumlah) as total,
    COUNT(*) as jumlah_transaksi
    FROM pengeluaran 
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
    GROUP BY kategori_pengeluaran";
$stmtSummary = $conn->prepare($summaryQuery);
$stmtSummary->bind_param("ii", $bulan, $tahun);
$stmtSummary->execute();
$summary = $stmtSummary->get_result();

$summaryData = [
    'operasional' => 0,
    'produksi' => 0,
    'gaji' => 0,
    'sewa' => 0,
    'lainnya' => 0
];

while($row = $summary->fetch_assoc()) {
    $summaryData[$row['kategori_pengeluaran']] = $row['total'];
}

$totalPengeluaran = array_sum($summaryData);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengeluaran - Admin Dapur RR</title>
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
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid;
        }
        
        .stat-card.operasional { border-left-color: #3B82F6; }
        .stat-card.produksi { border-left-color: #10B981; }
        .stat-card.gaji { border-left-color: #F59E0B; }
        .stat-card.sewa { border-left-color: #EF4444; }
        .stat-card.total { border-left-color: var(--accent); }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark);
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
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
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
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
        }
        
        tbody tr:hover {
            background: var(--light);
        }
        
        .badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-operasional { background: #DBEAFE; color: #3B82F6; }
        .badge-produksi { background: #D1FAE5; color: #10B981; }
        .badge-gaji { background: #FEF3C7; color: #F59E0B; }
        .badge-sewa { background: #FEE2E2; color: #EF4444; }
        .badge-lainnya { background: #F3E8FF; color: #A855F7; }
        
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
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #666;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
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
              <a href="pengeluaran.php" class="menu-item active">
                <span>💰</span>
                <span>Pengeluaran</span>
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
            <h1 class="page-title">💰 Pengeluaran</h1>
            <div class="user-info">
                <button class="btn btn-primary" onclick="openModal('tambahModal')">
                    ➕ Tambah Pengeluaran
                </button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Bulan</label>
                        <select name="bulan">
                            <?php 
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            for($i = 1; $i <= 12; $i++): 
                            ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                <?php echo $months[$i-1]; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun">
                            <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Cari</button>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card operasional">
                <div class="stat-label">Biaya Operasional</div>
                <div class="stat-value"><?php echo formatRupiah($summaryData['operasional']); ?></div>
            </div>
            <div class="stat-card produksi">
                <div class="stat-label">Biaya Produksi</div>
                <div class="stat-value"><?php echo formatRupiah($summaryData['produksi']); ?></div>
            </div>
            <div class="stat-card gaji">
                <div class="stat-label">Biaya Gaji</div>
                <div class="stat-value"><?php echo formatRupiah($summaryData['gaji']); ?></div>
            </div>
            <div class="stat-card sewa">
                <div class="stat-label">Biaya Sewa</div>
                <div class="stat-value"><?php echo formatRupiah($summaryData['sewa']); ?></div>
            </div>
            <div class="stat-card total">
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value"><?php echo formatRupiah($totalPengeluaran); ?></div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <h2 class="card-title">Daftar Pengeluaran</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th>Jumlah</th>
                        <th>Dibuat Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pengeluaran->num_rows > 0): ?>
                        <?php while($row = $pengeluaran->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['kategori_pengeluaran']; ?>">
                                    <?php echo ucfirst($row['kategori_pengeluaran']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                            <td><strong><?php echo formatRupiah($row['jumlah']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-warning" onclick='editPengeluaran(<?php echo json_encode($row); ?>)' style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                    Edit
                                </button>
                                <br><br>
                                
                                <button class="btn btn-danger" onclick="hapusPengeluaran(<?php echo $row['id']; ?>)" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #999;">
                                Tidak ada data pengeluaran
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Pengeluaran</h2>
                <button class="close-modal" onclick="closeModal('tambahModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori_pengeluaran" required>
                        <option value="">Pilih Kategori</option>
                        <option value="operasional">Biaya Operasional</option>
                        <option value="produksi">Biaya Produksi</option>
                        <option value="gaji">Biaya Gaji Karyawan</option>
                        <option value="sewa">Biaya Sewa Tempat</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" required placeholder="Masukkan keterangan pengeluaran..."></textarea>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="jumlah" required min="0" step="0.01" placeholder="0">
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('tambahModal')">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Pengeluaran</h2>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="edit_tanggal" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori_pengeluaran" id="edit_kategori" required>
                        <option value="operasional">Biaya Operasional</option>
                        <option value="produksi">Biaya Produksi</option>
                        <option value="gaji">Biaya Gaji Karyawan</option>
                        <option value="sewa">Biaya Sewa Tempat</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" required></textarea>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="jumlah" id="edit_jumlah" required min="0" step="0.01">
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div id="hapusModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Konfirmasi Hapus</h2>
                <button class="close-modal" onclick="closeModal('hapusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" id="hapus_id">
                <p style="margin-bottom: 1.5rem;">Apakah Anda yakin ingin menghapus pengeluaran ini?</p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-success" onclick="closeModal('hapusModal')">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editPengeluaran(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_kategori').value = data.kategori_pengeluaran;
            document.getElementById('edit_keterangan').value = data.keterangan;
            document.getElementById('edit_jumlah').value = data.jumlah;
            openModal('editModal');
        }

        function hapusPengeluaran(id) {
            document.getElementById('hapus_id').value = id;
            openModal('hapusModal');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>