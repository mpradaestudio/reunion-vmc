-- ============================================================
-- ACTUALIZACIÓN v3: Ajuste de perfiles
-- Perfiles finales: Anciano, Siervo Ministerial, Precursor
-- Ejecutar UNA vez en phpMyAdmin sobre 'reunion_programador'.
-- (Requiere haber ejecutado antes database_update_v2.sql)
-- ============================================================

USE reunion_programador;

-- 1) Reasignar a 'Anciano' las personas cuyo perfil principal sea 'Ayudante'
--    (id 4) para poder eliminar ese perfil sin romper la llave foránea.
UPDATE personas SET perfil_id = 1 WHERE perfil_id = 4;

-- 2) Quitar las relaciones del perfil 'Ayudante' en la tabla múltiple
DELETE FROM persona_perfiles WHERE perfil_id = 4;

-- 3) Eliminar el perfil 'Ayudante'
DELETE FROM perfiles WHERE id = 4;

-- 4) Renombrar 'Discursante' (id 3) a 'Precursor'
UPDATE perfiles SET nombre = 'Precursor', descripcion = 'Precursor regular' WHERE id = 3;

SELECT 'Migración v3 completada. Perfiles:' AS mensaje;
SELECT id, nombre FROM perfiles ORDER BY id;
