<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\CodigoAzul;

interface CodigoAzulRepositoryInterface
{
    /** @return CodigoAzul[] Nunca null. */
    public function listar(): array;

    /** @return CodigoAzul[] Nunca null. */
    public function listarPorArea(int $areaId): array;

    public function guardar(CodigoAzul $codigoAzul): CodigoAzul;
}
