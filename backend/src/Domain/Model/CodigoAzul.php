<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

use CodigoAzul\Domain\Service\TiempoTranscurrido;

final class CodigoAzul extends Evento
{
    private readonly ?int $tiempoRespuesta;

    public function __construct(
        ?int $id,
        string $fechaHora,
        int $areaId,
        string $personalInterviniente,
        string $observaciones,
        private int $pacienteId,
        private string $horaLlegadaEquipo,
        private string $intervencionRealizada,
        private ResultadoCodigoAzul $resultado,
        private ?int $llamadoOrigenId,
    ) {
        parent::__construct($id, $fechaHora, $areaId, $personalInterviniente, $observaciones);
        // tiempoRespuesta es un dato derivado (fechaHora -> horaLlegadaEquipo),
        // no un valor de entrada independiente: se calcula, nunca se recibe.
        $this->tiempoRespuesta = TiempoTranscurrido::enMinutos($fechaHora, $horaLlegadaEquipo);
    }

    public function pacienteId(): int
    {
        return $this->pacienteId;
    }

    public function horaLlegadaEquipo(): string
    {
        return $this->horaLlegadaEquipo;
    }

    public function tiempoRespuesta(): ?int
    {
        return $this->tiempoRespuesta;
    }

    public function intervencionRealizada(): string
    {
        return $this->intervencionRealizada;
    }

    public function resultado(): ResultadoCodigoAzul
    {
        return $this->resultado;
    }

    public function llamadoOrigenId(): ?int
    {
        return $this->llamadoOrigenId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fechaHora' => $this->fechaHora,
            'areaId' => $this->areaId,
            'personalInterviniente' => $this->personalInterviniente,
            'observaciones' => $this->observaciones,
            'pacienteId' => $this->pacienteId,
            'horaLlegadaEquipo' => $this->horaLlegadaEquipo,
            'tiempoRespuesta' => $this->tiempoRespuesta,
            'intervencionRealizada' => $this->intervencionRealizada,
            'resultado' => $this->resultado->value,
            'llamadoOrigenId' => $this->llamadoOrigenId,
        ];
    }
}
