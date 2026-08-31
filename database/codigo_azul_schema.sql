-- =============================================================================
-- Sistema de Gestión Código Azul (HospCall Manager) — Esquema MySQL
-- =============================================================================
-- Basado en:
--   - DER__Codigo_Azul.drawio (notación de Chen, con especialización total y
--     disjunta Evento -> Llamado / CodigoAzul)
--   - Diagrama_de_clases.drawio (Usuario -> Administrador / EncargadoDeArea)
--   - Diagrama de contexto (HospCall Manager v1.0)
--   - script.js del prototipo (campos, enums y reglas de validación reales)
--
-- Motor: InnoDB (requerido para claves foráneas y transacciones)
-- Charset: utf8mb4 (soporta tildes, ñ y emojis usados en la UI)
-- Requiere MySQL >= 8.0.16 para que las restricciones CHECK se validen.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS codigo_azul
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE codigo_azul;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Limpieza (permite re-ejecutar el script durante el desarrollo)
-- -----------------------------------------------------------------------------
DROP VIEW IF EXISTS vw_eventos;
DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS codigos_azul;
DROP TABLE IF EXISTS llamados;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS pacientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS areas;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. ÁREA
-- =============================================================================
CREATE TABLE areas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  ubicacion   VARCHAR(150) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_areas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. USUARIO (Administrador / EncargadoDeArea — herencia de un solo nivel,
--    resuelta con tabla única + columna discriminante `rol`, ya que ninguna
--    subclase agrega atributos propios en el diagrama de clases)
-- =============================================================================
CREATE TABLE usuarios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  usuario     VARCHAR(50)  NOT NULL,
  clave_hash  VARCHAR(255) NOT NULL COMMENT 'Hash de contraseña (bcrypt/argon2 en producción; el prototipo usa un hash simple no criptográfico)',
  rol         ENUM('ADMINISTRADOR','ENCARGADO') NOT NULL,
  area_id     INT UNSIGNED NULL COMMENT 'Obligatoria solo para rol ENCARGADO (relación Asignada, 0..1)',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_area (area_id),
  CONSTRAINT fk_usuarios_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Nota: la regla "rol=ADMINISTRADOR -> area_id NULL, rol=ENCARGADO -> area_id
-- obligatorio" NO se puede expresar como CHECK: MariaDB (a diferencia de
-- MySQL 8) rechaza un CHECK que referencie una columna con "ON UPDATE
-- CASCADE" en su FK (error 1901). Para que el script funcione igual en
-- MySQL y en MariaDB se aplica con un trigger (ver sección 6), igual que
-- las otras dos reglas que tampoco podían ser CHECK.

