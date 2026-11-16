<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'peminjaman';
$pageTitle = 'Peminjaman Barang';
$pageSubtitle = 'TRANSAKSI PEMINJAMAN';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';
$userId = (int) $_SESSION['user']['id'];

// proses simpan dan kelola aksi peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // ajukan peminjaman oleh peminjam
    if ($action === 'ajukan_peminjam') {
        if ($userRole === 'admin') {
            flash('error', 'Administrator tidak diizinkan meminjam barang untuk diri sendiri. Gunakan formulir di bawah untuk mencatat peminjaman staf.');
            header('Location: index.php');
            exit;
        }

        $barang_id = (int) ($_POST['barang_id'] ?? 0);
        $jumlah = max(1, (int) ($_POST['jumlah'] ?? 1));
        $tanggal_pinjam = trim((string) ($_POST['tanggal_pinjam'] ?? date('Y-m-d')));
        $tanggal_kembali = trim((string) ($_POST['tanggal_kembali'] ?? ''));

        $stmt = $pdo->prepare("
            SELECT b.id, b.nama_barang, b.jumlah,
                   COALESCE(p.dipinjam_count, 0) AS sedang_dipinjam
            FROM barang b
            LEFT JOIN (
                SELECT barang_id, SUM(jumlah) AS dipinjam_count
                FROM peminjaman
                WHERE status = 'dipinjam'
                GROUP BY barang_id
            ) p ON p.barang_id = b.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $barang_id]);
        $barang = $stmt->fetch();

        $sisa = $barang ? max(0, $barang['jumlah'] - $barang['sedang_dipinjam']) : 0;

        if (!$barang) {
            flash('error', 'Barang yang dipilih tidak ditemukan.');
        } elseif ($sisa <= 0) {
            flash('error', 'Maaf, stok barang "' . $barang['nama_barang'] . '" saat ini sedang habis.');
        } elseif ($jumlah > $sisa) {
            flash('error', 'Jumlah peminjaman (' . $jumlah . ' unit) melebihi sisa stok yang tersedia (' . $sisa . ' unit).');
        } else {
            $insert = $pdo->prepare("
                INSERT INTO peminjaman (user_id, barang_id, jumlah, tanggal_pinjam, tanggal_kembali, status)
                VALUES (:uid, :bid, :jumlah, :tgl_pinjam, :tgl_kembali, 'menunggu persetujuan')
            ");
            $insert->execute([
                'uid' => $userId,
                'bid' => $barang_id,
                'jumlah' => $jumlah,
                'tgl_pinjam' => $tanggal_pinjam,
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : null,
            ]);
            flash('success', 'Pengajuan peminjaman ' . $jumlah . ' unit berhasil dikirim. Menunggu persetujuan Admin KOMINFO.');
        }
        header('Location: index.php');
        exit;
    }

    // catat peminjaman langsung oleh admin
    if ($action === 'catat_admin' && $userRole === 'admin') {
        $peminjam_user_id = (int) ($_POST['user_id'] ?? 0);
        $barang_id = (int) ($_POST['barang_id'] ?? 0);
        $jumlah = max(1, (int) ($_POST['jumlah'] ?? 1));
        $tanggal_pinjam = trim((string) ($_POST['tanggal_pinjam'] ?? date('Y-m-d')));
        $tanggal_kembali = trim((string) ($_POST['tanggal_kembali'] ?? ''));

        $stmt = $pdo->prepare("
            SELECT b.id, b.nama_barang, b.jumlah,
                   COALESCE(p.dipinjam_count, 0) AS sedang_dipinjam
            FROM barang b
            LEFT JOIN (
                SELECT barang_id, SUM(jumlah) AS dipinjam_count
                FROM peminjaman
                WHERE status = 'dipinjam'
                GROUP BY barang_id
            ) p ON p.barang_id = b.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $barang_id]);
        $barang = $stmt->fetch();

        $sisa = $barang ? max(0, $barang['jumlah'] - $barang['sedang_dipinjam']) : 0;

        if ($peminjam_user_id <= 0 || !$barang) {
            flash('error', 'Peminjam atau barang tidak valid.');
        } elseif ($sisa <= 0) {
            flash('error', 'Stok barang habis.');
        } elseif ($jumlah > $sisa) {
            flash('error', 'Jumlah peminjaman (' . $jumlah . ' unit) melebihi sisa stok yang tersedia (' . $sisa . ' unit).');
        } else {
            $insert = $pdo->prepare("
                INSERT INTO peminjaman (user_id, barang_id, jumlah, tanggal_pinjam, tanggal_kembali, status)
                VALUES (:uid, :bid, :jumlah, :tgl_pinjam, :tgl_kembali, 'dipinjam')
            ");
            $insert->execute([
                'uid' => $peminjam_user_id,
                'bid' => $barang_id,
                'jumlah' => $jumlah,
                'tgl_pinjam' => $tanggal_pinjam,
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : null,
            ]);
            flash('success', 'Peminjaman ' . $jumlah . ' unit berhasil dicatat dengan status: Dipinjam.');
        }
        header('Location: index.php');
        exit;
    }

    // persetujuan peminjaman oleh admin
    if ($action === 'setujui' && $userRole === 'admin') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT p.barang_id, p.jumlah AS minta_jumlah, b.jumlah, COALESCE(dip.cnt, 0) AS dipinjam_count
            FROM peminjaman p
            JOIN barang b ON b.id = p.barang_id
            LEFT JOIN (
                SELECT barang_id, SUM(jumlah) AS cnt FROM peminjaman WHERE status = 'dipinjam' GROUP BY barang_id
            ) dip ON dip.barang_id = p.barang_id
            WHERE p.id = :pid
        ");
        $stmt->execute(['pid' => $peminjaman_id]);
        $cek = $stmt->fetch();

        if (!$cek) {
            flash('error', 'Data pengajuan tidak ditemukan.');
        } elseif (($cek['jumlah'] - $cek['dipinjam_count']) < $cek['minta_jumlah']) {
            flash('error', 'Gagal menyetujui: Sisa stok tidak mencukupi untuk ' . $cek['minta_jumlah'] . ' unit.');
        } else {
            $up = $pdo->prepare("UPDATE peminjaman SET status = 'dipinjam' WHERE id = :id");
            $up->execute(['id' => $peminjaman_id]);
            flash('success', 'Pengajuan disetujui. Peralatan resmi berstatus dipinjam.');
        }
        header('Location: index.php');
        exit;
    }

    // tolak pengajuan peminjaman
    if ($action === 'tolak' && $userRole === 'admin') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);
        $up = $pdo->prepare("UPDATE peminjaman SET status = 'ditolak' WHERE id = :id");
        $up->execute(['id' => $peminjaman_id]);
        flash('success', 'Pengajuan peminjaman telah ditolak.');
        header('Location: index.php');
        exit;
    }

    // batalkan pengajuan oleh peminjam
    if ($action === 'batalkan_pengajuan') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM peminjaman WHERE id = :id AND user_id = :uid AND status = 'menunggu persetujuan'");
        $del->execute(['id' => $peminjaman_id, 'uid' => $userId]);
        flash('success', 'Pengajuan peminjaman berhasil dibatalkan.');
        header('Location: index.php');
        exit;
    }
}

