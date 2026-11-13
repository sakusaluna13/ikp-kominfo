<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('admin');

$activePage = 'users';
$pageTitle = 'Manajemen Pengguna';
$pageSubtitle = 'PENGELOLAAN AKUN SISTEM';

$currentAdminId = (int) $_SESSION['user']['id'];

// ============================================
// PROSES AKSI POST (ADMIN ONLY)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // 1. TAMBAH PENGGUNA BARU
    if ($action === 'tambah') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'peminjam'], true) ? $_POST['role'] : 'peminjam';
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? $_POST['status'] : 'aktif';

        if (mb_strlen($nama) < 3) {
            flash('error', 'Nama lengkap minimal 3 karakter.');
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
            flash('error', 'Username harus 4–50 karakter (huruf, angka, titik, strip).');
        } elseif (strlen($password) < 8) {
            flash('error', 'Password minimal 8 karakter.');
        } else {
            // Cek username
            $cek = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
            $cek->execute(['u' => $username]);
            if ($cek->fetch()) {
                flash('error', 'Username "' . $username . '" sudah digunakan.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (nama, username, password, role, status) VALUES (:nama, :user, :pass, :role, :status)');
                $stmt->execute([
                    'nama' => $nama,
                    'user' => $username,
                    'pass' => $hash,
                    'role' => $role,
                    'status' => $status,
                ]);
                flash('success', 'Pengguna baru "' . $nama . '" berhasil didaftarkan.');
            }
        }
        header('Location: index.php');
        exit;
    }

    // 2. TOGGLE STATUS (AKTIF / NONAKTIF)
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $statusNow = (string) ($_POST['current_status'] ?? '');

        if ($id === $currentAdminId) {
            flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.');
        } else {
            $newStatus = ($statusNow === 'aktif') ? 'nonaktif' : 'aktif';
            $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            flash('success', 'Status akun berhasil diubah menjadi: ' . ucfirst($newStatus) . '.');
        }
        header('Location: index.php');
        exit;
    }

    // 3. UBAH ROLE (ADMIN / PEMINJAM)
    if ($action === 'ubah_role') {
        $id = (int) ($_POST['id'] ?? 0);
        $newRole = in_array($_POST['new_role'] ?? '', ['admin', 'peminjam'], true) ? $_POST['new_role'] : 'peminjam';

        if ($id === $currentAdminId) {
            flash('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute(['role' => $newRole, 'id' => $id]);
            flash('success', 'Hak akses (role) pengguna berhasil diubah menjadi: ' . ucfirst($newRole) . '.');
        }
        header('Location: index.php');
        exit;
    }

    // 4. RESET PASSWORD
    if ($action === 'reset_password') {
        $id = (int) ($_POST['id'] ?? 0);
        $newPass = (string) ($_POST['new_password'] ?? '');

        if (strlen($newPass) < 8) {
            flash('error', 'Password baru minimal 8 karakter.');
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
            $stmt->execute(['p' => $hash, 'id' => $id]);
            flash('success', 'Password pengguna berhasil direset.');
        }
        header('Location: index.php');
        exit;
    }

    // 5. HAPUS PENGGUNA
    if ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id === $currentAdminId) {
            flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        } else {
            // Cek apakah punya riwayat transaksi
            $cek = $pdo->prepare('SELECT COUNT(*) FROM peminjaman WHERE user_id = :id');
            $cek->execute(['id' => $id]);
            if ($cek->fetchColumn() > 0) {
                flash('error', 'Pengguna tidak dapat dihapus karena memiliki riwayat peminjaman. Anda dapat menonaktifkan status akunnya saja.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $id]);
                flash('success', 'Akun pengguna berhasil dihapus.');
            }
        }
        header('Location: index.php');
        exit;
    }
}

