<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

final class Llamado extends Evento
{
    public function __construct(
        ?int $id,
        string $fechaHora,
        int $areaId,
        string $personalInterviniente,
        string $observaciones,
        private TipoLlamado $tipo,
        private OrigenLlamado $origen,
        private ?int $pacienteId,
        private EstadoLlamado $estado = EstadoLlamado::PENDIENTE,
        private ?int $tiempoRespuesta = null,
    ) {
        parent::__construct($id, $fechaHora, $areaId, $personalInterviniente, $observaciones);
    }

    public function tipo(): TipoLlamado
    {
        return $this->tipo;
    }

    public function origen(): OrigenLlamado
    {
        return $this->origen;
    }

    public function pacienteId(): ?int
    {
        return $this->pacienteId;
    }

    public function estado(): EstadoLlamado
    {
        return $this->estado;
    }

    public function tiempoRespuesta(): ?int
    {
        return $this->tiempoRespuesta;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === EstadoLlamado::PENDIENTE;
    }

    public function marcarAtendido(int $tiempoRespuestaMinutos): void
    {
        $this->estado = EstadoLlamado::ATENDIDO;
        $this->tiempoRespuesta = $tiempoRespuestaMinutos;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fechaHora' => $this->fechaHora,
            'areaId' => $this->areaId,
            'personalInterviniente' => $this->personalInterviniente,
            'observaciones' => $this->observaciones,
            'tipo' => $this->tipo->value,
            'origen' => $this->origen->value,
            'pacienteId' => $this->pacienteId,
            'estado' => $this->estado->value,
            'tiempoRespuesta' => $this->tiempoRespuesta,
        ];
    }
}
