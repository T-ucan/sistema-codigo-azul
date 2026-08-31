<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Exception;

/** El usuario autenticado no tiene permiso para la accion (rol incorrecto). */
final class ForbiddenException extends \RuntimeException
{
}
