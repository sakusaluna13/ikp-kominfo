<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logos">
            <img
                src="../assets/images/logo-kominfo.png"
                alt="KOMINFO"
                onerror="this.style.display='none'"
            >

            <img
                src="../assets/images/logo-pemda.png"
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
        <a class="nav-link active" href="index.php">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <a
            class="nav-link disabled-link"
            href="#"
            aria-disabled="true"
            title="Segera dikembangkan"
        >
            <i data-lucide="package"></i>
            <span>Data Barang</span>
            <small>Segera</small>
        </a>

        <a
            class="nav-link disabled-link"
            href="#"
            aria-disabled="true"
        >
            <i data-lucide="clipboard-list"></i>
            <span>Peminjaman Barang</span>
            <small>Segera</small>
        </a>

        <a
            class="nav-link disabled-link"
            href="#"
            aria-disabled="true"
        >
            <i data-lucide="package-check"></i>
            <span>Pengembalian Barang</span>
            <small>Segera</small>
        </a>

        <a
            class="nav-link disabled-link"
            href="#"
            aria-disabled="true"
        >
            <i data-lucide="history"></i>
            <span>Riwayat Peminjaman</span>
            <small>Segera</small>
        </a>

        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <a
                class="nav-link disabled-link"
                href="#"
                aria-disabled="true"
            >
                <i data-lucide="file-chart-column"></i>
                <span>Laporan</span>
                <small>Segera</small>
            </a>

            <a
                class="nav-link disabled-link"
                href="#"
                aria-disabled="true"
            >
                <i data-lucide="users"></i>
                <span>Manajemen Pengguna</span>
                <small>Segera</small>
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