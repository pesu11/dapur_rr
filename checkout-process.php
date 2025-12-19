<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $noTelepon = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    $catatan = trim($_POST['catatan'] ?? '');
    $metodePembayaranId = $_POST['metode_pembayaran_id'] ?? 0;
    $metodePengirimanId = $_POST['metode_pengiriman_id'] ?? 0;
    
    $errors = [];
    
    if (empty($nama) || empty($noTelepon) || empty($alamat)) {
        $errors[] = "Semua field wajib harus diisi";
    }
    
    if ($metodePembayaranId == 0 || $metodePengirimanId == 0) {
        $errors[] = "Pilih metode pembayaran dan pengiriman";
    }
    
    // Ambil data keranjang
    $keranjangQuery = "SELECT k.*, p.nama_produk, p.harga, p.is_promo, p.harga_promo, p.stok
                       FROM keranjang k
                       JOIN produk p ON k.produk_id = p.id
                       WHERE k.user_id = ?";
    $stmt = $conn->prepare($keranjangQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $keranjang = $stmt->get_result();
    
    if ($keranjang->num_rows === 0) {
        setFlashMessage('error', 'Keranjang belanja kosong');
        redirect('keranjang.php');
    }
    
    // Validasi stok dan hitung total
    $items = [];
    $totalHarga = 0;
    
    while($item = $keranjang->fetch_assoc()) {
        if ($item['stok'] < $item['jumlah']) {
            $errors[] = "Stok " . $item['nama_produk'] . " tidak mencukupi";
        }
        
        $hargaSatuan = $item['is_promo'] ? $item['harga_promo'] : $item['harga'];
        $subtotal = $hargaSatuan * $item['jumlah'];
        
        $items[] = [
            'produk_id' => $item['produk_id'],
            'nama_produk' => $item['nama_produk'],
            'harga' => $hargaSatuan,
            'jumlah' => $item['jumlah'],
            'subtotal' => $subtotal
        ];
        
        $totalHarga += $subtotal;
    }
    
    // Ambil ongkir
    $stmt = $conn->prepare("SELECT biaya FROM metode_pengiriman WHERE id = ?");
    $stmt->bind_param("i", $metodePengirimanId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pengiriman = $result->fetch_assoc();
    $ongkir = $pengiriman['biaya'];
    
    $totalBayar = $totalHarga + $ongkir;
    
    if (empty($errors)) {
        // Mulai transaksi
        $conn->begin_transaction();
        
        try {
            // Generate nomor pesanan
            $noPesanan = generateNoPesanan();
            
            // Insert pesanan
            $stmt = $conn->prepare("INSERT INTO pesanan (user_id, no_pesanan, total_harga, ongkir, total_bayar, metode_pembayaran_id, metode_pengiriman_id, alamat_pengiriman, no_telepon, catatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isddiiisss", $userId, $noPesanan, $totalHarga, $ongkir, $totalBayar, $metodePembayaranId, $metodePengirimanId, $alamat, $noTelepon, $catatan);
            $stmt->execute();
            
            $pesananId = $conn->insert_id;
            
            // Insert detail pesanan dan update stok
            foreach($items as $item) {
                // Insert detail
                $stmt = $conn->prepare("INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdid", $pesananId, $item['produk_id'], $item['nama_produk'], $item['harga'], $item['jumlah'], $item['subtotal']);
                $stmt->execute();
                
                // Update stok
                $stmt = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['jumlah'], $item['produk_id']);
                $stmt->execute();
            }
            
            // Hapus keranjang
            $stmt = $conn->prepare("DELETE FROM keranjang WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Commit transaksi
            $conn->commit();
            
            setFlashMessage('success', 'Pesanan berhasil dibuat! Nomor pesanan: ' . $noPesanan);
            redirect('payment.php?pesanan_id=' . $pesananId);
            
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Terjadi kesalahan saat membuat pesanan');
            redirect('checkout.php');
        }
    } else {
        foreach($errors as $error) {
            setFlashMessage('error', $error);
        }
        redirect('checkout.php');
    }
} else {
    redirect('checkout.php');
}
?>