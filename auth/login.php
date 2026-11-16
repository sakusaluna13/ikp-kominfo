<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $query = $pdo->prepare('SELECT id, nama, username, password, role, status, foto FROM users WHERE username = :username LIMIT 1');
            $query->execute(['username' => $username]);
            $user = $query->fetch();
        } catch (PDOException $e) {
            $query = $pdo->prepare('SELECT id, nama, username, password, role, status FROM users WHERE username = :username LIMIT 1');
            $query->execute(['username' => $username]);
            $user = $query->fetch();
        }

        if ($user && $user['status'] === 'aktif' && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'       => (int) $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'role'     => $user['role'],
                'status'   => $user['status'],
                'photo'    => $user['foto'] ?? '',
            ];

            flash('success', 'Login berhasil. Selamat datang, ' . $user['nama'] . '!');
            header('Location: ../dashboard/index.php');
            exit;
        }

        $error = 'Username atau password salah, atau akun sedang dinonaktifkan.';
    }
}

$notice = get_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SIMPEL MEDIA KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">

        <!-- PANEL FORM LOGIN (KIRI) -->
        <main class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">

                <!-- BRAND LOGO -->
                <div class="flex items-center gap-3 mb-8">
                    <img src="../assets/images/logo-kominfo.png" alt="KOMINFO" class="w-11 h-11 object-contain" onerror="this.style.display='none'">
                    <img src="../assets/images/logo-sumut.png" alt="Sumut" class="w-10 h-10 object-contain" onerror="this.style.display='none'">
                    <div class="flex flex-col">
                        <span class="font-extrabold text-[#102b4e] text-base leading-tight tracking-tight">SIMPEL MEDIA</span>
                        <span class="text-[10px] font-semibold text-slate-400 tracking-wider">KOMINFO · INVENTARIS</span>
                    </div>
                </div>

                <span class="text-[11px] font-extrabold tracking-widest text-blue-600 uppercase block mb-1">
                    PORTAL LAYANAN INTERNAL
                </span>
                <h1 class="text-3xl font-extrabold text-[#102b4e] tracking-tight mb-2">
                    Selamat Datang
                </h1>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Silakan masuk untuk mengakses Sistem Informasi Peminjaman Barang Multimedia KOMINFO.
                </p>

                <!-- NOTIFIKASI LOGOUT / NOTICE -->
                <div id="logoutNotice" class="hidden p-3.5 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600"></i>
                    <span>Anda telah berhasil keluar dari akun.</span>
                </div>

                <?php if ($notice): ?>
                    <div class="p-3.5 mb-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-medium flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 text-blue-600"></i>
                        <span><?= e($notice['message']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-3.5 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- FORM LOGIN -->
                <form method="post" id="loginForm" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="flex flex-col gap-1.5">
                        <label for="username" class="text-xs font-bold text-slate-700">Username</label>
                        <div class="flex items-center gap-3 px-3.5 h-12 bg-white border border-slate-200 rounded-xl focus-within:border-blue-500 focus-within:ring-3 focus-within:ring-blue-100 transition shadow-2xs">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?= e($username) ?>" 
                                class="w-full bg-transparent text-sm text-slate-800 placeholder-slate-400 outline-none" 
                                placeholder="Masukkan username" 
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-xs font-bold text-slate-700">Password</label>
                        <div class="flex items-center gap-3 px-3.5 h-12 bg-white border border-slate-200 rounded-xl focus-within:border-blue-500 focus-within:ring-3 focus-within:ring-blue-100 transition shadow-2xs">
                            <i data-lucide="lock-keyhole" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="w-full bg-transparent text-sm text-slate-800 placeholder-slate-400 outline-none" 
                                placeholder="Masukkan sandi" 
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" data-toggle-password="password" class="p-1 text-slate-400 hover:text-slate-600" aria-label="Lihat password">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 accent-blue-600">
                            <span>Ingat saya</span>
                        </label>
                        <span class="text-[11px] text-slate-400">Akses resmi internal</span>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full h-12 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition duration-150 mt-2"
                    >
                        <span>Masuk ke Sistem</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <p class="text-center text-xs text-slate-500 mt-6">
                    Belum memiliki akun? 
                    <a href="register.php" class="font-bold text-blue-700 hover:text-blue-900 transition">Daftar Sekarang</a>
                </p>

                <p class="text-center text-[10px] text-slate-400 mt-8">
                    &copy; 2026 Sistem Informasi Peminjaman Barang Multimedia KOMINFO
                </p>

            </div>
        </main>

        <!-- PANEL VISUAL BRANDING (KANAN) -->
        <aside class="hidden lg:flex relative overflow-hidden bg-gradient-to-br from-[#102b4e] to-[#071628] text-white p-12 flex-col justify-between">
            <img 
                src="../assets/images/foto-kominfo.png" 
                alt="Kantor KOMINFO" 
                class="absolute inset-0 w-full h-full object-cover opacity-25 mix-blend-overlay"
                onerror="this.style.display='none'"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-[#091b32] via-[#102b4e]/85 to-transparent"></div>

            <div class="relative z-10 flex items-center gap-3">
                <img src="../assets/images/logo-sumut.png" alt="Pemda Sumut" class="w-12 h-12 object-contain drop-shadow">
                <div class="flex flex-col">
                    <span class="text-xs font-bold tracking-widest text-blue-200">PEMERINTAH PROVINSI SUMATERA UTARA</span>
                    <span class="text-[11px] text-slate-300">Dinas Komunikasi dan Informatika</span>
                </div>
            </div>

            <div class="relative z-10 max-w-lg mb-8">
                <span class="text-xs font-extrabold tracking-widest text-amber-300 uppercase block mb-3">
                    BIDANG PENGELOLAAN IKP
                </span>
                <h2 class="text-3xl font-extrabold leading-tight tracking-tight mb-4">
                    Sistem Informasi Peminjaman Inventaris Multimedia
                </h2>
                <p class="text-sm text-slate-200 leading-relaxed">
                    Pengelolaan dan pemantauan terpadu untuk kamera, audio, pencahayaan, dan perangkat liputan siaran pers KOMINFO.
                </p>
            </div>

            <div class="relative z-10 text-[11px] text-slate-400 tracking-wider">
                KOMINFO · BIDANG INFORMASI DAN KOMUNIKASI PUBLIK
            </div>
        </aside>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
