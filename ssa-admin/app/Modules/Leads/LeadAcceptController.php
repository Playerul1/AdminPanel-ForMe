<?php

class LeadAcceptController
{
    public function accept(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo "Invalid lead id";
            return;
        }

        if (!Auth::check()) {
            ssa_redirect(ssa_base_url('login'));
            return;
        }

        $user = Auth::user();
        $uid  = (int)($user['id'] ?? 0);

        if ($uid <= 0) {
            ssa_redirect(ssa_base_url('login'));
            return;
        }

        try {
            $pdo = DB::pdo('panel_db');

            $st = $pdo->prepare("SELECT id, accepted_by, assigned_to
                                 FROM ssa_leads
                                 WHERE id = ?
                                 LIMIT 1");
            $st->execute([$id]);
            $lead = $st->fetch(PDO::FETCH_ASSOC);

            if (!$lead) {
                http_response_code(404);
                echo "Lead not found";
                return;
            }

            $acceptedBy = (int)($lead['accepted_by'] ?? 0);

            // acceptat de altcineva -> blocăm
            if ($acceptedBy && $acceptedBy !== $uid) {
                View::render('modules/placeholder/page', [
                    'active' => 'leads',
                    'pageTitle' => 'Lead deja acceptat',
                    'userName' => ($user['name'] ?? 'User'),
                    'userRole' => ($user['role'] ?? 'Staff'),
                    'notifCount' => 2,
                    'title' => 'Lead deja acceptat',
                    'subtitle' => 'Acest lead este deja acceptat de alt angajat (ID: #' . (int)$acceptedBy . ').',
                    'next' => ['href' => 'leads/' . $id, 'label' => 'Vezi Detalii', 'icon' => '📄'],
                ], 'app');
                return;
            }

            // acceptare cu protecție (dacă 2 apasă în același timp)
            if (!$acceptedBy) {
                $upd = $pdo->prepare("
                    UPDATE ssa_leads
                    SET accepted_by = :uid,
                        accepted_at = NOW(),
                        assigned_to = CASE
                            WHEN assigned_to IS NULL OR assigned_to = 0 THEN :uid2
                            ELSE assigned_to
                        END
                    WHERE id = :id
                      AND (accepted_by IS NULL OR accepted_by = 0)
                    LIMIT 1
                ");
                $upd->execute([':uid'=>$uid, ':uid2'=>$uid, ':id'=>$id]);

                if ($upd->rowCount() === 0) {
                    // cineva a acceptat între timp
                    $chk = $pdo->prepare("SELECT accepted_by FROM ssa_leads WHERE id=? LIMIT 1");
                    $chk->execute([$id]);
                    $row = $chk->fetch(PDO::FETCH_ASSOC);
                    $ab = (int)($row['accepted_by'] ?? 0);

                    if ($ab && $ab !== $uid) {
                        View::render('modules/placeholder/page', [
                            'active' => 'leads',
                            'pageTitle' => 'Lead deja acceptat',
                            'userName' => ($user['name'] ?? 'User'),
                            'userRole' => ($user['role'] ?? 'Staff'),
                            'notifCount' => 2,
                            'title' => 'Lead deja acceptat',
                            'subtitle' => 'Acest lead a fost acceptat între timp de alt angajat (ID: #' . (int)$ab . ').',
                            'next' => ['href' => 'leads/' . $id, 'label' => 'Vezi Detalii', 'icon' => '📄'],
                        ], 'app');
                        return;
                    }
                }
            }

            ssa_redirect(ssa_base_url('leads/' . $id . '?accepted=1'));
        } catch (Throwable $e) {
            Logger::error('Lead accept failed: ' . $e->getMessage());
            View::render('modules/placeholder/page', [
                'active' => 'leads',
                'pageTitle' => 'Eroare',
                'userName' => ($user['name'] ?? 'User'),
                'userRole' => ($user['role'] ?? 'Staff'),
                'notifCount' => 2,
                'title' => 'Eroare la acceptare',
                'subtitle' => 'Nu s-a putut accepta lead-ul. Verifică log: /ssa-admin/storage/logs/app.log',
                'next' => ['href' => 'leads/' . $id, 'label' => 'Înapoi la detalii', 'icon' => '📄'],
            ], 'app');
        }
    }
}
