<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Area;

use CodigoAzul\Domain\Exception\ConflictException;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class EliminarAreaUseCase
{
    private const MENSAJE_EN_USO = 'No se puede eliminar: el área tiene pacientes, '
        . 'usuarios o eventos (llamados/códigos azul) asociados.';

    public function __construct(private readonly AreaRepositoryInterface $areas)
    {
    }

    public function __invoke(int $id): void
    {
        if ($this->areas->estaEnUso($id)) {
            throw new ConflictException(self::MENSAJE_EN_USO);
        }

        $this->areas->eliminar($id);
    }
}
