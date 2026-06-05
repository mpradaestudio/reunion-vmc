-- ============================================================
-- Migración v7: columna orden + reorder en tabla perfiles
-- ============================================================

-- Agregar columna orden (si no existe)
ALTER TABLE perfiles
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 0 AFTER descripcion;

-- Inicializar orden basado en el id existente
UPDATE perfiles SET orden = id WHERE orden = 0;
