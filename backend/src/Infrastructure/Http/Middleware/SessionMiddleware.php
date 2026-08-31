<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Middleware;

use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

/**
 * Abre una sesion de servidor endurecida (cookie HttpOnly + Secure + Strict,
 * nunca accesible desde JavaScript), genera el token CSRF de la sesion si
 * no existe todavia, y resuelve el Usuario autenticado antes de que
 * cualquier controlador se ejecute.
 *
 * Nota: adjuntar el csrfToken a la respuesta NO se hace aca, sino en
 * Kernel::manejar(). Si lo hicieramos aca (despues de $siguiente()) un
 * caso de uso que lanza una excepcion (422 de validacion, 404, 409...)
 * nunca volveria a pasar por esta linea -la excepcion se propaga antes- y
 * el cliente se quedaria sin token para reintentar. El Kernel es el unico
 * punto que corre siempre, haya o no una excepcion.
 */
final class SessionMiddleware implements Middleware
{
    /** @param array{nombre: string, duracionSegundos: int} $configuracion */
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly array $configuracion,
    ) {
    }

    public function manejar(Request $solicitud, \Closure $siguiente): JsonResponse
    {
        $this->iniciarSesionSegura();
        $this->asegurarTokenCsrf();

        return $siguiente($solicitud->conUsuarioAutenticado($this->usuarioDeLaSesion()));
    }

    private function usuarioDeLaSesion(): ?\CodigoAzul\Domain\Model\Usuario
    {
        $usuarioId = $_SESSION['usuarioId'] ?? null;

        return is_int($usuarioId) ? $this->usuarios->buscarPorId($usuarioId) : null;
    }

    private function iniciarSesionSegura(): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->configuracion['nombre']);
        session_set_cookie_params([
            'lifetime' => $this->configuracion['duracionSegundos'],
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => ($_SERVER['HTTPS'] ?? '') !== '',
        ]);
        session_start();
    }

    private function asegurarTokenCsrf(): void
    {
        if (!isset($_SESSION['csrfToken'])) {
            $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
        }
    }
}
