<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesananId = $_POST['pesanan_id'] ?? 0;
    
    // Validasi pesanan
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pesananId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        setFlashMessage('error', 'Pesanan tidak ditemukan');
        redirect('riwayat-pesanan.php');
    }
    
    $pesanan = $result->fetch_assoc();
    
    // Validasi file
    if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
        setFlashMessage('error', 'File bukti pembayaran harus diupload');
        redirect('payment.php?pesanan_id=' . $pesananId);
    }
    
    // Upload file
    $buktiPath = uploadFile($_FILES['bukti_pembayaran'], 'bukti_pembayaran');
    
    if (!$buktiPath) {
        setFlashMessage('error', 'Gagal upload bukti pembayaran. Pastikan file JPG/PNG dan maksimal 5MB');
        redirect('payment.php?pesanan_id=' . $pesananId);
    }
    
    // Update pesanan
    $tanggalBayar = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE pesanan SET bukti_pembayaran = ?, tanggal_pembayaran = ?, status = 'dibayar' WHERE id = ?");
    $stmt->bind_param("ssi", $buktiPath, $tanggalBayar, $pesananId);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Bukti pembayaran berhasil diupload! Pesanan Anda akan segera diproses.');
        redirect('detail-pesanan.php?id=' . $pesananId);
    } else {
        setFlashMessage('error', 'Terjadi kesalahan saat upload bukti pembayaran');
        redirect('payment.php?pesanan_id=' . $pesananId);
    }
} else {
    redirect('riwayat-pesanan.php');
}
?>