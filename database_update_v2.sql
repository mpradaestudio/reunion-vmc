-- ============================================================
-- ACTUALIZACIÓN v2: Perfiles múltiples por persona
-- Ejecutar este script UNA sola vez en phpMyAdmin sobre la base
-- de datos 'reunion_programador' (pestaña Importar o SQL).
-- No borra datos existentes.
-- ============================================================

USE reunion_programador;

-- Tabla de relación muchos-a-muchos entre personas y perfiles
CREATE TABLE IF NOT EXISTS persona_perfiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    persona_id INT NOT NULL,
    perfil_id INT NOT NULL,
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    FOREIGN KEY (perfil_id) REFERENCES perfiles(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_persona_perfil (persona_id, perfil_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Migrar el perfil actual (perfil_id) de cada persona a la nueva tabla
INSERT IGNORE INTO persona_perfiles (persona_id, perfil_id)
SELECT id, perfil_id FROM personas WHERE perfil_id IS NOT NULL;

SELECT 'Migración v2 completada: tabla persona_perfiles creada y poblada' AS mensaje;
