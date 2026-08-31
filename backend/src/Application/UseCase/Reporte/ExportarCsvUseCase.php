<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Reporte;

use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class ExportarCsvUseCase
{
    private const ENCABEZADO = ['Fecha y hora', 'Área', 'Tipo', 'Origen', 'Estado', 'Tiempo de respuesta (min)'];

    public function __construct(
        private readonly GenerarEstadisticasUseCase $generarEstadisticas,
        private readonly RegistrarGeneracionReporteUseCase $registrarGeneracion,
        private readonly AreaRepositoryInterface $areas,
    ) {
    }

    public function __invoke(FiltrosReporte $filtros, int $usuarioId): string
    {
        $estadisticas = ($this->generarEstadisticas)($filtros);
        $csv = $this->construirCsv($estadisticas->llamados);
        ($this->registrarGeneracion)('CSV', $filtros, $usuarioId);

        return $csv;
    }

    /** @param Llamado[] $llamados */
    private function construirCsv(array $llamados): string
    {
        $filas = array_map($this->filaCsv(...), $llamados);
        $lineas = array_map(
            fn (array $fila): string => implode(',', array_map($this->escaparCampo(...), $fila)),
            [self::ENCABEZADO, ...$filas],
        );

        return implode("\n", $lineas);
    }

    /** @return string[] */
    private function filaCsv(Llamado $llamado): array
    {
        return [
            $llamado->fechaHora(),
            $this->areas->buscarPorId($llamado->areaId())?->nombre() ?? 'Sin área',
            $llamado->tipo()->value,
            $llamado->origen()->value,
            $llamado->estado()->value,
            $llamado->tiempoRespuesta() === null ? '' : (string) $llamado->tiempoRespuesta(),
        ];
    }

    private function escaparCampo(string $valor): string
    {
        $requiereComillas = (bool) preg_match('/[",\n;]/', $valor);
        $escapado = str_replace('"', '""', $valor);

        return $requiereComillas ? '"' . $escapado . '"' : $escapado;
    }
}
