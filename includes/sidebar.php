<?php
declare(strict_types=1);

$currentPage = $activePage ?? '';
$userRole = $_SESSION['user']['role'] ?? '';
?>

<!-- latar belakang menu mobile -->
<div 
    id="sidebarBackdrop" 
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden md:hidden transition-opacity"
></div>

<!-- menu samping -->
<aside 
    id="sidebar" 
    class="fixed inset-y-0 left-0 w-72 sm:w-64 bg-white border-r border-slate-200 z-50 transition-transform duration-300 -translate-x-full md:translate-x-0 flex flex-col p-4 shadow-xl md:shadow-sm"
>
    <!-- logo dan judul -->
    <div class="flex items-center justify-between gap-2.5 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="flex items-center gap-1.5 shrink-0">
                <img 
                    src="../assets/images/logo-kominfo.png" 
                    alt="KOMINFO" 
                    class="h-7 w-auto object-contain"
                    onerror="this.style.display='none'"
                >
                <img 
                    src="../assets/images/logo-sumut.png" 
                    alt="Pemda Sumut" 
                    class="h-8 w-auto object-contain"
                    onerror="this.style.display='none'"
                >
            </div>

            <div class="flex flex-col min-w-0">
                <span class="font-extrabold text-sm tracking-tight text-[#102b4e] leading-tight truncate">IKP KOMINFO</span>
                <span class="text-[9px] font-semibold text-slate-400 tracking-wider uppercase whitespace-nowrap">Inventaris Multimedia</span>
            </div>
        </div>

        <!-- tombol tutup menu mobile -->
        <button 
            type="button" 
            id="sidebarClose" 
            class="md:hidden w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition shrink-0 cursor-pointer"
            aria-label="Tutup menu"
        >
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- judul kelompok menu -->
    <div class="px-3 text-[10px] font-extrabold tracking-wider text-slate-400 uppercase mt-4 mb-2">
        MENU UTAMA
    </div>

    <!-- daftar menu -->
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

        <a 
            href="../profile/index.php" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentPage === 'profile' ? 'bg-blue-50 text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>"
        >
            <i data-lucide="user-circle" class="w-4 h-4 shrink-0 <?= $currentPage === 'profile' ? 'text-blue-700' : 'text-slate-400' ?>"></i>
            <span>Profil Saya</span>
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

    <!-- bagian bawah menu -->
    <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 flex items-center gap-2.5">
            <i data-lucide="shield-check" class="w-5 h-5 text-blue-600 shrink-0"></i>
            <div class="flex flex-col min-w-0">
                <span class="text-[11px] font-bold text-slate-700 leading-tight">Sistem Terproteksi</span>
                <span class="text-[9px] text-slate-400 truncate">Hak akses terverifikasi</span>
            </div>
        </div>

        <div class="w-full pt-1">
            <button 
                type="button"
                onclick="bukaModalLogout(event)"
                class="w-full flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition active:scale-95 cursor-pointer"
            >
                <i data-lucide="log-out" class="w-4 h-4 text-white"></i>
                <span class="text-white font-bold">Keluar Akun</span>
            </button>
        </div>
    </div>
</aside>

<!-- modal konfirmasi keluar -->
<div 
    id="modalLogout" 
    class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4 transition-all"
    onclick="if(event.target===this) tutupModalLogout()"
>
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl text-center animate-in fade-in zoom-in-95 duration-150 border border-slate-100">
        <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3.5">
            <i data-lucide="log-out" class="w-7 h-7 text-rose-600"></i>
        </div>

        <h3 class="text-base font-bold text-[#102b4e] mb-1.5">Konfirmasi Logout</h3>
        <p class="text-xs text-slate-500 mb-6 leading-relaxed">
            Apakah Anda yakin ingin keluar dari akun sistem inventaris ini?
        </p>

        <div class="grid grid-cols-2 gap-3">
            <button 
                type="button" 
                onclick="tutupModalLogout()"
                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
            >
                Batal
            </button>
            <a 
                href="../auth/logout.php" 
                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition inline-flex items-center justify-center gap-1.5"
            >
                <i data-lucide="log-out" class="w-3.5 h-3.5 text-white"></i>
                <span class="text-white">Ya, Logout</span>
            </a>
        </div>
    </div>
</div>

<script>
function bukaModalLogout(e) {
    if (e) e.preventDefault();
    const m = document.getElementById('modalLogout');
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
        m.style.display = 'flex';
        if (window.lucide) lucide.createIcons();
    }
}
function tutupModalLogout() {
    const m = document.getElementById('modalLogout');
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
        m.style.display = 'none';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupModalLogout();
});
</script>
