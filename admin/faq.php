<?php
session_start();
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $pertanyaan = trim($_POST['pertanyaan']);
        $jawaban = trim($_POST['jawaban']);
        $urutan = intval($_POST['urutan']);
        
        $stmt = $conn->prepare("INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $pertanyaan, $jawaban, $urutan);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'FAQ berhasil ditambahkan');
        } else {
            setFlashMessage('error', 'Gagal menambahkan FAQ');
        }
        redirect('faq.php');
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $pertanyaan = trim($_POST['pertanyaan']);
        $jawaban = trim($_POST['jawaban']);
        $urutan = intval($_POST['urutan']);
        
        $stmt = $conn->prepare("UPDATE faq SET pertanyaan = ?, jawaban = ?, urutan = ? WHERE id = ?");
        $stmt->bind_param("ssii", $pertanyaan, $jawaban, $urutan, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'FAQ berhasil diupdate');
        }
        redirect('faq.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'FAQ berhasil dihapus');
        }
        redirect('faq.php');
    }
}

// Get FAQs
$faq = $conn->query("SELECT * FROM faq ORDER BY urutan ASC, created_at DESC");
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola FAQ - Admin Dapur RR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid white;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: #333;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .faq-item {
            padding: 1.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .faq-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .faq-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .faq-question {
            color: #333;
            font-weight: 600;
            font-size: 1.1rem;
            flex: 1;
        }
        
        .faq-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        .faq-answer {
            color: #666;
            line-height: 1.6;
            margin-top: 0.5rem;
        }
        
        .faq-meta {
            color: #999;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 1.5rem;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🧁 Dapur RR</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="produk.php" class="menu-item">🛍️ Produk</a>
            <a href="kategori.php" class="menu-item">📁 Kategori</a>
            <a href="pesanan.php" class="menu-item">📦 Pesanan</a>
            <a href="pelanggan.php" class="menu-item">👥 Pelanggan</a>
            <a href="artikel2.php" class="menu-item">📝 Artikel</a>
            <a href="banner.php" class="menu-item">🖼️ Banner</a>
            <a href="testimoni.php" class="menu-item">⭐ Testimoni</a>
            <a href="faq.php" class="menu-item active">❓ FAQ</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Kelola FAQ</h1>
            <div class="user-info">
                <span>👤 <?php echo getUserName(); ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Form Add -->
            <div class="card">
                <h2 class="card-title">Tambah FAQ Baru</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="pertanyaan">Pertanyaan *</label>
                        <input type="text" id="pertanyaan" name="pertanyaan" required>
                    </div>
                    <div class="form-group">
                        <label for="jawaban">Jawaban *</label>
                        <textarea id="jawaban" name="jawaban" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="urutan">Urutan</label>
                        <input type="number" id="urutan" name="urutan" min="1" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">➕ Tambah FAQ</button>
                </form>
            </div>

            <!-- List FAQs -->
            <div class="card">
                <h2 class="card-title">Daftar FAQ (<?php echo $faq->num_rows; ?>)</h2>
                <div class="faq-list">
                    <?php if ($faq->num_rows > 0): ?>
                        <?php while($f = $faq->fetch_assoc()): ?>
                        <div class="faq-item">
                            <div class="faq-header">
                                <div class="faq-question">
                                    <?php echo htmlspecialchars($f['pertanyaan']); ?>
                                </div>
                                <div class="faq-actions">
                                    <button onclick="editFaq(<?php echo $f['id']; ?>, '<?php echo addslashes($f['pertanyaan']); ?>', '<?php echo addslashes($f['jawaban']); ?>', <?php echo $f['urutan']; ?>)" 
                                            class="btn btn-warning btn-sm">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Yakin hapus FAQ ini?')">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="faq-answer">
                                <?php echo nl2br(htmlspecialchars($f['jawaban'])); ?>
                            </div>
                            <div class="faq-meta">
                                🔢 Urutan: <?php echo $f['urutan']; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">
                            Belum ada FAQ
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit FAQ</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_pertanyaan">Pertanyaan *</label>
                    <input type="text" id="edit_pertanyaan" name="pertanyaan" required>
                </div>
                <div class="form-group">
                    <label for="edit_jawaban">Jawaban *</label>
                    <textarea id="edit_jawaban" name="jawaban" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_urutan">Urutan</label>
                    <input type="number" id="edit_urutan" name="urutan" min="1">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Update FAQ</button>
            </form>
        </div>
    </div>

    <script>
        function editFaq(id, pertanyaan, jawaban, urutan) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_pertanyaan').value = pertanyaan;
            document.getElementById('edit_jawaban').value = jawaban;
            document.getElementById('edit_urutan').value = urutan;
            document.getElementById('editModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>