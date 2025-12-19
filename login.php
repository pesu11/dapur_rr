<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('home.php');
    }
}

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Email dan password harus diisi";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                setFlashMessage('success', 'Login berhasil! Selamat datang ' . $user['nama']);
                
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('home.php');
                }
            } else {
                $errors[] = "Email atau password salah";
            }
        } else {
            $errors[] = "Email atau password salah";
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $pengaturan['nama_toko']; ?></title>
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
            --gradient-1: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            --gradient-2: linear-gradient(135deg, #FFB6D9 0%, #FF69B4 100%);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #FF69B4, #FF1493, #FFB6D9, #FFC1E3);
            background-size: 400% 400%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            animation: gradientShift 10s ease infinite;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* Floating shapes animation */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.08) 0%, transparent 40%);
            animation: floatShapes 20s linear infinite;
            z-index: -1;
        }

        @keyframes floatShapes {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }
        
        /* Subtle pulse animation */
        @keyframes subtlePulse {
            0%, 100% {
                opacity: 0.5;
            }
            50% {
                opacity: 0.8;
            }
        }

        body::after {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: subtlePulse 4s ease-in-out infinite;
            z-index: -1;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(255, 105, 180, 0.3);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 246, 249, 0.8);
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
            background: white;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 20, 147, 0.4);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(255, 82, 82, 0.1) 0%, rgba(255, 138, 138, 0.05) 100%);
            color: #dc2626;
            border: 2px solid rgba(220, 38, 38, 0.2);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(167, 243, 208, 0.05) 100%);
            color: #059669;
            border: 2px solid rgba(5, 150, 105, 0.2);
        }
        
        .text-center {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .text-center a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .text-center a:hover {
            color: var(--accent);
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(255, 105, 180, 0.1);
        }
        
        .back-link:hover {
            background: rgba(255, 105, 180, 0.2);
            transform: translateX(-5px);
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, rgba(255, 182, 217, 0.1) 0%, rgba(255, 105, 180, 0.05) 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .demo-credentials strong {
            color: var(--primary);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .login-container {
                padding: 2rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="index.php" class="back-link">← Kembali ke Beranda</a>
        
        <div class="logo">
            <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
            <p>Masuk ke Akun Anda</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="margin-left: 1.5rem;">
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="text-center">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
           
        </div>
    </div>

    <script>
        // Simple background interaction
        document.addEventListener('mousemove', function(e) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            document.body.style.backgroundPosition = `${x * 100}% ${y * 100}%`;
        });

        // Auto focus email field
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput) {
                setTimeout(() => {
                    emailInput.focus();
                }, 300);
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>