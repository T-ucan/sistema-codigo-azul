<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Area;

use CodigoAzul\Application\Validation\AreaValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class GuardarAreaUseCase
{
    public function __construct(
        private readonly AreaRepositoryInterface $areas,
        private readonly AreaValidator $validador,
    ) {
    }

    public function __invoke(string $nombre, string $ubicacion, ?int $idExistente): Area
    {
        $nombre = trim($nombre);
        $ubicacion = trim($ubicacion);
        $errores = $this->validador->validar($nombre, $ubicacion, $idExistente);

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return $this->areas->guardar(new Area($idExistente, $nombre, $ubicacion));
    }
}
