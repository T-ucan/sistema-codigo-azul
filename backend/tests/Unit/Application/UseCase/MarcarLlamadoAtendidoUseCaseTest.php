<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Llamado\MarcarLlamadoAtendidoUseCase;
use CodigoAzul\Domain\Exception\NotFoundException;
use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Model\OrigenLlamado;
use CodigoAzul\Domain\Model\TipoLlamado;
use CodigoAzul\Tests\Fakes\FakeLlamadoRepository;
use PHPUnit\Framework\TestCase;

final class MarcarLlamadoAtendidoUseCaseTest extends TestCase
{
    public function test_marca_atendido_y_calcula_el_tiempo_de_respuesta(): void
    {
        $repositorio = new FakeLlamadoRepository();
        $llamado = $repositorio->guardar(new Llamado(
            id: null,
            fechaHora: '2026-01-01T10:00',
            areaId: 1,
            personalInterviniente: 'Enf. Torres',
            observaciones: '',
            tipo: TipoLlamado::NORMAL,
            origen: OrigenLlamado::CAMA,
            pacienteId: null,
        ));
        $marcarAtendido = new MarcarLlamadoAtendidoUseCase($repositorio);

        $actualizado = $marcarAtendido($llamado->id(), '2026-01-01T10:07');

        self::assertSame(EstadoLlamado::ATENDIDO, $actualizado->estado());
        self::assertSame(7, $actualizado->tiempoRespuesta());
    }

    /**
     * Regresion directa del bug de huso horario: este caso de uso NUNCA
     * debe leer el reloj del sistema (new DateTime()) para calcular "ahora"
     * -eso fue lo que mezclo hora local con UTC en el prototipo original y
     * genero un tiempo de respuesta de 180 minutos para un llamado atendido
     * casi al instante-. "Ahora" siempre lo decide quien llama.
     */
    public function test_el_ahora_lo_decide_quien_llama_no_el_reloj_del_sistema(): void
    {
        $repositorio = new FakeLlamadoRepository();
        $llamado = $repositorio->guardar(new Llamado(
            id: null,
            fechaHora: '2026-01-01T10:00',
            areaId: 1,
            personalInterviniente: 'Enf. Torres',
            observaciones: '',
            tipo: TipoLlamado::NORMAL,
            origen: OrigenLlamado::CAMA,
            pacienteId: null,
        ));
        $marcarAtendido = new MarcarLlamadoAtendidoUseCase($repositorio);

        $actualizado = $marcarAtendido($llamado->id(), '2026-01-01T10:00');

        self::assertSame(0, $actualizado->tiempoRespuesta());
    }

    public function test_lanza_notfound_si_el_llamado_no_existe(): void
    {
        $marcarAtendido = new MarcarLlamadoAtendidoUseCase(new FakeLlamadoRepository());

        $this->expectException(NotFoundException::class);

        $marcarAtendido(999, '2026-01-01T10:00');
    }
}
