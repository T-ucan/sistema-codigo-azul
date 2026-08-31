<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\Area;

interface AreaRepositoryInterface
{
    /** @return Area[] Nunca null: sin areas devuelve un arreglo vacio. */
    public function listar(): array;

    public function buscarPorId(int $id): ?Area;

    public function existeNombre(string $nombre, ?int $exceptoId): bool;

    /** @return bool true si hay pacientes, usuarios, llamados o codigos azul referenciando el area. */
    public function estaEnUso(int $id): bool;

    public function guardar(Area $area): Area;

    public function eliminar(int $id): void;
}
