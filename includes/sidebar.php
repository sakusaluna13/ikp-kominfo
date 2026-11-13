<?php
declare(strict_types=1);

$currentPage = $activePage ?? '';
$userRole = $_SESSION['user']['role'] ?? '';
?>

<!-- SIDEBAR BACKDROP (MOBILE) -->
<div 
    id="sidebarBackdrop" 
    class="fixed inset-0 bg-slate-900/40 z-30 hidden md:hidden transition-opacity"
></div>

<!-- SIDEBAR ASIDE -->
<aside 
    id="sidebar" 
    class="fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200 z-40 transition-transform duration-200 -translate-x-full md:translate-x-0 flex flex-col p-4 shadow-sm"
>
    <!-- BRAND / LOGO -->
    <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
        <div class="flex items-center gap-1.5 shrink-0">
            <img 
                src="../assets/images/logo-kominfo.png" 
                alt="KOMINFO" 
                class="w-8 h-8 object-contain"
                onerror="this.style.display='none'"
            >
            <img 
                src="../assets/images/logo-sumut.png" 
                alt="Pemda Sumut" 
                class="w-8 h-8 object-contain"
                onerror="this.style.display='none'"
            >
        </div>

        <div class="flex flex-col min-w-0">
            <span class="font-bold text-sm tracking-tight text-[#102b4e] leading-tight">SIMPEL MEDIA</span>
            <span class="text-[9px] font-semibold text-slate-400 tracking-wider">KOMINFO · INVENTARIS</span>
        </div>

        <!-- MOBILE CLOSE BUTTON -->
        <button 
            type="button" 
            id="sidebarClose" 
            class="ml-auto md:hidden text-slate-400 hover:text-slate-600 p-1"
            aria-label="Tutup menu"
        >
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- CAPTION -->
    <div class="px-3 text-[10px] font-extrabold tracking-wider text-slate-400 uppercase mt-4 mb-2">
        MENU UTAMA
    </div>

    <!-- NAVIGATION LINKS -->
    <nav class="flex flex-col gap-1 flex-1 overflow-y-auto">
        <a 
            href="../dashboard/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 <?= $currentPage === 'dashboard' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Dashboard</span>
        </a>

        <a 
            href="../barang/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'barang' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="package" class="w-4 h-4 shrink-0 <?= $currentPage === 'barang' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Data Barang</span>
        </a>

        <a 
            href="../barang/scan.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'scan' ? 'bg-indigo-50 text-indigo-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="scan-qr-code" class="w-4 h-4 shrink-0 <?= $currentPage === 'scan' ? 'text-indigo-700' : 'text-slate-400' ?>"></i>
            <span>Scan QR Barang</span>
            <span class="ml-auto text-[9px] font-bold bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full">NEW</span>
        </a>

        <a 
            href="../peminjaman/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'peminjaman' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0 <?= $currentPage === 'peminjaman' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Peminjaman Barang</span>
        </a>

        <a 
            href="../pengembalian/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'pengembalian' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="package-check" class="w-4 h-4 shrink-0 <?= $currentPage === 'pengembalian' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Pengembalian Barang</span>
        </a>

        <a 
            href="../riwayat/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'riwayat' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="history" class="w-4 h-4 shrink-0 <?= $currentPage === 'riwayat' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Riwayat Peminjaman</span>
        </a>

        <?php if ($userRole === 'admin'): ?>
            <div class="px-3 text-[10px] font-extrabold tracking-wider text-slate-400 uppercase mt-4 mb-2">
                ADMINISTRATOR
            </div>

            <a 
                href="../laporan/index.php" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'laporan' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
            >
                <i data-lucide="file-chart-column" class="w-4 h-4 shrink-0 <?= $currentPage === 'laporan' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
                <span>Laporan</span>
            </a>

            <a 
                href="../users/index.php" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'users' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
            >
                <i data-lucide="users" class="w-4 h-4 shrink-0 <?= $currentPage === 'users' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
                <span>Manajemen Pengguna</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- SIDEBAR BOTTOM -->
    <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 flex items-center gap-2.5">
            <i data-lucide="shield-check" class="w-5 h-5 text-blue-600 shrink-0"></i>
            <div class="flex flex-col min-w-0">
                <span class="text-[11px] font-bold text-slate-700 leading-tight">Sistem Terproteksi</span>
                <span class="text-[9px] text-slate-400 truncate">Hak akses terverifikasi</span>
            </div>
        </div>

        <form action="../auth/logout.php" method="post" id="logoutForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button 
                type="button" 
                data-modal-open="logoutModal" 
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition"
            >
                <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                <span>Keluar Akun</span>
            </button>
        </form>
    </div>
</aside>

<!-- MODAL KONFIRMASI LOGOUT -->
<div 
    id="logoutModal" 
    class="app-modal fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 transition-all"
    aria-hidden="true"
>
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl text-center animate-in fade-in zoom-in-95 duration-150">
        <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
            <i data-lucide="log-out" class="w-6 h-6"></i>
        </div>

        <h3 class="text-base font-bold text-[#102b4e] mb-1">Konfirmasi Logout</h3>
        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
            Apakah Anda yakin ingin keluar dari akun ini?
        </p>

        <div class="flex items-center justify-center gap-2.5">
            <button 
                type="button" 
                data-modal-close 
                class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
            >
                Batal
            </button>
            <button 
                type="button" 
                onclick="document.getElementById('logoutForm')?.submit();" 
                class="px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition"
            >
                Ya, Logout
            </button>
        </div>
    </div>
</div>
