<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Domain;

use CodigoAzul\Domain\Service\TiempoTranscurrido;
use PHPUnit\Framework\TestCase;

final class TiempoTranscurridoTest extends TestCase
{
    public function test_calcula_minutos_entre_dos_fechas(): void
    {
        self::assertSame(15, TiempoTranscurrido::enMinutos('2026-01-01T10:00', '2026-01-01T10:15'));
    }

    public function test_devuelve_cero_cuando_las_fechas_son_iguales(): void
    {
        self::assertSame(0, TiempoTranscurrido::enMinutos('2026-01-01T10:00', '2026-01-01T10:00'));
    }

    public function test_devuelve_null_si_el_fin_es_anterior_al_inicio(): void
    {
        self::assertNull(TiempoTranscurrido::enMinutos('2026-01-01T10:15', '2026-01-01T10:00'));
    }

    public function test_devuelve_null_ante_fechas_invalidas(): void
    {
        self::assertNull(TiempoTranscurrido::enMinutos('fecha-invalida', '2026-01-01T10:00'));
    }

    /**
     * Regresion del bug real encontrado en el front-end original: mezclar
     * hora local con UTC inflaba el tiempo de respuesta (se vio "180 min"
     * para un llamado atendido casi al instante, en UTC-3). Este servicio
     * nunca debe involucrar la hora del sistema ni UTC: solo resta los dos
     * textos que recibe, tal cual.
     */
    public function test_no_asume_ninguna_zona_horaria_implicita(): void
    {
        self::assertSame(3, TiempoTranscurrido::enMinutos('2026-01-01T23:57', '2026-01-02T00:00'));
    }
}
