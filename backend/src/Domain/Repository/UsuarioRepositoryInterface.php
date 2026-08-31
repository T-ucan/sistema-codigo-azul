<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\Usuario;

interface UsuarioRepositoryInterface
{
    /** @return Usuario[] Nunca null. */
    public function listar(): array;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorNombreUsuario(string $usuario): ?Usuario;

    public function existeNombreUsuario(string $usuario, ?int $exceptoId): bool;

    public function guardar(Usuario $usuario): Usuario;

    public function eliminar(int $id): void;
}
