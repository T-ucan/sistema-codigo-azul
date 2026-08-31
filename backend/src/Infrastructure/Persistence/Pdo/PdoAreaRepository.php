<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;

final class PdoAreaRepository implements AreaRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function listar(): array
    {
        $filas = $this->pdo->query('SELECT id, nombre, ubicacion FROM areas ORDER BY nombre')->fetchAll();

        return array_map($this->mapear(...), $filas);
    }

    public function buscarPorId(int $id): ?Area
    {
        $consulta = $this->pdo->prepare('SELECT id, nombre, ubicacion FROM areas WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila === false ? null : $this->mapear($fila);
    }

    public function existeNombre(string $nombre, ?int $exceptoId): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM areas WHERE LOWER(nombre) = LOWER(:nombre) AND id != :exceptoId
            ) AS existe',
        );
        $consulta->execute(['nombre' => $nombre, 'exceptoId' => $exceptoId ?? 0]);

        return (bool) $consulta->fetchColumn();
    }

    public function estaEnUso(int $id): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT EXISTS(SELECT 1 FROM pacientes WHERE area_id = :id1)
                OR EXISTS(SELECT 1 FROM usuarios WHERE area_id = :id2)
                OR EXISTS(SELECT 1 FROM eventos WHERE area_id = :id3) AS en_uso',
        );
        $consulta->execute(['id1' => $id, 'id2' => $id, 'id3' => $id]);

        return (bool) $consulta->fetchColumn();
    }

    public function guardar(Area $area): Area
    {
        return $area->id() === null ? $this->insertar($area) : $this->actualizar($area);
    }

    public function eliminar(int $id): void
    {
        $consulta = $this->pdo->prepare('DELETE FROM areas WHERE id = :id');
        $consulta->execute(['id' => $id]);
    }

    private function insertar(Area $area): Area
    {
        $consulta = $this->pdo->prepare('INSERT INTO areas (nombre, ubicacion) VALUES (:nombre, :ubicacion)');
        $consulta->execute(['nombre' => $area->nombre(), 'ubicacion' => $area->ubicacion()]);
        $area->asignarId((int) $this->pdo->lastInsertId());

        return $area;
    }

    private function actualizar(Area $area): Area
    {
        $consulta = $this->pdo->prepare('UPDATE areas SET nombre = :nombre, ubicacion = :ubicacion WHERE id = :id');
        $consulta->execute(['nombre' => $area->nombre(), 'ubicacion' => $area->ubicacion(), 'id' => $area->id()]);

        return $area;
    }

    private function mapear(array $fila): Area
    {
        return new Area((int) $fila['id'], $fila['nombre'], $fila['ubicacion']);
    }
}
