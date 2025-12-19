<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil pesanan yang bisa dikembalikan
$pesanan = $conn->query("SELECT * FROM pesanan 
                         WHERE user_id = $userId 
                         AND status IN ('dikirim', 'selesai')
                         ORDER BY created_at DESC");

$errors = [];

// Proses form pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesananId = $_POST['pesanan_id'] ?? 0;
    $alasan = trim($_POST['alasan']);
    
    if (empty($alasan)) {
        $errors[] = "Alasan pengembalian harus diisi";
    }
    
    // Validasi pesanan
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pesananId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $errors[] = "Pesanan tidak ditemukan";
    }
    
    // Cek apakah sudah pernah mengajukan pengembalian
    $stmt = $conn->prepare("SELECT id FROM pengembalian WHERE pesanan_id = ?");
    $stmt->bind_param("i", $pesananId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Anda sudah mengajukan pengembalian untuk pesanan ini";
    }
    
    // Handle upload bukti foto
    $buktiFoto = null;
    if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['bukti_foto']['type'], $allowedTypes)) {
            $errors[] = "Format file harus JPG, JPEG, atau PNG";
        }
        
        if ($_FILES['bukti_foto']['size'] > $maxSize) {
            $errors[] = "Ukuran file maksimal 5MB";
        }
        
        if (empty($errors)) {
            $uploadDir = 'uploads/pengembalian/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['bukti_foto']['name'], PATHINFO_EXTENSION);
            $filename = 'bukti_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'], $uploadPath)) {
                $buktiFoto = $uploadPath;
            } else {
                $errors[] = "Gagal mengupload bukti foto";
            }
        }
    } else {
        $errors[] = "Bukti foto harus diupload";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO pengembalian (pesanan_id, user_id, alasan, bukti_foto, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiss", $pesananId, $userId, $alasan, $buktiFoto);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Pengajuan pengembalian berhasil dikirim. Kami akan menghubungi Anda segera.');
            redirect('riwayat-pesanan.php');
        } else {
            $errors[] = "Gagal mengirim pengajuan pengembalian";
        }
    }
}

