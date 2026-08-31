<?php

/**
 * Router auxiliar SOLO para el servidor embebido de desarrollo
 * (`php -S 0.0.0.0:8000 -t public public/router.php`). Apache usa
 * .htaccess; Nginx/otros hostings deben redirigir todo a index.php en su
 * propia configuracion. Este archivo no se usa en produccion.
 */

$rutaSolicitada = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$archivoReal = __DIR__ . $rutaSolicitada;

if ($rutaSolicitada !== '/' && is_file($archivoReal)) {
    return false;
}

require __DIR__ . '/index.php';
