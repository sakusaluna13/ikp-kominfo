<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'barang';
$pageTitle = 'Data Barang';
$pageSubtitle = 'MASTER INVENTARIS';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';

// pastikan kolom foto pada tabel barang tersedia
try {
    $pdo->exec("ALTER TABLE barang ADD COLUMN foto VARCHAR(255) NULL DEFAULT NULL");
} catch (PDOException $e) {
    // abaikan jika kolom sudah ada
}

// proses simpan, edit, dan hapus barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // tambah barang baru
    if ($action === 'tambah' && $userRole === 'admin') {
        $nama_barang = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $jumlah = max(0, (int) ($_POST['jumlah'] ?? 0));
        $kondisi = trim((string) ($_POST['kondisi'] ?? 'Baik'));

        if ($nama_barang === '' || $kategori === '') {
            flash('error', 'Nama barang dan kategori wajib diisi.');
        } else {
            // kelola unggah foto barang
            $fotoPath = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['foto'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                if (in_array($mimeType, $allowedTypes, true) && $file['size'] <= 2 * 1024 * 1024) {
                    $uploadDir = __DIR__ . '/../assets/images/barang/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = match($mimeType) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                    $filename = 'barang_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $fotoPath = '../assets/images/barang/' . $filename;
                    }
                }
            }

            $stmt = $pdo->prepare('INSERT INTO barang (nama_barang, kategori, jumlah, kondisi, foto) VALUES (:nama, :kategori, :jumlah, :kondisi, :foto)');
            $stmt->execute([
                'nama' => $nama_barang,
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'kondisi' => $kondisi,
                'foto' => $fotoPath,
            ]);
            flash('success', 'Barang "' . $nama_barang . '" berhasil ditambahkan.');
        }
        header('Location: index.php');
        exit;
    }

    // perbarui data barang
    if ($action === 'edit' && $userRole === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama_barang = trim((string) ($_POST['nama_barang'] ?? ''));
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $jumlah = max(0, (int) ($_POST['jumlah'] ?? 0));
        $kondisi = trim((string) ($_POST['kondisi'] ?? 'Baik'));

        if ($id <= 0 || $nama_barang === '' || $kategori === '') {
            flash('error', 'Data perubahan tidak valid.');
        } else {
            // ambil data foto lama
            $stmtCek = $pdo->prepare('SELECT foto FROM barang WHERE id = :id');
            $stmtCek->execute(['id' => $id]);
            $currentFoto = $stmtCek->fetchColumn() ?: null;
            $fotoPath = $currentFoto;

            // opsi hapus foto jika dicentang
            if (!empty($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
                if ($currentFoto) {
                    $oldReal = __DIR__ . '/' . $currentFoto;
                    if (is_file($oldReal)) {
                        @unlink($oldReal);
                    }
                }
                $fotoPath = null;
            }

            // proses foto baru jika diunggah
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['foto'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                if (in_array($mimeType, $allowedTypes, true) && $file['size'] <= 2 * 1024 * 1024) {
                    $uploadDir = __DIR__ . '/../assets/images/barang/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = match($mimeType) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                    $filename = 'barang_' . $id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        if ($currentFoto) {
                            $oldReal = __DIR__ . '/' . $currentFoto;
                            if (is_file($oldReal)) {
                                @unlink($oldReal);
                            }
                        }
                        $fotoPath = '../assets/images/barang/' . $filename;
                    }
                }
            }

            $stmt = $pdo->prepare('UPDATE barang SET nama_barang = :nama, kategori = :kategori, jumlah = :jumlah, kondisi = :kondisi, foto = :foto WHERE id = :id');
            $stmt->execute([
                'nama' => $nama_barang,
                'kategori' => $kategori,
                'jumlah' => $jumlah,
                'kondisi' => $kondisi,
                'foto' => $fotoPath,
                'id' => $id,
            ]);
            flash('success', 'Data barang berhasil diperbarui.');
        }
        header('Location: index.php');
        exit;
    }

    // hapus barang
    if ($action === 'hapus' && $userRole === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);

        $cek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE barang_id = :id AND status = 'dipinjam'");
        $cek->execute(['id' => $id]);
        if ($cek->fetchColumn() > 0) {
            flash('error', 'Barang tidak dapat dihapus karena masih sedang dipinjam.');
        } else {
            try {
                $stmtFoto = $pdo->prepare('SELECT foto FROM barang WHERE id = :id');
                $stmtFoto->execute(['id' => $id]);
                $fotoHapus = $stmtFoto->fetchColumn();

                $stmt = $pdo->prepare('DELETE FROM barang WHERE id = :id');
                $stmt->execute(['id' => $id]);

                if ($fotoHapus) {
                    $fileDel = __DIR__ . '/' . $fotoHapus;
                    if (is_file($fileDel)) {
                        @unlink($fileDel);
                    }
                }
                flash('success', 'Barang berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Barang memiliki riwayat transaksi peminjaman sehingga tidak dapat dihapus.');
            }
        }
        header('Location: index.php');
        exit;
    }
}

