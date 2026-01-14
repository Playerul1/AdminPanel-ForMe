<?php
/**
 * SSA Admin Panel - App bootstrap
 */

class App
{
    private Router $router;

    public function __construct()
    {
        $tz = (string)ssa_config('app.timezone', 'Europe/Chisinau');
        if ($tz) {
            @date_default_timezone_set($tz);
        }

        Session::start();
        $this->router = new Router();
    }

    private function requireMiddleware(string $file): callable
    {
        $base = dirname(__DIR__); // .../ssa-admin/app
        $path = $base . '/Middleware/' . $file;

        if (!file_exists($path)) {
            throw new RuntimeException("Middleware missing: " . $path);
        }

        $mw = require $path;
        if (!is_callable($mw)) {
            throw new RuntimeException("Middleware invalid (must return callable): " . $path);
        }

        return $mw;
    }

    private function requireModule(string $relativePath): void
    {
        $base = dirname(__DIR__); // .../ssa-admin/app
        $path = $base . '/' . ltrim($relativePath, '/');

        if (!file_exists($path)) {
            throw new RuntimeException("Module file missing: " . $path);
        }

        require_once $path;
    }

    /**
     * Optional include (nu crapă aplicația dacă lipsește fișierul).
     */
    private function tryRequireModule(string $relativePath): bool
    {
        $base = dirname(__DIR__); // .../ssa-admin/app
        $path = $base . '/' . ltrim($relativePath, '/');

        if (!file_exists($path)) {
            return false;
        }

        require_once $path;
        return true;
    }

