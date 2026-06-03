-- Base de datos para Sistema de Programación de Reuniones
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS reunion_programador CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE reunion_programador;

-- Tabla de configuración general
CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_congregacion VARCHAR(255) NOT NULL DEFAULT 'Congregación',
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar configuración por defecto
INSERT INTO configuracion (nombre_congregacion) VALUES ('Congregación Nombre');

-- Tabla de perfiles
CREATE TABLE IF NOT EXISTS perfiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar perfiles predefinidos
INSERT INTO perfiles (nombre, descripcion) VALUES
('Anciano', 'Anciano de la congregación'),
('Siervo Ministerial', 'Siervo ministerial de la congregación'),
('Precursor', 'Precursor regular'),
('Publicador', 'Publicador');

-- Tabla de personas
CREATE TABLE IF NOT EXISTS personas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    perfil_id INT NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (perfil_id) REFERENCES perfiles(id) ON DELETE RESTRICT,
    INDEX idx_activo (activo),
    INDEX idx_perfil (perfil_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de partes que puede presentar cada persona
CREATE TABLE IF NOT EXISTS persona_partes_disponibles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    persona_id INT NOT NULL,
    tipo_parte VARCHAR(100) NOT NULL,
    puede_presentar TINYINT(1) DEFAULT 1,
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_persona_parte (persona_id, tipo_parte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de relación muchos-a-muchos entre personas y perfiles
CREATE TABLE IF NOT EXISTS persona_perfiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    persona_id INT NOT NULL,
    perfil_id INT NOT NULL,
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    FOREIGN KEY (perfil_id) REFERENCES perfiles(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_persona_perfil (persona_id, perfil_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de programas semanales (información extraída de jw.org)
CREATE TABLE IF NOT EXISTS programas_semanales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha_inicio DATE NOT NULL UNIQUE,
    fecha_fin DATE NOT NULL,
    titulo_semana VARCHAR(255) NOT NULL,
    referencia_biblica VARCHAR(100),
    cancion_inicial INT,
    cancion_media INT,
    cancion_final INT,
    contenido_json LONGTEXT,
    url_fuente VARCHAR(500),
    fecha_scraping TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de secciones del programa (TESOROS, SEAMOS MEJORES, NUESTRA VIDA)
CREATE TABLE IF NOT EXISTS programa_secciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    programa_id INT NOT NULL,
    orden INT NOT NULL,
    seccion VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    duracion INT,
    tipo_asignacion VARCHAR(50),
    notas TEXT,
    FOREIGN KEY (programa_id) REFERENCES programas_semanales(id) ON DELETE CASCADE,
    INDEX idx_programa (programa_id),
    INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de asignaciones de roles generales (Presidente, Oración, Conductor)
CREATE TABLE IF NOT EXISTS asignaciones_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    programa_id INT NOT NULL,
    rol VARCHAR(50) NOT NULL,
    persona_id INT,
    notas TEXT,
    FOREIGN KEY (programa_id) REFERENCES programas_semanales(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE SET NULL,
    UNIQUE KEY unique_programa_rol (programa_id, rol),
    INDEX idx_programa (programa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de asignaciones de partes del programa
CREATE TABLE IF NOT EXISTS asignaciones_partes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seccion_id INT NOT NULL,
    persona_id INT,
    rol VARCHAR(50) DEFAULT 'Asignado',
    orden_presentador INT DEFAULT 1,
    notas TEXT,
    FOREIGN KEY (seccion_id) REFERENCES programa_secciones(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE SET NULL,
    INDEX idx_seccion (seccion_id),
    INDEX idx_persona (persona_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de historial de scraping
CREATE TABLE IF NOT EXISTS historial_scraping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha_scraping TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    url_procesada VARCHAR(500),
    num_programas_extraidos INT DEFAULT 0,
    estado VARCHAR(50) DEFAULT 'exitoso',
    mensaje TEXT,
    INDEX idx_fecha (fecha_scraping)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Vista para obtener información completa de personas
CREATE OR REPLACE VIEW vista_personas_completa AS
SELECT 
    p.id,
    p.nombre,
    p.apellido,
    CONCAT(p.nombre, ' ', p.apellido) as nombre_completo,
    p.telefono,
    p.email,
    p.activo,
    p.notas,
    pf.nombre as perfil,
    pf.id as perfil_id,
    p.created_at,
    p.updated_at
FROM personas p
INNER JOIN perfiles pf ON p.perfil_id = pf.id;

-- Vista para obtener programas con asignaciones
CREATE OR REPLACE VIEW vista_programas_asignados AS
SELECT 
    ps.id as programa_id,
    ps.fecha_inicio,
    ps.fecha_fin,
    ps.titulo_semana,
    ps.referencia_biblica,
    ps.cancion_inicial,
    ps.cancion_media,
    ps.cancion_final,
    COUNT(DISTINCT ar.id) as roles_asignados,
    COUNT(DISTINCT ap.id) as partes_asignadas
FROM programas_semanales ps
LEFT JOIN asignaciones_roles ar ON ps.id = ar.programa_id
LEFT JOIN programa_secciones sec ON ps.id = sec.programa_id
LEFT JOIN asignaciones_partes ap ON sec.id = ap.seccion_id
GROUP BY ps.id, ps.fecha_inicio, ps.fecha_fin, ps.titulo_semana, ps.referencia_biblica, 
         ps.cancion_inicial, ps.cancion_media, ps.cancion_final
ORDER BY ps.fecha_inicio;
