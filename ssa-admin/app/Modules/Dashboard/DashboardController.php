<?php
/**
 * DashboardController
 * - GET /dashboard
 * Deocamdată: date mock (ca să arate bine UI-ul).
 * După ce facem DB tables + services: înlocuim mock cu date reale.
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        $u = Auth::user();
        $userName = $u['name'] ?? 'Guest';
        $userRole = $u['role'] ?? '—';

        // MOCK STATS (temporar)
        $stats = [
            'leads_active' => 18,
            'leads_new' => 6,
            'orders_active' => 4,

            'leads_today' => 5,
            'leads_week' => 21,
            'leads_month' => 74,
            'leads_year' => 512,

            'revenue_today_eur' => 250,
            'revenue_week_eur' => 1240,
            'revenue_month_eur' => 3890,

            'contracts_new' => 3,
            'contracts_accepted' => 2,
            'contracts_rejected' => 1,
            'contracts_in_process' => 7,
        ];

        // MOCK leaderboard
        $leaderboardWeekly = [
            ['name' => 'Alex', 'leads' => 12],
            ['name' => 'Maria', 'leads' => 9],
            ['name' => 'Vlad', 'leads' => 7],
            ['name' => 'Ion', 'leads' => 6],
        ];

        $leaderboardAllTime = [
            ['name' => 'Alex', 'leads' => 312],
            ['name' => 'Maria', 'leads' => 289],
            ['name' => 'Vlad', 'leads' => 201],
            ['name' => 'Ion', 'leads' => 177],
        ];

        // MOCK tasks (pentru user logat)
        $tasks = [
            ['title' => 'Sună lead-urile noi (astăzi)', 'done' => false],
            ['title' => 'Trimite follow-up la 3 clienți', 'done' => true],
            ['title' => 'Verifică comenzi web active', 'done' => false],
        ];

        $this->view('modules/dashboard/index', [
            'active' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'userName' => $userName,
            'userRole' => $userRole,
            'notifCount' => 2, // placeholder

            'stats' => $stats,
            'leaderboardWeekly' => $leaderboardWeekly,
            'leaderboardAllTime' => $leaderboardAllTime,
            'tasks' => $tasks,

            // dacă vrei, le creăm imediat după:
            // 'pageCss' => 'dashboard.css',
            // 'pageJs'  => 'dashboard.js',
        ], 'app');
    }
}
