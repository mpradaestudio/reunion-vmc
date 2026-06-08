<?php
/**
 * API REST para gestión de personas
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

class PersonasAPI {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Listar todas las personas
     */
    public function listar($filtros = []) {
        try {
            $sql = "SELECT * FROM vista_personas_completa WHERE 1=1";
            $params = [];
            
            // Filtro por activo
            if (isset($filtros['activo'])) {
                $sql .= " AND activo = ?";
                $params[] = $filtros['activo'];
            }
            
            // Filtro por perfil
            if (isset($filtros['perfil_id']) && $filtros['perfil_id'] > 0) {
                $sql .= " AND perfil_id = ?";
                $params[] = $filtros['perfil_id'];
            }
            
            $sql .= " ORDER BY nombre, apellido";
            
            $personas = fetchAll($sql, $params);
            
            return ['success' => true, 'data' => $personas];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al listar personas: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener una persona por ID
     */
    public function obtener($id) {
        try {
            $persona = fetchOne("SELECT * FROM vista_personas_completa WHERE id = ?", [$id]);
            
            if (!$persona) {
                return ['success' => false, 'message' => 'Persona no encontrada'];
            }
            
            // Obtener partes disponibles
            $partes = fetchAll(
                "SELECT tipo_parte, puede_presentar FROM persona_partes_disponibles WHERE persona_id = ?",
                [$id]
            );
            
            $persona['partes_disponibles'] = $partes;

            // Obtener perfiles múltiples (tabla persona_perfiles)
            $perfilesRows = fetchAll(
                "SELECT perfil_id FROM persona_perfiles WHERE persona_id = ?",
                [$id]
            );
            $perfilIds = array_map(function ($r) { return (int)$r['perfil_id']; }, $perfilesRows);
            // Respaldo: si no hay registros en persona_perfiles, usar el perfil_id principal
            if (empty($perfilIds) && !empty($persona['perfil_id'])) {
                $perfilIds = [(int)$persona['perfil_id']];
            }
            $persona['perfil_ids'] = $perfilIds;

            // Obtener privilegios asignados
            $privRows = [];
            try {
                $privRows = fetchAll(
                    "SELECT privilegio_id FROM persona_privilegios WHERE persona_id = ?",
                    [$id]
                );
            } catch (Exception $e) { /* tabla aún no existe */ }
            $persona['privilegio_ids'] = array_map(
                fn($r) => (int)$r['privilegio_id'], $privRows
            );

            return ['success' => true, 'data' => $persona];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener persona: ' . $e->getMessage()];
        }
    }
    
    /**
     * Crear nueva persona
     */
    public function crear($datos) {
        try {
            // Validar datos requeridos
            if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['perfil_id'])) {
                return ['success' => false, 'message' => 'Faltan datos requeridos'];
            }
            
            // Insertar persona
            $stmt = $this->pdo->prepare("
                INSERT INTO personas (nombre, apellido, perfil_id, telefono, email, activo, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                sanitizeInput($datos['nombre']),
                sanitizeInput($datos['apellido']),
                $datos['perfil_id'],
                sanitizeInput($datos['telefono'] ?? ''),
                sanitizeInput($datos['email'] ?? ''),
                isset($datos['activo']) ? (int)$datos['activo'] : 1,
                sanitizeInput($datos['notas'] ?? '')
            ]);
            
            $personaId = $this->pdo->lastInsertId();
            
            // Guardar partes disponibles
            if (!empty($datos['partes_disponibles'])) {
                $this->actualizarPartesDisponibles($personaId, $datos['partes_disponibles']);
            }
            
            return ['success' => true, 'message' => 'Persona creada exitosamente', 'id' => $personaId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear persona: ' . $e->getMessage()];
        }
    }
    
    /**
     * Actualizar persona existente
     */
    public function actualizar($id, $datos) {
        try {
            // Verificar que existe
            $existe = fetchOne("SELECT id FROM personas WHERE id = ?", [$id]);
            if (!$existe) {
                return ['success' => false, 'message' => 'Persona no encontrada'];
            }
            
            // Actualizar persona
            $stmt = $this->pdo->prepare("
                UPDATE personas SET
                    nombre = ?,
                    apellido = ?,
                    perfil_id = ?,
                    telefono = ?,
                    email = ?,
                    activo = ?,
                    notas = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                sanitizeInput($datos['nombre']),
                sanitizeInput($datos['apellido']),
                $datos['perfil_id'],
                sanitizeInput($datos['telefono'] ?? ''),
                sanitizeInput($datos['email'] ?? ''),
                isset($datos['activo']) ? (int)$datos['activo'] : 1,
                sanitizeInput($datos['notas'] ?? ''),
                $id
            ]);
            
            // Actualizar partes disponibles
            if (isset($datos['partes_disponibles'])) {
                $this->actualizarPartesDisponibles($id, $datos['partes_disponibles']);
            }
            
            return ['success' => true, 'message' => 'Persona actualizada exitosamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar persona: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar varias personas en lote
     */
    public function bulkDelete(array $ids): array {
        if (empty($ids)) return ['success' => false, 'message' => 'Sin IDs'];
        $ids = array_map('intval', $ids);

        $eliminadas = 0;
        $errores    = [];
        foreach ($ids as $id) {
            // Verificar asignaciones
            $asig = fetchOne("SELECT COUNT(*) AS total FROM asignaciones_partes WHERE persona_id = ?", [$id]);
            if ((int)($asig['total'] ?? 0) > 0) {
                $p = fetchOne("SELECT CONCAT(nombre,' ',apellido) AS n FROM personas WHERE id = ?", [$id]);
                $errores[] = ($p['n'] ?? "ID $id") . ' tiene asignaciones activas';
                continue;
            }
            $this->pdo->prepare("DELETE FROM personas WHERE id = ?")->execute([$id]);
            $eliminadas++;
        }

        $msg = "Se eliminaron $eliminadas persona(s).";
        if ($errores) $msg .= ' Omitidas: ' . implode('; ', $errores);
        return ['success' => true, 'message' => $msg, 'eliminadas' => $eliminadas, 'errores' => $errores];
    }

    /**
     * Editar perfiles en lote (add | replace)
     */
    public function bulkPerfiles(array $ids, array $perfilIds, string $mode): array {
        if (empty($ids) || empty($perfilIds)) return ['success' => false, 'message' => 'Sin datos'];
        $ids       = array_map('intval', $ids);
        $perfilIds = array_map('intval', $perfilIds);

        $stmtDel = $this->pdo->prepare("DELETE FROM persona_perfiles WHERE persona_id = ?");
        $stmtIns = $this->pdo->prepare("INSERT IGNORE INTO persona_perfiles (persona_id, perfil_id) VALUES (?, ?)");

        foreach ($ids as $pid) {
            if ($mode === 'replace') {
                $stmtDel->execute([$pid]);
            }
            foreach ($perfilIds as $pfId) {
                $stmtIns->execute([$pid, $pfId]);
            }
        }

        $accion = $mode === 'replace' ? 'reemplazados' : 'agregados';
        return ['success' => true, 'message' => "Perfiles $accion en " . count($ids) . " persona(s)."];
    }

    /**
     * Editar privilegios en lote (add | replace)
     */
    public function bulkPrivilegios(array $ids, array $privilegioIds, string $mode): array {
        if (empty($ids) || empty($privilegioIds)) return ['success' => false, 'message' => 'Sin datos'];
        $ids          = array_map('intval', $ids);
        $privilegioIds = array_map('intval', $privilegioIds);

        $stmtDel = $this->pdo->prepare("DELETE FROM persona_privilegios WHERE persona_id = ?");
        $stmtIns = $this->pdo->prepare("INSERT IGNORE INTO persona_privilegios (persona_id, privilegio_id) VALUES (?, ?)");

        foreach ($ids as $pid) {
            if ($mode === 'replace') {
                $stmtDel->execute([$pid]);
            }
            foreach ($privilegioIds as $prvId) {
                $stmtIns->execute([$pid, $prvId]);
            }
        }

        $accion = $mode === 'replace' ? 'reemplazados' : 'agregados';
        return ['success' => true, 'message' => "Privilegios $accion en " . count($ids) . " persona(s)."];
    }

    /**
     * Eliminar persona
     */
    public function eliminar($id) {
        try {
            // Verificar si tiene asignaciones
            $asignaciones = fetchOne(
                "SELECT COUNT(*) as total FROM asignaciones_partes WHERE persona_id = ?",
                [$id]
            );
            
            if ($asignaciones['total'] > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar. La persona tiene asignaciones activas.'];
            }
            
            // Eliminar
            $stmt = $this->pdo->prepare("DELETE FROM personas WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Persona eliminada exitosamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar persona: ' . $e->getMessage()];
        }
    }
    
    /**
     * Actualizar partes que puede presentar una persona
     */
    private function actualizarPartesDisponibles($personaId, $partes) {
        try {
            // Eliminar partes anteriores
            $this->pdo->prepare("DELETE FROM persona_partes_disponibles WHERE persona_id = ?")
                      ->execute([$personaId]);
            
            // Insertar nuevas partes
            if (is_array($partes) && count($partes) > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar)
                    VALUES (?, ?, 1)
                ");
                
                foreach ($partes as $parte) {
                    $stmt->execute([$personaId, $parte]);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al actualizar partes disponibles: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener personas disponibles para una parte específica
     */
    public function obtenerDisponiblesParaParte($tipoParte) {
        try {
            $sql = "
                SELECT DISTINCT p.* 
                FROM vista_personas_completa p
                INNER JOIN persona_partes_disponibles ppd ON p.id = ppd.persona_id
                WHERE p.activo = 1 
                AND ppd.tipo_parte = ?
                AND ppd.puede_presentar = 1
                ORDER BY p.nombre, p.apellido
            ";
            
            $personas = fetchAll($sql, [$tipoParte]);
            
            return ['success' => true, 'data' => $personas];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// Procesar petición
$api = new PersonasAPI();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $filtros = [
            'activo' => $_GET['activo'] ?? null,
            'perfil_id' => $_GET['perfil_id'] ?? null
        ];
        $resultado = $api->listar($filtros);
        
    } elseif ($action === 'get' && isset($_GET['id'])) {
        $resultado = $api->obtener($_GET['id']);
        
    } elseif ($action === 'disponibles' && isset($_GET['tipo_parte'])) {
        $resultado = $api->obtenerDisponiblesParaParte($_GET['tipo_parte']);
        
    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} elseif ($method === 'POST') {
    $datos = $_POST;
    $action = $datos['action'] ?? 'create';
    
    if ($action === 'create') {
        $resultado = $api->crear($datos);
        
    } elseif ($action === 'update' && isset($datos['id'])) {
        $resultado = $api->actualizar($datos['id'], $datos);
        
    } elseif ($action === 'delete' && isset($datos['id'])) {
        $resultado = $api->eliminar($datos['id']);

    } elseif ($action === 'bulk_delete' && !empty($datos['ids'])) {
        $resultado = $api->bulkDelete((array)$datos['ids']);

    } elseif ($action === 'bulk_perfiles' && !empty($datos['ids']) && !empty($datos['perfil_ids'])) {
        $resultado = $api->bulkPerfiles(
            (array)$datos['ids'],
            (array)$datos['perfil_ids'],
            $datos['mode'] ?? 'add'
        );

    } elseif ($action === 'bulk_privilegios' && !empty($datos['ids']) && !empty($datos['privilegio_ids'])) {
        $resultado = $api->bulkPrivilegios(
            (array)$datos['ids'],
            (array)$datos['privilegio_ids'],
            $datos['mode'] ?? 'add'
        );

    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} else {
    $resultado = ['success' => false, 'message' => 'Método no permitido'];
}

jsonResponse($resultado);
