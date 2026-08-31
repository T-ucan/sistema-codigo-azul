<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

use CodigoAzul\Domain\Model\ResultadoCodigoAzul;

final class CodigoAzulValidator
{
    /** @return string[] */
    public function validar(
        ?string $fechaHora,
        ?string $pacienteIdCrudo,
        ?string $horaLlegadaEquipo,
        string $personalInterviniente,
        string $intervencionRealizada,
        ?string $resultadoCrudo,
    ): array {
        $llegadaPosterior = !empty($horaLlegadaEquipo) && !empty($fechaHora) && $horaLlegadaEquipo >= $fechaHora;

        return RuleValidator::evaluar([
            [
                'valido' => !empty($fechaHora),
                'mensaje' => 'Debe indicar la fecha y hora del evento.',
            ],
            [
                'valido' => !empty($pacienteIdCrudo),
                'mensaje' => 'Debe seleccionar el paciente correspondiente.',
            ],
            [
                'valido' => $llegadaPosterior,
                'mensaje' => 'La hora de llegada del equipo debe ser posterior al evento.',
            ],
            [
                'valido' => Texto::tieneLargoMinimo($personalInterviniente, 2),
                'mensaje' => 'Debe indicar el personal interviniente.',
            ],
            [
                'valido' => Texto::tieneLargoMinimo($intervencionRealizada, 3),
                'mensaje' => 'Debe describir la intervención realizada.',
            ],
            [
                'valido' => ResultadoCodigoAzul::tryFrom($resultadoCrudo ?? '') !== null,
                'mensaje' => 'Debe seleccionar un resultado válido.',
            ],
        ]);
    }
}
