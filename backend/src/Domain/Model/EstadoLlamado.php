<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

enum EstadoLlamado: string
{
    case PENDIENTE = 'Pendiente';
    case ATENDIDO = 'Atendido';
}
