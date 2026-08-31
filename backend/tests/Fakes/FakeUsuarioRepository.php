<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;

final class FakeUsuarioRepository implements UsuarioRepositoryInterface
{
    /** @var array<int, Usuario> */
    private array $usuarios = [];
    private int $siguienteId = 1;

    public function listar(): array
    {
        return array_values($this->usuarios);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->usuarios[$id] ?? null;
    }

    public function buscarPorNombreUsuario(string $usuario): ?Usuario
    {
        foreach ($this->usuarios as $candidato) {
            if (mb_strtolower($candidato->usuario()) === mb_strtolower($usuario)) {
                return $candidato;
            }
        }

        return null;
    }

    public function existeNombreUsuario(string $usuario, ?int $exceptoId): bool
    {
        $encontrado = $this->buscarPorNombreUsuario($usuario);

        return $encontrado !== null && $encontrado->id() !== $exceptoId;
    }

    public function guardar(Usuario $usuario): Usuario
    {
        if ($usuario->id() === null) {
            $usuario->asignarId($this->siguienteId++);
        }
        $this->usuarios[$usuario->id()] = $usuario;

        return $usuario;
    }

    public function eliminar(int $id): void
    {
        unset($this->usuarios[$id]);
    }
}
