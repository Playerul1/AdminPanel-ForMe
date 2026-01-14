<?php
// Layout principal
$appName = (string)ssa_config('app.name', 'SmartSoftArt Admin');

// active page (pentru highlight în meniu) – o să îl setăm din controller mai târziu
$active = $active ?? '';
$pageTitle = $pageTitle ?? $appName;

// notificări (placeholder până facem NotificationService)
$notifCount = $notifCount ?? 0;

// user info (placeholder până facem Auth + staff din WHMCS)
$userName = $userName ?? 'Guest';
$userRole = $userRole ?? '—';

?><!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ssa_e($pageTitle) ?></title>

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= ssa_asset('css/core.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/layout.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/animations.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/responsive.css') ?>">

    <!-- Page CSS (opțional) -->
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= ssa_asset('css/pages/' . $pageCss) ?>">
    <?php endif; ?>
</head>
<body>

<div class="ssa-app" id="ssaApp">

    <!-- Mobile overlay -->
    <div class="ssa-overlay" id="ssaOverlay" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside class="ssa-sidebar" id="ssaSidebar" aria-label="Meniu">
        <div class="ssa-brand">
            <div class="ssa-logo-dot" aria-hidden="true"></div>
            <div class="ssa-brand-text">
                <div class="ssa-brand-title"><?= ssa_e($appName) ?></div>
                <div class="ssa-brand-subtitle">Staff Panel</div>
            </div>
        </div>

        <nav class="ssa-nav">
            <a class="ssa-nav-item <?= $active==='dashboard'?'is-active':'' ?>" href="<?= ssa_base_url('dashboard') ?>">
                <span class="ssa-ico" aria-hidden="true">🏠</span>
                <span>Dashboard</span>
            </a>

            <a class="ssa-nav-item <?= $active==='leads'?'is-active':'' ?>" href="<?= ssa_base_url('leads') ?>">
                <span class="ssa-ico" aria-hidden="true">🎯</span>
                <span>Lead-uri</span>
            </a>

            <a class="ssa-nav-item <?= $active==='webbuilder'?'is-active':'' ?>" href="<?= ssa_base_url('webbuilder') ?>">
                <span class="ssa-ico" aria-hidden="true">🧩</span>
                <span>Constructor Website</span>
            </a>

            <a class="ssa-nav-item <?= $active==='employees'?'is-active':'' ?>" href="<?= ssa_base_url('employees') ?>">
                <span class="ssa-ico" aria-hidden="true">👥</span>
                <span>Angajați</span>
            </a>

            <a class="ssa-nav-item <?= $active==='settings'?'is-active':'' ?>" href="<?= ssa_base_url('settings') ?>">
                <span class="ssa-ico" aria-hidden="true">⚙️</span>
                <span>Setări</span>
            </a>

            <div class="ssa-nav-sep"></div>

            <a class="ssa-nav-item <?= $active==='profile'?'is-active':'' ?>" href="<?= ssa_base_url('profile') ?>">
                <span class="ssa-ico" aria-hidden="true">🧑‍💼</span>
                <span>Profil</span>
            </a>

            <a class="ssa-nav-item" href="<?= ssa_base_url('logout') ?>">
                <span class="ssa-ico" aria-hidden="true">🚪</span>
                <span>Logout</span>
            </a>
        </nav>

        <div class="ssa-sidebar-footer">
            <div class="ssa-user-card">
                <div class="ssa-avatar" aria-hidden="true"><?= ssa_e(mb_strtoupper(mb_substr($userName, 0, 1))) ?></div>
                <div>
                    <div class="ssa-user-name"><?= ssa_e($userName) ?></div>
                    <div class="ssa-user-role"><?= ssa_e($userRole) ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <div class="ssa-main">
        <!-- Topbar -->
        <header class="ssa-topbar">
            <button class="ssa-icon-btn ssa-mobile-menu" id="ssaMenuBtn" aria-label="Deschide meniul">
                ☰
            </button>

            <div class="ssa-topbar-title">
                <?= ssa_e($pageTitle) ?>
            </div>

            <div class="ssa-topbar-actions">
                <a class="ssa-icon-btn ssa-notif-btn" href="<?= ssa_base_url('profile/notifications') ?>" aria-label="Notificări">
                    <span class="ssa-bell" aria-hidden="true">🔔</span>
                    <?php if ((int)$notifCount > 0): ?>
                        <span class="ssa-notif-badge ssa-glow"><?= (int)$notifCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Content -->
        <main class="ssa-content">
            <?= $content ?>
        </main>
    </div>
</div>

<!-- Core JS -->
<script src="<?= ssa_asset('js/core.js') ?>"></script>
<script src="<?= ssa_asset('js/ui.js') ?>"></script>

<!-- Page JS (opțional) -->
<?php if (!empty($pageJs)): ?>
    <script src="<?= ssa_asset('js/pages/' . $pageJs) ?>"></script>
<?php endif; ?>

</body>
</html>
