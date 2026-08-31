<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Auth;

use CodigoAzul\Domain\Exception\AuthenticationException;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;
use CodigoAzul\Domain\Service\PasswordHasherInterface;

final class AutenticarUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(string $usuario, string $clave): Usuario
    {
        $registro = $this->usuarios->buscarPorNombreUsuario(trim($usuario));
        $credencialesValidas = $registro !== null && $this->hasher->verificar($clave, $registro->claveHash());

        if (!$credencialesValidas) {
            throw new AuthenticationException('Usuario o contraseña incorrectos.');
        }

        return $registro;
    }
}
