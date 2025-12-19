<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pesananId = $_GET['pesanan_id'] ?? 0;
$produkId = $_GET['produk_id'] ?? 0;

// Validasi pesanan dan produk
$query = "SELECT p.*, dp.produk_id, dp.nama_produk 
          FROM pesanan p
          JOIN detail_pesanan dp ON p.id = dp.pesanan_id
          WHERE p.id = ? AND p.user_id = ? AND p.status = 'selesai' AND dp.produk_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $pesananId, $userId, $produkId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Pesanan tidak ditemukan atau belum selesai');
    redirect('riwayat-pesanan.php');
}

$data = $result->fetch_assoc();

// Cek apakah sudah pernah memberi rating
$checkRating = $conn->prepare("SELECT id FROM rating_produk WHERE user_id = ? AND produk_id = ? AND pesanan_id = ?");
$checkRating->bind_param("iii", $userId, $produkId, $pesananId);
$checkRating->execute();
if ($checkRating->get_result()->num_rows > 0) {
    setFlashMessage('error', 'Anda sudah memberikan rating untuk produk ini');
    redirect('detail-pesanan.php?id=' . $pesananId);
}

$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Rating - <?php echo $pengaturan['nama_toko']; ?></title>
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
            background: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--dark);
        }
        
        .rating-container {
            background: white;
            border-radius: 30px;
            padding: 3.5rem;
            max-width: 650px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            border: 3px solid rgba(255, 105, 180, 0.2);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
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
        
        .rating-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="stars" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M10 0 L13 7 L20 7 L14 11 L16 18 L10 13 L4 18 L6 11 L0 7 L7 7 Z" fill="%23FFB6D9" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23stars)"/></svg>');
            opacity: 0.1;
            z-index: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.8rem;
            letter-spacing: -0.5px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .product-info {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            text-align: center;
            border: 3px solid rgba(255, 105, 180, 0.3);
            position: relative;
            z-index: 1;
            box-shadow: var(--shadow-sm);
        }
        
        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .product-info::before {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 50%;
            border: 3px solid rgba(255, 105, 180, 0.3);
        }
        
        .form-group {
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .form-label {
            display: block;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--dark);
            font-size: 1.2rem;
            text-align: center;
        }
        
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 3.5rem;
            margin-bottom: 0.8rem;
        }
        
        .star {
            cursor: pointer;
            color: #FFD1DC;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .star:hover,
        .star.active {
            color: #FFD700;
            transform: scale(1.2) rotate(5deg);
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .rating-text {
            text-align: center;
            color: var(--accent);
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 0.8rem;
            padding: 0.8rem;
            background: rgba(255, 182, 217, 0.2);
            border-radius: 15px;
            border: 2px solid rgba(255, 105, 180, 0.3);
        }
        
        .form-control {
            width: 100%;
            padding: 1.5rem;
            border: 3px solid rgba(255, 105, 180, 0.3);
            border-radius: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            resize: vertical;
            min-height: 160px;
            background: rgba(255, 182, 217, 0.1);
            transition: all 0.3s ease;
            color: var(--dark);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
            background: white;
        }
        
        .form-control::placeholder {
            color: #999;
        }
        
        .char-count {
            text-align: right;
            color: #999;
            font-size: 0.9rem;
            margin-top: 0.8rem;
            font-weight: 500;
        }
        
        .btn-group {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .btn {
            flex: 1;
            padding: 1.2rem;
            border: none;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn::before {
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
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.4);
        }
        
        .btn-primary:disabled {
            background: linear-gradient(135deg, #CCCCCC 0%, #999999 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-secondary {
            background: white;
            color: var(--accent);
            border: 3px solid var(--accent);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .alert {
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            animation: slideInDown 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            z-index: 1;
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
        
        .floating-stars {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.3;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-stars:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .floating-stars:nth-child(2) { top: 20%; right: 15%; animation-delay: 1s; }
        .floating-stars:nth-child(3) { bottom: 30%; left: 20%; animation-delay: 2s; }
        .floating-stars:nth-child(4) { bottom: 15%; right: 25%; animation-delay: 3s; }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }
        
        @media (max-width: 768px) {
            .rating-container {
                padding: 2.5rem 1.5rem;
                margin: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .star-rating {
                font-size: 2.8rem;
                gap: 0.7rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            body {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .rating-container {
                padding: 2rem 1.2rem;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .star-rating {
                font-size: 2.2rem;
            }
            
            .product-name {
                font-size: 1.3rem;
            }
            
            .form-control {
                padding: 1.2rem;
                min-height: 140px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating decorative stars -->
    <div class="floating-stars">⭐</div>
    <div class="floating-stars">⭐</div>
    <div class="floating-stars">⭐</div>
    <div class="floating-stars">⭐</div>
    
    <div class="rating-container">
        <div class="header">
            <h1>⭐ Beri Rating & Ulasan</h1>
            <p>Bagikan pengalaman Anda dengan produk ini</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '🎉' : '⚠️'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="product-info">
            <div class="product-name"><?php echo htmlspecialchars($data['nama_produk']); ?></div>
        </div>

        <form method="POST" action="rating-action.php" id="ratingForm">
            <input type="hidden" name="pesanan_id" value="<?php echo $pesananId; ?>">
            <input type="hidden" name="produk_id" value="<?php echo $produkId; ?>">
            <input type="hidden" name="rating" id="ratingValue" value="0">
            
            <div class="form-group">
                <label class="form-label">Rating Bintang:</label>
                <div class="star-rating" id="starRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <div class="rating-text" id="ratingText">Pilih rating Anda</div>
            </div>

            <div class="form-group">
                <label class="form-label">Ulasan Anda:</label>
                <textarea name="ulasan" id="ulasan" class="form-control" 
                          placeholder="Ceritakan pengalaman Anda dengan produk ini..." 
                          maxlength="500" required></textarea>
                <div class="char-count">
                    <span id="charCount">0</span>/500 karakter
                </div>
            </div>

            <div class="btn-group">
                <a href="detail-pesanan.php?id=<?php echo $pesananId; ?>" class="btn btn-secondary">
                    <span style="font-size: 1.3rem;"></span> Batal
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <span style="font-size: 1.3rem;"></span> Kirim Rating
                </button>
            </div>
        </form>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');
        const ulasanInput = document.getElementById('ulasan');
        const charCount = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');
        
        const ratingLabels = {
            1: '⭐ Sangat Buruk',
            2: '⭐⭐ Buruk',
            3: '⭐⭐⭐ Cukup',
            4: '⭐⭐⭐⭐ Bagus',
            5: '⭐⭐⭐⭐⭐ Sangat Bagus'
        };
        
        let selectedRating = 0;
        
        // Star rating interaction
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                ratingValue.value = selectedRating;
                updateStars(selectedRating);
                ratingText.textContent = ratingLabels[selectedRating];
                checkFormValidity();
                
                // Animation feedback
                this.style.animation = 'bounce 0.3s ease';
                setTimeout(() => {
                    this.style.animation = '';
                }, 300);
            });
            
            star.addEventListener('mouseenter', function() {
                if (selectedRating === 0) {
                    updateStars(parseInt(this.dataset.rating));
                }
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            updateStars(selectedRating);
        });
        
        function updateStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Character counter
        ulasanInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // Color change based on length
            if (this.value.length >= 450) {
                charCount.style.color = '#FF1493';
                charCount.style.fontWeight = 'bold';
            } else if (this.value.length >= 400) {
                charCount.style.color = '#FF69B4';
            } else {
                charCount.style.color = '#999';
                charCount.style.fontWeight = 'normal';
            }
            
            checkFormValidity();
        });
        
        // Form validation
        function checkFormValidity() {
            const hasRating = selectedRating > 0;
            const hasUlasan = ulasanInput.value.trim().length >= 10;
            submitBtn.disabled = !(hasRating && hasUlasan);
            
            // Visual feedback for submit button
            if (submitBtn.disabled) {
                submitBtn.innerHTML = '<span style="font-size: 1.3rem;">⭐</span> Kirim Rating';
            } else {
                submitBtn.innerHTML = '<span style="font-size: 1.3rem;"></span> Kirim Rating';
            }
        }
        
        // Form submission animation
        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            if (selectedRating === 0) {
                e.preventDefault();
                showMessage('Silakan pilih rating bintang terlebih dahulu', 'error');
                return false;
            }
            
            if (ulasanInput.value.trim().length < 10) {
                e.preventDefault();
                showMessage('Ulasan minimal 10 karakter', 'error');
                return false;
            }
            
            // Loading animation
            submitBtn.innerHTML = '<span style="font-size: 1.3rem;">⏳</span> Mengirim...';
            submitBtn.disabled = true;
        });
        
        function showMessage(message, type) {
            const alertBox = document.createElement('div');
            alertBox.className = `alert alert-${type}`;
            alertBox.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                max-width: 400px;
                z-index: 1000;
                animation: slideInRight 0.3s ease;
            `;
            
            alertBox.innerHTML = `
                <span style="font-size: 1.5rem;">${type === 'error' ? '⚠️' : '🎉'}</span>
                ${message}
            `;
            
            document.body.appendChild(alertBox);
            
            setTimeout(() => {
                alertBox.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => alertBox.remove(), 300);
            }, 3000);
            
            // Add CSS for animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes slideOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Add bounce animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 100% {
                    transform: scale(1.2) rotate(5deg);
                }
                50% {
                    transform: scale(1.4) rotate(-5deg);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>