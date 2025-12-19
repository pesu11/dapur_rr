<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dapur_rr');

// Koneksi Database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    return $conn;
}

// Base URL
define('BASE_URL', 'http://localhost/dapur_rr/');

// Upload Path
define('UPLOAD_PATH', 'uploads/');

// Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function generateNoPesanan() {
    return 'DRR' . date('Ymd') . rand(1000, 9999);
}

function uploadFile($file, $folder = 'produk') {
    $targetDir = UPLOAD_PATH . $folder . '/';
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '.' . $fileExtension;
    $targetFile = $targetDir . $fileName;
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > 5000000) { // 5MB
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $folder . '/' . $fileName;
    }
    
    return false;
}

function getUserName() {
    return $_SESSION['nama'] ?? 'Guest';
}

function getCartCount() {
    if (!isLoggedIn()) return 0;
    
    $conn = getConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM keranjang WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}

function getWishlistCount() {
    if (!isLoggedIn()) return 0;
    
    $conn = getConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}
?>