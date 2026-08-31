<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;

final class FakeLlamadoRepository implements LlamadoRepositoryInterface
{
    /** @var array<int, Llamado> */
    private array $llamados = [];
    private int $siguienteId = 1;

    public function listar(): array
    {
        return array_values($this->llamados);
    }

    public function listarPorArea(int $areaId): array
    {
        return array_values(array_filter($this->llamados, fn (Llamado $l): bool => $l->areaId() === $areaId));
    }

    public function listarPendientesPorArea(int $areaId): array
    {
        return array_values(array_filter(
            $this->listarPorArea($areaId),
            fn (Llamado $l): bool => $l->estado() === EstadoLlamado::PENDIENTE,
        ));
    }

    public function buscarPorId(int $id): ?Llamado
    {
        return $this->llamados[$id] ?? null;
    }

    public function guardar(Llamado $llamado): Llamado
    {
        if ($llamado->id() === null) {
            $llamado->asignarId($this->siguienteId++);
        }
        $this->llamados[$llamado->id()] = $llamado;

        return $llamado;
    }
}
