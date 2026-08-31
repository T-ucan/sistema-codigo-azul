<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Exception;

/** Regla de negocio que impide la operacion (p. ej. area en uso). */
final class ConflictException extends \RuntimeException
{
}
