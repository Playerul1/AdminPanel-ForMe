<?php
/**
 * SSA Admin Panel - DB helper (2 conexiuni: panel_db + whmcs_db)
 * - folosește PDO
 * - loghează erori în /storage/logs/app.log
 */

class DB
{
    private static array $config = [];
    private static array $pdoPool = [];

    /**
     * Încarcă config o singură dată.
     */
    private static function cfg(): array
    {
        if (!self::$config) {
            $path = __DIR__ . '/../Config/config.php';
            if (!file_exists($path)) {
                throw new RuntimeException("Config file missing: app/Config/config.php");
            }
            self::$config = require $path;
        }
        return self::$config;
    }

    /**
     * Log simplu în storage/logs/app.log
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $basePath = dirname(__DIR__, 2); // .../ssa-admin/app
        $logDir   = $basePath . '/../storage/logs';
        $logFile  = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $ts = date('Y-m-d H:i:s');
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        @file_put_contents($logFile, "[$ts] [$level] $message$ctx\n", FILE_APPEND);
    }

    /**
     * Returnează o conexiune PDO pentru 'panel_db' sau 'whmcs_db'
     */
    public static function pdo(string $connection = 'panel_db'): PDO
    {
        if (isset(self::$pdoPool[$connection])) {
            return self::$pdoPool[$connection];
        }

        $cfg = self::cfg();
        if (!isset($cfg['db'][$connection])) {
            throw new InvalidArgumentException("DB connection '$connection' not configured in app/Config/config.php");
        }

        $c = $cfg['db'][$connection];

        $host = $c['host'] ?? 'localhost';
        $port = (int)($c['port'] ?? 3306);
        $db   = $c['database'] ?? '';
        $user = $c['username'] ?? '';
        $pass = $c['password'] ?? '';
        $charset = $c['charset'] ?? 'utf8mb4';

        if ($db === '' || $user === '') {
            throw new InvalidArgumentException("DB config incomplete for '$connection' (database/username missing).");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            self::$pdoPool[$connection] = $pdo;
            return $pdo;
        } catch (Throwable $e) {
            self::log('ERROR', "DB connection failed for '$connection'", [
                'message' => $e->getMessage(),
                'dsn' => $dsn,
                'user' => $user,
            ]);
            throw $e;
        }
    }

    /**
     * Test rapid: returnează true/false pentru conexiuni (și loghează dacă pică).
     */
    public static function test(string $connection = 'panel_db'): bool
    {
        try {
            $pdo = self::pdo($connection);
            $pdo->query("SELECT 1")->fetchColumn();
            return true;
        } catch (Throwable $e) {
            self::log('ERROR', "DB test failed for '$connection'", ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Helper: fetch one row
     */
    public static function fetch(string $sql, array $params = [], string $connection = 'panel_db'): ?array
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Helper: fetch all rows
     */
    public static function fetchAll(string $sql, array $params = [], string $connection = 'panel_db'): array
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Helper: execute (INSERT/UPDATE/DELETE)
     */
    public static function exec(string $sql, array $params = [], string $connection = 'panel_db'): int
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
