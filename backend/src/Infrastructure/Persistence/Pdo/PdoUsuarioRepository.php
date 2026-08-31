<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;

final class PdoUsuarioRepository implements UsuarioRepositoryInterface
{
    private const COLUMNAS = 'id, nombre, usuario, clave_hash, rol, area_id';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . ' FROM usuarios ORDER BY nombre';
        $filas = $this->pdo->query($sql)->fetchAll();

        return array_map($this->mapear(...), $filas);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->buscarPorColumna('id', $id);
    }

    public function buscarPorNombreUsuario(string $usuario): ?Usuario
    {
        return $this->buscarPorColumna('usuario', $usuario);
    }

    public function existeNombreUsuario(string $usuario, ?int $exceptoId): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM usuarios WHERE LOWER(usuario) = LOWER(:usuario) AND id != :exceptoId
            ) AS existe',
        );
        $consulta->execute(['usuario' => $usuario, 'exceptoId' => $exceptoId ?? 0]);

        return (bool) $consulta->fetchColumn();
    }

    public function guardar(Usuario $usuario): Usuario
    {
        return $usuario->id() === null ? $this->insertar($usuario) : $this->actualizar($usuario);
    }

    public function eliminar(int $id): void
    {
        $consulta = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $consulta->execute(['id' => $id]);
    }

    private function buscarPorColumna(string $columna, int|string $valor): ?Usuario
    {
        $sql = 'SELECT ' . self::COLUMNAS . " FROM usuarios WHERE {$columna} = :valor";
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute(['valor' => $valor]);
        $fila = $consulta->fetch();

        return $fila === false ? null : $this->mapear($fila);
    }

    private function insertar(Usuario $usuario): Usuario
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO usuarios (nombre, usuario, clave_hash, rol, area_id)
             VALUES (:nombre, :usuario, :claveHash, :rol, :areaId)',
        );
        $consulta->execute($this->parametros($usuario));
        $usuario->asignarId((int) $this->pdo->lastInsertId());

        return $usuario;
    }

    private function actualizar(Usuario $usuario): Usuario
    {
        $consulta = $this->pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, usuario = :usuario, clave_hash = :claveHash,
                rol = :rol, area_id = :areaId WHERE id = :id',
        );
        $consulta->execute([...$this->parametros($usuario), 'id' => $usuario->id()]);

        return $usuario;
    }

    /** @return array<string, scalar|null> */
    private function parametros(Usuario $usuario): array
    {
        return [
            'nombre' => $usuario->nombre(),
            'usuario' => $usuario->usuario(),
            'claveHash' => $usuario->claveHash(),
            'rol' => $usuario->rol()->value,
            'areaId' => $usuario->areaId(),
        ];
    }

    private function mapear(array $fila): Usuario
    {
        return Usuario::crear(
            (int) $fila['id'],
            $fila['nombre'],
            $fila['usuario'],
            $fila['clave_hash'],
            Rol::from($fila['rol']),
            $fila['area_id'] !== null ? (int) $fila['area_id'] : null,
        );
    }
}
