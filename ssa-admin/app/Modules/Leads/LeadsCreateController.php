<?php
/**
 * LeadsCreateController
 * - GET  /leads/create  => form
 * - POST /leads/create  => insert + upload image
 *
 * DB: panel_db (ssa_* tables)
 */

class LeadsCreateController extends Controller
{
    private function pdo(): PDO
    {
        if (class_exists('DB') && method_exists('DB', 'pdo')) {
            /** @var PDO $pdo */
            $pdo = DB::pdo('panel_db');
            return $pdo;
        }

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
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function fetchAll(PDO $pdo, string $sql): array
    {
        return $pdo->query($sql)->fetchAll();
    }

    private function getDefaultLeadStatusId(PDO $pdo): int
    {
        $st = $pdo->query("SELECT id FROM ssa_lead_statuses WHERE is_default=1 ORDER BY sort ASC LIMIT 1");
        $id = (int)($st->fetchColumn() ?: 0);
        if ($id > 0) return $id;

        $st = $pdo->query("SELECT id FROM ssa_lead_statuses ORDER BY sort ASC LIMIT 1");
        return (int)($st->fetchColumn() ?: 0);
    }

    private function parseExtra(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];

        $lines = preg_split('/\R/u', $text) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // format: key: value
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if ($k !== '') {
                    $out[$k] = $v;
                }
            } else {
                // fallback: keep raw
                $out[] = $line;
            }
        }

        if (!$out) {
            return ['raw' => $text];
        }
        return $out;
    }

    private function handleUpload(?array $file): ?string
    {
        if (!$file || empty($file['name'])) {
            return null;
        }

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload eșuat (error code).');
        }

        $max = 5 * 1024 * 1024; // 5MB
        if (!isset($file['size']) || (int)$file['size'] > $max) {
            throw new RuntimeException('Imagine prea mare. Max 5MB.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('Upload invalid.');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException('Format imagine invalid. Permis: JPG, PNG, WEBP.');
        }

        // Verify MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowedMime = ['image/jpeg','image/png','image/webp'];
        if (!in_array($mime, $allowedMime, true)) {
            throw new RuntimeException('Fișierul nu pare imagine validă.');
        }

        // Destination (date folders)
        $year = date('Y');
        $month = date('m');
        $relDir = "storage/uploads/leads/{$year}/{$month}";
        $absDir = dirname(__DIR__, 3) . '/' . $relDir; // .../ssa-admin + rel

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $absPath = $absDir . '/' . $name;

        if (!move_uploaded_file($tmp, $absPath)) {
            throw new RuntimeException('Nu am putut salva imaginea.');
        }

        // return path relative to /ssa-admin
        return $relDir . '/' . $name;
    }

    public function show(): void
    {
        $pdo = $this->pdo();
        $u = Auth::user();

        $countries = $this->fetchAll($pdo, "SELECT id, name FROM ssa_countries ORDER BY name ASC");
        $sources   = $this->fetchAll($pdo, "SELECT id, name FROM ssa_lead_sources ORDER BY name ASC");
        $types     = $this->fetchAll($pdo, "SELECT id, name FROM ssa_business_types ORDER BY name ASC");
        $statuses  = $this->fetchAll($pdo, "SELECT id, name, color FROM ssa_lead_statuses ORDER BY sort ASC, name ASC");

        $defaultStatusId = $this->getDefaultLeadStatusId($pdo);

        $this->view('modules/leads/create', [
            'active' => 'leads',
            'pageTitle' => 'Adaugă Lead',
            'userName' => $u['name'] ?? 'User',
            'userRole' => $u['role'] ?? 'Staff',
            'notifCount' => 2,

            'countries' => $countries,
            'sources' => $sources,
            'types' => $types,
            'statuses' => $statuses,
            'defaultStatusId' => $defaultStatusId,
        ], 'app');
    }

    public function store(): void
    {
        if (!ssa_is_post()) {
            $this->redirectTo('leads/create');
        }

        $pdo = $this->pdo();
        $u = Auth::user();

        $business_name = trim((string)$this->input('business_name', ''));
        $founder_name  = trim((string)$this->input('founder_name', ''));

        $phone   = trim((string)$this->input('phone', ''));
        $country = (int)$this->input('country_id', 0);
        $city    = trim((string)$this->input('city', ''));
        $county  = trim((string)$this->input('county', ''));
        $address = trim((string)$this->input('address', ''));
        $website = trim((string)$this->input('website', ''));

        $business_type_id = (int)$this->input('business_type_id', 0);
        $source_id        = (int)$this->input('source_id', 0);
        $status_id        = (int)$this->input('status_id', 0);

        $assigned_to = trim((string)$this->input('assigned_to', '')); // temporar: staff ID numeric
        $assigned_to_id = (ctype_digit($assigned_to) && (int)$assigned_to > 0) ? (int)$assigned_to : null;

        $company_reg_no = trim((string)$this->input('company_reg_no', ''));

        $social = [
            'facebook'  => trim((string)$this->input('social_facebook', '')),
            'instagram' => trim((string)$this->input('social_instagram', '')),
            'linkedin'  => trim((string)$this->input('social_linkedin', '')),
            'whatsapp'  => trim((string)$this->input('social_whatsapp', '')),
            'telegram'  => trim((string)$this->input('social_telegram', '')),
        ];
        // remove empty
        foreach ($social as $k => $v) {
            if ($v === '') unset($social[$k]);
        }

        $extra_text = (string)$this->input('extra_text', '');
        $extra = $this->parseExtra($extra_text);

        $description = trim((string)$this->input('description', ''));

        // Validare minimă (poți adăuga mai strict ulterior)
        $errors = [];

        if ($business_name === '' && $founder_name === '') {
            $errors[] = 'Completează Numele afacerii sau Numele fondatorului.';
        }
        if ($phone === '') {
            $errors[] = 'Completează numărul de telefon.';
        }
        if ($country <= 0) {
            $errors[] = 'Selectează țara.';
        }
        if ($source_id <= 0) {
            $errors[] = 'Selectează sursa lead-ului.';
        }
        if ($status_id <= 0) {
            $status_id = $this->getDefaultLeadStatusId($pdo);
            if ($status_id <= 0) {
                $errors[] = 'Nu există statusuri de lead în DB. Rulează install.php.';
            }
        }

        // Save old input (pentru user)
        ssa_set_old($_POST);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->redirectTo('leads/create');
        }

        try {
            $photo_path = $this->handleUpload($_FILES['photo'] ?? null);

            $now = date('Y-m-d H:i:s');
            $assigned_by = (int)($u['id'] ?? 0);

            $sql = "INSERT INTO ssa_leads (
                        business_name, founder_name,
                        phone, country_id, city, county, address, website,
                        business_type_id, source_id,
                        status_id, assigned_to, assigned_by,
                        company_reg_no,
                        social_json, photo_path, extra_json, description,
                        created_at, updated_at
                    ) VALUES (
                        :business_name, :founder_name,
                        :phone, :country_id, :city, :county, :address, :website,
                        :business_type_id, :source_id,
                        :status_id, :assigned_to, :assigned_by,
                        :company_reg_no,
                        :social_json, :photo_path, :extra_json, :description,
                        :created_at, :updated_at
                    )";

            $st = $pdo->prepare($sql);
            $st->execute([
                ':business_name' => $business_name !== '' ? $business_name : null,
                ':founder_name'  => $founder_name !== '' ? $founder_name : null,

                ':phone' => $phone !== '' ? $phone : null,
                ':country_id' => $country > 0 ? $country : null,
                ':city' => $city !== '' ? $city : null,
                ':county' => $county !== '' ? $county : null,
                ':address' => $address !== '' ? $address : null,
                ':website' => $website !== '' ? $website : null,

                ':business_type_id' => $business_type_id > 0 ? $business_type_id : null,
                ':source_id' => $source_id > 0 ? $source_id : null,

                ':status_id' => $status_id,
                ':assigned_to' => $assigned_to_id,
                ':assigned_by' => $assigned_by > 0 ? $assigned_by : null,

                ':company_reg_no' => $company_reg_no !== '' ? $company_reg_no : null,

                ':social_json' => $social ? json_encode($social, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
                ':photo_path' => $photo_path,
                ':extra_json' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
                ':description' => $description !== '' ? $description : null,

                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $id = (int)$pdo->lastInsertId();

            // clear old
            ssa_set_old([]);
            Session::flash('ok', 'Lead adăugat cu succes ✅');

            // redirect to details (ruta o facem în pașii următori)
            ssa_redirect(ssa_base_url('leads/' . $id));
            return;

        } catch (Throwable $e) {
            Session::flash('error', 'Eroare la salvare: ' . $e->getMessage());
            $this->redirectTo('leads/create');
        }
    }
}
