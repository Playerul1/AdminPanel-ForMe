<?php
/**
 * Placeholder page view
 * Primește:
 * - $title
 * - $subtitle
 * - $next
 */
$title = $title ?? 'Pagină';
$subtitle = $subtitle ?? 'În lucru...';
$next = $next ?? [];
?>

<div class="ssa-fade-in">
    <div class="page-head">
        <div>
            <h1><?= ssa_e($title) ?></h1>
            <div class="muted"><?= ssa_e($subtitle) ?></div>
        </div>

        <?php if (!empty($next['href']) && !empty($next['label'])): ?>
            <div class="page-actions">
                <a class="btn btn-primary" href="<?= ssa_base_url($next['href']) ?>">
                    <?= ssa_e($next['icon'] ?? '➡️') ?> <?= ssa_e($next['label']) ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel hover-lift">
        <div class="panel-title">Status</div>
        <div class="panel-sub">
            Pagina este pregătită ca structură/UI. Următorul pas: conectăm la DB + permisiuni + acțiuni.
        </div>

        <div class="hr"></div>

        <div class="grid" style="grid-template-columns: 1fr; gap:12px">
            <div class="card">
                <div class="card-inner">
                    <div style="font-weight:850">✅ UI/UX</div>
                    <div class="muted" style="margin-top:6px">
                        Layout responsive, sidebar mobil, topbar, butoane, animații.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-inner">
                    <div style="font-weight:850">🔒 Acces</div>
                    <div class="muted" style="margin-top:6px">
                        Ruta este protejată (necesită login). Mai târziu: SSO din WHMCS.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-inner">
                    <div style="font-weight:850">🧠 Urmează</div>
                    <div class="muted" style="margin-top:6px">
                        Aici adăugăm logica exactă pe care ai cerut-o (lead-uri, apeluri, comenzi, facturi, permisiuni).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
