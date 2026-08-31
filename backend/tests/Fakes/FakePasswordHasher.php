<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Fakes;

use CodigoAzul\Domain\Service\PasswordHasherInterface;

/** No usa un algoritmo real: alcanza con que sea determinista para los tests. */
final class FakePasswordHasher implements PasswordHasherInterface
{
    public function hash(string $claveEnTextoPlano): string
    {
        return 'hash:' . $claveEnTextoPlano;
    }

    public function verificar(string $claveEnTextoPlano, string $hashAlmacenado): bool
    {
        return $this->hash($claveEnTextoPlano) === $hashAlmacenado;
    }
}
