<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

/**
 * Traduce entre el formato de fecha/hora que usa la aplicacion
 * ("Y-m-d\TH:i", igual que los <input type="datetime-local">) y el formato
 * DATETIME de MySQL ("Y-m-d H:i:s"). Unico lugar que conoce esta diferencia:
 * el dominio y los casos de uso siempre trabajan con el formato de la app.
 */
final class FormatoFechaHora
{
    private function __construct()
    {
    }

    public static function aMysql(string $fechaHoraApp): string
    {
        return str_replace('T', ' ', $fechaHoraApp) . ':00';
    }

    public static function aApp(string $fechaHoraMysql): string
    {
        return substr(str_replace(' ', 'T', $fechaHoraMysql), 0, 16);
    }
}
