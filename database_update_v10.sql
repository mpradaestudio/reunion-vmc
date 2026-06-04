-- ============================================================
-- Migración v10: asegurar columnas no_presentar / nota_no_presentar
--
-- IMPORTANTE: La migración v9 incluía "ADD CONSTRAINT ... " junto con
-- "ADD COLUMN IF NOT EXISTS". MySQL NO soporta IF NOT EXISTS en
-- constraints, por lo que si el ALTER se ejecutó cuando la FK ya existía
-- (o falló a la mitad), las columnas no_presentar pudieron NO crearse.
--
-- Esta migración es 100% idempotente: agrega las columnas solo si faltan,
-- sin tocar constraints. Es seguro ejecutarla varias veces.
-- ============================================================

-- Agregar no_presentar si no existe
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'bosquejos'
      AND COLUMN_NAME  = 'no_presentar'
);
SET @sql := IF(@col_existe = 0,
    'ALTER TABLE bosquejos ADD COLUMN no_presentar TINYINT(1) NOT NULL DEFAULT 0 AFTER activo',
    'SELECT "columna no_presentar ya existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar nota_no_presentar si no existe
SET @col_existe2 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'bosquejos'
      AND COLUMN_NAME  = 'nota_no_presentar'
);
SET @sql2 := IF(@col_existe2 = 0,
    'ALTER TABLE bosquejos ADD COLUMN nota_no_presentar VARCHAR(500) DEFAULT NULL AFTER no_presentar',
    'SELECT "columna nota_no_presentar ya existe"'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
