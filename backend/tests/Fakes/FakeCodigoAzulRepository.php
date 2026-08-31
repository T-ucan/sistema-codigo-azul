<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\CodigoAzul;
use CodigoAzul\Domain\Repository\CodigoAzulRepositoryInterface;

final class FakeCodigoAzulRepository implements CodigoAzulRepositoryInterface
{
    /** @var array<int, CodigoAzul> */
    private array $fichas = [];
    private int $siguienteId = 1;

    public function listar(): array
    {
        return array_values($this->fichas);
    }

    public function listarPorArea(int $areaId): array
    {
        return array_values(array_filter($this->fichas, fn (CodigoAzul $c): bool => $c->areaId() === $areaId));
    }

    public function guardar(CodigoAzul $codigoAzul): CodigoAzul
    {
        if ($codigoAzul->id() === null) {
            $codigoAzul->asignarId($this->siguienteId++);
        }
        $this->fichas[$codigoAzul->id()] = $codigoAzul;

        return $codigoAzul;
    }
}
