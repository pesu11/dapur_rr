<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

// Ambil riwayat pesanan dengan status pengembalian
$query = "SELECT p.*, 
          mp.nama_metode as metode_pembayaran, 
          mg.nama_metode as metode_pengiriman,
          pg.status as status_pengembalian,
          pg.id as pengembalian_id
          FROM pesanan p
          LEFT JOIN metode_pembayaran mp ON p.metode_pembayaran_id = mp.id
          LEFT JOIN metode_pengiriman mg ON p.metode_pengiriman_id = mg.id
          LEFT JOIN pengembalian pg ON p.id = pg.pesanan_id
          WHERE p.user_id = ?
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$pesanan = $stmt->get_result();

$flash = getFlashMessage();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - <?php echo $pengaturan['nama_toko']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* KEEP ALL EXISTING STYLES FROM riwayat-pesanan.php */
        /* ADD NEW STYLES FOR RETURN STATUS */
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

        .btn {
            padding: 0.85rem 2rem;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
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
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.3);
        }

       .user-menu {
    display: flex;
    align-items: center;
    gap: 1.5rem;
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
/* Tambahkan CSS untuk btn-logout */
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

        /* STYLING KONTEN RIWAYAT PESANAN */
        .container {
            max-width: 1200px;
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
            margin-bottom: 2rem;
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
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 1rem 2rem;
            background: white;
            border: 2px solid rgba(255, 105, 180, 0.2);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: var(--dark);
            box-shadow: var(--shadow-sm);
        }
        
        .tab:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        
        .tab.active {
            background: var(--gradient-1);
            color: white;
            border-color: var(--accent);
            box-shadow: var(--shadow-md);
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .order-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(255, 105, 180, 0.2);
        }
        
        .order-number {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .order-date {
            color: #666;
            font-size: 1rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .order-status {
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: var(--shadow-sm);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }
        
        .status-dibayar {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            color: var(--info);
            border: 2px solid #93C5FD;
        }
        
        .status-diproses {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .status-dikirim {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
            color: #0EA5E9;
            border: 2px solid #7DD3FC;
        }
        
        .status-selesai {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }
        
        .status-dibatalkan {
            background: linear-gradient(135deg, #FEF2F2 0%, #FECACA 100%);
            color: #DC2626;
            border: 2px solid #FCA5A5;
        }
        
        .order-body {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2.5rem;
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .info-row {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
        }
        
        .info-label {
            font-weight: 700;
            color: var(--dark);
            min-width: 140px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-value {
            color: var(--dark);
            font-weight: 500;
        }
        
        .order-total {
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .total-label {
            color: #666;
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .total-amount {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--accent);
            text-shadow: 2px 4px 10px rgba(255, 20, 147, 0.3);
        }
        
        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        .btn-detail {
            padding: 1rem 2rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-detail:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-upload {
            padding: 1rem 2rem;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-upload:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        
        .btn-track {
            padding: 1rem 2rem;
            background: var(--info);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        .btn-track:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-return {
            padding: 1rem 2rem;
            background: #FFD700;
            color: var(--dark);
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        .btn-return:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            background: #FFC107;
        }
        
        .empty-state {
            background: white;
            padding: 4rem 2rem;
            border-radius: 25px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
        }

        .empty-state h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
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

            .order-body {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .order-actions a, .order-actions button {
                width: 100%;
                text-align: center;
                justify-content: center;
            }

            .info-row {
                flex-direction: column;
                gap: 0.5rem;
            }

            .info-label {
                min-width: auto;
            }

            .filter-tabs {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2.2rem;
            }

            .order-card {
                padding: 2rem 1.5rem;
            }

            .total-amount {
                font-size: 1.8rem;
            }

            .tab {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }
        .return-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-top: 0.8rem;
            box-shadow: var(--shadow-sm);
            animation: fadeIn 0.5s ease;
           
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .return-status-pending {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5EC 100%);
            color: var(--accent);
            border: 2px solid var(--secondary);
        }

        .return-status-disetujui {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            color: var(--info);
            border: 2px solid #93C5FD;
        }

        .return-status-ditolak {
            background: linear-gradient(135deg, #FEF2F2 0%, #FECACA 100%);
            color: var(--danger);
            border: 2px solid #FCA5A5;
        }

        .return-status-selesai {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            color: var(--success);
            border: 2px solid #86EFAC;
        }

        .return-action-btn {
            padding: 1rem 2rem;
            background: #FFD700;
            color: var(--dark);
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            margin-top: 0.5rem;
        }

        .return-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            background: #FFC107;
        }

        .return-action-btn.disabled {
            background: #E5E7EB;
            color: #6B7280;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .return-action-btn.disabled:hover {
            transform: none;
            box-shadow: var(--shadow-sm);
            background: #E5E7EB;
        }

        .order-status-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
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
                echo '<div class="logo-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #FF6B9D 0%, #FFC93C 100%); font-size: 2rem;"></div>';
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
                <li><a href="riwayat-pesanan.php" class="active">Pesanan</a></li>
                <li><a href="promo.php">Promo</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
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
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </div>
</header>

    <div class="container">
        <h1 class="page-title">Riwayat Pesanan</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.5rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="filter-tabs">
            <div class="tab active" data-status="all">Semua</div>
            <div class="tab" data-status="pending">Menunggu Pembayaran</div>
            <div class="tab" data-status="dibayar">Sudah Dibayar</div>
            <div class="tab" data-status="diproses">Diproses</div>
            <div class="tab" data-status="dikirim">Dikirim</div>
            <div class="tab" data-status="selesai">Selesai</div>
        </div>

        <?php if ($pesanan->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-icon"></div>
            <h2>Belum Ada Pesanan</h2>
            <p>Anda belum memiliki riwayat pesanan</p>
            <a href="produk.php" class="btn btn-primary" style="margin-top: 2rem;">🛍️ Mulai Belanja</a>
        </div>
        <?php else: ?>
        <div class="orders-list">
            <?php while($order = $pesanan->fetch_assoc()): 
                $statusPengembalian = $order['status_pengembalian'] ?? null;
                $pengembalianId = $order['pengembalian_id'] ?? null;
                
                // Text untuk status pengembalian
                $statusPengembalianText = '';
                $statusPengembalianClass = '';
                if ($statusPengembalian) {
                    $statusTexts = [
                        'pending' => ['text' => 'Pengembalian Diproses', 'class' => 'return-status-pending'],
                        'disetujui' => ['text' => 'Pengembalian Disetujui', 'class' => 'return-status-disetujui'],
                        'ditolak' => ['text' => 'Pengembalian Ditolak', 'class' => 'return-status-ditolak'],
                        'selesai' => ['text' => 'Pengembalian Selesai', 'class' => 'return-status-selesai']
                    ];
                    $statusPengembalianText = $statusTexts[$statusPengembalian]['text'] ?? '';
                    $statusPengembalianClass = $statusTexts[$statusPengembalian]['class'] ?? '';
                }
                
                // Tentukan apakah bisa mengajukan pengembalian
                $canReturn = ($order['status'] === 'dikirim' || $order['status'] === 'selesai') && !$statusPengembalian;
            ?>
            <div class="order-card" data-status="<?php echo $order['status']; ?>">
                <div class="order-header">
                    <div>
                        <div class="order-number">Pesanan #<?php echo htmlspecialchars($order['no_pesanan']); ?></div>
                        <div class="order-date"><?php echo date('d F Y H:i', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-status-container">
                        <div class="order-status status-<?php echo $order['status']; ?>">
                            <?php 
                            $status = $order['status'] ?? '';
                            $statusText = [
                                'pending' => 'Menunggu Pembayaran',
                                'dibayar' => 'Sudah Dibayar',
                                'diproses' => ' Sedang Diproses',
                                'dikirim' => 'Sedang Dikirim',
                                'selesai' => 'Selesai',
                                'dibatalkan' => 'Dibatalkan'
                            ];
                            echo $statusText[$status] ?? 'Status Tidak Diketahui';
                            ?>
                        </div>
                        
                        <?php if ($statusPengembalian): ?>
                        <div class="return-status-badge <?php echo $statusPengembalianClass; ?>">
                            <?php echo $statusPengembalianText; ?>
                            <?php if ($pengembalianId): ?>
                            <a href="detail-pengembalian.php?id=<?php echo $pengembalianId; ?>" 
                               style="margin-left: 0.5rem; color: inherit; text-decoration: none;" 
                               title="Lihat Detail Pengembalian">
                                Lihat detail
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-body">
                    <div class="order-info">
                        <div class="info-row">
                            <span class="info-label">Pembayaran:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['metode_pembayaran']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Pengiriman:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['metode_pengiriman']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Alamat:</span>
                            <span class="info-value"><?php echo htmlspecialchars(substr($order['alamat_pengiriman'], 0, 50)); ?>...</span>
                        </div>
                        <?php if ($order['resi_pengiriman']): ?>
                        <div class="info-row">
                            <span class="info-label">No. Resi:</span>
                            <span class="info-value"><strong><?php echo htmlspecialchars($order['resi_pengiriman']); ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-total">
                        <div class="total-label">Total Pembayaran</div>
                        <div class="total-amount"><?php echo formatRupiah($order['total_bayar']); ?></div>
                    </div>
                </div>

                <div class="order-actions">
                    <a href="detail-pesanan.php?id=<?php echo $order['id']; ?>" class="btn-detail">Lihat Detail</a>
                    
                    <?php if ($order['status'] === 'pending'): ?>
                    <a href="payment.php?pesanan_id=<?php echo $order['id']; ?>" class="btn-upload">Bayar Sekarang</a>
                    <?php endif; ?>
                    
                    

                    <?php if ($canReturn): ?>
                    <a href="pengembalian.php" class="return-action-btn">Ajukan Pengembalian</a>
                    <?php elseif (!$statusPengembalian && ($order['status'] === 'dikirim' || $order['status'] === 'selesai')): ?>
                    <button class="return-action-btn disabled" disabled>Ajukan Pengembalian</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const orders = document.querySelectorAll('.order-card');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const status = this.dataset.status;
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter orders
                orders.forEach(order => {
                    if (status === 'all' || order.dataset.status === status) {
                        order.style.display = 'block';
                    } else {
                        order.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>