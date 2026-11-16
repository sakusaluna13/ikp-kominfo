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
// FILTER & QUERY PENCARIAN
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
        p.jumlah,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
        p.created_at
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
    <title><?= e($pageTitle) ?> | SIMPEL MEDIA KOMINFO</title>
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

        <main class="flex-1 flex flex-col gap-4 sm:gap-6">

            <!-- STATISTIK RINGKASAN -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-2.5 sm:gap-4 shadow-xs">
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-list" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Total</span>
                        <strong class="text-base sm:text-2xl font-extrabold text-[#102b4e]"><?= $countTotal ?></strong>
                        <small class="text-[9px] text-slate-400 hidden sm:block">Sesuai filter</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-2.5 sm:gap-4 shadow-xs">
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i data-lucide="check-check" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Kembali</span>
                        <strong class="text-base sm:text-2xl font-extrabold text-[#102b4e]"><?= $countKembali ?></strong>
                        <small class="text-[9px] text-slate-400 hidden sm:block">Selesai</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-2.5 sm:gap-4 shadow-xs">
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i data-lucide="clock" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Dipinjam</span>
                        <strong class="text-base sm:text-2xl font-extrabold text-[#102b4e]"><?= $countDipinjam ?></strong>
                        <small class="text-[9px] text-slate-400 hidden sm:block">Berjalan</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-2.5 sm:gap-4 shadow-xs">
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Ditolak</span>
                        <strong class="text-base sm:text-2xl font-extrabold text-[#102b4e]"><?= $countDitolak ?></strong>
                        <small class="text-[9px] text-slate-400 hidden sm:block">Dibatalkan</small>
                    </div>
                </article>
            </section>

            <!-- KONTEN TABEL RIWAYAT -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">REKAPITULASI</span>
                        <h2 class="text-base sm:text-xl font-bold text-[#102b4e]">Histori Peminjaman</h2>
                        <p class="text-xs text-slate-400 hidden sm:block">
                            <?= $userRole === 'admin' 
                                ? 'Seluruh rekam jejak transaksi peminjaman aset multimedia KOMINFO.' 
                                : 'Rekam jejak peminjaman peralatan yang pernah Anda ajukan.' ?>
                        </p>
                    </div>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="../laporan/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-xs rounded-xl shadow-xs transition self-start sm:self-auto">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>Cetak Laporan</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- FORM FILTER PENCARIAN -->
                <form method="get" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-4 p-2.5 sm:p-3 bg-slate-50/80 rounded-xl border border-slate-100">
                    <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                        <input
                            type="text"
                            name="search"
                            class="col-span-2 sm:col-span-1 h-8 sm:h-9 px-3 text-xs bg-white border border-slate-200 rounded-lg sm:rounded-xl focus:outline-none focus:border-blue-500 w-full sm:w-48"
                            placeholder="Cari peminjam / barang..."
                            value="<?= e($search) ?>"
                        >

                        <select name="status" class="col-span-2 sm:col-span-1 h-8 sm:h-9 px-2 text-xs bg-white border border-slate-200 rounded-lg sm:rounded-xl focus:outline-none focus:border-blue-500">
                            <option value="">Semua Status</option>
                            <option value="menunggu persetujuan" <?= $filterStatus === 'menunggu persetujuan' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="dipinjam" <?= $filterStatus === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                            <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                            <option value="ditolak" <?= $filterStatus === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>

                        <div class="col-span-2 sm:col-span-1 flex items-center gap-1 text-xs w-full sm:w-auto">
                            <input
                                type="date"
                                name="start_date"
                                class="flex-1 sm:w-28 h-8 sm:h-9 px-2 text-[11px] sm:text-xs bg-white border border-slate-200 rounded-lg sm:rounded-xl focus:outline-none focus:border-blue-500"
                                value="<?= e($startDate) ?>"
                                title="Tanggal Mulai"
                            >
                            <span class="text-slate-400 shrink-0">–</span>
                            <input
                                type="date"
                                name="end_date"
                                class="flex-1 sm:w-28 h-8 sm:h-9 px-2 text-[11px] sm:text-xs bg-white border border-slate-200 rounded-lg sm:rounded-xl focus:outline-none focus:border-blue-500"
                                value="<?= e($endDate) ?>"
                                title="Tanggal Akhir"
                            >
                        </div>

                        <div class="col-span-2 sm:col-span-1 flex items-center gap-1.5">
                            <button type="submit" class="flex-1 sm:flex-none h-8 sm:h-9 px-3 bg-[#102b4e] hover:bg-[#1a3d6b] !text-white font-bold text-xs rounded-lg sm:rounded-xl flex items-center justify-center gap-1 transition">
                                <i data-lucide="filter" class="w-3.5 h-3.5 !text-white"></i>
                                <span class="!text-white">Filter</span>
                            </button>

                            <?php if ($search !== '' || $filterStatus !== '' || $startDate !== '' || $endDate !== ''): ?>
                                <a href="index.php" class="h-8 sm:h-9 px-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-lg sm:rounded-xl flex items-center justify-center transition">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500">
                        Total: <strong><?= count($rows) ?></strong> data
                    </span>
                </form>

                <!-- TABEL DATA -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                <th class="py-2.5 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2.5 px-3">Peminjam</th>
                                <th class="py-2.5 px-3">Peralatan</th>
                                <th class="py-2.5 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2.5 px-3 hidden sm:table-cell">Tgl. Pinjam</th>
                                <th class="py-2.5 px-3 hidden lg:table-cell">Kembali</th>
                                <th class="py-2.5 px-3 hidden md:table-cell">Durasi</th>
                                <th class="py-2.5 px-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="archive" class="w-6 h-6 text-slate-300"></i>
                                            <span class="font-bold text-xs">Tidak ada catatan transaksi ditemukan</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $i => $r): ?>
                                    <?php
                                    $badge = match ($r['status']) {
                                        'dipinjam' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'dikembalikan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'menunggu persetujuan' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200'
                                    };
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2.5 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[120px] sm:max-w-none"><?= e($r['nama_peminjam']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-normal sm:hidden"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></div>
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[130px] sm:max-w-none"><?= e($r['nama_barang']) ?></div>
                                            <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) $r['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden md:table-cell"><?= e($r['kategori']) ?></td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td class="py-2.5 px-3 text-slate-500 font-medium hidden lg:table-cell"><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-700 hidden md:table-cell"><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?> hari</td>
                                        <td class="py-2.5 px-3 text-right">
                                            <?php
                                            $statusLabel = match ($r['status']) {
                                                'menunggu persetujuan' => 'Menunggu',
                                                'dipinjam' => 'Dipinjam',
                                                'dikembalikan' => 'Kembali',
                                                'ditolak' => 'Ditolak',
                                                default => ucwords($r['status'])
                                            };
                                            ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap shrink-0 <?= $badge ?>">
                                                <?= e($statusLabel) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
