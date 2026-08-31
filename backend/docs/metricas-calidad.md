# Métricas de calidad — Backend Código Azul

Este documento explica la arquitectura del backend y los resultados reales
de las métricas de calidad pedidas, obtenidos corriendo las herramientas
(no estimados): [PHPMD](https://phpmd.org/) (complejidad, acoplamiento,
herencia, código muerto) y [PHPMetrics](https://www.phpmetrics.org/)
(índice de mantenibilidad, Halstead). Se corrieron contra una instancia
real de MariaDB (esquema de `database/codigo_azul_schema.sql`) y no solo
por lectura de código.

Reproducir localmente:

```bash
composer install
composer test      # PHPUnit (48 tests)
composer phpmd      # complejidad / acoplamiento / herencia / código muerto
composer metrics    # índice de mantenibilidad -> var/logs/phpmetrics/index.html
composer check-db   # integridad de la base de datos (requiere DB_* configuradas)
composer analyze    # las tres primeras, en orden
```

## 1. Arquitectura (hexagonal)

```
public/index.php  ← ÚNICO archivo PHP servible por HTTP (Front Controller)
        │
        ▼
Infrastructure/Http/Kernel  → pipeline de middlewares de seguridad
        │                      (headers, sesión endurecida, CSRF)
        ▼
Infrastructure/Http/Router + RouteMap  → despacha a un Controller
        │
        ▼
Infrastructure/Http/Controller/*  → adaptador delgado: Request → caso de uso → JsonResponse
        │
        ▼
Application/UseCase/*  → un caso de uso por acción (registrar llamado, marcar
        │                 atendido, generar reporte...). Solo dependen de
        │                 interfaces del dominio (puertos), nunca de PDO.
        ▼
Domain/Model, Domain/Repository (interfaces), Domain/Exception
        ▲
        │  implementa
Infrastructure/Persistence/Pdo/*  → adaptador real hacia MySQL/MariaDB
```

`Container` (raíz de composición) es el único lugar que conecta interfaces
con implementaciones concretas — todo el resto del código depende de
abstracciones, lo que explica el bajo acoplamiento medido más abajo.

## 2. Resultados vs. los umbrales pedidos

| Métrica pedida | Herramienta | Resultado real | Umbral | Cumple |
|---|---|---|---|---|
| Complejidad ciclomática | PHPMD (`CyclomaticComplexity`, reportLevel=6) | **0 violaciones** — promedio 2.93 por clase (PHPMetrics) | ≤ 5 | ✅ |
| Índice de mantenibilidad | PHPMetrics | **84.3 / 100** | ≥ 60% | ✅ |
| Deuda técnica | Método SQALE aplicado a mano (ver §3, no hay SonarQube en este entorno) | **≈ 0.11%** | ≤ 6% | ✅ |
| Ifs anidados / ifs sobre estado | PHPMD (`ElseExpression`, `IfStatementAssignment`) + revisión manual | **0 violaciones** — validadores y `ManejadorErrores` usan listas de reglas/tablas de búsqueda, no switch/if-elseif | — | ✅ |
| Niveles de herencia (Liskov) | PHPMD (`DepthOfInheritance`, reporta desde 3) | **0 violaciones** — profundidad real máxima = 1 (`Usuario→Administrador`, `Evento→Llamado`) | ≤ 2 | ✅ |
| CBO (acoplamiento) | PHPMD (`CouplingBetweenObjects`, máx. 12) + PHPMetrics | **0 violaciones** — acoplamiento aferente/eferente promedio 2.04 / 2.75 | bajo | ✅ |
| Ninguna tabla/repositorio null | `scripts/check-db-integrity.php` contra MariaDB real | **7/7 tablas OK**, repositorios `listar()` tipados `: array` (PHP no permite devolver null) | sin nulls | ✅ |

## 3. Deuda técnica — cómo se calculó

No hay SonarQube en este entorno (requiere un servidor Java aparte), así
que se aplicó **el mismo método que usa internamente (SQALE)** a mano, con
los hallazgos reales de PHPMD/PHPMetrics como entrada:

```
Deuda técnica % = (costo de remediación / costo de desarrollo) × 100
```

- **Costo de desarrollo**: 1890 líneas lógicas de código (LLOC, medidas por
  PHPMetrics) × 30 min/línea (supuesto estándar de SonarQube) = 56 700 min.
- **Costo de remediación**: PHPMD terminó en **0 violaciones** (ver §4).
  PHPMetrics señala 7 notas de diseño de paquetes (6 *warning* + 1
  *information*, 0 *error*, 0 *critical* — ver §5): 6×10 min + 1×5 min = 65 min.
- **Deuda técnica = 65 / 56700 × 100 ≈ 0.11%**, muy por debajo del 6% pedido.

## 4. Código muerto / ausencias encontradas y corregidas

La revisión de código (PHPMD `unusedcode.xml` + lectura manual + pruebas
reales contra MariaDB) encontró y corrigió:

1. **Parámetro sin usar real**: `AuthController::cerrarSesion(Request $solicitud)`
   nunca usaba `$solicitud`. Se sacó del método.
2. **Métodos por encima del límite de tamaño** (`ExcessiveMethodLength`):
   `RegistrarCodigoAzulUseCase::__invoke` y `UsuarioValidator::validar` se
   partieron en métodos privados más chicos y con un solo propósito.
3. **Dos propiedades de PHPMD mal nombradas en el ruleset propio**
   (`maximum` en vez de `minimum` para `DepthOfInheritance` y
   `ExcessiveParameterList`): el override nunca se aplicaba y la regla
   corría con el valor por defecto de la herramienta sin que nadie lo
   notara. Se corrigió y se verificó forzando una violación real (una
   jerarquía de 4 clases) para confirmar que ahora sí se detecta.
4. **Bug de integración real usando MariaDB real** (no solo lectura de
   código): el `Kernel` dejaba de adjuntar el `csrfToken` a la respuesta
   cada vez que un caso de uso lanzaba una excepción (422/404/409...),
   porque ese paso vivía en `SessionMiddleware` *después* del punto donde
   la excepción se escapaba. Un cliente que recibía un error de
   validación se quedaba sin token para reintentar. Se movió la
   responsabilidad al único punto que corre siempre: `Kernel::manejar()`.
5. **`seed_data.sql` incompatible con el backend real**: usaba
   `SHA2('admin123', 256)` para las contraseñas, pero
   `NativePasswordHasher` (el que realmente usa el backend) verifica con
   `password_verify()` (BCrypt). Con eso, ningún usuario semilla podía
   loguearse. Se reemplazó por hashes BCrypt reales.
6. **Esquema SQL incompatible con MariaDB** (entregado en una sesión
   anterior, solo probado antes contra MySQL 8 de memoria): un `CHECK`
   sobre `usuarios.area_id` fallaba en MariaDB real con el error 1901
   porque esa columna tiene `ON UPDATE CASCADE` en su FK. Se reemplazó
   por un trigger, igual que las otras dos reglas que ya no podían ser
   `CHECK`.
7. **`seed_data.sql` sin `SET NAMES utf8mb4`**: en una shell sin locale
   UTF-8 (frecuente en contenedores), el literal `'Baño'` se corrompía
   silenciosamente al importar. Se agregó el `SET NAMES` que el schema
   principal ya tenía.

Los puntos 4 a 7 se encontraron **corriendo el backend de punta a punta**
contra una base MariaDB real (login, CSRF, roles, registrar llamado,
marcar atendido, generar reportes, borrar un área en uso) — no aparecen
con una lectura de código ni con PHPMD/PHPMetrics, que solo ven un archivo
a la vez.

## 5. Hallazgos que quedaron documentados sin actuar

PHPMetrics marca 6 *warning* de "Stable Abstractions/Dependencies
Principle" (paquetes que mezclan interfaces y clases concretas) y 1
*information* ("Container tiene muchas dependencias", esperado: es la raíz
de composición). Son métricas de diseño de paquetes de Robert C. Martin,
pensadas para separar todo un ecosistema de paquetes en mitades
abstractas/concretas — partir aún más los namespaces actuales para
resolverlas agregaría ceremonia sin beneficio real para el tamaño de este
proyecto, y no son parte de lo que se pidió. Se dejan anotadas, no
ignoradas.

## 6. Ifs anidados / switch sobre estado — cómo se evitaron

En vez de encadenar `if/elseif` o `switch` sobre un campo de estado, todo
el código usa dos patrones, igual que ya hacía el front-end:

- **Lista de reglas** (`RuleValidator::evaluar`): cada validador arma un
  arreglo `[{valido, mensaje}, ...]` y una sola función genérica filtra
  los que fallaron. Agregar una regla nueva no toca la lógica de control.
- **Tabla de búsqueda** en vez de switch: `ManejadorErrores` mapea clase
  de excepción → código HTTP con un arreglo asociativo recorrido por
  `instanceof`, no una cadena de `if/elseif`.

`ElseExpression` de PHPMD (que directamente prohíbe la palabra clave
`else`) corre sobre toda la capa de aplicación e infraestructura con
**0 violaciones**.
