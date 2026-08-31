<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Security;

use CodigoAzul\Domain\Exception\AuthenticationException;
use CodigoAzul\Domain\Exception\ForbiddenException;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Infrastructure\Http\Request;

/**
 * Guardia de autorizacion que usan los controladores para exigir sesion
 * iniciada y, cuando corresponde, un rol especifico. Centraliza esta
 * verificacion en un solo lugar en vez de repetir ifs por controlador.
 */
final class AuthGuard
{
    public function exigirAutenticado(Request $solicitud): Usuario
    {
        return $solicitud->usuarioAutenticado() ?? throw new AuthenticationException('Debe iniciar sesión.');
    }

    public function exigirRol(Request $solicitud, Rol $rol): Usuario
    {
        $usuario = $this->exigirAutenticado($solicitud);

        if ($usuario->rol() !== $rol) {
            throw new ForbiddenException('No tiene permisos para esta acción.');
        }

        return $usuario;
    }
}
