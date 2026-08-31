<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;

final class PacienteValidator
{
    public function __construct(private readonly PacienteRepositoryInterface $pacientes)
    {
    }

    /** @return string[] */
    public function validar(
        string $nombre,
        string $dni,
        ?string $fechaNacimiento,
        ?string $areaIdCrudo,
        ?int $idActual,
    ): array {
        return RuleValidator::evaluar([
            [
                'valido' => Texto::tieneLargoMinimo($nombre, 2),
                'mensaje' => 'El nombre del paciente es obligatorio.',
            ],
            [
                'valido' => (bool) preg_match('/^\d{7,8}$/', $dni),
                'mensaje' => 'El DNI debe tener entre 7 y 8 dígitos numéricos.',
            ],
            [
                'valido' => !$this->pacientes->existeDni($dni, $idActual),
                'mensaje' => 'Ya existe un paciente registrado con ese DNI.',
            ],
            [
                'valido' => !empty($fechaNacimiento) && $fechaNacimiento <= date('Y-m-d'),
                'mensaje' => 'La fecha de nacimiento no puede ser futura.',
            ],
            [
                'valido' => !empty($areaIdCrudo),
                'mensaje' => 'Debe asignar un área al paciente.',
            ],
        ]);
    }
}
