<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Reporte;

use CodigoAzul\Domain\Model\Reporte;
use CodigoAzul\Domain\Repository\ReporteRepositoryInterface;

final class RegistrarGeneracionReporteUseCase
{
    public function __construct(private readonly ReporteRepositoryInterface $reportes)
    {
    }

    public function __invoke(string $formato, FiltrosReporte $filtros, int $usuarioId): Reporte
    {
        return $this->reportes->registrar(new Reporte(
            id: null,
            fechaGeneracion: (new \DateTimeImmutable())->format('Y-m-d\TH:i:s'),
            formato: $formato,
            filtrosAplicados: json_encode($filtros->aArreglo(), JSON_THROW_ON_ERROR),
            usuarioId: $usuarioId,
        ));
    }
}
