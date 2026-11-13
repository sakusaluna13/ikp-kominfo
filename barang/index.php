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

        // Cek apakah barang sedang dipinjam
        $cek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE barang_id = :id AND status = 'dipinjam'");
        $cek->execute(['id' => $id]);
        if ($cek->fetchColumn() > 0) {
            flash('error', 'Barang tidak dapat dihapus karena masih berstatus sedang dipinjam.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM barang WHERE id = :id');
                $stmt->execute(['id' => $id]);
                flash('success', 'Barang berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Barang memiliki riwayat transaksi peminjaman sehingga tidak dapat dihapus. Anda dapat mengubah jumlahnya menjadi 0.');
            }
        }
        header('Location: index.php');
        exit;
    }
}

// ============================================
// FILTER & PENCARIAN
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
        SELECT barang_id, COUNT(*) AS dipinjam_count
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

// Daftar kategori unik untuk filter
$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM barang WHERE kategori != '' ORDER BY kategori ASC")->fetchAll(PDO::FETCH_COLUMN);
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
</head>
<body class="dashboard-body">

    <div class="app-shell">

        <?php require __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">

            <?php require __DIR__ . '/../includes/header.php'; ?>

            <section class="content-card" style="margin-top: 24px;">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">INVENTARIS MULTIMEDIA</span>
                        <h3>Daftar Inventaris Peralatan</h3>
                        <p>Kelola dan pantau stok aset multimedia Bidang IKP KOMINFO.</p>
                    </div>

                    <?php if ($userRole === 'admin'): ?>
                        <button type="button" class="btn-primary btn-sm" data-modal-open="modalTambahBarang">
                            <i data-lucide="plus"></i>
                            <span>Tambah Barang</span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- FILTER & PENCARIAN -->
                <form method="get" class="filter-bar">
                    <div class="filter-group">
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Cari nama atau kategori..."
                            value="<?= e($search) ?>"
                            style="min-width: 200px;"
                        >

                        <select name="kategori" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= e($kat) ?>" <?= $filterKategori === $kat ? 'selected' : '' ?>>
                                    <?= e($kat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="kondisi" class="form-select">
                            <option value="">Semua Kondisi</option>
                            <option value="Baik" <?= $filterKondisi === 'Baik' ? 'selected' : '' ?>>Baik</option>
                            <option value="Rusak Ringan" <?= $filterKondisi === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                            <option value="Rusak Berat" <?= $filterKondisi === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                        </select>

                        <button type="submit" class="btn-secondary btn-sm">
                            <i data-lucide="filter"></i>
                            <span>Filter</span>
                        </button>

                        <?php if ($search !== '' || $filterKategori !== '' || $filterKondisi !== ''): ?>
                            <a href="index.php" class="btn-outline btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>

                    <span class="table-count"><?= count($barangList) ?> unit terdata</span>
                </form>

                <!-- TABEL DATA BARANG -->
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Total Stok</th>
                                <th>Sedang Dipinjam</th>
                                <th>Tersedia</th>
                                <th>Kondisi</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$barangList): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i data-lucide="package-open"></i>
                                            <strong>Tidak ada barang ditemukan</strong>
                                            <span>Silakan ubah filter atau tambahkan data barang baru.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barangList as $i => $b): ?>
                                    <?php
                                    $kondisiBadge = match ($b['kondisi']) {
                                        'Baik' => 'badge-success',
                                        'Rusak Ringan' => 'badge-warning',
                                        default => 'badge-danger',
                                    };
                                    $tersediaBadge = $b['sisa_tersedia'] > 0 ? 'badge-info' : 'badge-danger';
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell">
                                            <?= e($b['nama_barang']) ?>
                                        </td>
                                        <td><?= e($b['kategori']) ?></td>
                                        <td><strong><?= number_format((float) $b['jumlah'], 0, ',', '.') ?></strong> unit</td>
                                        <td><?= (int) $b['sedang_dipinjam'] ?> unit</td>
                                        <td>
                                            <span class="status-badge <?= $tersediaBadge ?>">
                                                <?= (int) $b['sisa_tersedia'] ?> unit
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $kondisiBadge ?>">
                                                <?= e($b['kondisi']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="action-group" style="justify-content: flex-end;">
                                                <?php if ($userRole === 'admin'): ?>
                                                    <!-- TOMBOL EDIT -->
                                                    <button
                                                        type="button"
                                                        class="btn-secondary btn-sm"
                                                        onclick="editBarang(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)"
                                                        title="Edit Data"
                                                    >
                                                        <i data-lucide="edit-3"></i>
                                                        <span>Edit</span>
                                                    </button>

                                                    <!-- TOMBOL HAPUS -->
                                                    <form method="post" onsubmit="return confirm('Yakin ingin menghapus barang <?= e($b['nama_barang']) ?>?');" style="margin: 0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm" title="Hapus Barang">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- ROLE PEMINJAM -->
                                                    <?php if ($b['sisa_tersedia'] > 0): ?>
                                                        <a href="../peminjaman/index.php?barang_id=<?= $b['id'] ?>" class="btn-primary btn-sm">
                                                            <i data-lucide="arrow-right"></i>
                                                            <span>Pinjam</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-neutral">Stok Habis</span>
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

            <!-- FOOTER -->
            <?php require __DIR__ . '/../includes/footer.php'; ?>

        </main>
    </div>

    <?php if ($userRole === 'admin'): ?>
        <!-- MODAL TAMBAH BARANG -->
        <div class="app-modal" id="modalTambahBarang">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Barang Inventaris</h3>
                    <button type="button" class="modal-close" data-modal-close>
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-field">
                        <label>Nama Barang *</label>
                        <input type="text" name="nama_barang" class="form-input" placeholder="Contoh: Sony Alpha A7 III" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Kategori *</label>
                            <input type="text" name="kategori" class="form-input" list="kategoriPresets" placeholder="Pilih atau ketik kategori" required>
                            <datalist id="kategoriPresets">
                                <option value="Kamera">
                                <option value="Aksesori Kamera">
                                <option value="Audio">
                                <option value="Pencahayaan">
                                <option value="Perangkat Streaming">
                                <option value="Laptop & Komputer">
                                <option value="Kabel & Adapter">
                            </datalist>
                        </div>

                        <div class="form-field">
                            <label>Jumlah Stok (Unit) *</label>
                            <input type="number" name="jumlah" class="form-input" min="0" value="1" required>
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Kondisi Barang *</label>
                        <select name="kondisi" class="form-select">
                            <option value="Baik">Baik (Normal / Siap Pakai)</option>
                            <option value="Rusak Ringan">Rusak Ringan (Perlu perhatian)</option>
                            <option value="Rusak Berat">Rusak Berat (Tidak siap pakai)</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary btn-sm" data-modal-close>Batal</button>
                        <button type="submit" class="btn-primary btn-sm">Simpan Barang</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT BARANG -->
        <div class="app-modal" id="modalEditBarang">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Data Barang</h3>
                    <button type="button" class="modal-close" data-modal-close>
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <form method="post" id="formEditBarang">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="form-field">
                        <label>Nama Barang *</label>
                        <input type="text" name="nama_barang" id="edit_nama" class="form-input" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Kategori *</label>
                            <input type="text" name="kategori" id="edit_kategori" class="form-input" list="kategoriPresets" required>
                        </div>

                        <div class="form-field">
                            <label>Jumlah Stok (Unit) *</label>
                            <input type="number" name="jumlah" id="edit_jumlah" class="form-input" min="0" required>
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Kondisi Barang *</label>
                        <select name="kondisi" id="edit_kondisi" class="form-select">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary btn-sm" data-modal-close>Batal</button>
                        <button type="submit" class="btn-primary btn-sm">Perbarui Barang</button>
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
            modal.classList.add('show');
            if (window.lucide) lucide.createIcons();
        }
        </script>
    <?php endif; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>

