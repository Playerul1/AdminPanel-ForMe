<?php
/**
 * SSA Admin Panel - View renderer (layouts + partials)
 */

class View
{
    /**
     * Render view + layout.
     * $view poate fi:
     *  - cale relativă față de app/Views (ex: 'auth/login')
     *  - cale absolută către un fișier .php (ex: __DIR__.'/../Modules/Dashboard/views/index.php')
     */
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewsBase = __DIR__ . '/../Views';

        $viewFile = $view;
        if (!str_contains($view, DIRECTORY_SEPARATOR) && !str_contains($view, '/')) {
            // e doar un nume, îl tratăm ca relativ "x"
            $viewFile = $viewsBase . '/' . $view . '.php';
        } elseif (!file_exists($view)) {
            // dacă e ceva de genul "auth/login"
            $candidate = $viewsBase . '/' . ltrim($view, '/') . '.php';
            $viewFile = $candidate;
        }

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View missing: " . htmlspecialchars($viewFile, ENT_QUOTES, 'UTF-8');
            return;
        }

        $layoutFile = $viewsBase . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            http_response_code(500);
            echo "Layout missing: " . htmlspecialchars($layoutFile, ENT_QUOTES, 'UTF-8');
            return;
        }

        // data -> variabile în view/layout
        extract($data, EXTR_SKIP);

        // 1) capture view output
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // 2) render layout (layout folosește $content)
        require $layoutFile;
    }

    /**
     * Render partial (folosit în layout)
     * Exemplu: View::partial('layouts/partials/sidebar', ['active' => 'dashboard'])
     */
    public static function partial(string $partial, array $data = []): void
    {
        $viewsBase = __DIR__ . '/../Views';

        $partialFile = $partial;
        if (!file_exists($partialFile)) {
            $candidate = $viewsBase . '/' . ltrim($partial, '/') . '.php';
            $partialFile = $candidate;
        }

        if (!file_exists($partialFile)) {
            echo "<!-- Partial missing: " . htmlspecialchars($partialFile, ENT_QUOTES, 'UTF-8') . " -->";
            return;
        }

        extract($data, EXTR_SKIP);
        require $partialFile;
    }
}
