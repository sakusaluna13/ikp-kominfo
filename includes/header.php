<?php

$msg = get_flash();

$displayName = $_SESSION['user']['nama'];

$roleLabel = $_SESSION['user']['role'] === 'admin'
    ? 'Administrator'
    : 'Peminjam';
?>

<header class="topbar">
    <div class="topbar-left">
        <button
            class="mobile-menu"
            id="mobileMenu"
            aria-label="Buka menu"
        >
            <i data-lucide="menu"></i>
        </button>

        <div>
            <span class="eyebrow"><?= e($pageSubtitle ?? 'SISTEM INFORMASI') ?></span>
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
    </div>

    <div class="topbar-right">
        <div class="date-chip">
            <i data-lucide="calendar-days"></i>
            <span id="currentDate"></span>
        </div>

        <div class="profile-chip">
            <div class="avatar">
                <?= e(strtoupper(substr($displayName, 0, 1))) ?>
            </div>

            <div class="profile-text">
                <strong><?= e($displayName) ?></strong>
                <span><?= e($roleLabel) ?></span>
            </div>

            <i
                data-lucide="chevron-down"
                class="profile-chevron"
            ></i>
        </div>
    </div>
</header>

<?php if ($msg): ?>
    <div class="dashboard-alert alert-<?= e($msg['type']) ?>">
        <?= e($msg['message']) ?>
    </div>
<?php endif; ?>