// pencarian dan filter data barang
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
        b.foto,
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
    <title><?= e($pageTitle) ?> | IKP KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- pustaka qr code -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 px-3 sm:px-8 min-h-screen flex flex-col transition-all pb-6">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 flex flex-col gap-4 sm:gap-6">

            <section class="bg-white rounded-xl sm:rounded-2xl border border-slate-100 shadow-xs p-3 sm:p-6">

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

                <!-- filter dan pencarian -->
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

                <!-- tabel data barang -->
                <div class="w-full overflow-hidden sm:overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse table-auto">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider bg-slate-50/50 sm:bg-transparent">
                                <th class="py-2 px-3 w-10 hidden sm:table-cell">No.</th>
                                <th class="py-2 px-1.5 sm:px-3">Peralatan</th>
                                <th class="py-2 px-3 hidden md:table-cell">Kategori</th>
                                <th class="py-2 px-3 hidden lg:table-cell">Total Stok</th>
                                <th class="py-2 px-3 hidden lg:table-cell">Dipinjam</th>
                                <th class="py-2 px-1 sm:px-3 text-center sm:text-left">Tersedia</th>
                                <th class="py-2 px-3 hidden sm:table-cell">Kondisi</th>
                                <th class="py-2 px-1 sm:px-2 text-center w-8 sm:w-12">QR</th>
                                <th class="py-2 px-1.5 sm:px-3 text-right">Aksi</th>
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
                                        <td class="py-2 px-1.5 sm:py-2.5 sm:px-3 font-bold text-slate-800">
                                            <div class="flex items-center gap-2.5 sm:gap-3">
                                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/70 flex items-center justify-center text-slate-400">
                                                    <?php if (!empty($b['foto'])): ?>
                                                        <img src="<?= e($b['foto']) ?>" alt="<?= e($b['nama_barang']) ?>" class="w-full h-full object-cover" onerror="this.remove();">
                                                    <?php else: ?>
                                                        <i data-lucide="package" class="w-4 h-4 sm:w-5 sm:h-5 text-slate-400"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate max-w-[120px] sm:max-w-none text-xs sm:text-[13px] text-slate-800 font-bold"><?= e($b['nama_barang']) ?></div>
                                                    <div class="flex items-center gap-1 mt-0.5 sm:hidden text-[9px]">
                                                        <span class="text-slate-400 truncate max-w-[65px]"><?= e($b['kategori']) ?></span>
                                                        <span class="px-1 py-0.2 rounded font-semibold whitespace-nowrap text-[8.5px] <?= $kondisiBadge ?>"><?= e($b['kondisi']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 font-medium hidden md:table-cell"><?= e($b['kategori']) ?></td>
                                        <td class="py-2.5 px-3 font-bold text-slate-700 hidden lg:table-cell"><?= number_format((float) $b['jumlah'], 0, ',', '.') ?> unit</td>
                                        <td class="py-2.5 px-3 text-slate-500 hidden lg:table-cell"><?= (int) $b['sedang_dipinjam'] ?> unit</td>
                                        <td class="py-2 px-1 sm:py-2.5 sm:px-3 text-center sm:text-left">
                                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 sm:min-w-0 sm:h-auto px-1 sm:px-2 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap <?= $tersediaBadge ?>">
                                                <?= (int) $b['sisa_tersedia'] ?><span class="hidden sm:inline ml-0.5">unit</span>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 hidden sm:table-cell">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap <?= $kondisiBadge ?>">
                                                <?= e($b['kondisi']) ?>
                                            </span>
                                        </td>
                                        <!-- tombol qr code -->
                                        <td class="py-2 px-1 sm:py-2.5 sm:px-2 text-center">
                                            <button
                                                type="button"
                                                data-id="<?= (int) $b['id'] ?>"
                                                data-nama="<?= e($b['nama_barang']) ?>"
                                                onclick="bukaModalQRBarang(this)"
                                                title="Lihat QR Code"
                                                class="inline-flex items-center justify-center w-6 h-6 sm:w-8 sm:h-8 rounded-md sm:rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition">
                                                <i data-lucide="qr-code" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                            </button>
                                        </td>
                                        <td class="py-2 px-1.5 sm:py-2.5 sm:px-3 text-right">
                                            <div class="inline-flex items-center gap-1 sm:gap-1.5 justify-end">
                                                <?php if ($userRole === 'admin'): ?>
                                                    <!-- tombol edit -->
                                                    <button
                                                        type="button"
                                                        class="p-1 sm:px-2.5 sm:py-1.5 rounded-md sm:rounded-lg text-[11px] sm:text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center gap-1 transition"
                                                        data-id="<?= (int) $b['id'] ?>"
                                                        data-nama="<?= e($b['nama_barang']) ?>"
                                                        data-kategori="<?= e($b['kategori']) ?>"
                                                        data-jumlah="<?= (int) $b['jumlah'] ?>"
                                                        data-kondisi="<?= e($b['kondisi']) ?>"
                                                        data-foto="<?= e($b['foto'] ?? '') ?>"
                                                        onclick="editBarangDariBtn(this)"
                                                    >
                                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                        <span class="hidden sm:inline">Edit</span>
                                                    </button>

                                                    <!-- tombol hapus -->
                                                    <form method="post"
                                                        data-confirm="Hapus barang <?= e($b['nama_barang']) ?> dari inventaris?"
                                                        data-confirm-title="Hapus Peralatan"
                                                        data-confirm-icon="warning"
                                                        data-confirm-btn="Ya, Hapus"
                                                        data-confirm-color="#e11d48"
                                                        class="inline m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                        <button type="submit" class="p-1 sm:p-1.5 rounded-md sm:rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus Barang">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if ($b['sisa_tersedia'] > 0): ?>
                                                        <a href="../peminjaman/index.php?barang_id=<?= $b['id'] ?>" class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md sm:rounded-lg text-[11px] sm:text-xs font-bold bg-[#102b4e] hover:bg-[#1a3d6b] !text-white inline-flex items-center gap-1 shadow-2xs transition whitespace-nowrap">
                                                            <span class="!text-white font-bold">Pinjam</span>
                                                            <i data-lucide="arrow-right" class="w-3 h-3 sm:w-3.5 sm:h-3.5 !text-white"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded-md whitespace-nowrap">Habis</span>
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
        <!-- modal tambah barang -->
        <div id="modalTambahBarang" class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <h3 class="text-base font-bold text-[#102b4e]">Tambah Peralatan Inventaris</h3>
                    <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="post" enctype="multipart/form-data"
                    data-confirm="Simpan peralatan baru ini ke inventaris multimedia?"
                    data-confirm-title="Tambah Peralatan"
                    data-confirm-icon="question"
                    data-confirm-btn="Simpan Barang"
                    data-confirm-color="#102b4e"
                    class="flex flex-col gap-4">
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

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Foto Peralatan (Opsional)</label>
                        <div class="flex items-center gap-3">
                            <div id="previewTambahWrapper" class="w-12 h-12 rounded-xl bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center shrink-0 overflow-hidden text-slate-400">
                                <i data-lucide="image" class="w-5 h-5 text-slate-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <input type="file" name="foto" id="inputFotoTambah" accept="image/png, image/jpeg, image/webp" class="block w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#102b4e] hover:file:bg-blue-100 cursor-pointer" onchange="pratinjauFoto(this, 'previewTambahWrapper')">
                                <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, atau WebP. Maks 2 MB.</p>
                            </div>
                        </div>
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

        <!-- modal edit barang -->
        <div id="modalEditBarang" class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <h3 class="text-base font-bold text-[#102b4e]">Perbarui Data Peralatan</h3>
                    <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="post" id="formEditBarang" enctype="multipart/form-data"
                    data-confirm="Simpan perubahan data peralatan ini?"
                    data-confirm-title="Perbarui Data"
                    data-confirm-icon="question"
                    data-confirm-btn="Simpan Perubahan"
                    data-confirm-color="#102b4e"
                    class="flex flex-col gap-4">
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

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-slate-700">Foto Peralatan (Opsional)</label>
                        <div class="flex items-center gap-3">
                            <div id="edit_foto_preview" class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 overflow-hidden text-slate-400">
                                <i data-lucide="image" class="w-5 h-5 text-slate-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <input type="file" name="foto" id="edit_input_foto" accept="image/png, image/jpeg, image/webp" class="block w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#102b4e] hover:file:bg-blue-100 cursor-pointer" onchange="pratinjauFoto(this, 'edit_foto_preview')">
                                <div id="wrapperHapusFoto" class="hidden items-center gap-1.5 mt-1.5">
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-rose-600 font-semibold cursor-pointer">
                                        <input type="checkbox" name="hapus_foto" value="1" id="checkHapusFoto" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                        Hapus foto saat ini
                                    </label>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5">Biarkan kosong jika tidak ingin mengubah foto.</p>
                            </div>
                        </div>
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
        function editBarangDariBtn(btn) {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_nama').value = btn.dataset.nama;
            document.getElementById('edit_kategori').value = btn.dataset.kategori;
            document.getElementById('edit_jumlah').value = btn.dataset.jumlah;
            document.getElementById('edit_kondisi').value = btn.dataset.kondisi;
            
            // reset input file dan opsi hapus foto
            const fileInput = document.getElementById('edit_input_foto');
            if (fileInput) fileInput.value = '';
            const chkHapus = document.getElementById('checkHapusFoto');
            if (chkHapus) chkHapus.checked = false;

            const preview = document.getElementById('edit_foto_preview');
            const wrapperHapus = document.getElementById('wrapperHapusFoto');
            const fotoUrl = btn.dataset.foto;

            if (fotoUrl && fotoUrl.trim() !== '') {
                preview.innerHTML = `<img src="${fotoUrl}" class="w-full h-full object-cover">`;
                if (wrapperHapus) wrapperHapus.classList.remove('hidden');
                if (wrapperHapus) wrapperHapus.classList.add('flex');
            } else {
                preview.innerHTML = `<i data-lucide="image" class="w-5 h-5 text-slate-400"></i>`;
                if (wrapperHapus) wrapperHapus.classList.add('hidden');
                if (wrapperHapus) wrapperHapus.classList.remove('flex');
            }

            const modal = document.getElementById('modalEditBarang');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.style.display = 'flex';
                if (window.lucide) lucide.createIcons();
            }
        }

        function pratinjauFoto(input, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        </script>
    <?php endif; ?>

    <script src="../assets/js/script.js"></script>

    <!-- modal qr code -->
    <div id="modalQRCode" class="app-modal fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <!-- bagian atas modal -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i data-lucide="qr-code" class="w-5 h-5 text-indigo-600"></i>
                    <h3 class="text-sm font-bold text-slate-800">QR Code Barang</h3>
                </div>
                <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- isi modal qr -->
            <div class="p-6 flex flex-col items-center gap-4">
                <p class="text-sm font-semibold text-slate-700 text-center" id="qrNamaBarang"></p>

                <!-- gambar qr code -->
                <div class="p-4 bg-white border-2 border-slate-200 rounded-2xl shadow-inner">
                    <div id="qrCanvas"></div>
                </div>

                <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                    Scan QR ini dengan HP untuk melihat<br>status & detail barang secara langsung
                </p>

                <!-- tombol aksi -->
                <div class="flex flex-col gap-2 w-full">
                    <div class="flex gap-2.5 w-full">
                        <button type="button" onclick="downloadQR()"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold py-2.5 rounded-xl transition text-xs shadow-xs">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Unduh QR</span>
                        </button>
                        <button type="button" onclick="printQR()"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl transition text-xs">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            <span>Cetak</span>
                        </button>
                    </div>
                    <button type="button" data-modal-close
                        class="w-full py-2 text-center text-xs font-semibold text-slate-500 hover:text-slate-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentQRUrl = '';
    let qrInstance   = null;

    function bukaModalQRBarang(btn) {
        const id = btn.dataset.id;
        const nama = btn.dataset.nama || 'Peralatan';
        bukaModalQR(id, nama);
    }

    function bukaModalQR(id, nama) {
        const baseUrl = window.location.origin + window.location.pathname.replace('index.php', 'detail.php');
        const url = baseUrl + '?id=' + id;
        currentQRUrl = url;

        document.getElementById('qrNamaBarang').textContent = nama;

        const canvas = document.getElementById('qrCanvas');
        canvas.innerHTML = '';

        // buka modal terlebih dahulu
        const modal = document.getElementById('modalQRCode');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            if (window.lucide) lucide.createIcons();
        }

        // buat qr code dengan penanganan error
        try {
            if (typeof QRCode !== 'undefined') {
                qrInstance = new QRCode(canvas, {
                    text:         url,
                    width:        200,
                    height:       200,
                    colorDark:    '#102b4e',
                    colorLight:   '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H,
                });
                setTimeout(() => {
                    const img = canvas.querySelector('img');
                    if (img) img.removeAttribute('title');
                    const cnv = canvas.querySelector('canvas');
                    if (cnv) cnv.removeAttribute('title');
                }, 50);
            } else {
                throw new Error('Library QRCode belum siap');
            }
        } catch (err) {
            // fallback otomatis bila cdn qr code gagal dimuat
            const fallbackImg = document.createElement('img');
            fallbackImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&color=102b4e&data=' + encodeURIComponent(url);
            fallbackImg.alt = 'QR Code';
            fallbackImg.className = 'w-[200px] h-[200px] mx-auto rounded-lg';
            canvas.appendChild(fallbackImg);
        }
    }

    function downloadQR() {
        setTimeout(() => {
            const canvasEl = document.getElementById('qrCanvas').querySelector('canvas');
            const imgEl = document.getElementById('qrCanvas').querySelector('img');
            const link  = document.createElement('a');
            link.download = 'qr-barang-' + (currentQRUrl.split('id=')[1] || 'item') + '.png';
            
            if (canvasEl) {
                link.href = canvasEl.toDataURL('image/png');
                link.click();
            } else if (imgEl && imgEl.src) {
                link.href = imgEl.src;
                link.target = '_blank';
                link.click();
            }
        }, 100);
    }

    function printQR() {
        const canvasEl = document.getElementById('qrCanvas').querySelector('canvas');
        const imgEl = document.getElementById('qrCanvas').querySelector('img');
        const imgSrc = canvasEl ? canvasEl.toDataURL('image/png') : (imgEl ? imgEl.src : '');
        if (!imgSrc) return;

        const nama = document.getElementById('qrNamaBarang').textContent;
        const win = window.open('', '_blank');
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
                <p>IKP KOMINFO — Inventaris Multimedia</p>
                <small>${currentQRUrl}</small>
            </body></html>
        `);
        win.document.close();
    }
    </script>
</body>
</html>
