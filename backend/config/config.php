<?php

declare(strict_types=1);

/**
 * Unico lugar que lee variables de entorno. Nunca hardcodear credenciales:
 * en produccion se definen como variables de entorno reales del servidor;
 * en desarrollo se puede usar un .env cargado por el propio servidor/hosting
 * antes de invocar PHP (este proyecto no agrega una libreria de .env para
 * no sumar dependencias innecesarias).
 *
 * @return array{
 *   db: array{host: string, port: string, name: string, user: string, password: string},
 *   sesion: array{nombre: string, duracionSegundos: int},
 *   zonaHoraria: string,
 *   entorno: string,
 * }
 */
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'codigo_azul',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'sesion' => [
        'nombre' => 'ca_sesion',
        'duracionSegundos' => 60 * 60 * 8,
    ],
    // Todas las fechas del sistema son "hora de pared" local, nunca UTC
    // (evita el bug de husos horarios ya corregido en el front-end).
    'zonaHoraria' => getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires',
    'entorno' => getenv('APP_ENV') ?: 'production',
];
