<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage = 'pengembalian';
$pageTitle = 'Pengembalian Barang';
$pageSubtitle = 'TRANSAKSI PENGEMBALIAN';

$userRole = $_SESSION['user']['role'] ?? 'peminjam';
$userId = (int) $_SESSION['user']['id'];

// ============================================
// PROSES AKSI PENGEMBALIAN (ADMIN ONLY)
// ============================================
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
            // Update status peminjaman menjadi 'dikembalikan'
            $stmt = $pdo->prepare("
                UPDATE peminjaman
                SET status = 'dikembalikan', tanggal_kembali = :tgl_kembali
                WHERE id = :id AND status = 'dipinjam'
            ");
            $stmt->execute([
                'tgl_kembali' => $tanggal_kembali !== '' ? $tanggal_kembali : date('Y-m-d'),
                'id' => $peminjaman_id,
            ]);

            // Jika kondisi barang diperbarui saat pengembalian
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

// ============================================
// DATA BARANG YANG SEDANG DIPINJAM
// ============================================
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
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
        DATEDIFF(CURDATE(), p.tanggal_pinjam) AS lama_hari
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

// Riwayat pengembalian terbaru (7 hari terakhir / limit 5)
$riwayatKembaliStmt = $pdo->prepare("
    SELECT 
        p.id,
        u.nama AS nama_peminjam,
        b.nama_barang,
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

            <!-- DAFTAR BARANG YANG PERLU DIKEMBALIKAN -->
            <section class="content-card" style="margin-top: 24px;">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">MONITORING PENGEMBALIAN</span>
                        <h3>Peralatan yang Sedang Dipinjam</h3>
                        <p>
                            <?= $userRole === 'admin' 
                                ? 'Daftar barang multimedia yang saat ini masih berada di luar dan perlu dikonfirmasi saat kembali.' 
                                : 'Peralatan yang sedang Anda gunakan. Serahkan kembali ke ruang multimedia jika pemakaian telah selesai.' ?>
                        </p>
                    </div>

                    <span class="table-count"><?= count($listKembali) ?> unit belum kembali</span>
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
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$listKembali): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i data-lucide="package-check"></i>
                                            <strong>Tidak ada barang yang sedang dipinjam</strong>
                                            <span>Semua peralatan telah dikembalikan ke inventaris.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listKembali as $i => $row): ?>
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
                                            <span class="status-badge badge-warning">Sedang Dipinjam</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if ($userRole === 'admin'): ?>
                                                <button
                                                    type="button"
                                                    class="btn-success btn-sm"
                                                    onclick="bukaModalKembali(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
                                                >
                                                    <i data-lucide="check-square"></i>
                                                    <span>Konfirmasi Kembali</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="status-badge badge-neutral">Harap kembalikan ke Admin</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- RIWAYAT PENGEMBALIAN TERAKHIR -->
            <section class="content-card">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">LOG SELESAI</span>
                        <h3>Pengembalian Terbaru</h3>
                        <p>Catatan barang-barang yang telah selesai dikembalikan ke gudang inventaris.</p>
                    </div>

                    <a href="../riwayat/index.php" class="btn-outline btn-sm">
                        <span>Lihat Semua Riwayat</span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Nama Peminjam</th>
                                <th>Nama Barang</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Pengembalian</th>
                                <th>Kondisi Terakhir</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$riwayatKembali): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i data-lucide="history"></i>
                                            <strong>Belum ada transaksi pengembalian</strong>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayatKembali as $i => $rk): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="name-cell"><?= e($rk['nama_peminjam']) ?></td>
                                        <td><?= e($rk['nama_barang']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($rk['tanggal_pinjam'])) ?></td>
                                        <td><?= $rk['tanggal_kembali'] ? date('d/m/Y', strtotime($rk['tanggal_kembali'])) : '—' ?></td>
                                        <td>
                                            <span class="status-badge badge-neutral"><?= e($rk['kondisi']) ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge badge-success">Dikembalikan</span>
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
        <!-- MODAL KONFIRMASI PENGEMBALIAN -->
        <div class="app-modal" id="modalPengembalian">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Konfirmasi Pengembalian Barang</h3>
                    <button type="button" class="modal-close" data-modal-close>
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <form method="post" id="formPengembalian">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="proses_kembali">
                    <input type="hidden" name="peminjaman_id" id="kembali_peminjaman_id">
                    <input type="hidden" name="barang_id" id="kembali_barang_id">

                    <div style="background: #f8fafc; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                        <div style="font-size: 11px; color: #8292a5; margin-bottom: 4px;">Peminjam:</div>
                        <div style="font-weight: 700; color: var(--navy); font-size: 13px;" id="kembali_peminjam_nama">—</div>

                        <div style="font-size: 11px; color: #8292a5; margin-top: 8px; margin-bottom: 4px;">Barang:</div>
                        <div style="font-weight: 700; color: var(--navy); font-size: 13px;" id="kembali_barang_nama">—</div>
                    </div>

                    <div class="form-field">
                        <label>Tanggal Pengembalian Fisik *</label>
                        <input type="date" name="tanggal_kembali" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-field">
                        <label>Kondisi Barang Saat Kembali *</label>
                        <select name="kondisi_kembali" id="kembali_kondisi" class="form-select" required>
                            <option value="Baik">Baik (Normal / Tanpa Kerusakan)</option>
                            <option value="Rusak Ringan">Rusak Ringan (Lecet / Masalah Minor)</option>
                            <option value="Rusak Berat">Rusak Berat (Perlu Perbaikan / Rusak Total)</option>
                        </select>
                        <span class="hint">Status kondisi barang di inventaris akan otomatis disesuaikan.</span>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary btn-sm" data-modal-close>Batal</button>
                        <button type="submit" class="btn-success btn-sm">
                            <i data-lucide="check"></i>
                            <span>Konfirmasi Barang Diterima</span>
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
            modal.classList.add('show');
            if (window.lucide) lucide.createIcons();
        }
        </script>
    <?php endif; ?>

    <script src="../assets/js/script.js"></script>
</body>
</html>

