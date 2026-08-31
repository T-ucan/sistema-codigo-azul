<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\ResultadoCodigoAzul;
use CodigoAzul\Domain\Repository\CodigoAzulRepositoryInterface;
use CodigoAzul\Domain\Model\CodigoAzul;

final class PdoCodigoAzulRepository implements CodigoAzulRepositoryInterface
{
    private const SELECT_BASE = 'SELECT e.id, e.fecha_hora, e.area_id, e.personal_interviniente, e.observaciones,
            c.paciente_id, c.hora_llegada_equipo, c.intervencion_realizada, c.resultado, c.llamado_origen_id
        FROM eventos e JOIN codigos_azul c ON c.id = e.id';

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
        $consulta = $this->pdo->prepare(self::SELECT_BASE . ' WHERE e.area_id = :areaId ORDER BY e.fecha_hora DESC');
        $consulta->execute(['areaId' => $areaId]);

        return array_map($this->mapear(...), $consulta->fetchAll());
    }

    public function guardar(CodigoAzul $codigoAzul): CodigoAzul
    {
        $this->pdo->beginTransaction();

        $insertarEvento = $this->pdo->prepare(
            "INSERT INTO eventos (fecha_hora, area_id, personal_interviniente, observaciones, tipo_evento)
             VALUES (:fechaHora, :areaId, :personalInterviniente, :observaciones, 'CODIGO_AZUL')",
        );
        $insertarEvento->execute([
            'fechaHora' => FormatoFechaHora::aMysql($codigoAzul->fechaHora()),
            'areaId' => $codigoAzul->areaId(),
            'personalInterviniente' => $codigoAzul->personalInterviniente(),
            'observaciones' => $codigoAzul->observaciones(),
        ]);
        $codigoAzul->asignarId((int) $this->pdo->lastInsertId());

        $insertarFicha = $this->pdo->prepare(
            'INSERT INTO codigos_azul
                (id, paciente_id, hora_llegada_equipo, intervencion_realizada, resultado, llamado_origen_id)
             VALUES (:id, :pacienteId, :horaLlegadaEquipo, :intervencionRealizada, :resultado, :llamadoOrigenId)',
        );
        $insertarFicha->execute([
            'id' => $codigoAzul->id(),
            'pacienteId' => $codigoAzul->pacienteId(),
            'horaLlegadaEquipo' => FormatoFechaHora::aMysql($codigoAzul->horaLlegadaEquipo()),
            'intervencionRealizada' => $codigoAzul->intervencionRealizada(),
            'resultado' => $codigoAzul->resultado()->value,
            'llamadoOrigenId' => $codigoAzul->llamadoOrigenId(),
        ]);

        $this->pdo->commit();

        return $codigoAzul;
    }

    private function mapear(array $fila): CodigoAzul
    {
        return new CodigoAzul(
            id: (int) $fila['id'],
            fechaHora: FormatoFechaHora::aApp($fila['fecha_hora']),
            areaId: (int) $fila['area_id'],
            personalInterviniente: $fila['personal_interviniente'],
            observaciones: $fila['observaciones'] ?? '',
            pacienteId: (int) $fila['paciente_id'],
            horaLlegadaEquipo: FormatoFechaHora::aApp($fila['hora_llegada_equipo']),
            intervencionRealizada: $fila['intervencion_realizada'],
            resultado: ResultadoCodigoAzul::from($fila['resultado']),
            llamadoOrigenId: $fila['llamado_origen_id'] !== null ? (int) $fila['llamado_origen_id'] : null,
        );
    }
}
