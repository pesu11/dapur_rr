<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

try {
    $conn = getConnection();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $namaToko = trim($_POST['nama_toko']);
        $tagline = trim($_POST['tagline']);
        $deskripsi = trim($_POST['deskripsi']);
        $alamat = trim($_POST['alamat']);
        $noTelepon = trim($_POST['no_telepon']);
        $email = trim($_POST['email']);
        $jamBuka = trim($_POST['jam_buka']);
        $instagram = trim($_POST['instagram']);
        $facebook = trim($_POST['facebook']);
        $whatsapp = trim($_POST['whatsapp']);

        $stmt = $conn->prepare("UPDATE pengaturan SET 
            nama_toko = ?, 
            tagline = ?, 
            deskripsi = ?,
            alamat = ?,
            no_telepon = ?,
            email = ?,
            jam_buka = ?,
            instagram = ?,
            facebook = ?,
            whatsapp = ?
            WHERE id = 1");

        if (!$stmt) {
            throw new Exception("Query prepare gagal");
        }

        $stmt->bind_param("ssssssssss",
            $namaToko, $tagline, $deskripsi, $alamat,
            $noTelepon, $email, $jamBuka,
            $instagram, $facebook, $whatsapp
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal eksekusi query");
        }

        setFlashMessage('success', 'Pengaturan berhasil disimpan');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Get current settings
    $result = $conn->query("SELECT * FROM pengaturan LIMIT 1");
    if (!$result) {
        throw new Exception("Gagal ambil data pengaturan");
    }
    $pengaturan = $result->fetch_assoc();
    $flash = getFlashMessage();

} catch (Exception $e) {
    // Jika terjadi error (query/koneksi dsb)
    setFlashMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Dapur RR</title>
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
        
        .alert-error {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #7F1D1D;
            border-left-color: var(--danger);
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }
        
        /* Card */
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            max-width: 900px;
            border: 1px solid rgba(255, 105, 180, 0.1);
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
        
        /* Form */
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
            background: white;
            color: var(--dark);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        /* Button */
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
            font-size: 1rem;
            font-weight: 600;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
            width: 100%;
            box-shadow: 0 4px 15px rgba(255, 20, 147, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }
        
        /* Section */
        .section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .section:last-child {
            border-bottom: none;
            padding-bottom: 0;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .card {
                padding: 1.5rem;
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
            <a href="analitik.php" class="menu-item">
                <span>📊</span>
                <span>Analitik</span>
            </a>
             <a href="pembayaran.php" class="menu-item">
                <span>💳</span>
                <span>Pembayaran</span>
            <a href="pengaturan.php" class="menu-item active">
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
            <h1 class="page-title">⚙️ Pengaturan Toko</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
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

        <div class="card">
            <form method="POST" action="">
                <!-- Informasi Toko -->
                <div class="section">
                    <h2 class="card-title"> Informasi Toko</h2>
                    
                    <div class="form-group">
                        <label for="nama_toko">Nama Toko</label>
                        <input type="text" id="nama_toko" name="nama_toko" 
                               value="<?php echo htmlspecialchars($pengaturan['nama_toko'] ?? ''); ?>" 
                               placeholder="Masukkan nama toko" required>
                    </div>

                    <div class="form-group">
                        <label for="tagline">Tagline</label>
                        <input type="text" id="tagline" name="tagline" 
                               value="<?php echo htmlspecialchars($pengaturan['tagline'] ?? ''); ?>"
                               placeholder="Slogan atau tagline toko Anda">
                        <div class="help-text">Slogan atau tagline toko Anda</div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Toko</label>
                        <textarea id="deskripsi" name="deskripsi" 
                                  placeholder="Deskripsi singkat tentang toko Anda"><?php echo htmlspecialchars($pengaturan['deskripsi'] ?? ''); ?></textarea>
                        <div class="help-text">Deskripsi singkat tentang toko Anda</div>
                    </div>
                </div>

                <!-- Kontak -->
                <div class="section">
                    <h2 class="card-title"> Informasi Kontak</h2>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat Lengkap</label>
                        <textarea id="alamat" name="alamat" 
                                  placeholder="Alamat lengkap toko Anda"><?php echo htmlspecialchars($pengaturan['alamat'] ?? ''); ?></textarea>
                        <div class="help-text">Alamat lengkap toko yang akan ditampilkan</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_telepon">Nomor Telepon</label>
                            <input type="text" id="no_telepon" name="no_telepon" 
                                   value="<?php echo htmlspecialchars($pengaturan['no_telepon'] ?? ''); ?>"
                                   placeholder="Contoh: 081234567890">
                            <div class="help-text">Nomor telepon yang dapat dihubungi</div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($pengaturan['email'] ?? ''); ?>"
                                   placeholder="Contoh: info@tokokue.com">
                            <div class="help-text">Email resmi toko</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="jam_buka">Jam Operasional</label>
                        <input type="text" id="jam_buka" name="jam_buka" 
                               value="<?php echo htmlspecialchars($pengaturan['jam_buka'] ?? ''); ?>"
                               placeholder="Contoh: Senin - Jumat: 08:00 - 17:00, Sabtu - Minggu: 09:00 - 16:00">
                        <div class="help-text">Jam buka toko Anda</div>
                    </div>
                </div>

                <!-- Media Sosial -->
                <div class="section">
                    <h2 class="card-title"> Media Sosial</h2>
                    
                    <div class="form-group">
                        <label for="instagram">Instagram</label>
                        <input type="text" id="instagram" name="instagram" 
                               value="<?php echo htmlspecialchars($pengaturan['instagram'] ?? ''); ?>"
                               placeholder="Contoh: @tokokue">
                        <div class="help-text">Username Instagram (tanpa @ jika sudah ada)</div>
                    </div>

                   

                    <div class="form-group">
                        <label for="whatsapp">WhatsApp</label>
                        <input type="text" id="whatsapp" name="whatsapp" 
                               value="<?php echo htmlspecialchars($pengaturan['whatsapp'] ?? ''); ?>"
                               placeholder="Contoh: 628123456789">
                        <div class="help-text">Format: 628xxx (tanpa +, gunakan kode negara Indonesia)</div>
                    </div>
                </div>

                <!-- Save Button -->
                <button type="submit" class="btn btn-primary">
                     Simpan Pengaturan
                </button>
            </form>
        </div>

        <!-- Additional Info -->
        <div class="card" style="margin-top: 2rem;">
            <h2 class="card-title"> Informasi</h2>
            <div style="padding: 1rem 0;">
                <p style="color: #666; margin-bottom: 1rem;">
                    Pengaturan ini akan mempengaruhi tampilan website toko Anda. Pastikan informasi yang dimasukkan akurat dan terbaru.
                </p>
                <div style="background: var(--light); padding: 1rem; border-radius: 8px; border-left: 4px solid var(--primary);">
                    <strong>Tips:</strong>
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem; color: #666;">
                        <li>Gunakan deskripsi yang menarik untuk menarik pelanggan</li>
                        <li>Pastikan nomor kontak dan email aktif</li>
                        <li>Update jam operasional sesuai dengan waktu buka toko</li>
                        <li>Link media sosial harus valid dan dapat diakses</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const namaToko = document.getElementById('nama_toko').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!namaToko) {
                alert('Nama toko wajib diisi!');
                e.preventDefault();
                return;
            }
            
            if (email && !isValidEmail(email)) {
                alert('Format email tidak valid!');
                e.preventDefault();
                return;
            }
            
            const whatsapp = document.getElementById('whatsapp').value.trim();
            if (whatsapp && !/^62\d{9,}$/.test(whatsapp)) {
                alert('Format WhatsApp tidak valid! Gunakan format: 628123456789');
                e.preventDefault();
                return;
            }
        });
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>