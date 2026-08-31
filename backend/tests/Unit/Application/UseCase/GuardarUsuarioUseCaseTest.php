<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Usuario\GuardarUsuarioUseCase;
use CodigoAzul\Application\Validation\UsuarioValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\Rol;
use CodigoAzul\Tests\Fakes\FakePasswordHasher;
use CodigoAzul\Tests\Fakes\FakeUsuarioRepository;
use PHPUnit\Framework\TestCase;

final class GuardarUsuarioUseCaseTest extends TestCase
{
    private function crearCasoDeUso(FakeUsuarioRepository $repositorio): GuardarUsuarioUseCase
    {
        return new GuardarUsuarioUseCase($repositorio, new UsuarioValidator($repositorio), new FakePasswordHasher());
    }

    public function test_administrador_nunca_queda_con_area_aunque_se_envie_una(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $guardar = $this->crearCasoDeUso($repositorio);

        $usuario = $guardar('Admin', 'admin', 'ADMINISTRADOR', '5', 'admin123', null);

        self::assertNull($usuario->areaId());
    }

    public function test_encargado_sin_area_es_invalido(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $guardar = $this->crearCasoDeUso($repositorio);

        $this->expectException(ValidationException::class);

        $guardar('Encargado', 'encargado', 'ENCARGADO', null, '1234', null);
    }

    public function test_encargado_con_area_guarda_el_area(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $guardar = $this->crearCasoDeUso($repositorio);

        $usuario = $guardar('Encargado', 'encargado', 'ENCARGADO', '3', '1234', null);

        self::assertSame(3, $usuario->areaId());
        self::assertSame(Rol::ENCARGADO, $usuario->rol());
    }

    public function test_al_editar_sin_clave_nueva_conserva_el_hash_anterior(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $guardar = $this->crearCasoDeUso($repositorio);
        $original = $guardar('Admin', 'admin', 'ADMINISTRADOR', null, 'admin123', null);

        $editado = $guardar('Admin', 'admin', 'ADMINISTRADOR', null, '', $original->id());

        self::assertSame($original->claveHash(), $editado->claveHash());
    }

    public function test_contrasena_nueva_muy_corta_es_invalida(): void
    {
        $repositorio = new FakeUsuarioRepository();
        $guardar = $this->crearCasoDeUso($repositorio);

        $this->expectException(ValidationException::class);

        $guardar('Admin', 'admin', 'ADMINISTRADOR', null, '12', null);
    }
}
