<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('home.php');
}

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $no_telepon = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    if ($password !== $konfirmasi_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    // Cek email sudah terdaftar
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email sudah terdaftar";
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, no_telepon, alamat, role) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->bind_param("sssss", $nama, $email, $hashedPassword, $no_telepon, $alamat);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Registrasi berhasil! Silakan login.');
            redirect('login.php');
        } else {
            $errors[] = "Terjadi kesalahan saat registrasi";
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
    <title>Daftar Akun - <?php echo $pengaturan['nama_toko']; ?></title>
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
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(255, 105, 180, 0.3);
            width: 100%;
            max-width: 500px;
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
        
        input, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 246, 249, 0.8);
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
            background: white;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
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
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            animation: fadeIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
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
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 105, 180, 0.1);
        }
        
        .text-center a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .text-center a:hover {
            color: var(--accent);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .strength-bar {
            flex: 1;
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-weak .strength-fill {
            background: #dc2626;
            width: 33%;
        }

        .strength-medium .strength-fill {
            background: #f59e0b;
            width: 66%;
        }

        .strength-strong .strength-fill {
            background: #059669;
            width: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .register-container {
                padding: 2rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="index.php" class="back-link">← Kembali ke Beranda</a>
        
        <div class="logo">
            <h1><?php echo htmlspecialchars($pengaturan['nama_toko']); ?></h1>
            <p>Daftar Akun Baru</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.2rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span style="font-size: 1.2rem;">⚠</span>
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="nama">Nama Lengkap *</label>
                <input type="text" id="nama" name="nama" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="no_telepon">No. Telepon</label>
                <input type="text" id="no_telepon" name="no_telepon" value="<?php echo isset($_POST['no_telepon']) ? htmlspecialchars($_POST['no_telepon']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="password">Password * (minimal 6 karakter)</label>
                <input type="password" id="password" name="password" required>
                <div class="password-strength" id="passwordStrength">
                    <span>Kekuatan:</span>
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <span class="strength-text" style="font-size: 0.8rem; color: #666;"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password *</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                <div id="passwordMatch" style="margin-top: 0.5rem; font-size: 0.9rem;"></div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Daftar</span>
                <span id="btnLoading" style="display: none;">⏳ Memproses...</span>
            </button>
        </form>

        <div class="text-center">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus pada nama field
            const namaInput = document.getElementById('nama');
            if (namaInput && !namaInput.value) {
                setTimeout(() => {
                    namaInput.focus();
                }, 300);
            }

            // Password strength checker
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('konfirmasi_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthBar = passwordStrength.querySelector('.strength-bar');
            const strengthFill = strengthBar.querySelector('.strength-fill');
            const strengthText = passwordStrength.querySelector('.strength-text');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let text = '';
                    let color = '';
                    
                    if (password.length === 0) {
                        text = '';
                        color = '#666';
                        strengthBar.className = 'strength-bar';
                    } else if (password.length < 6) {
                        text = 'Lemah';
                        color = '#dc2626';
                        strengthBar.className = 'strength-bar strength-weak';
                    } else if (password.length < 8) {
                        text = 'Cukup';
                        color = '#f59e0b';
                        strengthBar.className = 'strength-bar strength-medium';
                    } else {
                        // Check for complexity
                        let complexity = 0;
                        if (/[A-Z]/.test(password)) complexity++;
                        if (/[0-9]/.test(password)) complexity++;
                        if (/[^A-Za-z0-9]/.test(password)) complexity++;
                        
                        if (complexity >= 2) {
                            text = 'Kuat ✓';
                            color = '#059669';
                            strengthBar.className = 'strength-bar strength-strong';
                        } else {
                            text = 'Sedang';
                            color = '#3b82f6';
                            strengthBar.className = 'strength-bar strength-medium';
                        }
                    }
                    
                    strengthText.textContent = text;
                    strengthText.style.color = color;
                });
            }

            // Password match checker
            if (passwordInput && confirmPasswordInput) {
                function checkPasswordMatch() {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const matchDiv = document.getElementById('passwordMatch');
                    
                    if (!password || !confirmPassword) {
                        matchDiv.innerHTML = '';
                        return;
                    }
                    
                    if (password === confirmPassword) {
                        matchDiv.innerHTML = '✓ Password cocok';
                        matchDiv.style.color = '#059669';
                    } else {
                        matchDiv.innerHTML = '✗ Password tidak cocok';
                        matchDiv.style.color = '#dc2626';
                    }
                }
                
                passwordInput.addEventListener('input', checkPasswordMatch);
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            // Form submission with loading state
            const registerForm = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('konfirmasi_password').value;
                    
                    // Client-side validation
                    if (password.length < 6) {
                        e.preventDefault();
                        showError('Password minimal 6 karakter');
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        showError('Konfirmasi password tidak cocok');
                        return false;
                    }
                    
                    // Loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                });
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

            function showError(message) {
                // Hapus alert error sebelumnya
                const oldAlert = document.querySelector('.alert-error');
                if (oldAlert) {
                    oldAlert.remove();
                }
                
                // Buat alert error baru
                const alert = document.createElement('div');
                alert.className = 'alert alert-error';
                alert.innerHTML = `
                    <span style="font-size: 1.2rem;">⚠</span>
                    ${message}
                `;
                
                // Tambahkan sebelum form
                const form = document.getElementById('registerForm');
                if (form) {
                    form.parentNode.insertBefore(alert, form);
                }
                
                // Auto remove setelah 5 detik
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-10px)';
                        alert.style.transition = 'all 0.5s ease';
                        
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 500);
                    }
                }, 5000);
            }

            // Add subtle animation to form inputs on focus
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(255, 105, 180, 0.1)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });

        // Simple background interaction
        document.addEventListener('mousemove', function(e) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            document.body.style.backgroundPosition = `${x * 100}% ${y * 100}%`;
        });
    </script>
</body>
</html>