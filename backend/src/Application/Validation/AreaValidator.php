<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class AreaValidator
{
    public function __construct(private readonly AreaRepositoryInterface $areas)
    {
    }

    /** @return string[] */
    public function validar(string $nombre, string $ubicacion, ?int $idActual): array
    {
        return RuleValidator::evaluar([
            [
                'valido' => Texto::tieneLargoMinimo($nombre, 2),
                'mensaje' => 'El nombre del área debe tener al menos 2 caracteres.',
            ],
            [
                'valido' => !$this->areas->existeNombre($nombre, $idActual),
                'mensaje' => 'Ya existe un área con ese nombre.',
            ],
            [
                'valido' => Texto::tieneLargoMinimo($ubicacion, 2),
                'mensaje' => 'La ubicación es obligatoria.',
            ],
        ]);
    }
}
