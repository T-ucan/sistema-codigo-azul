<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Usuario\EliminarUsuarioUseCase;
use CodigoAzul\Application\UseCase\Usuario\GuardarUsuarioUseCase;
use CodigoAzul\Application\UseCase\Usuario\ListarUsuariosUseCase;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class UsuarioController
{
    public function __construct(
        private readonly ListarUsuariosUseCase $listarUsuarios,
        private readonly GuardarUsuarioUseCase $guardarUsuario,
        private readonly EliminarUsuarioUseCase $eliminarUsuario,
        private readonly AuthGuard $authGuard,
    ) {
    }

    public function listar(Request $solicitud): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $usuarios = array_map(static fn (Usuario $u): array => $u->toArray(), ($this->listarUsuarios)());

        return JsonResponse::exito(['usuarios' => $usuarios]);
    }

    /** @param array<string, string> $parametros */
    public function guardar(Request $solicitud, array $parametros): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $idExistente = isset($parametros['id']) ? (int) $parametros['id'] : null;

        $usuario = ($this->guardarUsuario)(
            $solicitud->campoTexto('nombre'),
            $solicitud->campoTexto('usuario'),
            $solicitud->campoTextoOpcional('rol'),
            $solicitud->campoTextoOpcional('areaId'),
            $solicitud->campoTexto('clave'),
            $idExistente,
        );

        return JsonResponse::exito(['usuario' => $usuario->toArray()], $idExistente === null ? 201 : 200);
    }

    /** @param array<string, string> $parametros */
    public function eliminar(Request $solicitud, array $parametros): JsonResponse
    {
        $usuarioActual = $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        ($this->eliminarUsuario)((int) $parametros['id'], (int) $usuarioActual->id());

        return JsonResponse::exito();
    }
}
