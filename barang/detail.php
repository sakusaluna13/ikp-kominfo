<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../dashboard/index.php'); exit; }

// proses pengajuan pinjam langsung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    verify_csrf();

    $userId     = (int) $_SESSION['user']['id'];
    $barangId   = (int) ($_POST['barang_id'] ?? 0);
    $tglKembali = trim((string) ($_POST['tanggal_kembali'] ?? ''));
    $jumlah     = max(1, (int) ($_POST['jumlah'] ?? 1));
    $tglPinjam  = date('Y-m-d');

    if ($barangId !== $id) {
        flash('error', 'Data tidak valid.');
    } elseif (empty($tglKembali) || $tglKembali < $tglPinjam) {
        flash('error', 'Tanggal kembali tidak boleh sebelum tanggal pinjam.');
    } else {
        // Hitung stok tersedia saat ini
        $cekStok = $pdo->prepare("
            SELECT (b.jumlah - COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0)) AS sisa
            FROM barang b
            LEFT JOIN peminjaman p ON p.barang_id = b.id AND p.status = 'dipinjam'
            WHERE b.id = :id
            GROUP BY b.id
        ");
        $cekStok->execute(['id' => $barangId]);
        $sisa = (int) $cekStok->fetchColumn();

        if ($jumlah > $sisa || $sisa <= 0) {
            flash('error', 'Jumlah melebihi stok tersedia (' . $sisa . ' unit).');
        } else {
            $ins = $pdo->prepare("INSERT INTO peminjaman (user_id, barang_id, jumlah, tanggal_pinjam, tanggal_kembali, status)
                                  VALUES (:uid, :bid, :jumlah, :tgl_pinjam, :tgl_kembali, 'menunggu persetujuan')");
            $ins->execute([
                'uid'         => $userId,
                'bid'         => $barangId,
                'jumlah'      => $jumlah,
                'tgl_pinjam'  => $tglPinjam,
                'tgl_kembali' => $tglKembali,
            ]);
            flash('success', 'Pengajuan ' . $jumlah . ' unit berhasil dikirim! Menunggu persetujuan Admin.');
            header('Location: ../peminjaman/index.php');
            exit;
        }
    }
    header('Location: detail.php?id=' . $id);
    exit;
}

// data barang dan status ketersediaan
$stmt = $pdo->prepare("
    SELECT
        b.id, b.nama_barang, b.kategori, b.jumlah, b.kondisi, b.created_at,
        COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0) AS sedang_dipinjam,
        GREATEST(0, b.jumlah - COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0)) AS sisa_tersedia
    FROM barang b
    LEFT JOIN peminjaman p ON p.barang_id = b.id AND p.status = 'dipinjam'
    WHERE b.id = :id
    GROUP BY b.id