// data untuk form dan tabel
$barangTersedia = $pdo->query("
    SELECT 
        b.id, b.nama_barang, b.kategori, b.kondisi, b.jumlah,
        GREATEST(0, b.jumlah - COALESCE(p.dipinjam_count, 0)) AS sisa_tersedia
    FROM barang b
    LEFT JOIN (
        SELECT barang_id, SUM(jumlah) AS dipinjam_count
        FROM peminjaman
        WHERE status = 'dipinjam'
        GROUP BY barang_id
    ) p ON p.barang_id = b.id
    ORDER BY b.nama_barang ASC
")->fetchAll();

$userList = [];
if ($userRole === 'admin') {
    $userList = $pdo->query("SELECT id, nama, username FROM users WHERE status = 'aktif' AND role = 'peminjam' ORDER BY nama ASC")->fetchAll();
}

$pengajuanStmt = $pdo->prepare("
    SELECT p.id, u.nama AS nama_peminjam, b.nama_barang, b.kategori, p.jumlah, p.tanggal_pinjam, p.tanggal_kembali, p.status, p.created_at
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
    WHERE p.status = 'menunggu persetujuan' " . ($userRole === 'peminjam' ? "AND p.user_id = :uid" : "") . "
    ORDER BY p.created_at DESC
");
if ($userRole === 'peminjam') {
    $pengajuanStmt->execute(['uid' => $userId]);
} else {
    $pengajuanStmt->execute();
}
$listPengajuan = $pengajuanStmt->fetchAll();

$dipinjamStmt = $pdo->prepare("
    SELECT p.id, u.nama AS nama_peminjam, b.nama_barang, b.kategori, p.jumlah, p.tanggal_pinjam, p.tanggal_kembali, p.status
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN barang b ON b.id = p.barang_id
    WHERE p.status = 'dipinjam' " . ($userRole === 'peminjam' ? "AND p.user_id = :uid" : "") . "
    ORDER BY p.tanggal_pinjam DESC
");
if ($userRole === 'peminjam') {
    $dipinjamStmt->execute(['uid' => $userId]);
} else {
    $dipinjamStmt->execute();
}
$listDipinjam = $dipinjamStmt->fetchAll();

$selectedBarangId = (int) ($_GET['barang_id'] ?? 0);
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

            <!-- form pengajuan atau pencatatan -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6 mb-2">
                <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[9px] font-bold tracking-widest text-blue-600 uppercase">
                            <?= $userRole === 'admin' ? 'PENCATATAN ADMIN' : 'FORMULIR PENGAJUAN' ?>
                        </span>
                        <h2 class="text-base sm:text-lg font-bold text-[#102b4e]">
                            <?= $userRole === 'admin' ? 'Catat Peminjaman' : 'Ajukan Peminjaman Peralatan' ?>
                        </h2>
                    </div>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        <?= $userRole === 'admin' ? 'Akses Admin' : 'Pegawai' ?>
                    </span>
                </div>

                <form method="post"
                    data-confirm="<?= $userRole === 'admin' ? 'Catat transaksi peminjaman barang ini untuk staf terpilih? Unit akan langsung dipotong dari stok.' : 'Kirim pengajuan peminjaman barang ini?' ?>"
                    data-confirm-title="<?= $userRole === 'admin' ? 'Catat Peminjaman' : 'Ajukan Peminjaman' ?>"
                    data-confirm-icon="question"
                    data-confirm-btn="<?= $userRole === 'admin' ? 'Catat Sekarang' : 'Kirim Pengajuan' ?>"
                    data-confirm-color="#102b4e"
                    class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $userRole === 'admin' ? 'catat_admin' : 'ajukan_peminjam' ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?= $userRole === 'admin' ? '5' : '4' ?> gap-3.5">
                        <?php if ($userRole === 'admin'): ?>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Peminjam (Pegawai) *</label>
                                <select name="user_id" class="h-10 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                                    <option value="">-- Pilih Pegawai --</option>
                                    <?php foreach ($userList as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= e($u['nama']) ?> (<?= e($u['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Peralatan Multimedia *</label>
                            <select name="barang_id" class="h-10 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                                <option value="">-- Pilih Peralatan --</option>
                                <?php foreach ($barangTersedia as $bt): ?>
                                    <option 
                                        value="<?= $bt['id'] ?>"
                                        <?= $selectedBarangId === (int) $bt['id'] ? 'selected' : '' ?>
                                        <?= $bt['sisa_tersedia'] <= 0 ? 'disabled' : '' ?>
                                    >
                                        <?= e($bt['nama_barang']) ?> (Sisa: <?= $bt['sisa_tersedia'] ?> unit)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Jumlah Unit *</label>
                            <input type="number" name="jumlah" value="1" min="1" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Tgl. Pinjam *</label>
                            <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Rencana Kembali *</label>
                            <input type="date" name="tanggal_kembali" min="<?= date('Y-m-d') ?>" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-1">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#102b4e] hover:bg-[#1a3d6b] !text-white font-bold text-xs rounded-xl shadow-xs transition whitespace-nowrap">
                            <i data-lucide="send" class="w-3.5 h-3.5 !text-white"></i>
                            <span class="!text-white whitespace-nowrap"><?= $userRole === 'admin' ? 'Catat Peminjaman' : 'Kirim Pengajuan' ?></span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- tabel pengajuan menunggu persetujuan -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-amber-600 uppercase">VERIFIKASI</span>
                        <h3 class="text-sm sm:text-base font-bold text-[#102b4e]">Pengajuan Menunggu Persetujuan</h3>
                        <p class="text-xs text-slate-400 hidden sm:block">Pengajuan yang memerlukan persetujuan Admin KOMINFO sebelum barang diserahkan.</p>
                    </div>

                    <span class="text-[11px] sm:text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-lg whitespace-nowrap shrink-0">
                        <?= count($listPengajuan) ?> pengajuan
                    </span>
                </div>

                <div class="w-full overflow-hidden sm:overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse table-auto">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider bg-slate-50/50 sm:bg-transparent">
                                <th class="py-2 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2 px-1.5 sm:px-3">Peminjam</th>
                                <th class="py-2 px-1.5 sm:px-3">Peralatan</th>
                                <th class="py-2 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Jumlah</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Tgl. Pinjam</th>
                                <th class="py-2 px-3 hidden lg:table-cell">Kembali</th>
                                <th class="py-2 px-3 hidden md:table-cell">Durasi</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Status</th>
                                <th class="py-2 px-1.5 sm:px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$listPengajuan): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-8 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-500"></i>
                                            <span class="font-bold text-xs">Semua antrean pengajuan telah diproses</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listPengajuan as $i => $row): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2 px-1.5 sm:px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[110px] sm:max-w-none text-xs"><?= e($row['nama_peminjam']) ?></div>
                                            <div class="text-[9px] text-slate-400 font-normal sm:hidden"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></div>
                                        </td>
                                        <td class="py-2 px-1.5 sm:px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[120px] sm:max-w-none text-xs"><?= e($row['nama_barang']) ?></div>
                                            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) $row['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-500 hidden md:table-cell"><?= e($row['kategori']) ?></td>
                                        <td class="py-2 px-3 font-bold text-blue-700 bg-blue-50/50 hidden sm:table-cell"><?= (int) $row['jumlah'] ?> unit</td>
                                        <td class="py-2 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td class="py-2 px-3 text-slate-500 hidden lg:table-cell"><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2 px-3 font-semibold text-slate-700 hidden md:table-cell"><?= hitung_durasi($row['tanggal_pinjam'], $row['tanggal_kembali']) ?> hari</td>
                                        <td class="py-2 px-3 hidden sm:table-cell">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                                Menunggu
                                            </span>
                                        </td>
                                        <td class="py-2 px-1.5 sm:px-3 text-right">
                                            <div class="inline-flex items-center gap-1 justify-end">
                                                <?php if ($userRole === 'admin'): ?>
                                                    <!-- setujui -->
                                                    <form method="post" data-confirm="Setujui pengajuan peminjaman barang ini? Unit akan otomatis dipotong dari stok inventaris." data-confirm-title="Setujui Peminjaman" data-confirm-icon="question" data-confirm-btn="Ya, Setujui" data-confirm-color="#059669" class="inline m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="setujui">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 !text-white inline-flex items-center gap-0.5 transition shadow-2xs" title="Setujui">
                                                            <i data-lucide="check" class="w-3 h-3 !text-white"></i>
                                                            <span class="hidden sm:inline !text-white">Setujui</span>
                                                        </button>
                                                    </form>

                                                    <!-- tolak -->
                                                    <form method="post" data-confirm="Tolak permohonan peminjaman ini?" data-confirm-title="Tolak Peminjaman" data-confirm-icon="warning" data-confirm-btn="Tolak Pengajuan" data-confirm-color="#e11d48" class="inline m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="tolak">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="px-1.5 py-1 rounded-md text-[11px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 inline-flex items-center gap-0.5 transition" title="Tolak">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                            <span class="hidden sm:inline">Tolak</span>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- batalkan pengajuan -->
                                                    <form method="post" data-confirm="Batalkan pengajuan peminjaman ini?" data-confirm-title="Batalkan Pengajuan" data-confirm-icon="warning" data-confirm-btn="Ya, Batalkan" data-confirm-color="#e11d48" class="inline m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="batalkan_pengajuan">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="px-2 py-1 rounded-md text-[11px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 inline-flex items-center gap-1 transition">
                                                            <i data-lucide="x-circle" class="w-3 h-3"></i>
                                                            <span>Batal</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- tabel barang sedang dipinjam -->
            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6 mb-4">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-[10px] font-extrabold tracking-widest text-blue-600 uppercase">STATUS LAPANGAN</span>
                        <h3 class="text-sm sm:text-base font-bold text-[#102b4e]">Peralatan Sedang Dipinjam</h3>
                        <p class="text-xs text-slate-400 hidden sm:block">
                            <?= $userRole === 'admin' ? 'Seluruh peralatan yang saat ini sedang dibawa oleh peminjam.' : 'Peralatan yang saat ini sedang Anda pinjam.' ?>
                        </p>
                    </div>

                    <a href="../pengembalian/index.php" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        <span>Pengembalian</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="w-full overflow-hidden sm:overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse table-auto">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider">
                                <th class="py-2 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2 px-1.5 sm:px-3">Peminjam</th>
                                <th class="py-2 px-1.5 sm:px-3">Peralatan</th>
                                <th class="py-2 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Jumlah</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Tgl. Pinjam</th>
                                <th class="py-2 px-3 hidden lg:table-cell">Kembali</th>
                                <th class="py-2 px-3 hidden md:table-cell">Durasi</th>
                                <th class="py-2 px-1.5 sm:px-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$listDipinjam): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="inbox" class="w-6 h-6 text-slate-300"></i>
                                            <span class="font-bold text-xs">Tidak ada barang yang sedang dipinjam</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listDipinjam as $i => $row): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2 px-1.5 sm:px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[110px] sm:max-w-none text-xs"><?= e($row['nama_peminjam']) ?></div>
                                            <div class="text-[9px] text-slate-400 font-normal sm:hidden"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></div>
                                        </td>
                                        <td class="py-2 px-1.5 sm:px-3 font-medium text-slate-700">
                                            <div class="truncate max-w-[120px] sm:max-w-none text-xs"><?= e($row['nama_barang']) ?></div>
                                            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1 py-0.2 rounded inline-block mt-0.5 sm:hidden"><?= (int) $row['jumlah'] ?> unit</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-500 hidden md:table-cell"><?= e($row['kategori']) ?></td>
                                        <td class="py-2 px-3 font-bold text-blue-700 bg-blue-50/50 hidden sm:table-cell"><?= (int) $row['jumlah'] ?> unit</td>
                                        <td class="py-2 px-3 text-slate-500 hidden sm:table-cell"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td class="py-2 px-3 text-slate-500 font-medium hidden lg:table-cell"><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td class="py-2 px-3 font-bold text-slate-700 hidden md:table-cell"><?= hitung_durasi($row['tanggal_pinjam'], $row['tanggal_kembali']) ?> hari</td>
                                        <td class="py-2 px-1.5 sm:px-3 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                                                Dipinjam
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
