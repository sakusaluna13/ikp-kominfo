<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$activePage = 'profile';
$pageTitle = 'Edit Profil';
$pageSubtitle = 'PENGATURAN AKUN';

$userId = (int) $_SESSION['user']['id'];

// Pastikan kolom foto ada (jalankan sekali)
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN foto VARCHAR(255) NULL DEFAULT NULL");
} catch (PDOException $e) {
    // Kolom sudah ada, abaikan
}

// Ambil data user terkini
$stmt = $pdo->prepare('SELECT id, nama, username, role, status, foto FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Pengguna tidak ditemukan.');
    header('Location: ../dashboard/index.php');
    exit;
}

// PROSES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama = trim((string) ($_POST['nama'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $errors = [];

    if (mb_strlen($nama) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
        $errors[] = 'Username harus 4–50 karakter (huruf, angka, titik, strip).';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }
    if ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Cek username duplikat
    if (!$errors) {
        $cek = $pdo->prepare('SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1');
        $cek->execute(['u' => $username, 'id' => $userId]);
        if ($cek->fetch()) {
            $errors[] = 'Username "' . $username . '" sudah dipakai pengguna lain.';
        }
    }

    // Handle upload foto
    $fotoPath = $user['foto'];
    if (!$errors && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes, true)) {
            $errors[] = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $uploadDir = __DIR__ . '/../assets/images/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = match($mimeType) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            $filename = 'user_' . $userId . '.' . $ext;
            $fullPath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                $fotoPath = '../assets/images/profile/' . $filename;
            } else {
                $errors[] = 'Gagal menyimpan foto. Coba lagi.';
            }
        }
    }

    if (!$errors) {
        // Update data
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET nama = :nama, username = :user, password = :pass, foto = :foto WHERE id = :id');
            $update->execute(['nama' => $nama, 'user' => $username, 'pass' => $hash, 'foto' => $fotoPath, 'id' => $userId]);
        } else {
            $update = $pdo->prepare('UPDATE users SET nama = :nama, username = :user, foto = :foto WHERE id = :id');
            $update->execute(['nama' => $nama, 'user' => $username, 'foto' => $fotoPath, 'id' => $userId]);
        }

        // Update session
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['photo'] = $fotoPath ? $fotoPath : '';

        flash('success', 'Profil berhasil diperbarui!');
        header('Location: index.php');
        exit;
    }

    foreach ($errors as $err) {
        flash('error', $err);
        break;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | IKP KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 px-3 sm:px-8 min-h-screen flex flex-col transition-all pb-6">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 flex flex-col gap-6 max-w-2xl w-full mx-auto">

            <!-- KARTU EDIT PROFIL -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-7">
                
                <!-- HEADER & FOTO PROFIL -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 pb-6 border-b border-slate-100">
                    <!-- FOTO DENGAN TOMBOL KAMERA -->
                    <div class="relative shrink-0">
                        <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border-2 border-slate-200" id="photoPreviewWrap">
                            <?php if ($user['foto']): ?>
                                <img id="photoPreview" src="<?= e($user['foto']) ?>?v=<?= time() ?>" class="w-full h-full object-cover" alt="Foto Profil">
                            <?php else: ?>
                                <span id="photoInitial" class="text-3xl font-bold text-slate-600"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span>
                                <img id="photoPreview" src="" class="w-full h-full object-cover hidden" alt="Foto Profil">
                            <?php endif; ?>
                        </div>
                        <label for="fotoInput" class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[#102b4e] hover:bg-[#1a3d6b] text-white flex items-center justify-center cursor-pointer shadow transition border-2 border-white" title="Ganti foto profil">
                            <i data-lucide="camera" class="w-4 h-4"></i>
                        </label>
                    </div>

                    <!-- IDENTITAS SINGKAT -->
                    <div class="text-center sm:text-left flex-1 min-w-0 pt-1">
                        <h2 class="text-lg sm:text-xl font-bold text-[#102b4e] truncate"><?= e($user['nama']) ?></h2>
                        <p class="text-xs text-slate-500 mt-0.5">@<?= e($user['username']) ?> · <?= ucfirst($user['role']) ?></p>
                        <p class="text-[11px] text-slate-400 mt-1">Dinas Komunikasi dan Informatika Prov. Sumut</p>
                    </div>
                </div>

                <!-- FORM EDIT PROFIL -->
                <form method="post" enctype="multipart/form-data" class="flex flex-col gap-5 pt-6">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/webp" class="hidden">

                    <!-- DATA UTAMA -->
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="nama" class="text-xs font-bold text-slate-700">Nama Lengkap *</label>
                            <input 
                                type="text" 
                                id="nama"
                                name="nama" 
                                value="<?= e($user['nama']) ?>"
                                class="h-10 sm:h-11 px-3.5 sm:px-4 text-xs sm:text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#102b4e] focus:ring-1 focus:ring-[#102b4e] transition"
                                placeholder="Masukkan nama lengkap"
                                required 
                                minlength="3"
                            >
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="username" class="text-xs font-bold text-slate-700">Username *</label>
                            <input 
                                type="text" 
                                id="username"
                                name="username" 
                                value="<?= e($user['username']) ?>"
                                class="h-10 sm:h-11 px-3.5 sm:px-4 text-xs sm:text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#102b4e] focus:ring-1 focus:ring-[#102b4e] transition"
                                placeholder="Masukkan username"
                                required
                            >
                            <span class="text-[10px] text-slate-400">4–50 karakter (huruf, angka, titik, strip).</span>
                        </div>
                    </div>

                    <!-- GANTI SANDI -->
                    <div class="border-t border-slate-100 pt-5 flex flex-col gap-4">
                        <div>
                            <h3 class="text-xs font-bold text-slate-700">Ganti Sandi</h3>
                            <p class="text-[11px] text-slate-400">Kosongkan kolom ini jika tidak ingin mengganti sandi.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div class="flex flex-col gap-1.5">
                                <label for="password" class="text-xs font-medium text-slate-600">Sandi Baru</label>
                                <div class="relative flex items-center">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password"
                                        minlength="8"
                                        class="w-full h-10 sm:h-11 px-3.5 pr-10 text-xs sm:text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#102b4e] focus:ring-1 focus:ring-[#102b4e] transition"
                                        placeholder="Masukkan sandi baru"
                                    >
                                    <button type="button" data-toggle-password="password" class="absolute right-3 text-slate-400 hover:text-slate-600 p-1" aria-label="Lihat sandi">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="confirm_password" class="text-xs font-medium text-slate-600">Ulangi Sandi Baru</label>
                                <div class="relative flex items-center">
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password"
                                        minlength="8"
                                        class="w-full h-10 sm:h-11 px-3.5 pr-10 text-xs sm:text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#102b4e] focus:ring-1 focus:ring-[#102b4e] transition"
                                        placeholder="Ulangi sandi baru"
                                    >
                                    <button type="button" data-toggle-password="confirm_password" class="absolute right-3 text-slate-400 hover:text-slate-600 p-1" aria-label="Lihat sandi">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div class="flex items-center gap-3 pt-3">
                        <button 
                            type="submit" 
                            class="inline-flex items-center justify-center gap-1.5 px-4 sm:px-5 py-2.5 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition whitespace-nowrap"
                        >
                            <i data-lucide="save" class="w-4 h-4 text-white"></i>
                            <span class="text-white">Simpan Perubahan</span>
                        </button>

                        <a 
                            href="../dashboard/index.php" 
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm rounded-xl transition whitespace-nowrap"
                        >
                            Batal
                        </a>
                    </div>
                </form>
            </section>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
    const fotoInput = document.getElementById('fotoInput');
    const photoPreview = document.getElementById('photoPreview');
    const photoInitial = document.getElementById('photoInitial');

    fotoInput?.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            if (photoPreview) {
                photoPreview.src = e.target.result;
                photoPreview.classList.remove('hidden');
            }
            if (photoInitial) photoInitial.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    });
    </script>
</body>
</html>