$flash = getFlashMessage();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Produk - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --info: #3B82F6;
            --dark: #2C1810;
            --light: #FFF5F8;
            --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            --gradient-2: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
            --shadow-sm: 0 2px 15px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 25px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 40px rgba(255, 105, 180, 0.2);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            overflow-x: hidden;
            min-height: 100vh;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
            border-bottom: 2px solid rgba(255, 105, 180, 0.1);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-100%);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-top {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.2rem 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .logo-image {
            width: 65px;
            height: 65px;
            border-radius: 20px;
            object-fit: cover;
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.3);
            border: 3px solid rgba(255, 105, 180, 0.3);
            transition: all 0.4s ease;
        }

        .logo:hover .logo-image {
            box-shadow: 0 8px 30px rgba(255, 105, 180, 0.5);
            transform: rotate(-5deg) scale(1.1);
            border-color: var(--accent);
        }

        .logo-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .logo-text p {
            font-size: 0.9rem;
            color: #FF69B4;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        nav ul {
            display: flex;
            gap: 2.8rem;
            list-style: none;
            align-items: center;
        }

        nav a {
            color: #4B5563;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s ease;
        }

        nav a::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: var(--gradient-1);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        nav a:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        nav a:hover::before {
            width: 100%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-menu a.user-name {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .user-menu a.user-name:hover {
            color: var(--accent);
            background: rgba(255, 105, 180, 0.1);
            transform: translateY(-2px);
        }

        .cart-icon, .wishlist-icon {
            position: relative;
            color: var(--dark);
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .cart-icon:hover, .wishlist-icon:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .btn-logout {
            background: transparent;
            color: var(--dark);
            border: 2px solid var(--dark);
            padding: 0.7rem 1.5rem;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--dark);
            color: white;
        }
        
        .container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            color: var(--dark);
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            background: var(--accent);
            color: white;
            transform: translateX(-5px);
            box-shadow: var(--shadow-md);
        }

        .alert {
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            animation: slideInDown 0.6s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left: 5px solid #10B981;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #7F1D1D;
            border-left: 5px solid #EF4444;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }

        .info-box {
            background: white;
            border: 2px solid rgba(255, 105, 180, 0.2);
            padding: 2.5rem;
            border-radius: 25px;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-md);
        }
        
        .info-box h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .info-box ul {
            margin-left: 1.5rem;
            color: #666;
        }
        
        .info-box li {
            margin-bottom: 0.8rem;
            line-height: 1.8;
        }
        
        .form-card {
            background: white;
            padding: 3rem;
            border-radius: 25px;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }

        .form-card h2 {
            font-family: 'Playfair Display', serif;
            color: var(--dark);
            font-size: 2.5rem;
            margin-bottom: 2rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 700;
            color: var(--dark);
            font-size: 1.05rem;
        }
        
        select, textarea {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 15px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: white;
        }
        
        select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 150px;
        }

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2rem;
            border: 3px dashed rgba(255, 105, 180, 0.3);
            border-radius: 15px;
            background: var(--light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--dark);
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: white;
            transform: translateY(-2px);
        }

        .file-upload-icon {
            font-size: 2rem;
        }

        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 2px solid var(--primary);
            display: none;
        }

        .file-preview.show {
            display: block;
        }

        .file-preview img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 0.5rem;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1.3rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 157, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.4);
        }

        .note-box {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
        }

        .note-box strong {
            color: var(--accent);
            display: block;
            margin-bottom: 0.5rem;
        }

        .empty-card {
            text-align: center;
            padding: 4rem 3rem;
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .empty-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1rem 20px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .container {
                padding: 0 20px;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .form-card {
                padding: 2rem;
            }

            .info-box {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-top">
            <div class="logo">
                <?php 
                $logo_paths = [
                    'uploads/logo/logo-dapurr.png',
                    'uploads/logo/logo.png',
                    'images/logo.png'
                ];
                
                $logo_found = false;
                foreach($logo_paths as $path) {
                    if(file_exists($path)) {
                        echo '<img src="' . $path . '" alt="Logo" class="logo-image">';
                        $logo_found = true;
                        break;
                    }
                }
                
                if(!$logo_found) {
                    echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #FF6B9D 0%, #FFC93C 100%); font-size: 2rem;">🧁</div>';
                }
                ?>
                <div class="logo-text">
                    <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
                    <p><?php echo htmlspecialchars($pengaturan['tagline']); ?></p>
                </div>
            </div>

            <nav>
                <ul>
                    <li><a href="home.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="riwayat-pesanan.php">Pesanan</a></li>
                    <li><a href="promo.php">Promo</a></li>
                </ul>
            </nav>

            <div class="user-menu">
                <a href="akun.php" class="user-name">
                    👤 <?php echo htmlspecialchars($_SESSION['nama']); ?>
                </a>
                <a href="wishlist.php" class="wishlist-icon">
                    ❤️
                    <?php if ($wishlistCount > 0): ?>
                    <span class="badge"><?php echo $wishlistCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="keranjang.php" class="cart-icon">
                    🛒
                    <?php if ($cartCount > 0): ?>
                    <span class="badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="riwayat-pesanan.php" class="btn-back">← Kembali ke Riwayat Pesanan</a>

        <div class="page-header">
            <h1 class="page-title">Pengembalian Produk</h1>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span style="font-size: 1.5rem;">⚠</span>
            <div>
                <strong>Terjadi Kesalahan:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>📋 Syarat & Ketentuan Pengembalian</h3>
            <ul>
                <li>Pengajuan pengembalian maksimal 1x24 jam setelah barang diterima</li>
                <li>Produk dalam kondisi kemasan asli dan belum dibuka</li>
                <li>Wajib menyertakan foto bukti kerusakan/cacat produk</li>
                <li>Hanya untuk produk rusak, cacat, atau tidak sesuai pesanan</li>
                <li>Kesalahan pemesanan dari pembeli tidak dapat dikembalikan</li>
                <li>Pengembalian dana diproses 3-7 hari kerja setelah disetujui</li>
            </ul>
        </div>

        <?php if ($pesanan->num_rows === 0): ?>
        <div class="form-card empty-card">
            <div class="empty-icon">📦</div>
            <h2>Tidak Ada Pesanan yang Dapat Dikembalikan</h2>
            <p style="color: #666; margin-top: 1rem;">Anda belum memiliki pesanan yang dapat dikembalikan.</p>
            <a href="produk.php" class="btn-submit" style="display: inline-block; width: auto; margin-top: 2rem; text-decoration: none; padding: 1rem 2rem;">Mulai Belanja</a>
        </div>
        <?php else: ?>
        <div class="form-card">
            <h2>Form Pengajuan Pengembalian</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="pesanan_id">Pilih Pesanan *</label>
                    <select id="pesanan_id" name="pesanan_id" required>
                        <option value="">-- Pilih Pesanan --</option>
                        <?php while($p = $pesanan->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>">
                            Pesanan #<?php echo htmlspecialchars($p['no_pesanan']); ?> - 
                            <?php echo formatRupiah($p['total_bayar']); ?> - 
                            <?php echo date('d/m/Y', strtotime($p['created_at'])); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">Pilih pesanan yang ingin Anda kembalikan</small>
                </div>

                <div class="form-group">
                    <label for="alasan">Alasan Pengembalian *</label>
                    <textarea id="alasan" name="alasan" required placeholder="Jelaskan alasan pengembalian secara detail. Contoh: Produk rusak, tidak sesuai pesanan, dll."></textarea>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">Jelaskan alasan pengembalian sejelas mungkin</small>
                </div>

                <div class="form-group">
                    <label for="bukti_foto">Upload Bukti Foto Produk *</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="bukti_foto" name="bukti_foto" accept="image/*" required class="file-upload-input" onchange="previewImage(this)">
                        <label for="bukti_foto" class="file-upload-label">
                            <span class="file-upload-icon">📷</span>
                            <span>Klik untuk upload foto bukti kerusakan/cacat produk</span>
                        </label>
                    </div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">Format: JPG, PNG. Maksimal 5MB</small>
                    <div id="file-preview" class="file-preview"></div>
                </div>

                <div class="note-box">
                    <strong>📌 Catatan Penting:</strong>
                    <p style="color: #666; margin-top: 0.5rem;">
                        Setelah mengajukan pengembalian, tim kami akan menghubungi Anda melalui email atau WhatsApp untuk 
                        verifikasi dan instruksi selanjutnya. Pastikan foto yang diupload jelas menunjukkan kerusakan produk.
                    </p>
                </div>

                <button type="submit" class="btn-submit">📤 Ajukan Pengembalian</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('file-preview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <strong style="color: var(--success);">✓ File terpilih: ${file.name}</strong>
                        <img src="${e.target.result}" alt="Preview">
                    `;
                    preview.classList.add('show');
                };
                reader.readAsDataURL(file);
            }
        }

        const alert = document.querySelector('.alert');
        if(alert) {
            setTimeout(() => {
                alert.style.animation = 'slideOutUp 0.5s ease forwards';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutUp {
                to {
                    opacity: 0;
                    transform: translateY(-30px) scale(0.95);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>