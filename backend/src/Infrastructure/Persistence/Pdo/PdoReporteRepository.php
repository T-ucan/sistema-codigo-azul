<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Persistence\Pdo;

use CodigoAzul\Domain\Model\Reporte;
use CodigoAzul\Domain\Repository\ReporteRepositoryInterface;

final class PdoReporteRepository implements ReporteRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function registrar(Reporte $reporte): Reporte
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO reportes (fecha_generacion, formato, filtros_aplicados, usuario_id)
             VALUES (:fechaGeneracion, :formato, :filtrosAplicados, :usuarioId)',
        );
        $consulta->execute([
            'fechaGeneracion' => str_replace('T', ' ', $reporte->fechaGeneracion()),
            'formato' => $reporte->formato(),
            'filtrosAplicados' => $reporte->filtrosAplicados(),
            'usuarioId' => $reporte->usuarioId(),
        ]);

        return $reporte;
    }
}
