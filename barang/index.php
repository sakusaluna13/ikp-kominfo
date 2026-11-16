<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'barang';
$pageTitle = 'Data Barang';
$pageSubtitle = 'MASTER INVENTARIS';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';

// ============================================
// PROSES AKSI (ADMIN ONLY: TAMBAH, EDIT, HAPUS)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // TAMBAH BARANG
    if ($action === 'tambah' && $userRole === 'admin') {
        $nama_barang = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $jumlah = max(0, (int) ($_POST['jumlah'] ?? 0));
        $kondisi = trim((string) ($_POST['kondisi'] ?? 'Baik'));

        if ($nama_barang === '' || $kategori === '') {
            flash('error', 'Nama barang dan kategori wajib diisi.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO barang (nama_barang, kategori, jumlah, kondisi) VALUES (:nama, :kategori, :jumlah, :kondisi)');
            $stmt->execute([
                'nama' => $nama_barang,
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'kondisi' => $kondisi,
            ]);
            flash('success', 'Barang "' . $nama_barang . '" berhasil ditambahkan.');
        }
        header('Location: index.php');
        exit;
    }

    // EDIT BARANG
    if ($action === 'edit' && $userRole === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama_barang = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $jumlah = max(0, (int) ($_POST['jumlah'] ?? 0));
        $kondisi = trim((string) ($_POST['kondisi'] ?? 'Baik'));

        if ($id <= 0 || $nama_barang === '' || $kategori === '') {
            flash('error', 'Data perubahan tidak valid.');
        } else {
            $stmt = $pdo->prepare('UPDATE barang SET nama_barang = :nama, kategori = :kategori, jumlah = :jumlah, kondisi = :kondisi WHERE id = :id');
            $stmt->execute([
                'nama' => $nama_barang,
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'kondisi' => $kondisi,
                'id' => $id,
            ]);
            flash('success', 'Data barang berhasil diperbarui.');
        }
        header('Location: index.php');
        exit;
    }

    // HAPUS BARANG
    if ($action === 'hapus' && $userRole === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);

        $cek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE barang_id = :id AND status = 'dipinjam'");
        $cek->execute(['id' => $id]);
        if ($cek->fetchColumn() > 0) {
            flash('error', 'Barang tidak dapat dihapus karena masih sedang dipinjam.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM barang WHERE id = :id');
                $stmt->execute(['id' => $id]);
                flash('success', 'Barang berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Barang memiliki riwayat transaksi peminjaman sehingga tidak dapat dihapus.');
            }
        }
        header('Location: index.php');
        exit;
    }
}

// ============================================
// FILTER & QUERY DATA
// ============================================
$search = trim((string) ($_GET['search'] ?? ''));
$filterKategori = trim((string) ($_GET['kategori'] ?? ''));
$filterKondisi = trim((string) ($_GET['kondisi'] ?? ''));

$sql = "
    SELECT 
        b.id,
        b.nama_barang,
        b.kategori,
        b.jumlah,
        b.kondisi,
        b.created_at,
        COALESCE(p.dipinjam_count, 0) AS sedang_dipinjam,
        GREATEST(0, b.jumlah - COALESCE(p.dipinjam_count, 0)) AS sisa_tersedia
    FROM barang b
    LEFT JOIN (
        SELECT barang_id, SUM(jumlah) AS dipinjam_count
        FROM peminjaman
        WHERE status = 'dipinjam'
        GROUP BY barang_id
    ) p ON p.barang_id = b.id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (b.nama_barang LIKE :search OR b.kategori LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filterKategori !== '') {
    $sql .= " AND b.kategori = :kategori";
    $params['kategori'] = $filterKategori;
}

if ($filterKondisi !== '') {
    $sql .= " AND b.kondisi = :kondisi";
    $params['kondisi'] = $filterKondisi;
}

$sql .= " ORDER BY b.nama_barang ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$barangList = $stmt->fetchAll();

