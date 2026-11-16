<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$errors = [];
$nama = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama = trim((string) ($_POST['nama'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirmation'] ?? '');

    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150) {
        $errors[] = 'Nama lengkap wajib diisi (3–150 karakter).';
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
        $errors[] = 'Username harus 4–50 karakter dan hanya boleh berisi huruf, angka, titik, strip, atau garis bawah.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $q = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $q->execute(['u' => $username]);

        if ($q->fetch()) {
            $errors[] = 'Username "' . $username . '" sudah digunakan.';
        } else {
            try {
                $q = $pdo->prepare('INSERT INTO users (nama, username, password, role, status) VALUES (:n, :u, :p, "peminjam", "aktif")');
                $q->execute([
                    'n' => $nama,
                    'u' => $username,
                    'p' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                flash('success', 'Pendaftaran berhasil. Silakan masuk menggunakan akun baru Anda.');
                header('Location: login.php');
                exit;
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Pendaftaran gagal. Silakan coba kembali.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Akun | IKP KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50/30 text-slate-800 min-h-screen flex items-center justify-center p-4 sm:p-8">

    <main class="w-full max-w-lg my-4">

        <!-- KEMBALI KE LOGIN -->
        <a href="login.php" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-blue-700 transition mb-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Halaman Login</span>
        </a>

        <!-- KARTU FORM REGISTRASI -->
        <section class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/60 p-6 sm:p-9">

            <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
                <div class="flex items-center gap-2 shrink-0">
                    <img 
                        src="../assets/images/logo-kominfo.png" 
                        alt="KOMINFO" 
                        class="h-8 sm:h-9 w-auto object-contain" 
                        onerror="this.style.display='none'"
                    >
                    <img 
                        src="../assets/images/logo-sumut.png" 
                        alt="Pemda Sumut" 
                        class="h-9 sm:h-10 w-auto object-contain" 
                        onerror="this.style.display='none'"
                    >
                </div>
                <div class="h-8 w-px bg-slate-200/80"></div>
                <div class="flex flex-col min-w-0">
                    <span class="font-extrabold text-[#102b4e] text-base leading-tight tracking-tight">IKP KOMINFO</span>
                    <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase whitespace-nowrap">Registrasi Pegawai</span>
                </div>
            </div>

            <div class="mb-5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200/60 text-[10px] font-extrabold text-blue-700 tracking-wider uppercase mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                    Akun Peminjam Multimedia
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#102b4e] tracking-tight">
                    Buat Akun Baru
                </h1>
                <p class="text-xs text-slate-500 leading-relaxed mt-1">
                    Daftarkan akun staf/pegawai untuk mengajukan peminjaman aset multimedia Bidang IKP KOMINFO.
                </p>
            </div>

            <!-- DAFTAR ERROR -->
            <?php if ($errors): ?>
                <div class="p-3.5 mb-5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 text-xs font-semibold flex flex-col gap-1.5 shadow-2xs">
                    <?php foreach ($errors as $err): ?>
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                            <span><?= e($err) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form method="post" id="registerForm" class="flex flex-col gap-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <!-- NAMA LENGKAP -->
                <div class="flex flex-col gap-1.5">
                    <label for="nama" class="text-xs font-bold text-slate-700">Nama Lengkap Pegawai *</label>
                    <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                        <i data-lucide="id-card" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <input 
                            type="text" 
                            id="nama" 
                            name="nama" 
                            value="<?= e($nama) ?>" 
                            maxlength="150"
                            class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                            placeholder="Masukkan nama lengkap" 
                            required
                        >
                    </div>
                </div>

                <!-- USERNAME -->
                <div class="flex flex-col gap-1.5">
                    <label for="username" class="text-xs font-bold text-slate-700">Username *</label>
                    <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= e($username) ?>" 
                            minlength="4"
                            maxlength="50"
                            class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                            placeholder="Masukkan username" 
                            required
                        >
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-xs font-bold text-slate-700">Password (Min. 8 karakter) *</label>
                    <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                        <i data-lucide="lock-keyhole" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            minlength="8"
                            class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                            placeholder="Masukkan sandi" 
                            required
                        >
                        <button type="button" data-toggle-password="password" class="p-1.5 text-slate-400 hover:text-slate-600 transition cursor-pointer" aria-label="Lihat password">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- KONFIRMASI PASSWORD -->
                <div class="flex flex-col gap-1.5">
                    <label for="confirmation" class="text-xs font-bold text-slate-700">Ulangi Password *</label>
                    <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                        <i data-lucide="shield-check" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <input 
                            type="password" 
                            id="confirmation" 
                            name="confirmation" 
                            minlength="8"
                            class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                            placeholder="Ulangi sandi" 
                            required
                        >
                        <button type="button" data-toggle-password="confirmation" class="p-1.5 text-slate-400 hover:text-slate-600 transition cursor-pointer" aria-label="Lihat password">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full h-12 bg-[#102b4e] hover:bg-[#1a3d6b] active:scale-[0.99] text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 shadow-md shadow-blue-950/20 hover:shadow-lg transition duration-150 mt-2 cursor-pointer"
                >
                    <span>Daftarkan Akun Sekarang</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <p class="text-center text-xs text-slate-500 mt-5">
                Sudah memiliki akun? 
                <a href="login.php" class="font-bold text-blue-700 hover:text-blue-900 hover:underline transition">Masuk di sini</a>
            </p>

        </section>

        <p class="text-center text-[10px] text-slate-400 mt-6">
            &copy; 2026 Bidang IKP Dinas KOMINFO Provinsi Sumatera Utara
        </p>

    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>
