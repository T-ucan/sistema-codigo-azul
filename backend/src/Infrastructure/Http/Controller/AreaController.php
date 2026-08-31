<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Area\EliminarAreaUseCase;
use CodigoAzul\Application\UseCase\Area\GuardarAreaUseCase;
use CodigoAzul\Application\UseCase\Area\ListarAreasUseCase;
use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class AreaController
{
    public function __construct(
        private readonly ListarAreasUseCase $listarAreas,
        private readonly GuardarAreaUseCase $guardarArea,
        private readonly EliminarAreaUseCase $eliminarArea,
        private readonly AuthGuard $authGuard,
    ) {
    }

    public function listar(Request $solicitud): JsonResponse
    {
        $this->authGuard->exigirAutenticado($solicitud);
        $areas = array_map(static fn (Area $area): array => $area->toArray(), ($this->listarAreas)());

        return JsonResponse::exito(['areas' => $areas]);
    }

    /** @param array<string, string> $parametros */
    public function guardar(Request $solicitud, array $parametros): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $idExistente = $this->idDesdeParametros($parametros);

        $area = ($this->guardarArea)($solicitud->campoTexto('nombre'), $solicitud->campoTexto('ubicacion'), $idExistente);

        return JsonResponse::exito(['area' => $area->toArray()], $idExistente === null ? 201 : 200);
    }

    /** @param array<string, string> $parametros */
    public function eliminar(Request $solicitud, array $parametros): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        ($this->eliminarArea)((int) $parametros['id']);

        return JsonResponse::exito();
    }

    /** @param array<string, string> $parametros */
    private function idDesdeParametros(array $parametros): ?int
    {
        return isset($parametros['id']) ? (int) $parametros['id'] : null;
    }
}
