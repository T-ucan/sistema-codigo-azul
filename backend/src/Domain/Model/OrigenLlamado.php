<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

enum OrigenLlamado: string
{
    case CAMA = 'Cama';
    case BANO = 'Baño';
}
