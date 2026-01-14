<?php
/**
 * SSA Admin Panel - Base Controller
 */

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirectTo(string $path = '', int $code = 302): never
    {
        // dacă primește deja url complet, îl folosește direct
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            ssa_redirect($path, $code);
        }

        // route/path (ex: 'dashboard', 'login')
        $path = ltrim($path, '/');
        $url = $path ? ssa_base_url($path) : ssa_base_url();

        ssa_redirect($url, $code);
    }

    protected function input(string $key, mixed $default = ''): mixed
    {
        // POST are prioritate (form)
        if (isset($_POST[$key])) {
            return $this->clean($_POST[$key]);
        }
        if (isset($_GET[$key])) {
            return $this->clean($_GET[$key]);
        }
        return $default;
    }

    protected function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->clean($v);
            }
            return $out;
        }

        if (is_string($value)) {
            // trim basic; escaping se face la output (ssa_e)
            return trim($value);
        }

        return $value;
    }
}
