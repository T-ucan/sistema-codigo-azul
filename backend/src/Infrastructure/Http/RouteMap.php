<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http;

use CodigoAzul\Infrastructure\Container\Container;

/**
 * Tabla explicita de rutas de la API. Vive separada del Container para que
 * este no crezca con logica de ruteo, y separada del Kernel para que el
 * Kernel no conozca los controladores concretos.
 */
final class RouteMap
{
    public function __construct(private readonly Container $contenedor)
    {
    }

    public function registrar(Router $router): void
    {
        $this->rutasAuth($router);
        $this->rutasAreas($router);
        $this->rutasUsuarios($router);
        $this->rutasPacientes($router);
        $this->rutasLlamados($router);
        $this->rutasCodigosAzul($router);
        $this->rutasReportes($router);
    }

    private function rutasAuth(Router $router): void
    {
        $auth = $this->contenedor->authController();
        $router->post('/api/auth/login', [$auth, 'iniciarSesion']);
        $router->post('/api/auth/logout', [$auth, 'cerrarSesion']);
        $router->get('/api/auth/sesion', [$auth, 'sesionActual']);
    }

    private function rutasAreas(Router $router): void
    {
        $areas = $this->contenedor->areaController();
        $router->get('/api/areas', [$areas, 'listar']);
        $router->post('/api/areas', [$areas, 'guardar']);
        $router->put('/api/areas/{id}', [$areas, 'guardar']);
        $router->delete('/api/areas/{id}', [$areas, 'eliminar']);
    }

    private function rutasUsuarios(Router $router): void
    {
        $usuarios = $this->contenedor->usuarioController();
        $router->get('/api/usuarios', [$usuarios, 'listar']);
        $router->post('/api/usuarios', [$usuarios, 'guardar']);
        $router->put('/api/usuarios/{id}', [$usuarios, 'guardar']);
        $router->delete('/api/usuarios/{id}', [$usuarios, 'eliminar']);
    }

    private function rutasPacientes(Router $router): void
    {
        $pacientes = $this->contenedor->pacienteController();
        $router->get('/api/pacientes', [$pacientes, 'listar']);
        $router->post('/api/pacientes', [$pacientes, 'guardar']);
        $router->put('/api/pacientes/{id}', [$pacientes, 'guardar']);
    }

    private function rutasLlamados(Router $router): void
    {
        $llamados = $this->contenedor->llamadoController();
        $router->get('/api/llamados', [$llamados, 'listarDelArea']);
        $router->get('/api/llamados/pendientes', [$llamados, 'listarPendientes']);
        $router->post('/api/llamados', [$llamados, 'registrar']);
        $router->put('/api/llamados/{id}/atendido', [$llamados, 'marcarAtendido']);
    }

    private function rutasCodigosAzul(Router $router): void
    {
        $codigosAzul = $this->contenedor->codigoAzulController();
        $router->get('/api/codigos-azul', [$codigosAzul, 'listar']);
        $router->post('/api/codigos-azul', [$codigosAzul, 'registrar']);
    }

    private function rutasReportes(Router $router): void
    {
        $reportes = $this->contenedor->reporteController();
        $router->get('/api/reportes/estadisticas', [$reportes, 'estadisticas']);
        // POST, no GET: generar el CSV tambien registra la generacion (efecto
        // secundario), y una peticion que muta estado nunca debe ser un GET
        // (rompe la semantica segura de HTTP y evitaria la proteccion CSRF).
        $router->post('/api/reportes/csv', [$reportes, 'exportarCsv']);
        $router->post('/api/reportes/pdf', [$reportes, 'registrarExportacionPdf']);
    }
}
