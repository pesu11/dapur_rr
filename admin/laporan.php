<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// PENDAPATAN (Revenue)
$pendapatan = $conn->query("SELECT 
    COUNT(*) as total_pesanan,
    SUM(total_harga) as total_revenue,
    SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END) as revenue_selesai,
    SUM(CASE WHEN status = 'pending' THEN total_harga ELSE 0 END) as revenue_pending
    FROM pesanan 
    WHERE created_at BETWEEN '$startDate' AND '$endDate 23:59:59'")->fetch_assoc();

// PENGELUARAN (Expenses)
$pengeluaran = $conn->query("SELECT 
    kategori_pengeluaran,
    SUM(jumlah) as total
    FROM pengeluaran 
    WHERE tanggal BETWEEN '$startDate' AND '$endDate'
    GROUP BY kategori_pengeluaran")->fetch_all(MYSQLI_ASSOC);

$pengeluaranData = [
    'operasional' => 0,
    'produksi' => 0,
    'gaji' => 0,
    'sewa' => 0,
    'lainnya' => 0
];

foreach($pengeluaran as $row) {
    $pengeluaranData[$row['kategori_pengeluaran']] = $row['total'];
}

$totalPengeluaran = array_sum($pengeluaranData);
$totalPendapatan = $pendapatan['revenue_selesai'] ?? 0;
$labaKotor = $totalPendapatan - $pengeluaranData['produksi'];
$labaBersih = $totalPendapatan - $totalPengeluaran;

// Detail Pendapatan per Produk
$produkPendapatan = $conn->query("SELECT pr.nama_produk, 
    SUM(dp.jumlah) as qty,
    SUM(dp.subtotal) as revenue
    FROM detail_pesanan dp
    JOIN produk pr ON dp.produk_id = pr.id
    JOIN pesanan p ON dp.pesanan_id = p.id
    WHERE p.created_at BETWEEN '$startDate' AND '$endDate 23:59:59'
    AND p.status = 'selesai'
    GROUP BY dp.produk_id
    ORDER BY revenue DESC");

// Detail Pengeluaran
$detailPengeluaran = $conn->query("SELECT * FROM pengeluaran 
    WHERE tanggal BETWEEN '$startDate' AND '$endDate'
    ORDER BY tanggal DESC, kategori_pengeluaran");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi - Admin Dapur RR</title>
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .filters {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
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
            margin-bottom: 0.8rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .filter-group input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
        }
        
        .report-header h2 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .report-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card.pendapatan { border-left-color: var(--success); }
        .stat-card.pengeluaran { border-left-color: var(--danger); }
        .stat-card.laba-kotor { border-left-color: var(--info); }
        .stat-card.laba-bersih { border-left-color: var(--primary); }
        
        .stat-label {
            color: var(--dark);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-value.positive { color: var(--success); }
        .stat-value.negative { color: var(--danger); }
        .stat-value.info { color: var(--info); }
        .stat-value.primary { color: var(--primary); }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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
        }
        
        tbody tr:hover {
            background: var(--light);
        }
        
        .laba-rugi-table {
            width: 100%;
            margin-top: 1rem;
        }
        
        .laba-rugi-table tr {
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .laba-rugi-table td {
            padding: 0.8rem 1rem;
        }
        
        .laba-rugi-table td:first-child {
            font-weight: 500;
        }
        
        .laba-rugi-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        
        .section-header {
            background: var(--light);
            font-weight: 700;
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .subsection {
            padding-left: 2rem !important;
            font-size: 0.95rem;
        }
        
        .total-row {
            background: var(--light);
            font-weight: 700;
            font-size: 1.05rem;
        }
        
        .final-row {
            background: var(--gradient-1);
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
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

        @media print {
            .sidebar, .filters, .top-bar .user-info, .btn {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            body {
                background: white !important;
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
            
            </a>
            <a href="laporan.php" class="menu-item active">
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
            <h1 class="page-title"> Laporan Laba Rugi</h1>
            <div class="user-info">
                <a href="pengeluaran.php" class="btn btn-primary"> Kelola Pengeluaran</a>
                <button onclick="window.print()" class="btn btn-success"> Print</button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"> Tampilkan</button>
                </div>
            </form>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <h2>LAPORAN LABA RUGI</h2>
            <p>Periode: <?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></p>
            <p style="color: var(--primary); font-size: 0.9rem; margin-top: 0.5rem;">Dibuat pada: <?php echo date('d M Y H:i'); ?></p>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card pendapatan">
                <div class="stat-label"> Total Pendapatan</div>
                <div class="stat-value positive"><?php echo formatRupiah($totalPendapatan); ?></div>
                <div style="font-size: 0.9rem; color: #666;"><?php echo $pendapatan['total_pesanan']; ?> pesanan selesai</div>
            </div>
            <div class="stat-card pengeluaran">
                <div class="stat-label"> Total Pengeluaran</div>
                <div class="stat-value negative"><?php echo formatRupiah($totalPengeluaran); ?></div>
                <div style="font-size: 0.9rem; color: #666;">Semua kategori</div>
            </div>
            <div class="stat-card laba-kotor">
                <div class="stat-label"> Laba Kotor</div>
                <div class="stat-value info"><?php echo formatRupiah($labaKotor); ?></div>
                <div style="font-size: 0.9rem; color: #666;">Pendapatan - Biaya Produksi</div>
            </div>
            <div class="stat-card laba-bersih">
                <div class="stat-label"> Laba Bersih</div>
                <div class="stat-value <?php echo $labaBersih >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo formatRupiah($labaBersih); ?>
                </div>
                <div style="font-size: 0.9rem; color: #666;">
                    <?php 
                    if ($totalPendapatan > 0) {
                        $margin = ($labaBersih / $totalPendapatan) * 100;
                        echo 'Margin: ' . number_format($margin, 2) . '%';
                    } else {
                        echo 'Tidak ada pendapatan';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Laporan Laba Rugi Detail -->
        <div class="card">
            <h2 class="card-title"> Laporan Laba Rugi</h2>
            <table class="laba-rugi-table">
                <tbody>
                    <!-- PENDAPATAN -->
                    <tr class="section-header">
                        <td colspan="2">PENDAPATAN</td>
                    </tr>
                    <tr>
                        <td class="subsection">Penjualan</td>
                        <td><?php echo formatRupiah($totalPendapatan); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>TOTAL PENDAPATAN</td>
                        <td><?php echo formatRupiah($totalPendapatan); ?></td>
                    </tr>
                    
                    <!-- HARGA POKOK PENJUALAN -->
                    <tr class="section-header">
                        <td colspan="2">HARGA POKOK PENJUALAN</td>
                    </tr>
                    <tr>
                        <td class="subsection">Biaya Produksi</td>
                        <td><?php echo formatRupiah($pengeluaranData['produksi']); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>LABA KOTOR</td>
                        <td><?php echo formatRupiah($labaKotor); ?></td>
                    </tr>
                    
                    <!-- BEBAN OPERASIONAL -->
                    <tr class="section-header">
                        <td colspan="2">BEBAN OPERASIONAL</td>
                    </tr>
                    <tr>
                        <td class="subsection">Biaya Operasional</td>
                        <td><?php echo formatRupiah($pengeluaranData['operasional']); ?></td>
                    </tr>
                    <tr>
                        <td class="subsection">Biaya Gaji Karyawan</td>
                        <td><?php echo formatRupiah($pengeluaranData['gaji']); ?></td>
                    </tr>
                    <tr>
                        <td class="subsection">Biaya Sewa Tempat</td>
                        <td><?php echo formatRupiah($pengeluaranData['sewa']); ?></td>
                    </tr>
                    <tr>
                        <td class="subsection">Biaya Lainnya</td>
                        <td><?php echo formatRupiah($pengeluaranData['lainnya']); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>TOTAL BEBAN OPERASIONAL</td>
                        <td><?php 
                        $totalBebanOperasional = $pengeluaranData['operasional'] + 
                                                 $pengeluaranData['gaji'] + 
                                                 $pengeluaranData['sewa'] + 
                                                 $pengeluaranData['lainnya'];
                        echo formatRupiah($totalBebanOperasional); 
                        ?></td>
                    </tr>
                    
                    <!-- LABA BERSIH -->
                    <tr class="final-row">
                        <td>LABA BERSIH</td>
                        <td><?php echo formatRupiah($labaBersih); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Detail Pendapatan per Produk -->
        <div class="card">
            <h2 class="card-title"> Detail Pendapatan per Produk</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Qty Terjual</th>
                        <th>Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($produkPendapatan->num_rows > 0): ?>
                        <?php while($produk = $produkPendapatan->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                            <td><span style="background: var(--light); padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;"><?php echo $produk['qty']; ?> pcs</span></td>
                            <td><strong style="color: var(--success);"><?php echo formatRupiah($produk['revenue']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 2rem; color: #999;">
                                Tidak ada data penjualan produk
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Detail Pengeluaran -->
        <div class="card">
            <h2 class="card-title"> Detail Pengeluaran</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detailPengeluaran->num_rows > 0): ?>
                        <?php while($detail = $detailPengeluaran->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($detail['tanggal'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $detail['kategori_pengeluaran']; ?>">
                                    <?php echo ucfirst($detail['kategori_pengeluaran']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($detail['keterangan']); ?></td>
                            <td><strong style="color: var(--danger);"><?php echo formatRupiah($detail['jumlah']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: #999;">
                                Tidak ada data pengeluaran
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Ringkasan -->
        <div class="card">
            <h2 class="card-title"> Ringkasan Analisis</h2>
            <div style="padding: 1rem 0;">
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 105, 180, 0.1);">
                    <span>Total Pesanan:</span>
                    <strong><?php echo number_format($pendapatan['total_pesanan']); ?> pesanan</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 105, 180, 0.1);">
                    <span>Rata-rata Nilai Pesanan:</span>
                    <strong><?php echo formatRupiah($pendapatan['total_pesanan'] > 0 ? ($totalPendapatan / $pendapatan['total_pesanan']) : 0); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 105, 180, 0.1);">
                    <span>Margin Laba Kotor:</span>
                    <strong><?php echo $totalPendapatan > 0 ? number_format(($labaKotor / $totalPendapatan) * 100, 2) : '0'; ?>%</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 105, 180, 0.1);">
                    <span>Margin Laba Bersih:</span>
                    <strong style="color: <?php echo $labaBersih >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?php echo $totalPendapatan > 0 ? number_format(($labaBersih / $totalPendapatan) * 100, 2) : '0'; ?>%
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid rgba(255, 105, 180, 0.1);">
                    <span>Status Keuangan:</span>
                    <?php if ($labaBersih > 0): ?>
                        <span class="badge" style="background: #D1FAE5; color: #065F46;"> Profit</span>
                    <?php elseif ($labaBersih < 0): ?>
                        <span class="badge" style="background: #FEE2E2; color: #991B1B;"> Loss</span>
                    <?php else: ?>
                        <span class="badge" style="background: #FEF3C7; color: #92400E;"> Break Even</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.8rem 0;">
                    <span>Periode Laporan:</span>
                    <strong><?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Print optimization
        window.addEventListener('beforeprint', function() {
            document.querySelector('.sidebar').style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '0';
        });
        
        window.addEventListener('afterprint', function() {
            document.querySelector('.sidebar').style.display = 'block';
            document.querySelector('.main-content').style.marginLeft = '250px';
        });
    </script>
</body>
</html>