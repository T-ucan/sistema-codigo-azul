<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Model;

/**
 * Jerarquia de un solo nivel (Usuario -> Administrador / EncargadoDeArea),
 * igual que en el diagrama de clases: las subclases no agregan atributos ni
 * redefinen comportamiento (principio de sustitucion de Liskov).
 */
abstract class Usuario
{
    public function __construct(
        protected ?int $id,
        protected string $nombre,
        protected string $usuario,
        protected string $claveHash,
        protected Rol $rol,
        protected ?int $areaId,
    ) {
    }

    public static function crear(
        ?int $id,
        string $nombre,
        string $usuario,
        string $claveHash,
        Rol $rol,
        ?int $areaId,
    ): self {
        return $rol === Rol::ADMINISTRADOR
            ? new Administrador($id, $nombre, $usuario, $claveHash, $rol, $areaId)
            : new EncargadoDeArea($id, $nombre, $usuario, $claveHash, $rol, $areaId);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function usuario(): string
    {
        return $this->usuario;
    }

    public function claveHash(): string
    {
        return $this->claveHash;
    }

    public function rol(): Rol
    {
        return $this->rol;
    }

    public function areaId(): ?int
    {
        return $this->areaId;
    }

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::ADMINISTRADOR;
    }

    public function asignarId(int $id): void
    {
        $this->id ??= $id;
    }

    /** Datos seguros para exponer por la API: nunca incluye claveHash. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'rol' => $this->rol->value,
            'areaId' => $this->areaId,
        ];
    }
}
