
<?php

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
        $query = $pdo->prepare(
            'SELECT id, nama, username, password, role, status
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $query->execute([
            'username' => $username
        ]);

        $user = $query->fetch();

        if (
            $user &&
            $user['status'] === 'aktif' &&
            password_verify($password, $user['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'       => (int) $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'role'     => $user['role'],
                'status'   => $user['status']
            ];

            flash(
                'success',
                'Login berhasil. Selamat datang, ' . $user['nama'] . '!'
            );

            header('Location: ../dashboard/index.php');
            exit;
        }

        $error = 'Username atau password salah, atau akun sedang nonaktif.';
    }
}

$notice = get_flash();

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Peminjaman Multimedia KOMINFO</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="auth-body">

    <div class="auth-layout">

        <section class="auth-panel">

            <div class="auth-content">

                <div class="brand-logos">
                    <img
                        src="../assets/images/logo-kominfo.png"
                        alt="Logo KOMINFO"
                        onerror="this.style.display='none'"
                    >

                    <span class="logo-fallback">
                        <i data-lucide="landmark"></i>
                        KOMINFO
                    </span>
                </div>

                <span class="eyebrow">
                    PORTAL LAYANAN INTERNAL
                </span>

                <h1>Selamat Datang</h1>

                <p class="auth-description">
                    Silakan masuk untuk mengakses Sistem Informasi
                    Peminjaman Barang Multimedia KOMINFO.
                </p>

                <?php if ($notice): ?>
                    <div class="alert alert-<?= e($notice['type']) ?>">
                        <?= e($notice['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form" id="loginForm">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <label for="username">
                        Username
                    </label>

                    <div class="input-wrap">

                        <i data-lucide="user"></i>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= e($username) ?>"
                            autocomplete="username"
                            placeholder="Masukkan username"
                            required
                        >

                    </div>

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrap">

                        <i data-lucide="lock-keyhole"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Masukkan password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-toggle-password="password"
                        >
                            <i data-lucide="eye"></i>
                        </button>

                    </div>

                    <div class="form-options">

                        <label class="check-label">
                            <input
                                type="checkbox"
                                name="remember"
                            >
                            Ingat saya
                        </label>

                        <span class="muted-small">
                            Akses resmi pengguna
                        </span>

                    </div>

                    <button class="btn-primary" type="submit">

                        <span>Login</span>

                        <i data-lucide="arrow-right"></i>

                    </button>

                </form>

                <p class="auth-switch">
                    Belum punya akun?
                    <a href="register.php">Daftar Sekarang</a>
                </p>

                <p class="auth-footer">
                    © 2026 Sistem Informasi Peminjaman Barang Multimedia
                    KOMINFO. All Rights Reserved.
                </p>

            </div>

        </section>


        <aside class="auth-visual relative overflow-hidden">

            <img
                src="../assets/images/foto-kominfo.png"
                alt="Foto Gedung KOMINFO"
                class="absolute inset-0 w-full h-full object-cover"
                onerror="this.style.display='none'"
            >

            <div class="visual-overlay"></div>

            <div class="absolute top-8 left-8 md:top-20 md:left-20 z-20">

                <div class="flex items-center gap-5">

                    <div class="relative flex flex-col items-center justify-center w-24 h-24">

                        <div
                            class="absolute inset-0 rounded-full
                                   bg-amber-300/40 blur-2xl"
                        ></div>

                        <img
                            src="../assets/images/logo-sumut.png"
                            alt="Logo Sumatera Utara"
                            class="relative z-10 w-20 h-20 object-contain
                                   drop-shadow-[0_0_15px_rgba(255,215,100,0.8)]
                                   hover:scale-110 transition-transform duration-300"
                        >

                        <span
                            class="relative z-10 text-white text-[10px]
                                   font-semibold tracking-wide drop-shadow-md"
                        >
                            SUMATERA UTARA
                        </span>

                    </div>

                </div>

            </div>

            <div class="visual-copy relative -translate-y-15">

                <span>PELAYANAN DIGITAL</span>

                <h2>
                    SISTEM INFORMASI
                    PEMINJAMAN BARANG
                    MULTIMEDIA
                </h2>

                <p>
                    Solusi digital untuk mempermudah pengelolaan,
                    peminjaman, dan pengembalian barang multimedia
                    secara terstruktur, transparan, dan efisien.
                </p>

            </div>

            <div class="visual-bottom relative -translate-y-2">
                KOMINFO · ADMINISTRASI MULTIMEDIA
            </div>

        </aside>

    </div>

    <script src="../assets/js/script.js"></script>

</body>
</html>
