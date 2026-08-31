<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

enum Rol: string
{
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case ENCARGADO = 'ENCARGADO';
}
