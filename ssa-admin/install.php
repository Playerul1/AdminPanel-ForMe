<?php
declare(strict_types=1);

/**
 * SSA Admin Panel - Installer (Panel DB only)
 * - creează tabele pentru Lead-uri + Apeluri + setări (țări/surse/statusuri)
 * - seed cu valori default
 *
 * Rulează o singură dată:
 * https://admin.smartsoftart.com/ssa-admin/install.php
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/app/Core/Helpers.php';
require __DIR__ . '/app/Core/Session.php';

Session::start();

function pdo_from_panel_config(): PDO
{
    $cfg = (array)ssa_config('db.panel_db', []);

    $host = (string)($cfg['host'] ?? 'localhost');
    $db   = (string)($cfg['database'] ?? '');
    $user = (string)($cfg['username'] ?? '');
    $pass = (string)($cfg['password'] ?? '');
    $charset = (string)($cfg['charset'] ?? 'utf8mb4');

    if ($db === '' || $user === '') {
        throw new RuntimeException("Config db.panel_db lipsă (database/username). Verifică app/Config/config.php");
    }

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // good defaults
    $pdo->exec("SET NAMES {$charset}");
    $pdo->exec("SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    return $pdo;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function now(): string { return date('Y-m-d H:i:s'); }

$err = '';
$ok = '';
$details = [];

try {
    $pdo = pdo_from_panel_config();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do_install'] ?? '') === '1') {

        // 1) Create tables
        $sql = [];

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_countries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            iso2 CHAR(2) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_country_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // --- Staff users (synced from WHMCS tbladmins) ---
        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_staff_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            whmcs_admin_id INT UNSIGNED NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(190) DEFAULT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            whmcs_role_id INT UNSIGNED DEFAULT NULL,
            whmcs_role_name VARCHAR(190) DEFAULT NULL,
            panel_role VARCHAR(50) NOT NULL DEFAULT 'staff',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            perms_json TEXT DEFAULT NULL,
            last_login_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_whmcs_admin (whmcs_admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_lead_sources (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_source_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_business_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_btype_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_lead_statuses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            color VARCHAR(32) NOT NULL DEFAULT '#7c5cff',
            sort INT NOT NULL DEFAULT 0,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_lstatus_name (name),
            KEY idx_lstatus_sort (sort)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_call_statuses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            color VARCHAR(32) NOT NULL DEFAULT '#00d4ff',
            requires_followup TINYINT(1) NOT NULL DEFAULT 0,
            sort INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_cstatus_name (name),
            KEY idx_cstatus_sort (sort)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_leads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            business_name VARCHAR(190) NULL,
            founder_name  VARCHAR(190) NULL,

            phone VARCHAR(60) NULL,
            country_id INT UNSIGNED NULL,
            city   VARCHAR(120) NULL,
            county VARCHAR(120) NULL,
            address TEXT NULL,
            website VARCHAR(190) NULL,

            business_type_id INT UNSIGNED NULL,
            source_id INT UNSIGNED NULL,

            status_id INT UNSIGNED NOT NULL,
            assigned_to INT UNSIGNED NULL,
            assigned_by INT UNSIGNED NULL,

            accepted_by INT UNSIGNED NULL,
            accepted_at DATETIME NULL,

            company_reg_no VARCHAR(120) NULL,

            social_json JSON NULL,   -- facebook/instagram/linkedin/whatsapp/telegram etc
            photo_path VARCHAR(255) NULL,

            extra_json JSON NULL,    -- alte clasificari (pe tari)
            description TEXT NULL,   -- descriere

            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME NULL,

            KEY idx_leads_status (status_id),
            KEY idx_leads_country (country_id),
            KEY idx_leads_source (source_id),
            KEY idx_leads_assigned (assigned_to),
            KEY idx_leads_created (created_at),

            CONSTRAINT fk_leads_country FOREIGN KEY (country_id) REFERENCES ssa_countries(id)
                ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT fk_leads_source FOREIGN KEY (source_id) REFERENCES ssa_lead_sources(id)
                ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT fk_leads_btype FOREIGN KEY (business_type_id) REFERENCES ssa_business_types(id)
                ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT fk_leads_status FOREIGN KEY (status_id) REFERENCES ssa_lead_statuses(id)
                ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql[] = "CREATE TABLE IF NOT EXISTS ssa_lead_calls (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lead_id BIGINT UNSIGNED NOT NULL,

            caller_staff_id INT UNSIGNED NULL,

            started_at DATETIME NOT NULL,
            ended_at DATETIME NULL,
            duration_seconds INT UNSIGNED NULL,

            call_status_id INT UNSIGNED NOT NULL,
            lead_status_before INT UNSIGNED NULL,
            lead_status_after  INT UNSIGNED NULL,

            notes LONGTEXT NULL,
            follow_up_at DATETIME NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            KEY idx_calls_lead (lead_id),
            KEY idx_calls_status (call_status_id),
            KEY idx_calls_started (started_at),

            CONSTRAINT fk_calls_lead FOREIGN KEY (lead_id) REFERENCES ssa_leads(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_calls_status FOREIGN KEY (call_status_id) REFERENCES ssa_call_statuses(id)
                ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        foreach ($sql as $q) {
            $pdo->exec($q);
        }

        // 2) Seed defaults (idempotent)
        $seedCountries = [
            ['Moldova', 'MD'],
            ['România', 'RO'],
            ['Italia', 'IT'],
            ['Germania', 'DE'],
            ['Spania', 'ES'],
            ['Franța', 'FR'],
            ['Regatul Unit', 'GB'],
        ];
        $stmt = $pdo->prepare("INSERT IGNORE INTO ssa_countries(name, iso2) VALUES(?, ?)");
        foreach ($seedCountries as $c) { $stmt->execute([$c[0], $c[1]]); }

        $seedSources = ['Facebook', 'Instagram', 'LinkedIn', 'WhatsApp', 'Telegram', 'Website', 'Recomandare', 'Telefon'];
        $stmt = $pdo->prepare("INSERT IGNORE INTO ssa_lead_sources(name) VALUES(?)");
        foreach ($seedSources as $s) { $stmt->execute([$s]); }

        $seedTypes = ['Restaurant', 'Salon / Beauty', 'Cabinet Medical', 'Magazin Online', 'Service Auto', 'Imobiliare', 'Educație', 'Altul'];
        $stmt = $pdo->prepare("INSERT IGNORE INTO ssa_business_types(name) VALUES(?)");
        foreach ($seedTypes as $t) { $stmt->execute([$t]); }

        // lead statuses (cu culori + default)
        $leadStatuses = [
            ['Nou', '#7c5cff', 10, 1],
            ['Asignat', '#00d4ff', 20, 0],
            ['În discuție', '#f1c40f', 30, 0],
            ['Revenire', '#f39c12', 40, 0],
            ['Succes', '#2ecc71', 50, 0],
            ['Refuzat', '#e74c3c', 60, 0],
        ];
        $stmt = $pdo->prepare("INSERT IGNORE INTO ssa_lead_statuses(name, color, sort, is_default) VALUES(?, ?, ?, ?)");
        foreach ($leadStatuses as $ls) { $stmt->execute($ls); }

        // call statuses (cu follow-up required)
        $callStatuses = [
            ['Apel reușit', '#2ecc71', 0, 10],
            ['Nu a răspuns', '#f1c40f', 0, 20],
            ['Client agresiv', '#e74c3c', 0, 30],
            ['Revenire mai târziu', '#f39c12', 1, 40],
            ['Număr greșit', '#9aa4b2', 0, 50],
        ];
        $stmt = $pdo->prepare("INSERT IGNORE INTO ssa_call_statuses(name, color, requires_followup, sort) VALUES(?, ?, ?, ?)");
        foreach ($callStatuses as $cs) { $stmt->execute($cs); }

        // 3) Ensure upload folders
        $uploadDir = __DIR__ . '/storage/uploads/leads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        // block php execution in uploads (best effort)
        $ht = __DIR__ . '/storage/uploads/.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\nDeny from all\n</FilesMatch>\n");
        }

        $ok = "Instalare completă ✅ (tabele create + seed default).";
        $details[] = "Următorul pas: Lead-uri reale în UI (/leads) + Add Lead + Detalii + Acceptă + Apeluri.";
    }

} catch (Throwable $e) {
    $err = $e->getMessage();
}

?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SSA Admin • Install</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#0b0e14;color:#e9eefc}
    .wrap{max-width:900px;margin:0 auto;padding:24px}
    .card{background:#121829;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px;margin:16px 0}
    .muted{color:rgba(233,238,252,.75)}
    .btn{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(124,92,255,.20);color:#e9eefc;cursor:pointer}
    .btn:hover{opacity:.95}
    code{background:rgba(255,255,255,.06);padding:2px 6px;border-radius:8px}
    .ok{border:1px solid rgba(46,204,113,.35);background:rgba(46,204,113,.10);padding:12px 14px;border-radius:14px}
    .bad{border:1px solid rgba(231,76,60,.35);background:rgba(231,76,60,.10);padding:12px 14px;border-radius:14px}
    ul{margin:10px 0 0 20px}
  </style>
</head>
<body>
<div class="wrap">
  <h1>SSA Admin • Installer</h1>
  <p class="muted">Asta creează tabelele în <b>panel_db</b> (nu atinge WHMCS DB).</p>

  <div class="card">
    <div class="muted">URL instalare:</div>
    <div><code><?= ssa_e((string)ssa_base_url('ssa-admin/install.php')) ?></code></div>
  </div>

  <?php if ($err): ?>
    <div class="card"><div class="bad">Eroare: <?= ssa_e($err) ?></div></div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div class="card">
      <div class="ok"><?= ssa_e($ok) ?></div>
      <?php if ($details): ?>
        <ul class="muted">
          <?php foreach ($details as $d): ?>
            <li><?= ssa_e($d) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3>Rulează instalarea</h3>
    <p class="muted">Apasă o singură dată. E safe (CREATE IF NOT EXISTS + INSERT IGNORE).</p>
    <form method="post">
      <input type="hidden" name="do_install" value="1">
      <button class="btn" type="submit">🧱 Creează tabele + seed default</button>
    </form>
  </div>

  <div class="card">
    <h3>După instalare</h3>
    <p class="muted">
      Intră în panel: <code>https://admin.smartsoftart.com/leads</code> (după ce îți dau următoarele fișiere din FAZA 4).
    </p>
  </div>
</div>
</body>
</html>
