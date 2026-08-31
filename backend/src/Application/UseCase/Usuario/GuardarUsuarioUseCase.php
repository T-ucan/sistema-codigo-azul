<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Usuario;

use CodigoAzul\Application\Validation\UsuarioValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;
use CodigoAzul\Domain\Service\PasswordHasherInterface;

final class GuardarUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly UsuarioValidator $validador,
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(
        string $nombre,
        string $usuario,
        ?string $rolCrudo,
        ?string $areaIdCrudo,
        string $claveIngresada,
        ?int $idExistente,
    ): Usuario {
        $esNuevo = $idExistente === null;
        $nombre = trim($nombre);
        $usuario = trim($usuario);
        $claveIngresada = trim($claveIngresada);

        $errores = $this->validador->validar($nombre, $usuario, $rolCrudo, $areaIdCrudo, $claveIngresada, $esNuevo, $idExistente);
        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        $rol = Rol::from($rolCrudo);
        $areaId = $rol === Rol::ENCARGADO ? (int) $areaIdCrudo : null;
        $claveHash = $this->resolverClaveHash($claveIngresada, $idExistente);

        return $this->usuarios->guardar(Usuario::crear($idExistente, $nombre, $usuario, $claveHash, $rol, $areaId));
    }

    private function resolverClaveHash(string $claveIngresada, ?int $idExistente): string
    {
        if ($claveIngresada !== '') {
            return $this->hasher->hash($claveIngresada);
        }

        $existente = $idExistente !== null ? $this->usuarios->buscarPorId($idExistente) : null;

        return $existente?->claveHash()
            ?? throw new ValidationException(['No se pudo determinar la contraseña del usuario.']);
    }
}