// ============================================
// DATA PENGGUNA
// ============================================
$search = trim((string) ($_GET['search'] ?? ''));
$filterRole = trim((string) ($_GET['role'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$sql = "
    SELECT 
        u.id,
        u.nama,
        u.username,
        u.role,
        u.status,
        u.created_at,
        COUNT(p.id) AS total_transaksi
    FROM users u
    LEFT JOIN peminjaman p ON p.user_id = u.id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.nama LIKE :search OR u.username LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filterRole !== '') {
    $sql .= " AND u.role = :role";
    $params['role'] = $filterRole;
}

if ($filterStatus !== '') {
    $sql .= " AND u.status = :status";
    $params['status'] = $filterStatus;
}

$sql .= " GROUP BY u.id ORDER BY u.role ASC, u.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$userList = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | KOMINFO</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-body">

    <div class="app-shell">

        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">

            <?php require __DIR__ . '/../includes/header.php'; ?>

            <section class="content-card" style="margin-top: 24px;">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">HAK AKSES & AKUN</span>
                        <h3>Daftar Pengguna Sistem</h3>
                        <p>Kelola hak akses administrator dan akun staf peminjam multimedia.</p>
                    </div>

                    <button type="button" class="btn-primary btn-sm" data-modal-open="modalTambahUser">
                        <i data-lucide="user-plus"></i>
                        <span>Tambah Pengguna</span>
                    </button>
                </div>

                <!-- FILTER FORM -->
                <form method="get" class="filter-bar">
                    <div class="filter-group">
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Cari nama atau username..."
                            value="<?= e($search) ?>"
                            style="min-width: 200px;"
                        >

                        <select name="role" class="form-select">
                            <option value="">Semua Peran (Role)</option>
                            <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="peminjam" <?= $filterRole === 'peminjam' ? 'selected' : '' ?>>Peminjam</option>
                        </select>

                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>

                        <button type="submit" class="btn-secondary btn-sm">
                            <i data-lucide="filter"></i>
                            <span>Filter</span>
                        </button>

                        <?php if ($search !== '' || $filterRole !== '' || $filterStatus !== ''): ?>
                            <a href="index.php" class="btn-outline btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>

                    <span class="table-count"><?= count($userList) ?> akun terdaftar</span>
                </form>

                <!-- TABEL PENGGUNA -->
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Peran (Role)</th>
                                <th>Status</th>
                                <th>Total Pinjam</th>
                                <th>Terdaftar Sejak</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$userList): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i data-lucide="users"></i>
                                            <strong>Tidak ada pengguna ditemukan</strong>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userList as $i => $u): ?>
                                    <?php
                                    $isMe = ((int) $u['id'] === $currentAdminId);
                                    $roleBadge = ($u['role'] === 'admin') ? 'badge-info' : 'badge-neutral';
                                    $statusBadge = ($u['status'] === 'aktif') ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell">
                                            <?= e($u['nama']) ?>
                                            <?php if ($isMe): ?>
                                                <span style="font-size: 9px; color: #2563a6; font-weight: 700; margin-left: 5px;">(Anda)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= e($u['username']) ?></code></td>
                                        <td>
                                            <span class="status-badge <?= $roleBadge ?>">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $statusBadge ?>">
                                                <?= ucfirst($u['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= (int) $u['total_transaksi'] ?> kali</td>
                                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                        <td style="text-align: right;">
                                            <div class="action-group" style="justify-content: flex-end;">
                                                <?php if (!$isMe): ?>
                                                    <!-- TOGGLE STATUS AKTIF / NONAKTIF -->
                                                    <form method="post" style="margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                                        <button 
                                                            type="submit" 
                                                            class="<?= $u['status'] === 'aktif' ? 'btn-secondary' : 'btn-success' ?> btn-sm"
                                                            title="<?= $u['status'] === 'aktif' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>"
                                                        >
                                                            <i data-lucide="<?= $u['status'] === 'aktif' ? 'user-x' : 'user-check' ?>"></i>
                                                            <span><?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></span>
                                                        </button>
                                                    </form>

                                                    <!-- RESET PASSWORD BUTTON -->
                                                    <button
                                                        type="button"
                                                        class="btn-outline btn-sm"
                                                        onclick="bukaResetPassword(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')"
                                                        title="Reset Password"
                                                    >
                                                        <i data-lucide="key"></i>
                                                    </button>

                                                    <!-- HAPUS AKUN -->
                                                    <form method="post" onsubmit="return confirm('Hapus pengguna <?= e($u['nama']) ?>?');" style="margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm" title="Hapus Akun">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size: 10px; color: #94a3b8; font-style: italic;">Akun Aktif</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- FOOTER -->
            <?php require __DIR__ . '/../includes/footer.php'; ?>

        </main>
    </div>

    <!-- MODAL TAMBAH USER -->
    <div class="app-modal" id="modalTambahUser">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Pengguna Baru</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="tambah">

                <div class="form-field">
                    <label>Nama Lengkap Pegawai *</label>
                    <input type="text" name="nama" class="form-input" placeholder="Contoh: Budi Santoso" required>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-input" placeholder="budisantoso" required>
                    </div>

                    <div class="form-field">
                        <label>Password Awal *</label>
                        <input type="password" name="password" class="form-input" placeholder="Min. 8 karakter" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Peran (Role) *</label>
                        <select name="role" class="form-select">
                            <option value="peminjam">Peminjam (Pegawai / Staf)</option>
                            <option value="admin">Administrator (Pengelola Inventaris)</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Status Akun *</label>
                        <select name="status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary btn-sm" data-modal-close>Batal</button>
                    <button type="submit" class="btn-primary btn-sm">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL RESET PASSWORD -->
    <div class="app-modal" id="modalResetPassword">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reset Password Pengguna</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_user_id">

                <p style="font-size: 12px; color: #475569; margin: 0 0 14px;">
                    Mengatur ulang kata sandi untuk akun: <strong id="reset_user_nama">—</strong>
                </p>

                <div class="form-field">
                    <label>Password Baru *</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Minimal 8 karakter" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary btn-sm" data-modal-close>Batal</button>
                    <button type="submit" class="btn-primary btn-sm">Perbarui Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaResetPassword(userId, userName) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('reset_user_nama').textContent = userName;

        const modal = document.getElementById('modalResetPassword');
        modal.classList.add('show');
        if (window.lucide) lucide.createIcons();
    }
    </script>

    <script src="../assets/js/script.js"></script>
</body>
</html>

