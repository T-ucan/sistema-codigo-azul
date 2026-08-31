-- =============================================================================
-- Datos de ejemplo — equivalentes a DatosSemilla del prototipo (script.js)
-- Ejecutar DESPUÉS de codigo_azul_schema.sql
-- =============================================================================
USE codigo_azul;
-- Fuerza el charset de la conexión: sin esto, un cliente ejecutado desde una
-- shell sin locale UTF-8 (frecuente en contenedores mínimos) puede corromper
-- literales con tildes/ñ como 'Baño' antes de que lleguen al servidor.
SET NAMES utf8mb4;

-- 1. Áreas
INSERT INTO areas (nombre, ubicacion) VALUES
  ('Guardia', 'Planta Baja - Sector A'),
  ('Terapia Intensiva', '2° Piso - Sector B'),
  ('Internación General', '3° Piso - Sector C');

-- 2. Usuarios
-- clave_hash son hashes BCrypt reales (PASSWORD_DEFAULT de PHP), generados
-- con password_hash() -no SHA2()-, para que backend/ (NativePasswordHasher,
-- que usa password_hash/password_verify) pueda autenticarlos tal cual.
-- Contraseñas de referencia:
--   admin   -> admin123
--   jperez  -> 1234
--   mgomez  -> 1234
INSERT INTO usuarios (nombre, usuario, clave_hash, rol, area_id) VALUES
  ('Administrador General', 'admin',  '$2y$12$02VyCjWRLAViKXom9F3sVO9T0RUiCKZ0DyStj5efYrZbHVIT1YkL6', 'ADMINISTRADOR', NULL),
  ('Juan Pérez',            'jperez', '$2y$12$5R2YfOGcPf.iQe7EbF6nSekWxB3jpFDo6ZMekYDLZu33PAWTshbTK', 'ENCARGADO', (SELECT id FROM areas WHERE nombre = 'Guardia')),
  ('María Gómez',           'mgomez', '$2y$12$5R2YfOGcPf.iQe7EbF6nSekWxB3jpFDo6ZMekYDLZu33PAWTshbTK', 'ENCARGADO', (SELECT id FROM areas WHERE nombre = 'Terapia Intensiva'));

-- 3. Pacientes
INSERT INTO pacientes (nombre, dni, fecha_nacimiento, datos_medicos, area_id) VALUES
  ('Carlos Rodríguez', '30123456', '1975-04-12', 'Hipertensión. Alergia a penicilina.', (SELECT id FROM areas WHERE nombre = 'Guardia')),
  ('Ana Fernández',     '28456789', '1968-09-03', 'Diabetes tipo II.',                   (SELECT id FROM areas WHERE nombre = 'Terapia Intensiva')),
  ('Luis Martínez',     '35789012', '1990-01-20', 'Sin antecedentes relevantes.',         (SELECT id FROM areas WHERE nombre = 'Guardia'));

-- 4. Llamados (usando el stored procedure para respetar la especialización)
SET @area_guardia = (SELECT id FROM areas WHERE nombre = 'Guardia');
SET @area_uti      = (SELECT id FROM areas WHERE nombre = 'Terapia Intensiva');
SET @area_internacion = (SELECT id FROM areas WHERE nombre = 'Internación General');
SET @pac_carlos = (SELECT id FROM pacientes WHERE dni = '30123456');
SET @pac_ana    = (SELECT id FROM pacientes WHERE dni = '28456789');
SET @pac_luis   = (SELECT id FROM pacientes WHERE dni = '35789012');

CALL sp_registrar_llamado(NOW() - INTERVAL 0 DAY, @area_guardia, 'Normal', 'Cama', @pac_carlos, 'Enf. Torres', 'Solicita agua.', @evt1);
CALL sp_marcar_llamado_atendido(@evt1);

CALL sp_registrar_llamado(NOW() - INTERVAL 0 DAY, @area_guardia, 'Emergencia', 'Baño', @pac_luis, 'Enf. Torres', 'Mareos.', @evt2);
-- Este queda 'Pendiente' a propósito, como en el prototipo.

CALL sp_registrar_llamado(NOW() - INTERVAL 1 DAY, @area_uti, 'Normal', 'Cama', @pac_ana, 'Enf. Ledesma', '', @evt3);
CALL sp_marcar_llamado_atendido(@evt3);

CALL sp_registrar_llamado(NOW() - INTERVAL 2 DAY, @area_guardia, 'Emergencia', 'Cama', @pac_carlos, 'Enf. Torres', 'Dolor de pecho.', @evt4);
CALL sp_marcar_llamado_atendido(@evt4);

CALL sp_registrar_llamado(NOW() - INTERVAL 5 DAY, @area_guardia, 'Emergencia', 'Cama', @pac_luis, 'Enf. Torres', 'Caída.', @evt7);
CALL sp_marcar_llamado_atendido(@evt7);

-- 5. Códigos Azul (originados en llamados de emergencia previos)
CALL sp_registrar_codigo_azul(
  NOW() - INTERVAL 2 DAY, @area_guardia, @pac_carlos, NOW() - INTERVAL 2 DAY + INTERVAL 3 MINUTE,
  'Equipo de Reanimación A', 'Traslado posterior a UTI.', 'RCP y desfibrilación.', 'RCE', @evt4, @codigo1
);

CALL sp_registrar_codigo_azul(
  NOW() - INTERVAL 5 DAY, @area_guardia, @pac_luis, NOW() - INTERVAL 5 DAY + INTERVAL 2 MINUTE,
  'Equipo de Reanimación B', '', 'Inmovilización y control de signos vitales.', 'Traslado a UTI', @evt7, @codigo2
);
