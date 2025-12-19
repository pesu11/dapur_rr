<?php
require_once 'config/database.php';
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = getConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $produkId = intval($_POST['produk_id'] ?? 0);

    if ($produkId > 0) {

        if ($action === 'add') {
            // Cek apakah produk sudah ada di wishlist
            $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND produk_id = ?");
            $stmt->bind_param("ii", $userId, $produkId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['flash_error'] = 'Produk sudah ada di wishlist';
            } else {
                $stmt = $conn->prepare("INSERT INTO wishlist (user_id, produk_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $produkId);
                $stmt->execute();
                $_SESSION['flash_success'] = 'Produk berhasil ditambahkan ke wishlist';
            }

            // Redirect kembali ke halaman sebelumnya
            $referer = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';
            header("Location: $referer");
            exit;
        }

        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND produk_id = ?");
            $stmt->bind_param("ii", $userId, $produkId);
            $stmt->execute();
            $_SESSION['flash_success'] = 'Produk dihapus dari wishlist';

            header("Location: wishlist.php");
            exit;
        }
    }
}

// Jika tidak melalui POST
header("Location: wishlist.php");
exit;
?>
