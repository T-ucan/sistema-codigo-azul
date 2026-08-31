<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Controller;

use CodigoAzul\Application\UseCase\Paciente\BuscarPacientesUseCase;
use CodigoAzul\Application\UseCase\Paciente\GuardarPacienteUseCase;
use CodigoAzul\Application\UseCase\Paciente\ListarPacientesUseCase;
use CodigoAzul\Domain\Model\Paciente;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;

final class PacienteController
{
    public function __construct(
        private readonly ListarPacientesUseCase $listarPacientes,
        private readonly BuscarPacientesUseCase $buscarPacientes,
        private readonly GuardarPacienteUseCase $guardarPaciente,
        private readonly AuthGuard $authGuard,
    ) {
    }

    /** Lectura: ambos roles la necesitan (fichas y selectores de llamado/código azul). */
    public function listar(Request $solicitud): JsonResponse
    {
        $this->authGuard->exigirAutenticado($solicitud);
        $texto = $solicitud->parametroQuery('busqueda');
        $pacientes = $texto === '' ? ($this->listarPacientes)() : ($this->buscarPacientes)($texto);

        return JsonResponse::exito(['pacientes' => array_map(static fn (Paciente $p): array => $p->toArray(), $pacientes)]);
    }

    /** Alta/edicion de fichas: solo Administrador (igual que el front-end). */
    public function guardar(Request $solicitud, array $parametros): JsonResponse
    {
        $this->authGuard->exigirRol($solicitud, Rol::ADMINISTRADOR);
        $idExistente = isset($parametros['id']) ? (int) $parametros['id'] : null;

        $paciente = ($this->guardarPaciente)(
            $solicitud->campoTexto('nombre'),
            $solicitud->campoTexto('dni'),
            $solicitud->campoTextoOpcional('fechaNacimiento'),
            $solicitud->campoTexto('datosMedicos'),
            $solicitud->campoTextoOpcional('areaId'),
            $idExistente,
        );

        return JsonResponse::exito(['paciente' => $paciente->toArray()], $idExistente === null ? 201 : 200);
    }
}