    public function defineRoutes(): void
    {
        /**
         * Controllers (core)
         */
        $this->requireModule('Modules/Auth/AuthController.php');
        $this->requireModule('Modules/Dashboard/DashboardController.php');

        $this->requireModule('Modules/Leads/LeadsController.php');
        $this->requireModule('Modules/Leads/LeadsCreateController.php');
        $this->requireModule('Modules/Leads/LeadDetailsController.php');
        $this->requireModule('Modules/Leads/LeadAcceptController.php');

        /**
         * Controllers (optional / extensii)
         * - dacă există, activăm modulul real
         * - dacă nu, rămâne placeholder
         */
        $employeesEnabled = $this->tryRequireModule('Modules/Employees/EmployeesController.php');
        $settingsEnabled  = $this->tryRequireModule('Modules/Settings/SettingsController.php');
        $profileEnabled   = $this->tryRequireModule('Modules/Profile/ProfileController.php');

        $authController      = new AuthController();
        $dashboardController = new DashboardController();

        $leadsController       = new LeadsController();
        $leadsCreateController = new LeadsCreateController();
        $leadDetailsController = new LeadDetailsController();
        $leadAcceptController  = new LeadAcceptController();

        $employeesController = null;
        if ($employeesEnabled && class_exists('EmployeesController')) {
            $employeesController = new EmployeesController();
        } else {
            $employeesEnabled = false;
        }

        $settingsController = null;
        if ($settingsEnabled && class_exists('SettingsController')) {
            $settingsController = new SettingsController();
        } else {
            $settingsEnabled = false;
        }

        $profileController = null;
        if ($profileEnabled && class_exists('ProfileController')) {
            $profileController = new ProfileController();
        } else {
            $profileEnabled = false;
        }

        /**
         * Middleware
         */
        $requireAuth = $this->requireMiddleware('RequireAuth.php');

        /**
         * Root
         */
        $this->router->get('/', function () {
            if (Auth::check()) {
                ssa_redirect(ssa_base_url('dashboard'));
            }
            ssa_redirect(ssa_base_url('login'));
        });

        /**
         * Auth
         */
        $this->router->get('/login', function () use ($authController) {
            $authController->showLogin();
        });

        $this->router->post('/login', function () use ($authController) {
            $authController->doLogin();
        });

        $this->router->get('/logout', function () use ($authController) {
            $authController->logout();
        }, [$requireAuth]);

        /**
         * Dashboard
         */
        $this->router->get('/dashboard', function () use ($dashboardController) {
            $dashboardController->index();
        }, [$requireAuth]);

        /**
         * LEADS
         */
        $this->router->get('/leads', function () use ($leadsController) {
            $leadsController->index();
        }, [$requireAuth]);

        // Create lead (IMPORTANT: before /leads/{id})
        $this->router->get('/leads/create', function () use ($leadsCreateController) {
            $leadsCreateController->show();
        }, [$requireAuth]);

        $this->router->post('/leads/create', function () use ($leadsCreateController) {
            $leadsCreateController->store();
        }, [$requireAuth]);

        /**
         * ✅ ACCEPT REAL - RUTA SIGURĂ (ID LA FINAL)
         * IMPORTANT: trebuie să fie înainte de /leads/{id}
         */
        $this->router->get('/leads/accept/{id}', function ($p) use ($leadAcceptController) {
            $leadAcceptController->accept($p);
        }, [$requireAuth]);

        // Details
        $this->router->get('/leads/{id}', function ($p) use ($leadDetailsController) {
            $leadDetailsController->show($p);
        }, [$requireAuth]);

        // ✅ Accept via POST from details page (uses _action=accept)
        $this->router->post('/leads/{id}', function ($p) use ($leadAcceptController) {
            $action = (string)($_POST['_action'] ?? '');
            if ($action === 'accept') {
                $leadAcceptController->accept($p);
                return;
            }
            $id = (int)($p['id'] ?? 0);
            ssa_redirect(ssa_base_url('leads/' . $id));
        }, [$requireAuth]);

        // Download
        $this->router->get('/leads/{id}/download', function ($p) use ($leadDetailsController) {
            $leadDetailsController->download($p);
        }, [$requireAuth]);

        /**
         * (Opțional) păstrăm și ruta veche pentru compatibilitate.
         */
        $this->router->get('/leads/{id}/accept', function ($p) use ($leadAcceptController) {
            $leadAcceptController->accept($p);
        }, [$requireAuth]);

        // Edit placeholder (îl facem după)
        $this->router->get('/leads/{id}/edit', function ($p) {
            View::render('modules/placeholder/page', [
                'active' => 'leads',
                'pageTitle' => 'Editează Lead',
                'userName' => (Auth::user()['name'] ?? 'User'),
                'userRole' => (Auth::user()['role'] ?? 'Staff'),
                'notifCount' => 2,
                'title' => 'Editează Lead #' . ssa_e($p['id'] ?? ''),
                'subtitle' => 'Următorul pas: form edit complet + upload imagine + social links + extra fields.',
                'next' => ['href' => 'leads', 'label' => 'Înapoi la Lead-uri', 'icon' => '🎯'],
            ], 'app');
        }, [$requireAuth]);

        /**
         * WEBBUILDER (placeholder - îl facem după)
         */
        $this->router->get('/webbuilder', function () {
            $u = Auth::user();
            View::render('modules/placeholder/page', [
                'active' => 'webbuilder',
                'pageTitle' => 'Constructor Website',
                'userName' => $u['name'] ?? 'User',
                'userRole' => $u['role'] ?? 'Staff',
                'notifCount' => 2,
                'title' => 'Constructor Website',
                'subtitle' => 'Următorul pas: integrare cu plugin-ul (site principal) + comenzi + facturi WHMCS.',
            ], 'app');
        }, [$requireAuth]);

        /**
         * EMPLOYEES (real dacă există controller-ul, altfel placeholder)
         */
        if ($employeesEnabled && $employeesController) {
            $this->router->get('/employees', function () use ($employeesController) {
                $employeesController->index();
            }, [$requireAuth]);

            $this->router->post('/employees', function () use ($employeesController) {
                $employeesController->handle();
            }, [$requireAuth]);
        } else {
            $this->router->get('/employees', function () {
                $u = Auth::user();
                View::render('modules/placeholder/page', [
                    'active' => 'employees',
                    'pageTitle' => 'Angajați',
                    'userName' => $u['name'] ?? 'User',
                    'userRole' => $u['role'] ?? 'Staff',
                    'notifCount' => 2,
                    'title' => 'Angajați',
                    'subtitle' => 'Modul neinstalat încă. (Următorul pas: listă staff WHMCS + roluri + permisiuni.)',
                ], 'app');
            }, [$requireAuth]);
        }

        /**
         * SETTINGS (real dacă există controller-ul, altfel placeholder)
         */
        if ($settingsEnabled && $settingsController) {
            $this->router->get('/settings', function () use ($settingsController) {
                $settingsController->index();
            }, [$requireAuth]);

            $this->router->post('/settings', function () use ($settingsController) {
                $settingsController->handle();
            }, [$requireAuth]);
        } else {
            $this->router->get('/settings', function () {
                $u = Auth::user();
                View::render('modules/placeholder/page', [
                    'active' => 'settings',
                    'pageTitle' => 'Setări',
                    'userName' => $u['name'] ?? 'User',
                    'userRole' => $u['role'] ?? 'Staff',
                    'notifCount' => 2,
                    'title' => 'Setări',
                    'subtitle' => 'Modul neinstalat încă. (Următorul pas: țări/surse/statusuri/culori/tipuri.)',
                ], 'app');
            }, [$requireAuth]);
        }

        /**
         * PROFILE (real dacă există controller-ul, altfel placeholder)
         */
        if ($profileEnabled && $profileController) {
            $this->router->get('/profile', function () use ($profileController) {
                $profileController->index();
            }, [$requireAuth]);

            $this->router->post('/profile', function () use ($profileController) {
                $profileController->handle();
            }, [$requireAuth]);

            $this->router->get('/profile/notifications', function () use ($profileController) {
                $profileController->notifications();
            }, [$requireAuth]);
        } else {
            $this->router->get('/profile', function () {
                $u = Auth::user();
                View::render('modules/placeholder/page', [
                    'active' => 'profile',
                    'pageTitle' => 'Profil',
                    'userName' => $u['name'] ?? 'User',
                    'userRole' => $u['role'] ?? 'Staff',
                    'notifCount' => 2,
                    'title' => 'Profil',
                    'subtitle' => 'Modul neinstalat încă. (Următorul pas: profil + istoric lead-uri + apeluri.)',
                ], 'app');
            }, [$requireAuth]);

            $this->router->get('/profile/notifications', function () {
                $u = Auth::user();
                View::render('modules/placeholder/page', [
                    'active' => 'profile',
                    'pageTitle' => 'Notificări',
                    'userName' => $u['name'] ?? 'User',
                    'userRole' => $u['role'] ?? 'Staff',
                    'notifCount' => 2,
                    'title' => 'Notificări',
                    'subtitle' => 'Modul neinstalat încă. (Următorul pas: listă notificări + mark as read.)',
                ], 'app');
            }, [$requireAuth]);
        }

        /**
         * Setup (temporary)
         */
        $this->router->get('/setup', function () {
            $appName = (string)ssa_config('app.name', 'SSA Admin');
            $panelOk = DB::test('panel_db');
            $whmcsOk = DB::test('whmcs_db');
            ?>
            <!doctype html>
            <html lang="ro">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?= ssa_e($appName) ?> • Setup</title>
                <style>
                    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#0b0e14;color:#e9eefc}
                    .wrap{max-width:900px;margin:0 auto;padding:24px}
                    .card{background:#121829;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px;margin:16px 0}
                    .row{display:flex;gap:12px;flex-wrap:wrap}
                    .pill{padding:8px 12px;border-radius:999px;font-size:14px;border:1px solid rgba(255,255,255,.12)}
                    .ok{background:rgba(46,204,113,.12);border-color:rgba(46,204,113,.35)}
                    .bad{background:rgba(231,76,60,.12);border-color:rgba(231,76,60,.35)}
                    .muted{color:rgba(233,238,252,.75)}
                    code{background:rgba(255,255,255,.06);padding:2px 6px;border-radius:8px}
                </style>
            </head>
            <body>
            <div class="wrap">
                <h1><?= ssa_e($appName) ?></h1>
                <p class="muted">Setup check (temporary). DB status:</p>
                <div class="card">
                    <div class="row">
                        <div class="pill <?= $panelOk ? 'ok' : 'bad' ?>">panel_db: <?= $panelOk ? 'OK ✅' : 'FAIL ❌' ?></div>
                        <div class="pill <?= $whmcsOk ? 'ok' : 'bad' ?>">whmcs_db: <?= $whmcsOk ? 'OK ✅' : 'FAIL ❌' ?></div>
                    </div>
                    <p class="muted" style="margin-top:12px">Log: <code>/ssa-admin/storage/logs/app.log</code></p>
                </div>
            </div>
            </body>
            </html>
            <?php
        });

        /**
         * Health
         */
        $this->router->get('/health', function () {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'time' => date('c'),
                'auth' => Auth::check(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
    }

    public function run(): void
    {
        $this->defineRoutes();
        $this->router->dispatch();
    }
}
