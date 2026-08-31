<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Usuario;

use CodigoAzul\Domain\Exception\ConflictException;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;

final class EliminarUsuarioUseCase
{
    public function __construct(private readonly UsuarioRepositoryInterface $usuarios)
    {
    }

    public function __invoke(int $id, int $usuarioActualId): void
    {
        if ($id === $usuarioActualId) {
            throw new ConflictException('No puede eliminar el usuario con el que está trabajando.');
        }

        $this->usuarios->eliminar($id);
    }
}
