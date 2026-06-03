<?php
/**
 * API REST para gestión de asignaciones
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

class AsignacionesAPI {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Asignar rol general (Presidente, Oración inicial, Oración final)
     */
    public function asignarRol($programaId, $rol, $personaId) {
        try {
            // Verificar si ya existe asignación para este rol
            $existe = fetchOne(
                "SELECT id FROM asignaciones_roles WHERE programa_id = ? AND rol = ?",
                [$programaId, $rol]
            );
            
            if ($existe) {
                // Actualizar
                $stmt = $this->pdo->prepare("
                    UPDATE asignaciones_roles SET persona_id = ? 
                    WHERE programa_id = ? AND rol = ?
                ");
                $stmt->execute([$personaId, $programaId, $rol]);
            } else {
                // Insertar
                $stmt = $this->pdo->prepare("
                    INSERT INTO asignaciones_roles (programa_id, rol, persona_id)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$programaId, $rol, $personaId]);
            }
            
            return ['success' => true, 'message' => 'Rol asignado correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Desasignar rol general
     */
    public function desasignarRol($programaId, $rol) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM asignaciones_roles 
                WHERE programa_id = ? AND rol = ?
            ");
            $stmt->execute([$programaId, $rol]);
            
            return ['success' => true, 'message' => 'Rol desasignado correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Asignar persona a una parte del programa
     */
    public function asignarParte($seccionId, $personaId, $rol = 'Asignado', $orden = 1) {
        try {
            // Verificar si ya existe una asignación para este orden
            $existe = fetchOne(
                "SELECT id FROM asignaciones_partes WHERE seccion_id = ? AND orden_presentador = ?",
                [$seccionId, $orden]
            );
            
            if ($existe) {
                // Actualizar
                $stmt = $this->pdo->prepare("
                    UPDATE asignaciones_partes SET persona_id = ?, rol = ?
                    WHERE seccion_id = ? AND orden_presentador = ?
                ");
                $stmt->execute([$personaId, $rol, $seccionId, $orden]);
            } else {
                // Insertar
                $stmt = $this->pdo->prepare("
                    INSERT INTO asignaciones_partes (seccion_id, persona_id, rol, orden_presentador)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$seccionId, $personaId, $rol, $orden]);
            }
            
            return ['success' => true, 'message' => 'Parte asignada correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Desasignar persona de una parte
     */
    public function desasignarParte($asignacionId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM asignaciones_partes WHERE id = ?");
            $stmt->execute([$asignacionId]);
            
            return ['success' => true, 'message' => 'Asignación eliminada correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Desasignar por sección y orden
     */
    public function desasignarPorSeccionOrden($seccionId, $orden) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM asignaciones_partes 
                WHERE seccion_id = ? AND orden_presentador = ?
            ");
            $stmt->execute([$seccionId, $orden]);
            
            return ['success' => true, 'message' => 'Asignación eliminada correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener personas disponibles para un tipo de asignación
     */
    public function obtenerPersonasDisponibles($tipoAsignacion) {
        try {
            $sql = "
                SELECT DISTINCT p.id, p.nombre, p.apellido,
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_completo,
                       pr.nombre as perfil
                FROM personas p
                INNER JOIN perfiles pr ON p.perfil_id = pr.id
                LEFT JOIN persona_partes_disponibles ppd ON p.id = ppd.persona_id
                WHERE p.activo = 1
            ";
            
            // Filtrar según tipo de asignación
            if ($tipoAsignacion && $tipoAsignacion !== 'Todos') {
                $sql .= " AND ppd.tipo_parte = ? AND ppd.puede_presentar = 1";
                $params = [$tipoAsignacion];
            } else {
                $params = [];
            }
            
            $sql .= " ORDER BY p.nombre, p.apellido";
            
            $personas = fetchAll($sql, $params);
            
            return ['success' => true, 'data' => $personas];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// Procesar petición
$api = new AsignacionesAPI();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'personas_disponibles') {
        $tipoAsignacion = $_GET['tipo'] ?? 'Todos';
        $resultado = $api->obtenerPersonasDisponibles($tipoAsignacion);
    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} elseif ($method === 'POST') {
    $datos = $_POST;
    $action = $datos['action'] ?? '';
    
    if ($action === 'asignar_rol') {
        $resultado = $api->asignarRol(
            $datos['programa_id'],
            $datos['rol'],
            $datos['persona_id']
        );
        
    } elseif ($action === 'desasignar_rol') {
        $resultado = $api->desasignarRol(
            $datos['programa_id'],
            $datos['rol']
        );
        
    } elseif ($action === 'asignar_parte') {
        $resultado = $api->asignarParte(
            $datos['seccion_id'],
            $datos['persona_id'],
            $datos['rol'] ?? 'Asignado',
            $datos['orden'] ?? 1
        );
        
    } elseif ($action === 'desasignar_parte') {
        if (isset($datos['asignacion_id'])) {
            $resultado = $api->desasignarParte($datos['asignacion_id']);
        } elseif (isset($datos['seccion_id']) && isset($datos['orden'])) {
            $resultado = $api->desasignarPorSeccionOrden($datos['seccion_id'], $datos['orden']);
        } else {
            $resultado = ['success' => false, 'message' => 'Faltan parámetros'];
        }
        
    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} else {
    $resultado = ['success' => false, 'message' => 'Método no permitido'];
}

jsonResponse($resultado);
