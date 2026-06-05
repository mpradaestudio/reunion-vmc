<?php
/**
 * API REST para gestión de programas semanales
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

class ProgramasAPI {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Listar programas
     */
    public function listar($filtros = []) {
        try {
            $sql = "SELECT * FROM programas_semanales WHERE 1=1";
            $params = [];
            
            // Filtro por fecha
            if (isset($filtros['desde'])) {
                $sql .= " AND fecha_inicio >= ?";
                $params[] = $filtros['desde'];
            }
            
            if (isset($filtros['hasta'])) {
                $sql .= " AND fecha_inicio <= ?";
                $params[] = $filtros['hasta'];
            }
            
            // Filtro por mes
            if (isset($filtros['mes']) && isset($filtros['anio'])) {
                $sql .= " AND MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?";
                $params[] = $filtros['mes'];
                $params[] = $filtros['anio'];
            }
            
            $sql .= " ORDER BY fecha_inicio";
            
            $programas = fetchAll($sql, $params);
            
            return ['success' => true, 'data' => $programas];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al listar programas: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener programa con sus secciones y asignaciones
     */
    public function obtener($id) {
        try {
            // Obtener programa
            $programa = fetchOne("SELECT * FROM programas_semanales WHERE id = ?", [$id]);
            
            if (!$programa) {
                return ['success' => false, 'message' => 'Programa no encontrado'];
            }
            
            // Obtener secciones
            $secciones = fetchAll(
                "SELECT * FROM programa_secciones WHERE programa_id = ? ORDER BY orden",
                [$id]
            );
            
            // Obtener asignaciones de cada sección
            foreach ($secciones as &$seccion) {
                $asignaciones = fetchAll("
                    SELECT ap.*, p.nombre, p.apellido,
                           CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
                    FROM asignaciones_partes ap
                    LEFT JOIN personas p ON ap.persona_id = p.id
                    WHERE ap.seccion_id = ?
                    ORDER BY ap.orden_presentador
                ", [$seccion['id']]);
                
                $seccion['asignaciones'] = $asignaciones;
            }
            
            // Obtener roles generales
            $roles = fetchAll("
                SELECT ar.*, p.nombre, p.apellido,
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
                FROM asignaciones_roles ar
                LEFT JOIN personas p ON ar.persona_id = p.id
                WHERE ar.programa_id = ?
            ", [$id]);
            
            $programa['secciones'] = $secciones;
            $programa['roles'] = $roles;
            
            return ['success' => true, 'data' => $programa];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener programa: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar programa
     */
    public function eliminar($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM programas_semanales WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Programa eliminado exitosamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar programa: ' . $e->getMessage()];
        }
    }

    /**
     * Eliminar múltiples programas en lote
     */
    public function eliminarLote(array $ids) {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No se proporcionaron IDs'];
        }

        // Validar que todos sean enteros
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return ['success' => false, 'message' => 'IDs no válidos'];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare("DELETE FROM programas_semanales WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $eliminados = $stmt->rowCount();

            return [
                'success'    => true,
                'eliminados' => $eliminados,
                'message'    => "$eliminados programa(s) eliminado(s) exitosamente",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }
    
    /**
     * Autollenar asignaciones vacías con personas aleatorias
     * respetando partes_disponibles de cada persona.
     */
    public function autofill(int $programaId): array {
        try {
            // ── 1. Roles generales vacíos ────────────────────────────────
            $rolesConfig = [
                'Presidente'    => 'Presidente',
                'Oración inicial' => 'Oración',
                'Oración final'   => 'Oración',
            ];

            foreach ($rolesConfig as $rol => $capacidad) {
                // ¿Ya asignado?
                $existing = fetchOne(
                    "SELECT id FROM asignaciones_roles WHERE programa_id = ? AND rol = ?",
                    [$programaId, $rol]
                );
                if ($existing) continue;

                // Personas con esa capacidad
                $candidatos = fetchAll("
                    SELECT ppd.persona_id
                    FROM persona_partes_disponibles ppd
                    INNER JOIN personas p ON p.id = ppd.persona_id
                    WHERE p.activo = 1
                      AND ppd.puede_presentar = 1
                      AND ppd.tipo_parte = ?
                    ORDER BY RAND()
                    LIMIT 1
                ", [$capacidad]);

                if (empty($candidatos)) continue;

                $personaId = $candidatos[0]['persona_id'];

                // Upsert
                $existe = fetchOne(
                    "SELECT id FROM asignaciones_roles WHERE programa_id = ? AND rol = ?",
                    [$programaId, $rol]
                );
                if ($existe) {
                    $this->pdo->prepare("UPDATE asignaciones_roles SET persona_id = ? WHERE programa_id = ? AND rol = ?")
                        ->execute([$personaId, $programaId, $rol]);
                } else {
                    $this->pdo->prepare("INSERT INTO asignaciones_roles (programa_id, rol, persona_id) VALUES (?, ?, ?)")
                        ->execute([$programaId, $rol, $personaId]);
                }
            }

            // ── 2. Partes de secciones vacías ───────────────────────────
            $secciones = fetchAll(
                "SELECT * FROM programa_secciones WHERE programa_id = ? ORDER BY orden",
                [$programaId]
            );

            foreach ($secciones as $sec) {
                $tipo      = $sec['tipo_asignacion'];
                $titulo    = $sec['titulo'];
                $seccionNom = $sec['seccion'];
                $dosPersonas = in_array($tipo, ['Estudiante/Ayudante', 'Conductor/Lector']);
                $slots = $dosPersonas ? [1, 2] : [1];

                foreach ($slots as $orden) {
                    // ¿Ya asignado?
                    $existing = fetchOne(
                        "SELECT id FROM asignaciones_partes WHERE seccion_id = ? AND orden_presentador = ?",
                        [$sec['id'], $orden]
                    );
                    if ($existing) continue;

                    // Determinar capacidad
                    $cap = $this->capacidadRequerida($seccionNom, $titulo, $orden);
                    if (!$cap) continue;

                    $candidatos = fetchAll("
                        SELECT ppd.persona_id
                        FROM persona_partes_disponibles ppd
                        INNER JOIN personas p ON p.id = ppd.persona_id
                        WHERE p.activo = 1
                          AND ppd.puede_presentar = 1
                          AND ppd.tipo_parte = ?
                        ORDER BY RAND()
                        LIMIT 1
                    ", [$cap]);

                    if (empty($candidatos)) continue;

                    $personaId = $candidatos[0]['persona_id'];

                    $this->pdo->prepare("
                        INSERT INTO asignaciones_partes (seccion_id, persona_id, rol, orden_presentador)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE persona_id = VALUES(persona_id)
                    ")->execute([$sec['id'], $personaId, $tipo, $orden]);
                }
            }

            return ['success' => true, 'message' => 'Autollenado completado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en autollenado: ' . $e->getMessage()];
        }
    }

    /**
     * Replica la lógica de capacidadRequerida() de programa_detalle.php
     */
    private function capacidadRequerida(string $seccion, string $titulo, int $orden): ?string {
        $t = mb_strtolower($titulo, 'UTF-8');

        if ($seccion === 'TESOROS DE LA BIBLIA') {
            if (mb_strpos($t, 'perlas')   !== false) return 'Busquemos perlas escondidas';
            if (mb_strpos($t, 'lectura')  !== false) return 'Lectura de la Biblia';
            return 'Discurso Tesoros';
        }
        if ($seccion === 'SEAMOS MEJORES MAESTROS') {
            return ($orden == 1) ? 'Estudiante' : 'Ayudante';
        }
        if ($seccion === 'NUESTRA VIDA CRISTIANA') {
            if (mb_strpos($t, 'estudio b') !== false) {
                return ($orden == 1) ? 'Conductor' : 'Lector';
            }
            if (mb_strpos($t, 'necesidades') !== false) return 'Necesidades';
            return 'Partes';
        }
        return null;
    }

    /**
     * Obtener programas de un mes específico
     */
    public function obtenerPorMes($mes, $anio) {
        try {
            $programas = fetchAll("
                SELECT * FROM programas_semanales 
                WHERE MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?
                ORDER BY fecha_inicio
            ", [$mes, $anio]);
            
            // Obtener secciones y asignaciones para cada programa
            foreach ($programas as &$programa) {
                $resultado = $this->obtener($programa['id']);
                if ($resultado['success']) {
                    $programa['secciones'] = $resultado['data']['secciones'];
                    $programa['roles'] = $resultado['data']['roles'];
                }
            }
            
            return ['success' => true, 'data' => $programas];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// Procesar petición
$api = new ProgramasAPI();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $filtros = [
            'desde' => $_GET['desde'] ?? null,
            'hasta' => $_GET['hasta'] ?? null,
            'mes' => $_GET['mes'] ?? null,
            'anio' => $_GET['anio'] ?? null
        ];
        $resultado = $api->listar($filtros);
        
    } elseif ($action === 'get' && isset($_GET['id'])) {
        $resultado = $api->obtener($_GET['id']);
        
    } elseif ($action === 'mes' && isset($_GET['mes']) && isset($_GET['anio'])) {
        $resultado = $api->obtenerPorMes($_GET['mes'], $_GET['anio']);
        
    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} elseif ($method === 'POST') {
    $datos = $_POST;
    $action = $datos['action'] ?? '';
    
    if ($action === 'delete' && isset($datos['id'])) {
        $resultado = $api->eliminar($datos['id']);

    } elseif ($action === 'delete_batch' && !empty($datos['ids'])) {
        $ids = is_array($datos['ids']) ? $datos['ids'] : explode(',', $datos['ids']);
        $resultado = $api->eliminarLote($ids);

    } elseif ($action === 'autofill' && !empty($datos['programa_id'])) {
        $resultado = $api->autofill((int)$datos['programa_id']);

    } else {
        $resultado = ['success' => false, 'message' => 'Acción no válida'];
    }
    
} else {
    $resultado = ['success' => false, 'message' => 'Método no permitido'];
}

jsonResponse($resultado);
