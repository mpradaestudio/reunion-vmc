-- ============================================================
-- Migración v8: Reunión Fin de Semana
-- ============================================================

-- Tabla maestra de semanas FDS
-- Vinculada opcionalmente a la semana entre-semana via fecha_inicio
CREATE TABLE IF NOT EXISTS programas_fds (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL,
    -- Discurso público
    dp_tema         VARCHAR(255) DEFAULT NULL,
    dp_cancion      VARCHAR(20)  DEFAULT NULL,
    -- Metadata
    notas           TEXT         DEFAULT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fds_fecha (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Asignaciones FDS: cada rol por semana
-- roles: DP_Orador, DP_Presidente, EA_Conductor, EA_Lector, EA_Oracion, EA_Hospitalidad
CREATE TABLE IF NOT EXISTS asignaciones_fds (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    programa_fds_id INT NOT NULL,
    rol             VARCHAR(50)  NOT NULL,
    persona_id      INT          DEFAULT NULL,   -- NULL = sin asignar
    nombre_libre    VARCHAR(150) DEFAULT NULL,   -- para oradores externos / hospitalidad texto libre
    FOREIGN KEY (programa_fds_id) REFERENCES programas_fds(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id)      REFERENCES personas(id)       ON DELETE SET NULL,
    UNIQUE KEY uq_fds_rol (programa_fds_id, rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
