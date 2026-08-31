<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

enum TipoLlamado: string
{
    case NORMAL = 'Normal';
    case EMERGENCIA = 'Emergencia';
}
