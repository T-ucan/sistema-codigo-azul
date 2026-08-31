<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Reporte;

final class EstadisticasReporte
{
    /**
     * @param \CodigoAzul\Domain\Model\Llamado[] $llamados
     * @param array<int, array{etiqueta: string, valor: int}> $porArea
     * @param array<int, array{etiqueta: string, valor: int}> $porTipo
     * @param array<int, array{etiqueta: string, valor: int}> $porDia
     */
    public function __construct(
        public readonly array $llamados,
        public readonly int $total,
        public readonly int $atendidos,
        public readonly int $pendientes,
        public readonly float $tiempoPromedioRespuesta,
        public readonly array $porArea,
        public readonly array $porTipo,
        public readonly array $porDia,
    ) {
    }
}