");
$stmt->execute(['id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    http_response_code(404);
    echo '<!doctype html><html><head><script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script></head>
    <body class="min-h-screen flex items-center justify-center bg-slate-100">
    <div class="text-center p-8"><div class="text-6xl mb-4">❌</div>
    <h1 class="text-xl font-bold text-slate-800 mb-2">Barang Tidak Ditemukan</h1>
    <p class="text-slate-500 text-sm">ID barang tidak valid atau sudah dihapus.</p></div></body></html>';
    exit;
}

// Siapa yang sedang meminjam
$peminjamStmt = $pdo->prepare("
    SELECT p.id, u.nama, u.username, p.jumlah, p.tanggal_pinjam, p.tanggal_kembali
    FROM peminjaman p JOIN users u ON u.id = p.user_id
    WHERE p.barang_id = :id AND p.status = 'dipinjam'
    ORDER BY p.tanggal_pinjam ASC
");
$peminjamStmt->execute(['id' => $id]);
$peminjaman = $peminjamStmt->fetchAll();

// Riwayat 5 terakhir
$riwayatStmt = $pdo->prepare("
    SELECT p.id, u.nama, p.jumlah, p.tanggal_pinjam, p.status
    FROM peminjaman p JOIN users u ON u.id = p.user_id
    WHERE p.barang_id = :id
    ORDER BY p.tanggal_pinjam DESC LIMIT 5
");
$riwayatStmt->execute(['id' => $id]);
$riwayatList = $riwayatStmt->fetchAll();

$statusTersedia = (int)$barang['sisa_tersedia'] > 0;
$kondisiBadge   = match($barang['kondisi']) {
    'Baik'         => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
    'Rusak Ringan' => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500'],
    default        => ['bg' => 'bg-rose-100',     'text' => 'text-rose-700',    'dot' => 'bg-rose-500'],
};

// Cek pengajuan aktif user ini untuk barang ini
$sudahAda = false;
if (isset($_SESSION['user'])) {
    $cekAktif = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=:uid AND barang_id=:bid AND status IN ('menunggu persetujuan','disetujui','dipinjam')");
    $cekAktif->execute(['uid' => $_SESSION['user']['id'], 'bid' => $id]);
    $sudahAda = $cekAktif->fetchColumn() > 0;
}

// Flash
$flashMsg  = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? null;
if ($flashMsg) { unset($_SESSION['flash_message'], $_SESSION['flash_type']); }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($barang['nama_barang']) ?> — IKP KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <!-- bilah atas -->
    <div class="bg-[#102b4e] text-white px-4 py-3 flex items-center gap-3 shadow-lg sticky top-0 z-10">
        <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center">
            <i data-lucide="scan-qr-code" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="font-bold text-sm">IKP KOMINFO</div>
            <div class="text-[10px] text-blue-200 tracking-wider">Inventaris Multimedia</div>
        </div>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="../barang/index.php" class="ml-auto text-xs text-blue-200 hover:text-white flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali
            </a>
        <?php endif; ?>
    </div>

    <div class="max-w-lg mx-auto p-4 space-y-4 pb-10">

        <!-- pesan notifikasi -->
        <?php if ($flashMsg): ?>
            <div class="flex items-center gap-2 text-sm font-semibold px-4 py-3 rounded-xl mt-2
                <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                <i data-lucide="<?= $flashType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
                <?= e($flashMsg) ?>
            </div>
        <?php endif; ?>

        <!-- kartu informasi barang -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

            <!-- status ketersediaan -->
            <?php if ($statusTersedia): ?>
                <div class="bg-emerald-500 text-white px-5 py-3 flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    <span class="font-bold text-sm">Tersedia untuk Dipinjam</span>
                    <span class="ml-auto font-bold text-emerald-100 text-sm"><?= (int)$barang['sisa_tersedia'] ?> unit</span>
                </div>
            <?php else: ?>
                <div class="bg-rose-500 text-white px-5 py-3 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                    <span class="font-bold text-sm">Semua Unit Sedang Dipinjam</span>
                </div>
            <?php endif; ?>

            <!-- rincian barang -->
            <div class="p-5">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-8 h-8 text-slate-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg font-bold text-slate-900 leading-tight"><?= e($barang['nama_barang']) ?></h1>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                                <i data-lucide="tag" class="w-3 h-3"></i> <?= e($barang['kategori']) ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold <?= $kondisiBadge['bg'] ?> <?= $kondisiBadge['text'] ?> px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full <?= $kondisiBadge['dot'] ?>"></span>
                                <?= e($barang['kondisi']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ringkasan unit -->
                <div class="grid grid-cols-3 gap-3 mt-5">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-bold text-slate-800"><?= (int)$barang['jumlah'] ?></div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Total Unit</div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-bold text-amber-600"><?= (int)$barang['sedang_dipinjam'] ?></div>
                        <div class="text-[10px] text-amber-600 font-semibold mt-0.5">Dipinjam</div>
                    </div>
                    <div class="<?= $statusTersedia ? 'bg-emerald-50' : 'bg-rose-50' ?> rounded-xl p-3 text-center">
                        <div class="text-2xl font-bold <?= $statusTersedia ? 'text-emerald-600' : 'text-rose-600' ?>"><?= (int)$barang['sisa_tersedia'] ?></div>
                        <div class="text-[10px] <?= $statusTersedia ? 'text-emerald-600' : 'text-rose-600' ?> font-semibold mt-0.5">Tersedia</div>
                    </div>
                </div>

                <!-- formulir pinjam -->
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin'): ?>
                    <?php if ($sudahAda): ?>
                        <div class="mt-4 flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 font-semibold py-3 px-4 rounded-xl text-sm">
                            <i data-lucide="clock" class="w-4 h-4 shrink-0 text-amber-500"></i>
                            Anda sudah memiliki pengajuan aktif untuk barang ini
                        </div>
                    <?php elseif ($statusTersedia): ?>
                        <form method="post" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="barang_id" value="<?= $barang['id'] ?>">

                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Form Pengajuan Peminjaman</p>

                            <!-- jumlah unit -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Jumlah Unit yang Dipinjam</label>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="ubahJumlah(-1)"
                                        class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 font-bold text-xl text-slate-700 flex items-center justify-center transition shrink-0">−</button>
                                    <input type="number" id="inputJumlah" name="jumlah"
                                        value="1" min="1" max="<?= (int)$barang['sisa_tersedia'] ?>"
                                        class="flex-1 text-center border border-slate-200 rounded-xl px-4 py-2.5 text-lg font-bold focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="ubahJumlah(1)"
                                        class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 font-bold text-xl text-slate-700 flex items-center justify-center transition shrink-0">+</button>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 text-center">Maks. <?= (int)$barang['sisa_tersedia'] ?> unit tersedia</p>
                            </div>

                            <!-- tanggal rencana pengembalian -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">📅 Tanggal Rencana Pengembalian</label>
                                <input type="date" name="tanggal_kembali"
                                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                    required
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            </div>

                            <button type="submit"
                                class="w-full flex items-center justify-center gap-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold py-3.5 rounded-xl transition text-sm">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                Kirim Pengajuan Peminjaman
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="mt-4 flex items-center justify-center gap-2 bg-rose-50 border border-rose-200 text-rose-700 font-semibold py-3 rounded-xl text-sm">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                            Stok habis — tidak tersedia
                        </div>
                    <?php endif; ?>
                <?php elseif (!isset($_SESSION['user'])): ?>
                    <a href="../auth/login.php"
                       class="mt-4 w-full flex items-center justify-center gap-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold py-3 rounded-xl transition text-sm">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Login untuk Meminjam
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- peminjam saat ini -->
        <?php if ($peminjaman): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-amber-500"></i>
                    <h2 class="text-sm font-bold text-slate-800">Sedang Dipinjam Oleh</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($peminjaman as $p): ?>
                        <div class="px-5 py-3.5 flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm text-slate-800"><?= e($p['nama']) ?> <span class="text-xs font-normal text-slate-400">(<?= (int)$p['jumlah'] ?> unit)</span></div>
                                <div class="text-xs text-slate-500">
                                    <?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?>
                                    <?= $p['tanggal_kembali'] ? '→ ' . date('d/m/Y', strtotime($p['tanggal_kembali'])) : '→ Belum ditentukan' ?>
                                </div>
                            </div>
                            <div class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg shrink-0">
                                <?= $p['tanggal_kembali'] ? 's/d ' . date('d/m', strtotime($p['tanggal_kembali'])) : 'Belum ada' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- riwayat transaksi barang -->
        <?php if ($riwayatList): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                    <h2 class="text-sm font-bold text-slate-800">Riwayat Peminjaman</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($riwayatList as $r): ?>
                        <?php
                        $st = match($r['status']) {
                            'dikembalikan'        => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'label' => 'Selesai'],
                            'dipinjam'            => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'label' => 'Dipinjam'],
                            'disetujui'           => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',    'label' => 'Disetujui'],
                            'ditolak'             => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'label' => 'Ditolak'],
                            'menunggu persetujuan'=> ['bg' => 'bg-slate-50',   'text' => 'text-slate-600',   'label' => 'Menunggu'],
                            default               => ['bg' => 'bg-slate-50',   'text' => 'text-slate-600',   'label' => ucfirst($r['status'])],
                        };
                        ?>
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-slate-700"><?= e($r['nama']) ?></div>
                                <div class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?> · <?= (int)$r['jumlah'] ?> unit</div>
                            </div>
                            <span class="text-[10px] font-bold <?= $st['bg'] ?> <?= $st['text'] ?> px-2.5 py-1 rounded-full shrink-0"><?= $st['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center text-xs text-slate-400 pt-2">
            <p>IKP KOMINFO — Sistem Inventaris Multimedia</p>
            <p class="mt-0.5">Dinas Komunikasi dan Informatika Provinsi Sumatera Utara</p>
        </div>

    </div>

    <script>
    function ubahJumlah(delta) {
        const input = document.getElementById('inputJumlah');
        const max   = parseInt(input.max);
        const val   = Math.min(max, Math.max(1, parseInt(input.value || 1) + delta));
        input.value = val;
    }
    lucide.createIcons();
    </script>
</body>
</html>
