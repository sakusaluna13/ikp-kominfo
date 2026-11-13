<?php
$currentPage = $activePage ?? '';
$userRole = $_SESSION['user']['role'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logos">
            <img
                src="../assets/images/logo-kominfo.png"
                alt="KOMINFO"
                onerror="this.style.display='none'"
            >

            <img
                src="../assets/images/logo-sumut.png"
                alt="Pemda"
                onerror="this.style.display='none'"
            >
        </div>

        <div class="brand-text">
            <strong>SIMPEL MEDIA</strong>
            <span>KOMINFO · INVENTARIS</span>
        </div>

        <button
            class="sidebar-collapse"
            id="sidebarCollapse"
            aria-label="Ciutkan sidebar"
        >
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <div class="sidebar-caption">MENU UTAMA</div>

    <nav class="sidebar-nav">
        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="../dashboard/index.php">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <a class="nav-link <?= $currentPage === 'barang' ? 'active' : '' ?>" href="../barang/index.php">
            <i data-lucide="package"></i>
            <span>Data Barang</span>
        </a>

        <a class="nav-link <?= $currentPage === 'peminjaman' ? 'active' : '' ?>" href="../peminjaman/index.php">
            <i data-lucide="clipboard-list"></i>
            <span>Peminjaman Barang</span>
        </a>

        <a class="nav-link <?= $currentPage === 'pengembalian' ? 'active' : '' ?>" href="../pengembalian/index.php">
            <i data-lucide="package-check"></i>
            <span>Pengembalian Barang</span>
        </a>

        <a class="nav-link <?= $currentPage === 'riwayat' ? 'active' : '' ?>" href="../riwayat/index.php">
            <i data-lucide="history"></i>
            <span>Riwayat Peminjaman</span>
        </a>

        <?php if ($userRole === 'admin'): ?>
            <a class="nav-link <?= $currentPage === 'laporan' ? 'active' : '' ?>" href="../laporan/index.php">
                <i data-lucide="file-chart-column"></i>
                <span>Laporan</span>
            </a>

            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="../users/index.php">
                <i data-lucide="users"></i>
                <span>Manajemen Pengguna</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="help-card">
            <i data-lucide="shield-check"></i>

            <div>
                <strong>Sistem Terproteksi</strong>
                <span>Akses sesuai hak pengguna</span>
            </div>
        </div>

        <form
            action="../auth/logout.php"
            method="post"
            data-logout-form
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <button
                class="nav-link logout-link"
                type="button"
                data-logout-trigger
            >
                <i data-lucide="log-out"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<div
    class="sidebar-backdrop"
    id="sidebarBackdrop"
></div>

<div
    class="logout-modal"
    id="logoutModal"
    aria-hidden="true"
>
    <div
        class="modal-card"
        role="dialog"
        aria-modal="true"
    >
        <div class="modal-icon">
            <i data-lucide="log-out"></i>
        </div>

        <h3>Konfirmasi Logout</h3>

        <p>
            Apakah Anda yakin ingin keluar dari akun ini?
        </p>

        <div class="modal-actions">
            <button
                type="button"
                class="btn-secondary"
                data-logout-cancel
            >
                Batal
            </button>

            <button
                type="button"
                class="btn-danger"
                data-logout-confirm
            >
                Ya, Logout
            </button>
        </div>
    </div>
</div>