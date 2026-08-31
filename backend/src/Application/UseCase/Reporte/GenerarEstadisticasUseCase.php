<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Reporte;

use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;

final class GenerarEstadisticasUseCase
{
    public function __construct(
        private readonly LlamadoRepositoryInterface $llamados,
        private readonly AreaRepositoryInterface $areas,
    ) {
    }

    public function __invoke(FiltrosReporte $filtros): EstadisticasReporte
    {
        $llamados = array_values(array_filter($this->llamados->listar(), $filtros->coincideCon(...)));
        $tiempos = $this->tiemposDeRespuesta($llamados);

        return new EstadisticasReporte(
            llamados: $llamados,
            total: count($llamados),
            atendidos: count(array_filter($llamados, self::esAtendido(...))),
            pendientes: count(array_filter($llamados, self::esPendiente(...))),
            tiempoPromedioRespuesta: $this->promedio($tiempos),
            porArea: $this->agruparPor($llamados, fn (Llamado $l): string => $this->nombreArea($l->areaId())),
            porTipo: $this->agruparPor($llamados, static fn (Llamado $l): string => $l->tipo()->value),
            porDia: $this->porDiaOrdenado($llamados),
        );
    }

    private static function esAtendido(Llamado $llamado): bool
    {
        return $llamado->estado() === EstadoLlamado::ATENDIDO;
    }

    private static function esPendiente(Llamado $llamado): bool
    {
        return $llamado->estaPendiente();
    }

    /** @param Llamado[] $llamados @return int[] */
    private function tiemposDeRespuesta(array $llamados): array
    {
        $tiempos = array_map(static fn (Llamado $l): ?int => $l->tiempoRespuesta(), $llamados);

        return array_values(array_filter($tiempos, static fn (?int $t): bool => $t !== null));
    }

    private function nombreArea(int $areaId): string
    {
        return $this->areas->buscarPorId($areaId)?->nombre() ?? 'Sin área';
    }

    /**
     * @param Llamado[] $llamados
     * @param callable(Llamado): string $obtenerClave
     * @return array<int, array{etiqueta: string, valor: int}>
     */
    private function agruparPor(array $llamados, callable $obtenerClave): array
    {
        $conteos = [];
        foreach ($llamados as $llamado) {
            $clave = $obtenerClave($llamado);
            $conteos[$clave] = ($conteos[$clave] ?? 0) + 1;
        }

        return array_map(
            static fn (string $etiqueta, int $valor): array => ['etiqueta' => $etiqueta, 'valor' => $valor],
            array_keys($conteos),
            array_values($conteos),
        );
    }

    /**
     * @param Llamado[] $llamados
     * @return array<int, array{etiqueta: string, valor: int}>
     */
    private function porDiaOrdenado(array $llamados): array
    {
        $porDia = $this->agruparPor($llamados, static fn (Llamado $l): string => substr($l->fechaHora(), 0, 10));
        usort($porDia, static fn (array $a, array $b): int => $a['etiqueta'] <=> $b['etiqueta']);

        return $porDia;
    }

    /** @param int[] $tiempos */
    private function promedio(array $tiempos): float
    {
        if ($tiempos === []) {
            return 0.0;
        }

        return round(array_sum($tiempos) / count($tiempos), 1);
    }
}
