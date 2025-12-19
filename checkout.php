<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Cek keranjang
$keranjangQuery = "SELECT k.*, p.nama_produk, p.harga, p.is_promo, p.harga_promo, p.stok
                   FROM keranjang k
                   JOIN produk p ON k.produk_id = p.id
                   WHERE k.user_id = ?";
$stmt = $conn->prepare($keranjangQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$keranjang = $stmt->get_result();

if ($keranjang->num_rows === 0) {
    setFlashMessage('error', 'Keranjang belanja Anda kosong');
    redirect('keranjang.php');
}

// Hitung total
$totalHarga = 0;
$items = [];
while($item = $keranjang->fetch_assoc()) {
    $hargaSatuan = $item['is_promo'] ? $item['harga_promo'] : $item['harga'];
    $item['harga_satuan'] = $hargaSatuan;
    $item['subtotal'] = $hargaSatuan * $item['jumlah'];
    $totalHarga += $item['subtotal'];
    $items[] = $item;
}

// Ambil data user
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Ambil metode pembayaran
$metodePembayaran = $conn->query("SELECT * FROM metode_pembayaran WHERE is_active = 1");

// Ambil metode pengiriman
$metodePengiriman = $conn->query("SELECT * FROM metode_pengiriman WHERE is_active = 1");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo $pengaturan['nama_toko']; ?></title>
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
            --success: #FF69B4;
            --info: #FF85A1;
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
            background: linear-gradient(180deg, #FFF0F5 0%, #FFE4E9 50%, #FFD6E0 100%);
            color: var(--dark);
            line-height: 1.6;
        }
        
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
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
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .header-content span {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--accent);
            background: rgba(255, 182, 217, 0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 40px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }
        
        .alert {
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            animation: slideInDown 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .alert-error {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFD1DC 100%);
            color: #D32F2F;
            border-left: 5px solid var(--accent);
            box-shadow: var(--shadow-md);
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            background: var(--accent);
            color: white;
        }
        
        .checkout-wrapper {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 3rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid rgba(255, 105, 180, 0.2);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100px;
            height: 3px;
            background: var(--gradient-1);
            border-radius: 3px;
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 1.2rem 1.5rem;
            border: 2px solid rgba(255, 105, 180, 0.3);
            border-radius: 15px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            background: rgba(255, 182, 217, 0.1);
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
            background: white;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .radio-option {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 1.8rem;
            border: 3px solid rgba(255, 105, 180, 0.3);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            position: relative;
            overflow: hidden;
        }
        
        .radio-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-2);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .radio-option:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: var(--shadow-md);
        }
        
        .radio-option:hover::before {
            opacity: 0.05;
        }
        
        .radio-option input[type="radio"] {
            width: 24px;
            height: 24px;
            margin-top: 0.5rem;
            accent-color: var(--accent);
            position: relative;
            z-index: 1;
        }
        
        .radio-option.selected {
            border-color: var(--accent);
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            box-shadow: var(--shadow-md);
        }
        
        .option-details {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .option-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .option-desc {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        .option-price {
            color: var(--accent);
            font-weight: 800;
            font-size: 1.2rem;
            margin-top: 0.5rem;
        }
        
        .order-summary {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(255, 105, 180, 0.1);
            animation: fadeInUp 0.8s ease 0.2s both;
            height: fit-content;
            position: sticky;
            top: 30px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.8rem;
            padding-bottom: 1.8rem;
            border-bottom: 2px dashed rgba(255, 105, 180, 0.2);
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
            margin-right: 1.5rem;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .item-qty {
            color: var(--primary);
            font-weight: 600;
            font-size: 1rem;
            background: rgba(255, 182, 217, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 10px;
            display: inline-block;
        }
        
        .item-price {
            font-weight: 800;
            color: var(--accent);
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            color: #666;
        }
        
        .summary-row span:last-child {
            font-weight: 600;
            color: var(--dark);
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.8rem;
            font-weight: 800;
            padding-top: 1.5rem;
            border-top: 3px solid rgba(255, 105, 180, 0.3);
            margin-top: 1.5rem;
            color: var(--dark);
        }
        
        .summary-total span:last-child {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1.5rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.5px;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-submit:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.4);
        }
        
        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-submit:active {
            transform: translateY(-2px);
        }
        
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 0.8rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .checkout-wrapper {
                grid-template-columns: 1fr 400px;
                gap: 2.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .order-summary {
                position: static;
            }
            
            .container {
                padding: 0 30px;
            }
            
            .header-content {
                padding: 1.5rem 30px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
                margin: 2rem auto;
            }
            
            .header-content {
                padding: 1rem 20px;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
            
            .page-title {
                font-size: 2.2rem;
                text-align: center;
            }
            
            .checkout-form,
            .order-summary {
                padding: 2rem;
            }
            
            .section-title {
                font-size: 1.6rem;
            }
        }
        
        @media (max-width: 480px) {
            .checkout-form,
            .order-summary {
                padding: 1.5rem;
            }
            
            .radio-option {
                padding: 1.2rem;
            }
            
            .option-name {
                font-size: 1.1rem;
            }
            
            .btn-submit {
                padding: 1.2rem;
                font-size: 1.1rem;
            }
        }
        
        /* Floating animation for important elements */
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
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
                        echo '<img src="' . $path . '" alt="Logo" style="width: 50px; height: 50px; border-radius: 15px; object-fit: cover;">';
                        $logo_found = true;
                        break;
                    }
                }
                ?>
                <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
            </div>
            <span>Checkout Pesanan</span>
        </div>
    </header>

    <div class="container">
        <a href="keranjang.php" class="btn-back">← Kembali ke Keranjang</a>
        
        <h1 class="page-title">Selesaikan Pesanan Anda</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '🎉' : '⚠️'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="checkout-process.php">
            <div class="checkout-wrapper">
                <div class="checkout-form">
                    <!-- Informasi Pengiriman -->
                    <h2 class="section-title">📍 Informasi Pengiriman</h2>
                    
                    <div class="form-group">
                        <label for="nama">Nama Penerima *</label>
                        <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="no_telepon">No. Telepon *</label>
                        <input type="text" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="alamat">Alamat Lengkap *</label>
                        <textarea id="alamat" name="alamat" required><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan Pesanan (Opsional)</label>
                        <textarea id="catatan" name="catatan" placeholder="Contoh: Mohon kirim pagi hari, Tolong tambahkan pita, dll."></textarea>
                    </div>

                    <!-- Metode Pengiriman -->
                    <h2 class="section-title" style="margin-top: 3rem;">Metode Pengiriman</h2>
                    
                    <div class="radio-group">
                        <?php 
                        $metodePengiriman->data_seek(0); // Reset pointer
                        while($metode = $metodePengiriman->fetch_assoc()): 
                        ?>
                        <label class="radio-option">
                            <input type="radio" name="metode_pengiriman_id" value="<?php echo $metode['id']; ?>" data-biaya="<?php echo $metode['biaya']; ?>" required>
                            <div class="option-details">
                                <div class="option-name"><?php echo htmlspecialchars($metode['nama_metode']); ?></div>
                                <div class="option-desc"><?php echo htmlspecialchars($metode['deskripsi']); ?></div>
                                <div class="option-desc">Estimasi: <?php echo htmlspecialchars($metode['estimasi']); ?></div>
                                <div class="option-price"><?php echo formatRupiah($metode['biaya']); ?></div>
                            </div>
                        </label>
                        <?php endwhile; ?>
                    </div>

                    <!-- Metode Pembayaran -->
                    <h2 class="section-title" style="margin-top: 3rem;">💳 Metode Pembayaran</h2>
                    
                    <div class="radio-group">
                        <?php 
                        $metodePembayaran->data_seek(0); // Reset pointer
                        while($metode = $metodePembayaran->fetch_assoc()): 
                            $icon = '';
                            if (stripos($metode['nama_metode'], 'bank') !== false) $icon = '';
                            if (stripos($metode['nama_metode'], 'gopay') !== false) $icon = '';
                            if (stripos($metode['nama_metode'], 'ovo') !== false) $icon = '';
                            if (stripos($metode['nama_metode'], 'dana') !== false) $icon = '';
                            if (stripos($metode['nama_metode'], 'cod') !== false) $icon = '';
                        ?>
                        <label class="radio-option">
                            <input type="radio" name="metode_pembayaran_id" value="<?php echo $metode['id']; ?>" required>
                            <div class="option-details">
                                <div class="option-name">
                                    <span class="payment-icon"><?php echo $icon; ?></span>
                                    <?php echo htmlspecialchars($metode['nama_metode']); ?>
                                </div>
                                <div class="option-desc"><?php echo htmlspecialchars($metode['deskripsi']); ?></div>
                                <?php if ($metode['no_rekening'] !== '-'): ?>
                                <div class="option-desc">
                                    No. Rekening: <strong><?php echo htmlspecialchars($metode['no_rekening']); ?></strong><br>
                                    A/n: <strong><?php echo htmlspecialchars($metode['atas_nama']); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary floating">
                    <h2 class="section-title">Ringkasan Pesanan</h2>
                    
                    <?php foreach($items as $item): ?>
                    <div class="summary-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="item-qty"><?php echo $item['jumlah']; ?> x <?php echo formatRupiah($item['harga_satuan']); ?></div>
                        </div>
                        <div class="item-price"><?php echo formatRupiah($item['subtotal']); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-row" style="margin-top: 2rem;">
                        <span>Subtotal Produk:</span>
                        <span id="subtotal"><?php echo formatRupiah($totalHarga); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Ongkos Kirim:</span>
                        <span id="ongkir">Rp 0</span>
                    </div>

                    <div class="summary-total">
                        <span>Total Bayar:</span>
                        <span id="total"><?php echo formatRupiah($totalHarga); ?></span>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span style="font-size: 1.4rem;"></span> Buat Pesanan Sekarang
                    </button>
                    
                    <p style="text-align: center; margin-top: 1.5rem; color: #666; font-size: 0.9rem;">
                        Dengan mengklik tombol di atas, Anda menyetujui 
                        <a href="syarat-ketentuan.php" style="color: var(--accent); text-decoration: none;">syarat & ketentuan</a> kami.
                    </p>
                </div>
            </div>
        </form>
    </div>

    <script>
        const subtotal = <?php echo $totalHarga; ?>;
        const radioOptions = document.querySelectorAll('.radio-option');
        const pengirimanRadios = document.querySelectorAll('input[name="metode_pengiriman_id"]');
        let selectedOngkir = 0;
        
        // Highlight selected option
        radioOptions.forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Remove selected class from siblings
                const siblings = Array.from(this.parentElement.children);
                siblings.forEach(sib => sib.classList.remove('selected'));
                this.classList.add('selected');
                
                // If this is a shipping option, update total
                if (radio.name === 'metode_pengiriman_id') {
                    const biaya = parseInt(radio.dataset.biaya);
                    selectedOngkir = biaya;
                    updateTotal();
                }
            });
            
            // Auto-select first shipping option
            const radio = option.querySelector('input[name="metode_pengiriman_id"]');
            if (radio && !selectedOngkir) {
                const biaya = parseInt(radio.dataset.biaya);
                if (biaya > 0) {
                    radio.checked = true;
                    option.classList.add('selected');
                    selectedOngkir = biaya;
                    updateTotal();
                }
            }
        });

        // Update total function
        function updateTotal() {
            const total = subtotal + selectedOngkir;
            
            document.getElementById('ongkir').textContent = formatRupiah(selectedOngkir);
            document.getElementById('total').textContent = formatRupiah(total);
            
            // Animate update
            const totalElement = document.getElementById('total');
            totalElement.style.transform = 'scale(1.1)';
            setTimeout(() => {
                totalElement.style.transform = 'scale(1)';
            }, 300);
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const shippingSelected = document.querySelector('input[name="metode_pengiriman_id"]:checked');
            const paymentSelected = document.querySelector('input[name="metode_pembayaran_id"]:checked');
            
            if (!shippingSelected || !paymentSelected) {
                e.preventDefault();
                alert('Silakan pilih metode pengiriman dan pembayaran terlebih dahulu!');
                return false;
            }
            
            // Show loading
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.innerHTML = '⏳ Memproses...';
            submitBtn.disabled = true;
        });

        // Add floating effect to order summary
        document.addEventListener('DOMContentLoaded', function() {
            const orderSummary = document.querySelector('.order-summary');
            let startTime = null;
            
            function animate(timestamp) {
                if (!startTime) startTime = timestamp;
                const progress = timestamp - startTime;
                
                const y = Math.sin(progress * 0.002) * 5;
                orderSummary.style.transform = `translateY(${y}px)`;
                
                requestAnimationFrame(animate);
            }
            
            requestAnimationFrame(animate);
        });
    </script>
</body>
</html>