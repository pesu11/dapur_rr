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
        $judul = trim($_POST['judul']);
        $deskripsi = trim($_POST['deskripsi']);
        $link = trim($_POST['link']);
        $urutan = intval($_POST['urutan']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle file upload
        // ===== FIXED UPLOAD SECTION =====
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                // Gunakan __DIR__ agar path mutlak (tidak salah arah)
                $uploadDir = dirname(__DIR__) . '/uploads/banner/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Buat nama unik
                $newFilename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $uploadPath = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                    // Simpan path relatif ke database
                    $gambar = 'uploads/banner/' . $newFilename;
                } else {
                    setFlashMessage('error', 'Gagal mengupload gambar');
                    redirect('banner.php');
                }
            } else {
                setFlashMessage('error', 'Format gambar tidak valid. Gunakan: JPG, PNG, GIF, WEBP');
                redirect('banner.php');
            }
        }

        $stmt = $conn->prepare("INSERT INTO banner (judul, deskripsi, gambar, link, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $judul, $deskripsi, $gambar, $link, $urutan, $isActive);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Banner berhasil ditambahkan');
        } else {
            setFlashMessage('error', 'Gagal menambahkan banner');
        }
        header('location: banner.php');
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $judul = trim($_POST['judul']);
        $deskripsi = trim($_POST['deskripsi']);
        $link = trim($_POST['link']);
        $urutan = intval($_POST['urutan']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Get current image
        $current = $conn->query("SELECT gambar FROM banner WHERE id = $id")->fetch_assoc();
        $gambar = $current['gambar'];
        
        // Handle new upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $uploadDir = '../uploads/banner/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newFilename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadPath)) {
                    // Delete old image
                    if ($gambar && file_exists('../' . $gambar)) {
                        unlink('../' . $gambar);
                    }
                    $gambar = 'uploads/banner/' . $newFilename;
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE banner SET judul = ?, deskripsi = ?, gambar = ?, link = ?, urutan = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssssiis", $judul, $deskripsi, $gambar, $link, $urutan, $isActive, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Banner berhasil diupdate');
        }
        header('Location: banner.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Get image path
        $banner = $conn->query("SELECT gambar FROM banner WHERE id = $id")->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM banner WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Delete image file
            if ($banner['gambar'] && file_exists('../' . $banner['gambar'])) {
                unlink('../' . $banner['gambar']);
            }
            setFlashMessage('success', 'Banner berhasil dihapus');
        }
        header('Location: banner.php');
    }
    
    if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE banner SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        setFlashMessage('success', 'Status banner berhasil diubah');
        header('Location: banner.php');
    }
}

