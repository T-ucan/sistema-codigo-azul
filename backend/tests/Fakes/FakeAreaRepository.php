<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

/** Repositorio en memoria: prueba que los casos de uso solo dependen del puerto. */
final class FakeAreaRepository implements AreaRepositoryInterface
{
    /** @var array<int, Area> */
    private array $areas = [];
    private int $siguienteId = 1;
    /** @var int[] */
    private array $idsEnUso = [];

    public function listar(): array
    {
        return array_values($this->areas);
    }

    public function buscarPorId(int $id): ?Area
    {
        return $this->areas[$id] ?? null;
    }

    public function existeNombre(string $nombre, ?int $exceptoId): bool
    {
        foreach ($this->areas as $area) {
            $mismoNombre = mb_strtolower($area->nombre()) === mb_strtolower($nombre);
            if ($area->id() !== $exceptoId && $mismoNombre) {
                return true;
            }
        }

        return false;
    }

    public function estaEnUso(int $id): bool
    {
        return in_array($id, $this->idsEnUso, true);
    }

    public function marcarEnUso(int $id): void
    {
        $this->idsEnUso[] = $id;
    }

    public function guardar(Area $area): Area
    {
        if ($area->id() === null) {
            $area->asignarId($this->siguienteId++);
        }
        $this->areas[$area->id()] = $area;

        return $area;
    }

    public function eliminar(int $id): void
    {
        unset($this->areas[$id]);
    }
}
