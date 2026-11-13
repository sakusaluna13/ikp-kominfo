<?php
declare(strict_types=1);

$msg = get_flash();

$displayName = $_SESSION['user']['nama'] ?? 'Pengguna';
$roleLabel = ($_SESSION['user']['role'] ?? '') === 'admin'
    ? 'Administrator'
    : 'Peminjam';
?>

<!-- TOPBAR -->
<header class="h-20 flex items-center justify-between border-b border-slate-200/80 gap-4 mb-6">
    <div class="flex items-center gap-3">
        <!-- MOBILE HAMBURGER BUTTON -->
        <button 
            type="button" 
            id="mobileMenu" 
            class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition"
            aria-label="Buka menu navigasi"
        >
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="flex flex-col">
            <span class="text-[10px] font-extrabold tracking-wider text-blue-600 uppercase">
                <?= e($pageSubtitle ?? 'SISTEM INFORMASI') ?>
            </span>
            <h1 class="text-xl font-extrabold text-[#102b4e] tracking-tight">
                <?= e($pageTitle ?? 'Dashboard') ?>
            </h1>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <!-- DATE CHIP -->
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200/80 text-slate-500 text-xs shadow-2xs">
            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400"></i>
            <span id="currentDate"></span>
        </div>

        <!-- PROFILE CHIP -->
        <div class="flex items-center gap-2.5 p-1.5 pl-2.5 rounded-full bg-white border border-slate-200/80 shadow-2xs">
            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                <?= e(strtoupper(substr($displayName, 0, 1))) ?>
            </div>

            <div class="hidden sm:flex flex-col min-w-0 pr-2">
                <span class="text-xs font-bold text-slate-800 leading-tight truncate"><?= e($displayName) ?></span>
                <span class="text-[10px] text-slate-400 leading-tight"><?= e($roleLabel) ?></span>
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
    <div class="flex items-center gap-2.5 px-4 py-3 mb-6 rounded-xl border text-xs font-medium shadow-2xs <?= $alertStyle ?>">
        <i data-lucide="<?= $alertIcon ?>" class="w-4 h-4 shrink-0"></i>
        <span><?= e($msg['message']) ?></span>
    </div>
<?php endif; ?>
