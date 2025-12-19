<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Statistik
$totalProduk = $conn->query("SELECT COUNT(*) as total FROM produk")->fetch_assoc()['total'];
$totalPesanan = $conn->query("SELECT COUNT(*) as total FROM pesanan")->fetch_assoc()['total'];
$totalPelanggan = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$totalPendapatan = $conn->query("SELECT SUM(total_bayar) as total FROM pesanan")->fetch_assoc()['total'] ?? 0;

// Pesanan hari ini
$pesananHariIni = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];

// Pendapatan bulan ini
$pendapatanBulan = $conn->query("SELECT SUM(total_bayar) as total FROM pesanan WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Produk stok rendah
$stokRendah = $conn->query("SELECT COUNT(*) as total FROM produk WHERE stok < 5")->fetch_assoc()['total'];

// Pesanan terbaru
$pesananTerbaru = $conn->query("SELECT p.*, u.nama FROM pesanan p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");

// Produk terlaris
$produkTerlaris = $conn->query("SELECT pr.nama_produk, SUM(dp.jumlah) as total_terjual 
                                FROM detail_pesanan dp 
                                JOIN produk pr ON dp.produk_id = pr.id 
                                GROUP BY dp.produk_id 
                                ORDER BY total_terjual DESC 
                                LIMIT 5");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo $pengaturan['nama_toko']; ?></title>
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }
        
        .stat-card.primary::before {
            background: var(--primary);
        }
        
        .stat-card.success::before {
            background: var(--success);
        }
        
        .stat-card.warning::before {
            background: var(--warning);
        }
        
        .stat-card.danger::before {
            background: var(--danger);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 0.8rem;
            font-weight: 500;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        
        /* Content Sections */
        .content-section {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
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
        }
        
        tr:hover {
            background: var(--light);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }
        
        .status-dibayar, .status-dikonfirmasi {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            color: var(--info);
            border: 2px solid #93C5FD;
        }
        
        .status-selesai {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
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
        
        /* Quick Stats Row */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quick-stat {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255, 105, 180, 0.1);
            transition: all 0.3s;
        }
        
        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .quick-stat-icon {
            font-size: 2rem;
            margin-bottom: 0.8rem;
            color: var(--primary);
        }
        
        .quick-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }
        
        .quick-stat-label {
            font-size: 0.9rem;
            color: var(--dark);
            opacity: 0.7;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 0.8rem;
                font-size: 0.9rem;
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
            <a href="dashboard.php" class="menu-item active">
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
            <h1 class="page-title">Dashboard Admin</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars($_SESSION['nama']); ?></div>
                <a href="../logout.php" class="btn-logout">
                    <span></span>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.2rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Main Stats -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-info">
                    <h3>TOTAL PENDAPATAN</h3>
                    <div class="stat-value"><?php echo formatRupiah($totalPendapatan); ?></div>
                </div>
                <div class="stat-icon"></div>
            </div>

            <div class="stat-card success">
                <div class="stat-info">
                    <h3>TOTAL PESANAN</h3>
                    <div class="stat-value"><?php echo $totalPesanan; ?></div>
                </div>
                <div class="stat-icon"></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-info">
                    <h3>TOTAL PELANGGAN</h3>
                    <div class="stat-value"><?php echo $totalPelanggan; ?></div>
                </div>
                <div class="stat-icon"></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-info">
                    <h3>TOTAL PRODUK</h3>
                    <div class="stat-value"><?php echo $totalProduk; ?></div>
                </div>
                <div class="stat-icon"></div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat">
                <div class="quick-stat-icon"></div>
                <div class="quick-stat-value"><?php echo $pesananHariIni; ?></div>
                <div class="quick-stat-label">Pesanan Hari Ini</div>
            </div>
            
            <div class="quick-stat">
                <div class="quick-stat-icon"></div>
                <div class="quick-stat-value"><?php echo formatRupiah($pendapatanBulan); ?></div>
                <div class="quick-stat-label">Pendapatan Bulan Ini</div>
            </div>
            
            <div class="quick-stat">
                <div class="quick-stat-icon"></div>
                <div class="quick-stat-value"><?php echo $stokRendah; ?></div>
                <div class="quick-stat-label">Produk Stok Rendah</div>
            </div>
        </div>

        <!-- Pesanan Terbaru -->
        <div class="content-section">
            <h2 class="section-title">
                <span></span>
                Pesanan Terbaru
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pesananTerbaru->num_rows > 0): ?>
                        <?php while($pesanan = $pesananTerbaru->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pesanan['no_pesanan']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pesanan['nama']); ?></td>
                            <td><strong><?php echo formatRupiah($pesanan['total_bayar']); ?></strong></td>
                            <td>
                                <?php 
                                $status = $pesanan['status'] ?? '';
                                $statusClass = 'status-pending';
                                $statusIcon = '⏳';
                                
                                if ($status === 'dibayar' || $status === 'dikonfirmasi') {
                                    $statusClass = 'status-dibayar';
                                    $statusIcon = '💳';
                                } elseif ($status === 'selesai') {
                                    $statusClass = 'status-selesai';
                                    $statusIcon = '✅';
                                } elseif ($status === 'dikirim') {
                                    $statusClass = 'status-dibayar';
                                    $statusIcon = '🚚';
                                } elseif ($status === 'diproses') {
                                    $statusClass = 'status-dibayar';
                                    $statusIcon = '👨‍🍳';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusIcon; ?>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pesanan['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #666;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                                <div>Belum ada pesanan</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Produk Terlaris -->
        <div class="content-section">
            <h2 class="section-title">
                <span>🏆</span>
                Produk Terlaris
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Total Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($produkTerlaris->num_rows > 0): ?>
                        <?php while($produk = $produkTerlaris->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                            <td>
                                <strong style="font-size: 1.2rem;"><?php echo $produk['total_terjual']; ?></strong>
                                <span style="color: var(--gray); font-size: 0.9rem;"> item</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 3rem; color: #666;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                                <div>Belum ada data penjualan</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>