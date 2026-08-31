<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Auth\AutenticarUsuarioUseCase;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

final class AuthController
{
    public function __construct(private readonly AutenticarUsuarioUseCase $autenticarUsuario)
    {
    }

    public function iniciarSesion(Request $solicitud): JsonResponse
    {
        $usuario = ($this->autenticarUsuario)($solicitud->campoTexto('usuario'), $solicitud->campoTexto('clave'));

        // Se regenera el ID de sesion en cada login (mitiga session fixation).
        session_regenerate_id(true);
        $_SESSION['usuarioId'] = $usuario->id();

        return JsonResponse::exito(['usuario' => $usuario->toArray()]);
    }

    public function cerrarSesion(): JsonResponse
    {
        unset($_SESSION['usuarioId']);
        session_regenerate_id(true);
        $_SESSION['csrfToken'] = bin2hex(random_bytes(32));

        return JsonResponse::exito();
    }

    public function sesionActual(Request $solicitud): JsonResponse
    {
        return JsonResponse::exito(['usuario' => $solicitud->usuarioAutenticado()?->toArray()]);
    }
}
