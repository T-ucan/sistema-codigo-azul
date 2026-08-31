<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http;

use CodigoAzul\Domain\Model\Usuario;

/**
 * Unico punto de entrada a los datos de la peticion HTTP. Ningun otro
 * codigo de la aplicacion lee $_GET/$_POST/php://input directamente: todo
 * pasa por aca, lo que permite auditar y normalizar en un solo lugar el
 * manejo de datos sensibles que llegan del cliente.
 */
final class Request
{
    /** @param array<string, mixed> $query @param array<string, mixed> $cuerpo @param array<string, string> $cabeceras */
    private function __construct(
        public readonly string $metodo,
        public readonly string $ruta,
        private readonly array $query,
        private readonly array $cuerpo,
        private readonly array $cabeceras,
        private readonly ?Usuario $usuarioAutenticado = null,
    ) {
    }

    public static function desdeGlobals(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        return new self($metodo, $ruta, $_GET, self::leerCuerpo($metodo), self::leerCabeceras());
    }

    /** @return array<string, mixed> */
    private static function leerCuerpo(string $metodo): array
    {
        if ($metodo === 'GET' || $metodo === 'DELETE') {
            return [];
        }

        $decodificado = json_decode((string) file_get_contents('php://input'), true);

        return is_array($decodificado) ? $decodificado : $_POST;
    }

    /** @return array<string, string> */
    private static function leerCabeceras(): array
    {
        $cabeceras = [];
        foreach ($_SERVER as $clave => $valor) {
            if (str_starts_with((string) $clave, 'HTTP_') && is_string($valor)) {
                $cabeceras[str_replace('_', '-', substr((string) $clave, 5))] = $valor;
            }
        }

        return $cabeceras;
    }

    public function conUsuarioAutenticado(?Usuario $usuario): self
    {
        return new self($this->metodo, $this->ruta, $this->query, $this->cuerpo, $this->cabeceras, $usuario);
    }

    public function usuarioAutenticado(): ?Usuario
    {
        return $this->usuarioAutenticado;
    }

    public function campoTexto(string $clave, string $porDefecto = ''): string
    {
        $valor = $this->cuerpo[$clave] ?? $porDefecto;

        return is_string($valor) ? trim($valor) : $porDefecto;
    }

    public function campoTextoOpcional(string $clave): ?string
    {
        $valor = $this->campoTexto($clave);

        return $valor === '' ? null : $valor;
    }

    public function parametroQuery(string $clave, string $porDefecto = ''): string
    {
        $valor = $this->query[$clave] ?? $porDefecto;

        return is_string($valor) ? trim($valor) : $porDefecto;
    }

    public function cabecera(string $nombre): ?string
    {
        return $this->cabeceras[strtoupper($nombre)] ?? null;
    }
}
