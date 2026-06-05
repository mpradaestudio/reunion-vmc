-- ── Actualización v12: tabla eventos_especiales ─────────────
-- Almacena las fechas de eventos que bloquearán/marcarán semanas
-- en los programas entre semana y fin de semana.
--
-- Tipos y límites por año:
--   regional      → 1  (3 días: fecha_inicio + fecha_fin)
--   circuito      → 2  (1 día:  fecha_inicio = fecha_fin)
--   visita         → 2  (martes–domingo: fecha_inicio + fecha_fin)
--   conmemoracion → 1  (1 día:  fecha_inicio = fecha_fin)

CREATE TABLE IF NOT EXISTS `eventos_especiales` (
    `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `tipo`         ENUM('regional','circuito','visita','conmemoracion') NOT NULL,
    `fecha_inicio` DATE             NOT NULL,
    `fecha_fin`    DATE             NOT NULL,
    `notas`        VARCHAR(255)     NULL DEFAULT NULL,
    `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tipo`        (`tipo`),
    KEY `idx_fecha_inicio` (`fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
