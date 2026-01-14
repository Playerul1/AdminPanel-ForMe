<?php
$leads = $leads ?? [];
$total = (int)($total ?? 0);
$page  = (int)($page ?? 1);
$pages = (int)($pages ?? 1);

$filters = $filters ?? [];
$q = (string)($filters['q'] ?? '');
$countryId = (int)($filters['country_id'] ?? 0);
$statusId  = (int)($filters['status_id'] ?? 0);
$sourceId  = (int)($filters['source_id'] ?? 0);
$assigned  = (string)($filters['assigned'] ?? '');

$countries = $countries ?? [];
$statuses  = $statuses ?? [];
$sources   = $sources ?? [];
$assignees = $assignees ?? [];

function build_qs(array $extra = []): string {
    $base = $_GET ?? [];
    unset($base['route']);
    $merged = array_merge($base, $extra);
    // curăță empty
    foreach ($merged as $k => $v) {
        if ($v === '' || $v === null || $v === 0 || $v === '0') {
            // păstrăm totuși page dacă e 1? -> îl scoatem
            if ($k === 'page') unset($merged[$k]);
            continue;
        }
    }
    $qs = http_build_query($merged);
    return $qs ? ('?' . $qs) : '';
}

$badge = function($name, $color){
    $color = $color ?: '#7c5cff';
    $safe = preg_replace('/[^#a-zA-Z0-9]/', '', (string)$color);
    return '<span class="badge" style="border-color:'.ssa_e($safe).'55;background:'.ssa_e($safe).'22">'.ssa_e($name).'</span>';
};
?>

<div class="ssa-fade-in">

    <div class="page-head">
        <div>
            <h1>Lead-uri</h1>
            <div class="muted">Total: <b><?= (int)$total ?></b> • Pagină: <b><?= (int)$page ?></b>/<b><?= (int)$pages ?></b></div>
        </div>

        <div class="page-actions">
            <a class="btn btn-primary" href="<?= ssa_base_url('leads') ?>">🔄 Refresh</a>
            <!-- Add Lead va fi în pasul următor (cu permisiuni) -->
            <a class="btn" href="<?= ssa_base_url('leads/create') ?>">➕ Adaugă Lead</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="panel hover-lift">
        <div class="panel-head">
            <div>
                <div class="panel-title">Filtre</div>
                <div class="panel-sub">Caută și filtrează după țară, status, sursă, asignare.</div>
            </div>
        </div>

        <form method="get" action="<?= ssa_base_url('leads') ?>">
            <div class="form-grid three">
                <div class="field">
                    <label>Căutare</label>
                    <input class="input" type="text" name="q" placeholder="Nume afacere / nume persoană / telefon / website" value="<?= ssa_e($q) ?>">
                </div>

                <div class="field">
                    <label>Țara</label>
                    <select name="country_id">
                        <option value="0">Toate</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $countryId) ? 'selected' : '' ?>>
                                <?= ssa_e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Status Lead</label>
                    <select name="status_id">
                        <option value="0">Toate</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id'] === $statusId) ? 'selected' : '' ?>>
                                <?= ssa_e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid three" style="margin-top:10px">
                <div class="field">
                    <label>Sursa</label>
                    <select name="source_id">
                        <option value="0">Toate</option>
                        <?php foreach ($sources as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id'] === $sourceId) ? 'selected' : '' ?>>
                                <?= ssa_e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Asignare</label>
                    <select name="assigned">
                        <?php foreach ($assignees as $a): ?>
                            <option value="<?= ssa_e($a['value']) ?>" <?= ((string)$a['value'] === $assigned) ? 'selected' : '' ?>>
                                <?= ssa_e($a['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help">“Lead-urile mele” folosește staff ID-ul user-ului logat (temporar: Owner).</div>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" type="submit">🎯 Aplică filtre</button>
                </div>
            </div>

            <div class="page-actions" style="margin-top:10px">
                <a class="btn btn-ghost" href="<?= ssa_base_url('leads') ?>">❌ Resetează</a>
                <a class="btn btn-primary" href="<?= ssa_base_url('leads') . build_qs(['assigned'=>'mine','page'=>1]) ?>">👤 Lead-urile mele</a>
            </div>
        </form>
    </div>

    <div style="height:14px"></div>

    <!-- Table -->
    <div class="panel hover-lift">
        <div class="panel-head">
            <div>
                <div class="panel-title">Listă Lead-uri</div>
                <div class="panel-sub">Nume afacere / persoană, telefon, țară, status, tip afacere, sursă, asignat.</div>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nume</th>
                    <th>Telefon</th>
                    <th>Țara</th>
                    <th>Status</th>
                    <th>Tip afacere</th>
                    <th>Sursă</th>
                    <th>Asignat</th>
                    <th>Acțiuni</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$leads): ?>
                    <tr>
                        <td colspan="9" class="muted">Nu există lead-uri încă. (În pasul următor facem Add Lead.)</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $row): ?>
                        <?php
                        $name = $row['business_name'] ?: $row['founder_name'] ?: ('Lead #' . (int)$row['id']);
                        $phone = $row['phone'] ?: '—';
                        $country = $row['country_name'] ?: '—';
                        $statusName = $row['status_name'] ?: '—';
                        $statusColor = $row['status_color'] ?: '#7c5cff';
                        $btype = $row['business_type_name'] ?: '—';
                        $source = $row['source_name'] ?: '—';
                        $assignedTo = $row['assigned_to'] ? ('#' . (int)$row['assigned_to']) : '—';
                        ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td>
                                <div style="font-weight:850"><?= ssa_e($name) ?></div>
                                <div class="muted" style="font-size:12px">
                                    <?= $row['website'] ? ssa_e($row['website']) : 'fără website' ?>
                                </div>
                            </td>
                            <td><?= ssa_e($phone) ?></td>
                            <td><?= ssa_e($country) ?></td>
                            <td><?= $badge($statusName, $statusColor) ?></td>
                            <td><?= ssa_e($btype) ?></td>
                            <td><?= ssa_e($source) ?></td>
                            <td><?= ssa_e($assignedTo) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-mini" href="<?= ssa_base_url('leads/' . (int)$row['id']) ?>">📄 Detalii</a>
                                    <a class="btn btn-mini btn-accept" href="<?= ssa_base_url('leads/' . (int)$row['id'] . '/accept') ?>">✅ Acceptă</a>
                                    <a class="btn btn-mini" href="<?= ssa_base_url('leads/' . (int)$row['id'] . '/edit') ?>">✏️ Editează</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div class="hr"></div>
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap">
                <div class="muted">Pagina <?= (int)$page ?> / <?= (int)$pages ?></div>
                <div class="actions">
                    <?php
                    $prev = max(1, $page - 1);
                    $next = min($pages, $page + 1);
                    ?>
                    <a class="btn btn-mini" href="<?= ssa_base_url('leads') . build_qs(['page'=>$prev]) ?>">⬅️ Prev</a>
                    <a class="btn btn-mini" href="<?= ssa_base_url('leads') . build_qs(['page'=>$next]) ?>">Next ➡️</a>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>
