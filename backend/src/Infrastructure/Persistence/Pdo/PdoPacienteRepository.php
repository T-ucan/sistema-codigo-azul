<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\Paciente;
use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;

final class PdoPacienteRepository implements PacienteRepositoryInterface
{
    private const COLUMNAS = 'id, nombre, dni, fecha_nacimiento, datos_medicos, area_id';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function listar(): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . ' FROM pacientes ORDER BY nombre';
        $filas = $this->pdo->query($sql)->fetchAll();

        return array_map($this->mapear(...), $filas);
    }

    public function listarPorArea(int $areaId): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM pacientes WHERE area_id = :areaId ORDER BY nombre',
        );
        $consulta->execute(['areaId' => $areaId]);

        return array_map($this->mapear(...), $consulta->fetchAll());
    }

    public function buscar(string $texto): array
    {
        if ($texto === '') {
            return $this->listar();
        }

        $consulta = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM pacientes
             WHERE LOWER(nombre) LIKE :patron OR dni LIKE :patron
             ORDER BY nombre',
        );
        $consulta->execute(['patron' => '%' . mb_strtolower($texto) . '%']);

        return array_map($this->mapear(...), $consulta->fetchAll());
    }

    public function buscarPorId(int $id): ?Paciente
    {
        $consulta = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM pacientes WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila === false ? null : $this->mapear($fila);
    }

    public function existeDni(string $dni, ?int $exceptoId): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT EXISTS(SELECT 1 FROM pacientes WHERE dni = :dni AND id != :exceptoId) AS existe',
        );
        $consulta->execute(['dni' => $dni, 'exceptoId' => $exceptoId ?? 0]);

        return (bool) $consulta->fetchColumn();
    }

    public function guardar(Paciente $paciente): Paciente
    {
        return $paciente->id() === null ? $this->insertar($paciente) : $this->actualizar($paciente);
    }

    private function insertar(Paciente $paciente): Paciente
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO pacientes (nombre, dni, fecha_nacimiento, datos_medicos, area_id)
             VALUES (:nombre, :dni, :fechaNacimiento, :datosMedicos, :areaId)',
        );
        $consulta->execute($this->parametros($paciente));
        $paciente->asignarId((int) $this->pdo->lastInsertId());

        return $paciente;
    }

    private function actualizar(Paciente $paciente): Paciente
    {
        $consulta = $this->pdo->prepare(
            'UPDATE pacientes SET nombre = :nombre, dni = :dni, fecha_nacimiento = :fechaNacimiento,
                datos_medicos = :datosMedicos, area_id = :areaId WHERE id = :id',
        );
        $consulta->execute([...$this->parametros($paciente), 'id' => $paciente->id()]);

        return $paciente;
    }

    /** @return array<string, scalar|null> */
    private function parametros(Paciente $paciente): array
    {
        return [
            'nombre' => $paciente->nombre(),
            'dni' => $paciente->dni(),
            'fechaNacimiento' => $paciente->fechaNacimiento(),
            'datosMedicos' => $paciente->datosMedicos(),
            'areaId' => $paciente->areaId(),
        ];
    }

    private function mapear(array $fila): Paciente
    {
        return new Paciente(
            (int) $fila['id'],
            $fila['nombre'],
            $fila['dni'],
            $fila['fecha_nacimiento'],
            $fila['datos_medicos'] ?? '',
            (int) $fila['area_id'],
        );
    }
}
