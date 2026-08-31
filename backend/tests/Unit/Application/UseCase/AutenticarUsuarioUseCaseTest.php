<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Auth\AutenticarUsuarioUseCase;
use CodigoAzul\Domain\Exception\AuthenticationException;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Tests\Fakes\FakePasswordHasher;
use CodigoAzul\Tests\Fakes\FakeUsuarioRepository;
use PHPUnit\Framework\TestCase;

final class AutenticarUsuarioUseCaseTest extends TestCase
{
    private FakeUsuarioRepository $usuarios;
    private FakePasswordHasher $hasher;
    private AutenticarUsuarioUseCase $autenticar;

    protected function setUp(): void
    {
        $this->usuarios = new FakeUsuarioRepository();
        $this->hasher = new FakePasswordHasher();
        $this->autenticar = new AutenticarUsuarioUseCase($this->usuarios, $this->hasher);

        $this->usuarios->guardar(Usuario::crear(null, 'Admin', 'admin', $this->hasher->hash('admin123'), Rol::ADMINISTRADOR, null));
    }

    public function test_autentica_con_credenciales_correctas(): void
    {
        $usuario = ($this->autenticar)('admin', 'admin123');

        self::assertSame('admin', $usuario->usuario());
    }

    public function test_rechaza_contrasena_incorrecta(): void
    {
        $this->expectException(AuthenticationException::class);

        ($this->autenticar)('admin', 'incorrecta');
    }

    public function test_rechaza_usuario_inexistente(): void
    {
        $this->expectException(AuthenticationException::class);

        ($this->autenticar)('no-existe', 'admin123');
    }

    public function test_usuario_de_acceso_no_distingue_mayusculas(): void
    {
        $usuario = ($this->autenticar)('ADMIN', 'admin123');

        self::assertSame('admin', $usuario->usuario());
    }
}
