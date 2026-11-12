<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// CEK STATUS LOGIN
if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

// INISIALISASI VARIABEL
$errors = [];
$nama = '';
$username = '';

// PROSES REGISTRASI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $nama = trim((string) ($_POST['nama'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirmation'] ?? '');

    // VALIDASI NAMA LENGKAP
    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150) {
        $errors[] = 'Nama lengkap wajib diisi (3–150 karakter).';
    }

    // VALIDASI USERNAME
    if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
        $errors[] = 'Username harus 4–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    }

    // VALIDASI PASSWORD
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }

    // VALIDASI KONFIRMASI PASSWORD
    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    // CEK USERNAME DAN SIMPAN DATA
    if (!$errors) {

        $q = $pdo->prepare(
            'SELECT id FROM users WHERE username = :u LIMIT 1'
        );

        $q->execute([
            'u' => $username
        ]);

        if ($q->fetch()) {

            $errors[] = 'Username sudah digunakan.';

        } else {

            try {

                $q = $pdo->prepare("
                    INSERT INTO users (
                        nama,
                        username,
                        password,
                        role,
                        status
                    )
                    VALUES (
                        :n,
                        :u,
                        :p,
                        \"peminjam\",
                        \"aktif\"
                    )
                ");

                $q->execute([
                    'n' => $nama,
                    'u' => $username,
                    'p' => password_hash($password, PASSWORD_DEFAULT)
                ]);

                flash(
                    'success',
                    'Pendaftaran berhasil. Silakan login.'
                );

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

    <title>Daftar Akun | KOMINFO</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="register-body">

    <main class="register-shell">

        <!-- TOMBOL KEMBALI -->
        <a class="back-link" href="login.php">
            <i data-lucide="arrow-left"></i>
            Kembali ke Login
        </a>

        <section class="register-card">

            <!-- LOGO INSTANSI -->
            <div class="brand-logos register-logos">

                <img
                    src="../assets/images/logo-kominfo.png"
                    alt="Logo KOMINFO"
                    onerror="this.style.display='none'"
                >

                <img
                    src="../assets/images/logo-pemda.png"
                    alt="Logo Pemda"
                    onerror="this.style.display='none'"
                >

                <span class="logo-fallback">
                    <i data-lucide="landmark"></i>
                    KOMINFO
                </span>

            </div>

            <!-- JUDUL REGISTRASI -->
            <span class="eyebrow">
                REGISTRASI PENGGUNA
            </span>

            <h1>Buat Akun Peminjam</h1>

            <p class="auth-description">
                Daftarkan akun untuk menggunakan layanan peminjaman
                barang multimedia.
            </p>

            <!-- PESAN ERROR -->
            <?php foreach ($errors as $err): ?>

                <div class="alert alert-error">
                    <?= e($err) ?>
                </div>

            <?php endforeach; ?>

            <!-- FORM REGISTRASI -->
            <form
                method="post"
                class="auth-form"
                id="registerForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <!-- NAMA LENGKAP -->
                <label for="nama">
                    Nama Lengkap
                </label>

                <div class="input-wrap">
                    <i data-lucide="id-card"></i>

                    <input
                        id="nama"
                        name="nama"
                        value="<?= e($nama) ?>"
                        maxlength="150"
                        required
                        placeholder="Masukkan nama lengkap"
                    >
                </div>

                <!-- USERNAME -->
                <label for="username">
                    Username
                </label>

                <div class="input-wrap">
                    <i data-lucide="user"></i>

                    <input
                        id="username"
                        name="username"
                        value="<?= e($username) ?>"
                        minlength="4"
                        maxlength="50"
                        required
                        placeholder="Buat username"
                    >
                </div>

                <!-- PASSWORD -->
                <label for="password">
                    Password
                </label>

                <div class="input-wrap">
                    <i data-lucide="lock-keyhole"></i>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        required
                        placeholder="Minimal 8 karakter"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-toggle-password="password"
                    >
                        <i data-lucide="eye"></i>
                    </button>
                </div>

                <!-- KONFIRMASI PASSWORD -->
                <label for="confirmation">
                    Konfirmasi Password
                </label>

                <div class="input-wrap">
                    <i data-lucide="shield-check"></i>

                    <input
                        id="confirmation"
                        name="confirmation"
                        type="password"
                        required
                        placeholder="Ulangi password"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-toggle-password="confirmation"
                    >
                        <i data-lucide="eye"></i>
                    </button>
                </div>

                <!-- INFORMASI ROLE -->
                <p class="muted-small">
                    Akun yang dibuat melalui halaman ini otomatis
                    memiliki role <strong>Peminjam</strong>.
                </p>

                <!-- TOMBOL DAFTAR -->
                <button
                    class="btn-primary"
                    type="submit"
                >
                    <span>Daftar Akun</span>
                    <i data-lucide="user-plus"></i>
                </button>

            </form>

            <!-- LINK LOGIN -->
            <p class="auth-switch">
                Sudah punya akun?
                <a href="login.php">Login</a>
            </p>

        </section>

        <!-- FOOTER -->
        <p class="register-footer">
            &copy; 2026 Sistem Informasi Peminjaman Barang Multimedia KOMINFO
        </p>

    </main>

    <script src="../assets/js/script.js"></script>

</body>
</html>