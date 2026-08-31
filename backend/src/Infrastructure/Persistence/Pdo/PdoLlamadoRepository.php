<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;

/**
 * Mapea la especializacion Evento -> Llamado (tabla base + tabla de
 * subtipo, ver database/codigo_azul_schema.sql): siempre se lee via JOIN
 * y se escribe transaccionalmente en eventos + llamados.
 */
final class PdoLlamadoRepository implements LlamadoRepositoryInterface
{
    private const SELECT_BASE = 'SELECT e.id, e.fecha_hora, e.area_id, e.personal_interviniente, e.observaciones,
            l.tipo, l.origen, l.paciente_id, l.estado, l.tiempo_respuesta
        FROM eventos e JOIN llamados l ON l.id = e.id';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function listar(): array
    {
        $filas = $this->pdo->query(self::SELECT_BASE . ' ORDER BY e.fecha_hora DESC')->fetchAll();

        return array_map($this->mapear(...), $filas);
    }

    public function listarPorArea(int $areaId): array
    {
        return $this->listarConCondicion('e.area_id = :areaId', ['areaId' => $areaId]);
    }

    public function listarPendientesPorArea(int $areaId): array
    {
        return $this->listarConCondicion(
            'e.area_id = :areaId AND l.estado = :estado',
            ['areaId' => $areaId, 'estado' => EstadoLlamado::PENDIENTE->value],
        );
    }

    public function buscarPorId(int $id): ?Llamado
    {
        $consulta = $this->pdo->prepare(self::SELECT_BASE . ' WHERE e.id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila === false ? null : $this->mapear($fila);
    }

    public function guardar(Llamado $llamado): Llamado
    {
        return $llamado->id() === null ? $this->insertar($llamado) : $this->actualizar($llamado);
    }

    /** @param array<string, scalar> $parametros */
    private function listarConCondicion(string $condicion, array $parametros): array
    {
        $consulta = $this->pdo->prepare(self::SELECT_BASE . " WHERE {$condicion} ORDER BY e.fecha_hora DESC");
        $consulta->execute($parametros);

        return array_map($this->mapear(...), $consulta->fetchAll());
    }

    private function insertar(Llamado $llamado): Llamado
    {
        $this->pdo->beginTransaction();

        $insertarEvento = $this->pdo->prepare(
            "INSERT INTO eventos (fecha_hora, area_id, personal_interviniente, observaciones, tipo_evento)
             VALUES (:fechaHora, :areaId, :personalInterviniente, :observaciones, 'LLAMADO')",
        );
        $insertarEvento->execute([
            'fechaHora' => FormatoFechaHora::aMysql($llamado->fechaHora()),
            'areaId' => $llamado->areaId(),
            'personalInterviniente' => $llamado->personalInterviniente(),
            'observaciones' => $llamado->observaciones(),
        ]);
        $llamado->asignarId((int) $this->pdo->lastInsertId());

        $insertarLlamado = $this->pdo->prepare(
            'INSERT INTO llamados (id, tipo, origen, paciente_id, estado, tiempo_respuesta)
             VALUES (:id, :tipo, :origen, :pacienteId, :estado, :tiempoRespuesta)',
        );
        $insertarLlamado->execute($this->parametrosLlamado($llamado));

        $this->pdo->commit();

        return $llamado;
    }

    private function actualizar(Llamado $llamado): Llamado
    {
        $consulta = $this->pdo->prepare(
            'UPDATE llamados SET estado = :estado, tiempo_respuesta = :tiempoRespuesta,
                paciente_id = :pacienteId WHERE id = :id',
        );
        $consulta->execute([
            'estado' => $llamado->estado()->value,
            'tiempoRespuesta' => $llamado->tiempoRespuesta(),
            'pacienteId' => $llamado->pacienteId(),
            'id' => $llamado->id(),
        ]);

        return $llamado;
    }

    /** @return array<string, scalar|null> */
    private function parametrosLlamado(Llamado $llamado): array
    {
        return [
            'id' => $llamado->id(),
            'tipo' => $llamado->tipo()->value,
            'origen' => $llamado->origen()->value,
            'pacienteId' => $llamado->pacienteId(),
            'estado' => $llamado->estado()->value,
            'tiempoRespuesta' => $llamado->tiempoRespuesta(),
        ];
    }

    private function mapear(array $fila): Llamado
    {
        return new Llamado(
            id: (int) $fila['id'],
            fechaHora: FormatoFechaHora::aApp($fila['fecha_hora']),
            areaId: (int) $fila['area_id'],
            personalInterviniente: $fila['personal_interviniente'],
            observaciones: $fila['observaciones'] ?? '',
            tipo: TipoLlamado::from($fila['tipo']),
            origen: OrigenLlamado::from($fila['origen']),
            pacienteId: $fila['paciente_id'] !== null ? (int) $fila['paciente_id'] : null,
            estado: EstadoLlamado::from($fila['estado']),
            tiempoRespuesta: $fila['tiempo_respuesta'] !== null ? (int) $fila['tiempo_respuesta'] : null,
        );
    }
}
