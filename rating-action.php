<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('riwayat-pesanan.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];
$pesananId = $_POST['pesanan_id'] ?? 0;
$produkId = $_POST['produk_id'] ?? 0;
$rating = intval($_POST['rating'] ?? 0);
$ulasan = trim($_POST['ulasan'] ?? '');

// Validasi input
if ($rating < 1 || $rating > 5) {
    setFlashMessage('error', 'Rating tidak valid');
    redirect('beri-rating.php?pesanan_id=' . $pesananId . '&produk_id=' . $produkId);
}

if (strlen($ulasan) < 10) {
    setFlashMessage('error', 'Ulasan minimal 10 karakter');
    redirect('beri-rating.php?pesanan_id=' . $pesananId . '&produk_id=' . $produkId);
}

// Validasi pesanan
$query = "SELECT p.id FROM pesanan p
          JOIN detail_pesanan dp ON p.id = dp.pesanan_id
          WHERE p.id = ? AND p.user_id = ? AND p.status = 'selesai' AND dp.produk_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $pesananId, $userId, $produkId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    setFlashMessage('error', 'Pesanan tidak valid');
    redirect('riwayat-pesanan.php');
}

// Cek duplikasi
$checkRating = $conn->prepare("SELECT id FROM rating_produk WHERE user_id = ? AND produk_id = ? AND pesanan_id = ?");
$checkRating->bind_param("iii", $userId, $produkId, $pesananId);
$checkRating->execute();
if ($checkRating->get_result()->num_rows > 0) {
    setFlashMessage('error', 'Anda sudah memberikan rating untuk produk ini');
    redirect('detail-pesanan.php?id=' . $pesananId);
}

// Simpan rating
$insertStmt = $conn->prepare("INSERT INTO rating_produk (user_id, produk_id, pesanan_id, rating, ulasan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$insertStmt->bind_param("iiiis", $userId, $produkId, $pesananId, $rating, $ulasan);

if ($insertStmt->execute()) {
    setFlashMessage('success', 'Terima kasih! Rating dan ulasan Anda telah disimpan');
    redirect('detail-pesanan.php?id=' . $pesananId);
} else {
    setFlashMessage('error', 'Gagal menyimpan rating. Silakan coba lagi');
    redirect('beri-rating.php?pesanan_id=' . $pesananId . '&produk_id=' . $produkId);
}
?>