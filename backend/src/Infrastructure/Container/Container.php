<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Container;

use CodigoAzul\Application\UseCase\Area\EliminarAreaUseCase;
use CodigoAzul\Application\UseCase\Area\GuardarAreaUseCase;
use CodigoAzul\Application\UseCase\Area\ListarAreasUseCase;
use CodigoAzul\Application\UseCase\Auth\AutenticarUsuarioUseCase;
use CodigoAzul\Application\UseCase\CodigoAzul\ListarCodigosAzulUseCase;
use CodigoAzul\Application\UseCase\CodigoAzul\RegistrarCodigoAzulUseCase;
use CodigoAzul\Application\UseCase\Llamado\ListarLlamadosPendientesUseCase;
use CodigoAzul\Application\UseCase\Llamado\ListarLlamadosPorAreaUseCase;
use CodigoAzul\Application\UseCase\Llamado\MarcarLlamadoAtendidoUseCase;
use CodigoAzul\Application\UseCase\Llamado\RegistrarLlamadoUseCase;
use CodigoAzul\Application\UseCase\Paciente\BuscarPacientesUseCase;
use CodigoAzul\Application\UseCase\Paciente\GuardarPacienteUseCase;
use CodigoAzul\Application\UseCase\Paciente\ListarPacientesUseCase;
use CodigoAzul\Application\UseCase\Reporte\ExportarCsvUseCase;
use CodigoAzul\Application\UseCase\Reporte\GenerarEstadisticasUseCase;
use CodigoAzul\Application\UseCase\Reporte\RegistrarGeneracionReporteUseCase;
use CodigoAzul\Application\UseCase\Usuario\EliminarUsuarioUseCase;
use CodigoAzul\Application\UseCase\Usuario\GuardarUsuarioUseCase;
use CodigoAzul\Application\UseCase\Usuario\ListarUsuariosUseCase;
use CodigoAzul\Application\Validation\AreaValidator;
use CodigoAzul\Application\Validation\CodigoAzulValidator;
use CodigoAzul\Application\Validation\LlamadoValidator;
use CodigoAzul\Application\Validation\PacienteValidator;
use CodigoAzul\Application\Validation\UsuarioValidator;
use CodigoAzul\Domain\Repository\AreaRepositoryInterface;
use CodigoAzul\Domain\Repository\CodigoAzulRepositoryInterface;
use CodigoAzul\Domain\Repository\LlamadoRepositoryInterface;
use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;
use CodigoAzul\Domain\Repository\ReporteRepositoryInterface;
use CodigoAzul\Domain\Repository\UsuarioRepositoryInterface;
use CodigoAzul\Domain\Service\PasswordHasherInterface;
use CodigoAzul\Infrastructure\Http\Controller\AreaController;
use CodigoAzul\Infrastructure\Http\Controller\AuthController;
use CodigoAzul\Infrastructure\Http\Controller\CodigoAzulController;
use CodigoAzul\Infrastructure\Http\Controller\LlamadoController;
use CodigoAzul\Infrastructure\Http\Controller\PacienteController;
use CodigoAzul\Infrastructure\Http\Controller\ReporteController;
use CodigoAzul\Infrastructure\Http\Controller\UsuarioController;
use CodigoAzul\Infrastructure\Http\ErrorHandling\ManejadorErrores;
use CodigoAzul\Infrastructure\Http\Kernel;
use CodigoAzul\Infrastructure\Http\Middleware\CsrfMiddleware;
use CodigoAzul\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use CodigoAzul\Infrastructure\Http\Middleware\SessionMiddleware;
use CodigoAzul\Infrastructure\Http\Router;
use CodigoAzul\Infrastructure\Http\Security\AuthGuard;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoAreaRepository;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoCodigoAzulRepository;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoConnectionFactory;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoLlamadoRepository;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoPacienteRepository;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoReporteRepository;
use CodigoAzul\Infrastructure\Persistence\Pdo\PdoUsuarioRepository;
use CodigoAzul\Infrastructure\Security\NativePasswordHasher;

