-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Des 2025 pada 10.43
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dapur_rr`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `artikel`
--

CREATE TABLE `artikel` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `konten` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `artikel`
--

INSERT INTO `artikel` (`id`, `judul`, `slug`, `konten`, `gambar`, `author_id`, `views`, `is_published`, `created_at`) VALUES
(7, 'Dapur RR', 'dapur-rr', 'Siapa sangka, dari iseng bikin kue di rumah saat pandemi, justru lahirlah sebuah usaha yang manis — Dapur RR.\r\n\r\n    Sebelum memulai usaha kue, kami sebenarnya sudah lebih dulu memiliki bisnis kaos karakter sejak tahun 2017. Bisnis itu berjalan cukup baik lewat bazar-bazar offline. Tapi, ketika pandemi COVID-19 datang di tahun 2020, semua kegiatan bazar harus berhenti total.\r\n\r\n    Awalnya sempat bingung harus mulai dari mana. Tapi karena waktu di rumah jadi lebih banyak, kami pun mulai bereksperimen di dapur — membuat berbagai jenis kue untuk keluarga. Tidak disangka, hasilnya disukai banyak teman dan tetangga. Dari situlah muncul keberanian untuk menjual kue buatan sendiri dengan nama Dapur RR.\r\n\r\n    Respon yang datang luar biasa. Dari pesanan kecil-kecilan, kini semakin banyak pelanggan yang mengenal produk kami — mulai dari nastar klasik, kastengel, hingga cookies kekinian. Setiap kue dibuat dengan bahan pilihan dan penuh cinta, agar setiap gigitan punya rasa yang istimewa.\r\n\r\n    Kini, Dapur RR terus berinovasi dan menjaga kualitas produk agar tetap jadi pilihan utama untuk camilan, hampers, atau oleh-oleh keluarga. Kami percaya, usaha besar selalu berawal dari hal kecil — dan Dapur RR adalah buktinya.', '1763804647_logo-dapurr.png', 1, 9, 1, '2025-11-22 09:44:07'),
(18, 'Bazzar Dapur RR 25 - 27 Desember 2025', 'bazzar-dapur-rr-25--27-desember-2025', 'Join bazzar dapur rr yukk di lapangan kukusan', NULL, 1, 0, 1, '2025-12-18 04:47:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `banner`
--

CREATE TABLE `banner` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `banner`
--

INSERT INTO `banner` (`id`, `judul`, `deskripsi`, `gambar`, `link`, `urutan`, `is_active`, `created_at`) VALUES
(9, 'New Drink', '', 'uploads/banner/banner_1763619985_3542.jpeg', '', 1, 1, '2025-11-20 06:26:25'),
(12, 'Promo Ramadhan', '', 'uploads/banner/banner_1764512897_6535.jpg', '', 2, 1, '2025-11-30 14:28:17'),
(13, 'Promo 12.12', '', 'uploads/banner/banner_1764512931_1175.jpg', '', 3, 1, '2025-11-30 14:28:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `produk_id`, `nama_produk`, `harga`, `jumlah`, `subtotal`) VALUES
(11, 8, 21, 'Lemon Sereh', 8000.00, 1, 8000.00),
(18, 14, 28, 'Risol', 12000.00, 2, 24000.00),
(20, 14, 19, 'Donat Kampung', 30000.00, 1, 30000.00),
(21, 15, 42, 'Donat bomboloni', 45000.00, 2, 90000.00),
(22, 15, 19, 'Donat Kampung', 30000.00, 1, 30000.00),
(23, 16, 43, 'Donat sosis', 40000.00, 1, 40000.00),
(24, 16, 27, 'Putri Salju', 50000.00, 1, 50000.00),
(25, 17, 35, 'Lemper', 15000.00, 1, 15000.00),
(26, 17, 33, 'Strawberry susu', 15000.00, 1, 15000.00),
(27, 17, 19, 'Donat Kampung', 30000.00, 1, 30000.00),
(28, 18, 43, 'Donat sosis', 40000.00, 1, 40000.00),
(29, 19, 41, 'Donat mini', 25000.00, 1, 25000.00),
(30, 19, 37, 'Pastel bihun', 10000.00, 1, 10000.00),
(31, 20, 42, 'Donat bomboloni', 45000.00, 1, 45000.00),
(32, 20, 27, 'Putri Salju', 50000.00, 1, 50000.00),
(33, 21, 31, 'Brownchees', 130000.00, 1, 130000.00),
(34, 22, 43, 'Donat sosis', 40000.00, 3, 120000.00),
(35, 22, 40, 'Donat labu', 40000.00, 3, 120000.00),
(36, 23, 42, 'Donat bomboloni', 45000.00, 1, 45000.00),
(37, 24, 42, 'Donat bomboloni', 45000.00, 1, 45000.00),
(38, 25, 41, 'Donat mini', 25000.00, 1, 25000.00),
(39, 26, 32, 'Cookie nutella', 115000.00, 1, 115000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `faq`
--

INSERT INTO `faq` (`id`, `pertanyaan`, `jawaban`, `urutan`, `is_active`, `created_at`) VALUES
(1, 'Bagaimana cara memesan?', 'Anda dapat memesan dengan mendaftar terlebih dahulu, kemudian pilih produk dan masukkan ke keranjang, lalu lakukan checkout.', 1, 1, '2025-10-11 07:22:50'),
(2, 'Berapa lama waktu pengiriman?', 'Waktu pengiriman tergantung metode yang dipilih, mulai dari same day hingga 5 hari kerja.', 2, 1, '2025-10-11 07:22:50'),
(3, 'Apakah bisa custom kue?', 'Ya, kami menerima pesanan custom. Silakan hubungi kami melalui WhatsApp atau kontak kami.', 3, 1, '2025-10-11 07:22:50'),
(4, 'Bagaimana cara pembayaran?', 'Kami menerima pembayaran melalui transfer bank dan COD untuk area tertentu.', 4, 1, '2025-10-11 07:22:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `interaksi_crm`
--

CREATE TABLE `interaksi_crm` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tipe` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `gambar`, `created_at`) VALUES
(6, 'Donat', 'Kue bulat bertekstur lembut dan diberi berbagai topping.', NULL, '2025-10-20 09:45:15'),
(7, 'Minuman', 'Aneka minuman hangat atau dingin sebagai pelengkap camilan.', NULL, '2025-10-20 09:47:55'),
(8, 'Bolu', 'Kue lembut dan mengembang, dibuat dari telur, gula, dan tepung.', NULL, '2025-10-20 10:00:37'),
(9, 'Kue Pasar', 'Kue tradisional Indonesia dengan rasa manis atau gurih, sering dijual di pasar.', NULL, '2025-10-20 10:00:53'),
(10, 'Hari Raya', 'Kue khas Lebaran dan Natal seperti nastar, kastengel, atau kukis.', NULL, '2025-10-20 10:01:10'),
(15, 'Pastry', 'Kue Kering Panggang', NULL, '2025-12-18 03:39:34'),
(16, 'puff pastry', 'roti berlapis renyah yang manis', NULL, '2025-12-18 04:42:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `keranjang`
--

INSERT INTO `keranjang` (`id`, `user_id`, `produk_id`, `jumlah`, `created_at`) VALUES
(37, 5, 41, 1, '2025-12-01 08:13:09'),
(48, 7, 27, 1, '2025-12-13 10:53:05'),
(49, 7, 43, 2, '2025-12-13 11:00:40'),
(50, 7, 42, 1, '2025-12-13 11:01:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontak_masuk`
--

CREATE TABLE `kontak_masuk` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subjek` varchar(200) DEFAULT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kontak_masuk`
--

INSERT INTO `kontak_masuk` (`id`, `nama`, `email`, `subjek`, `pesan`, `is_read`, `created_at`) VALUES
(5, 'sdsds', 'o@gmail.com', 'zsdz', 'dzsdzdzs', 1, '2025-11-25 05:24:08'),
(7, 'el', 'elele@gmail.com', 'nanya produk', 'bisa pesan buat hampers ga?', 1, '2025-12-01 07:14:33'),
(8, 'usep slebew', 'yaiyalahbodo@gmail.com', 'Happers', 'apakah disini dapat membuat hampers?', 0, '2025-12-13 11:04:35'),
(9, 'mute', 'mute1@gmail.com', 'hampers', 'tolong adain pembuatan hampers hari raya', 0, '2025-12-18 04:39:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `sumber` varchar(100) DEFAULT NULL,
  `status` enum('baru','follow_up','tertarik','tidak_tertarik','konversi') DEFAULT 'baru',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `id` int(11) NOT NULL,
  `nama_metode` varchar(100) NOT NULL,
  `no_rekening` varchar(100) DEFAULT NULL,
  `atas_nama` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`id`, `nama_metode`, `no_rekening`, `atas_nama`, `deskripsi`, `is_active`, `created_at`) VALUES
(1, 'Transfer Bank BCA', '1234567890', 'Dapur RR', 'Transfer ke rekening BCA', 1, '2025-10-11 07:22:50'),
(2, 'Transfer Bank Mandiri', '0987654321', 'Dapur RR', 'Transfer ke rekening Mandiri', 1, '2025-10-11 07:22:50'),
(3, 'Cash On Delivery (COD)', '-', '-', 'Bayar saat barang diterima', 1, '2025-10-11 07:22:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `metode_pengiriman`
--

CREATE TABLE `metode_pengiriman` (
  `id` int(11) NOT NULL,
  `nama_metode` varchar(100) NOT NULL,
  `biaya` decimal(10,2) NOT NULL,
  `estimasi` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `metode_pengiriman`
--

INSERT INTO `metode_pengiriman` (`id`, `nama_metode`, `biaya`, `estimasi`, `deskripsi`, `is_active`, `created_at`) VALUES
(1, 'Reguler', 10000.00, '3-5 hari', 'Pengiriman reguler', 1, '2025-10-11 07:22:50'),
(2, 'Express', 25000.00, '1-2 hari', 'Pengiriman cepat', 1, '2025-10-11 07:22:50'),
(3, 'Same Day', 50000.00, 'Hari ini', 'Pengiriman di hari yang sama', 1, '2025-10-11 07:22:50'),
(4, 'Ambil Sendiri', 0.00, 'Langsung', 'Ambil di toko', 1, '2025-10-11 07:22:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`, `used_at`) VALUES
(1, 'o@gmail.com', '2d50597cdc44e489985e2808fd58c25db79f95a20fac8d012d9f1374c268991a', '2025-12-01 08:37:03', '2025-12-01 06:37:03', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_toko` varchar(100) DEFAULT 'Dapur RR',
  `tagline` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `maps_embed` text DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `facebook` varchar(100) DEFAULT NULL,
  `twitter` varchar(100) DEFAULT NULL,
  `jam_buka` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_toko`, `tagline`, `deskripsi`, `alamat`, `no_telepon`, `email`, `whatsapp`, `logo`, `favicon`, `maps_embed`, `instagram`, `facebook`, `twitter`, `jam_buka`) VALUES
(1, 'Dapur RR', 'Lezat Setiap Hari', 'Tempat lahirnya cita rasa yang bikin nagih! Setiap masakan dibuat dari bahan segar dan penuh cinta siap memanjakan lidahmu di setiap gigitan.', 'Jl. Rw. Pule 2 No.12a, RT.03/RW.02, Kukusan, Kecamatan Beji, Kota Depok, Jawa Barat 16425', '+62 856-9145-2909', 'dapurRR@gmail.com', '6285691452909', NULL, NULL, NULL, 'DapurRR@gmail.com', '', NULL, 'Senin - Sabtu: 08.00 - 20.00 WIB');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kategori_pengeluaran` enum('operasional','produksi','gaji','sewa','lainnya') NOT NULL,
  `keterangan` text NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengeluaran`
--

INSERT INTO `pengeluaran` (`id`, `tanggal`, `kategori_pengeluaran`, `keterangan`, `jumlah`, `created_by`, `created_at`, `updated_at`) VALUES
(4, '2025-12-10', 'gaji', 'gaji andini', 1500000.00, 1, '2025-12-10 00:33:26', '2025-12-10 00:33:26'),
(5, '2025-12-13', 'produksi', 'Pembelian bahan produksi', 600000.00, 1, '2025-12-13 11:31:47', '2025-12-13 11:33:02'),
(6, '2025-12-18', 'sewa', 'sewa ruko ', 500000.00, 1, '2025-12-18 04:45:29', '2025-12-18 04:45:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alasan` text NOT NULL,
  `bukti_foto` varchar(255) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak','selesai') DEFAULT 'pending',
  `keterangan_admin` text DEFAULT NULL,
  `tanggal_diproses` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `pesanan_id`, `user_id`, `alasan`, `bukti_foto`, `status`, `keterangan_admin`, `tanggal_diproses`, `tanggal_selesai`, `created_at`) VALUES
(1, 6, 2, 'ada produk cacat', NULL, 'ditolak', 'ya', NULL, NULL, '2025-11-15 22:44:56'),
(2, 3, 2, 'rusak', NULL, 'disetujui', 'sa', NULL, NULL, '2025-11-19 01:12:04'),
(3, 11, 2, 'tidak ada karena pengen di kembalikan saja', NULL, 'disetujui', 'ya', NULL, NULL, '2025-12-01 16:21:18'),
(4, 15, 2, 'saya pengen mengembalikan saja', NULL, 'ditolak', 'ok', NULL, NULL, '2025-12-01 16:23:41'),
(5, 21, 7, 'kurang satu broncis nya', NULL, 'pending', 'siap kak, nanti di antar lagi yang baru', NULL, NULL, '2025-12-13 10:43:09'),
(6, 20, 2, 'wdawds', 'uploads/pengembalian/bukti_1765863643_6940f0db2b286.png', 'selesai', 'ya', NULL, '2025-12-16 11:47:30', '2025-12-16 05:40:43'),
(7, 22, 2, 'asasa', 'uploads/pengembalian/bukti_1765865733_6940f9052ba98.png', 'selesai', 'ya', NULL, '2025-12-17 02:08:30', '2025-12-16 06:15:33'),
(8, 26, 8, 'kuenya hancur', 'uploads/pengembalian/bukti_1766032020_694382942fcc1.jpg', 'disetujui', 'siap buk, nanti saya antar', '2025-12-18 05:27:50', NULL, '2025-12-18 04:27:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `no_pesanan` varchar(50) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `ongkir` decimal(10,2) DEFAULT 0.00,
  `total_bayar` decimal(10,2) NOT NULL,
  `metode_pembayaran_id` int(11) DEFAULT NULL,
  `metode_pengiriman_id` int(11) DEFAULT NULL,
  `alamat_pengiriman` text NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','dikonfirmasi','dibayar','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `status_pengembalian` enum('pending','disetujui','ditolak','selesai') DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `resi_pengiriman` varchar(100) DEFAULT NULL,
  `tanggal_pembayaran` datetime DEFAULT NULL,
  `tanggal_kirim` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `is_rated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `no_pesanan`, `total_harga`, `ongkir`, `total_bayar`, `metode_pembayaran_id`, `metode_pengiriman_id`, `alamat_pengiriman`, `no_telepon`, `catatan`, `status`, `status_pengembalian`, `bukti_pembayaran`, `resi_pengiriman`, `tanggal_pembayaran`, `tanggal_kirim`, `tanggal_selesai`, `is_rated`, `created_at`) VALUES
(1, 2, 'DRR202510114109', 150000.00, 10000.00, 160000.00, 3, 1, 'cariu', '085716379677', 'pagi hari', 'selesai', NULL, NULL, NULL, NULL, NULL, '2025-11-26 07:30:12', 0, '2025-10-11 12:59:55'),
(2, 2, 'DRR202510112971', 65000.00, 10000.00, 75000.00, 3, 1, 'cariu', '085716379677', 'pagi', 'dibatalkan', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-10-11 13:12:15'),
(3, 2, 'DRR202510126420', 15000.00, 0.00, 15000.00, 3, 4, 'cariu', '085716379677', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-10-12 11:35:13'),
(4, 2, 'DRR202510142588', 400000.00, 0.00, 400000.00, 3, 4, 'cariu', '085716379677', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-10-14 13:12:09'),
(5, 2, 'DRR202510145189', 15000.00, 0.00, 15000.00, 3, 4, 'cariu', '085716379677', '', 'dibatalkan', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-10-14 13:31:42'),
(6, 2, 'DRR202510151810', 30000.00, 10000.00, 40000.00, 3, 1, 'cariu', '085716379677', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-15 01:34:02'),
(7, 1, 'DRR202510187769', 10000.00, 10000.00, 20000.00, 1, 1, 'wewewew', '085716379677', 'wew', 'selesai', NULL, 'bukti_pembayaran/68f3782307dac.png', NULL, '2025-10-18 13:21:07', NULL, NULL, 0, '2025-10-18 11:20:38'),
(8, 3, 'DRR202511121957', 158000.00, 10000.00, 168000.00, 3, 1, 'rapul', '09181111', 'mohon cepat ya, saya lapar', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-12 01:16:17'),
(9, 3, 'DRR202511122317', 18000.00, 25000.00, 43000.00, 1, 2, 'rapul', '09181111', '', 'selesai', NULL, 'bukti_pembayaran/6913e060ebc61.jpg', NULL, '2025-11-12 02:18:24', NULL, NULL, 0, '2025-11-12 01:17:11'),
(10, 2, 'DRR202511186418', 420000.00, 10000.00, 430000.00, 1, 1, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/691bdd887de9e.png', NULL, '2025-11-18 03:44:24', NULL, NULL, 0, '2025-11-18 02:35:18'),
(11, 2, 'DRR202511183384', 10000.00, 10000.00, 20000.00, 1, 1, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/691be0d6edf0a.png', '13', '2025-11-18 03:58:30', NULL, NULL, 1, '2025-11-18 02:58:17'),
(12, 2, 'DRR202511193081', 18000.00, 0.00, 18000.00, 1, 4, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/691d103478767.png', NULL, '2025-11-19 01:32:52', NULL, NULL, 0, '2025-11-19 00:32:30'),
(13, 2, 'DRR202511192538', 10000.00, 0.00, 10000.00, 1, 4, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/691d1934971a8.png', NULL, '2025-11-19 02:11:16', NULL, NULL, 0, '2025-11-19 01:11:01'),
(14, 2, 'DRR202511277216', 69000.00, 0.00, 69000.00, 3, 4, 'cariu', '085716379677', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-27 02:52:42'),
(15, 2, 'DRR202512017088', 120000.00, 10000.00, 130000.00, 3, 1, 'cariu', '085716379677', 'saa', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-12-01 05:42:41'),
(16, 5, 'DRR202512014635', 90000.00, 25000.00, 115000.00, 1, 2, 'tanah baru', '0856', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-12-01 08:07:16'),
(17, 6, 'DRR202512018834', 60000.00, 10000.00, 70000.00, 1, 1, 'bojong gede', '0888', '', 'selesai', NULL, 'bukti_pembayaran/692d5b44f3e2a.png', NULL, '2025-12-01 10:09:25', NULL, NULL, 0, '2025-12-01 08:53:43'),
(18, 6, 'DRR202512013099', 40000.00, 10000.00, 50000.00, 3, 1, 'bojong gede', '0888', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-12-01 09:10:24'),
(19, 6, 'DRR202512015303', 35000.00, 10000.00, 45000.00, 1, 1, 'bojong gede', '0888', '', 'selesai', NULL, 'bukti_pembayaran/692d62cf98cda.png', NULL, '2025-12-01 10:41:35', NULL, NULL, 0, '2025-12-01 09:41:14'),
(20, 2, 'DRR202512038136', 95000.00, 0.00, 95000.00, 1, 4, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/692f8c42462d9.png', NULL, '2025-12-03 02:02:58', NULL, NULL, 0, '2025-12-03 01:01:58'),
(21, 7, 'DRR202512133079', 130000.00, 50000.00, 180000.00, 1, 3, 'jl.12', '082193801', 'tambahkan pita', 'selesai', NULL, 'bukti_pembayaran/693d41e227623.png', NULL, '2025-12-13 11:37:22', NULL, NULL, 0, '2025-12-13 10:34:45'),
(22, 2, 'DRR202512166364', 240000.00, 10000.00, 250000.00, 3, 1, 'cariu', '085716379677', '', 'selesai', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-12-16 06:14:36'),
(23, 2, 'DRR202512179734', 45000.00, 10000.00, 55000.00, 1, 1, 'cariu', '085716379677', '', 'selesai', NULL, 'bukti_pembayaran/6941f7f066aa9.png', '123', '2025-12-17 01:23:12', NULL, NULL, 0, '2025-12-17 00:22:31'),
(24, 2, 'DRR202512176677', 45000.00, 10000.00, 55000.00, 1, 1, 'cariu', '085716379677', '', 'dibayar', NULL, 'bukti_pembayaran/6941fd96386b8.png', NULL, '2025-12-17 01:47:18', NULL, NULL, 0, '2025-12-17 00:46:49'),
(25, 2, 'DRR202512175384', 25000.00, 10000.00, 35000.00, 1, 1, 'cariu', '085716379677', '', 'dikirim', NULL, 'bukti_pembayaran/694201ba75e1c.png', '1234', '2025-12-17 02:04:58', NULL, NULL, 0, '2025-12-17 01:04:39'),
(26, 8, 'DRR202512189897', 115000.00, 50000.00, 165000.00, 1, 3, 'jl. rapul 2', '081928372', 'tambahkan pita', 'selesai', NULL, 'bukti_pembayaran/6943814bd061b.jpg', '123', '2025-12-18 05:21:31', NULL, NULL, 0, '2025-12-18 04:20:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `berat` int(11) DEFAULT 500,
  `is_promo` tinyint(1) DEFAULT 0,
  `harga_promo` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `kategori_id`, `deskripsi`, `harga`, `stok`, `gambar`, `berat`, `is_promo`, `harga_promo`, `created_at`, `updated_at`) VALUES
(15, 'Bolu Ketan Hitam', 8, 'Bolu lembut dengan bahan dasar ketan hitam, memberikan aroma khas dan tekstur yang unik, manisnya pas untuk acara spesial. Ukuran Loyang : 18cm Cara Simpan : Simpan disuhu ruang, tahan hingga 2 hari.', 60000.00, 10, 'uploads/produk/produk_1760955157_2207.jpg', 0, 0, 0.00, '2025-10-20 10:12:37', NULL),
(16, 'Bolu Pandan', 8, 'Bolu berwarna hijau lembut dengan aroma pandan yang khas dan rasa manis ringan. Lembut dan menenangkan. Ukuran Loyang : 18cm Cara Simpan : Simpan disuhu ruang, tahan hingga 2 hari.', 70000.00, 7, 'uploads/produk/produk_1760955211_6170.jpeg', 0, 0, 0.00, '2025-10-20 10:13:31', '2025-10-20 10:15:45'),
(17, 'Bolu Pisang', 8, 'Bolu lembut dengan rasa dan aroma pisang yang harum. Cocok untuk camilan sore hari bersama teh hangat. Ukuran Loyang : 22cm Cara Simpan : Simpan disuhu ruang, tahan hingga 2 hari.', 80000.00, 10, 'uploads/produk/produk_1760955278_4069.png', 0, 0, 0.00, '2025-10-20 10:14:38', '2025-10-20 10:15:32'),
(18, 'Bolu Tape', 8, 'Bolu lembut dengan campuran tape singkong yang memberikan rasa manis-asam alami dan tekstur moist. Ukuran Loyang : 22cm Cara Simpan : Simpan disuhu ruang, tahan hingga 2 hari.', 75000.00, 6, 'uploads/produk/produk_1760955408_5827.jpg', 0, 1, 70000.00, '2025-10-20 10:16:48', '2025-11-22 14:29:05'),
(19, 'Donat Kampung', 6, 'Donat klasik dengan tambahan kentang tumbuk, dengan taburan gula halus. Menghasilkan tekstur lembut, ringan, dan empuk. 12pcs/box Cara Simpan: Simpan disuhu ruang, tahan 2 hari.', 30000.00, 1, 'uploads/produk/produk_1760955502_6564.jpg', 0, 0, 0.00, '2025-10-20 10:18:22', '2025-12-01 08:53:43'),
(20, 'Kastengel', 10, 'Kue keju gurih dengan aroma butter dan taburan keju parut di atasnya. Rasanya gurih dan mewah, cocok untuk pecinta keju sejati. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu.', 140000.00, 3, 'uploads/produk/produk_1760955577_1022.jpeg', 0, 0, 0.00, '2025-10-20 10:19:37', NULL),
(21, 'Lemon Sereh', 7, 'Minuman segar dan menyehatkan dari percampuran lemon segar dan sereh wangi, membantu menyegarkan tubuh di suasana Siang Hari yang panas. Ukuran Botol : 250ml Cara Simpan : Simpan pada suhu dingin, tahan hingga 1 minggu setelah dibuka.', 8000.00, 3, 'uploads/produk/produk_1760955653_5030.jpg', 0, 0, 0.00, '2025-10-20 10:20:53', '2025-11-12 01:16:17'),
(27, 'Putri Salju', 10, 'Kue lembut berlapis gula halus seperti salju, manis dan lumer di mulut. Simbol kehangatan dan kebersamaan di hari raya. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu. Berat Bersih : 260gr', 105000.00, 1, 'uploads/produk/produk_1763949605_8305.jpeg', 500, 1, 90000.00, '2025-11-24 02:00:05', '2025-12-16 06:36:21'),
(28, 'Risol Sayur', 9, 'Camilan gurih dengan isian sayur dan kentang lembut dibalut kulit risol yang digoreng hingga renyah. Sehat dan mengenyangkan.  Ukuran Tinwal : 500ml/4pcs. Cara Penyimpanan :  Simpan disuhu ruang, tahan 1 hari. Berat Bersih : 260gr', 10000.00, 5, 'uploads/produk/produk_1763949698_5969.jpg', 0, 0, 50000.00, '2025-11-24 02:01:38', '2025-12-01 08:28:27'),
(29, 'Nastar', 10, 'Kue kering klasik isi selai nanas manis-asam yang lembut dan lumer di mulut.Teksturnya renyah di luar dan lembut di dalam, cocok sebagai suguhan khas saat Lebaran & Natal. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu. Berat Bersih : 270gr', 130000.00, 4, 'uploads/produk/produk_1764427312_8095.jpeg', 0, 1, 104000.00, '2025-11-29 14:41:52', '2025-12-13 11:55:49'),
(30, 'Kastengel', 10, 'Kue keju gurih dengan aroma butter dan taburan keju parut di atasnya. Rasanya gurih dan mewah, cocok untuk pecinta keju sejati. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu. Bberat Bersih : 250gr', 140000.00, 0, 'uploads/produk/produk_1764427368_5078.jpeg', 0, 0, 0.00, '2025-11-29 14:42:48', NULL),
(31, 'Brownchees', 10, 'perpaduan unik antara keju gurih dan gula aren manis, menghadirkan cita rasa yang kaya, lembut, dan berkarakter, menciptakan sensasi baru di lidah antara asin, manis, dan sedikit karamel yang berpadu harmonis. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu. Berat Bersih : 300gr', 130000.00, 4, 'uploads/produk/produk_1764427590_4147.jpg', 0, 0, 0.00, '2025-11-29 14:46:30', '2025-12-13 10:34:45'),
(32, 'Cookie nutella', 10, 'Kue kering renyah berisi lelehan Nutella di tengahnya. Cokelat yang melimpah membuat setiap gigitan penuh kenikmatan. Ukuran biggy : 500gr Cara Simpan : Simpan diwadah kedap udara dengan suhu ruang tahan 2 minggu. Berat Bersih : 280gr', 115000.00, 3, 'uploads/produk/produk_1764427693_3443.jpg', 0, 0, 0.00, '2025-11-29 14:48:13', '2025-12-18 04:20:45'),
(33, 'Strawberry susu', 7, 'Kombinasi manisnya susu segar dengan rasa stroberi yang lembut dan menyegarkan. Favorit semua kalangan, terutama anak-anak. Ukuran Botol : 500ml Cara Simpan : Simpan pada suhu dingin, tahan hingga 1 minggu setelah dibuka. Berat Bersih : 495gr', 15000.00, 2, 'uploads/produk/produk_1764427772_4322.jpg', 0, 0, 0.00, '2025-11-29 14:49:32', '2025-12-01 08:53:43'),
(34, 'Sirsak', 7, 'Minuman buah sirsak asli yang manis-asam alami dan kaya vitamin C. Cocok disajikan dingin untuk melepas dahaga. Ukuran Botol : 500ml Cara Simpan : Simpan pada suhu dingin, tahan hingga 1 minggu setelah dibuka.  Berat Bersih : 495gr', 15000.00, 4, 'uploads/produk/produk_1764427814_4959.png', 0, 0, 0.00, '2025-11-29 14:50:14', '2025-11-29 14:50:23'),
(35, 'Lemper', 9, 'Nasi ketan lembut diisi dengan abon sapi, dibungkus daun pisang, cita rasa tradisional. Ukuran Tinwal : 500ml/5pcs. Cara Penyimpanan : Simpan disuhu ruang, tahan 1 hari. Berat Bersih : 250gr', 15000.00, 5, 'uploads/produk/produk_1764427888_6706.jpg', 0, 0, 0.00, '2025-11-29 14:51:28', '2025-12-01 08:53:43'),
(36, 'Nona manis', 9, 'Kue berwarna cantik dengan lapisan ungu serta isian vla manis di tengahnya. Lembut, manis, dan menggoda.  Ukuran Tinwal : 500ml/6pcs. Cara Penyimpanan : Simpan disuhu ruang, tahan 1 hari. Berat Bersih : 230gr', 18000.00, 6, 'uploads/produk/produk_1764427973_2516.png', 0, 0, 0.00, '2025-11-29 14:52:53', NULL),
(37, 'Pastel bihun', 9, 'Gorengan berisi bihun dan sayuran berbumbu gurih, dengan kulit renyah keemasan. Nikmat sebagai camilan. Ukuran Tinwal : 500ml/4pcs. Cara Penyimpanan :  Simpan disuhu ruang, tahan 1 hari. Berat Bersih : 270gr', 10000.00, 0, 'uploads/produk/produk_1764428021_6666.jpg', 0, 0, 0.00, '2025-11-29 14:53:41', '2025-12-01 09:41:14'),
(38, 'Arem arem', 9, 'Nasi berbumbu gurih berisi ayam dan sayuran, dibungkus daun pisang dan dikukus hingga harum. Praktis dan lezat sebagai pengganjal lapar.  Ukuran Tinwal : 500ml/4pcs. Cara Penyimpanan :  Simpan disuhu ruang, tahan 1 hari. Berat Bersih : 280gr', 10000.00, 0, 'uploads/produk/produk_1764428085_2941.jpg', 0, 0, 0.00, '2025-11-29 14:54:45', NULL),
(39, 'Bolu caramell', 8, 'Bolu bertekstur lembut dan bersarang dengan rasa karamel manis dan aroma wangi gula jawa yang khas. Ukuran Loyang : 20cm Cara Simpan : Simpan disuhu ruang, tahan hingga 2 hari. Berat Bersih : 400gr', 100000.00, 5, 'uploads/produk/produk_1764428176_8081.jpg', 0, 1, 70000.00, '2025-11-29 14:56:16', '2025-12-18 04:30:48'),
(40, 'Donat labu', 6, 'Donat empuk dengan campuran labu kuning yang memberikan rasa manis alami dan warna kuning cantik. Dengan berbagai topping lucu seperti cokelat, keju, kacang dan meses coklat 12pcs/box Cara Simpan: Simpan disuhu ruang, tahan 2 hari. Berat Bersih : 520gr', 40000.00, 2, 'uploads/produk/produk_1764428234_1420.jpg', 0, 0, 0.00, '2025-11-29 14:57:14', '2025-12-16 06:14:36'),
(41, 'Donat mini', 6, 'Donat kecil bertekstur lembut dengan berbagai topping lucu seperti cokelat, keju, kacang dan meses coklat. Cocok untuk hampers atau pesta. 12pcs/box Cara Simpan: Simpan disuhu ruang, tahan 2 hari. Berat Bersih : 500gr', 25000.00, 2, 'uploads/produk/produk_1764428288_4653.png', 0, 0, 0.00, '2025-11-29 14:58:08', '2025-12-17 01:04:39'),
(42, 'Donat bomboloni', 6, 'Donat isi ala Italia dengan isian selai blueberry, atau strawberry, dan nuteella yang melimpah. Lembut dan meledak di mulut. 12pcs/box Cara Simpan: Simpan disuhu ruang, tahan 2 hari. Berat Bersih : 550gr', 45000.00, 2, 'uploads/produk/produk_1764428329_9722.jpg', 0, 0, 0.00, '2025-11-29 14:58:50', '2025-12-17 00:46:49'),
(43, 'Donat sosis', 6, 'Donat unik dengan tambahan sosis dan selada gurih, perpaduan rasa manis dan gurih untuk variasi camilan. cocok dinikmati oleh semua kalangan, dari anak-anak hingga orang dewasa. 12pcs/box Cara Simpan: Simpan disuhu ruang, tahan 2 hari. Berat Bersih : 580gr', 50000.00, 3, 'uploads/produk/produk_1764428372_6263.jpg', 0, 1, 35000.00, '2025-11-29 14:59:32', '2025-12-17 01:06:58'),
(48, 'cinnamonroll', 16, 'roti kayu manis', 25000.00, 10, 'uploads/produk/produk_1766033013_5777.jpg', 500, 0, 0.00, '2025-12-18 04:43:33', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `rating_produk`
--

CREATE TABLE `rating_produk` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `ulasan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rating_produk`
--

INSERT INTO `rating_produk` (`id`, `pesanan_id`, `produk_id`, `user_id`, `rating`, `ulasan`, `created_at`) VALUES
(1, 14, 28, 2, 5, 'enak banget', '2025-11-27 02:54:33'),
(2, 14, 19, 2, 5, 'mantap cuy', '2025-11-27 02:56:32'),
(3, 15, 42, 2, 4, 'wenak coyy,isinya kurang 1', '2025-12-01 05:44:13'),
(4, 19, 41, 6, 5, 'makyus banget', '2025-12-01 09:57:53'),
(5, 15, 19, 2, 5, 'enak bangettt', '2025-12-01 16:38:15'),
(6, 20, 42, 2, 5, 'enakkk bangett', '2025-12-03 01:04:27'),
(7, 21, 31, 7, 5, 'Enak rasanya', '2025-12-13 10:41:17'),
(8, 26, 32, 8, 5, 'enak banget oi', '2025-12-18 04:24:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `testimoni`
--

CREATE TABLE `testimoni` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pesanan_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT 5,
  `komentar` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_telepon`, `alamat`, `role`, `is_active`, `created_at`) VALUES
(1, 'Administrator', 'admin@dapurrr.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'admin', 1, '2025-10-11 07:22:50'),
(2, 'USEP', 'o@gmail.com', '$2y$10$WRphJC0u487fhJz5yXAdoOH/GWCE1CwKPQb4PTwJn4iIX3TVxZOYS', '085716379677', 'cariu', 'user', 1, '2025-10-11 07:45:21'),
(3, 'nawa', 'nawa@gmail.com', '$2y$10$L4IDulus./gSVJWfee7jveK6fWqVAIRIwb7d6hQV4dQXhqsq0jfNS', '09181111', 'rapul', 'user', 1, '2025-11-12 01:13:46'),
(4, 'doni', '1@gmail.com', '$2y$10$4axt13Um.w4FqDtePCwNNOvpXVixx7hV6yv.PsMmgbFlaR1iq/oou', '0857443424', 'depok', 'user', 1, '2025-12-01 07:00:19'),
(5, 'elo', 'elele@gmail.com', '$2y$10$GoDK.jsa5KXh2AO6Q0AXquBarU6IPbihXYBqYG/LZkEVQqgkDADQu', '0856', 'tanah baru', 'user', 1, '2025-12-01 07:10:14'),
(6, 'fathia', 'fathiaoy@gmail.com', '$2y$10$X3VEviuvKyN2AfBqlKPYZupXaDhpJM.IB3gCAgKzTb44uHM4Q55wO', '0888', 'bojong gede', 'user', 1, '2025-12-01 08:20:15'),
(7, 'usep', '12@gmail.com', '$2y$10$cGgMfDzRa8bXpxveB8asu.MmQ63AoTdY70rMz2CRWxXljtCjzCE1e', '082193801', 'jl. mawar bogor', 'user', 1, '2025-12-13 10:30:14'),
(8, 'Mutia', 'kelompok3@gmail.com', '$2y$10$L.HOn7XbIvA9wYVgwvU.6ekGGyOKTMrYJZT2qXMwgzBv8fSp3D9m6', '081928372', 'jl. rapul 2', 'user', 1, '2025-12-18 04:17:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `produk_id`, `created_at`) VALUES
(29, 5, 42, '2025-12-01 07:36:30'),
(45, 2, 43, '2025-12-18 03:26:44'),
(46, 8, 43, '2025-12-18 04:32:36'),
(47, 8, 42, '2025-12-18 04:33:04');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `artikel`
--
ALTER TABLE `artikel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Indeks untuk tabel `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `interaksi_crm`
--
ALTER TABLE `interaksi_crm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `kontak_masuk`
--
ALTER TABLE `kontak_masuk`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `metode_pengiriman`
--
ALTER TABLE `metode_pengiriman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_pesanan` (`no_pesanan`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `metode_pembayaran_id` (`metode_pembayaran_id`),
  ADD KEY `metode_pengiriman_id` (`metode_pengiriman_id`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `rating_produk`
--
ALTER TABLE `rating_produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`pesanan_id`,`produk_id`,`user_id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `testimoni`
--
ALTER TABLE `testimoni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pesanan_id` (`pesanan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT untuk tabel `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `interaksi_crm`
--
ALTER TABLE `interaksi_crm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT untuk tabel `kontak_masuk`
--
ALTER TABLE `kontak_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `metode_pengiriman`
--
ALTER TABLE `metode_pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `rating_produk`
--
ALTER TABLE `rating_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `testimoni`
--
ALTER TABLE `testimoni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `artikel`
--
ALTER TABLE `artikel`
  ADD CONSTRAINT `artikel_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `interaksi_crm`
--
ALTER TABLE `interaksi_crm`
  ADD CONSTRAINT `interaksi_crm_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interaksi_crm_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `pengeluaran_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengembalian_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`metode_pembayaran_id`) REFERENCES `metode_pembayaran` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pesanan_ibfk_3` FOREIGN KEY (`metode_pengiriman_id`) REFERENCES `metode_pengiriman` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `testimoni`
--
ALTER TABLE `testimoni`
  ADD CONSTRAINT `testimoni_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `testimoni_ibfk_2` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
