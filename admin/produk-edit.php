<?php
session_start();
require_once '../config/database.php';

// Check admin login
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setFlashMessage('error', 'ID produk tidak valid');
    header('Location: produk.php');
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // Cek apakah produk ada
    $stmt = $conn->prepare("SELECT id, nama_produk, gambar FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $produk = $result->fetch_assoc();
        
        // Hapus file gambar jika ada
        if (!empty($produk['gambar'])) {
            $gambarPath = "../uploads/products/" . $produk['gambar'];
            if (file_exists($gambarPath)) {
                @unlink($gambarPath);
            }
        }
        
        // Hapus produk dari database
        $deleteStmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            setFlashMessage('success', 'Produk "' . htmlspecialchars($produk['nama_produk']) . '" berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus produk: ' . $conn->error);
        }
        
        $deleteStmt->close();
    } else {
        setFlashMessage('error', 'Produk tidak ditemukan');
    }
    
    header('Location: produk.php');
}

// Get produk data
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    setFlashMessage('error', 'Produk tidak ditemukan');
    header('Location: produk.php');
}

// Get kategori
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Handle UPDATE form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $namaProduk = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategoriId = intval($_POST['kategori_id']);
    $harga = floatval($_POST['harga']);
    $hargaPromo = isset($_POST['harga_promo']) ? floatval($_POST['harga_promo']) : 0;
    $isPromo = isset($_POST['is_promo']) ? 1 : 0;
    $stok = intval($_POST['stok']);
    $berat = floatval($_POST['berat']);
    
    $errors = [];
    
    // Validation
    if (empty($namaProduk)) {
        $errors[] = "Nama produk wajib diisi";
    }
    if (empty($deskripsi)) {
        $errors[] = "Deskripsi wajib diisi";
    }
    if ($kategoriId <= 0) {
        $errors[] = "Kategori wajib dipilih";
    }
    if ($harga <= 0) {
        $errors[] = "Harga harus lebih dari 0";
    }
    if ($isPromo && $hargaPromo <= 0) {
        $errors[] = "Harga promo harus lebih dari 0";
    }
    if ($isPromo && $hargaPromo >= $harga) {
        $errors[] = "Harga promo harus lebih kecil dari harga normal";
    }
    if ($stok < 0) {
        $errors[] = "Stok tidak boleh negatif";
    }
    
    if (empty($errors)) {
        // Update produk
        $stmt = $conn->prepare("UPDATE produk SET 
            nama_produk = ?, 
            deskripsi = ?, 
            kategori_id = ?, 
            harga = ?, 
            harga_promo = ?, 
            is_promo = ?, 
            stok = ?, 
            berat = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        
        $stmt->bind_param("ssiddiidi", 
            $namaProduk, 
            $deskripsi, 
            $kategoriId, 
            $harga, 
            $hargaPromo, 
            $isPromo, 
            $stok, 
            $berat,
            $id
        );
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Produk berhasil diupdate');
            header('Location: produk.php');
        } else {
            $errors[] = "Gagal mengupdate produk: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Admin Dapur RR</title>
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
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group label.required::after {
            content: " *";
            color: var(--danger);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert ul {
            margin-left: 1.5rem;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        .promo-fields {
            display: none;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .promo-fields.show {
            display: block;
        }
        
        .info-box {
            background: var(--light);
            border-left: 4px solid var(--info);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🧁 Dapur RR</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="produk.php" class="menu-item active">🛍️ Produk</a>
            <a href="kategori.php" class="menu-item">📁 Kategori</a>
            <a href="pesanan.php" class="menu-item">📦 Pesanan</a>
            <a href="pelanggan.php" class="menu-item">👥 Pelanggan</a>
            <a href="artikel2.php" class="menu-item">📝 Artikel</a>
            <a href="banner.php" class="menu-item">🖼️ Banner</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Produk</h1>
            <div class="user-info">
                <span>👤 <?php echo getUserName(); ?></span>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="info-box">
                <strong>ℹ️ Info:</strong> Anda sedang mengedit produk: <strong><?php echo htmlspecialchars($produk['nama_produk']); ?></strong>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama_produk" class="required">Nama Produk</label>
                    <input type="text" id="nama_produk" name="nama_produk" 
                           value="<?php echo htmlspecialchars($produk['nama_produk']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="required">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required><?php echo htmlspecialchars($produk['deskripsi']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="kategori_id" class="required">Kategori</label>
                    <select id="kategori_id" name="kategori_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while($k = $kategori->fetch_assoc()): ?>
                        <option value="<?php echo $k['id']; ?>" 
                                <?php echo ($produk['kategori_id'] == $k['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k['nama_kategori']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="harga" class="required">Harga Normal (Rp)</label>
                        <input type="number" id="harga" name="harga" min="0" step="1000" 
                               value="<?php echo $produk['harga']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stok" class="required">Stok</label>
                        <input type="number" id="stok" name="stok" min="0" 
                               value="<?php echo $produk['stok']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="berat">Berat (gram)</label>
                    <input type="number" id="berat" name="berat" min="0" step="1" 
                           value="<?php echo $produk['berat']; ?>">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_promo" name="is_promo" value="1"
                               <?php echo $produk['is_promo'] ? 'checked' : ''; ?>>
                        <label for="is_promo">Produk ini sedang promo</label>
                    </div>
                </div>

                <div class="promo-fields <?php echo $produk['is_promo'] ? 'show' : ''; ?>" id="promo-fields">
                    <div class="form-group">
                        <label for="harga_promo">Harga Promo (Rp)</label>
                        <input type="number" id="harga_promo" name="harga_promo" min="0" step="1000"
                               value="<?php echo $produk['harga_promo']; ?>">
                        <div class="help-text">Harga setelah diskon</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Produk</button>
                    <a href="produk.php" class="btn btn-secondary">❌ Batal</a>
                    <button type="button" 
                            class="btn btn-danger" 
                            onclick="if(confirm('Yakin ingin menghapus produk ini?\n\nProduk yang dihapus tidak dapat dikembalikan!')) { document.getElementById('deleteForm').submit(); }"
                            style="margin-left: auto;">
                        🗑️ Hapus Produk
                    </button>
                </div>
            </form>

            <!-- Hidden form untuk delete -->
            <form id="deleteForm" method="POST" action="" style="display: none;">
                <input type="hidden" name="action" value="delete">
            </form>
        </div>
    </div>

    <script>
        const promoCheckbox = document.getElementById('is_promo');
        const promoFields = document.getElementById('promo-fields');
        
        promoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                promoFields.classList.add('show');
            } else {
                promoFields.classList.remove('show');
            }
        });
    </script>
</body>
</html>