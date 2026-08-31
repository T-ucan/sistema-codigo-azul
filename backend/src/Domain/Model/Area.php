<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

final class Area
{
    public function __construct(
        private ?int $id,
        private string $nombre,
        private string $ubicacion,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function ubicacion(): string
    {
        return $this->ubicacion;
    }

    public function asignarId(int $id): void
    {
        $this->id ??= $id;
    }

    /** @return array{id: ?int, nombre: string, ubicacion: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre, 'ubicacion' => $this->ubicacion];
    }
}
