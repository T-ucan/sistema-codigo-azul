<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\CodigoAzul\ListarCodigosAzulUseCase;
use CodigoAzul\Application\UseCase\CodigoAzul\RegistrarCodigoAzulUseCase;
use CodigoAzul\Domain\Model\CodigoAzul;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class CodigoAzulController
{
    public function __construct(
        private readonly RegistrarCodigoAzulUseCase $registrarCodigoAzul,
        private readonly ListarCodigosAzulUseCase $listarCodigosAzul,
        private readonly AuthGuard $authGuard,
    ) {
    }

    public function listar(Request $solicitud): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);
        $fichas = array_map(static fn (CodigoAzul $c): array => $c->toArray(), ($this->listarCodigosAzul)());

        return JsonResponse::exito(['codigosAzul' => $fichas]);
    }

    public function registrar(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);

        $ficha = ($this->registrarCodigoAzul)(
            $solicitud->campoTextoOpcional('fechaHora'),
            $solicitud->campoTextoOpcional('pacienteId'),
            $solicitud->campoTextoOpcional('horaLlegadaEquipo'),
            $solicitud->campoTexto('personalInterviniente'),
            $solicitud->campoTexto('intervencionRealizada'),
            $solicitud->campoTextoOpcional('resultado'),
            $solicitud->campoTexto('observaciones'),
            $solicitud->campoTextoOpcional('llamadoOrigenId'),
            (int) $usuario->areaId(),
        );

        return JsonResponse::exito(['codigoAzul' => $ficha->toArray()], 201);
    }
}
