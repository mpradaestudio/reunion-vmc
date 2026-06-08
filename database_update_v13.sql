-- ── Actualización v13: Visita de Circuito en programa_detalle ───────────
-- titulo_visita: título editable del Estudio Bíblico durante semanas de
-- visita de circuito (Opción B — no modifica titulo original).
-- cancion_final_visita: canción final libre durante semanas de visita.
-- nombre_libre en asignaciones_partes ya existe — permite guardar
-- Conductor/Lector como texto libre durante visita de circuito.

ALTER TABLE `programa_secciones`
    ADD COLUMN `titulo_visita` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Título personalizado durante visita de circuito';

ALTER TABLE `programas_semanales`
    ADD COLUMN `cancion_final_visita` VARCHAR(20) NULL DEFAULT NULL
        COMMENT 'Canción final libre durante semana de visita de circuito';
