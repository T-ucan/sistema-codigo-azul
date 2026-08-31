<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\CodigoAzul;

use CodigoAzul\Domain\Repository\CodigoAzulRepositoryInterface;

final class ListarCodigosAzulUseCase
{
    public function __construct(private readonly CodigoAzulRepositoryInterface $codigosAzul)
    {
    }

    /** @return \CodigoAzul\Domain\Model\CodigoAzul[] */
    public function __invoke(): array
    {
        return $this->codigosAzul->listar();
    }
}
