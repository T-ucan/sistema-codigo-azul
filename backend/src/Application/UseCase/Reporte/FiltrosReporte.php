<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Reporte;

use CodigoAzul\Application\Validation\Enteros;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;

/**
 * Objeto de especificacion: sabe si un Llamado "coincide" con los filtros
 * aplicados. Cada regla es un metodo de una sola condicion (sin ifs
 * anidados); coincideCon() solo los combina con AND.
 */
final class FiltrosReporte
{
    public function __construct(
        public readonly ?int $areaId = null,
        public readonly ?OrigenLlamado $origen = null,
        public readonly ?TipoLlamado $tipo = null,
        public readonly ?string $desde = null,
        public readonly ?string $hasta = null,
    ) {
    }

    /** @param array<string, string|null> $datos */
    public static function desdeArreglo(array $datos): self
    {
        return new self(
            areaId: Enteros::opcional($datos['areaId'] ?? null),
            origen: OrigenLlamado::tryFrom($datos['origen'] ?? ''),
            tipo: TipoLlamado::tryFrom($datos['tipo'] ?? ''),
            desde: ($datos['desde'] ?? '') !== '' ? $datos['desde'] : null,
            hasta: ($datos['hasta'] ?? '') !== '' ? $datos['hasta'] : null,
        );
    }

    public function coincideCon(Llamado $llamado): bool
    {
        return $this->coincideArea($llamado)
            && $this->coincideOrigen($llamado)
            && $this->coincideTipo($llamado)
            && $this->coincideDesde($llamado)
            && $this->coincideHasta($llamado);
    }

    /** @return array<string, string|null> */
    public function aArreglo(): array
    {
        return [
            'areaId' => $this->areaId !== null ? (string) $this->areaId : null,
            'origen' => $this->origen?->value,
            'tipo' => $this->tipo?->value,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ];
    }

    private function coincideArea(Llamado $llamado): bool
    {
        return $this->areaId === null || $llamado->areaId() === $this->areaId;
    }

    private function coincideOrigen(Llamado $llamado): bool
    {
        return $this->origen === null || $llamado->origen() === $this->origen;
    }

    private function coincideTipo(Llamado $llamado): bool
    {
        return $this->tipo === null || $llamado->tipo() === $this->tipo;
    }

    private function coincideDesde(Llamado $llamado): bool
    {
        return $this->desde === null || substr($llamado->fechaHora(), 0, 10) >= $this->desde;
    }

    private function coincideHasta(Llamado $llamado): bool
    {
        return $this->hasta === null || substr($llamado->fechaHora(), 0, 10) <= $this->hasta;
    }
}