// Get banners
$banners = $conn->query("SELECT * FROM banner ORDER BY urutan ASC, created_at DESC");
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Banner - Admin Dapur RR</title>
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
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        /* Cards */
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
            font-weight: 600;
        }
        
        /* Buttons */
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            background: #0DA875;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
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
        }
        
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
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
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        /* File Input */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 1rem;
            background: var(--light);
            border: 2px dashed rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            color: var(--dark);
            font-weight: 500;
        }
        
        .file-input-label:hover {
            border-color: var(--primary);
            background: var(--light);
            transform: translateY(-2px);
        }
        
        .file-input-label.has-file {
            border-color: var(--success);
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
            accent-color: var(--primary);
        }
        
        /* Banner List */
        .banner-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .banner-item {
            padding: 1.5rem;
            border: 1px solid rgba(255, 105, 180, 0.1);
            border-radius: 12px;
            display: flex;
            gap: 1.5rem;
            transition: all 0.3s;
            background: white;
        }
        
        .banner-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .banner-image {
            flex: 0 0 200px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            background: var(--gradient-3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: var(--shadow-sm);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .banner-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .banner-info {
            flex: 1;
        }
        
        .banner-info h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .banner-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .banner-meta {
            color: #999;
            font-size: 0.85rem;
        }
        
        .banner-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        /* Badges */
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
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: var(--danger);
            border: 2px solid #FCA5A5;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            color: var(--warning);
            border: 2px solid #FCD34D;
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
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
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
            margin: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .modal-header h3 {
            color: var(--dark);
            font-size: 1.5rem;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 2rem;
            color: #999;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .current-image {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .current-image label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        .current-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
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
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .banner-item {
                flex-direction: column;
            }
            
            .banner-image {
                width: 100%;
                height: 150px;
            }
            
            .banner-actions {
                flex-direction: row;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .banner-item {
                padding: 1rem;
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
            <a href="banner.php" class="menu-item active">
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
            <h1 class="page-title">Kelola Banner</h1>
            <div class="user-info">
                <div class="admin-name">👤 <?php echo htmlspecialchars(getUserName()); ?></div>
                <a href="../logout.php" class="btn-logout">
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

        <div class="content-grid">
            <!-- Form Add -->
            <div class="card">
                <h2 class="card-title">Tambah Banner Baru</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="gambar">Gambar Banner *</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="gambar" name="gambar" accept="image/*" onchange="updateFileName(this, 'add')" required>
                            <label for="gambar" class="file-input-label" id="file-label-add">
                                📷 Klik untuk pilih gambar
                            </label>
                        </div>
                        <div class="help-text">Format: JPG, PNG, GIF, WEBP. Maks: 2MB. Rekomendasi: 1920x600px</div>
                    </div>
                    <div class="form-group">
                        <label for="judul">Judul *</label>
                        <input type="text" id="judul" name="judul" required>
                    </div>
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="urutan">Urutan</label>
                        <input type="number" id="urutan" name="urutan" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active">Aktif</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Tambah Banner</button>
                </form>
            </div>

            <!-- List Banners -->
            <div class="card">
                <h2 class="card-title">Daftar Banner (<?php echo $banners->num_rows; ?>)</h2>
                <div class="banner-list">
                    <?php if ($banners->num_rows > 0): ?>
                        <?php while($b = $banners->fetch_assoc()): ?>
                        <div class="banner-item">
                            <div class="banner-image">
                                <?php if ($b['gambar'] && file_exists('../' . $b['gambar'])): ?>
                                    <img src="../<?php echo htmlspecialchars($b['gambar']); ?>" alt="Banner">
                                <?php else: ?>
                                    🖼️
                                <?php endif; ?>
                            </div>
                            <div class="banner-info">
                                <h3><?php echo htmlspecialchars($b['judul']); ?></h3>
                                <p><?php echo htmlspecialchars($b['deskripsi']); ?></p>
                                <div class="banner-meta">
                                    Urutan: <?php echo $b['urutan']; ?> | 
                                    <span class="badge <?php echo $b['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $b['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                    <?php if ($b['link']): ?>
                                    <br><a href="<?php echo htmlspecialchars($b['link']); ?>" target="_blank">Link</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="banner-actions">
                                <button onclick='editBanner(<?php echo json_encode($b); ?>)' 
                                        class="btn btn-warning btn-sm">
                                    Edit
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <?php echo $b['is_active'] ? '👁️' : '🚫'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Yakin hapus banner ini? Gambar akan dihapus permanen.')">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">
                            Belum ada banner
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Banner</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="current-image" id="current_image_container" style="display: none;">
                    <label style="margin-bottom: 0.5rem; display: block;">Gambar Saat Ini:</label>
                    <img id="current_image" src="" alt="Current Banner" style="max-height: 150px;">
                </div>
                
                <div class="form-group">
                    <label for="edit_gambar">Gambar Baru (Opsional - kosongkan jika tidak diubah)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="edit_gambar" name="gambar" accept="image/*" onchange="updateFileName(this, 'edit')">
                        <label for="edit_gambar" class="file-input-label" id="file-label-edit">
                            📷 Klik untuk pilih gambar baru
                        </label>
                    </div>
                    <div class="help-text">Format: JPG, PNG, GIF, WEBP. Maks: 2MB</div>
                </div>
                
                <div class="form-group">
                    <label for="edit_judul">Judul *</label>
                    <input type="text" id="edit_judul" name="judul" required>
                </div>
                <div class="form-group">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <textarea id="edit_deskripsi" name="deskripsi"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_link">Link (Opsional)</label>
                    <input type="text" id="edit_link" name="link">
                </div>
                <div class="form-group">
                    <label for="edit_urutan">Urutan</label>
                    <input type="number" id="edit_urutan" name="urutan" min="1">
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label for="edit_is_active">Aktif</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Update Banner</button>
            </form>
        </div>
    </div>

    <script>
        function updateFileName(input, type) {
            const label = document.getElementById('file-label-' + type);
            if (input.files && input.files[0]) {
                label.textContent = '✓ ' + input.files[0].name;
                label.classList.add('has-file');
            } else {
                label.textContent = '📷Klik untuk pilih gambar';
                label.classList.remove('has-file');
            }
        }
        
        function editBanner(banner) {
            document.getElementById('edit_id').value = banner.id;
            document.getElementById('edit_judul').value = banner.judul;
            document.getElementById('edit_deskripsi').value = banner.deskripsi;
            document.getElementById('edit_link').value = banner.link;
            document.getElementById('edit_urutan').value = banner.urutan;
            document.getElementById('edit_is_active').checked = banner.is_active == 1;
            
            // Show current image if exists
            if (banner.gambar) {
                document.getElementById('current_image').src = '../' + banner.gambar;
                document.getElementById('current_image_container').style.display = 'block';
            } else {
                document.getElementById('current_image_container').style.display = 'none';
            }
            
            // Reset file input label
            document.getElementById('file-label-edit').textContent = '📷 Klik untuk pilih gambar baru';
            document.getElementById('file-label-edit').classList.remove('has-file');
            
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