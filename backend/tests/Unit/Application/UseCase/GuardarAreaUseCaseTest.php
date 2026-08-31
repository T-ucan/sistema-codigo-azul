<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Area\GuardarAreaUseCase;
use CodigoAzul\Application\Validation\AreaValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Tests\Fakes\FakeAreaRepository;
use PHPUnit\Framework\TestCase;

final class GuardarAreaUseCaseTest extends TestCase
{
    public function test_crea_un_area_nueva_con_id_autoasignado(): void
    {
        $repositorio = new FakeAreaRepository();
        $guardar = new GuardarAreaUseCase($repositorio, new AreaValidator($repositorio));

        $area = $guardar('Guardia', 'Planta baja', null);

        self::assertNotNull($area->id());
        self::assertCount(1, $repositorio->listar());
    }

    public function test_recorta_espacios_en_nombre_y_ubicacion(): void
    {
        $repositorio = new FakeAreaRepository();
        $guardar = new GuardarAreaUseCase($repositorio, new AreaValidator($repositorio));

        $area = $guardar('  Guardia  ', '  Planta baja  ', null);

        self::assertSame('Guardia', $area->nombre());
        self::assertSame('Planta baja', $area->ubicacion());
    }

    public function test_no_guarda_si_los_datos_son_invalidos(): void
    {
        $repositorio = new FakeAreaRepository();
        $guardar = new GuardarAreaUseCase($repositorio, new AreaValidator($repositorio));

        try {
            $guardar('', '', null);
            self::fail('Debia lanzar ValidationException.');
        } catch (ValidationException $error) {
            self::assertNotEmpty($error->errores());
        }

        self::assertCount(0, $repositorio->listar());
    }
}
