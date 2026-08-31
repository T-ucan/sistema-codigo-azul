<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Usuario\EliminarUsuarioUseCase;
use CodigoAzul\Domain\Exception\ConflictException;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Domain\Model\Usuario;
use CodigoAzul\Tests\Fakes\FakeUsuarioRepository;
use PHPUnit\Framework\TestCase;

final class EliminarUsuarioUseCaseTest extends TestCase
{
    public function test_no_permite_eliminar_el_propio_usuario(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $usuario = $repositorio->guardar(Usuario::crear(null, 'Admin', 'admin', 'hash', Rol::ADMINISTRADOR, null));
        $eliminar = new EliminarUsuarioUseCase($repositorio);

        $this->expectException(ConflictException::class);

        $eliminar($usuario->id(), $usuario->id());
    }

    public function test_permite_eliminar_a_otro_usuario(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $actual = $repositorio->guardar(Usuario::crear(null, 'Admin', 'admin', 'hash', Rol::ADMINISTRADOR, null));
        $otro = $repositorio->guardar(Usuario::crear(null, 'Otro', 'otro', 'hash', Rol::ADMINISTRADOR, null));
        $eliminar = new EliminarUsuarioUseCase($repositorio);

        $eliminar($otro->id(), $actual->id());

        self::assertNull($repositorio->buscarPorId($otro->id()));
    }
}
