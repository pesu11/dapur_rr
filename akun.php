<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $noTelepon = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid";
    }
    
    // Cek email sudah digunakan user lain
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email sudah digunakan";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, alamat = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama, $email, $noTelepon, $alamat, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            setFlashMessage('success', 'Profil berhasil diperbarui');
            redirect('akun.php');
        } else {
            $errors[] = "Gagal memperbarui profil";
        }
    }
}

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $passwordLama = $_POST['password_lama'];
    $passwordBaru = $_POST['password_baru'];
    $konfirmasiPassword = $_POST['konfirmasi_password'];
    
    $errors = [];
    
    if (!password_verify($passwordLama, $user['password'])) {
        $errors[] = "Password lama tidak sesuai";
    }
    
    if (strlen($passwordBaru) < 6) {
        $errors[] = "Password baru minimal 6 karakter";
    }
    
    if ($passwordBaru !== $konfirmasiPassword) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Password berhasil diubah');
            redirect('akun.php');
        } else {
            $errors[] = "Gagal mengubah password";
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
    <title>Pengaturan Akun - <?php echo htmlspecialchars($pengaturan['nama_toko']); ?></title>
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
            --gradient-3: linear-gradient(135deg, #FFC1E3 0%, #FFB6D9 100%);
            --gradient-4: linear-gradient(135deg, #FF85A1 0%, #FF69B4 100%);
            --shadow-sm: 0 2px 15px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 25px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 40px rgba(255, 105, 180, 0.2);
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            min-height: 100vh;
        }
        
        /* HEADER SAMA PERSIS DENGAN INDEX.PHP */
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
            animation: fadeInLeft 0.8s ease;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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

        nav {
            background: transparent;
            animation: fadeIn 1s ease 0.3s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            letter-spacing: 0.3px;
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

        nav a::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 0;
            height: 0;
            background: rgba(255, 105, 180, 0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: -1;
        }

        nav a:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        nav a:hover::before {
            width: 100%;
        }

        nav a:hover::after {
            width: 120%;
            height: 150%;
        }

        nav a.active {
            color: var(--accent);
        }

        nav a.active::before {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            animation: fadeInRight 0.8s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
            background: transparent;
        }
        
        .user-menu a.user-name:hover {
            color: var(--accent);
            background: rgba(255, 105, 180, 0.1);
            transform: translateY(-2px);
        }
        
        .user-menu a.user-name.active {
            color: var(--accent);
            background: rgba(255, 105, 180, 0.1);
        }
        
        .cart-icon, .wishlist-icon {
            position: relative;
            color: var(--dark);
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s;
            padding: 0.5rem;
        }
        
        .cart-icon:hover, .wishlist-icon:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }
        
        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--gradient-1);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(255, 20, 147, 0.3);
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
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-logout:hover {
            background: var(--dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 24, 16, 0.3);
        }

        /* STYLING KONTEN AKUN */
        .container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: -1px;
        }
        
        .alert {
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
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
            color: #991B1B;
            border-left: 5px solid #EF4444;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }
        
        .account-section {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .account-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }
        
        .account-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.7rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }
        
        input, textarea {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #FFE5EC;
            border-radius: 15px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #FFF9FB;
            color: var(--dark);
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
            background: white;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn-submit {
            padding: 1.2rem 2.5rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 107, 157, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 157, 0.4);
        }
        
        .btn-submit:active {
            transform: translateY(-1px);
        }
        
        .error-list {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            border-left: 5px solid #EF4444;
            color: #991B1B;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
        }
        
        .error-list ul {
            margin-left: 1.5rem;
        }
        
        .error-list li {
            margin-bottom: 0.5rem;
        }
        
        .user-info-display {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFFAF5 100%);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.1);
        }
        
        .info-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 105, 180, 0.2);
            align-items: center;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 700;
            color: var(--primary);
            min-width: 180px;
            font-size: 1rem;
        }
        
        .info-value {
            color: var(--dark);
            font-weight: 500;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1rem 20px;
            }

            .user-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
                padding: 0 20px;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .container {
                padding: 0 20px;
            }

            .account-section {
                padding: 2rem 1.5rem;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .info-label {
                min-width: auto;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .account-section {
                padding: 1.5rem 1rem;
            }

            .btn-logout {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .btn-submit {
                padding: 1rem 2rem;
                font-size: 1rem;
                width: 100%;
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
                    'images/logo.png',
                    'assets/logo.png',
                    'logo.png'
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

            <div class="auth-buttons">
                <div class="user-menu">
                    <a href="akun.php" class="user-name active">
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
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">⚙️Pengaturan Akun</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <strong>Terjadi Kesalahan:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Informasi Akun -->
        <div class="account-section">
            <h2 class="section-title">👤 Informasi Akun</h2>
            <div class="user-info-display">
                <div class="info-item">
                    <span class="info-label">Nama:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['nama']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">No. Telepon:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['no_telepon'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Alamat:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['alamat'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Terdaftar Sejak:</span>
                    <span class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Edit Profil -->
        <div class="account-section">
            <h2 class="section-title">Edit Profil</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap *</label>
                    <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_telepon">No. Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-submit">Simpan Perubahan</button>
            </form>
        </div>

        <!-- Ubah Password -->
        <div class="account-section">
            <h2 class="section-title">Ubah Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="password_lama">Password Lama *</label>
                    <input type="password" id="password_lama" name="password_lama" required>
                </div>

                <div class="form-group">
                    <label for="password_baru">Password Baru * (minimal 6 karakter)</label>
                    <input type="password" id="password_baru" name="password_baru" required>
                </div>

                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password Baru *</label>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                </div>

                <button type="submit" name="update_password" class="btn-submit">Ubah Password</button>
            </form>
        </div>
    </div>
</body>
</html>