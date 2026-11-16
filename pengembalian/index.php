<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'pengembalian';
$pageTitle = 'Pengembalian Barang';
$pageSubtitle = 'TRANSAKSI PENGEMBALIAN';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';
$userId = (int) $_SESSION['user']['id'];

// proses konfirmasi pengembalian barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userRole === 'admin') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'proses_kembali') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);
        $barang_id = (int) ($_POST['barang_id'] ?? 0);
        $tanggal_kembali = trim((string) ($_POST['tanggal_kembali'] ?? date('Y-m-d')));
        $kondisi_kembali = trim((string) ($_POST['kondisi_kembali'] ?? ''));

        if ($peminjaman_id <= 0) {
            flash('error', 'ID peminjaman tidak valid.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE peminjaman
                SET status = 'dikembalikan', tanggal_kembali = :tgl_kembali
                WHERE id = :id AND status = 'dipinjam'
            ");
            $stmt->execute([
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : date('Y-m-d'),
                'id' => $peminjaman_id,
            ]);

            if ($barang_id > 0 && in_array($kondisi_kembali, ['Baik', 'Rusak Ringan', 'Rusak Berat'], true)) {
                $upBarang = $pdo->prepare('UPDATE barang SET kondisi = :kondisi WHERE id = :bid');
                $upBarang->execute([
                    'kondisi' => $kondisi_kembali,
                    'bid' => $barang_id,
                ]);
            }

            flash('success', 'Pengembalian barang berhasil dikonfirmasi. Unit telah kembali ke stok inventaris.');
        }
        header('Location: index.php');
        exit;
    }
}

// ambil data barang yang sedang dipinjam
$sql = "
    SELECT 
        p.id AS peminjaman_id,
        p.user_id,
        u.nama AS nama_peminjam,
        u.username,
        b.id AS barang_id,
        b.nama_barang,
        b.kategori,
        b.kondisi AS kondisi_awal,
        p.jumlah,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
    WHERE p.status = 'dipinjam'
";

$params = [];
if ($userRole === 'peminjam') {
    $sql .= " AND p.user_id = :uid";
    $params['uid'] = $userId;
}

$sql .= " ORDER BY p.tanggal_pinjam ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listKembali = $stmt->fetchAll();

