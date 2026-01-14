<?php
/**
 * Login view (layout: auth)
 */

$appName = (string)ssa_config('app.name', 'SSA Admin');

// Flash messages
$err = (string)(Session::flash('error') ?? '');
$ok  = (string)(Session::flash('ok') ?? '');
if ($err === '' && isset($error)) {
    $err = (string)$error;
}

// active tab: ?mode=master OR old input fallback
$activeTab = strtolower((string)($_GET['mode'] ?? ssa_old('mode', 'whmcs')));
if (!in_array($activeTab, ['whmcs', 'master'], true)) {
    $activeTab = 'whmcs';
}

$identifier = (string)ssa_old('identifier', '');
?>

<style>
  .login-sub{margin:10px 0 14px;color:rgba(233,238,252,.75);font-size:13px;line-height:1.35}
  .login-alert{margin:0 0 12px;padding:10px 12px;border-radius:14px;border:1px solid rgba(231,76,60,.35);background:rgba(231,76,60,.10);color:#ffd7d3;font-weight:650}
  .login-ok{margin:0 0 12px;padding:10px 12px;border-radius:14px;border:1px solid rgba(46,204,113,.35);background:rgba(46,204,113,.10);color:#d8ffe5;font-weight:650}
  .tabs{display:flex;gap:10px;margin:8px 0 14px}
  .tab-btn{flex:1;cursor:pointer;border-radius:14px;padding:10px 12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e9eefc;font-weight:850;transition:transform .12s ease,background .12s ease,border-color .12s ease}
  .tab-btn:hover{transform:translateY(-1px);background:rgba(255,255,255,.06)}
  .tab-btn.active{border-color:rgba(124,92,255,.45);background:rgba(124,92,255,.12)}

  .frow{display:flex;flex-direction:column;gap:6px;margin:10px 0}
  .flabel{font-size:12px;color:rgba(233,238,252,.72);font-weight:750}
  .finput{width:100%;padding:11px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e9eefc;outline:none}
  .finput:focus{border-color:rgba(124,92,255,.55);box-shadow:0 0 0 4px rgba(124,92,255,.16)}

  .actions{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:12px}
  .btn-primary{display:inline-flex;align-items:center;gap:8px;justify-content:center;cursor:pointer;border-radius:14px;padding:10px 12px;border:1px solid rgba(124,92,255,.55);background:linear-gradient(135deg, rgba(124,92,255,.55), rgba(55,120,255,.45));color:#fff;font-weight:900;min-width:120px}
  .btn-primary:hover{filter:brightness(1.05)}
  .btn-ghost{display:inline-flex;align-items:center;gap:8px;justify-content:center;cursor:pointer;border-radius:14px;padding:10px 12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.04);color:#e9eefc;font-weight:850}
  .btn-ghost:hover{background:rgba(255,255,255,.06)}

  .hint{margin-top:10px;color:rgba(233,238,252,.65);font-size:12px}
  .hint code{background:rgba(255,255,255,.06);padding:2px 6px;border-radius:8px}

  .tab{display:none}
  .tab.active{display:block}
</style>

<div class="login-sub">
  Autentificare staff folosind conturile din WHMCS (<code>tbladmins</code>).
  Dacă ai nevoie de acces de urgență, poți folosi și Master Key (fallback).
</div>

<?php if ($ok !== ''): ?>
  <div class="login-ok">✅ <?= ssa_e($ok) ?></div>
<?php endif; ?>

<?php if ($err !== ''): ?>
  <div class="login-alert">⚠️ <?= ssa_e($err) ?></div>
<?php endif; ?>

<div class="tabs" role="tablist" aria-label="Login tabs">
  <button class="tab-btn" type="button" data-tab="whmcs">🔐 WHMCS</button>
  <button class="tab-btn" type="button" data-tab="master">🗝️ Master Key</button>
</div>

<div class="tab" id="tab-whmcs">
  <form method="post" action="<?= ssa_base_url('login') ?>" autocomplete="on">
    <input type="hidden" name="mode" value="whmcs">

    <div class="frow">
      <div class="flabel">Username / Email (WHMCS)</div>
      <input class="finput" type="text" name="identifier" value="<?= ssa_e($identifier) ?>" placeholder="ex: admin@domeniu.com" required>
    </div>

    <div class="frow">
      <div class="flabel">Parolă</div>
      <input class="finput" type="password" name="password" placeholder="••••••••" required>
    </div>

    <div class="actions">
      <button class="btn-primary" type="submit">➡️ Login</button>
      <a class="btn-ghost" href="<?= ssa_base_url('setup') ?>">🧪 Setup</a>
    </div>

    <div class="hint">
      Folosim automat rolul din WHMCS (<code>tbladminroles</code>) și îl mapăm în panel.
    </div>
  </form>
</div>

<div class="tab" id="tab-master">
  <form method="post" action="<?= ssa_base_url('login') ?>" autocomplete="off">
    <input type="hidden" name="mode" value="master">

    <div class="frow">
      <div class="flabel">Master Key</div>
      <input class="finput" type="password" name="master_key" placeholder="Master key" required>
    </div>

    <div class="actions">
      <button class="btn-primary" type="submit">➡️ Intră</button>
      <a class="btn-ghost" href="<?= ssa_base_url('setup') ?>">🧪 Setup</a>
    </div>

    <div class="hint">
      Master Key e fallback. Dacă <code>auth.master_key</code> e gol, merge și cu <code>app.app_key</code>.
    </div>
  </form>
</div>

<script>
  (function(){
    var initial = "<?= ssa_e($activeTab) ?>";
    var btns = document.querySelectorAll('.tab-btn');
    var tabs = {
      whmcs: document.getElementById('tab-whmcs'),
      master: document.getElementById('tab-master')
    };

    function setTab(key){
      if(!tabs[key]) key = 'whmcs';
      Object.keys(tabs).forEach(function(k){
        if(tabs[k]) tabs[k].classList.toggle('active', k === key);
      });
      btns.forEach(function(b){
        b.classList.toggle('active', b.getAttribute('data-tab') === key);
      });
    }

    btns.forEach(function(b){
      b.addEventListener('click', function(){
        setTab(b.getAttribute('data-tab'));
      });
    });

    setTab(initial);
  })();
</script>
