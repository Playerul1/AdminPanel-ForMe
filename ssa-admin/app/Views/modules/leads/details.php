<?php
$lead = $lead ?? [];
$canSeePhone = (bool)($canSeePhone ?? false);
$phoneDisplay = (string)($phoneDisplay ?? '—');

$id = (int)($lead['id'] ?? 0);
$name = $lead['business_name'] ?: ($lead['founder_name'] ?: ('Lead #' . $id));

$statusName  = (string)($lead['status_name'] ?? '—');
$statusColor = (string)($lead['status_color'] ?? '#7c5cff');
$country     = (string)($lead['country_name'] ?? '—');

$social = $lead['social'] ?? [];
$extra  = $lead['extra'] ?? [];

$acceptedFlash = (isset($_GET['accepted']) && (string)$_GET['accepted'] === '1');

function ssa_linkify($url): string {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    return $url;
}

function ssa_only_digits($s): string {
    return preg_replace('/\D+/', '', (string)$s);
}

function ssa_social_url(string $type, string $value): string {
    $v = trim($value);
    if ($v === '') return '';

    if (preg_match('~^https?://~i', $v)) return $v;

    $vv = ltrim($v, '@');

    switch ($type) {
        case 'facebook':  return 'https://facebook.com/' . $vv;
        case 'instagram': return 'https://instagram.com/' . $vv;
        case 'linkedin':
            if (strpos($vv, 'linkedin.com') !== false) return 'https://' . $vv;
            return 'https://www.linkedin.com/in/' . $vv;
        case 'telegram':  return 'https://t.me/' . $vv;
        case 'whatsapp':
            $digits = ssa_only_digits($v);
            if ($digits !== '') return 'https://wa.me/' . $digits;
            return $v;
        default:
            return $v;
    }
}

function ssa_has($v): bool { return trim((string)$v) !== ''; }

$websiteUrl = ssa_linkify((string)($lead['website'] ?? ''));

// base URL (poate include deja ?route=...)
$leadBaseUrl = ssa_base_url('leads/' . $id);
$qsSep = (strpos($leadBaseUrl, '?') !== false) ? '&' : '?';

// --- PHOTO (thumbnail standard + modal preview allow) ---
$photoPath = (string)($lead['photo_path'] ?? '');
$photoUrl = '';
if ($photoPath) {
    if (preg_match('~^https?://~i', $photoPath)) {
        $photoUrl = $photoPath;
    } else {
        $pp = ltrim($photoPath, '/');
        if (strpos($pp, 'ssa-admin/') === 0) $pp = substr($pp, strlen('ssa-admin/'));
        $photoUrl = ssa_base_url($pp);
    }
}

// initials fallback (if no photo)
$initials = 'L';
if (!empty($lead['business_name'])) {
    $parts = preg_split('/\s+/', trim((string)$lead['business_name']));
    $initials = strtoupper(substr($parts[0] ?? 'L', 0, 1) . substr($parts[1] ?? '', 0, 1));
} elseif (!empty($lead['founder_name'])) {
    $parts = preg_split('/\s+/', trim((string)$lead['founder_name']));
    $initials = strtoupper(substr($parts[0] ?? 'L', 0, 1) . substr($parts[1] ?? '', 0, 1));
}

$socialItems = [
    'facebook' => ['label'=>'Facebook',  'raw'=>(string)($social['facebook'] ?? ''),  'url'=>ssa_social_url('facebook',  (string)($social['facebook'] ?? ''))],
    'instagram'=> ['label'=>'Instagram', 'raw'=>(string)($social['instagram'] ?? ''), 'url'=>ssa_social_url('instagram', (string)($social['instagram'] ?? ''))],
    'linkedin' => ['label'=>'LinkedIn',  'raw'=>(string)($social['linkedin'] ?? ''),  'url'=>ssa_social_url('linkedin',  (string)($social['linkedin'] ?? ''))],
    'whatsapp' => ['label'=>'WhatsApp',  'raw'=>(string)($social['whatsapp'] ?? ''),  'url'=>ssa_social_url('whatsapp',  (string)($social['whatsapp'] ?? ''))],
    'telegram' => ['label'=>'Telegram',  'raw'=>(string)($social['telegram'] ?? ''),  'url'=>ssa_social_url('telegram',  (string)($social['telegram'] ?? ''))],
];
foreach ($socialItems as $k => $it) { if (!ssa_has($it['raw'])) unset($socialItems[$k]); }

