<?php
/**
 * Middleware: RequireAuth
 * - dacă nu e logat => redirect /login
 */

return function (string $method, string $path, array $params) {
    if (!Auth::check()) {
        // păstrăm ruta cerută, ca să revenim după login
        Session::set('_intended', $path);
        ssa_redirect(ssa_base_url('login'));
        return false;
    }
    return true;
};
