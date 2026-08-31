<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Usuario;

use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;

final class ListarUsuariosUseCase
{
    public function __construct(private readonly UsuarioRepositoryInterface $usuarios)
    {
    }

    /** @return \CodigoAzul\Domain\Model\Usuario[] */
    public function __invoke(): array
    {
        return $this->usuarios->listar();
    }
}
