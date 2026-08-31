<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

enum ResultadoCodigoAzul: string
{
    case RCE = 'RCE';
    case TRASLADO_A_UTI = 'Traslado a UTI';
    case FALLECIMIENTO = 'Fallecimiento';
    case OTRO = 'Otro';
}
