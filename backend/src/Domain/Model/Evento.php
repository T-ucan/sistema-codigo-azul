<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

/**
 * Superclase de la especializacion total y disjunta del DER: todo Evento es
 * Llamado o CodigoAzul, nunca ambos ni ninguno. Un nivel de herencia.
 */
abstract class Evento
{
    public function __construct(
        protected ?int $id,
        protected string $fechaHora,
        protected int $areaId,
        protected string $personalInterviniente,
        protected string $observaciones,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function fechaHora(): string
    {
        return $this->fechaHora;
    }

    public function areaId(): int
    {
        return $this->areaId;
    }

    public function personalInterviniente(): string
    {
        return $this->personalInterviniente;
    }

    public function observaciones(): string
    {
        return $this->observaciones;
    }

    public function asignarId(int $id): void
    {
        $this->id ??= $id;
    }
}
