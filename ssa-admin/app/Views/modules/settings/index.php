<?php
$tab = $tab ?? 'lead_statuses';

$tabs = [
  'lead_statuses' => ['Lead Statusuri', '🎯'],
  'call_statuses' => ['Call Statusuri', '📞'],
  'countries' => ['Țări', '🌍'],
  'sources' => ['Surse Lead', '📣'],
  'business' => ['Tipuri Afacere', '🏢'],
];

function ssa_checked($cond): string { return $cond ? 'checked' : ''; }
function tabUrl($k){ return ssa_base_url('settings?tab=' . urlencode($k)); }

$lists = [
  'countries'     => $countries ?? [],
  'sources'       => $sources ?? [],
  'business'      => $business ?? [],
  'lead_statuses' => $lead_statuses ?? [],
  'call_statuses' => $call_statuses ?? [],
];

$cur = $lists[$tab] ?? [];

$needsColor = in_array($tab, ['lead_statuses','call_statuses'], true);
$needsOrder = in_array($tab, ['lead_statuses','call_statuses'], true);
$needsCode  = ($tab === 'countries');

$title = $tabs[$tab][0] ?? 'Setări';
$icon  = $tabs[$tab][1] ?? '⚙️';
?>

<style>
.tabs{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 14px}
.tab{padding:8px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);text-decoration:none;color:#e9eefc;font-weight:800}
.tab.active{border-color:rgba(124,92,255,.50);background:rgba(124,92,255,.14)}
.table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.06);vertical-align:top}
.table th{background:rgba(255,255,255,.03);text-align:left;color:rgba(233,238,252,.80);font-size:12px;text-transform:uppercase;letter-spacing:.08em}
.table tr:last-child td{border-bottom:none}
.input,.select{padding:8px 10px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e9eefc;outline:none}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.small{font-size:12px;color:rgba(233,238,252,.72)}
</style>

<div class="ssa-fade-in">
  <div class="page-head">
    <div>
      <h1>Setări</h1>
      <div class="muted">Liste folosite în sistemul de lead-uri (statusuri, țări, surse, tipuri).</div>
    </div>
  </div>

  <div class="tabs">
    <?php foreach($tabs as $k => $t): ?>
      <a class="tab <?= $k===$tab?'active':'' ?>" href="<?= tabUrl($k) ?>"><?= $t[1] ?> <?= ssa_e($t[0]) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="panel" style="padding:14px;margin-bottom:14px">
    <h2 style="margin:0 0 8px;font-size:16px;font-weight:950"><?= $icon ?> <?= ssa_e($title) ?></h2>
    <div class="small">Adaugă / editează / șterge. (Atenție: ștergerea poate rupe lead-uri deja create.)</div>

    <div style="height:12px"></div>

    <!-- ADD -->
    <form method="post" action="<?= ssa_base_url('settings') ?>">
      <input type="hidden" name="tab" value="<?= ssa_e($tab) ?>">
      <input type="hidden" name="_action" value="add">

      <div class="row">
        <input class="input" name="name" placeholder="Nume" required>

        <?php if ($needsCode): ?>
          <input class="input" name="code" placeholder="ISO2 (ex: MD)">
        <?php endif; ?>

        <?php if ($needsColor): ?>
          <input class="input" name="color" placeholder="#7c5cff" value="#7c5cff">
        <?php endif; ?>

        <?php if ($needsOrder): ?>
          <input class="input" name="order_index" placeholder="Order" type="number" value="0" style="width:110px">
        <?php endif; ?>

        <select class="select" name="is_active">
          <option value="1" selected>Active</option>
          <option value="0">Inactive</option>
        </select>

        <button class="btn btn-primary" type="submit">➕ Adaugă</button>
      </div>
    </form>

    <div style="height:14px"></div>

    <!-- LIST -->
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nume</th>
          <?php if ($needsCode): ?><th>Code</th><?php endif; ?>
          <?php if ($needsColor): ?><th>Color</th><?php endif; ?>
          <?php if ($needsOrder): ?><th>Order</th><?php endif; ?>
          <th>Active</th>
          <th>Acțiuni</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($cur)): ?>
          <tr><td colspan="10" class="muted">Nu există încă în această listă.</td></tr>
        <?php endif; ?>

        <?php foreach($cur as $it): ?>
          <tr>
            <td><b>#<?= (int)$it['id'] ?></b></td>

            <td>
              <form method="post" action="<?= ssa_base_url('settings') ?>" class="row">
                <input type="hidden" name="tab" value="<?= ssa_e($tab) ?>">
                <input type="hidden" name="_action" value="update">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">

                <input class="input" name="name" value="<?= ssa_e((string)$it['name']) ?>" required>

                <?php if ($needsCode): ?>
                  <input class="input" name="code" value="<?= ssa_e((string)($it['code'] ?? '')) ?>" style="width:110px">
                <?php endif; ?>

                <?php if ($needsColor): ?>
                  <input class="input" name="color" value="<?= ssa_e((string)($it['color'] ?? '')) ?>" style="width:120px">
                <?php endif; ?>

                <?php if ($needsOrder): ?>
                  <input class="input" name="order_index" value="<?= (int)($it['order_index'] ?? 0) ?>" type="number" style="width:110px">
                <?php endif; ?>

                <select class="select" name="is_active">
                  <option value="1" <?= ((int)$it['is_active']===1?'selected':'') ?>>Active</option>
                  <option value="0" <?= ((int)$it['is_active']===0?'selected':'') ?>>Inactive</option>
                </select>

                <button class="btn btn-primary" type="submit">💾</button>
              </form>
            </td>

            <?php if ($needsCode): ?><td class="muted"><?= ssa_e((string)($it['code'] ?? '')) ?></td><?php endif; ?>
            <?php if ($needsColor): ?><td class="muted"><?= ssa_e((string)($it['color'] ?? '')) ?></td><?php endif; ?>
            <?php if ($needsOrder): ?><td class="muted"><?= (int)($it['order_index'] ?? 0) ?></td><?php endif; ?>

            <td><?= ((int)$it['is_active']===1) ? '✅' : '—' ?></td>

            <td>
              <form method="post" action="<?= ssa_base_url('settings') ?>" onsubmit="return confirm('Sigur ștergi?');">
                <input type="hidden" name="tab" value="<?= ssa_e($tab) ?>">
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <button class="btn" type="submit">🗑️ Șterge</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
