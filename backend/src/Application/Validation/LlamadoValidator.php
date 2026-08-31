<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;

final class LlamadoValidator
{
    /** @return string[] */
    public function validar(
        ?string $fechaHora,
        ?string $tipoCrudo,
        ?string $origenCrudo,
        string $personalInterviniente,
    ): array {
        return RuleValidator::evaluar([
            [
                'valido' => !empty($fechaHora),
                'mensaje' => 'Debe indicar la fecha y hora del llamado.',
            ],
            [
                'valido' => TipoLlamado::tryFrom($tipoCrudo ?? '') !== null,
                'mensaje' => 'Debe seleccionar un tipo de llamado válido.',
            ],
            [
                'valido' => OrigenLlamado::tryFrom($origenCrudo ?? '') !== null,
                'mensaje' => 'Debe seleccionar un origen válido (Cama o Baño).',
            ],
            [
                'valido' => Texto::tieneLargoMinimo($personalInterviniente, 2),
                'mensaje' => 'Debe indicar el personal interviniente.',
            ],
        ]);
    }
}
