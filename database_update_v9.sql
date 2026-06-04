-- ============================================================
-- Migración v9: tabla bosquejos + columna dp_bosquejo_id en programas_fds
-- ============================================================

CREATE TABLE IF NOT EXISTS bosquejos (
    id      INT PRIMARY KEY AUTO_INCREMENT,
    numero  INT           NOT NULL,
    titulo  VARCHAR(255)  NOT NULL,
    activo  TINYINT(1)    NOT NULL DEFAULT 1,
    UNIQUE KEY uq_bosquejo_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vincular bosquejo al Discurso Público de cada semana FDS
ALTER TABLE programas_fds
    ADD COLUMN IF NOT EXISTS dp_bosquejo_id INT DEFAULT NULL AFTER dp_cancion,
    ADD CONSTRAINT fk_fds_bosquejo
        FOREIGN KEY (dp_bosquejo_id) REFERENCES bosquejos(id) ON DELETE SET NULL;


-- Campos "No presentar" en bosquejos (agregados en v9 revisión 2)
ALTER TABLE bosquejos
    ADD COLUMN IF NOT EXISTS no_presentar      TINYINT(1)   NOT NULL DEFAULT 0 AFTER activo,
    ADD COLUMN IF NOT EXISTS nota_no_presentar VARCHAR(500) DEFAULT NULL       AFTER no_presentar;
