<?php
$appName   = (string)ssa_config('app.name', 'SmartSoftArt Admin');
$pageTitle = $pageTitle ?? ($appName . ' • Login');
?><!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ssa_e($pageTitle) ?></title>

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= ssa_asset('css/core.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/animations.css') ?>">
    <link rel="stylesheet" href="<?= ssa_asset('css/responsive.css') ?>">

    <!-- Auth page CSS (optional) -->
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= ssa_asset('css/pages/' . $pageCss) ?>">
    <?php endif; ?>
</head>
<body>

<style>
    /* Layout auth inline minimal (ca să fie gata chiar dacă uiți auth.css) */
    .auth-wrap{
        min-height:100vh;
        display:grid;
        place-items:center;
        padding: 18px;
        background:
          radial-gradient(900px 500px at 15% 10%, rgba(124,92,255,.22), transparent 55%),
          radial-gradient(900px 500px at 85% 15%, rgba(0,212,255,.14), transparent 55%),
          radial-gradient(900px 500px at 60% 90%, rgba(124,92,255,.14), transparent 55%),
          var(--bg);
    }
    .auth-card{
        width: 100%;
        max-width: 420px;
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,.10);
        background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.015));
        box-shadow: 0 18px 50px rgba(0,0,0,.45);
        overflow:hidden;
    }
    .auth-head{
        padding: 18px 18px 12px;
        display:flex;
        align-items:center;
        gap: 12px;
    }
    .auth-dot{
        width: 40px; height: 40px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(124,92,255,.85), rgba(0,212,255,.55));
        box-shadow: 0 0 0 7px rgba(124,92,255,.10);
    }
    .auth-title{font-weight: 900; letter-spacing:.2px}
    .auth-sub{font-size: 13px; color: rgba(233,238,252,.70); margin-top:2px}
    .auth-body{padding: 14px 18px 18px}
</style>

<div class="auth-wrap">
    <div class="auth-card ssa-fade-in">
        <div class="auth-head">
            <div class="auth-dot" aria-hidden="true"></div>
            <div>
                <div class="auth-title"><?= ssa_e($appName) ?></div>
                <div class="auth-sub">Autentificare Staff</div>
            </div>
        </div>

        <div class="auth-body">
            <?= $content ?>
        </div>
    </div>
</div>

<script src="<?= ssa_asset('js/core.js') ?>"></script>
</body>
</html>
