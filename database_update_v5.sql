-- ============================================================
-- Migración v5: campo subtipo_actividad en programa_secciones
-- Almacena el texto descriptivo extraído de jw.org para cada
-- parte (ej. "Predicación informal", "De casa en casa", etc.)
-- ============================================================

ALTER TABLE programa_secciones
    ADD COLUMN subtipo_actividad VARCHAR(200) NULL DEFAULT NULL
    AFTER tipo_asignacion;
