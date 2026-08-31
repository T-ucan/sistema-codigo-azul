<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;

final class UsuarioValidator
{
    public function __construct(private readonly UsuarioRepositoryInterface $usuarios)
    {
    }

    /** @return string[] */
    public function validar(
        string $nombre,
        string $usuario,
        ?string $rolCrudo,
        ?string $areaIdCrudo,
        string $claveIngresada,
        bool $esNuevo,
        ?int $idActual,
    ): array {
        $rol = Rol::tryFrom($rolCrudo ?? '');
        $requiereArea = $rol === Rol::ENCARGADO;

        return RuleValidator::evaluar([
            [
                'valido' => Texto::tieneLargoMinimo($nombre, 2),
                'mensaje' => 'El nombre completo es obligatorio.',
            ],
            [
                'valido' => Texto::tieneLargoMinimo($usuario, 3),
                'mensaje' => 'El usuario de acceso debe tener al menos 3 caracteres.',
            ],
            [
                'valido' => !$this->usuarios->existeNombreUsuario($usuario, $idActual),
                'mensaje' => 'Ya existe un usuario con ese nombre de acceso.',
            ],
            [
                'valido' => $rol !== null,
                'mensaje' => 'Debe seleccionar un rol válido.',
            ],
            [
                'valido' => !$requiereArea || !empty($areaIdCrudo),
                'mensaje' => 'Debe asignar un área al Encargado de Área.',
            ],
            [
                'valido' => self::claveValida($claveIngresada, $esNuevo),
                'mensaje' => 'La contraseña debe tener al menos 4 caracteres.',
            ],
        ]);
    }

    private static function claveValida(string $claveIngresada, bool $esNuevo): bool
    {
        if ($esNuevo) {
            return Texto::tieneLargoMinimo($claveIngresada, 4);
        }

        return $claveIngresada === '' || Texto::tieneLargoMinimo($claveIngresada, 4);
    }
}
