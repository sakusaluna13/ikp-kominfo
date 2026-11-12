<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

// STATISTIK DASHBOARD
$totalBarang = (int) $pdo
    ->query('SELECT COALESCE(SUM(jumlah), 0) FROM barang')
    ->fetchColumn();

$totalDipinjam = (int) $pdo
    ->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")
    ->fetchColumn();

$totalPeminjaman = (int) $pdo
    ->query('SELECT COUNT(*) FROM peminjaman')
    ->fetchColumn();

$totalTersedia = max(
    0,
    $totalBarang - (int) $pdo->query("
        SELECT COALESCE(SUM(x.jumlah_pinjam), 0)
        FROM (
            SELECT barang_id, COUNT(*) AS jumlah_pinjam
            FROM peminjaman
            WHERE status = 'dipinjam'
            GROUP BY barang_id
        ) x
    ")->fetchColumn()
);

// DATA PEMINJAMAN TERBARU
$sql = "
    SELECT
        p.id,
        u.nama AS nama_peminjam,
        b.nama_barang,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
";

$params = [];

if ($_SESSION['user']['role'] === 'peminjam') {
    $sql .= ' WHERE p.user_id = :uid';
    $params['uid'] = $_SESSION['user']['id'];
}

$sql .= ' ORDER BY p.created_at DESC LIMIT 8';

$q = $pdo->prepare($sql);
$q->execute($params);

$rows = $q->fetchAll();

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard | KOMINFO</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="dashboard-body">

    <div class="app-shell">

        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">

            <?php require __DIR__ . '/../includes/header.php'; ?>

            <!-- WELCOME BANNER -->
            <section class="welcome-banner">
                <div>
                    <span class="eyebrow">RINGKASAN SISTEM</span>

                    <h2>
                        Selamat datang,
                        <?= e($_SESSION['user']['nama']) ?> 👋
                    </h2>

                    <p>
                        Pantau informasi peminjaman barang multimedia
                        dalam satu tempat.
                    </p>
                </div>

                <div class="welcome-icon">
                    <i data-lucide="monitor-check"></i>
                </div>
            </section>

            <!-- STATISTIK DASHBOARD -->
            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon blue">
                        <i data-lucide="package"></i>
                    </div>

                    <div>
                        <span>Total Barang</span>

                        <strong>
                            <?= number_format($totalBarang, 0, ',', '.') ?>
                        </strong>

                        <small>Unit barang terdata</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green">
                        <i data-lucide="package-check"></i>
                    </div>

                    <div>
                        <span>Barang Tersedia</span>

                        <strong>
                            <?= number_format($totalTersedia, 0, ',', '.') ?>
                        </strong>

                        <small>Perkiraan unit tersedia*</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon amber">
                        <i data-lucide="handshake"></i>
                    </div>

                    <div>
                        <span>Sedang Dipinjam</span>

                        <strong>
                            <?= number_format($totalDipinjam, 0, ',', '.') ?>
                        </strong>

                        <small>Transaksi berstatus dipinjam</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon violet">
                        <i data-lucide="clipboard-list"></i>
                    </div>

                    <div>
                        <span>Total Peminjaman</span>

                        <strong>
                            <?= number_format($totalPeminjaman, 0, ',', '.') ?>
                        </strong>

                        <small>Seluruh transaksi tercatat</small>
                    </div>
                </article>

            </section>

            <!-- AKTIVITAS PEMINJAMAN -->
            <section class="content-card">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">PEMANTAUAN TRANSAKSI</span>

                        <h3>Aktivitas Peminjaman Terbaru</h3>

                        <p>
                            Daftar transaksi yang tercatat di dalam sistem.
                        </p>
                    </div>

                    <span class="table-count">
                        <?= count($rows) ?> aktivitas
                    </span>
                </div>

                <div class="table-scroll">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Barang</th>
                                <th>Tanggal Peminjaman</th>
                                <th>Tanggal Pengembalian</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!$rows): ?>

                                <tr>
                                    <td colspan="6">

                                        <div class="empty-state">
                                            <i data-lucide="inbox"></i>

                                            <strong>
                                                Belum ada aktivitas peminjaman
                                            </strong>

                                            <span>
                                                Transaksi akan muncul di sini
                                                setelah tersedia.
                                            </span>
                                        </div>

                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($rows as $i => $r): ?>

                                    <?php
                                    $cls = match ($r['status']) {
                                        'dipinjam' => 'badge-warning',
                                        'dikembalikan' => 'badge-success',
                                        'menunggu persetujuan' => 'badge-info',
                                        'ditolak' => 'badge-danger',
                                        default => 'badge-neutral'
                                    };
                                    ?>

                                    <tr>
                                        <td><?= $i + 1 ?></td>

                                        <td class="name-cell">
                                            <?= e($r['nama_peminjam']) ?>
                                        </td>

                                        <td>
                                            <?= e($r['nama_barang']) ?>
                                        </td>

                                        <td>
                                            <?= date(
                                                'd/m/Y',
                                                strtotime($r['tanggal_pinjam'])
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $r['tanggal_kembali']
                                                ? date(
                                                    'd/m/Y',
                                                    strtotime($r['tanggal_kembali'])
                                                )
                                                : '—'
                                            ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= $cls ?>">
                                                <?= e(ucwords($r['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <p class="table-note">
                    *Ketersediaan dihitung sederhana dari total stok dikurangi
                    transaksi aktif; pencatatan stok per unit disiapkan pada
                    tahap berikutnya.
                </p>

            </section>

            <!-- INFORMASI PENGGUNA -->
            <section class="content-card">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">AKUN AKTIF</span>

                        <h3>Informasi Pengguna</h3>
                    </div>

                    <span class="status-badge badge-success">
                        Akun Aktif
                    </span>
                </div>

                <div class="user-info-grid">

                    <div>
                        <span>Nama Lengkap</span>

                        <strong>
                            <?= e($_SESSION['user']['nama']) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Username</span>

                        <strong>
                            <?= e($_SESSION['user']['username']) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Role</span>

                        <strong>
                            <?= e(ucfirst($_SESSION['user']['role'])) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Status Akun</span>

                        <strong>Aktif</strong>
                    </div>

                </div>

            </section>

            <!-- FOOTER -->
            <?php require __DIR__ . '/../includes/footer.php'; ?>

        </main>

    </div>

    <script src="../assets/js/script.js"></script>

</body>
</html>