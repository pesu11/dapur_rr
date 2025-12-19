<?php
require_once 'config/database.php';

// Hapus semua session
session_destroy();

// Redirect ke index dengan pesan
session_start();
setFlashMessage('success', 'Anda telah berhasil logout');
redirect('index.php');
?>