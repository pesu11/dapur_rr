<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Get selected year and month for filters
$selectedYear = $_GET['year'] ?? date('Y');
$selectedMonth = $_GET['month'] ?? date('m');

// 1. Total Penjualan (Revenue)
$totalPenjualan = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total 
    FROM pesanan 
    WHERE status = 'selesai' 
    AND YEAR(created_at) = $selectedYear
    AND MONTH(created_at) = $selectedMonth")->fetch_assoc()['total'];

// 2. Total Pesanan (Order Count)
$totalPesanan = $conn->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM pesanan 
    WHERE YEAR(created_at) = $selectedYear
    AND MONTH(created_at) = $selectedMonth")->fetch_assoc();

// 3. Pelanggan Aktif (Active Customers)
$pelangganAktif = $conn->query("SELECT COUNT(DISTINCT user_id) as total 
    FROM pesanan 
    WHERE YEAR(created_at) = $selectedYear
    AND MONTH(created_at) = $selectedMonth")->fetch_assoc()['total'];

// 4. Total Pengeluaran
$totalPengeluaran = $conn->query("SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM pengeluaran 
    WHERE YEAR(tanggal) = $selectedYear
    AND MONTH(tanggal) = $selectedMonth")->fetch_assoc()['total'];

// 5. Laba Bersih
$labaBersih = $totalPenjualan - $totalPengeluaran;

// 6. Pengeluaran per Kategori
$pengeluaranPerKategori = $conn->query("SELECT 
    kategori_pengeluaran,
    SUM(jumlah) as total
    FROM pengeluaran 
    WHERE YEAR(tanggal) = $selectedYear
    AND MONTH(tanggal) = $selectedMonth
    GROUP BY kategori_pengeluaran");

$pengeluaranData = [
    'operasional' => 0,
    'produksi' => 0,
    'gaji' => 0,
    'sewa' => 0,
    'lainnya' => 0
];

while($row = $pengeluaranPerKategori->fetch_assoc()) {
    $pengeluaranData[$row['kategori_pengeluaran']] = $row['total'];
}

// 7. Produk Terlaris (Best Selling Products)
$produkTerlaris = $conn->query("SELECT p.nama_produk, 
    SUM(dp.jumlah) as terjual,
    SUM(dp.subtotal) as revenue
    FROM detail_pesanan dp
    JOIN produk p ON dp.produk_id = p.id
    JOIN pesanan ps ON dp.pesanan_id = ps.id
    WHERE ps.status = 'selesai' 
    AND YEAR(ps.created_at) = $selectedYear
    AND MONTH(ps.created_at) = $selectedMonth
    GROUP BY dp.produk_id
    ORDER BY terjual DESC
    LIMIT 10");

// Rincian Penjualan (Sales Details)
$rincianPenjualan = $conn->query("SELECT 
    ps.no_pesanan,
    u.nama as customer,
    ps.total_harga as total,
    ps.status,
    DATE(ps.created_at) as tanggal,
    mp.nama_metode as metode_bayar
    FROM pesanan ps
    JOIN users u ON ps.user_id = u.id
    LEFT JOIN metode_pembayaran mp ON ps.metode_pembayaran_id = mp.id
    WHERE YEAR(ps.created_at) = $selectedYear
    AND MONTH(ps.created_at) = $selectedMonth
    ORDER BY ps.created_at DESC
    LIMIT 15");

// Pendapatan per Kategori (Revenue by Category)
$pendapatanPerKategori = $conn->query("SELECT 
    k.nama_kategori as kategori,
    SUM(dp.subtotal) as revenue,
    COUNT(DISTINCT dp.pesanan_id) as jumlah_transaksi
    FROM detail_pesanan dp
    JOIN produk p ON dp.produk_id = p.id
    JOIN kategori k ON p.kategori_id = k.id
    JOIN pesanan ps ON dp.pesanan_id = ps.id
    WHERE ps.status = 'selesai'
    AND YEAR(ps.created_at) = $selectedYear
    AND MONTH(ps.created_at) = $selectedMonth
    GROUP BY k.id
    ORDER BY revenue DESC");

// Prepare chart data for Pendapatan per Kategori
$kategoriLabels = [];
$kategoriData = [];
$kategoriColors = ['#FF69B4', '#FFB6D9', '#FF1493', '#FFC1E3', '#FF85A2', '#FF6B9D'];
$kategoriChartData = $pendapatanPerKategori->fetch_all(MYSQLI_ASSOC);
foreach($kategoriChartData as $row) {
    $kategoriLabels[] = $row['kategori'];
    $kategoriData[] = $row['revenue'];
}

// Penjualan & Pengeluaran per Bulan (Monthly Trend)
$penjualanPerBulan = $conn->query("SELECT 
    MONTH(created_at) as bulan,
    SUM(total_harga) as total_penjualan,
    COUNT(*) as jumlah_pesanan
    FROM pesanan 
    WHERE status = 'selesai'
    AND YEAR(created_at) = $selectedYear
    GROUP BY MONTH(created_at)
    ORDER BY bulan");

$pengeluaranPerBulan = $conn->query("SELECT 
    MONTH(tanggal) as bulan,
    SUM(jumlah) as total_pengeluaran
    FROM pengeluaran 
    WHERE YEAR(tanggal) = $selectedYear
    GROUP BY MONTH(tanggal)
    ORDER BY bulan");

$bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$penjualanData = array_fill(0, 12, 0);
$pengeluaranDataChart = array_fill(0, 12, 0);
$labaData = array_fill(0, 12, 0);

$penjualanTrend = $penjualanPerBulan->fetch_all(MYSQLI_ASSOC);
foreach($penjualanTrend as $row) {
    $index = $row['bulan'] - 1;
    $penjualanData[$index] = $row['total_penjualan'];
}

$pengeluaranTrend = $pengeluaranPerBulan->fetch_all(MYSQLI_ASSOC);
foreach($pengeluaranTrend as $row) {
    $index = $row['bulan'] - 1;
    $pengeluaranDataChart[$index] = $row['total_pengeluaran'];
}

// Calculate laba per bulan
for($i = 0; $i < 12; $i++) {
    $labaData[$i] = $penjualanData[$i] - $pengeluaranDataChart[$i];
}

// Prepare chart data for Pengeluaran per Kategori
$pengeluaranLabels = ['Operasional', 'Produksi', 'Gaji', 'Sewa', 'Lainnya'];
$pengeluaranValues = [
    $pengeluaranData['operasional'],
    $pengeluaranData['produksi'],
    $pengeluaranData['gaji'],
    $pengeluaranData['sewa'],
    $pengeluaranData['lainnya']
];

// Top Pelanggan (Best Customers)
$topPelanggan = $conn->query("SELECT 
    u.nama,
    u.email,
    COUNT(ps.id) as jumlah_pesanan,
    SUM(ps.total_harga) as total_belanja
    FROM pesanan ps
    JOIN users u ON ps.user_id = u.id
    WHERE ps.status = 'selesai'
    AND YEAR(ps.created_at) = $selectedYear
    GROUP BY u.id
    ORDER BY total_belanja DESC
    LIMIT 8");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik & Dashboard - Admin Dapur RR</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        /* Stats Cards */
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
            border: 1px solid rgba(255, 105, 180, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card.penjualan {
            background: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            color: white;
        }
        
        .stat-card.pesanan {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        
        .stat-card.pelanggan {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            color: white;
        }
        
        .stat-card.pengeluaran {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
        }
        
        .stat-card.laba {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }
        
        .stat-icon {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .stat-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-meta {
            font-size: 0.85rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light);
        }
        
        .card-title {
            font-size: 1.2rem;
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 105, 180, 0.1);
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
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
            font-size: 0.9rem;
            color: var(--dark);
        }
        
        tbody tr:hover {
            background: var(--light);
            transform: scale(1.002);
            transition: all 0.3s;
        }
        
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
        
        .badge-warning {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            color: var(--warning);
            border: 2px solid #FCD34D;
        }
        
        /* Chart Container */
        .chart-wrapper {
            position: relative;
            height: 300px;
            padding: 1rem;
        }
        
        /* Product List */
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
        }
        
        .product-item:hover {
            background: var(--light);
            transform: translateX(5px);
        }
        
        .product-info h4 {
            color: var(--dark);
            margin-bottom: 0.3rem;
        }
        
        .product-stats {
            text-align: right;
        }
        
        .product-qty {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .product-revenue {
            font-size: 0.9rem;
            color: var(--success);
        }
        
        /* Customer List */
        .customer-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .customer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
        }
        
        .customer-item:hover {
            background: var(--light);
            transform: translateX(5px);
        }
        
        .customer-info h4 {
            color: var(--dark);
            margin-bottom: 0.3rem;
        }
        
        .customer-info p {
            color: #666;
            font-size: 0.85rem;
        }
        
        .customer-stats {
            text-align: right;
        }
        
        .customer-orders {
            font-size: 1rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .customer-spent {
            font-size: 0.9rem;
            color: var(--success);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Responsive */
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
            
            .menu-item {
                padding: 1rem;
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
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
            
            .card-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .card {
                margin: 0 -0.5rem;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            
            .chart-wrapper {
                height: 250px;
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
          
            <a href="laporan.php" class="menu-item">
                <span>📈</span>
                <span>Laporan</span>
            </a>
            <a href="analitik.php" class="menu-item active">
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
            <h1 class="page-title"> Analitik Penjualan</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="year">
                            <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Bulan</label>
                        <select name="month">
                            <?php 
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            foreach($months as $num => $name): ?>
                            <option value="<?php echo str_pad($num, 2, '0', STR_PAD_LEFT); ?>" 
                                <?php echo $selectedMonth == str_pad($num, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-logout" style="background: var(--primary); padding: 0.9rem; width: 100%;">
                            🔍 Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card penjualan">
                <div class="stat-icon"></div>
                <div class="stat-label">Total Penjualan</div>
                <div class="stat-value"><?php echo formatRupiah($totalPenjualan); ?></div>
                <div class="stat-meta">
                    <span></span>
                    <span><?php echo $months[intval($selectedMonth)]; ?> <?php echo $selectedYear; ?></span>
                </div>
            </div>
            
            <div class="stat-card pesanan">
                <div class="stat-icon"></div>
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-value"><?php echo number_format($totalPesanan['total']); ?></div>
                <div class="stat-meta">
                    <span><?php echo $totalPesanan['selesai']; ?> selesai</span>
                    <span><?php echo $totalPesanan['pending']; ?> pending</span>
                </div>
            </div>
            
            <div class="stat-card pelanggan">
                <div class="stat-icon"></div>
                <div class="stat-label">Pelanggan Aktif</div>
                <div class="stat-value"><?php echo number_format($pelangganAktif); ?></div>
                <div class="stat-meta">
                    <span></span>
                    <span>Bulan <?php echo $months[intval($selectedMonth)]; ?></span>
                </div>
            </div>
            
            <div class="stat-card pengeluaran">
                <div class="stat-icon"></div>
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value"><?php echo formatRupiah($totalPengeluaran); ?></div>
                <div class="stat-meta">
                    <span></span>
                    <span>Semua Kategori</span>
                </div>
            </div>
            
            <div class="stat-card laba">
                <div class="stat-icon"></div>
                <div class="stat-label">Laba Bersih</div>
                <div class="stat-value"><?php echo formatRupiah($labaBersih); ?></div>
                <div class="stat-meta">
                    <span><?php echo $labaBersih >= 0 ? '' : ''; ?></span>
                    <span><?php echo $labaBersih >= 0 ? 'Profit' : 'Loss'; ?></span>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Pendapatan per Kategori -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"> Pendapatan per Kategori</h3>
                    <span style="color: var(--dark); font-size: 0.9rem;">
                        <?php echo $months[intval($selectedMonth)]; ?> <?php echo $selectedYear; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="kategoriChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pengeluaran per Kategori -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"> Pengeluaran per Kategori</h3>
                    <span style="color: var(--dark); font-size: 0.9rem;">
                        <?php echo $months[intval($selectedMonth)]; ?> <?php echo $selectedYear; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="pengeluaranChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trend Penjualan & Laba -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title"> Trend Penjualan, Pengeluaran & Laba</h3>
                <span style="color: var(--dark); font-size: 0.9rem;">
                    Tahun <?php echo $selectedYear; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="chart-wrapper" style="height: 400px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="content-grid">
            <!-- Produk Terlaris -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"> Produk Terlaris</h3>
                    <span style="color: var(--dark); font-size: 0.9rem;">
                        <?php echo $months[intval($selectedMonth)]; ?> <?php echo $selectedYear; ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($produkTerlaris && $produkTerlaris->num_rows > 0): ?>
                    <div class="product-list">
                        <?php $rank = 1; while($produk = $produkTerlaris->fetch_assoc()): ?>
                        <div class="product-item">
                            <div class="product-info">
                                <h4><?php echo $rank++; ?>. <?php echo htmlspecialchars($produk['nama_produk']); ?></h4>
                                <div style="color: #666; font-size: 0.85rem;">Terjual: <?php echo $produk['terjual']; ?> pcs</div>
                            </div>
                            <div class="product-stats">
                                <div class="product-qty"><?php echo $produk['terjual']; ?> pcs</div>
                                <div class="product-revenue"><?php echo formatRupiah($produk['revenue']); ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"></div>
                        <p>Tidak ada data produk terlaris</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Pelanggan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"> Top Pelanggan</h3>
                    <span style="color: var(--dark); font-size: 0.9rem;">
                        Tahun <?php echo $selectedYear; ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($topPelanggan && $topPelanggan->num_rows > 0): ?>
                    <div class="customer-list">
                        <?php $rank = 1; while($customer = $topPelanggan->fetch_assoc()): ?>
                        <div class="customer-item">
                            <div class="customer-info">
                                <h4><?php echo $rank++; ?>. <?php echo htmlspecialchars($customer['nama']); ?></h4>
                                <p><?php echo htmlspecialchars($customer['email']); ?></p>
                            </div>
                            <div class="customer-stats">
                                <div class="customer-orders"><?php echo $customer['jumlah_pesanan']; ?> pesanan</div>
                                <div class="customer-spent"><?php echo formatRupiah($customer['total_belanja']); ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <p>Tidak ada data pelanggan</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Rincian Penjualan -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3 class="card-title"> Rincian Penjualan</h3>
                <span style="color: var(--dark); font-size: 0.9rem;">
                    <?php echo $months[intval($selectedMonth)]; ?> <?php echo $selectedYear; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Metode Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rincianPenjualan && $rincianPenjualan->num_rows > 0): 
                                while($penjualan = $rincianPenjualan->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($penjualan['no_pesanan']); ?></strong></td>
                                <td><?php echo htmlspecialchars($penjualan['customer']); ?></td>
                                <td><span style="color: var(--success); font-weight: 600;"><?php echo formatRupiah($penjualan['total']); ?></span></td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-warning';
                                    if ($penjualan['status'] === 'selesai') $badgeClass = 'badge-success';
                                    if ($penjualan['status'] === 'dibatalkan') $badgeClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($penjualan['status']); ?></span>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($penjualan['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($penjualan['metode_bayar'] ?? '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <p>Tidak ada data penjualan</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pendapatan per Kategori Chart (Doughnut)
        const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
        new Chart(kategoriCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($kategoriLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($kategoriData); ?>,
                    backgroundColor: [
                        '#FF69B4',
                        '#FFB6D9',
                        '#FF1493',
                        '#FFC1E3',
                        '#FF85A2',
                        '#FF6B9D'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11,
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            },
                            color: '#2C1810'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                return label + ': Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        backgroundColor: 'rgba(44, 24, 16, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        padding: 10
                    }
                },
                cutout: '60%'
            }
        });

        // Pengeluaran per Kategori Chart (Bar)
        const pengeluaranCtx = document.getElementById('pengeluaranChart').getContext('2d');
        new Chart(pengeluaranCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pengeluaranLabels); ?>,
                datasets: [{
                    label: 'Pengeluaran',
                    data: <?php echo json_encode($pengeluaranValues); ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(168, 85, 247, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        backgroundColor: 'rgba(44, 24, 16, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        padding: 10
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                                } else if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Trend Penjualan, Pengeluaran & Laba Chart (Line)
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($bulanLabels); ?>,
                datasets: [
                    {
                        label: 'Penjualan',
                        data: <?php echo json_encode($penjualanData); ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?php echo json_encode($pengeluaranDataChart); ?>,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Laba Bersih',
                        data: <?php echo json_encode($labaData); ?>,
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            },
                            color: '#2C1810',
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw || 0;
                                return label + ': Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        backgroundColor: 'rgba(44, 24, 16, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        padding: 10
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                                } else if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Update chart on window resize
        window.addEventListener('resize', function() {
            kategoriChart.resize();
            pengeluaranChart.resize();
            trendChart.resize();
        });
    </script>
</body>
</html>