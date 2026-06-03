-- Datos de demostración para el Programador de Reuniones
-- Ejecuta este archivo DESPUÉS de database.sql para agregar datos de prueba

USE reunion_programador;

-- Actualizar nombre de congregación
UPDATE configuracion SET nombre_congregacion = 'Congregación Central' WHERE id = 1;

-- Insertar personas de ejemplo
INSERT INTO personas (nombre, apellido, perfil_id, telefono, email, activo, notas) VALUES
('Juan', 'Pérez', 1, '555-0101', 'juan.perez@example.com', 1, 'Disponible para presidente'),
('María', 'González', 1, '555-0102', 'maria.gonzalez@example.com', 1, 'Excelente oradora'),
('Pedro', 'Ramírez', 2, '555-0103', 'pedro.ramirez@example.com', 1, 'Buen conductor'),
('Ana', 'Martínez', 2, '555-0104', 'ana.martinez@example.com', 1, 'Lectura clara'),
('Carlos', 'López', 3, '555-0105', 'carlos.lopez@example.com', 1, 'Discursante experimentado'),
('Laura', 'Fernández', 4, '555-0106', 'laura.fernandez@example.com', 1, 'Ayudante entusiasta'),
('Miguel', 'Sánchez', 1, '555-0107', 'miguel.sanchez@example.com', 1, 'Puede todas las partes'),
('Sofia', 'Torres', 4, '555-0108', 'sofia.torres@example.com', 1, 'Estudiante aplicada'),
('Diego', 'Vargas', 2, '555-0109', 'diego.vargas@example.com', 1, 'Oración expresiva'),
('Isabel', 'Castro', 3, '555-0110', 'isabel.castro@example.com', 1, 'Buena investigadora'),
('Roberto', 'Morales', 1, '555-0111', 'roberto.morales@example.com', 1, 'Presidente experimentado'),
('Carmen', 'Ruiz', 4, '555-0112', 'carmen.ruiz@example.com', 1, 'Ayudante confiable'),
('Fernando', 'Jiménez', 2, '555-0113', 'fernando.jimenez@example.com', 1, 'Lector claro'),
('Patricia', 'Herrera', 1, '555-0114', 'patricia.herrera@example.com', 1, 'Oradora experimentada'),
('Javier', 'Medina', 3, '555-0115', 'javier.medina@example.com', 1, 'Discursante público');

-- Asignar partes disponibles a las personas
-- Juan Pérez - Anciano (puede todo)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(1, 'Presidente', 1),
(1, 'Oración', 1),
(1, 'Conductor/Lector', 1),
(1, 'Asignado', 1);

-- María González - Anciana (puede todo)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(2, 'Oración', 1),
(2, 'Asignado', 1),
(2, 'Estudiante/Ayudante', 1);

-- Pedro Ramírez - Siervo (conductor y asignado)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(3, 'Oración', 1),
(3, 'Conductor/Lector', 1),
(3, 'Asignado', 1);

-- Ana Martínez - Sierva (estudiante y asignada)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(4, 'Asignado', 1),
(4, 'Estudiante/Ayudante', 1);

-- Carlos López - Discursante (partes principales)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(5, 'Presidente', 1),
(5, 'Oración', 1),
(5, 'Asignado', 1);

-- Laura Fernández - Ayudante
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(6, 'Estudiante/Ayudante', 1),
(6, 'Asignado', 1);

-- Miguel Sánchez - Anciano (puede todo)
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(7, 'Presidente', 1),
(7, 'Oración', 1),
(7, 'Conductor/Lector', 1),
(7, 'Asignado', 1);

-- Sofia Torres - Ayudante
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(8, 'Estudiante/Ayudante', 1),
(8, 'Asignado', 1);

-- Diego Vargas - Siervo
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(9, 'Oración', 1),
(9, 'Asignado', 1),
(9, 'Estudiante/Ayudante', 1);

-- Isabel Castro - Discursante
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(10, 'Asignado', 1),
(10, 'Estudiante/Ayudante', 1);

-- Roberto Morales - Anciano
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(11, 'Presidente', 1),
(11, 'Oración', 1),
(11, 'Conductor/Lector', 1),
(11, 'Asignado', 1);

-- Carmen Ruiz - Ayudante
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(12, 'Estudiante/Ayudante', 1),
(12, 'Asignado', 1);

-- Fernando Jiménez - Siervo
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(13, 'Conductor/Lector', 1),
(13, 'Asignado', 1),
(13, 'Estudiante/Ayudante', 1);

-- Patricia Herrera - Anciana
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(14, 'Oración', 1),
(14, 'Asignado', 1),
(14, 'Estudiante/Ayudante', 1);

-- Javier Medina - Discursante
INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar) VALUES
(15, 'Presidente', 1),
(15, 'Oración', 1),
(15, 'Asignado', 1);

-- Mensaje de confirmación
SELECT 'Datos de demostración insertados correctamente' AS mensaje;
SELECT COUNT(*) AS total_personas FROM personas;
SELECT COUNT(*) AS total_asignaciones_disponibles FROM persona_partes_disponibles;
