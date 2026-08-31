<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\Validation;

use CodigoAzul\Application\Validation\AreaValidator;
use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Tests\Fakes\FakeAreaRepository;
use PHPUnit\Framework\TestCase;

final class AreaValidatorTest extends TestCase
{
    public function test_nombre_muy_corto_es_invalido(): void
    {
        $validador = new AreaValidator(new FakeAreaRepository());

        self::assertSame(
            ['El nombre del área debe tener al menos 2 caracteres.'],
            $validador->validar('A', 'Planta baja', null),
        );
    }

    public function test_ubicacion_vacia_es_invalida(): void
    {
        $validador = new AreaValidator(new FakeAreaRepository());

        self::assertSame(['La ubicación es obligatoria.'], $validador->validar('Guardia', '', null));
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        $repositorio = new FakeAreaRepository();
        $repositorio->guardar(new Area(null, 'Guardia', 'Planta baja'));
        $validador = new AreaValidator($repositorio);

        self::assertSame(['Ya existe un área con ese nombre.'], $validador->validar('guardia', 'Otro piso', null));
    }

    public function test_permite_conservar_el_propio_nombre_al_editar(): void
    {
        $repositorio = new FakeAreaRepository();
        $area = $repositorio->guardar(new Area(null, 'Guardia', 'Planta baja'));
        $validador = new AreaValidator($repositorio);

        self::assertSame([], $validador->validar('Guardia', 'Planta baja actualizada', $area->id()));
    }

    public function test_datos_validos_no_generan_errores(): void
    {
        $validador = new AreaValidator(new FakeAreaRepository());

        self::assertSame([], $validador->validar('Guardia', 'Planta baja', null));
    }
}
