<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('admin');

$activePage = 'laporan';
$pageTitle = 'Laporan Peminjaman';
$pageSubtitle = 'REKAP & CETAK DOKUMEN';

// ============================================
// FILTER PERIODE & STATUS
// ============================================
$startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
$endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$sql = "
    SELECT 
        p.id,
        u.nama AS nama_peminjam,
        b.nama_barang,
        b.kategori,
        b.kondisi,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
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

if ($startDate !== '') {
    $sql .= " AND p.tanggal_pinjam >= :start_date";
    $params['start_date'] = $startDate;
}

if ($endDate !== '') {
    $sql .= " AND p.tanggal_pinjam <= :end_date";
    $params['end_date'] = $endDate;
}

if ($filterStatus !== '') {
    $sql .= " AND p.status = :status";
    $params['status'] = $filterStatus;
}

$sql .= " ORDER BY p.tanggal_pinjam ASC, p.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// RINGKASAN
$total = count($rows);
$selesai = 0;
$dipinjam = 0;
$ditolak = 0;

foreach ($rows as $r) {
    if ($r['status'] === 'dikembalikan') $selesai++;
    elseif ($r['status'] === 'dipinjam') $dipinjam++;
    elseif ($r['status'] === 'ditolak') $ditolak++;
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
    <style>
        .print-only { display: none; }
        @media print {
            .print-only { display: block !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="app-shell">

        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">

            <?php require __DIR__ . '/../includes/header.php'; ?>

            <!-- KOP SURAT RESMI (HANYA MUNCUL SAAT DICETAK) -->
            <div class="print-only" style="margin-bottom: 25px;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 3px double #000; padding-bottom: 12px;">
                    <img src="../assets/images/logo-sumut.png" alt="Logo Pemda" style="width: 70px; height: 70px; object-fit: contain;">
                    <div style="text-align: center;">
                        <h2 style="font-size: 14pt; margin: 0; font-weight: 800; letter-spacing: 0.5px;">PEMERINTAH PROVINSI SUMATERA UTARA</h2>
                        <h3 style="font-size: 13pt; margin: 2px 0; font-weight: 800;">DINAS KOMUNIKASI DAN INFORMATIKA</h3>
                        <p style="font-size: 9pt; margin: 0; color: #222;">BIDANG PENGELOLAAN INFORMASI DAN KOMUNIKASI PUBLIK (IKP)</p>
                        <p style="font-size: 8pt; margin: 2px 0 0; color: #444;">Jl. Pangeran Diponegoro No. 30, Medan · Website: kominfo.sumutprov.go.id</p>
                    </div>
                    <img src="../assets/images/logo-kominfo.png" alt="Logo Kominfo" style="width: 70px; height: 70px; object-fit: contain;">
                </div>

                <div style="text-align: center; margin-top: 18px; margin-bottom: 18px;">
                    <h4 style="font-size: 12pt; margin: 0; text-decoration: underline; font-weight: 800; text-transform: uppercase;">
                        LAPORAN REKAPITULASI PEMINJAMAN PERALATAN MULTIMEDIA
                    </h4>
                    <span style="font-size: 9pt; color: #333;">
                        Periode: <?= date('d F Y', strtotime($startDate)) ?> s/d <?= date('d F Y', strtotime($endDate)) ?>
                    </span>
                </div>
            </div>

            <!-- KONTEN NON-PRINT (LAYAR MONITOR) -->
            <section class="content-card no-print" style="margin-top: 24px;">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">DOKUMENTASI & ARSIP</span>
                        <h3>Filter Laporan Peminjaman</h3>
                        <p>Tentukan rentang tanggal dan status transaksi untuk mencetak atau merekap data peminjaman.</p>
                    </div>

                    <button type="button" class="btn-primary btn-sm" onclick="window.print();">
                        <i data-lucide="printer"></i>
                        <span>Cetak Laporan</span>
                    </button>
                </div>

                <form method="get" class="filter-bar" style="margin-bottom: 0;">
                    <div class="filter-group">
                        <label style="font-size: 11px; font-weight: 700; color: #344b64;">Dari:</label>
                        <input type="date" name="start_date" class="form-input" value="<?= e($startDate) ?>" required>

                        <label style="font-size: 11px; font-weight: 700; color: #344b64;">Sampai:</label>
                        <input type="date" name="end_date" class="form-input" value="<?= e($endDate) ?>" required>

                        <label style="font-size: 11px; font-weight: 700; color: #344b64;">Status:</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?= $filterStatus === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : '' ?>>Selesai Dikembalikan</option>
                            <option value="ditolak" <?= $filterStatus === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>

                        <button type="submit" class="btn-secondary btn-sm">
                            <i data-lucide="refresh-cw"></i>
                            <span>Tampilkan</span>
                        </button>
                    </div>

                    <div style="font-size: 11px; color: #718096;">
                        Total: <strong><?= $total ?></strong> transaksi
                    </div>
                </form>
            </section>

            <!-- TABEL LAPORAN (TAMPIL DI LAYAR DAN CETAK) -->
            <section class="content-card">
                <div class="no-print" style="margin-bottom: 12px; font-size: 12px; color: #64748b;">
                    Menampilkan data periode: <strong><?= date('d/m/Y', strtotime($startDate)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($endDate)) ?></strong>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Peralatan</th>
                                <th>Kategori</th>
                                <th>Tgl. Pinjam</th>
                                <th>Tgl. Kembali</th>
                                <th>Durasi</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 25px;">
                                        Tidak ada transaksi peminjaman pada periode ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $i => $r): ?>
                                    <?php
                                    $cls = match ($r['status']) {
                                        'dipinjam' => 'badge-warning',
                                        'dikembalikan' => 'badge-success',
                                        'ditolak' => 'badge-danger',
                                        default => 'badge-info',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell"><?= e($r['nama_peminjam']) ?></td>
                                        <td><?= e($r['nama_barang']) ?></td>
                                        <td><?= e($r['kategori']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '—' ?></td>
                                        <td><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?> hari</td>
                                        <td><?= e($r['kondisi']) ?></td>
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

                <!-- TANDA TANGAN (HANYA MUNCUL DI PRINT) -->
                <div class="print-only" style="margin-top: 50px; display: flex; justify-content: flex-end;">
                    <div style="text-align: center; width: 250px; font-size: 10pt;">
                        <p style="margin: 0;">Medan, <?= date('d F Y') ?></p>
                        <p style="margin: 4px 0 65px;">Pengelola Inventaris Multimedia IKP,</p>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline;">
                            <?= e($_SESSION['user']['nama']) ?>
                        </p>
                        <p style="margin: 2px 0 0; font-size: 9pt; color: #333;">NIP. ........................................</p>
                    </div>
                </div>

            </section>

            <!-- FOOTER -->
            <div class="no-print">
                <?php require __DIR__ . '/../includes/footer.php'; ?>
            </div>

        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>

