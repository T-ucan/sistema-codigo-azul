<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

/**
 * Unico lugar que sabe construir la conexion PDO real. Solo se invoca una
 * vez, desde la raiz de composicion (Container). Los repositorios reciben
 * el \PDO ya creado por inyeccion de dependencias: nunca llaman a esta
 * fabrica ni conocen los datos de conexion.
 */
final class PdoConnectionFactory
{
    /** @param array{host: string, port: string, name: string, user: string, password: string} $config */
    public static function crear(array $config): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name'],
        );

        return new \PDO($dsn, $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
