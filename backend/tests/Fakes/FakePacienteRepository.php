<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\Paciente;
use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;

final class FakePacienteRepository implements PacienteRepositoryInterface
{
    /** @var array<int, Paciente> */
    private array $pacientes = [];
    private int $siguienteId = 1;

    public function listar(): array
    {
        return array_values($this->pacientes);
    }

    public function listarPorArea(int $areaId): array
    {
        return array_values(array_filter($this->pacientes, fn (Paciente $p): bool => $p->areaId() === $areaId));
    }

    public function buscar(string $texto): array
    {
        $consulta = mb_strtolower($texto);

        return array_values(array_filter(
            $this->pacientes,
            fn (Paciente $p): bool => str_contains(mb_strtolower($p->nombre()), $consulta) || str_contains($p->dni(), $consulta),
        ));
    }

    public function buscarPorId(int $id): ?Paciente
    {
        return $this->pacientes[$id] ?? null;
    }

    public function existeDni(string $dni, ?int $exceptoId): bool
    {
        foreach ($this->pacientes as $paciente) {
            if ($paciente->id() !== $exceptoId && $paciente->dni() === $dni) {
                return true;
            }
        }

        return false;
    }

    public function guardar(Paciente $paciente): Paciente
    {
        if ($paciente->id() === null) {
            $paciente->asignarId($this->siguienteId++);
        }
        $this->pacientes[$paciente->id()] = $paciente;

        return $paciente;
    }
}