/**
 * Raiz de composicion (composition root): el UNICO lugar del proyecto que
 * conoce clases concretas de infraestructura y hace `new`. Todo lo demas
 * (casos de uso, validadores, controladores) solo recibe interfaces/objetos
 * ya armados por aca, por eso el acoplamiento del resto del codigo es bajo.
 * Cada metodo es una linea (complejidad 1); son muchos porque cablean todo
 * el grafo de objetos de la aplicacion, no por logica de negocio.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class Container
{
    /** @var array<string, object> */
    private array $instancias = [];

    /** @param array<string, mixed> $configuracion */
    public function __construct(private readonly array $configuracion)
    {
    }

    // ---------- Infraestructura ----------

    public function pdo(): \PDO
    {
        return $this->compartido(\PDO::class, fn (): \PDO => PdoConnectionFactory::crear($this->configuracion['db']));
    }

    public function areaRepositorio(): AreaRepositoryInterface
    {
        return $this->compartido(AreaRepositoryInterface::class, fn () => new PdoAreaRepository($this->pdo()));
    }

    public function usuarioRepositorio(): UsuarioRepositoryInterface
    {
        return $this->compartido(UsuarioRepositoryInterface::class, fn () => new PdoUsuarioRepository($this->pdo()));
    }

    public function pacienteRepositorio(): PacienteRepositoryInterface
    {
        return $this->compartido(PacienteRepositoryInterface::class, fn () => new PdoPacienteRepository($this->pdo()));
    }

    public function llamadoRepositorio(): LlamadoRepositoryInterface
    {
        return $this->compartido(LlamadoRepositoryInterface::class, fn () => new PdoLlamadoRepository($this->pdo()));
    }

    public function codigoAzulRepositorio(): CodigoAzulRepositoryInterface
    {
        return $this->compartido(CodigoAzulRepositoryInterface::class, fn () => new PdoCodigoAzulRepository($this->pdo()));
    }

    public function reporteRepositorio(): ReporteRepositoryInterface
    {
        return $this->compartido(ReporteRepositoryInterface::class, fn () => new PdoReporteRepository($this->pdo()));
    }

    public function passwordHasher(): PasswordHasherInterface
    {
        return $this->compartido(PasswordHasherInterface::class, static fn () => new NativePasswordHasher());
    }

    // ---------- Validadores ----------

    public function areaValidator(): AreaValidator
    {
        return $this->compartido(AreaValidator::class, fn () => new AreaValidator($this->areaRepositorio()));
    }

    public function usuarioValidator(): UsuarioValidator
    {
        return $this->compartido(UsuarioValidator::class, fn () => new UsuarioValidator($this->usuarioRepositorio()));
    }

    public function pacienteValidator(): PacienteValidator
    {
        return $this->compartido(PacienteValidator::class, fn () => new PacienteValidator($this->pacienteRepositorio()));
    }

    public function llamadoValidator(): LlamadoValidator
    {
        return $this->compartido(LlamadoValidator::class, static fn () => new LlamadoValidator());
    }

    public function codigoAzulValidator(): CodigoAzulValidator
    {
        return $this->compartido(CodigoAzulValidator::class, static fn () => new CodigoAzulValidator());
    }

    // ---------- Casos de uso ----------

    public function autenticarUsuario(): AutenticarUsuarioUseCase
    {
        return $this->compartido(
            AutenticarUsuarioUseCase::class,
            fn () => new AutenticarUsuarioUseCase($this->usuarioRepositorio(), $this->passwordHasher()),
        );
    }

    public function listarAreas(): ListarAreasUseCase
    {
        return $this->compartido(ListarAreasUseCase::class, fn () => new ListarAreasUseCase($this->areaRepositorio()));
    }

    public function guardarArea(): GuardarAreaUseCase
    {
        return $this->compartido(
            GuardarAreaUseCase::class,
            fn () => new GuardarAreaUseCase($this->areaRepositorio(), $this->areaValidator()),
        );
    }

    public function eliminarArea(): EliminarAreaUseCase
    {
        return $this->compartido(EliminarAreaUseCase::class, fn () => new EliminarAreaUseCase($this->areaRepositorio()));
    }

    public function listarUsuarios(): ListarUsuariosUseCase
    {
        return $this->compartido(ListarUsuariosUseCase::class, fn () => new ListarUsuariosUseCase($this->usuarioRepositorio()));
    }

    public function guardarUsuario(): GuardarUsuarioUseCase
    {
        return $this->compartido(
            GuardarUsuarioUseCase::class,
            fn () => new GuardarUsuarioUseCase($this->usuarioRepositorio(), $this->usuarioValidator(), $this->passwordHasher()),
        );
    }

    public function eliminarUsuario(): EliminarUsuarioUseCase
    {
        return $this->compartido(
            EliminarUsuarioUseCase::class,
            fn () => new EliminarUsuarioUseCase($this->usuarioRepositorio()),
        );
    }

    public function listarPacientes(): ListarPacientesUseCase
    {
        return $this->compartido(
            ListarPacientesUseCase::class,
            fn () => new ListarPacientesUseCase($this->pacienteRepositorio()),
        );
    }

    public function buscarPacientes(): BuscarPacientesUseCase
    {
        return $this->compartido(
            BuscarPacientesUseCase::class,
            fn () => new BuscarPacientesUseCase($this->pacienteRepositorio()),
        );
    }

    public function guardarPaciente(): GuardarPacienteUseCase
    {
        return $this->compartido(
            GuardarPacienteUseCase::class,
            fn () => new GuardarPacienteUseCase($this->pacienteRepositorio(), $this->pacienteValidator()),
        );
    }

    public function registrarLlamado(): RegistrarLlamadoUseCase
    {
        return $this->compartido(
            RegistrarLlamadoUseCase::class,
            fn () => new RegistrarLlamadoUseCase($this->llamadoRepositorio(), $this->llamadoValidator()),
        );
    }

    public function marcarLlamadoAtendido(): MarcarLlamadoAtendidoUseCase
    {
        return $this->compartido(
            MarcarLlamadoAtendidoUseCase::class,
            fn () => new MarcarLlamadoAtendidoUseCase($this->llamadoRepositorio()),
        );
    }

    public function listarLlamadosPendientes(): ListarLlamadosPendientesUseCase
    {
        return $this->compartido(
            ListarLlamadosPendientesUseCase::class,
            fn () => new ListarLlamadosPendientesUseCase($this->llamadoRepositorio()),
        );
    }

    public function listarLlamadosPorArea(): ListarLlamadosPorAreaUseCase
    {
        return $this->compartido(
            ListarLlamadosPorAreaUseCase::class,
            fn () => new ListarLlamadosPorAreaUseCase($this->llamadoRepositorio()),
        );
    }

    public function registrarCodigoAzul(): RegistrarCodigoAzulUseCase
    {
        return $this->compartido(
            RegistrarCodigoAzulUseCase::class,
            fn () => new RegistrarCodigoAzulUseCase($this->codigoAzulRepositorio(), $this->codigoAzulValidator()),
        );
    }

    public function listarCodigosAzul(): ListarCodigosAzulUseCase
    {
        return $this->compartido(
            ListarCodigosAzulUseCase::class,
            fn () => new ListarCodigosAzulUseCase($this->codigoAzulRepositorio()),
        );
    }

    public function generarEstadisticas(): GenerarEstadisticasUseCase
    {
        return $this->compartido(
            GenerarEstadisticasUseCase::class,
            fn () => new GenerarEstadisticasUseCase($this->llamadoRepositorio(), $this->areaRepositorio()),
        );
    }

    public function registrarGeneracionReporte(): RegistrarGeneracionReporteUseCase
    {
        return $this->compartido(
            RegistrarGeneracionReporteUseCase::class,
            fn () => new RegistrarGeneracionReporteUseCase($this->reporteRepositorio()),
        );
    }

    public function exportarCsv(): ExportarCsvUseCase
    {
        return $this->compartido(
            ExportarCsvUseCase::class,
            fn () => new ExportarCsvUseCase($this->generarEstadisticas(), $this->registrarGeneracionReporte(), $this->areaRepositorio()),
        );
    }

    // ---------- Seguridad HTTP ----------

    public function authGuard(): AuthGuard
    {
        return $this->compartido(AuthGuard::class, static fn () => new AuthGuard());
    }

    public function manejadorErrores(): ManejadorErrores
    {
        return $this->compartido(
            ManejadorErrores::class,
            fn () => new ManejadorErrores($this->configuracion['entorno'] === 'development'),
        );
    }

    // ---------- Controladores ----------

    public function authController(): AuthController
    {
        return $this->compartido(AuthController::class, fn () => new AuthController($this->autenticarUsuario()));
    }

    public function areaController(): AreaController
    {
        return $this->compartido(
            AreaController::class,
            fn () => new AreaController($this->listarAreas(), $this->guardarArea(), $this->eliminarArea(), $this->authGuard()),
        );
    }

    public function usuarioController(): UsuarioController
    {
        return $this->compartido(
            UsuarioController::class,
            fn () => new UsuarioController(
                $this->listarUsuarios(),
                $this->guardarUsuario(),
                $this->eliminarUsuario(),
                $this->authGuard(),
            ),
        );
    }

    public function pacienteController(): PacienteController
    {
        return $this->compartido(
            PacienteController::class,
            fn () => new PacienteController(
                $this->listarPacientes(),
                $this->buscarPacientes(),
                $this->guardarPaciente(),
                $this->authGuard(),
            ),
        );
    }

    public function llamadoController(): LlamadoController
    {
        return $this->compartido(
            LlamadoController::class,
            fn () => new LlamadoController(
                $this->registrarLlamado(),
                $this->marcarLlamadoAtendido(),
                $this->listarLlamadosPendientes(),
                $this->listarLlamadosPorArea(),
                $this->authGuard(),
            ),
        );
    }

    public function codigoAzulController(): CodigoAzulController
    {
        return $this->compartido(
            CodigoAzulController::class,
            fn () => new CodigoAzulController($this->registrarCodigoAzul(), $this->listarCodigosAzul(), $this->authGuard()),
        );
    }

    public function reporteController(): ReporteController
    {
        return $this->compartido(
            ReporteController::class,
            fn () => new ReporteController(
                $this->generarEstadisticas(),
                $this->exportarCsv(),
                $this->registrarGeneracionReporte(),
                $this->authGuard(),
            ),
        );
    }

    // ---------- Front Controller (rutas + middlewares) ----------

    public function kernel(): Kernel
    {
        return $this->compartido(
            Kernel::class,
            fn () => new Kernel($this->middlewaresGlobales(), $this->router(), $this->manejadorErrores()),
        );
    }

    /** @return \CodigoAzul\Infrastructure\Http\Middleware\Middleware[] */
    private function middlewaresGlobales(): array
    {
        return [
            new SecurityHeadersMiddleware(),
            new SessionMiddleware($this->usuarioRepositorio(), $this->configuracion['sesion']),
            new CsrfMiddleware(),
        ];
    }

    private function router(): Router
    {
        $router = new Router();
        (new \CodigoAzul\Infrastructure\Http\RouteMap($this))->registrar($router);

        return $router;
    }

    /** @param \Closure(): object $fabrica */
    private function compartido(string $clave, \Closure $fabrica): object
    {
        return $this->instancias[$clave] ??= $fabrica();
    }
}
