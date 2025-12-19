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
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $noTelepon = trim($_POST['no_telepon']);
        
        // Check email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            setFlashMessage('error', 'Email sudah terdaftar');
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, no_telepon) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $email, $password, $role, $noTelepon);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'User berhasil ditambahkan');
            }
        }
        redirect('pengguna.php');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Prevent deleting self
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('error', 'Tidak dapat menghapus akun sendiri');
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'User berhasil dihapus');
            }
        }
        redirect('pengguna.php');
    }
    
    if ($action === 'change_role') {
        $id = intval($_POST['id']);
        $newRole = $_POST['new_role'];
        
        if ($id == $_SESSION['user_id']) {
            setFlashMessage('error', 'Tidak dapat mengubah role sendiri');
        } else {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $newRole, $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Role berhasil diubah');
            }
        }
        redirect('pengguna.php');
    }
}

// Get users
$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];
$types = '';

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if ($search) {
    $where[] = "(nama LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM users $whereClause ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Dapur RR</title>
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
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
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
            <a href="pengguna.php" class="menu-item active">👤 Pengguna</a>
            <a href="pengaturan.php" class="menu-item">⚙️ Pengaturan</a>
            <a href="../logout.php" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Kelola Pengguna</h1>
            <div class="user-info">
                <span>👤 <?php echo getUserName(); ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="">Semua Role</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari</label>
                        <input type="text" name="search" placeholder="Nama atau email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <strong><?php echo $users->num_rows; ?></strong> Pengguna
                </div>
                <button onclick="showAddModal()" class="btn btn-primary">➕ Tambah User</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($u['nama'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['nama']); ?></strong>
                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge badge-success">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['no_telepon']); ?></td>
                            <td>
                                <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="changeRole(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')" 
                                            class="btn btn-warning btn-sm">
                                        🔄 Role
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Yakin hapus user ini?')">
                                            🗑️
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color: #999; font-size: 0.85rem;">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem;">
                                Tidak ada user ditemukan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah User Baru</h3>
                <span class="modal-close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="nama">Nama Lengkap *</label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="no_telepon">No. Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon">
                </div>
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Tambah User</button>
            </form>
        </div>
    </div>

    <!-- Modal Change Role -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ubah Role User</h3>
                <span class="modal-close" onclick="closeRoleModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="id" id="role_user_id">
                <div class="form-group">
                    <label for="new_role">Role Baru *</label>
                    <select id="new_role" name="new_role" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Ubah Role</button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.add('show');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }
        
        function changeRole(userId, currentRole) {
            document.getElementById('role_user_id').value = userId;
            document.getElementById('new_role').value = currentRole === 'admin' ? 'customer' : 'admin';
            document.getElementById('roleModal').classList.add('show');
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>