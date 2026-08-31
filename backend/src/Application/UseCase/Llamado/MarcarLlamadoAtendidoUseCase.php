<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Llamado;

use CodigoAzul\Domain\Exception\NotFoundException;
use CodigoAzul\Domain\Model\Llamado;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;
use CodigoAzul\Domain\Service\TiempoTranscurrido;

final class MarcarLlamadoAtendidoUseCase
{
    public function __construct(private readonly LlamadoRepositoryInterface $llamados)
    {
    }

    /**
     * @param string $ahora Hora actual en formato "Y-m-d\TH:i", provista por
     *   el controlador (nunca calculada aqui) para que el caso de uso sea
     *   puro y testeable sin depender del reloj del sistema.
     */
    public function __invoke(int $id, string $ahora): Llamado
    {
        $llamado = $this->llamados->buscarPorId($id);

        if ($llamado === null) {
            throw new NotFoundException('Llamado no encontrado.');
        }

        $llamado->marcarAtendido(TiempoTranscurrido::enMinutos($llamado->fechaHora(), $ahora) ?? 0);

        return $this->llamados->guardar($llamado);
    }
}
