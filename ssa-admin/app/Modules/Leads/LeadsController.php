<?php
/**
 * LeadsController
 * - GET /leads  => listă + filtre + "Lead-urile mele"
 *
 * DB: panel_db (tabele create de install.php)
 */

class LeadsController extends Controller
{
    private function panelPdo(): PDO
    {
        // 1) dacă DB class are pdo('panel_db')
        if (class_exists('DB') && method_exists('DB', 'pdo')) {
            /** @var PDO $pdo */
            $pdo = DB::pdo('panel_db');
            return $pdo;
        }

        // 2) fallback direct din config
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

    private function fetchPairs(PDO $pdo, string $sql, array $params = []): array
    {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function index(): void
    {
        $pdo = $this->panelPdo();

        $u = Auth::user();
        $staffId = (int)($u['id'] ?? 0);

        // Filters
        $q         = trim((string)$this->input('q', ''));
        $countryId = (int)$this->input('country_id', 0);
        $statusId  = (int)$this->input('status_id', 0);
        $sourceId  = (int)$this->input('source_id', 0);
        $assigned  = (string)$this->input('assigned', ''); // 'mine' | 'unassigned' | '' | numeric

        $page = max(1, (int)$this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Build WHERE
        $where = ["l.deleted_at IS NULL"];
        $params = [];

        if ($q !== '') {
            $where[] = "(l.business_name LIKE :q OR l.founder_name LIKE :q OR l.phone LIKE :q OR l.website LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        if ($countryId > 0) {
            $where[] = "l.country_id = :country_id";
            $params[':country_id'] = $countryId;
        }
        if ($statusId > 0) {
            $where[] = "l.status_id = :status_id";
            $params[':status_id'] = $statusId;
        }
        if ($sourceId > 0) {
            $where[] = "l.source_id = :source_id";
            $params[':source_id'] = $sourceId;
        }

        // Assigned filters
        if ($assigned === 'mine') {
            $where[] = "l.assigned_to = :me";
            $params[':me'] = $staffId;
        } elseif ($assigned === 'unassigned') {
            $where[] = "l.assigned_to IS NULL";
        } elseif ($assigned !== '' && ctype_digit($assigned)) {
            $where[] = "l.assigned_to = :assigned_to";
            $params[':assigned_to'] = (int)$assigned;
        }

        $whereSql = implode(' AND ', $where);

        // Count
        $countSql = "SELECT COUNT(*) AS cnt
                     FROM ssa_leads l
                     WHERE {$whereSql}";
        $st = $pdo->prepare($countSql);
        $st->execute($params);
        $total = (int)($st->fetchColumn() ?: 0);
        $pages = max(1, (int)ceil($total / $perPage));

        // Data query
        $sql = "SELECT
                    l.*,
                    c.name  AS country_name,
                    src.name AS source_name,
                    bt.name AS business_type_name,
                    stt.name AS status_name,
                    stt.color AS status_color
                FROM ssa_leads l
                LEFT JOIN ssa_countries c ON c.id = l.country_id
                LEFT JOIN ssa_lead_sources src ON src.id = l.source_id
                LEFT JOIN ssa_business_types bt ON bt.id = l.business_type_id
                LEFT JOIN ssa_lead_statuses stt ON stt.id = l.status_id
                WHERE {$whereSql}
                ORDER BY l.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $leads = $st->fetchAll();

        // Dropdown data
        $countries = $this->fetchPairs($pdo, "SELECT id, name FROM ssa_countries ORDER BY name ASC");
        $statuses  = $this->fetchPairs($pdo, "SELECT id, name, color FROM ssa_lead_statuses ORDER BY sort ASC, name ASC");
        $sources   = $this->fetchPairs($pdo, "SELECT id, name FROM ssa_lead_sources ORDER BY name ASC");
        $types     = $this->fetchPairs($pdo, "SELECT id, name FROM ssa_business_types ORDER BY name ASC");

        // Assigned options (temporar, până legăm WHMCS staff list)
        $assignees = [
            ['value' => '', 'label' => 'Toți'],
            ['value' => 'mine', 'label' => 'Lead-urile mele'],
            ['value' => 'unassigned', 'label' => 'Neasignate'],
            // mai târziu: încărcăm staff real din WHMCS și punem aici id+name
        ];

        $this->view('modules/leads/index', [
            'active' => 'leads',
            'pageTitle' => 'Lead-uri',

            'userName' => $u['name'] ?? 'User',
            'userRole' => $u['role'] ?? 'Staff',
            'notifCount' => 2,

            'leads' => $leads,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,

            'filters' => [
                'q' => $q,
                'country_id' => $countryId,
                'status_id' => $statusId,
                'source_id' => $sourceId,
                'assigned' => $assigned,
            ],

            'countries' => $countries,
            'statuses' => $statuses,
            'sources' => $sources,
            'types' => $types,
            'assignees' => $assignees,

            // opțional (le facem imediat dacă vrei):
            // 'pageCss' => 'leads.css',
            // 'pageJs' => 'leads.js',
        ], 'app');
    }
}
