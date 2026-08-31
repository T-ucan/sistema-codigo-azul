<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http;

use CodigoAzul\Infrastructure\Http\ErrorHandling\ManejadorErrores;
use CodigoAzul\Infrastructure\Http\Middleware\Middleware;

/**
 * Motor del Front Controller: arma el pipeline de middlewares de seguridad
 * (headers, sesion, CSRF) alrededor del despacho de rutas. public/index.php
 * es la unica puerta de entrada HTTP; este Kernel es lo que ese unico punto
 * de entrada ejecuta.
 *
 * El manejo centralizado de errores vive ADENTRO del Kernel (no solo en
 * index.php): asi la garantia de "nunca devolver un stack trace ni un
 * mensaje crudo de base de datos" es estructural, y se cumple para
 * cualquier codigo que invoque manejar() (tests incluidos), no solo para
 * quien recuerde envolver el llamado en un try/catch.
 */
final class Kernel
{
    /** @param Middleware[] $middlewares */
    public function __construct(
        private readonly array $middlewares,
        private readonly Router $router,
        private readonly ManejadorErrores $manejadorErrores,
    ) {
    }

    public function manejar(Request $solicitud): JsonResponse
    {
        try {
            $respuesta = $this->ejecutarPipeline($solicitud);
        } catch (\Throwable $error) {
            $respuesta = $this->manejadorErrores->manejar($error);
        }

        // Se adjunta aca (no en SessionMiddleware) porque este es el unico
        // punto que corre siempre, tanto si la peticion tuvo éxito como si
        // algun caso de uso lanzo una excepcion a mitad del pipeline.
        return $this->conCsrfTokenVigente($respuesta);
    }

    private function conCsrfTokenVigente(JsonResponse $respuesta): JsonResponse
    {
        $token = $_SESSION['csrfToken'] ?? null;

        return $token === null ? $respuesta : $respuesta->conDato('csrfToken', $token);
    }

    private function ejecutarPipeline(Request $solicitud): JsonResponse
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn (\Closure $siguiente, Middleware $middleware): \Closure
                => fn (Request $solicitud): JsonResponse => $middleware->manejar($solicitud, $siguiente),
            fn (Request $solicitud): JsonResponse => $this->despachar($solicitud),
        );

        return $pipeline($solicitud);
    }

    private function despachar(Request $solicitud): JsonResponse
    {
        $coincidencia = $this->router->resolver($solicitud->metodo, $solicitud->ruta);

        if ($coincidencia === null) {
            return JsonResponse::error('Recurso no encontrado.', 404);
        }

        return ($coincidencia['manejador'])($solicitud, $coincidencia['parametros']);
    }
}
