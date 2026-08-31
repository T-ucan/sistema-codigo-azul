<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Security;

use CodigoAzul\Domain\Service\PasswordHasherInterface;

/**
 * Adaptador real de hashing: usa password_hash/password_verify de PHP
 * (BCrypt/Argon2 segun PASSWORD_DEFAULT), nunca un hash propio. Reemplaza
 * al hash djb2 no criptografico que usaba el prototipo de front-end.
 */
final class NativePasswordHasher implements PasswordHasherInterface
{
    public function hash(string $claveEnTextoPlano): string
    {
        return password_hash($claveEnTextoPlano, PASSWORD_DEFAULT);
    }

    public function verificar(string $claveEnTextoPlano, string $hashAlmacenado): bool
    {
        return password_verify($claveEnTextoPlano, $hashAlmacenado);
    }
}
