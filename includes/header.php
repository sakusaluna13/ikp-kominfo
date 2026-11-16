<?php
declare(strict_types=1);

$msg = get_flash();

$displayName = $_SESSION['user']['nama'] ?? 'Pengguna';
$roleLabel = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'Administrator' : 'Peminjam';
$photoUrl = $_SESSION['user']['photo'] ?? '';
?>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-[min(90vw,320px)] sm:w-80 pointer-events-none [&>*]:pointer-events-auto"></div>

<!-- TOPBAR -->
<header class="h-14 sm:h-16 flex items-center justify-between border-b border-slate-200/80 gap-3 mb-4 sm:mb-6">
    <div class="flex items-center gap-2.5 min-w-0">
        <!-- MOBILE HAMBURGER BUTTON -->
        <button 
            type="button" 
            id="mobileMenu"
            data-open-sidebar
            class="md:hidden p-2 rounded-xl text-slate-700 bg-white border border-slate-200 shadow-2xs hover:bg-slate-50 active:scale-95 transition flex items-center justify-center shrink-0"
            aria-label="Buka menu navigasi"
        >
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="flex flex-col min-w-0">
            <span class="text-[9px] font-bold tracking-wider text-blue-600 uppercase hidden sm:block leading-none mb-0.5">
                <?= e($pageSubtitle ?? 'SISTEM INFORMASI') ?>
            </span>
            <h1 class="text-sm sm:text-lg font-extrabold text-[#102b4e] tracking-tight truncate leading-tight">
                <?= e($pageTitle ?? 'Dashboard') ?>
            </h1>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <!-- DATE CHIP -->
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200/80 text-slate-500 text-xs shadow-2xs">
            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400"></i>
            <span id="currentDate"></span>
        </div>

        <!-- PROFILE DROPDOWN -->
        <div class="relative" id="profileDropdownWrap">
            <button 
                type="button"
                id="profileDropdownBtn"
                class="flex items-center gap-2 p-1.5 pl-2.5 rounded-full bg-white border border-slate-200/80 shadow-2xs hover:bg-slate-50 transition"
            >
                <?php if ($photoUrl): ?>
                    <img src="<?= e($photoUrl) ?>" class="w-8 h-8 rounded-full object-cover" alt="Foto Profil" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-800 font-extrabold text-xs items-center justify-center shrink-0 hidden">
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    </div>
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    </div>
                <?php endif; ?>

                <div class="hidden sm:flex flex-col min-w-0 pr-1 text-left">
                    <span class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[100px]"><?= e($displayName) ?></span>
                    <span class="text-[10px] text-slate-400 leading-tight"><?= e($roleLabel) ?></span>
                </div>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 mr-1 hidden sm:block"></i>
            </button>

            <!-- DROPDOWN MENU -->
            <div 
                id="profileDropdown" 
                class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl border border-slate-200 shadow-xl py-1.5 z-50"
            >
                <a href="../profile/index.php" class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    <i data-lucide="user-circle" class="w-4 h-4 text-slate-400"></i>
                    Edit Profil
                </a>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" data-modal-open="logoutModal" class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Keluar
                </button>
            </div>
        </div>
    </div>
</header>

<!-- NOTIFIKASI FLASH -->
<?php if ($msg): ?>
    <?php
    $alertStyle = match ($msg['type']) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error' => 'bg-rose-50 border-rose-200 text-rose-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        default => 'bg-sky-50 border-sky-200 text-sky-800',
    };
    $alertIcon = match ($msg['type']) {
        'success' => 'check-circle-2',
        'error' => 'alert-triangle',
        default => 'info',
    };
    ?>
    <div data-flash-message class="flex items-center gap-2.5 px-4 py-3 mb-6 rounded-xl border text-xs font-medium shadow-2xs <?= $alertStyle ?>">
        <i data-lucide="<?= $alertIcon ?>" class="w-4 h-4 shrink-0"></i>
        <span><?= e($msg['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-current opacity-50 hover:opacity-100">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>
<?php endif; ?>
