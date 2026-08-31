<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Service;

/**
 * Calculo puro de dominio: minutos entre dos fechas/horas en el mismo
 * formato usado por todo el sistema ("Y-m-d\TH:i"). Sin dependencias de
 * infraestructura (sin reloj externo, sin zona horaria implicita): recibe
 * ambos extremos como texto y no asume "ahora".
 */
final class TiempoTranscurrido
{
    private function __construct()
    {
    }

    public static function enMinutos(string $inicio, string $fin): ?int
    {
        $inicioFecha = self::parsear($inicio);
        $finFecha = self::parsear($fin);
        $sonValidas = $inicioFecha !== null && $finFecha !== null;

        if (!$sonValidas || $finFecha < $inicioFecha) {
            return null;
        }

        return (int) round(($finFecha->getTimestamp() - $inicioFecha->getTimestamp()) / 60);
    }

    private static function parsear(string $valor): ?\DateTimeImmutable
    {
        $fecha = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor);

        return $fecha instanceof \DateTimeImmutable ? $fecha : null;
    }
}
