<?php

declare(strict_types=1);

/**
 * FRONT CONTROLLER — unico punto de entrada HTTP de todo el backend.
 * -----------------------------------------------------------------------
 * Este es el UNICO archivo PHP que el servidor web debe exponer (el
 * document root del vhost/hosting debe apuntar a esta carpeta "public/",
 * nunca a la raiz del proyecto). Todo el resto del codigo -src/, config/,
 * vendor/, la base de datos- queda fuera del arbol servible por HTTP, asi
 * que no hay forma de pedirle al servidor un .php de src/ directamente ni
 * de descargar accidentalmente config/config.php con las credenciales.
 *
 * Responsabilidades que se centralizan aca, para datos sensibles (fichas
 * medicas, credenciales, sesiones):
 *   1. Nunca mostrar errores/stack traces al cliente (solo al log).
 *   2. Armar la Request UNA sola vez a partir de los superglobals.
 *   3. Delegar en el Kernel, que aplica el pipeline de middlewares de
 *      seguridad (cabeceras, sesion endurecida, CSRF) antes de llegar a
 *      cualquier controlador.
 *   4. Capturar cualquier excepcion que se escape y convertirla, siempre,
 *      en una respuesta JSON generica (nunca en el mensaje crudo de una
 *      excepcion de base de datos).
 */

use CodigoAzul\Infrastructure\Container\Container;
use CodigoAzul\Infrastructure\Http\ErrorHandling\ManejadorErrores;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

// Los errores de PHP se registran en el log del servidor, nunca se
// imprimen: un warning suelto no debe filtrar rutas de archivos ni
// romper el JSON de la respuesta.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$configuracion = require __DIR__ . '/../config/config.php';
date_default_timezone_set($configuracion['zonaHoraria']);

try {
    // El Kernel ya envuelve TODO el pipeline (rutas, middlewares, casos de
    // uso) en su propio manejo de errores; este try/catch solo cubre el
    // arranque en si (config o Container rotos), que ocurre antes de que
    // el Kernel exista.
    $respuesta = (new Container($configuracion))->kernel()->manejar(Request::desdeGlobals());
} catch (\Throwable $error) {
    $respuesta = (new ManejadorErrores($configuracion['entorno'] === 'development'))->manejar($error);
}

$respuesta->enviar();