$badge = function($name, $color){
    $color = $color ?: '#7c5cff';
    $safe = preg_replace('/[^#a-zA-Z0-9]/', '', (string)$color);
    return '<span class="badge" style="border-color:'.ssa_e($safe).'55;background:'.ssa_e($safe).'22">'.ssa_e($name).'</span>';
};

$kv = function(string $k, $v, bool $asLink=false){
    $v = ($v === null || $v === '') ? '—' : (string)$v;
    $val = $asLink && $v !== '—'
        ? '<a class="ssa-link" href="'.ssa_e($v).'" target="_blank" rel="noopener noreferrer">'.ssa_e($v).'</a>'
        : ssa_e($v);
    echo '<tr><td class="k">'.ssa_e($k).'</td><td class="v">'.$val.'</td></tr>';
};
?>

<style>
.details-grid{display:grid;gap:14px;grid-template-columns:1.2fr .8fr;}
@media (max-width: 980px){.details-grid{grid-template-columns:1fr;}}

.kv-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border-radius:14px;border:1px solid rgba(255,255,255,.08);}
.kv-table tr td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.06);vertical-align:top;}
.kv-table tr:last-child td{border-bottom:none;}
.kv-table .k{width:42%;color:rgba(233,238,252,.72);font-weight:650;}
.kv-table .v{color:#e9eefc;word-break:break-word;}

.section-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;}
.section-title h2{margin:0;font-size:15px;font-weight:900;letter-spacing:.2px;}
.ssa-link{color:#9fb7ff;text-decoration:none;}
.ssa-link:hover{text-decoration:underline;}

.header-card{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.lead-avatar{
  width:72px;height:72px;border-radius:16px;overflow:hidden;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.03);
  display:flex;align-items:center;justify-content:center;
}
.lead-avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.lead-avatar .txt{font-weight:900;color:#e9eefc;font-size:18px;letter-spacing:.5px;opacity:.95;}
@media (max-width: 520px){
  .lead-avatar{width:56px;height:56px;border-radius:14px;}
  .lead-avatar .txt{font-size:16px;}
}

.social-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;}
.social-btn{
  display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:14px;
  border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);
  text-decoration:none;color:#e9eefc;transition:transform .12s ease,border-color .12s ease,background .12s ease;
}
.social-btn:hover{transform:translateY(-1px);background:rgba(255,255,255,.05);border-color:rgba(124,92,255,.40);}
.ico{width:22px;height:22px;flex:0 0 22px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;background:rgba(124,92,255,.18);border:1px solid rgba(124,92,255,.35);}
.ico svg{width:16px;height:16px;fill:#c7b9ff;}

.phone-wrap{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.phone-blur{filter:blur(4px);user-select:none;cursor:not-allowed;padding:4px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);}
.copy-mini{border:none;padding:6px 10px;border-radius:999px;cursor:pointer;background:rgba(255,255,255,.06);color:#e9eefc;border:1px solid rgba(255,255,255,.10);}
.copy-mini:hover{background:rgba(255,255,255,.10);}

.ok-banner{
  border:1px solid rgba(46,204,113,.35);
  background:rgba(46,204,113,.12);
  color:#eafff2;
  padding:10px 12px;
  border-radius:14px;
  margin-bottom:12px;
  font-weight:750;
}

/* Image modal */
.img-modal{
  position:fixed;inset:0;display:none;align-items:center;justify-content:center;
  background:rgba(0,0,0,.65);z-index:9999;padding:18px;
}
.img-modal.open{display:flex;}
.img-modal .box{
  max-width:min(920px, 96vw);
  max-height:min(820px, 86vh);
  background:rgba(10,12,18,.92);
  border:1px solid rgba(255,255,255,.12);
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 20px 60px rgba(0,0,0,.5);
}
.img-modal img{width:100%;height:100%;object-fit:contain;display:block;}
.img-modal .bar{
  display:flex;justify-content:space-between;align-items:center;
  padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.10);
}
.img-modal .bar .t{font-weight:850}
.img-modal .bar button{
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
  color:#e9eefc;border-radius:12px;padding:8px 10px;cursor:pointer;
}
@media print{.no-print{display:none !important;} body{background:#fff !important;} .panel{border:1px solid #ddd !important; box-shadow:none !important;}}
</style>

<div class="ssa-fade-in">

  <div class="page-head no-print">
    <div>
      <h1>Detalii Lead #<?= (int)$id ?></h1>
      <div class="muted"><b><?= ssa_e($name) ?></b> • <?= $badge($statusName, $statusColor) ?> • <?= ssa_e($country) ?></div>
    </div>
    <div class="page-actions">
      <a class="btn" href="<?= ssa_base_url('leads') ?>">⬅️ Înapoi</a>

      <!-- ✅ ACCEPT -->
      <a class="btn btn-primary" href="<?= ssa_base_url('leads/' . $id . '/accept') ?>" onclick="return confirm('Sigur accepți acest lead?');">✅ Acceptă</a>

      <a class="btn" href="<?= ssa_base_url('leads/' . $id . '/edit') ?>">✏️ Editează</a>
      <a class="btn" href="<?= ssa_base_url('leads/' . $id . '/download') ?>">⬇️ Download</a>
      <button class="btn btn-ghost" onclick="window.print()">🖨️ Print</button>
    </div>
  </div>

  <?php if ($acceptedFlash): ?>
    <div class="ok-banner no-print">✅ Lead-ul a fost acceptat cu succes.</div>
  <?php endif; ?>

  <div class="details-grid">

    <!-- LEFT -->
    <div class="panel hover-lift" style="padding:14px">

      <div class="section-title">
        <h2>📌 Rezumat</h2>
      </div>

      <div class="header-card" style="margin-bottom:12px">
        <div class="lead-avatar no-print" role="button" tabindex="0"
             style="cursor:<?= $photoUrl ? 'zoom-in' : 'default' ?>;"
             onclick="<?= $photoUrl ? "SSA_openImg('".ssa_e($photoUrl)."','".ssa_e($name)."')" : "" ?>">
          <?php if ($photoUrl): ?>
            <img src="<?= ssa_e($photoUrl) ?>" alt="Lead photo">
          <?php else: ?>
            <div class="txt"><?= ssa_e($initials) ?></div>
          <?php endif; ?>
        </div>

        <div style="min-width:220px">
          <div style="font-weight:950;font-size:16px;line-height:1.1"><?= ssa_e($name) ?></div>
          <div class="muted" style="margin-top:6px">
            Lead #<?= (int)$id ?> • <?= $badge($statusName, $statusColor) ?>
          </div>
        </div>
      </div>

      <table class="kv-table">
        <?php $kv('Țara', (string)($lead['country_name'] ?? '—')); ?>
        <?php $kv('Oraș', (string)($lead['city'] ?? '—')); ?>
        <?php $kv('Status', strip_tags($statusName)); ?>
        <?php $kv('Sursă', (string)($lead['source_name'] ?? '—')); ?>
        <?php $kv('Tip afacere', (string)($lead['business_type_name'] ?? '—')); ?>
      </table>

      <?php if (!empty($lead['description'])): ?>
        <div style="height:12px"></div>
        <div class="section-title"><h2>📝 Descriere</h2></div>
        <div class="panel" style="padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02)">
          <div class="muted" style="white-space:pre-wrap"><?= ssa_e((string)$lead['description']) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($extra) && is_array($extra)): ?>
        <div style="height:12px"></div>
        <div class="section-title"><h2>🏷️ Alte clasificări</h2></div>
        <table class="kv-table">
          <?php foreach ($extra as $k => $v): ?>
            <?php
              if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
              $kk = is_string($k) ? $k : 'Info';
              $kv($kk, (string)$v);
            ?>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="panel hover-lift" style="padding:14px">
      <div class="section-title"><h2>☎️ Contact</h2></div>

      <table class="kv-table">
        <tr>
          <td class="k">Telefon</td>
          <td class="v">
            <div class="phone-wrap">
              <?php if ($canSeePhone && $phoneDisplay !== '—'): ?>
                <a class="ssa-link" href="tel:<?= ssa_e($phoneDisplay) ?>"><?= ssa_e($phoneDisplay) ?></a>
                <button class="copy-mini no-print" type="button" onclick="SSA_copyText('<?= ssa_e($phoneDisplay) ?>')">Copy</button>
              <?php else: ?>
                <span class="phone-blur" title="Telefon ascuns până la acceptare"><?= ssa_e($phoneDisplay) ?></span>
              <?php endif; ?>
            </div>

            <?php if (!$canSeePhone): ?>
              <div class="muted no-print" style="margin-top:8px">
                🔒 Telefonul este ascuns până când lead-ul este acceptat de tine (sau ai rol privileged).
              </div>
            <?php endif; ?>
          </td>
        </tr>

        <?php $kv('Website', $websiteUrl ?: '—', $websiteUrl ? true : false); ?>
        <?php $kv('Nr. înregistrare', (string)($lead['company_reg_no'] ?? '—')); ?>
      </table>

      <?php if (!empty($socialItems)): ?>
        <div style="height:12px"></div>
        <div class="section-title"><h2>🌐 Social Media</h2></div>

        <div class="social-grid">
          <?php
          $svg = [
            'facebook' => '<svg viewBox="0 0 24 24"><path d="M22 12a10 10 0 1 0-11.56 9.88v-7H8v-2.88h2.44V9.8c0-2.4 1.43-3.73 3.62-3.73 1.05 0 2.15.19 2.15.19v2.37h-1.21c-1.19 0-1.56.74-1.56 1.5v1.8H16.9L16.5 15H14.2v7A10 10 0 0 0 22 12z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 4.5A5.5 5.5 0 1 1 6.5 12 5.5 5.5 0 0 1 12 8.5zm0 2A3.5 3.5 0 1 0 15.5 12 3.5 3.5 0 0 0 12 10.5zM17.8 6.2a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24"><path d="M6.94 6.5A2.44 2.44 0 1 1 4.5 4.06 2.44 2.44 0 0 1 6.94 6.5zM4.75 20h4.38V8.5H4.75V20zM13 8.5h-4.2V20H13v-6.3c0-3.35 4.35-3.62 4.35 0V20H21V12.2c0-6.03-6.3-5.8-8-2.84z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24"><path d="M12.04 2A9.9 9.9 0 0 0 2.1 11.88a9.76 9.76 0 0 0 1.33 5L2 22l5.2-1.37a9.93 9.93 0 0 0 4.84 1.23 9.88 9.88 0 0 0 0-19.76zm5.77 14.38c-.24.67-1.2 1.24-1.64 1.3-.41.06-.93.08-1.5-.1-.35-.11-.8-.26-1.38-.51-2.43-1.05-4.02-3.52-4.14-3.68-.12-.16-.98-1.31-.98-2.49 0-1.18.62-1.76.84-2 .22-.24.49-.3.65-.3h.47c.15 0 .35-.06.55.42.2.48.68 1.66.74 1.78.06.12.1.26.02.42-.08.16-.12.26-.24.4-.12.14-.25.31-.36.42-.12.12-.24.24-.1.48.14.24.64 1.05 1.37 1.7.95.85 1.75 1.11 1.99 1.23.24.12.38.1.52-.06.14-.16.6-.7.76-.94.16-.24.32-.2.53-.12.22.08 1.37.65 1.6.77.23.12.38.18.44.28.06.1.06.6-.18 1.27z"/></svg>',
            'telegram' => '<svg viewBox="0 0 24 24"><path d="M21.6 3.4 2.9 10.6c-1.3.5-1.3 1.2-.2 1.5l4.8 1.5 1.8 5.6c.2.6.4.6.8.3l2.8-2.3 5.8 4.2c1.1.6 1.8.3 2.1-1l3.6-17c.4-1.6-.6-2.3-1.8-1.8zM8.2 13.2l10.7-6.8c.5-.3 1-.1.6.2l-8.7 7.9-.3 3.3-1.6-4.6-.7-.2z"/></svg>',
          ];
          ?>
          <?php foreach ($socialItems as $key => $it): ?>
            <a class="social-btn" href="<?= ssa_e($it['url']) ?>" target="_blank" rel="noopener noreferrer">
              <span class="ico"><?= $svg[$key] ?? '' ?></span>
              <span style="font-weight:850"><?= ssa_e($it['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="height:12px"></div>
      <div class="section-title"><h2>📍 Adresă</h2></div>
      <table class="kv-table">
        <?php $kv('Țara', (string)($lead['country_name'] ?? '—')); ?>
        <?php $kv('Oraș', (string)($lead['city'] ?? '—')); ?>
        <?php $kv('Județ/Raion', (string)($lead['county'] ?? '—')); ?>
        <?php $kv('Adresa', (string)($lead['address'] ?? '—')); ?>
      </table>

      <div style="height:12px"></div>
      <div class="section-title"><h2>🧩 Încadrare</h2></div>
      <table class="kv-table">
        <?php $kv('Status', strip_tags($statusName)); ?>
        <?php $kv('Asignat către (ID)', (string)($lead['assigned_to'] ?? '—')); ?>
        <?php $kv('Acceptat de (ID)', (string)($lead['accepted_by'] ?? '—')); ?>
        <?php $kv('Acceptat la', (string)($lead['accepted_at'] ?? '—')); ?>
        <?php $kv('Creat la', (string)($lead['created_at'] ?? '—')); ?>
      </table>

    </div>

  </div>
</div>

<div class="img-modal no-print" id="ssaImgModal" onclick="SSA_closeImg(event)">
  <div class="box" onclick="event.stopPropagation()">
    <div class="bar">
      <div class="t" id="ssaImgTitle">Imagine</div>
      <button type="button" onclick="SSA_closeImg()">Închide ✕</button>
    </div>
    <img id="ssaImgEl" src="" alt="preview">
  </div>
</div>

<script>
window.SSA_openImg = function(src, title){
  var m = document.getElementById('ssaImgModal');
  var img = document.getElementById('ssaImgEl');
  var t = document.getElementById('ssaImgTitle');
  if(!m || !img) return;
  img.src = src;
  if(t) t.textContent = title || 'Imagine';
  m.classList.add('open');
};
window.SSA_closeImg = function(e){
  if(e && e.target && e.target.id !== 'ssaImgModal') return;
  var m = document.getElementById('ssaImgModal');
  var img = document.getElementById('ssaImgEl');
  if(img) img.src = '';
  if(m) m.classList.remove('open');
};

window.SSA_copyText = function(text){
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function(){
        if (window.SSA && typeof SSA.toast === 'function') SSA.toast('Telefon copiat ✅','ok');
        else alert('Telefon copiat ✅');
      });
    } else {
      alert(text);
    }
  } catch(e) {
    alert(text);
  }
};

window.SSA_lockAccept = function(form){
  var btn = form.querySelector('button[type="submit"]');
  if(btn){
    btn.disabled = true;
    btn.textContent = '⏳ Se acceptă...';
  }
  return true;
};
</script>
