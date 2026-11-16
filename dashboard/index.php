<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'SISTEM INFORMASI';

// ============================================
// STATISTIK DASHBOARD
// ============================================
$totalBarang = (int) $pdo->query('SELECT COALESCE(SUM(jumlah), 0) FROM barang')->fetchColumn();

$totalDipinjam = (int) $pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();

$totalPeminjaman = (int) $pdo->query('SELECT COUNT(*) FROM peminjaman')->fetchColumn();

$totalTersedia = max(0, $totalBarang - $totalDipinjam);

// ============================================
// DATA PEMINJAMAN TERBARU (LIMIT 8)
// ============================================
$sql = "
    SELECT
        p.id,
        u.nama AS nama_peminjam,
        b.nama_barang,
        b.kategori,
        p.jumlah,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
";

$params = [];

if (($_SESSION['user']['role'] ?? '') === 'peminjam') {
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

            <!-- BANNER SELAMAT DATANG -->
            <section class="rounded-xl sm:rounded-2xl bg-gradient-to-r from-[#102b4e] via-[#163d6b] to-[#1e4e85] text-white p-4 sm:p-6 flex items-center justify-between shadow-xs">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[9px] font-bold tracking-widest text-blue-200 uppercase">RINGKASAN SISTEM</span>
                    <h2 class="text-base sm:text-2xl font-extrabold tracking-tight">
                        Selamat datang, <?= e($_SESSION['user']['nama']) ?> 👋
                    </h2>
                    <p class="text-xs text-blue-100 max-w-lg mt-0.5 hidden sm:block">
                        Pantau inventaris dan kelola transaksi peminjaman barang multimedia Bidang IKP KOMINFO secara mudah dan terstruktur.
                    </p>
                </div>

                <div class="hidden sm:flex w-12 h-12 rounded-xl bg-white/10 backdrop-blur-xs items-center justify-center shrink-0">
                    <i data-lucide="monitor-check" class="w-6 h-6 text-blue-100"></i>
                </div>
            </section>

            <!-- GRID STATISTIK (COMPACT ON MOBILE) -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-3 shadow-xs">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Total Barang</span>
                        <strong class="text-lg sm:text-2xl font-extrabold text-[#102b4e] leading-tight"><?= number_format($totalBarang, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400 hidden sm:block">Unit terdata di sistem</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-3 shadow-xs">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i data-lucide="package-check" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Tersedia</span>
                        <strong class="text-lg sm:text-2xl font-extrabold text-[#102b4e] leading-tight"><?= number_format($totalTersedia, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400 hidden sm:block">Siap dipinjam</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-3 shadow-xs">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i data-lucide="handshake" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Dipinjam</span>
                        <strong class="text-lg sm:text-2xl font-extrabold text-[#102b4e] leading-tight"><?= number_format($totalDipinjam, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400 hidden sm:block">Unit di luar</small>
                    </div>
                </article>

                <article class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 p-3 sm:p-5 flex items-center gap-3 shadow-xs">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-list" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400">Transaksi</span>
                        <strong class="text-lg sm:text-2xl font-extrabold text-[#102b4e] leading-tight"><?= number_format($totalPeminjaman, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400 hidden sm:block">Seluruh catatan</small>
                    </div>
                </article>
            </section>

            <!-- TABEL AKTIVITAS TERBARU -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-[#102b4e]">Aktivitas Terbaru</h3>
                        <p class="text-[11px] text-slate-400 hidden sm:block">Daftar transaksi peralatan yang baru tercatat di sistem.</p>
                    </div>

                    <a href="../riwayat/index.php" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                        <span>Lihat Semua</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <div class="w-full overflow-hidden sm:overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse table-auto">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider bg-slate-50/50 sm:bg-transparent">
                                <th class="py-2 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2 px-1.5 sm:px-3">Peminjam</th>
                                <th class="py-2 px-1.5 sm:px-3">Peralatan</th>
                                <th class="py-2 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Tgl. Pinjam</th>
                                <th class="py-2 px-3 hidden lg:table-cell">Rencana Kembali</th>
                                <th class="py-2 px-3 hidden md:table-cell">Durasi</th>
                                <th class="py-2 px-1.5 sm:px-3 text-right sm:text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="inbox" class="w-6 h-6 text-slate-300"></i>
                                            <span class="font-bold text-xs">Belum ada aktivitas peminjaman</span>
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
                                        <td class="py-2 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2 px-1.5 sm:px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[110px] sm:max-w-none text-xs"><?= e($r['nama_peminjam']) ?></div>
                                            <div class="text-[9px] text-slate-400 font-normal sm:hidden"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></div>
                                        </td>
                                        <td class="py-2 px-1.5 sm:px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[120px] sm:max-w-none text-xs"><?= e($r['nama_barang']) ?></div>
                                            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) $r['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-500 hidden md:table-cell"><?= e($r['kategori']) ?></td>
                                        <td class="py-2 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td class="py-2 px-3 text-slate-500 hidden lg:table-cell"><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2 px-3 font-semibold text-slate-700 hidden md:table-cell"><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?> hari</td>
                                        <td class="py-2 px-1.5 sm:px-3 text-right sm:text-left">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap <?= $badge ?>">
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

            <!-- INFORMASI PENGGUNA SAAT INI -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6 mb-4">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">AKUN</span>
                        <h3 class="text-sm sm:text-base font-bold text-[#102b4e]">Informasi Pengguna</h3>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Akun Aktif
                    </span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    <div class="p-3 sm:p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wide">Nama Lengkap</span>
                        <strong class="text-xs sm:text-sm text-slate-800 truncate"><?= e($_SESSION['user']['nama']) ?></strong>
                    </div>

                    <div class="p-3 sm:p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wide">Username</span>
                        <strong class="text-xs sm:text-sm text-slate-800 truncate font-semibold"><?= e($_SESSION['user']['username']) ?></strong>
                    </div>

                    <div class="p-3 sm:p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wide">Peran (Role)</span>
                        <strong class="text-xs sm:text-sm text-slate-800"><?= ucfirst($_SESSION['user']['role']) ?></strong>
                    </div>

                    <div class="p-3 sm:p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wide">Instansi</span>
                        <strong class="text-xs sm:text-sm text-slate-800 truncate">KOMINFO Prov. Sumut</strong>
                    </div>
                </div>
            </section>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
