<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\Paciente;

interface PacienteRepositoryInterface
{
    /** @return Paciente[] Nunca null. */
    public function listar(): array;

    /** @return Paciente[] Nunca null. */
    public function listarPorArea(int $areaId): array;

    /** @return Paciente[] Nunca null. */
    public function buscar(string $texto): array;

    public function buscarPorId(int $id): ?Paciente;

    public function existeDni(string $dni, ?int $exceptoId): bool;

    public function guardar(Paciente $paciente): Paciente;
}
