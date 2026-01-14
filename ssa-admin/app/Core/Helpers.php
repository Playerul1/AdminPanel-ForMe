<?php
/**
 * SSA Admin Panel - Helpers (config, urls, assets, responses, safety)
 */

function ssa_config(?string $path = null, mixed $default = null): mixed
{
    static $cfg = null;

    if ($cfg === null) {
        $file = __DIR__ . '/../Config/config.php';
        if (!file_exists($file)) {
            throw new RuntimeException("Config missing: app/Config/config.php");
        }
        $cfg = require $file;
    }

    if ($path === null) {
        return $cfg;
    }

    // dot notation: "app.base_url"
    $parts = explode('.', $path);
    $val = $cfg;

    foreach ($parts as $p) {
        if (is_array($val) && array_key_exists($p, $val)) {
            $val = $val[$p];
        } else {
            return $default;
        }
    }

    return $val;
}

function ssa_base_url(string $path = ''): string
{
    $base = rtrim((string)ssa_config('app.base_url', ''), '/');
    $path = ltrim($path, '/');
    return $path ? "{$base}/{$path}" : $base;
}

function ssa_asset(string $path): string
{
    // assets sunt în /ssa-admin/assets/...
    $path = ltrim($path, '/');
    return ssa_base_url("ssa-admin/assets/{$path}");
}

function ssa_redirect(string $to, int $code = 302): never
{
    header("Location: {$to}", true, $code);
    exit;
}

function ssa_back(string $fallback = ''): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref) {
        ssa_redirect($ref);
    }
    ssa_redirect($fallback ?: ssa_base_url());
}

function ssa_abort(int $code = 404, string $message = 'Not Found'): never
{
    http_response_code($code);
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

function ssa_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ssa_is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function ssa_old(string $key, mixed $default = ''): mixed
{
    // păstrăm "old input" în sesiune, ca la formulare (ex: Add Lead)
    $old = Session::get('_old', []);
    return $old[$key] ?? $default;
}

function ssa_set_old(array $data): void
{
    Session::set('_old', $data);
}

function ssa_clear_old(): void
{
    Session::forget('_old');
}

function ssa_dd(...$vars): never
{
    header('Content-Type: text/plain; charset=utf-8');
    foreach ($vars as $v) {
        var_dump($v);
        echo "\n-------------------\n";
    }
    exit;
}
