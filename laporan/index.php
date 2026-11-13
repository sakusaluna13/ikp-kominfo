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
        p.jumlah,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status
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

$total = count($rows);
$selesai = 0; $dipinjam = 0; $ditolak = 0;
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
    <title><?= e($pageTitle) ?> | SIMPEL MEDIA KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: #fff !important; color: #000 !important; }
            .md\:ml-64 { margin-left: 0 !important; padding: 0 20px !important; }
            table { border-collapse: collapse !important; width: 100% !important; }
            th, td { border: 1px solid #999 !important; padding: 6px 8px !important; font-size: 10pt !important; color: #000 !important; }
            th { background: #eee !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <div class="no-print">
        <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <div class="md:ml-64 px-4 sm:px-8 min-h-screen flex flex-col transition-all">

        <div class="no-print">
            <?php require __DIR__ . '/../includes/header.php'; ?>
        </div>

        <!-- KOP SURAT (HANYA MUNCUL SAAT CETAK) -->
        <div class="print-only" style="margin-bottom: 20px;">
            <div style="display:flex; align-items:center; gap:18px; border-bottom:3px double #000; padding-bottom:12px;">
                <img src="../assets/images/logo-sumut.png" alt="Sumut" style="width:65px; height:65px; object-fit:contain;">
                <div style="flex:1; text-align:center;">
                    <div style="font-size:13pt; font-weight:800; text-transform:uppercase;">PEMERINTAH PROVINSI SUMATERA UTARA</div>
                    <div style="font-size:12pt; font-weight:800;">DINAS KOMUNIKASI DAN INFORMATIKA</div>
                    <div style="font-size:9pt; color:#333;">BIDANG PENGELOLAAN INFORMASI DAN KOMUNIKASI PUBLIK (IKP)</div>
                </div>
                <img src="../assets/images/logo-kominfo.png" alt="KOMINFO" style="width:65px; height:65px; object-fit:contain;">
            </div>
            <div style="text-align:center; margin-top:16px; margin-bottom:12px;">
                <div style="font-size:12pt; font-weight:800; text-decoration:underline; text-transform:uppercase;">
                    LAPORAN REKAPITULASI PEMINJAMAN PERALATAN MULTIMEDIA
                </div>
                <div style="font-size:9pt; color:#333; margin-top:4px;">
                    Periode: <?= date('d F Y', strtotime($startDate)) ?> s/d <?= date('d F Y', strtotime($endDate)) ?>
                </div>
            </div>
        </div>

        <main class="flex-1 flex flex-col gap-6">

            <!-- FILTER & OPSI CETAK (HANYA DI LAYAR) -->
            <section class="no-print bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">DOKUMENTASI & ARSIP</span>
                        <h2 class="text-xl font-bold text-[#102b4e]">Filter & Cetak Laporan</h2>
                        <p class="text-xs text-slate-400">Tentukan rentang tanggal dan status untuk menyusun laporan resmi peminjaman.</p>
                    </div>

                    <button
                        type="button"
                        onclick="window.print();"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-xs rounded-xl shadow-sm transition"
                    >
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Cetak Laporan</span>
                    </button>
                </div>

                <form method="get" class="flex flex-wrap items-end gap-3 p-3 bg-slate-50/80 rounded-xl border border-slate-100">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= e($startDate) ?>" class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= e($endDate) ?>" class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500">Status Transaksi</label>
                        <select name="status" class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?= $filterStatus === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : '' ?>>Selesai Dikembalikan</option>
                            <option value="ditolak" <?= $filterStatus === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>

                    <button type="submit" class="h-9 px-4 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition mt-auto">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Tampilkan</span>
                    </button>
                </form>

                <!-- RINGKASAN FILTER -->
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-slate-400">Total Data</span>
                        <strong class="text-lg font-extrabold text-[#102b4e]"><?= $total ?></strong>
                        <span class="text-[10px] text-slate-400">Transaksi terfilter</span>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-emerald-600">Dikembalikan</span>
                        <strong class="text-lg font-extrabold text-emerald-800"><?= $selesai ?></strong>
                        <span class="text-[10px] text-emerald-500">Transaksi selesai</span>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-100 flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-amber-600">Sedang Dipinjam</span>
                        <strong class="text-lg font-extrabold text-amber-800"><?= $dipinjam ?></strong>
                        <span class="text-[10px] text-amber-500">Masih di lapangan</span>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-100 flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-rose-600">Ditolak</span>
                        <strong class="text-lg font-extrabold text-rose-800"><?= $ditolak ?></strong>
                        <span class="text-[10px] text-rose-500">Tidak disetujui</span>
                    </div>
                </div>
            </section>

            <!-- TABEL LAPORAN (TAMPIL DI LAYAR & CETAK) -->
            <section class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6 mb-4">
                <div class="no-print mb-4 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">
                        Periode: <strong class="text-slate-700"><?= date('d/m/Y', strtotime($startDate)) ?></strong> s/d <strong class="text-slate-700"><?= date('d/m/Y', strtotime($endDate)) ?></strong>
                        <?php if ($filterStatus !== ''): ?>
                            &nbsp;·&nbsp; Status: <strong class="text-slate-700"><?= e(ucwords($filterStatus)) ?></strong>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-3 w-10">No.</th>
                                <th class="py-3 px-3">Nama Peminjam</th>
                                <th class="py-3 px-3">Peralatan Multimedia</th>
                                <th class="py-3 px-3">Kategori</th>
                                <th class="py-3 px-3">Tgl. Pinjam</th>
                                <th class="py-3 px-3">Tgl. Kembali</th>
                                <th class="py-3 px-3">Durasi</th>
                                <th class="py-3 px-3">Kondisi</th>
                                <th class="py-3 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-10 text-slate-400 text-xs">
                                        Tidak ada transaksi peminjaman pada periode dan filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $i => $r): ?>
                                    <?php
                                    $badge = match ($r['status']) {
                                        'dipinjam' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'dikembalikan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-blue-50 text-blue-700 border-blue-200',
                                    };
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-3 text-slate-400"><?= $i + 1 ?></td>
                                        <td class="py-3.5 px-3 font-bold text-slate-800"><?= e($r['nama_peminjam']) ?></td>
                                        <td class="py-3.5 px-3 font-medium text-slate-700">
                                            <?= e($r['nama_barang']) ?>
                                            <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md ml-1"><?= (int) $r['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-3.5 px-3 text-slate-500"><?= e($r['kategori']) ?></td>
                                        <td class="py-3.5 px-3 text-slate-500"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td class="py-3.5 px-3 text-slate-500"><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-3.5 px-3 font-bold text-slate-700"><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?> hari</td>
                                        <td class="py-3.5 px-3 text-slate-500"><?= e($r['kondisi']) ?></td>
                                        <td class="py-3.5 px-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>">
                                                <?= e(ucwords($r['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TANDA TANGAN (HANYA DI CETAK) -->
                <div class="print-only" style="margin-top: 50px; display: flex; justify-content: flex-end;">
                    <div style="text-align: center; min-width: 240px; font-size: 10pt;">
                        <p style="margin: 0;">Medan, <?= date('d F Y') ?></p>
                        <p style="margin: 4px 0 70px;">Pengelola Inventaris Multimedia IKP,</p>
                        <p style="margin: 0; font-weight: 800; text-decoration: underline;"><?= e($_SESSION['user']['nama']) ?></p>
                        <p style="margin: 2px 0 0; font-size: 9pt; color: #333;">NIP. ........................................</p>
                    </div>
                </div>

            </section>

        </main>

        <div class="no-print">
            <?php require __DIR__ . '/../includes/footer.php'; ?>
        </div>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
