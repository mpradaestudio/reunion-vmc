-- ============================================================
-- Migración v6: módulo Privilegios
-- ============================================================

-- Tabla maestra de privilegios (gestionable desde Configuración)
CREATE TABLE IF NOT EXISTS privilegios (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nombre     VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo     TINYINT(1)  NOT NULL DEFAULT 1,
    orden      INT         NOT NULL DEFAULT 0,
    created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_privilegio_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar ejemplos iniciales
INSERT INTO privilegios (nombre, orden) VALUES
    ('Acomodador',  1),
    ('Vigilancia',  2),
    ('Micrófonos',  3);

-- Tabla relacional persona ↔ privilegios
CREATE TABLE IF NOT EXISTS persona_privilegios (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    persona_id   INT NOT NULL,
    privilegio_id INT NOT NULL,
    FOREIGN KEY (persona_id)   REFERENCES personas(id)   ON DELETE CASCADE,
    FOREIGN KEY (privilegio_id) REFERENCES privilegios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_persona_privilegio (persona_id, privilegio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
