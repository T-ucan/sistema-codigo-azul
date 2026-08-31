# Backend — Sistema Código Azul

API REST en PHP, arquitectura hexagonal (puertos y adaptadores), con un
Front Controller único para centralizar la seguridad del manejo de datos
sensibles (fichas médicas, credenciales, sesiones). Sin framework: PSR-4
vía Composer, sin dependencias externas en producción.

## Requisitos

- PHP >= 8.2 con extensión `pdo_mysql`
- MySQL 8.0.16+ o MariaDB 10.11+, con el esquema de `../database/codigo_azul_schema.sql`
- Composer

## Instalación

```bash
composer install
cp .env.example .env   # si no existe, ver "Configuración" abajo
```

## Configuración

Todas las variables de entorno las lee `config/config.php` (único lugar que
llama a `getenv()`). Nunca hardcodear credenciales:

| Variable | Default | Descripción |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | Host de MySQL/MariaDB |
| `DB_PORT` | `3306` | Puerto |
| `DB_NAME` | `codigo_azul` | Base de datos (ver `../database/`) |
| `DB_USER` | `root` | Usuario de la base |
| `DB_PASSWORD` | *(vacío)* | Contraseña |
| `APP_TIMEZONE` | `America/Argentina/Buenos_Aires` | Zona horaria única de todo el sistema (evita mezclar hora local con UTC) |
| `APP_ENV` | `production` | En `development` los errores 500 devuelven el mensaje real al cliente; en cualquier otro valor, siempre un mensaje genérico |

## Correr en desarrollo

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

En producción, el *document root* del servidor web (Apache/Nginx/hosting)
debe apuntar a la carpeta `public/`, nunca a la raíz del proyecto — así
`config/`, `src/` y las credenciales nunca son accesibles por HTTP.
Apache usa `public/.htaccess` (ya incluido); Nginx necesita un
`try_files $uri /index.php;` equivalente.

## Comandos

```bash
composer test       # PHPUnit
composer phpmd       # análisis estático (complejidad, acoplamiento, herencia, código muerto)
composer metrics     # PHPMetrics -> var/logs/phpmetrics/index.html (índice de mantenibilidad)
composer check-db    # integridad de la base de datos (requiere DB_* configuradas)
composer analyze     # test + phpmd + metrics
```

Ver `docs/metricas-calidad.md` para los resultados y el detalle de la
arquitectura.

## API

Todas las rutas son JSON (`Content-Type: application/json`), requieren la
cookie de sesión que el propio backend emite en el login, y toda petición
que muta datos (POST/PUT/DELETE) requiere repetir en la cabecera
`X-CSRF-Token` el valor `csrfToken` que trae el cuerpo de la respuesta
anterior.

| Método | Ruta | Rol requerido |
|---|---|---|
| POST | `/api/auth/login` | — |
| POST | `/api/auth/logout` | sesión iniciada |
| GET | `/api/auth/sesion` | — |
| GET | `/api/areas` | sesión iniciada |
| POST/PUT/DELETE | `/api/areas[/{id}]` | ADMINISTRADOR |
| GET/POST/PUT | `/api/usuarios[/{id}]` | ADMINISTRADOR |
| DELETE | `/api/usuarios/{id}` | ADMINISTRADOR |
| GET | `/api/pacientes?busqueda=` | sesión iniciada |
| POST/PUT | `/api/pacientes[/{id}]` | ADMINISTRADOR |
| GET | `/api/llamados`, `/api/llamados/pendientes` | ENCARGADO |
| POST | `/api/llamados` | ENCARGADO |
| PUT | `/api/llamados/{id}/atendido` | ENCARGADO |
| GET/POST | `/api/codigos-azul` | ENCARGADO |
| GET | `/api/reportes/estadisticas?areaId=&origen=&tipo=&desde=&hasta=` | ADMINISTRADOR |
| POST | `/api/reportes/csv`, `/api/reportes/pdf` | ADMINISTRADOR |
