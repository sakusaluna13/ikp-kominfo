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
            $cek = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
            $cek->execute(['u' => $username]);
            if ($cek->fetch()) {
                flash('error', 'Username "' . $username . '" sudah digunakan.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (nama, username, password, role, status) VALUES (:nama, :user, :pass, :role, :status)');
                $stmt->execute([
                    'nama'   => $nama,
                    'user'   => $username,
                    'pass'   => $hash,
                    'role'   => $role,
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

    // 3. UBAH ROLE
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
            $cek = $pdo->prepare('SELECT COUNT(*) FROM peminjaman WHERE user_id = :id');
            $cek->execute(['id' => $id]);
            if ($cek->fetchColumn() > 0) {
                flash('error', 'Pengguna tidak dapat dihapus karena memiliki riwayat peminjaman. Nonaktifkan saja akunnya.');
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
$search       = trim((string) ($_GET['search'] ?? ''));
$filterRole   = trim((string) ($_GET['role'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$sql = "
    SELECT 
        u.id, u.nama, u.username, u.role, u.status, u.created_at,
        COUNT(p.id) AS total_transaksi
    FROM users u
    LEFT JOIN peminjaman p ON p.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= ' AND (u.nama LIKE :search OR u.username LIKE :search)';
    $params['search'] = "%$search%";
}
if ($filterRole !== '') {
    $sql .= ' AND u.role = :role';
    $params['role'] = $filterRole;
}
if ($filterStatus !== '') {
    $sql .= ' AND u.status = :status';
    $params['status'] = $filterStatus;
}

$sql .= ' GROUP BY u.id ORDER BY u.role ASC, u.nama ASC';

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 p-4 md:p-6 space-y-6">

            <!-- CARD UTAMA -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

                <!-- Header Card -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
                    <div>
                        <p class="text-xs font-bold tracking-widest text-blue-600 uppercase mb-1">Hak Akses &amp; Akun</p>
                        <h3 class="text-lg font-bold text-slate-800">Daftar Pengguna Sistem</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Kelola hak akses administrator dan akun staf peminjam multimedia.</p>
                    </div>
                    <button type="button"
                        data-modal-open="modalTambahUser"
                        class="inline-flex items-center gap-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        Tambah Pengguna
                    </button>
                </div>

                <!-- FILTER FORM -->
                <form method="get" class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100 bg-slate-50/60">
                    <input
                        type="text"
                        name="search"
                        class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-56 bg-white"
                        placeholder="Cari nama atau username..."
                        value="<?= e($search) ?>"
                    >

                    <select name="role" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Peran (Role)</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="peminjam" <?= $filterRole === 'peminjam' ? 'selected' : '' ?>>Peminjam</option>
                    </select>

                    <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>

                    <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
                    </button>

                    <?php if ($search !== '' || $filterRole !== '' || $filterStatus !== ''): ?>
                        <a href="index.php" class="inline-flex items-center gap-1.5 border border-slate-300 hover:bg-slate-100 text-slate-600 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Reset
                        </a>
                    <?php endif; ?>

                    <span class="ml-auto text-xs text-slate-400 font-medium"><?= count($userList) ?> akun terdaftar</span>
                </form>

                <!-- TABEL -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-4 py-3 w-10">No.</th>
                                <th class="px-4 py-3">Nama Lengkap</th>
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Peran (Role)</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Total Pinjam</th>
                                <th class="px-4 py-3">Terdaftar Sejak</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$userList): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-14 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-400">
                                            <i data-lucide="users" class="w-10 h-10 opacity-30"></i>
                                            <p class="font-semibold">Tidak ada pengguna ditemukan</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userList as $i => $u): ?>
                                    <?php $isMe = ((int) $u['id'] === $currentAdminId); ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3 text-slate-400 text-xs"><?= $i + 1 ?></td>
                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            <?= e($u['nama']) ?>
                                            <?php if ($isMe): ?>
                                                <span class="ml-1.5 text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-full">(Anda)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <code class="bg-slate-100 text-slate-600 text-xs px-2 py-1 rounded-md font-mono"><?= e($u['username']) ?></code>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                                                    <i data-lucide="shield-check" class="w-3 h-3"></i> Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                                                    <i data-lucide="user" class="w-3 h-3"></i> Peminjam
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($u['status'] === 'aktif'): ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold bg-rose-50 text-rose-700 px-2.5 py-1 rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600"><?= (int) $u['total_transaksi'] ?> kali</td>
                                        <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                        <td class="px-4 py-3">
                                            <?php if (!$isMe): ?>
                                                <div class="flex items-center justify-end gap-1.5 flex-wrap">

                                                    <!-- TOGGLE STATUS -->
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                                        <?php if ($u['status'] === 'aktif'): ?>
                                                            <button type="submit"
                                                                title="Nonaktifkan Akun"
                                                                class="inline-flex items-center gap-1 text-xs font-medium bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 px-2.5 py-1.5 rounded-lg transition-colors">
                                                                <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                                                <span class="hidden sm:inline">Nonaktifkan</span>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit"
                                                                title="Aktifkan Akun"
                                                                class="inline-flex items-center gap-1 text-xs font-medium bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2.5 py-1.5 rounded-lg transition-colors">
                                                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                                                <span class="hidden sm:inline">Aktifkan</span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>

                                                    <!-- RESET PASSWORD -->
                                                    <button
                                                        type="button"
                                                        onclick="bukaResetPassword(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')"
                                                        title="Reset Password"
                                                        class="inline-flex items-center gap-1 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1.5 rounded-lg transition-colors">
                                                        <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                                    </button>

                                                    <!-- HAPUS -->
                                                    <form method="post" onsubmit="return confirm('Hapus pengguna <?= e($u['nama']) ?>?')" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <button type="submit"
                                                            title="Hapus Akun"
                                                            class="inline-flex items-center gap-1 text-xs font-medium bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-2.5 py-1.5 rounded-lg transition-colors">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>

                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 italic text-right block">Akun Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <!-- MODAL TAMBAH USER -->
    <div id="modalTambahUser" class="app-modal fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Tambah Pengguna Baru</h3>
                <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="post" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="tambah">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap Pegawai *</label>
                    <input type="text" name="nama"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Contoh: Budi Santoso" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username *</label>
                        <input type="text" name="username"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="budisantoso" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password Awal *</label>
                        <input type="password" name="password"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Min. 8 karakter" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Peran (Role) *</label>
                        <select name="role"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="peminjam">Peminjam (Staf)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Status Akun *</label>
                        <select name="status"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" data-modal-close
                        class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-[#102b4e] hover:bg-[#1a3d6b] rounded-xl transition-colors">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL RESET PASSWORD -->
    <div id="modalResetPassword" class="app-modal fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Reset Password Pengguna</h3>
                <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="post" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_user_id">

                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
                    Mengatur ulang kata sandi untuk: <strong id="reset_user_nama" class="text-amber-900">—</strong>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru *</label>
                    <input type="password" name="new_password"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Minimal 8 karakter" required>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" data-modal-close
                        class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-[#102b4e] hover:bg-[#1a3d6b] rounded-xl transition-colors">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaResetPassword(userId, userName) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('reset_user_nama').textContent = userName;

        const modal = document.getElementById('modalResetPassword');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) lucide.createIcons();
    }
    </script>

    <script src="../assets/js/script.js"></script>
</body>
</html>
