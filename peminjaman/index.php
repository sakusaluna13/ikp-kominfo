<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'peminjaman';
$pageTitle = 'Peminjaman Barang';
$pageSubtitle = 'TRANSAKSI PEMINJAMAN';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';
$userId = (int) $_SESSION['user']['id'];

// ============================================
// PROSES AKSI POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    // 1. AJUKAN PEMINJAMAN (OLEH PEMINJAM)
    if ($action === 'ajukan_peminjam') {
        $barang_id = (int) ($_POST['barang_id'] ?? 0);
        $tanggal_pinjam = trim((string) ($_POST['tanggal_pinjam'] ?? date('Y-m-d')));
        $tanggal_kembali = trim((string) ($_POST['tanggal_kembali'] ?? ''));

        // Cek stok barang
        $stmt = $pdo->prepare("
            SELECT b.id, b.nama_barang, b.jumlah,
                   COALESCE(p.dipinjam_count, 0) AS sedang_dipinjam
            FROM barang b
            LEFT JOIN (
                SELECT barang_id, COUNT(*) AS dipinjam_count
                FROM peminjaman
                WHERE status = 'dipinjam'
                GROUP BY barang_id
            ) p ON p.barang_id = b.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $barang_id]);
        $barang = $stmt->fetch();

        if (!$barang) {
            flash('error', 'Barang yang dipilih tidak ditemukan.');
        } elseif (($barang['jumlah'] - $barang['sedang_dipinjam']) <= 0) {
            flash('error', 'Maaf, stok barang "' . $barang['nama_barang'] . '" saat ini sedang habis.');
        } else {
            $insert = $pdo->prepare("
                INSERT INTO peminjaman (user_id, barang_id, tanggal_pinjam, tanggal_kembali, status)
                VALUES (:uid, :bid, :tgl_pinjam, :tgl_kembali, 'menunggu persetujuan')
            ");
            $insert->execute([
                'uid' => $userId,
                'bid' => $barang_id,
                'tgl_pinjam' => $tanggal_pinjam,
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : null,
            ]);
            flash('success', 'Pengajuan peminjaman berhasil dikirim. Menunggu persetujuan Admin KOMINFO.');
        }
        header('Location: index.php');
        exit;
    }

    // 2. CATAT PEMINJAMAN LANGSUNG (OLEH ADMIN)
    if ($action === 'catat_admin' && $userRole === 'admin') {
        $peminjam_user_id = (int) ($_POST['user_id'] ?? 0);
        $barang_id = (int) ($_POST['barang_id'] ?? 0);
        $tanggal_pinjam = trim((string) ($_POST['tanggal_pinjam'] ?? date('Y-m-d')));
        $tanggal_kembali = trim((string) ($_POST['tanggal_kembali'] ?? ''));

        // Cek stok barang
        $stmt = $pdo->prepare("
            SELECT b.id, b.nama_barang, b.jumlah,
                   COALESCE(p.dipinjam_count, 0) AS sedang_dipinjam
            FROM barang b
            LEFT JOIN (
                SELECT barang_id, COUNT(*) AS dipinjam_count
                FROM peminjaman
                WHERE status = 'dipinjam'
                GROUP BY barang_id
            ) p ON p.barang_id = b.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $barang_id]);
        $barang = $stmt->fetch();

        if ($peminjam_user_id <= 0 || !$barang) {
            flash('error', 'Peminjam atau barang tidak valid.');
        } elseif (($barang['jumlah'] - $barang['sedang_dipinjam']) <= 0) {
            flash('error', 'Stok barang habis.');
        } else {
            $insert = $pdo->prepare("
                INSERT INTO peminjaman (user_id, barang_id, tanggal_pinjam, tanggal_kembali, status)
                VALUES (:uid, :bid, :tgl_pinjam, :tgl_kembali, 'dipinjam')
            ");
            $insert->execute([
                'uid' => $peminjam_user_id,
                'bid' => $barang_id,
                'tgl_pinjam' => $tanggal_pinjam,
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : null,
            ]);
            flash('success', 'Peminjaman berhasil dicatat dengan status: Dipinjam.');
        }
        header('Location: index.php');
        exit;
    }

    // 3. ADMIN: SETUJUI PEMINJAMAN
    if ($action === 'setujui' && $userRole === 'admin') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);

        // Cek stok kembali
        $stmt = $pdo->prepare("
            SELECT p.barang_id, b.jumlah, COALESCE(dip.cnt, 0) AS dipinjam_count
            FROM peminjaman p
            JOIN barang b ON b.id = p.barang_id
            LEFT JOIN (
                SELECT barang_id, COUNT(*) AS cnt FROM peminjaman WHERE status = 'dipinjam' GROUP BY barang_id
            ) dip ON dip.barang_id = p.barang_id
            WHERE p.id = :pid
        ");
        $stmt->execute(['pid' => $peminjaman_id]);
        $cek = $stmt->fetch();

        if (!$cek) {
            flash('error', 'Data pengajuan tidak ditemukan.');
        } elseif (($cek['jumlah'] - $cek['dipinjam_count']) <= 0) {
            flash('error', 'Gagal menyetujui: Stok barang ini saat ini sudah habis dipinjam transaksi lain.');
        } else {
            $up = $pdo->prepare("UPDATE peminjaman SET status = 'dipinjam' WHERE id = :id");
            $up->execute(['id' => $peminjaman_id]);
            flash('success', 'Pengajuan peminjaman telah disetujui. Barang resmi berstatus dipinjam.');
        }
        header('Location: index.php');
        exit;
    }

    // 4. ADMIN: TOLAK PEMINJAMAN
    if ($action === 'tolak' && $userRole === 'admin') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);
        $up = $pdo->prepare("UPDATE peminjaman SET status = 'ditolak' WHERE id = :id");
        $up->execute(['id' => $peminjaman_id]);
        flash('success', 'Pengajuan peminjaman telah ditolak.');
        header('Location: index.php');
        exit;
    }

    // 5. PEMINJAM: BATALKAN PENGAJUAN
    if ($action === 'batalkan_pengajuan') {
        $peminjaman_id = (int) ($_POST['peminjaman_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM peminjaman WHERE id = :id AND user_id = :uid AND status = 'menunggu persetujuan'");
        $del->execute(['id' => $peminjaman_id, 'uid' => $userId]);
        flash('success', 'Pengajuan peminjaman berhasil dibatalkan.');
        header('Location: index.php');
        exit;
    }
}

// ============================================
// DATA UNTUK FORM & TABEL
// ============================================

// Daftar barang dengan sisa stok
$barangTersedia = $pdo->query("
    SELECT 
        b.id, b.nama_barang, b.kategori, b.kondisi, b.jumlah,
        GREATEST(0, b.jumlah - COALESCE(p.dipinjam_count, 0)) AS sisa_tersedia
    FROM barang b
    LEFT JOIN (
        SELECT barang_id, COUNT(*) AS dipinjam_count
        FROM peminjaman
        WHERE status = 'dipinjam'
        GROUP BY barang_id
    ) p ON p.barang_id = b.id
    ORDER BY b.nama_barang ASC
")->fetchAll();

// Daftar users peminjam (untuk dropdown admin)
$userList = [];
if ($userRole === 'admin') {
    $userList = $pdo->query("SELECT id, nama, username FROM users WHERE status = 'aktif' ORDER BY nama ASC")->fetchAll();
}

// Data Pengajuan Menunggu Persetujuan
$pengajuanStmt = $pdo->prepare("
    SELECT p.id, u.nama AS nama_peminjam, b.nama_barang, b.kategori, p.tanggal_pinjam, p.tanggal_kembali, p.status, p.created_at
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

// Data Barang Sedang Dipinjam (Aktif)
$dipinjamStmt = $pdo->prepare("
    SELECT p.id, u.nama AS nama_peminjam, b.nama_barang, b.kategori, p.tanggal_pinjam, p.tanggal_kembali, p.status,
           DATEDIFF(CURDATE(), p.tanggal_pinjam) AS lama_hari
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

// Preset barang dari URL (jika user klik Pinjam dari menu Data Barang)
$selectedBarangId = (int) ($_GET['barang_id'] ?? 0);
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

            <!-- FORM PEMINJAMAN / PENGAJUAN -->
            <section class="content-card" style="margin-top: 24px;">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow"><?= $userRole === 'admin' ? 'PENCATATAN ADMIN' : 'FORMULIR PENGAJUAN' ?></span>
                        <h3><?= $userRole === 'admin' ? 'Catat Peminjaman Barang' : 'Ajukan Peminjaman Peralatan' ?></h3>
                        <p>
                            <?= $userRole === 'admin' 
                                ? 'Admin dapat langsung mencatat transaksi peminjaman atas nama pengguna.' 
                                : 'Pilih barang multimedia yang ingin dipinjam untuk kegiatan operasional KOMINFO.' ?>
                        </p>
                    </div>

                    <span class="status-badge badge-info">
                        <?= $userRole === 'admin' ? 'Akses Admin' : 'Permintaan Peminjam' ?>
                    </span>
                </div>

                <form method="post" style="margin-top: 10px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $userRole === 'admin' ? 'catat_admin' : 'ajukan_peminjam' ?>">

                    <div class="form-grid">
                        <?php if ($userRole === 'admin'): ?>
                            <div class="form-field">
                                <label>Pilih Peminjam (User) *</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih Peminjam --</option>
                                    <?php foreach ($userList as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= e($u['nama']) ?> (<?= e($u['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-field">
                            <label>Pilih Peralatan Multimedia *</label>
                            <select name="barang_id" class="form-select" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php foreach ($barangTersedia as $bt): ?>
                                    <option 
                                        value="<?= $bt['id'] ?>"
                                        <?= $selectedBarangId === (int) $bt['id'] ? 'selected' : '' ?>
                                        <?= $bt['sisa_tersedia'] <= 0 ? 'disabled' : '' ?>
                                    >
                                        <?= e($bt['nama_barang']) ?> - <?= e($bt['kategori']) ?> 
                                        (Tersedia: <?= $bt['sisa_tersedia'] ?> unit, Kondisi: <?= e($bt['kondisi']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Tanggal Pinjam *</label>
                            <input type="date" name="tanggal_pinjam" class="form-input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-field">
                            <label>Perkiraan Tanggal Pengembalian</label>
                            <input type="date" name="tanggal_kembali" class="form-input" min="<?= date('Y-m-d') ?>">
                            <span class="hint">Opsional (dapat ditentukan saat pengembalian)</span>
                        </div>
                    </div>

                    <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-primary btn-sm">
                            <i data-lucide="send"></i>
                            <span><?= $userRole === 'admin' ? 'Catat Peminjaman Sekarang' : 'Kirim Pengajuan Peminjaman' ?></span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- DAFTAR PENGAJUAN MENUNGGU PERSETUJUAN -->
            <section class="content-card">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">VERIFIKASI</span>
                        <h3>Pengajuan Menunggu Persetujuan</h3>
                        <p>Pengajuan yang memerlukan tindak lanjut sebelum barang diserahkan.</p>
                    </div>

                    <span class="table-count"><?= count($listPengajuan) ?> permintaan</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Tgl. Pinjam</th>
                                <th>Tgl. Rencana Kembali</th>
                                <th>Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$listPengajuan): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i data-lucide="check-circle-2"></i>
                                            <strong>Tidak ada antrean pengajuan</strong>
                                            <span>Semua pengajuan peminjaman telah diproses.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listPengajuan as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell"><?= e($row['nama_peminjam']) ?></td>
                                        <td><?= e($row['nama_barang']) ?></td>
                                        <td><?= e($row['kategori']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td>
                                            <span class="status-badge badge-warning">Menunggu Persetujuan</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="action-group" style="justify-content: flex-end;">
                                                <?php if ($userRole === 'admin'): ?>
                                                    <!-- SETUJUI -->
                                                    <form method="post" style="margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="setujui">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn-success btn-sm" title="Setujui Peminjaman">
                                                            <i data-lucide="check"></i>
                                                            <span>Setujui</span>
                                                        </button>
                                                    </form>

                                                    <!-- TOLAK -->
                                                    <form method="post" onsubmit="return confirm('Tolak permohonan peminjaman ini?');" style="margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="tolak">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm" title="Tolak Peminjaman">
                                                            <i data-lucide="x"></i>
                                                            <span>Tolak</span>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- PEMINJAM DAPAT MEMBATALKAN -->
                                                    <form method="post" onsubmit="return confirm('Batalkan pengajuan ini?');" style="margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="batalkan_pengajuan">
                                                        <input type="hidden" name="peminjaman_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm">
                                                            <i data-lucide="x-circle"></i>
                                                            <span>Batalkan</span>
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

            <!-- DAFTAR PEMINJAMAN AKTIF (SEDANG DIPINJAM) -->
            <section class="content-card">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">STATUS LAPANGAN</span>
                        <h3>Barang Sedang Dipinjam</h3>
                        <p><?= $userRole === 'admin' ? 'Seluruh peralatan yang saat ini sedang dibawa oleh peminjam.' : 'Peralatan yang saat ini sedang Anda pinjam.' ?></p>
                    </div>

                    <a href="../pengembalian/index.php" class="btn-secondary btn-sm">
                        <i data-lucide="arrow-right"></i>
                        <span>Ke Menu Pengembalian</span>
                    </a>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Durasi Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$listDipinjam): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i data-lucide="inbox"></i>
                                            <strong>Tidak ada barang yang sedang dipinjam</strong>
                                            <span>Semua peralatan berada di tempat inventaris.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listDipinjam as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell"><?= e($row['nama_peminjam']) ?></td>
                                        <td><?= e($row['nama_barang']) ?></td>
                                        <td><?= e($row['kategori']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '—' ?></td>
                                        <td>
                                            <strong><?= hitung_durasi($row['tanggal_pinjam'], $row['tanggal_kembali']) ?></strong> hari
                                        </td>
                                        <td>
                                            <span class="status-badge badge-info">Sedang Dipinjam</span>
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

    <script src="../assets/js/script.js"></script>
</body>
</html>

