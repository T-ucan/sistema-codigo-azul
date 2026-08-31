<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Model\Reporte;
use CodigoAzul\Domain\Repository\ReporteRepositoryInterface;

final class FakeReporteRepository implements ReporteRepositoryInterface
{
    /** @var Reporte[] */
    public array $registrados = [];

    public function registrar(Reporte $reporte): Reporte
    {
        $this->registrados[] = $reporte;

        return $reporte;
    }
}
