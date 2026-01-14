<?php
/**
 * SSA Admin Panel - Logger
 * Scrie în /ssa-admin/storage/logs/app.log
 */

class Logger
{
    private static function logPath(): string
    {
        $base = dirname(__DIR__, 2); // .../ssa-admin/app
        $dir  = $base . '/../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/app.log';
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $ts = date('Y-m-d H:i:s');
        $ctx = '';
        if (!empty($context)) {
            $ctx = ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents(self::logPath(), "[$ts] [$level] $message$ctx\n", FILE_APPEND);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARN', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }
}
