<?php
/**
 * SSA Admin Panel - Session manager (safe defaults)
 */

class Session
{
    private static bool $started = false;

    private static function cfg(): array
    {
        $path = __DIR__ . '/../Config/config.php';
        if (!file_exists($path)) {
            throw new RuntimeException("Config missing: app/Config/config.php");
        }
        $cfg = require $path;
        return $cfg['session'] ?? [];
    }

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg = self::cfg();

        $name = $cfg['name'] ?? 'ssa_admin_session';
        session_name($name);

        // Cookie options
        $secure   = (bool)($cfg['cookie_secure'] ?? true);
        $httponly = (bool)($cfg['cookie_httponly'] ?? true);
        $samesite = (string)($cfg['cookie_samesite'] ?? 'Lax');

        // PHP 7.3+ supports array options
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        // Extra hardening
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_httponly', $httponly ? '1' : '0');

        // Start
        session_start();
        self::$started = true;

        // Regenerate once per new session to reduce fixation
        if (empty($_SESSION['_init'])) {
            $_SESSION['_init'] = time();
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();

        // setter
        if (func_num_args() === 2) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        // getter (one-time)
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        self::$started = false;
    }
}
