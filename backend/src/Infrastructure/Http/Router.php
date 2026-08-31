<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http;

final class Router
{
    /** @var array<int, array{metodo: string, patron: string, manejador: callable}> */
    private array $rutas = [];

    public function get(string $patron, callable $manejador): void
    {
        $this->agregar('GET', $patron, $manejador);
    }

    public function post(string $patron, callable $manejador): void
    {
        $this->agregar('POST', $patron, $manejador);
    }

    public function put(string $patron, callable $manejador): void
    {
        $this->agregar('PUT', $patron, $manejador);
    }

    public function delete(string $patron, callable $manejador): void
    {
        $this->agregar('DELETE', $patron, $manejador);
    }

    private function agregar(string $metodo, string $patron, callable $manejador): void
    {
        $this->rutas[] = ['metodo' => $metodo, 'patron' => $patron, 'manejador' => $manejador];
    }

    /** @return array{manejador: callable, parametros: array<string, string>}|null */
    public function resolver(string $metodo, string $ruta): ?array
    {
        foreach ($this->rutas as $definicion) {
            $parametros = $this->coincide($definicion, $metodo, $ruta);

            if ($parametros !== null) {
                return ['manejador' => $definicion['manejador'], 'parametros' => $parametros];
            }
        }

        return null;
    }

    /**
     * @param array{metodo: string, patron: string, manejador: callable} $definicion
     * @return array<string, string>|null
     */
    private function coincide(array $definicion, string $metodo, string $ruta): ?array
    {
        if ($definicion['metodo'] !== $metodo) {
            return null;
        }

        $expresion = (string) preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $definicion['patron']);
        $coincide = preg_match('#^' . $expresion . '$#', $ruta, $coincidencias);

        if ($coincide !== 1) {
            return null;
        }

        return array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
