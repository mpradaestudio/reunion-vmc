-- ── Actualización v11: horario de reuniones ─────────────────────
-- Agrega día y hora para Reunión Entre Semana y Reunión Fin de Semana
-- a la tabla configuracion.

ALTER TABLE `configuracion`
    ADD COLUMN `dia_entre_semana`  VARCHAR(20)  NULL DEFAULT NULL AFTER `nombre_congregacion`,
    ADD COLUMN `hora_entre_semana` VARCHAR(10)  NULL DEFAULT NULL AFTER `dia_entre_semana`,
    ADD COLUMN `dia_fin_semana`    VARCHAR(20)  NULL DEFAULT NULL AFTER `hora_entre_semana`,
    ADD COLUMN `hora_fin_semana`   VARCHAR(10)  NULL DEFAULT NULL AFTER `dia_fin_semana`;
