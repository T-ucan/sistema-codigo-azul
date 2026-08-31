<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Service;

/**
 * Puerto de dominio para el hashing de contrasenas. La implementacion real
 * (password_hash/password_verify de PHP, con Argon2/BCrypt) vive en
 * Infrastructure\Security, para que el dominio y los casos de uso nunca
 * dependan de un algoritmo concreto.
 */
interface PasswordHasherInterface
{
    public function hash(string $claveEnTextoPlano): string;

    public function verificar(string $claveEnTextoPlano, string $hashAlmacenado): bool;
}
