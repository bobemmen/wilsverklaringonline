<?php

declare(strict_types=1);

// Eigen autoloader (geen Composer nodig): WV\Foo => src/Foo.php, WV\Controllers\Foo => src/Controllers/Foo.php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Http.php'; // bevat HttpException, Request en Response

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'WV\\')) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 3)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

mb_internal_encoding('UTF-8');
