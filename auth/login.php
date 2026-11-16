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
    <title>Login | IKP KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50/30 text-slate-800 min-h-screen">

    <div class="min-h-screen flex flex-col lg:grid lg:grid-cols-2">

        <!-- banner visual mobile -->
        <header class="relative lg:hidden w-full h-52 sm:h-64 overflow-hidden bg-[#102b4e] flex flex-col justify-between p-6 text-white shrink-0">
            <img 
                src="../assets/images/foto-kominfo.png" 
                alt="Kantor KOMINFO" 
                class="absolute inset-0 w-full h-full object-cover opacity-75 brightness-105"
                onerror="this.style.display='none'"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-[#0a1b30] via-[#102b4e]/60 to-[#102b4e]/30"></div>

            <div class="relative z-10 flex items-center gap-2.5">
                <img src="../assets/images/logo-sumut.png" alt="Pemda Sumut" class="h-9 w-auto object-contain drop-shadow">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold tracking-widest text-blue-200 uppercase">PEMERINTAH PROVINSI SUMATERA UTARA</span>
                    <span class="text-[9px] text-slate-300">Dinas Komunikasi dan Informatika</span>
                </div>
            </div>

            <div class="relative z-10">
                <span class="text-[9px] font-extrabold tracking-widest text-amber-300 uppercase block mb-1">
                    BIDANG PENGELOLAAN IKP
                </span>
                <h2 class="text-xl font-extrabold leading-tight text-white drop-shadow">
                    Inventaris Multimedia
                </h2>
                <p class="text-[11px] text-slate-200 mt-1 line-clamp-1">
                    Pengelolaan dan pemantauan terpadu aset multimedia KOMINFO.
                </p>
            </div>
        </header>

        <!-- panel form login -->
        <main class="flex-1 flex items-center justify-center p-4 sm:p-8 lg:p-12 -mt-6 sm:-mt-8 lg:mt-0 relative z-20">
            <div class="w-full max-w-md bg-white rounded-3xl p-6 sm:p-9 shadow-2xl lg:shadow-xl shadow-slate-300/50 lg:shadow-slate-200/60 border border-slate-100 mb-6 lg:mb-0">

                <!-- logo dan judul -->
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
                        <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase whitespace-nowrap">Inventaris Multimedia</span>
                    </div>
                </div>

                <!-- judul sambutan -->
                <div class="mb-5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200/60 text-[10px] font-extrabold text-blue-700 tracking-wider uppercase mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        Portal Layanan Internal
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#102b4e] tracking-tight">
                        Selamat Datang
                    </h1>
                    <p class="text-xs text-slate-500 leading-relaxed mt-1">
                        Silakan masuk untuk mengakses Sistem Informasi Peminjaman Inventaris Multimedia Bidang IKP KOMINFO.
                    </p>
                </div>

                <!-- notifikasi keluar -->
                <div id="logoutNotice" class="hidden p-3.5 mb-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-semibold items-center gap-2.5 shadow-2xs">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                    </div>
                    <span>Anda telah berhasil keluar dari akun.</span>
                </div>

                <?php if ($notice): ?>
                    <div class="p-3.5 mb-4 rounded-2xl bg-blue-50 border border-blue-200/80 text-blue-800 text-xs font-semibold flex items-center gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-600"></i>
                        </div>
                        <span><?= e($notice['message']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-3.5 mb-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 text-xs font-semibold flex items-center gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                        </div>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- formulir login -->
                <form method="post" id="loginForm" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="flex flex-col gap-1.5">
                        <label for="username" class="text-xs font-bold text-slate-700">Username</label>
                        <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?= e($username) ?>" 
                                class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                                placeholder="Masukkan username" 
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-xs font-bold text-slate-700">Password</label>
                        <div class="flex items-center gap-3 px-3.5 h-12 bg-slate-50/60 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100/60 transition shadow-2xs">
                            <i data-lucide="lock-keyhole" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="w-full bg-transparent text-sm font-medium text-slate-800 placeholder-slate-400 outline-none" 
                                placeholder="Masukkan sandi" 
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" data-toggle-password="password" class="p-1.5 text-slate-400 hover:text-slate-600 transition cursor-pointer" aria-label="Lihat password">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer select-none font-medium text-slate-600 hover:text-slate-800">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 accent-blue-600 cursor-pointer">
                            <span>Ingat saya</span>
                        </label>
                        <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            Akses resmi internal
                        </span>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full h-12 bg-[#102b4e] hover:bg-[#1a3d6b] active:scale-[0.99] text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 shadow-md shadow-blue-950/20 hover:shadow-lg transition duration-150 mt-1 cursor-pointer"
                    >
                        <span>Masuk ke Sistem</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <p class="text-center text-xs text-slate-500 mt-5">
                    Belum memiliki akun? 
                    <a href="register.php" class="font-bold text-blue-700 hover:text-blue-900 hover:underline transition">Daftar Sekarang</a>
                </p>

                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-[10px] text-slate-400 font-medium">
                        &copy; 2026 Bidang IKP Dinas KOMINFO Provinsi Sumatera Utara
                    </p>
                </div>

            </div>
        </main>

        <!-- panel visual samping desktop -->
        <aside class="hidden lg:flex relative overflow-hidden bg-[#102b4e] text-white p-12 flex-col justify-between">
            <img 
                src="../assets/images/foto-kominfo.png" 
                alt="Kantor KOMINFO" 
                class="absolute inset-0 w-full h-full object-cover opacity-75 brightness-105"
                onerror="this.style.display='none'"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-[#0a1b30] via-[#102b4e]/60 to-[#102b4e]/30"></div>

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
                <h2 class="text-3xl font-extrabold leading-tight tracking-tight mb-4 text-white drop-shadow-sm">
                    Sistem Informasi Peminjaman Inventaris Multimedia
                </h2>
                <p class="text-sm text-slate-200 leading-relaxed drop-shadow-xs">
                    Pengelolaan dan pemantauan terpadu untuk kamera, audio, pencahayaan, dan perangkat liputan siaran pers KOMINFO.
                </p>
            </div>

            <div class="relative z-10 text-[11px] text-slate-300 tracking-wider font-medium">
                KOMINFO · BIDANG INFORMASI DAN KOMUNIKASI PUBLIK
            </div>
        </aside>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