-- =============================================================================
-- 3. PACIENTE
-- =============================================================================
CREATE TABLE pacientes (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre            VARCHAR(150) NOT NULL,
  dni               VARCHAR(8) NOT NULL,
  fecha_nacimiento  DATE NOT NULL,
  datos_medicos     TEXT NULL COMMENT 'Observaciones / antecedentes (attr "observaciones" del DER)',
  area_id           INT UNSIGNED NOT NULL COMMENT 'Internado en (relación Internado_en, 1,1 obligatoria)',
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pacientes_dni (dni),
  KEY idx_pacientes_area (area_id),
  CONSTRAINT fk_pacientes_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_pacientes_dni CHECK (dni REGEXP '^[0-9]{7,8}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Nota: la validación "fecha_nacimiento no puede ser futura" no puede expresarse
-- como CHECK porque requeriría CURDATE(), una función no determinista, y MySQL
-- rechaza ese tipo de funciones dentro de restricciones CHECK. Se aplica con un
-- trigger (ver sección 6).

-- =============================================================================
-- 4. EVENTO (superclase) + LLAMADO / CODIGO_AZUL (subclases)
-- -----------------------------------------------------------------------------
-- Mapeo elegido según la leyenda del DER: tabla base `eventos` + una tabla por
-- subtipo, con PK de la tabla hija = FK hacia eventos.id (sin columnas nulas
-- de subtipo, permite JOIN para consultas combinadas). La especialización es
-- TOTAL (todo Evento tiene subtipo) y DISJUNTA (nunca ambos a la vez); ambas
-- reglas se garantizan con los triggers y los stored procedures más abajo, no
-- solo con las FK.
-- =============================================================================
CREATE TABLE eventos (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha_hora              DATETIME NOT NULL,
  area_id                 INT UNSIGNED NOT NULL COMMENT 'Atendido_en / Ocurre_en (1,1 obligatoria)',
  personal_interviniente  VARCHAR(150) NOT NULL,
  observaciones           TEXT NULL,
  tipo_evento             ENUM('LLAMADO','CODIGO_AZUL') NOT NULL COMMENT 'Discriminante de la especialización; la mantienen los triggers de llamados/codigos_azul',
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_eventos_area (area_id),
  KEY idx_eventos_fecha (fecha_hora),
  CONSTRAINT fk_eventos_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE llamados (
  id                INT UNSIGNED PRIMARY KEY COMMENT 'FK hacia eventos.id (misma PK, mapeo tabla-por-subtipo)',
  tipo              ENUM('Normal','Emergencia') NOT NULL,
  origen            ENUM('Cama','Baño') NOT NULL,
  paciente_id       INT UNSIGNED NULL COMMENT 'Opcional: puede no identificarse el paciente al momento del llamado',
  estado            ENUM('Pendiente','Atendido') NOT NULL DEFAULT 'Pendiente',
  tiempo_respuesta  INT UNSIGNED NULL COMMENT 'Minutos entre fecha_hora del evento y atención (calculado en el back-end)',
  KEY idx_llamados_paciente (paciente_id),
  KEY idx_llamados_estado (estado),
  CONSTRAINT fk_llamados_evento FOREIGN KEY (id) REFERENCES eventos(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_llamados_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE codigos_azul (
  id                       INT UNSIGNED PRIMARY KEY COMMENT 'FK hacia eventos.id (misma PK, mapeo tabla-por-subtipo)',
  paciente_id              INT UNSIGNED NOT NULL COMMENT 'Corresponde_a (1,1 obligatoria)',
  hora_llegada_equipo      DATETIME NOT NULL,
  tiempo_respuesta         INT UNSIGNED NULL COMMENT 'Minutos entre fecha_hora del evento y hora_llegada_equipo',
  intervencion_realizada   TEXT NOT NULL,
  resultado                ENUM('RCE','Traslado a UTI','Fallecimiento','Otro') NOT NULL,
  llamado_origen_id        INT UNSIGNED NULL COMMENT 'Llamado que originó este código azul, si corresponde (relación "origina" del diagrama de clases)',
  KEY idx_codigos_azul_paciente (paciente_id),
  KEY idx_codigos_azul_resultado (resultado),
  KEY idx_codigos_azul_llamado_origen (llamado_origen_id),
  CONSTRAINT fk_codigos_azul_evento FOREIGN KEY (id) REFERENCES eventos(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_codigos_azul_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_codigos_azul_llamado_origen FOREIGN KEY (llamado_origen_id) REFERENCES llamados(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Nota: "hora_llegada_equipo debe ser posterior a fecha_hora del evento" requiere
-- leer otra tabla (eventos), algo que un CHECK de columna no puede hacer. Se
-- aplica con un trigger (ver sección 6).

-- =============================================================================
-- 5. REPORTE
-- =============================================================================
CREATE TABLE reportes (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha_generacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  formato            VARCHAR(20) NOT NULL COMMENT 'Ej: CSV, PDF',
  filtros_aplicados  TEXT NULL COMMENT 'JSON con los filtros usados al generar el reporte',
  usuario_id         INT UNSIGNED NULL COMMENT 'Genera (se conserva el reporte aunque se borre el usuario -> ON DELETE SET NULL)',
  KEY idx_reportes_usuario (usuario_id),
  CONSTRAINT fk_reportes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. TRIGGERS — garantizan la especialización TOTAL y DISJUNTA de Evento
--    (todo evento es Llamado o CodigoAzul, nunca ambos)
-- =============================================================================
DELIMITER $$

CREATE TRIGGER trg_usuarios_bi_rol_area
BEFORE INSERT ON usuarios
FOR EACH ROW
BEGIN
  IF NEW.rol = 'ADMINISTRADOR' AND NEW.area_id IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Un Administrador no debe tener área asignada.';
  END IF;
  IF NEW.rol = 'ENCARGADO' AND NEW.area_id IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Debe asignar un área a un Encargado de Área.';
  END IF;
END$$

CREATE TRIGGER trg_usuarios_bu_rol_area
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
  IF NEW.rol = 'ADMINISTRADOR' AND NEW.area_id IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Un Administrador no debe tener área asignada.';
  END IF;
  IF NEW.rol = 'ENCARGADO' AND NEW.area_id IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Debe asignar un área a un Encargado de Área.';
  END IF;
END$$

CREATE TRIGGER trg_pacientes_bi_fecha_nacimiento
BEFORE INSERT ON pacientes
FOR EACH ROW
BEGIN
  IF NEW.fecha_nacimiento > CURDATE() THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La fecha de nacimiento no puede ser futura.';
  END IF;
END$$

CREATE TRIGGER trg_pacientes_bu_fecha_nacimiento
BEFORE UPDATE ON pacientes
FOR EACH ROW
BEGIN
  IF NEW.fecha_nacimiento > CURDATE() THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La fecha de nacimiento no puede ser futura.';
  END IF;
END$$

CREATE TRIGGER trg_codigos_azul_bi_llegada_posterior
BEFORE INSERT ON codigos_azul
FOR EACH ROW
BEGIN
  IF NEW.hora_llegada_equipo < (SELECT fecha_hora FROM eventos WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La hora de llegada del equipo debe ser posterior al evento.';
  END IF;
END$$

CREATE TRIGGER trg_llamados_bi_disjuncion
BEFORE INSERT ON llamados
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM codigos_azul WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Especialización disjunta: el evento ya está registrado como Código Azul.';
  END IF;
END$$

CREATE TRIGGER trg_llamados_ai_tipo_evento
AFTER INSERT ON llamados
FOR EACH ROW
BEGIN
  UPDATE eventos SET tipo_evento = 'LLAMADO' WHERE id = NEW.id;
END$$

CREATE TRIGGER trg_codigos_azul_bi_disjuncion
BEFORE INSERT ON codigos_azul
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM llamados WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Especialización disjunta: el evento ya está registrado como Llamado.';
  END IF;
END$$

CREATE TRIGGER trg_codigos_azul_ai_tipo_evento
AFTER INSERT ON codigos_azul
FOR EACH ROW
BEGIN
  UPDATE eventos SET tipo_evento = 'CODIGO_AZUL' WHERE id = NEW.id;
END$$

DELIMITER ;

-- =============================================================================
-- 7. STORED PROCEDURES — casos de uso "Registrar Llamado" y "Registrar Ficha
--    de Código Azul": insertan atómicamente en eventos + su tabla de subtipo,
--    garantizando la especialización TOTAL (nunca queda un Evento huérfano).
-- =============================================================================
DELIMITER $$

CREATE PROCEDURE sp_registrar_llamado(
  IN p_fecha_hora DATETIME,
  IN p_area_id INT UNSIGNED,
  IN p_tipo ENUM('Normal','Emergencia'),
  IN p_origen ENUM('Cama','Baño'),
  IN p_paciente_id INT UNSIGNED,
  IN p_personal_interviniente VARCHAR(150),
  IN p_observaciones TEXT,
  OUT p_evento_id INT UNSIGNED
)
proc: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
  INSERT INTO eventos (fecha_hora, area_id, personal_interviniente, observaciones, tipo_evento)
    VALUES (p_fecha_hora, p_area_id, p_personal_interviniente, p_observaciones, 'LLAMADO');
  SET p_evento_id = LAST_INSERT_ID();
  INSERT INTO llamados (id, tipo, origen, paciente_id, estado, tiempo_respuesta)
    VALUES (p_evento_id, p_tipo, p_origen, p_paciente_id, 'Pendiente', NULL);
  COMMIT;
END proc$$

CREATE PROCEDURE sp_marcar_llamado_atendido(
  IN p_llamado_id INT UNSIGNED
)
proc: BEGIN
  UPDATE llamados l
    JOIN eventos e ON e.id = l.id
    SET l.estado = 'Atendido',
        l.tiempo_respuesta = TIMESTAMPDIFF(MINUTE, e.fecha_hora, NOW())
    WHERE l.id = p_llamado_id;
END proc$$

CREATE PROCEDURE sp_registrar_codigo_azul(
  IN p_fecha_hora DATETIME,
  IN p_area_id INT UNSIGNED,
  IN p_paciente_id INT UNSIGNED,
  IN p_hora_llegada_equipo DATETIME,
  IN p_personal_interviniente VARCHAR(150),
  IN p_observaciones TEXT,
  IN p_intervencion_realizada TEXT,
  IN p_resultado ENUM('RCE','Traslado a UTI','Fallecimiento','Otro'),
  IN p_llamado_origen_id INT UNSIGNED,
  OUT p_evento_id INT UNSIGNED
)
proc: BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
  INSERT INTO eventos (fecha_hora, area_id, personal_interviniente, observaciones, tipo_evento)
    VALUES (p_fecha_hora, p_area_id, p_personal_interviniente, p_observaciones, 'CODIGO_AZUL');
  SET p_evento_id = LAST_INSERT_ID();
  INSERT INTO codigos_azul (id, paciente_id, hora_llegada_equipo, tiempo_respuesta, intervencion_realizada, resultado, llamado_origen_id)
    VALUES (
      p_evento_id, p_paciente_id, p_hora_llegada_equipo,
      TIMESTAMPDIFF(MINUTE, p_fecha_hora, p_hora_llegada_equipo),
      p_intervencion_realizada, p_resultado, p_llamado_origen_id
    );
  COMMIT;
END proc$$

DELIMITER ;

-- =============================================================================
-- 8. VISTA — combina Evento + subtipo para reportes (equivalente al JOIN que
--    menciona la leyenda del DER)
-- =============================================================================
CREATE VIEW vw_eventos AS
SELECT
  e.id, e.fecha_hora, e.area_id, a.nombre AS area_nombre,
  e.personal_interviniente, e.observaciones, e.tipo_evento,
  l.tipo AS llamado_tipo, l.origen AS llamado_origen, l.estado AS llamado_estado,
  l.paciente_id AS llamado_paciente_id, l.tiempo_respuesta AS llamado_tiempo_respuesta,
  c.paciente_id AS codigo_azul_paciente_id, c.hora_llegada_equipo, c.tiempo_respuesta AS codigo_azul_tiempo_respuesta,
  c.intervencion_realizada, c.resultado, c.llamado_origen_id
FROM eventos e
JOIN areas a ON a.id = e.area_id
LEFT JOIN llamados l ON l.id = e.id
LEFT JOIN codigos_azul c ON c.id = e.id;

-- =============================================================================
-- Fin del script
-- =============================================================================
