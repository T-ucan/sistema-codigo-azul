<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Llamado;

use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;

final class ListarLlamadosPorAreaUseCase
{
    public function __construct(private readonly LlamadoRepositoryInterface $llamados)
    {
    }

    /** @return \CodigoAzul\Domain\Model\Llamado[] */
    public function __invoke(int $areaId): array
    {
        return $this->llamados->listarPorArea($areaId);
    }
}
