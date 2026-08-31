<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\CodigoAzul;

use CodigoAzul\Application\Validation\CodigoAzulValidator;
use CodigoAzul\Application\Validation\Enteros;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\CodigoAzul;
use CodigoAzul\Domain\Model\ResultadoCodigoAzul;
use CodigoAzul\Domain\Repository\CodigoAzulRepositoryInterface;

final class RegistrarCodigoAzulUseCase
{
    public function __construct(
        private readonly CodigoAzulRepositoryInterface $codigosAzul,
        private readonly CodigoAzulValidator $validador,
    ) {
    }

    public function __invoke(
        ?string $fechaHora,
        ?string $pacienteIdCrudo,
        ?string $horaLlegadaEquipo,
        string $personalInterviniente,
        string $intervencionRealizada,
        ?string $resultadoCrudo,
        string $observaciones,
        ?string $llamadoOrigenIdCrudo,
        int $areaId,
    ): CodigoAzul {
        $personalInterviniente = trim($personalInterviniente);
        $intervencionRealizada = trim($intervencionRealizada);

        $this->validarODescartar($fechaHora, $pacienteIdCrudo, $horaLlegadaEquipo, $personalInterviniente, $intervencionRealizada, $resultadoCrudo);

        $ficha = self::crearFicha(
            $fechaHora,
            $areaId,
            $personalInterviniente,
            trim($observaciones),
            $pacienteIdCrudo,
            $horaLlegadaEquipo,
            $intervencionRealizada,
            $resultadoCrudo,
            $llamadoOrigenIdCrudo,
        );

        return $this->codigosAzul->guardar($ficha);
    }

    private function validarODescartar(
        ?string $fechaHora,
        ?string $pacienteIdCrudo,
        ?string $horaLlegadaEquipo,
        string $personalInterviniente,
        string $intervencionRealizada,
        ?string $resultadoCrudo,
    ): void {
        $errores = $this->validador->validar(
            $fechaHora,
            $pacienteIdCrudo,
            $horaLlegadaEquipo,
            $personalInterviniente,
            $intervencionRealizada,
            $resultadoCrudo,
        );
        if ($errores !== []) {
            throw new ValidationException($errores);
        }
    }

    private static function crearFicha(
        string $fechaHora,
        int $areaId,
        string $personalInterviniente,
        string $observaciones,
        string $pacienteIdCrudo,
        string $horaLlegadaEquipo,
        string $intervencionRealizada,
        string $resultadoCrudo,
        ?string $llamadoOrigenIdCrudo,
    ): CodigoAzul {
        return new CodigoAzul(
            id: null,
            fechaHora: $fechaHora,
            areaId: $areaId,
            personalInterviniente: $personalInterviniente,
            observaciones: $observaciones,
            pacienteId: (int) $pacienteIdCrudo,
            horaLlegadaEquipo: $horaLlegadaEquipo,
            intervencionRealizada: $intervencionRealizada,
            resultado: ResultadoCodigoAzul::from($resultadoCrudo),
            llamadoOrigenId: Enteros::opcional($llamadoOrigenIdCrudo),
        );
    }
}
