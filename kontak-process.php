<?php
require_once 'config/database.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    
    $errors = [];
    
    // Validasi
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (empty($subjek)) {
        $errors[] = "Subjek harus diisi";
    }
    
    if (empty($pesan)) {
        $errors[] = "Pesan harus diisi";
    } elseif (strlen($pesan) < 10) {
        $errors[] = "Pesan minimal 10 karakter";
    }
    
    if (empty($errors)) {
        // Insert ke database
        $stmt = $conn->prepare("INSERT INTO kontak_masuk (nama, email, subjek, pesan, status) VALUES (?, ?, ?, ?, 'baru')");
        $stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Terima kasih! Pesan Anda telah berhasil dikirim. Kami akan segera menghubungi Anda.');
            redirect('kontak.php');
        } else {
            setFlashMessage('error', 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.');
            redirect('kontak.php');
        }
    } else {
        // Jika ada error validasi
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old'] = [
            'nama' => $nama,
            'email' => $email,
            'subjek' => $subjek,
            'pesan' => $pesan
        ];
        redirect('kontak.php');
    }
} else {
    // Jika akses langsung tanpa POST
    redirect('kontak.php');
}
?>