<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Area\EliminarAreaUseCase;
use CodigoAzul\Domain\Exception\ConflictException;
use CodigoAzul\Domain\Model\Area;
use CodigoAzul\Tests\Fakes\FakeAreaRepository;
use PHPUnit\Framework\TestCase;

final class EliminarAreaUseCaseTest extends TestCase
{
    public function test_elimina_un_area_libre(): void
    {
        $repositorio = new FakeAreaRepository();
        $area = $repositorio->guardar(new Area(null, 'Guardia', 'Planta baja'));
        $eliminar = new EliminarAreaUseCase($repositorio);

        $eliminar($area->id());

        self::assertNull($repositorio->buscarPorId($area->id()));
    }

    /**
     * Regresion del bug encontrado en el prototipo: la eliminacion de un
     * área solo verificaba pacientes/usuarios, no llamados/códigos azul,
     * dejando eventos "huérfanos" (con un area_id que ya no existe).
     */
    public function test_no_elimina_un_area_en_uso(): void
    {
        $repositorio = new FakeAreaRepository();
        $area = $repositorio->guardar(new Area(null, 'Guardia', 'Planta baja'));
        $repositorio->marcarEnUso($area->id());
        $eliminar = new EliminarAreaUseCase($repositorio);

        $this->expectException(ConflictException::class);

        $eliminar($area->id());
    }
}
