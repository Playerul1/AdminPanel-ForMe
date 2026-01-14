<?php
/**
 * LeadDetailsController
 * - Show lead details
 * - Download lead as JSON
 */

class LeadDetailsController extends Controller
{
    private function maskPhone(string $phone): string
    {
        $p = trim($phone);
        if ($p === '') return '—';

        // keep +CC and last 2 digits visible, mask the rest
        $digits = preg_replace('/\D+/', '', $p);
        if ($digits === '') return '—';

        // If original has a + prefix, keep it.
        $prefixPlus = (strpos($p, '+') === 0) ? '+' : '';

        // show first 3-4 digits (country-ish) and last 2
        $len = strlen($digits);
        $head = substr($digits, 0, min(3, $len));
        $tail = ($len >= 2) ? substr($digits, -2) : '';
        $maskCount = max(0, $len - strlen($head) - strlen($tail));
        $mask = str_repeat('•', $maskCount);

        return $prefixPlus . $head . $mask . $tail;
    }

    private function decodeJson(?string $json): array
    {
        $json = (string)$json;
        if (trim($json) === '') return [];
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    private function loadLead(int $id): ?array
    {
        $pdo = DB::pdo('panel_db');
        $sql = "
            SELECT
                l.*,
                c.name AS country_name,
                s.name AS status_name,
                s.color AS status_color,
                src.name AS source_name,
                bt.name AS business_type_name
            FROM ssa_leads l
            LEFT JOIN ssa_countries c ON c.id = l.country_id
            LEFT JOIN ssa_lead_statuses s ON s.id = l.status_id
            LEFT JOIN ssa_lead_sources src ON src.id = l.source_id
            LEFT JOIN ssa_business_types bt ON bt.id = l.business_type_id
            WHERE l.id = :id
            LIMIT 1
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            ssa_redirect(ssa_base_url('leads'));
        }

        $lead = $this->loadLead($id);
        if (!$lead) {
            View::render('modules/placeholder/page', [
                'active' => 'leads',
                'pageTitle' => 'Lead-uri',
                'userName' => (Auth::user()['name'] ?? 'User'),
                'userRole' => (Auth::user()['role'] ?? 'Staff'),
                'notifCount' => 2,
                'title' => 'Lead inexistent',
                'subtitle' => 'Lead-ul nu a fost găsit.',
                'next' => ['href' => 'leads', 'label' => 'Înapoi la Lead-uri', 'icon' => '⬅️'],
            ], 'app');
            return;
        }

        // decode json blobs
        $lead['social'] = $this->decodeJson($lead['social_json'] ?? null);
        $lead['extra']  = $this->decodeJson($lead['extra_json'] ?? null);

        $u = Auth::user();
        $uid = (int)($u['id'] ?? 0);
        $role = (string)($u['role'] ?? '');
        $isPrivileged = in_array(strtolower($role), ['master', 'owner', 'admin'], true);

        $acceptedBy = (int)($lead['accepted_by'] ?? 0);
        $canSeePhone = $isPrivileged || ($acceptedBy > 0 && $acceptedBy === $uid);

        $phone = (string)($lead['phone'] ?? '');
        $phoneDisplay = $canSeePhone ? (trim($phone) !== '' ? $phone : '—') : $this->maskPhone($phone);

        View::render('modules/leads/details', [
            'active' => 'leads',
            'pageTitle' => 'Detalii Lead',
            'userName' => ($u['name'] ?? 'User'),
            'userRole' => ($u['role'] ?? 'Staff'),
            'notifCount' => 2,
            'lead' => $lead,
            'canSeePhone' => $canSeePhone,
            'phoneDisplay' => $phoneDisplay,
        ], 'app');
    }

    public function download(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            ssa_redirect(ssa_base_url('leads'));
        }

        $lead = $this->loadLead($id);
        if (!$lead) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Lead not found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $lead['social'] = $this->decodeJson($lead['social_json'] ?? null);
        $lead['extra']  = $this->decodeJson($lead['extra_json'] ?? null);

        // Do not expose full phone unless privileged or owner of accepted lead
        $u = Auth::user();
        $uid = (int)($u['id'] ?? 0);
        $role = (string)($u['role'] ?? '');
        $isPrivileged = in_array(strtolower($role), ['master', 'owner', 'admin'], true);
        $acceptedBy = (int)($lead['accepted_by'] ?? 0);
        $canSeePhone = $isPrivileged || ($acceptedBy > 0 && $acceptedBy === $uid);
        if (!$canSeePhone) {
            $lead['phone'] = $this->maskPhone((string)($lead['phone'] ?? ''));
        }

        // remove internal json columns for clean export
        unset($lead['social_json'], $lead['extra_json']);

        $filename = 'lead_' . $id . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode(['ok' => true, 'lead' => $lead], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
