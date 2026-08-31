<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\Llamado;

interface LlamadoRepositoryInterface
{
    /** @return Llamado[] Nunca null. */
    public function listar(): array;

    /** @return Llamado[] Nunca null. */
    public function listarPorArea(int $areaId): array;

    /** @return Llamado[] Nunca null. */
    public function listarPendientesPorArea(int $areaId): array;

    public function buscarPorId(int $id): ?Llamado;

    public function guardar(Llamado $llamado): Llamado;
}
