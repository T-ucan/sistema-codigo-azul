<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

final class Paciente
{
    public function __construct(
        private ?int $id,
        private string $nombre,
        private string $dni,
        private string $fechaNacimiento,
        private string $datosMedicos,
        private int $areaId,
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

    public function dni(): string
    {
        return $this->dni;
    }

    public function fechaNacimiento(): string
    {
        return $this->fechaNacimiento;
    }

    public function datosMedicos(): string
    {
        return $this->datosMedicos;
    }

    public function areaId(): int
    {
        return $this->areaId;
    }

    public function asignarId(int $id): void
    {
        $this->id ??= $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'dni' => $this->dni,
            'fechaNacimiento' => $this->fechaNacimiento,
            'datosMedicos' => $this->datosMedicos,
            'areaId' => $this->areaId,
        ];
    }
}