$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM barang WHERE kategori != '' ORDER BY kategori ASC")->fetchAll(PDO::FETCH_COLUMN);
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
    <!-- Library Generate QR Code -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 px-3 sm:px-8 min-h-screen flex flex-col transition-all pb-6">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 flex flex-col gap-4 sm:gap-6">

            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-4 sm:p-6">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 sm:mb-6">
                    <div>
                        <span class="text-[9px] font-bold tracking-widest text-blue-600 uppercase">INVENTARIS</span>
                        <h2 class="text-base sm:text-xl font-bold text-[#102b4e]">Daftar Peralatan Multimedia</h2>
                        <p class="text-xs text-slate-400 hidden sm:block">Kelola dan pantau stok aset multimedia Bidang IKP KOMINFO.</p>
                    </div>

                    <?php if ($userRole === 'admin'): ?>
                        <button 
                            type="button" 
                            data-modal-open="modalTambahBarang"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-xs rounded-xl shadow-xs transition"
                        >
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Barang</span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- FILTER & PENCARIAN -->
                <form method="get" class="flex flex-wrap items-center justify-between gap-2.5 mb-4 p-2.5 sm:p-3 bg-slate-50/80 rounded-xl border border-slate-100">
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <input
                            type="text"
                            name="search"
                            class="h-8 sm:h-9 px-3 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-100 flex-1 sm:w-48"
                            placeholder="Cari peralatan..."
                            value="<?= e($search) ?>"
                        >

                        <select name="kategori" class="h-8 sm:h-9 px-2.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= e($kat) ?>" <?= $filterKategori === $kat ? 'selected' : '' ?>>
                                    <?= e($kat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="kondisi" class="h-8 sm:h-9 px-2.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Kondisi</option>
                            <option value="Baik" <?= $filterKondisi === 'Baik' ? 'selected' : '' ?>>Baik</option>
                            <option value="Rusak Ringan" <?= $filterKondisi === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                            <option value="Rusak Berat" <?= $filterKondisi === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                        </select>

                        <button type="submit" class="h-8 sm:h-9 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-lg flex items-center gap-1 transition">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Filter</span>
                        </button>

                        <?php if ($search !== '' || $filterKategori !== '' || $filterKondisi !== ''): ?>
                            <a href="index.php" class="h-8 sm:h-9 px-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs rounded-lg flex items-center transition">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>

                    <span class="text-[11px] font-semibold text-slate-400 ml-auto">
                        Total <strong><?= count($barangList) ?></strong> barang
                    </span>
                </form>

                <!-- TABEL DATA BARANG (COMPACT RESPONSIVE) -->
                <div class="overflow-x-auto -mx-4 sm:mx-0">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider bg-slate-50/50 sm:bg-transparent">
                                <th class="py-2.5 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2.5 px-3">Peralatan</th>
                                <th class="py-2.5 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2.5 px-3 hidden lg:table-cell">Total Stok</th>
                                <th class="py-2.5 px-3 hidden lg:table-cell">Dipinjam</th>
                                <th class="py-2.5 px-3">Tersedia</th>
                                <th class="py-2.5 px-3 hidden sm:table-cell">Kondisi</th>
                                <th class="py-2.5 px-2 text-center">QR</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$barangList): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-10 text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <i data-lucide="package-open" class="w-7 h-7 text-slate-300"></i>
                                            <span class="font-bold text-xs">Tidak ada data peralatan ditemukan</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barangList as $i => $b): ?>
                                    <?php
                                    $kondisiBadge = match ($b['kondisi']) {
                                        'Baik' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Rusak Ringan' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-rose-50 text-rose-700 border-rose-200',
                                    };
                                    $tersediaBadge = $b['sisa_tersedia'] > 0 
                                        ? 'bg-blue-50 text-blue-700 border-blue-200' 
                                        : 'bg-rose-50 text-rose-700 border-rose-200';
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2.5 px-3 text-slate-400 hidden sm:table-cell"><?= $i + 1 ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-800">
                                            <div class="truncate max-w-[130px] sm:max-w-none text-xs sm:text-[13px]"><?= e($b['nama_barang']) ?></div>
                                            <div class="flex items-center gap-1.5 mt-0.5 sm:hidden">
                                                <span class="text-[10px] text-slate-400 font-normal"><?= e($b['kategori']) ?></span>
                                                <span class="text-[9px] px-1 rounded font-semibold <?= $kondisiBadge ?>"><?= e($b['kondisi']) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 font-medium hidden md:table-cell"><?= e($b['kategori']) ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-700 hidden lg:table-cell"><?= number_format((float) $b['jumlah'], 0, ',', '.') ?> unit</td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden lg:table-cell"><?= (int) $b['sedang_dipinjam'] ?> unit</td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $tersediaBadge ?>">
                                                <?= (int) $b['sisa_tersedia'] ?> <span class="hidden sm:inline ml-0.5">unit</span>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 hidden sm:table-cell">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $kondisiBadge ?>">
                                                <?= e($b['kondisi']) ?>
                                            </span>
                                        </td>
                                        <!-- TOMBOL QR CODE -->
                                        <td class="py-2.5 px-2 text-center">
                                            <button
                                                type="button"
                                                onclick="bukaModalQR(<?= $b['id'] ?>, '<?= e($b['nama_barang']) ?>')"
                                                title="Lihat QR Code"
                                                class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition">
                                                <i data-lucide="qr-code" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </button>
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <div class="inline-flex items-center gap-1.5 justify-end">
                                                <?php if ($userRole === 'admin'): ?>
                                                    <!-- EDIT -->
                                                    <button
                                                        type="button"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center gap-1 transition"
                                                        onclick="editBarang(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)"
                                                    >
                                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                        <span>Edit</span>
                                                    </button>

                                                    <!-- HAPUS -->
                                                    <form method="post" onsubmit="return confirm('Hapus barang <?= e($b['nama_barang']) ?>?');" class="inline m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                        <button type="submit" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus Barang">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if ($b['sisa_tersedia'] > 0): ?>
                                                        <a href="../peminjaman/index.php?barang_id=<?= $b['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 !text-white inline-flex items-center gap-1 shadow-2xs transition">
                                                            <span class="!text-white font-bold">Pinjam</span>
                                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 !text-white"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-md">Habis</span>
                                                    <?php endif; ?>
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

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <?php if ($userRole === 'admin'): ?>
        <!-- MODAL TAMBAH BARANG -->
        <div id="modalTambahBarang" class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <h3 class="text-base font-bold text-[#102b4e]">Tambah Peralatan Inventaris</h3>
                    <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="post" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="tambah">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Nama Barang *</label>
                        <input type="text" name="nama_barang" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="Contoh: Sony Alpha A7 III" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Kategori *</label>
                            <input type="text" name="kategori" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" list="kategoriPresets" placeholder="Kamera / Audio / Lighting" required>
                            <datalist id="kategoriPresets">
                                <option value="Kamera">
                                <option value="Aksesori Kamera">
                                <option value="Audio">
                                <option value="Pencahayaan">
                                <option value="Drone & Aerial">
                                <option value="Perangkat Streaming">
                                <option value="Perangkat Liputan">
                                <option value="Studio & Dekorasi">
                                <option value="Laptop & Komputer">
                            </datalist>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Jumlah Stok (Unit) *</label>
                            <input type="number" name="jumlah" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" min="0" value="1" required>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Kondisi Fisik *</label>
                        <select name="kondisi" class="h-10 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="Baik">Baik (Normal / Siap Pakai)</option>
                            <option value="Rusak Ringan">Rusak Ringan (Perlu perhatian)</option>
                            <option value="Rusak Berat">Rusak Berat (Tidak siap pakai)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-2">
                        <button type="button" data-modal-close class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#102b4e] hover:bg-[#1a3d6b] text-white shadow-sm transition">
                            Simpan Peralatan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT BARANG -->
        <div id="modalEditBarang" class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <h3 class="text-base font-bold text-[#102b4e]">Perbarui Data Peralatan</h3>
                    <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="post" id="formEditBarang" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Nama Barang *</label>
                        <input type="text" name="nama_barang" id="edit_nama" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Kategori *</label>
                            <input type="text" name="kategori" id="edit_kategori" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" list="kategoriPresets" required>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-700">Jumlah Stok (Unit) *</label>
                            <input type="number" name="jumlah" id="edit_jumlah" class="h-10 px-3.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" min="0" required>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Kondisi Fisik *</label>
                        <select name="kondisi" id="edit_kondisi" class="h-10 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-2">
                        <button type="button" data-modal-close class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#102b4e] hover:bg-[#1a3d6b] text-white shadow-sm transition">
                            Perbarui Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function editBarang(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_nama').value = item.nama_barang;
            document.getElementById('edit_kategori').value = item.kategori;
            document.getElementById('edit_jumlah').value = item.jumlah;
            document.getElementById('edit_kondisi').value = item.kondisi;
            
            const modal = document.getElementById('modalEditBarang');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) lucide.createIcons();
        }
        </script>
    <?php endif; ?>

    <script src="../assets/js/script.js"></script>

    <!-- MODAL QR CODE -->
    <div id="modalQRCode" class="app-modal fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i data-lucide="qr-code" class="w-5 h-5 text-indigo-600"></i>
                    <h3 class="text-sm font-bold text-slate-800">QR Code Barang</h3>
                </div>
                <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 flex flex-col items-center gap-4">
                <p class="text-sm font-semibold text-slate-700 text-center" id="qrNamaBarang"></p>

                <!-- QR Canvas -->
                <div class="p-4 bg-white border-2 border-slate-200 rounded-2xl shadow-inner">
                    <div id="qrCanvas"></div>
                </div>

                <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                    Scan QR ini dengan HP untuk melihat<br>status & detail barang secara langsung
                </p>

                <!-- URL Link -->
                <div class="w-full bg-slate-50 rounded-xl px-3 py-2.5 text-[11px] text-slate-500 font-mono break-all text-center" id="qrUrlText"></div>

                <!-- Tombol Download -->
                <div class="flex gap-3 w-full">
                    <button onclick="downloadQR()"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl transition text-xs">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Download QR
                    </button>
                    <button onclick="printQR()"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl transition text-xs">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentQRUrl = '';
    let qrInstance   = null;

    function bukaModalQR(id, nama) {
        // Buat URL detail barang
        const baseUrl = window.location.origin + window.location.pathname.replace('index.php', 'detail.php');
        const url = baseUrl + '?id=' + id;
        currentQRUrl = url;

        document.getElementById('qrNamaBarang').textContent = nama;
        document.getElementById('qrUrlText').textContent    = url;

        // Hapus QR lama
        const canvas = document.getElementById('qrCanvas');
        canvas.innerHTML = '';

        // Generate QR baru
        qrInstance = new QRCode(canvas, {
            text:         url,
            width:        200,
            height:       200,
            colorDark:    '#102b4e',
            colorLight:   '#ffffff',
            correctLevel: QRCode.CorrectLevel.H,
        });

        // Buka modal
        const modal = document.getElementById('modalQRCode');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) lucide.createIcons();
    }

    function downloadQR() {
        setTimeout(() => {
            const canvas = document.getElementById('qrCanvas').querySelector('canvas');
            if (!canvas) return;
            const link  = document.createElement('a');
            link.download = 'qr-barang-' + currentQRUrl.split('id=')[1] + '.png';
            link.href      = canvas.toDataURL('image/png');
            link.click();
        }, 100);
    }

    function printQR() {
        const canvas = document.getElementById('qrCanvas').querySelector('canvas');
        if (!canvas) return;
        const nama   = document.getElementById('qrNamaBarang').textContent;
        const imgSrc = canvas.toDataURL('image/png');
        const win    = window.open('', '_blank');
        win.document.write(`
            <html><head><title>QR Code - ${nama}</title>
            <style>
                body { font-family: sans-serif; text-align: center; padding: 40px; }
                img  { width: 220px; height: 220px; border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; }
                h2   { color: #102b4e; margin-bottom: 8px; font-size: 16px; }
                p    { color: #64748b; font-size: 11px; margin-top: 8px; }
                small{ display:block; color:#94a3b8; font-size:9px; margin-top:4px; }
            </style></head>
            <body onload="window.print();window.close();">
                <img src="${imgSrc}">
                <h2>${nama}</h2>
                <p>SIMPEL MEDIA — KOMINFO</p>
                <small>${currentQRUrl}</small>
            </body></html>
        `);
        win.document.close();
    }
    </script>
</body>
</html>
