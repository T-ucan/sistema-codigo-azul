<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

final class Reporte
{
    public function __construct(
        private ?int $id,
        private string $fechaGeneracion,
        private string $formato,
        private string $filtrosAplicados,
        private ?int $usuarioId,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function fechaGeneracion(): string
    {
        return $this->fechaGeneracion;
    }

    public function formato(): string
    {
        return $this->formato;
    }

    public function filtrosAplicados(): string
    {
        return $this->filtrosAplicados;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fechaGeneracion' => $this->fechaGeneracion,
            'formato' => $this->formato,
            'filtrosAplicados' => $this->filtrosAplicados,
            'usuarioId' => $this->usuarioId,
        ];
    }
}
