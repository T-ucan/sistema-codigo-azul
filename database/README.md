# Base de datos MySQL — Sistema Código Azul

Esquema relacional derivado del DER (notación de Chen) y del diagrama de
clases del proyecto, alineado con los campos y reglas de validación reales
del prototipo (`script.js`).

## Archivos

- `codigo_azul_schema.sql` — base de datos, tablas, claves foráneas,
  triggers y stored procedures. Es el script principal.
- `seed_data.sql` — datos de ejemplo (mismas áreas/usuarios/pacientes que
  usa el prototipo en `localStorage`), opcional, solo para pruebas.

## Cómo cargarla

```bash
mysql -u root -p < codigo_azul_schema.sql
mysql -u root -p < seed_data.sql   # opcional
```

Requiere **MySQL 8.0.16 o superior** (las restricciones `CHECK` recién se
validan a partir de esa versión; en versiones anteriores se ignoran
silenciosamente, aunque los triggers y claves foráneas sí se aplican
siempre).

## Modelo

| Tabla | Corresponde a (DER) | Notas |
|---|---|---|
| `areas` | Área | |
| `usuarios` | Usuario (Administrador / EncargadoDeArea) | Herencia de un nivel resuelta con columna discriminante `rol`, porque las subclases no agregan atributos propios. `area_id` es obligatorio solo para `ENCARGADO` (constraint `chk_usuarios_rol_area`). |
| `pacientes` | Paciente | `area_id` obligatorio (relación *Internado_en*, 1,1). |
| `eventos` | Evento (superclase) | Tabla base de la especialización. |
| `llamados` | Llamado (subclase) | `id` es a la vez PK y FK hacia `eventos.id`. |
| `codigos_azul` | Código Azul (subclase) | Igual mapeo que `llamados`. |
| `reportes` | Reporte | |

### Especialización total y disjunta (Evento → Llamado / Código Azul)

El DER exige que todo Evento sea **o** Llamado **o** Código Azul, nunca
ambos ni ninguno. En MySQL eso no se puede declarar de forma puramente
declarativa, así que se resuelve con:

- **Disjunción**: triggers `trg_llamados_bi_disjuncion` /
  `trg_codigos_azul_bi_disjuncion` impiden insertar un `id` que ya exista
  en la tabla del otro subtipo.
- **Totalidad**: usar siempre los stored procedures `sp_registrar_llamado`
  y `sp_registrar_codigo_azul`, que insertan en `eventos` + la tabla hija
  dentro de una misma transacción. Si se inserta manualmente en `eventos`
  sin completar el subtipo, la fila queda "huérfana"; para detectarlas:

  ```sql
  SELECT e.* FROM eventos e
  LEFT JOIN llamados l ON l.id = e.id
  LEFT JOIN codigos_azul c ON c.id = e.id
  WHERE l.id IS NULL AND c.id IS NULL;
  ```

### Vista `vw_eventos`

Hace el `JOIN` de `eventos` con `llamados`/`codigos_azul` que menciona la
leyenda del DER, útil para reportes que necesiten listar ambos tipos de
evento en una sola consulta.

### Stored procedures disponibles

- `sp_registrar_llamado(...)` — caso de uso "Registrar Llamado".
- `sp_marcar_llamado_atendido(id_llamado)` — caso de uso "Atender Llamado";
  calcula `tiempo_respuesta` automáticamente.
- `sp_registrar_codigo_azul(...)` — caso de uso "Registrar Ficha de Código
  Azul"; calcula `tiempo_respuesta` y valida que `hora_llegada_equipo` sea
  posterior al evento.

### Decisiones de diseño más allá del prototipo

- Claves primarias `INT UNSIGNED AUTO_INCREMENT` en vez de los IDs de texto
  generados en el cliente (`area-1`, `usr-2`, etc.) — es lo estándar para
  una base de datos relacional real.
- `pacientes.dni` es `UNIQUE`: el prototipo no lo valida, pero en un
  sistema real dos fichas no deberían compartir el mismo DNI.
- `clave_hash` está pensada para un hash seguro (bcrypt/argon2) generado
  en el backend; el prototipo usa un hash no criptográfico (djb2) solo para
  la demo en el navegador.
- `reportes.usuario_id` permite `NULL` (`ON DELETE SET NULL`) para poder
  borrar un usuario sin perder el historial de reportes que generó.
