<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Llamado\ListarLlamadosPendientesUseCase;
use CodigoAzul\Application\UseCase\Llamado\ListarLlamadosPorAreaUseCase;
use CodigoAzul\Application\UseCase\Llamado\MarcarLlamadoAtendidoUseCase;
use CodigoAzul\Application\UseCase\Llamado\RegistrarLlamadoUseCase;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class LlamadoController
{
    public function __construct(
        private readonly RegistrarLlamadoUseCase $registrarLlamado,
        private readonly MarcarLlamadoAtendidoUseCase $marcarAtendido,
        private readonly ListarLlamadosPendientesUseCase $listarPendientes,
        private readonly ListarLlamadosPorAreaUseCase $listarPorArea,
        private readonly AuthGuard $authGuard,
    ) {
    }

    public function listarPendientes(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);
        $llamados = ($this->listarPendientes)($this->areaDelEncargado($usuario));

        return JsonResponse::exito(['llamados' => $this->serializar($llamados)]);
    }

    public function listarDelArea(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);
        $llamados = ($this->listarPorArea)($this->areaDelEncargado($usuario));

        return JsonResponse::exito(['llamados' => $this->serializar($llamados)]);
    }

    public function registrar(Request $solicitud): JsonResponse
    {
        $usuario = $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);

        $llamado = ($this->registrarLlamado)(
            $solicitud->campoTextoOpcional('fechaHora'),
            $solicitud->campoTextoOpcional('tipo'),
            $solicitud->campoTextoOpcional('origen'),
            $solicitud->campoTextoOpcional('pacienteId'),
            $solicitud->campoTexto('personalInterviniente'),
            $solicitud->campoTexto('observaciones'),
            $this->areaDelEncargado($usuario),
        );

        return JsonResponse::exito(['llamado' => $llamado->toArray()], 201);
    }

    /** @param array<string, string> $parametros */
    public function marcarAtendido(Request $solicitud, array $parametros): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ENCARGADO);
        $ahora = (new \DateTimeImmutable())->format('Y-m-d\TH:i');

        $llamado = ($this->marcarAtendido)((int) $parametros['id'], $ahora);

        return JsonResponse::exito(['llamado' => $llamado->toArray()]);
    }

    /** El area de trabajo siempre sale de la sesion, nunca de un dato enviado por el cliente. */
    private function areaDelEncargado(\CodigoAzul\Domain\Model\Usuario $usuario): int
    {
        return (int) $usuario->areaId();
    }

    /** @param Llamado[] $llamados @return array<int, array<string, mixed>> */
    private function serializar(array $llamados): array
    {
        return array_map(static fn (Llamado $l): array => $l->toArray(), $llamados);
    }
}
