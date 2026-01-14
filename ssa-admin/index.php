<?php
declare(strict_types=1);

/**
 * SSA Admin Panel - Front Controller
 */

// --- Storage bootstrap (before anything else) ---
$storageBase = __DIR__ . '/storage';
$storageDirs = [
    $storageBase,
    $storageBase . '/logs',
    $storageBase . '/cache',
    $storageBase . '/uploads',
];
foreach ($storageDirs as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
}
$logFile = $storageBase . '/logs/app.log';

// safe error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// Convert all errors to log entries (helps on shared hosting where server error log isn't accessible)
set_error_handler(function ($severity, $message, $file, $line) use ($logFile) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$ts] PHP ERROR ($severity): $message in $file:$line\n", FILE_APPEND);
    return false; // let PHP handle too
});

set_exception_handler(function (Throwable $e) use ($logFile) {
    $ts = date('Y-m-d H:i:s');
    $msg = "[$ts] UNCAUGHT: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    @file_put_contents($logFile, $msg, FILE_APPEND);
    http_response_code(500);
    echo "Internal Server Error";
});

// Core includes
require __DIR__ . '/app/Core/Session.php';
require __DIR__ . '/app/Core/Helpers.php';
require __DIR__ . '/app/Core/Logger.php';
require __DIR__ . '/app/Core/DB.php';
require __DIR__ . '/app/Core/Router.php';
require __DIR__ . '/app/Core/View.php';
require __DIR__ . '/app/Core/Controller.php';
require __DIR__ . '/app/Core/Auth.php';
require __DIR__ . '/app/Core/App.php';

// Run
$app = new App();
$app->run();
