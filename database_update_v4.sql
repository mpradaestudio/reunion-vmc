-- ============================================================
-- ACTUALIZACIÓN v4: Agregar perfil 'Publicador'
-- Perfiles finales: Anciano, Siervo Ministerial, Precursor, Publicador
-- Ejecutar UNA vez en phpMyAdmin sobre 'reunion_programador'.
-- Es idempotente: si ya existe 'Publicador', no lo duplica.
-- ============================================================

USE reunion_programador;

INSERT INTO perfiles (nombre, descripcion)
SELECT 'Publicador', 'Publicador'
WHERE NOT EXISTS (SELECT 1 FROM perfiles WHERE nombre = 'Publicador');

SELECT 'Migración v4 completada. Perfiles:' AS mensaje;
SELECT id, nombre FROM perfiles ORDER BY id;
