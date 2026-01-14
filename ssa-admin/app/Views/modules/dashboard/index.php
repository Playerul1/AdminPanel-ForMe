<?php
$stats = $stats ?? [];
$leaderboardWeekly = $leaderboardWeekly ?? [];
$leaderboardAllTime = $leaderboardAllTime ?? [];
$tasks = $tasks ?? [];

$money = fn($n) => number_format((float)$n, 0, '.', ' ') . ' €';
?>
<div class="ssa-fade-in">

    <div class="page-head">
        <div>
            <h1>Salut, <?= ssa_e($userName ?? 'Guest') ?> 👋</h1>
            <div class="muted">Aici vezi statisticile, leaderboard-ul și task-urile tale.</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-primary" href="<?= ssa_base_url('leads') ?>">🎯 Vezi lead-uri</a>
            <a class="btn" href="<?= ssa_base_url('webbuilder') ?>">🧩 Constructor Website</a>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="stats">
        <div class="stat hover-lift">
            <div class="label">Lead-uri active</div>
            <div class="value"><?= (int)($stats['leads_active'] ?? 0) ?></div>
            <div class="meta">În lucru acum</div>
        </div>

        <div class="stat hover-lift">
            <div class="label">Lead-uri noi</div>
            <div class="value"><?= (int)($stats['leads_new'] ?? 0) ?></div>
            <div class="meta">Ultimele 24h</div>
        </div>

        <div class="stat hover-lift">
            <div class="label">Comenzi active</div>
            <div class="value"><?= (int)($stats['orders_active'] ?? 0) ?></div>
            <div class="meta">Website-uri la comandă</div>
        </div>

        <div class="stat hover-lift">
            <div class="label">Venit (astăzi)</div>
            <div class="value"><?= $money($stats['revenue_today_eur'] ?? 0) ?></div>
            <div class="meta">Din website-uri la comandă</div>
        </div>
    </div>

    <div class="grid" style="margin-top:14px; grid-template-columns: 1fr; gap:14px">
        <div class="panel hover-lift">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Statistici lead-uri</div>
                    <div class="panel-sub">Astăzi / săptămână / lună / an</div>
                </div>
            </div>

            <div class="chips">
                <span class="chip">Astăzi: <b><?= (int)($stats['leads_today'] ?? 0) ?></b></span>
                <span class="chip">Săptămâna: <b><?= (int)($stats['leads_week'] ?? 0) ?></b></span>
                <span class="chip">Luna: <b><?= (int)($stats['leads_month'] ?? 0) ?></b></span>
                <span class="chip">Anul: <b><?= (int)($stats['leads_year'] ?? 0) ?></b></span>
            </div>

            <div class="hr"></div>

            <div class="chips">
                <span class="chip">€ astăzi: <b><?= $money($stats['revenue_today_eur'] ?? 0) ?></b></span>
                <span class="chip">€ săptămâna: <b><?= $money($stats['revenue_week_eur'] ?? 0) ?></b></span>
                <span class="chip">€ luna: <b><?= $money($stats['revenue_month_eur'] ?? 0) ?></b></span>
            </div>
        </div>

        <div class="panel hover-lift">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Contracte</div>
                    <div class="panel-sub">Noi / acceptate / refuzate / în proces</div>
                </div>
            </div>

            <div class="chips">
                <span class="chip">Noi: <b><?= (int)($stats['contracts_new'] ?? 0) ?></b></span>
                <span class="chip">Acceptate: <b><?= (int)($stats['contracts_accepted'] ?? 0) ?></b></span>
                <span class="chip">Refuzate: <b><?= (int)($stats['contracts_rejected'] ?? 0) ?></b></span>
                <span class="chip">În proces: <b><?= (int)($stats['contracts_in_process'] ?? 0) ?></b></span>
            </div>
        </div>
    </div>

    <div class="grid" style="margin-top:14px; grid-template-columns: 1fr; gap:14px">
        <!-- Leaderboard -->
        <div class="panel hover-lift">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Leaderboard</div>
                    <div class="panel-sub">Cei mai activi angajați (lead-uri)</div>
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr; gap:12px">
                <div class="card">
                    <div class="card-inner">
                        <div class="panel-title">Săptămânal</div>
                        <div class="muted" style="font-size:13px">Top în ultimele 7 zile</div>

                        <div class="hr"></div>
                        <?php if (!$leaderboardWeekly): ?>
                            <div class="muted">Nu există date încă.</div>
                        <?php else: ?>
                            <?php foreach ($leaderboardWeekly as $i => $row): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <span class="badge"><?= (int)($i+1) ?></span>
                                        <b><?= ssa_e($row['name']) ?></b>
                                    </div>
                                    <span class="badge ok"><?= (int)$row['leads'] ?> lead-uri</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-inner">
                        <div class="panel-title">All-time</div>
                        <div class="muted" style="font-size:13px">Top din tot timpul</div>

                        <div class="hr"></div>
                        <?php if (!$leaderboardAllTime): ?>
                            <div class="muted">Nu există date încă.</div>
                        <?php else: ?>
                            <?php foreach ($leaderboardAllTime as $i => $row): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <span class="badge"><?= (int)($i+1) ?></span>
                                        <b><?= ssa_e($row['name']) ?></b>
                                    </div>
                                    <span class="badge"><?= (int)$row['leads'] ?> lead-uri</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks -->
        <div class="panel hover-lift">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Task-urile tale</div>
                    <div class="panel-sub">Bifează ce ai terminat (temporar mock)</div>
                </div>
            </div>

            <?php if (!$tasks): ?>
                <div class="muted">Nu ai task-uri setate.</div>
            <?php else: ?>
                <div class="grid" style="gap:10px">
                    <?php foreach ($tasks as $t): ?>
                        <label class="card" style="padding:12px; display:flex; gap:10px; align-items:flex-start; cursor:pointer">
                            <input type="checkbox" <?= !empty($t['done']) ? 'checked' : '' ?> style="margin-top:4px">
                            <div>
                                <div style="font-weight:800"><?= ssa_e($t['title']) ?></div>
                                <div class="muted" style="font-size:13px">Se va salva în DB când facem modulul Tasks.</div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
