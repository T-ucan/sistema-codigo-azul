<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Reporte\FiltrosReporte;
use CodigoAzul\Application\UseCase\Reporte\GenerarEstadisticasUseCase;
use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;
use CodigoAzul\Tests\Fakes\FakeAreaRepository;
use CodigoAzul\Tests\Fakes\FakeLlamadoRepository;
use PHPUnit\Framework\TestCase;

final class GenerarEstadisticasUseCaseTest extends TestCase
{
    private function llamado(int $areaId, TipoLlamado $tipo, EstadoLlamado $estado, ?int $tiempoRespuesta, string $fecha = '2026-01-01T10:00'): Llamado
    {
        return new Llamado(
            id: null,
            fechaHora: $fecha,
            areaId: $areaId,
            personalInterviniente: 'Enf. Torres',
            observaciones: '',
            tipo: $tipo,
            origen: OrigenLlamado::CAMA,
            pacienteId: null,
            estado: $estado,
            tiempoRespuesta: $tiempoRespuesta,
        );
    }

    public function test_calcula_totales_atendidos_pendientes_y_promedio(): void
    {
        $areas = new FakeAreaRepository();
        $area = $areas->guardar(new Area(null, 'Guardia', 'Planta baja'));
        $llamados = new FakeLlamadoRepository();
        $llamados->guardar($this->llamado($area->id(), TipoLlamado::NORMAL, EstadoLlamado::ATENDIDO, 4));
        $llamados->guardar($this->llamado($area->id(), TipoLlamado::EMERGENCIA, EstadoLlamado::ATENDIDO, 2));
        $llamados->guardar($this->llamado($area->id(), TipoLlamado::NORMAL, EstadoLlamado::PENDIENTE, null));

        $estadisticas = (new GenerarEstadisticasUseCase($llamados, $areas))(new FiltrosReporte());

        self::assertSame(3, $estadisticas->total);
        self::assertSame(2, $estadisticas->atendidos);
        self::assertSame(1, $estadisticas->pendientes);
        self::assertSame(3.0, $estadisticas->tiempoPromedioRespuesta);
    }

    public function test_filtra_por_area(): void
    {
        $areas = new FakeAreaRepository();
        $guardia = $areas->guardar(new Area(null, 'Guardia', ''));
        $uti = $areas->guardar(new Area(null, 'UTI', ''));
        $llamados = new FakeLlamadoRepository();
        $llamados->guardar($this->llamado($guardia->id(), TipoLlamado::NORMAL, EstadoLlamado::ATENDIDO, 1));
        $llamados->guardar($this->llamado($uti->id(), TipoLlamado::NORMAL, EstadoLlamado::ATENDIDO, 1));

        $estadisticas = (new GenerarEstadisticasUseCase($llamados, $areas))(new FiltrosReporte(areaId: $guardia->id()));

        self::assertSame(1, $estadisticas->total);
    }

    public function test_promedio_es_cero_sin_llamados_atendidos(): void
    {
        $areas = new FakeAreaRepository();
        $llamados = new FakeLlamadoRepository();

        $estadisticas = (new GenerarEstadisticasUseCase($llamados, $areas))(new FiltrosReporte());

        self::assertSame(0.0, $estadisticas->tiempoPromedioRespuesta);
    }

    public function test_area_sin_nombre_se_reporta_como_sin_area(): void
    {
        $areas = new FakeAreaRepository();
        $llamados = new FakeLlamadoRepository();
        $llamados->guardar($this->llamado(999, TipoLlamado::NORMAL, EstadoLlamado::ATENDIDO, 1));

        $estadisticas = (new GenerarEstadisticasUseCase($llamados, $areas))(new FiltrosReporte());

        self::assertSame('Sin área', $estadisticas->porArea[0]['etiqueta']);
    }
}
