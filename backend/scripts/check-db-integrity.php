<?php

declare(strict_types=1);

/**
 * Verificacion de integridad de la base de datos: confirma que ninguna
 * "tabla" del sistema puede quedar en null y que no hay eventos huerfanos
 * (ver database/README.md, seccion de especializacion total/disjunta).
 *
 * Uso: php scripts/check-db-integrity.php   (o "composer check-db")
 * Sale con codigo 0 si todo esta OK, 1 si encuentra un problema.
 */

require __DIR__ . '/../vendor/autoload.php';

use CodigoAzul\Infrastructure\Container\Container;

$configuracion = require __DIR__ . '/../config/config.php';
date_default_timezone_set($configuracion['zonaHoraria']);

$contenedor = new Container($configuracion);
$pdo = $contenedor->pdo();

$huboProblemas = false;

function reportar(string $titulo, bool $ok, string $detalle = ''): bool
{
    $icono = $ok ? 'OK  ' : 'FAIL';
    echo "[{$icono}] {$titulo}" . ($detalle !== '' ? " — {$detalle}" : '') . "\n";

    return !$ok;
}

// 1. Las 7 tablas del esquema existen y son consultables (ninguna "no existe").
$tablasEsperadas = ['areas', 'usuarios', 'pacientes', 'eventos', 'llamados', 'codigos_azul', 'reportes'];
foreach ($tablasEsperadas as $tabla) {
    try {
        $cantidad = (int) $pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
        $huboProblemas = reportar("Tabla '{$tabla}' existe y es consultable", true, "{$cantidad} filas") || $huboProblemas;
    } catch (\PDOException) {
        $huboProblemas = reportar("Tabla '{$tabla}' existe y es consultable", false, 'no existe o no es accesible') || $huboProblemas;
    }
}

// 2. Los repositorios de "listado" nunca devuelven null: el tipado ": array"
//    de cada interfaz de dominio ya lo garantiza en tiempo de ejecucion
//    (PHP lanzaria TypeError si una implementacion intentara devolver
//    null), pero igual se ejercitan aca para detectar cualquier excepcion.
$listados = [
    'areas' => fn () => $contenedor->areaRepositorio()->listar(),
    'usuarios' => fn () => $contenedor->usuarioRepositorio()->listar(),
    'pacientes' => fn () => $contenedor->pacienteRepositorio()->listar(),
    'llamados' => fn () => $contenedor->llamadoRepositorio()->listar(),
    'codigosAzul' => fn () => $contenedor->codigoAzulRepositorio()->listar(),
];
foreach ($listados as $nombre => $listar) {
    $resultado = $listar();
    $huboProblemas = reportar(
        "Repositorio '{$nombre}'->listar() nunca es null",
        is_array($resultado),
        count($resultado) . ' registros',
    ) || $huboProblemas;
}

// 3. Ningun evento debe quedar sin su subtipo (especializacion TOTAL:
//    todo Evento es Llamado o CodigoAzul). Si esto falla, alguien insertó
//    en `eventos` sin pasar por sp_registrar_llamado/sp_registrar_codigo_azul.
$huerfanos = $pdo->query(
    'SELECT e.id FROM eventos e
     LEFT JOIN llamados l ON l.id = e.id
     LEFT JOIN codigos_azul c ON c.id = e.id
     WHERE l.id IS NULL AND c.id IS NULL',
)->fetchAll();
$huboProblemas = reportar(
    'Ningún evento queda sin Llamado/CodigoAzul (especialización total)',
    $huerfanos === [],
    $huerfanos === [] ? '' : count($huerfanos) . ' evento(s) huérfano(s): ' . implode(',', array_column($huerfanos, 'id')),
) || $huboProblemas;

// 4. Ningun Encargado de Area sin area, ningun Administrador con area.
$inconsistentes = $pdo->query(
    "SELECT id, usuario, rol, area_id FROM usuarios
     WHERE (rol = 'ADMINISTRADOR' AND area_id IS NOT NULL)
        OR (rol = 'ENCARGADO' AND area_id IS NULL)",
)->fetchAll();
$huboProblemas = reportar(
    'Todo usuario ENCARGADO tiene área y todo ADMINISTRADOR no tiene',
    $inconsistentes === [],
    $inconsistentes === [] ? '' : count($inconsistentes) . ' usuario(s) inconsistente(s)',
) || $huboProblemas;

echo "\n" . ($huboProblemas ? 'Se encontraron problemas de integridad.' : 'Integridad de la base de datos OK.') . "\n";

exit($huboProblemas ? 1 : 0);
