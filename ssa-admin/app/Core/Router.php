<?php
/**
 * SSA Admin Panel - Router (GET/POST) + middleware + params
 */

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $pattern = '/' . trim($pattern, '/'); // normalize
        if ($pattern === '/') {
            $pattern = '/';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->toRegex($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Convertă "/leads/{id}" în regex + nume param.
     */
    private function toRegex(string $pattern): array
    {
        $paramNames = [];

        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^\/]+)';
        }, $pattern);

        // exact match
        $regex = '#^' . $regex . '$#';

        return ['regex' => $regex, 'params' => $paramNames];
    }

    /**
     * Determină ruta curentă:
     * - dacă există ?route=... (din .htaccess), o folosește
     * - altfel, încearcă din REQUEST_URI
     */
    private function currentPath(): string
    {
        $route = $_GET['route'] ?? null;
        if ($route !== null) {
            $route = '/' . trim((string)$route, '/');
            return $route === '/' ? '/' : $route;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : $uri;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $this->currentPath();

        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) {
                continue;
            }

            $rx = $r['regex']['regex'];
            $paramNames = $r['regex']['params'];

            if (!preg_match($rx, $path, $matches)) {
                continue;
            }

            array_shift($matches); // full match off
            $params = [];
            foreach ($matches as $i => $val) {
                $name = $paramNames[$i] ?? $i;
                $params[$name] = $val;
            }

            // middleware chain
            foreach ($r['middleware'] as $mw) {
                // middleware primește ($method, $path, $params)
                $result = $mw($method, $path, $params);

                // dacă middleware returnează false => stop
                if ($result === false) {
                    return;
                }
            }

            // handler
            call_user_func($r['handler'], $params);
            return;
        }

        // 404
        http_response_code(404);
        echo "404 - Not Found";
    }
}
