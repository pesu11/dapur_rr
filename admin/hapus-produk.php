<?php
session_start();
require_once '../config/database.php';

// Check admin login
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Pastikan ID ada dan valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'ID produk tidak valid'
    ];
    header('Location: produk.php');
    exit();
}

$id = intval($_GET['id']);
$conn = getConnection();

// Cek apakah produk ada
$checkStmt = $conn->prepare("SELECT nama_produk, gambar FROM produk WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Produk tidak ditemukan'
    ];
    header('Location: produk.php');
    exit();
}

$produk = $result->fetch_assoc();

// Hapus gambar jika ada
if (!empty($produk['gambar']) && file_exists("../uploads/products/" . $produk['gambar'])) {
    unlink("../uploads/products/" . $produk['gambar']);
}

// Hapus produk dari database
$deleteStmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Produk "' . htmlspecialchars($produk['nama_produk']) . '" berhasil dihapus'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Gagal menghapus produk: ' . $conn->error
    ];
}

$deleteStmt->close();
$conn->close();

header('Location: produk.php');
exit();
?>