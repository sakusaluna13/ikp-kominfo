<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'riwayat';
$pageTitle = 'Riwayat Peminjaman';
$pageSubtitle = 'HISTORI TRANSAKSI';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';
$userId = (int) $_SESSION['user']['id'];

// ============================================
// FILTER & PENCARIAN
// ============================================
$search = trim((string) ($_GET['search'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$startDate = trim((string) ($_GET['start_date'] ?? ''));
$endDate = trim((string) ($_GET['end_date'] ?? ''));

$sql = "
    SELECT 
        p.id,
        u.nama AS nama_peminjam,
        u.username,
        b.nama_barang,
        b.kategori,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
        p.created_at,
        CASE 
            WHEN p.tanggal_kembali IS NOT NULL THEN DATEDIFF(p.tanggal_kembali, p.tanggal_pinjam)
            ELSE DATEDIFF(CURDATE(), p.tanggal_pinjam)
        END AS lama_hari
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
    WHERE 1=1
";

$params = [];

if ($userRole === 'peminjam') {
    $sql .= " AND p.user_id = :uid";
    $params['uid'] = $userId;
}

if ($search !== '') {
    $sql .= " AND (b.nama_barang LIKE :search OR u.nama LIKE :search OR b.kategori LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filterStatus !== '') {
    $sql .= " AND p.status = :status";
    $params['status'] = $filterStatus;
}

if ($startDate !== '') {
    $sql .= " AND p.tanggal_pinjam >= :start_date";
    $params['start_date'] = $startDate;
}

if ($endDate !== '') {
    $sql .= " AND p.tanggal_pinjam <= :end_date";
    $params['end_date'] = $endDate;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// STATISTIK RINGKASAN
$countTotal = count($rows);
$countKembali = 0;
$countDipinjam = 0;
$countDitolak = 0;

foreach ($rows as $r) {
    if ($r['status'] === 'dikembalikan') $countKembali++;
    elseif ($r['status'] === 'dipinjam') $countDipinjam++;
    elseif ($r['status'] === 'ditolak') $countDitolak++;
}
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

            <!-- STATISTIK RINGKASAN FILTER -->
            <section class="stats-grid" style="margin-top: 24px;">
                <article class="stat-card">
                    <div class="stat-icon blue">
                        <i data-lucide="clipboard-list"></i>
                    </div>
                    <div>
                        <span>Total Transaksi</span>
                        <strong><?= $countTotal ?></strong>
                        <small>Sesuai filter pencarian</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green">
                        <i data-lucide="check-check"></i>
                    </div>
                    <div>
                        <span>Selesai Kembali</span>
                        <strong><?= $countKembali ?></strong>
                        <small>Peralatan dikembalikan</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon amber">
                        <i data-lucide="clock"></i>
                    </div>
                    <div>
                        <span>Sedang Berjalan</span>
                        <strong><?= $countDipinjam ?></strong>
                        <small>Masih berstatus dipinjam</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon violet">
                        <i data-lucide="x-circle"></i>
                    </div>
                    <div>
                        <span>Ditolak</span>
                        <strong><?= $countDitolak ?></strong>
                        <small>Pengajuan tidak disetujui</small>
                    </div>
                </article>
            </section>

            <!-- TABEL RIWAYAT TRANSAKSI -->
            <section class="content-card">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">REKAPITULASI</span>
                        <h3>Histori Semua Peminjaman</h3>
                        <p><?= $userRole === 'admin' ? 'Seluruh rekam jejak peminjaman aset multimedia KOMINFO.' : 'Rekam jejak peminjaman yang pernah Anda ajukan.' ?></p>
                    </div>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="../laporan/index.php" class="btn-primary btn-sm">
                            <i data-lucide="printer"></i>
                            <span>Cetak Laporan</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- FILTER FORM -->
                <form method="get" class="filter-bar">
                    <div class="filter-group">
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Cari peminjam atau barang..."
                            value="<?= e($search) ?>"
                            style="min-width: 190px;"
                        >

                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="menunggu persetujuan" <?= $filterStatus === 'menunggu persetujuan' ? 'selected' : '' ?>>Menunggu Persetujuan</option>
                            <option value="dipinjam" <?= $filterStatus === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                            <option value="ditolak" <?= $filterStatus === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>

                        <input
                            type="date"
                            name="start_date"
                            class="form-input"
                            title="Tanggal Mulai"
                            value="<?= e($startDate) ?>"
                        >

                        <span style="font-size: 11px; color: #8292a5;">s/d</span>

                        <input
                            type="date"
                            name="end_date"
                            class="form-input"
                            title="Tanggal Akhir"
                            value="<?= e($endDate) ?>"
                        >

                        <button type="submit" class="btn-secondary btn-sm">
                            <i data-lucide="filter"></i>
                            <span>Terapkan</span>
                        </button>

                        <?php if ($search !== '' || $filterStatus !== '' || $startDate !== '' || $endDate !== ''): ?>
                            <a href="index.php" class="btn-outline btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>

                    <span class="table-count"><?= count($rows) ?> data ditemukan</span>
                </form>

                <!-- TABEL DATA -->
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Pengembalian</th>
                                <th>Durasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i data-lucide="archive"></i>
                                            <strong>Tidak ada catatan transaksi</strong>
                                            <span>Tidak ada histori peminjaman yang cocok dengan kriteria filter.</span>
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
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell"><?= e($r['nama_peminjam']) ?></td>
                                        <td><?= e($r['nama_barang']) ?></td>
                                        <td><?= e($r['kategori']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '—' ?></td>
                                        <td><strong><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?></strong> hari</td>
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

            </section>

            <!-- FOOTER -->
            <?php require __DIR__ . '/../includes/footer.php'; ?>

        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>

