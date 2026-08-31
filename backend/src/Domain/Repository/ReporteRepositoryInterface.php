<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Repository;

use CodigoAzul\Domain\Model\Reporte;

interface ReporteRepositoryInterface
{
    public function registrar(Reporte $reporte): Reporte;
}
