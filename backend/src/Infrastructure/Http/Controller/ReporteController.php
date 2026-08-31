<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Reporte\ExportarCsvUseCase;
use CodigoAzul\Application\UseCase\Reporte\FiltrosReporte;
use CodigoAzul\Application\UseCase\Reporte\GenerarEstadisticasUseCase;
use CodigoAzul\Application\UseCase\Reporte\RegistrarGeneracionReporteUseCase;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class ReporteController
{
    public function __construct(
        private readonly GenerarEstadisticasUseCase $generarEstadisticas,
        private readonly ExportarCsvUseCase $exportarCsv,
        private readonly RegistrarGeneracionReporteUseCase $registrarGeneracion,
        private readonly AuthGuard $authGuard,
    ) {
    }

    public function estadisticas(Request $solicitud): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $estadisticas = ($this->generarEstadisticas)($this->filtrosDesdeQuery($solicitud));

        return JsonResponse::exito([
            'total' => $estadisticas->total,
            'atendidos' => $estadisticas->atendidos,
            'pendientes' => $estadisticas->pendientes,
            'tiempoPromedioRespuesta' => $estadisticas->tiempoPromedioRespuesta,
            'porArea' => $estadisticas->porArea,
            'porTipo' => $estadisticas->porTipo,
            'porDia' => $estadisticas->porDia,
            'llamados' => array_map(static fn (Llamado $l): array => $l->toArray(), $estadisticas->llamados),
        ]);
    }

    public function exportarCsv(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $csv = ($this->exportarCsv)($this->filtrosDesdeCuerpo($solicitud), (int) $usuario->id());

        return JsonResponse::exito(['csv' => $csv, 'nombreArchivo' => 'reporte-codigo-azul.csv']);
    }

    public function registrarExportacionPdf(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        ($this->registrarGeneracion)('PDF', $this->filtrosDesdeCuerpo($solicitud), (int) $usuario->id());

        return JsonResponse::exito();
    }

    private function filtrosDesdeQuery(Request $solicitud): FiltrosReporte
    {
        return FiltrosReporte::desdeArreglo([
            'areaId' => $solicitud->parametroQuery('areaId'),
            'origen' => $solicitud->parametroQuery('origen'),
            'tipo' => $solicitud->parametroQuery('tipo'),
            'desde' => $solicitud->parametroQuery('desde'),
            'hasta' => $solicitud->parametroQuery('hasta'),
        ]);
    }

    private function filtrosDesdeCuerpo(Request $solicitud): FiltrosReporte
    {
        return FiltrosReporte::desdeArreglo([
            'areaId' => $solicitud->campoTexto('areaId'),
            'origen' => $solicitud->campoTexto('origen'),
            'tipo' => $solicitud->campoTexto('tipo'),
            'desde' => $solicitud->campoTexto('desde'),
            'hasta' => $solicitud->campoTexto('hasta'),
        ]);
    }
}
