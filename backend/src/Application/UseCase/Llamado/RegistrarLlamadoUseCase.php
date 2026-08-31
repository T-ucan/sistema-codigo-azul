<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Llamado;

use CodigoAzul\Application\Validation\Enteros;
use CodigoAzul\Application\Validation\LlamadoValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;

final class RegistrarLlamadoUseCase
{
    public function __construct(
        private readonly LlamadoRepositoryInterface $llamados,
        private readonly LlamadoValidator $validador,
    ) {
    }

    public function __invoke(
        ?string $fechaHora,
        ?string $tipoCrudo,
        ?string $origenCrudo,
        ?string $pacienteIdCrudo,
        string $personalInterviniente,
        string $observaciones,
        int $areaId,
    ): Llamado {
        $personalInterviniente = trim($personalInterviniente);
        $errores = $this->validador->validar($fechaHora, $tipoCrudo, $origenCrudo, $personalInterviniente);

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        $llamado = new Llamado(
            id: null,
            fechaHora: $fechaHora,
            areaId: $areaId,
            personalInterviniente: $personalInterviniente,
            observaciones: trim($observaciones),
            tipo: TipoLlamado::from($tipoCrudo),
            origen: OrigenLlamado::from($origenCrudo),
            pacienteId: Enteros::opcional($pacienteIdCrudo),
            estado: EstadoLlamado::PENDIENTE,
            tiempoRespuesta: null,
        );

        return $this->llamados->guardar($llamado);
    }
}
