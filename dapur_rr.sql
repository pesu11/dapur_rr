-- Database: dapur_rr
CREATE DATABASE IF NOT EXISTS dapur_rr;
USE dapur_rr;

-- Tabel Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kategori
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Produk
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(200) NOT NULL,
    kategori_id INT,
    deskripsi TEXT,
    harga DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    gambar VARCHAR(255),
    berat INT DEFAULT 500,
    is_promo BOOLEAN DEFAULT FALSE,
    harga_promo DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- Tabel Keranjang
CREATE TABLE keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- Tabel Wishlist
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    produk_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- Tabel Metode Pembayaran
CREATE TABLE metode_pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_metode VARCHAR(100) NOT NULL,
    no_rekening VARCHAR(100),
    atas_nama VARCHAR(100),
    deskripsi TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Metode Pengiriman
CREATE TABLE metode_pengiriman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_metode VARCHAR(100) NOT NULL,
    biaya DECIMAL(10,2) NOT NULL,
    estimasi VARCHAR(50),
    deskripsi TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Pesanan
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    no_pesanan VARCHAR(50) UNIQUE NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    ongkir DECIMAL(10,2) DEFAULT 0,
    total_bayar DECIMAL(10,2) NOT NULL,
    metode_pembayaran_id INT,
    metode_pengiriman_id INT,
    alamat_pengiriman TEXT NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    catatan TEXT,
    status ENUM('pending', 'dibayar', 'diproses', 'dikirim', 'selesai', 'dibatalkan') DEFAULT 'pending',
    bukti_pembayaran VARCHAR(255),
    resi_pengiriman VARCHAR(100),
    tanggal_pembayaran DATETIME,
    tanggal_kirim DATETIME,
    tanggal_selesai DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (metode_pembayaran_id) REFERENCES metode_pembayaran(id) ON DELETE SET NULL,
    FOREIGN KEY (metode_pengiriman_id) REFERENCES metode_pengiriman(id) ON DELETE SET NULL
);

-- Tabel Detail Pesanan
CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- Tabel Pengembalian
CREATE TABLE pengembalian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    user_id INT NOT NULL,
    alasan TEXT NOT NULL,
    status ENUM('pending', 'disetujui', 'ditolak', 'selesai') DEFAULT 'pending',
    keterangan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Testimoni
CREATE TABLE testimoni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pesanan_id INT,
    rating INT DEFAULT 5,
    komentar TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE SET NULL
);

-- Tabel Blog/Artikel
CREATE TABLE artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    konten TEXT NOT NULL,
    gambar VARCHAR(255),
    author_id INT,
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel FAQ
CREATE TABLE faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pertanyaan TEXT NOT NULL,
    jawaban TEXT NOT NULL,
    urutan INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kontak
CREATE TABLE kontak_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subjek VARCHAR(200),
    pesan TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Pengaturan Website
CREATE TABLE pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_toko VARCHAR(100) DEFAULT 'Dapur RR',
    tagline VARCHAR(255),
    deskripsi TEXT,
    alamat TEXT,
    no_telepon VARCHAR(20),
    email VARCHAR(100),
    whatsapp VARCHAR(20),
    logo VARCHAR(255),
    favicon VARCHAR(255),
    maps_embed TEXT,
    instagram VARCHAR(100),
    facebook VARCHAR(100),
    twitter VARCHAR(100),
    jam_buka VARCHAR(100)
);

-- Tabel Banner/Promo
CREATE TABLE banner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    urutan INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Leads CRM
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_telepon VARCHAR(20),
    sumber VARCHAR(100),
    status ENUM('baru', 'follow_up', 'tertarik', 'tidak_tertarik', 'konversi') DEFAULT 'baru',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Interaksi CRM
CREATE TABLE interaksi_crm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    user_id INT,
    tipe VARCHAR(50),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert Data Default
INSERT INTO pengaturan (nama_toko, tagline, deskripsi, alamat, no_telepon, email, whatsapp, jam_buka) VALUES
('Dapur RR', 'Kue Lezat untuk Setiap Momen', 'Dapur RR adalah toko kue yang menyediakan berbagai macam kue berkualitas tinggi dengan harga terjangkau', 'Jl. Contoh No. 123, Jakarta', '081234567890', 'info@dapurrr.com', '081234567890', 'Senin - Sabtu: 08.00 - 20.00 WIB');

INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@dapurrr.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password

INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Kue Basah', 'Berbagai jenis kue basah yang lezat'),
('Kue Kering', 'Aneka kue kering untuk berbagai acara'),
('Roti', 'Roti segar setiap hari'),
('Cake', 'Cake untuk ulang tahun dan acara spesial'),
('Cookies', 'Cookies renyah dan manis');

INSERT INTO metode_pembayaran (nama_metode, no_rekening, atas_nama, deskripsi) VALUES
('Transfer Bank BCA', '1234567890', 'Dapur RR', 'Transfer ke rekening BCA'),
('Transfer Bank Mandiri', '0987654321', 'Dapur RR', 'Transfer ke rekening Mandiri'),
('Cash On Delivery (COD)', '-', '-', 'Bayar saat barang diterima');

INSERT INTO metode_pengiriman (nama_metode, biaya, estimasi, deskripsi) VALUES
('Reguler', 10000, '3-5 hari', 'Pengiriman reguler'),
('Express', 25000, '1-2 hari', 'Pengiriman cepat'),
('Same Day', 50000, 'Hari ini', 'Pengiriman di hari yang sama'),
('Ambil Sendiri', 0, 'Langsung', 'Ambil di toko');

INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES
('Bagaimana cara memesan?', 'Anda dapat memesan dengan mendaftar terlebih dahulu, kemudian pilih produk dan masukkan ke keranjang, lalu lakukan checkout.', 1),
('Berapa lama waktu pengiriman?', 'Waktu pengiriman tergantung metode yang dipilih, mulai dari same day hingga 5 hari kerja.', 2),
('Apakah bisa custom kue?', 'Ya, kami menerima pesanan custom. Silakan hubungi kami melalui WhatsApp atau kontak kami.', 3),
('Bagaimana cara pembayaran?', 'Kami menerima pembayaran melalui transfer bank dan COD untuk area tertentu.', 4);

INSERT INTO produk (nama_produk, kategori_id, deskripsi, harga, stok, is_promo, harga_promo) VALUES
('Brownies Coklat', 1, 'Brownies coklat lembut dan legit', 75000, 20, TRUE, 65000),
('Nastar Premium', 2, 'Nastar dengan selai nanas pilihan', 85000, 15, FALSE, NULL),
('Roti Sobek', 3, 'Roti sobek lembut dan empuk', 45000, 30, FALSE, NULL),
('Black Forest Cake', 4, 'Cake black forest untuk ulang tahun', 250000, 10, TRUE, 225000),
('Chocolate Chip Cookies', 5, 'Cookies dengan chocolate chip melimpah', 55000, 25, FALSE, NULL),
('Donat Kentang Spesial', 6, 'Donat kentang empuk dengan topping gula halus', 15000, 50, FALSE, NULL);

INSERT INTO banner (judul, deskripsi, gambar, urutan, is_active) VALUES
('Promo Spesial Akhir Tahun', 'Diskon hingga 30% untuk semua produk', 'banner1.jpg', 1, TRUE),
('Kue Custom untuk Acaramu', 'Pesan kue custom sesuai keinginan', 'banner2.jpg', 2, TRUE);