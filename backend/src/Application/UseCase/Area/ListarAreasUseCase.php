<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Area;

use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class ListarAreasUseCase
{
    public function __construct(private readonly AreaRepositoryInterface $areas)
    {
    }

    /** @return \CodigoAzul\Domain\Model\Area[] */
    public function __invoke(): array
    {
        return $this->areas->listar();
    }
}
