<?php
require_once 'config/database.php';

$conn = getConnection();
$pengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

$error = '';
$success = '';
$show_form = true;

// Tangkap token dari URL
$token = $_GET['token'] ?? '';

// Validasi token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $reset_data = $result->fetch_assoc();
        $user_email = $reset_data['email'];
        
        // Proses reset password jika form disubmit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($password) || empty($confirm_password)) {
                $error = "Password dan konfirmasi password harus diisi";
            } elseif (strlen($password) < 6) {
                $error = "Password minimal 6 karakter";
            } elseif ($password !== $confirm_password) {
                $error = "Password dan konfirmasi password tidak cocok";
            } else {
                // Hash password baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password user
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $user_email);
                
                if ($stmt->execute()) {
                    // Tandai token sudah digunakan
                    $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    
                    $success = "Password berhasil direset! Silakan login dengan password baru Anda.";
                    $show_form = false;
                } else {
                    $error = "Terjadi kesalahan. Silakan coba lagi.";
                }
            }
        }
    } else {
        $error = "Token tidak valid atau sudah kedaluwarsa. Silakan minta reset password kembali.";
        $show_form = false;
    }
} else {
    $error = "Token tidak ditemukan.";
    $show_form = false;
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo $pengaturan['nama_toko']; ?></title>
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
        
        .reset-container {
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
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-1);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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
            margin-bottom: 1rem;
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
            margin-top: 0.5rem;
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
        
        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 197, 253, 0.05) 100%);
            color: #1d4ed8;
            border: 2px solid rgba(59, 130, 246, 0.2);
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(255, 105, 180, 0.1);
        }
        
        .text-center a:hover {
            background: rgba(255, 105, 180, 0.2);
            transform: translateY(-2px);
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

        .password-requirements {
            background: linear-gradient(135deg, rgba(255, 182, 217, 0.1) 0%, rgba(255, 105, 180, 0.05) 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 2px solid rgba(255, 105, 180, 0.1);
        }
        
        .password-requirements h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-requirements ul {
            margin-left: 1.5rem;
            color: #666;
        }
        
        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .reset-container {
                padding: 2rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <a href="login.php" class="back-link">← Kembali ke Login</a>
        
        <div class="logo">
            <div class="logo-icon">
                <span>🔒</span>
            </div>
            <h1>Reset Password</h1>
            <p>Buat password baru untuk akun Anda</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <span style="font-size: 1.2rem;"><?php echo $flash['type'] === 'success' ? '✓' : '⚠'; ?></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <span style="font-size: 1.2rem;">⚠</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <span style="font-size: 1.2rem;">✓</span>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
        <div class="password-requirements">
            <h3><span>📋</span> Syarat Password:</h3>
            <ul>
                <li>Minimal 6 karakter</li>
                <li>Sebaiknya kombinasi huruf dan angka</li>
                <li>Password harus sama dengan konfirmasi</li>
            </ul>
        </div>

        <form method="POST" id="resetForm">
            <div class="form-group">
                <label for="password">Password Baru</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Masukkan password baru"
                    autocomplete="new-password"
                    autofocus>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Ulangi password baru"
                    autocomplete="new-password">
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Reset Password</span>
                <span id="btnLoading" style="display: none;">⏳ Memproses...</span>
            </button>
        </form>
        <?php endif; ?>

        <div class="text-center">
            <?php if ($success): ?>
                <p><a href="login.php">🎉 Login dengan password baru</a></p>
            <?php else: ?>
                <p><a href="forgot-password.php">↶ Minta reset password baru</a></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus pada password field
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                setTimeout(() => {
                    passwordInput.focus();
                }, 300);
            }

            // Form submission dengan loading state
            const resetForm = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    // Validasi client-side
                    if (password.length < 6) {
                        e.preventDefault();
                        showError('Password minimal 6 karakter');
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        showError('Password dan konfirmasi password tidak cocok');
                        return false;
                    }
                    
                    // Loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                });
            }

            // Show password strength indicator
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('confirm_password');
            
            if (passwordField && confirmField) {
                passwordField.addEventListener('input', checkPasswordStrength);
                confirmField.addEventListener('input', checkPasswordMatch);
            }

            function checkPasswordStrength() {
                const password = passwordField.value;
                const requirementBox = document.querySelector('.password-requirements');
                
                if (!requirementBox) return;
                
                let strengthText = '';
                let strengthColor = '#666';
                
                if (password.length === 0) {
                    strengthText = '';
                } else if (password.length < 6) {
                    strengthText = 'Lemah (minimal 6 karakter)';
                    strengthColor = '#dc2626';
                } else if (password.length < 8) {
                    strengthText = 'Sedang';
                    strengthColor = '#f59e0b';
                } else if (/[A-Z]/.test(password) && /[0-9]/.test(password)) {
                    strengthText = 'Kuat ✓';
                    strengthColor = '#059669';
                } else {
                    strengthText = 'Cukup';
                    strengthColor = '#3b82f6';
                }
                
                // Update strength indicator
                let indicator = requirementBox.querySelector('.strength-indicator');
                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.className = 'strength-indicator';
                    indicator.style.marginTop = '0.5rem';
                    indicator.style.fontWeight = '600';
                    requirementBox.appendChild(indicator);
                }
                
                if (strengthText) {
                    indicator.innerHTML = `Kekuatan password: <span style="color: ${strengthColor}">${strengthText}</span>`;
                    indicator.style.display = 'block';
                } else {
                    indicator.style.display = 'none';
                }
            }

            function checkPasswordMatch() {
                const password = passwordField.value;
                const confirmPassword = confirmField.value;
                const requirementBox = document.querySelector('.password-requirements');
                
                if (!requirementBox || !password || !confirmPassword) return;
                
                let matchIndicator = requirementBox.querySelector('.match-indicator');
                if (!matchIndicator) {
                    matchIndicator = document.createElement('div');
                    matchIndicator.className = 'match-indicator';
                    matchIndicator.style.marginTop = '0.5rem';
                    matchIndicator.style.fontWeight = '600';
                    requirementBox.appendChild(matchIndicator);
                }
                
                if (password === confirmPassword) {
                    matchIndicator.innerHTML = '✓ Password cocok';
                    matchIndicator.style.color = '#059669';
                } else {
                    matchIndicator.innerHTML = '✗ Password tidak cocok';
                    matchIndicator.style.color = '#dc2626';
                }
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
                const form = document.getElementById('resetForm');
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
        });
    </script>
</body>
</html>