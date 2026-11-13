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
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 px-4 sm:px-8 min-h-screen flex flex-col transition-all">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 flex flex-col gap-6">

            <!-- BANNER SELAMAT DATANG -->
            <section class="rounded-2xl bg-gradient-to-r from-[#102b4e] via-[#163d6b] to-[#1e4e85] text-white p-6 sm:p-7 flex items-center justify-between shadow-sm">
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-extrabold tracking-widest text-blue-200 uppercase">RINGKASAN SISTEM</span>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">
                        Selamat datang, <?= e($_SESSION['user']['nama']) ?> 👋
                    </h2>
                    <p class="text-xs text-blue-100 max-w-lg mt-0.5">
                        Pantau inventaris dan kelola transaksi peminjaman barang multimedia Bidang IKP KOMINFO secara mudah dan terstruktur.
                    </p>
                </div>

                <div class="hidden sm:flex w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-xs items-center justify-center shrink-0">
                    <i data-lucide="monitor-check" class="w-7 h-7 text-blue-100"></i>
                </div>
            </section>

            <!-- AKSES CEPAT (SHORTCUTS) -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                    <a href="../barang/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="package" class="w-4 h-4 text-blue-600"></i>
                        <span>Kelola Barang</span>
                    </a>
                    <a href="../peminjaman/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="clipboard-list" class="w-4 h-4 text-amber-600"></i>
                        <span>Peminjaman Baru</span>
                    </a>
                    <a href="../pengembalian/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="package-check" class="w-4 h-4 text-emerald-600"></i>
                        <span>Konfirmasi Kembali</span>
                    </a>
                    <a href="../laporan/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="printer" class="w-4 h-4 text-purple-600"></i>
                        <span>Cetak Laporan</span>
                    </a>
                <?php else: ?>
                    <a href="../peminjaman/index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-[#102b4e] hover:bg-[#1a3d6b] text-white shadow-sm transition">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Ajukan Peminjaman</span>
                    </a>
                    <a href="../barang/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="package" class="w-4 h-4 text-blue-600"></i>
                        <span>Katalog Peralatan</span>
                    </a>
                    <a href="../pengembalian/index.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                        <span>Pinjaman Aktif Saya</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- GRID STATISTIK -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <article class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 flex items-center gap-4 shadow-xs">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-bold text-slate-400">Total Barang</span>
                        <strong class="text-2xl font-extrabold text-[#102b4e]"><?= number_format($totalBarang, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400">Unit terdata di sistem</small>
                    </div>
                </article>

                <article class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 flex items-center gap-4 shadow-xs">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i data-lucide="package-check" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-bold text-slate-400">Barang Tersedia</span>
                        <strong class="text-2xl font-extrabold text-[#102b4e]"><?= number_format($totalTersedia, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400">Siap dipinjam saat ini</small>
                    </div>
                </article>

                <article class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 flex items-center gap-4 shadow-xs">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i data-lucide="handshake" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-bold text-slate-400">Sedang Dipinjam</span>
                        <strong class="text-2xl font-extrabold text-[#102b4e]"><?= number_format($totalDipinjam, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400">Unit di luar kantor</small>
                    </div>
                </article>

                <article class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 flex items-center gap-4 shadow-xs">
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-bold text-slate-400">Total Transaksi</span>
                        <strong class="text-2xl font-extrabold text-[#102b4e]"><?= number_format($totalPeminjaman, 0, ',', '.') ?></strong>
                        <small class="text-[10px] text-slate-400">Seluruh catatan transaksi</small>
                    </div>
                </article>
            </section>

            <!-- TABEL AKTIVITAS TERBARU -->
            <section class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4 mb-5">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">PEMANTAUAN</span>
                        <h3 class="text-base font-bold text-[#102b4e]">Aktivitas Peminjaman Terbaru</h3>
                        <p class="text-xs text-slate-400">Daftar transaksi peralatan yang baru tercatat di sistem.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-lg">
                            <?= count($rows) ?> aktivitas
                        </span>
                        <a href="../riwayat/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-blue-700 bg-white border border-slate-200 hover:border-slate-300 shadow-2xs transition">
                            <span>Lihat Semua</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-3 w-12">No.</th>
                                <th class="py-3 px-3">Nama Peminjam</th>
                                <th class="py-3 px-3">Peralatan</th>
                                <th class="py-3 px-3">Kategori</th>
                                <th class="py-3 px-3">Tgl. Pinjam</th>
                                <th class="py-3 px-3">Rencana Kembali</th>
                                <th class="py-3 px-3">Durasi</th>
                                <th class="py-3 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
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
                                        <td class="py-3 px-3 text-slate-400"><?= $i + 1 ?></td>
                                        <td class="py-3 px-3 font-bold text-slate-800"><?= e($r['nama_peminjam']) ?></td>
                                        <td class="py-3 px-3 font-medium text-slate-700">
                                            <?= e($r['nama_barang']) ?>
                                            <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md ml-1"><?= (int) $r['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-3 px-3 text-slate-500"><?= e($r['kategori']) ?></td>
                                        <td class="py-3 px-3 text-slate-500"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td class="py-3 px-3 text-slate-500"><?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-3 px-3 font-semibold text-slate-700"><?= hitung_durasi($r['tanggal_pinjam'], $r['tanggal_kembali']) ?> hari</td>
                                        <td class="py-3 px-3">
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
            </section>

            <!-- INFORMASI PENGGUNA SAAT INI -->
            <section class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6 mb-4">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">AKUN</span>
                        <h3 class="text-base font-bold text-[#102b4e]">Informasi Pengguna Masuk</h3>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Akun Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Nama Lengkap</span>
                        <strong class="text-xs text-slate-800"><?= e($_SESSION['user']['nama']) ?></strong>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Username</span>
                        <code class="text-xs text-slate-800 font-mono"><?= e($_SESSION['user']['username']) ?></code>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Peran (Role)</span>
                        <strong class="text-xs text-slate-800"><?= ucfirst($_SESSION['user']['role']) ?></strong>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Instansi</span>
                        <strong class="text-xs text-slate-800">KOMINFO Prov. Sumut</strong>
                    </div>
                </div>
            </section>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
