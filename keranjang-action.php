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
    $jumlah = intval($_POST['jumlah'] ?? 1);

    if ($action === 'add' && $produkId > 0) {
        // Cek stok produk
        $stmt = $conn->prepare("SELECT stok, nama_produk FROM produk WHERE id = ?");
        $stmt->bind_param("i", $produkId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['flash_error'] = 'Produk tidak ditemukan';
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
            exit;
        }

        $produk = $result->fetch_assoc();

        if ($produk['stok'] < $jumlah) {
            $_SESSION['flash_error'] = 'Stok tidak mencukupi';
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
            exit;
        }

        // Cek apakah sudah ada di keranjang
        $stmt = $conn->prepare("SELECT id, jumlah FROM keranjang WHERE user_id = ? AND produk_id = ?");
        $stmt->bind_param("ii", $userId, $produkId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update jumlah
            $row = $result->fetch_assoc();
            $newJumlah = $row['jumlah'] + $jumlah;

            if ($newJumlah > $produk['stok']) {
                $_SESSION['flash_error'] = 'Jumlah melebihi stok tersedia';
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
                exit;
            }

            $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
            $stmt->bind_param("ii", $newJumlah, $row['id']);
            $stmt->execute();

            $_SESSION['flash_success'] = 'Jumlah produk di keranjang diperbarui';
        } else {
            // Insert baru
            $stmt = $conn->prepare("INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $userId, $produkId, $jumlah);
            $stmt->execute();

            $_SESSION['flash_success'] = 'Produk berhasil ditambahkan ke keranjang';
        }

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
        exit;
    }

    if ($action === 'update' && $produkId > 0) {
        // Cek stok
        $stmt = $conn->prepare("SELECT stok FROM produk WHERE id = ?");
        $stmt->bind_param("i", $produkId);
        $stmt->execute();
        $result = $stmt->get_result();
        $produk = $result->fetch_assoc();

        if ($jumlah > $produk['stok']) {
            $_SESSION['flash_error'] = 'Jumlah melebihi stok tersedia';
            header("Location: keranjang.php");
            exit;
        }

        if ($jumlah <= 0) {
            // Hapus dari keranjang
            $stmt = $conn->prepare("DELETE FROM keranjang WHERE user_id = ? AND produk_id = ?");
            $stmt->bind_param("ii", $userId, $produkId);
            $stmt->execute();

            $_SESSION['flash_success'] = 'Produk dihapus dari keranjang';
        } else {
            // Update jumlah
            $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE user_id = ? AND produk_id = ?");
            $stmt->bind_param("iii", $jumlah, $userId, $produkId);
            $stmt->execute();

            $_SESSION['flash_success'] = 'Keranjang diperbarui';
        }

        header("Location: keranjang.php");
        exit;
    }

    if ($action === 'delete' && $produkId > 0) {
        $stmt = $conn->prepare("DELETE FROM keranjang WHERE user_id = ? AND produk_id = ?");
        $stmt->bind_param("ii", $userId, $produkId);
        $stmt->execute();

        $_SESSION['flash_success'] = 'Produk dihapus dari keranjang';
        header("Location: keranjang.php");
        exit;
    }
}

// Arahkan ke home jika tidak ada POST
header("Location: home.php");
exit;
?>