// Riwayat pengembalian terbaru (limit 6)
$riwayatKembaliStmt = $pdo->prepare("
    SELECT 
        p.id,
        u.nama AS nama_peminjam,
        b.nama_barang,
        p.jumlah,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        b.kondisi
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
    WHERE p.status = 'dikembalikan' " . ($userRole === 'peminjam' ? "AND p.user_id = :uid" : "") . "
    ORDER BY p.tanggal_kembali DESC, p.created_at DESC
    LIMIT 6
");
if ($userRole === 'peminjam') {
    $riwayatKembaliStmt->execute(['uid' => $userId]);
} else {
    $riwayatKembaliStmt->execute();
}
$riwayatKembali = $riwayatKembaliStmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | IKP KOMINFO</title>
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

            <!-- daftar barang yang perlu dikembalikan -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-amber-600 uppercase">MONITORING PENGEMBALIAN</span>
                        <h2 class="text-base sm:text-xl font-bold text-[#102b4e]">Peralatan Sedang Dipinjam</h2>
                        <p class="text-xs text-slate-400 hidden sm:block">
                            <?= $userRole === 'admin' 
                                ? 'Daftar barang yang masih berada di luar. Klik Konfirmasi Kembali untuk memverifikasi fisik.' 
                                : 'Peralatan yang sedang Anda gunakan. Serahkan kembali ke meja inventaris jika liputan telah selesai.' ?>
                        </p>
                    </div>

                    <span class="text-[11px] sm:text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-lg shrink-0">
                        <?= count($listKembali) ?> belum kembali
                    </span>
                </div>

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
                                <th class="py-2.5 px-3 hidden sm:table-cell">Status</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$listKembali): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="package-check" class="w-6 h-6 text-emerald-500"></i>
                                            <span class="font-bold text-xs">Semua peralatan telah dikembalikan ke inventaris</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listKembali as $i => $row): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2.5 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[120px] sm:max-w-none"><?= e($row['nama_peminjam']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-normal sm:hidden"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></div>
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[130px] sm:max-w-none"><?= e($row['nama_barang']) ?></div>
                                            <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) $row['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden md:table-cell"><?= e($row['kategori']) ?></td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td class="py-2.5 px-3 text-slate-500 font-medium hidden lg:table-cell"><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-700 hidden md:table-cell"><?= hitung_durasi($row['tanggal_pinjam'], $row['tanggal_kembali']) ?> hari</td>
                                        <td class="py-2.5 px-3 hidden sm:table-cell">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                                Dipinjam
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <?php if ($userRole === 'admin'): ?>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 !text-white shadow-2xs transition"
                                                    onclick="bukaModalKembali(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
                                                >
                                                    <i data-lucide="check-square" class="w-3.5 h-3.5 !text-white"></i>
                                                    <span class="hidden sm:inline !text-white">Konfirmasi Terima</span>
                                                    <span class="sm:hidden !text-white">Terima</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                                    Serahkan ke Admin
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- riwayat pengembalian terakhir -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6 mb-4">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-emerald-600 uppercase">LOG SELESAI</span>
                        <h3 class="text-sm sm:text-base font-bold text-[#102b4e]">Pengembalian Terbaru</h3>
                        <p class="text-xs text-slate-400 hidden sm:block">Catatan peralatan yang telah selesai diperiksa dan dikembalikan ke gudang.</p>
                    </div>

                    <a href="../riwayat/index.php" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-blue-700 bg-white border border-slate-200 shadow-2xs transition">
                        <span>Semua Riwayat</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                <th class="py-2.5 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2.5 px-3">Peminjam</th>
                                <th class="py-2.5 px-3">Peralatan</th>
                                <th class="py-2.5 px-3 hidden sm:table-cell">Tgl. Pinjam</th>
                                <th class="py-2.5 px-3 hidden sm:table-cell">Tgl. Kembali</th>
                                <th class="py-2.5 px-3 hidden md:table-cell">Kondisi</th>
                                <th class="py-2.5 px-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$riwayatKembali): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-6 text-slate-400 text-xs">
                                        Belum ada catatan pengembalian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayatKembali as $i => $rk): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2.5 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[120px] sm:max-w-none"><?= e($rk['nama_peminjam']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-normal sm:hidden"><?= $rk['tanggal_kembali'] ? date('d/m/Y', strtotime($rk['tanggal_kembali'])) : '—' ?></div>
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[130px] sm:max-w-none"><?= e($rk['nama_barang']) ?></div>
                                            <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) ($rk['jumlah'] ?? 1) ?> unit</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($rk['tanggal_pinjam'])) ?></td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden sm:table-cell"><?= $rk['tanggal_kembali'] ? date('d/m/Y', strtotime($rk['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2.5 px-3 hidden md:table-cell">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                                <?= e($rk['kondisi']) ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                                Kembali
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

    <?php if ($userRole === 'admin'): ?>
        <!-- modal konfirmasi pengembalian -->
        <div id="modalPengembalian" class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-[#102b4e]">Konfirmasi Terima Barang</h3>
                    <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="post" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="proses_kembali">
                    <input type="hidden" name="peminjaman_id" id="kembali_peminjaman_id">
                    <input type="hidden" name="barang_id" id="kembali_barang_id">

                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 flex flex-col gap-2">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Peminjam:</span>
                            <div class="font-bold text-xs text-slate-800" id="kembali_peminjam_nama">—</div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Peralatan:</span>
                            <div class="font-bold text-xs text-[#102b4e]" id="kembali_barang_nama">—</div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Tanggal Pengembalian Fisik *</label>
                        <input type="date" name="tanggal_kembali" value="<?= date('Y-m-d') ?>" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Kondisi Fisik Saat Kembali *</label>
                        <select name="kondisi_kembali" id="kembali_kondisi" class="h-10 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                            <option value="Baik">Baik (Normal / Lengkap)</option>
                            <option value="Rusak Ringan">Rusak Ringan (Lecet / Kendala Minor)</option>
                            <option value="Rusak Berat">Rusak Berat (Perlu Diservis / Rusak)</option>
                        </select>
                        <span class="text-[10px] text-slate-400">Kondisi barang di inventaris akan otomatis disesuaikan.</span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-2">
                        <button type="button" data-modal-close class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                            Konfirmasi Barang Diterima
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function bukaModalKembali(data) {
            document.getElementById('kembali_peminjaman_id').value = data.peminjaman_id;
            document.getElementById('kembali_barang_id').value = data.barang_id;
            document.getElementById('kembali_peminjam_nama').textContent = data.nama_peminjam;
            document.getElementById('kembali_barang_nama').textContent = data.nama_barang + ' (' + data.kategori + ')';
            document.getElementById('kembali_kondisi').value = data.kondisi_awal || 'Baik';

            const modal = document.getElementById('modalPengembalian');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) lucide.createIcons();
        }
        </script>
    <?php endif; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>
