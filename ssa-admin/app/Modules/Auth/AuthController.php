<?php
/**
 * AuthController
 * - GET /login => form
 * - POST /login => verifică master key
 * - GET /logout => logout
 */

class AuthController extends Controller
{
    public function showLogin(): void
    {
        // dacă e deja logat, du-l la dashboard
        if (Auth::check()) {
            $this->redirectTo('dashboard');
        }

        $this->view('modules/auth/login', [
            'pageTitle' => 'Login',
            'pageCss' => 'auth.css', // optional (îl facem mai târziu)
        ], 'auth');
    }

    public function doLogin(): void
    {
        if (!ssa_is_post()) {
            $this->redirectTo('login');
        }

        $mode = strtolower(trim((string)$this->input('mode', 'whmcs')));

        $identifier = trim((string)$this->input('identifier', ''));
        $password   = (string)$this->input('password', '');
        $masterKey  = trim((string)$this->input('master_key', ''));

        $ok = false;
        if ($mode === 'master') {
            if ($masterKey === '') {
                Session::flash('error', 'Introdu Master Key.');
                ssa_set_old(['identifier' => $identifier, 'mode' => 'master']);
                $this->redirectTo('login?mode=master');
            }

            $ok = Auth::loginWithMasterKey($masterKey);
            if (!$ok) {
                Session::flash('error', 'Master Key invalid.');
                ssa_set_old(['identifier' => $identifier, 'mode' => 'master']);
                $this->redirectTo('login?mode=master');
            }
        } else {
            // WHMCS staff username/email + password
            if ($identifier === '' || $password === '') {
                Session::flash('error', 'Introdu username/email și parola.');
                ssa_set_old(['identifier' => $identifier, 'mode' => 'whmcs']);
                $this->redirectTo('login');
            }

            $ok = Auth::loginWithWhmcsStaff($identifier, $password);
            if (!$ok) {
                Session::flash('error', 'Date de autentificare invalide sau cont dezactivat.');
                ssa_set_old(['identifier' => $identifier, 'mode' => 'whmcs']);
                $this->redirectTo('login');
            }
        }

        // clear old inputs
        ssa_clear_old();

        // redirect to intended
        $intended = (string)Session::get('_intended', '');
        Session::forget('_intended');

        if ($intended && $intended !== '/login') {
            // intended e cu slash la început
            $intended = ltrim($intended, '/');
            $this->redirectTo($intended);
        }

        $this->redirectTo('dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('ok', 'Te-ai deconectat.');
        $this->redirectTo('login');
    }
}
