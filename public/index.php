<?php

declare(strict_types=1);

// Ingebouwde PHP-server (php -S): bestaande statische bestanden direct serveren.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

$root = dirname(__DIR__);
require $root . '/src/bootstrap.php';

header_remove('X-Powered-By');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    WV\App::boot($root)->run();
} catch (Throwable $e) {
    error_log('[WilsVerklaring] opstartfout: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "De applicatie kan niet starten. Controleer de configuratie (zie README) en het serverlogboek.\n";
}
