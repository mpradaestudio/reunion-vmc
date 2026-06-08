<?php
/**
 * API REST — Programas Fin de Semana
 * Acciones POST: create | update | delete | save_asignacion
 * Acciones GET:  list | get
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();

/* ── GET ─────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    // Listar semanas
    if ($action === 'list') {
        $rows = fetchAll("
            SELECT p.*,
                   (SELECT a.persona_id  FROM asignaciones_fds a WHERE a.programa_fds_id = p.id AND a.rol = 'DP_Orador'     LIMIT 1) AS orador_id,
                   (SELECT CONCAT(pe.nombre,' ',pe.apellido) FROM asignaciones_fds a LEFT JOIN personas pe ON pe.id = a.persona_id WHERE a.programa_fds_id = p.id AND a.rol = 'DP_Orador' LIMIT 1) AS orador_nombre,
                   (SELECT a.nombre_libre FROM asignaciones_fds a WHERE a.programa_fds_id = p.id AND a.rol = 'DP_Orador'     LIMIT 1) AS orador_libre,
                   (SELECT CONCAT(pe.nombre,' ',pe.apellido) FROM asignaciones_fds a LEFT JOIN personas pe ON pe.id = a.persona_id WHERE a.programa_fds_id = p.id AND a.rol = 'EA_Conductor' LIMIT 1) AS conductor_nombre,
                   (SELECT CONCAT(pe.nombre,' ',pe.apellido) FROM asignaciones_fds a LEFT JOIN personas pe ON pe.id = a.persona_id WHERE a.programa_fds_id = p.id AND a.rol = 'EA_Lector' LIMIT 1) AS lector_nombre
            FROM programas_fds p
            ORDER BY p.fecha_inicio DESC
        ");
        jsonResponse(['success' => true, 'data' => $rows]);
    }

    // Obtener una semana con todas sus asignaciones
    if ($action === 'get' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $prog = fetchOne("SELECT * FROM programas_fds WHERE id = ?", [$id]);
        if (!$prog) {
            jsonResponse(['success' => false, 'message' => 'Semana no encontrada'], 404);
        }
        $asigs = fetchAll("
            SELECT a.rol, a.persona_id, a.nombre_libre,
                   CONCAT(p.nombre,' ',p.apellido) AS nombre_completo
            FROM asignaciones_fds a
            LEFT JOIN personas p ON p.id = a.persona_id
            WHERE a.programa_fds_id = ?
        ", [$id]);
        $prog['asignaciones'] = [];
        foreach ($asigs as $a) {
            $prog['asignaciones'][$a['rol']] = $a;
        }
        jsonResponse(['success' => true, 'data' => $prog]);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

/* ── POST ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Crear semana ──────────────────────────────────────────────
    if ($action === 'create') {
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $fechaFin    = $_POST['fecha_fin']    ?? '';
        if (!$fechaInicio || !$fechaFin) {
            jsonResponse(['success' => false, 'message' => 'Fechas obligatorias']);
        }
        try {
            $pdo->prepare("
                INSERT INTO programas_fds (fecha_inicio, fecha_fin, dp_tema, dp_cancion, notas)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $fechaInicio, $fechaFin,
                sanitizeInput($_POST['dp_tema']    ?? ''),
                sanitizeInput($_POST['dp_cancion'] ?? ''),
                sanitizeInput($_POST['notas']      ?? ''),
            ]);
            $nuevo = fetchOne("SELECT * FROM programas_fds WHERE id = ?", [$pdo->lastInsertId()]);
            jsonResponse(['success' => true, 'message' => 'Semana creada', 'data' => $nuevo]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Ya existe una semana con esa fecha de inicio']);
        }
    }

    // ── Actualizar semana ─────────────────────────────────────────
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID no válido']);
        // dp_bosquejo_id puede ser vacío (NULL) o un entero
        $bosquejoId = !empty($_POST['dp_bosquejo_id']) ? (int)$_POST['dp_bosquejo_id'] : null;
        try {
            $pdo->prepare("
                UPDATE programas_fds
                SET fecha_inicio=?, fecha_fin=?, dp_tema=?, dp_cancion=?,
                    dp_bosquejo_id=?, notas=?
                WHERE id=?
            ")->execute([
                $_POST['fecha_inicio'] ?? '',
                $_POST['fecha_fin']    ?? '',
                sanitizeInput($_POST['dp_tema']    ?? ''),
                sanitizeInput($_POST['dp_cancion'] ?? ''),
                $bosquejoId,
                sanitizeInput($_POST['notas']      ?? ''),
                $id,
            ]);
            jsonResponse(['success' => true, 'message' => 'Semana actualizada']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    // ── Eliminar semana ───────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID no válido']);
        $pdo->prepare("DELETE FROM asignaciones_fds WHERE programa_fds_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM programas_fds WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Semana eliminada']);
    }

    // ── Eliminar semanas en lote ──────────────────────────────────
    if ($action === 'delete_batch') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) jsonResponse(['success' => false, 'message' => 'Sin IDs']);
        $ids = array_map('intval', (array)$ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM asignaciones_fds WHERE programa_fds_id IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM programas_fds WHERE id IN ($placeholders)")->execute($ids);
        jsonResponse(['success' => true, 'message' => count($ids) . ' semana(s) eliminada(s)']);
    }

    // ── Autollenado ───────────────────────────────────────────────
    if ($action === 'autofill') {
        $programaFdsId = (int)($_POST['programa_fds_id'] ?? 0);
        if (!$programaFdsId) jsonResponse(['success' => false, 'message' => 'ID no válido']);

        // Roles a llenar con su capacidad FDS requerida
        $rolesConfig = [
            'DP_Presidente'     => 'FDS_Presidente',
            'DP_Oracion_Inicial'=> 'FDS_Oración',
            'DP_Oracion_Final'  => 'FDS_Oración',
            'EA_Conductor'      => 'FDS_Conductor',
            'EA_Lector'         => 'FDS_Lector',
        ];

        foreach ($rolesConfig as $rol => $capacidad) {
            // ¿Ya asignado?
            $existing = fetchOne(
                "SELECT id FROM asignaciones_fds WHERE programa_fds_id = ? AND rol = ? AND persona_id IS NOT NULL AND persona_id != 0",
                [$programaFdsId, $rol]
            );
            if ($existing) continue;

            // Buscar candidato aleatorio con esa capacidad
            $candidato = fetchOne("
                SELECT ppd.persona_id
                FROM persona_partes_disponibles ppd
                INNER JOIN personas p ON p.id = ppd.persona_id
                WHERE p.activo = 1
                  AND ppd.puede_presentar = 1
                  AND ppd.tipo_parte = ?
                ORDER BY RAND()
                LIMIT 1
            ", [$capacidad]);

            if (!$candidato) continue;

            $personaId = (int)$candidato['persona_id'];

            // Upsert
            $existe = fetchOne(
                "SELECT id FROM asignaciones_fds WHERE programa_fds_id = ? AND rol = ?",
                [$programaFdsId, $rol]
            );
            if ($existe) {
                $pdo->prepare("UPDATE asignaciones_fds SET persona_id = ?, nombre_libre = NULL WHERE programa_fds_id = ? AND rol = ?")
                    ->execute([$personaId, $programaFdsId, $rol]);
            } else {
                $pdo->prepare("INSERT INTO asignaciones_fds (programa_fds_id, rol, persona_id) VALUES (?, ?, ?)")
                    ->execute([$programaFdsId, $rol, $personaId]);
            }
        }

        jsonResponse(['success' => true, 'message' => 'Autollenado completado']);
    }

    // ── Guardar asignación ────────────────────────────────────────
    // Upsert: INSERT ... ON DUPLICATE KEY UPDATE
    if ($action === 'save_asignacion') {
        $programaId  = (int)($_POST['programa_fds_id'] ?? 0);
        $rol         = sanitizeInput($_POST['rol'] ?? '');
        $personaId   = !empty($_POST['persona_id'])   ? (int)$_POST['persona_id']   : null;
        $nombreLibre = sanitizeInput($_POST['nombre_libre'] ?? '');

        if (!$programaId || !$rol) {
            jsonResponse(['success' => false, 'message' => 'Datos incompletos']);
        }

        try {
            $pdo->prepare("
                INSERT INTO asignaciones_fds (programa_fds_id, rol, persona_id, nombre_libre)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE persona_id=VALUES(persona_id), nombre_libre=VALUES(nombre_libre)
            ")->execute([$programaId, $rol, $personaId, $nombreLibre ?: null]);

            // Devolver nombre resuelto
            $nombre = $nombreLibre;
            if ($personaId) {
                $p = fetchOne("SELECT CONCAT(nombre,' ',apellido) AS n FROM personas WHERE id=?", [$personaId]);
                $nombre = $p['n'] ?? '';
            }

            jsonResponse(['success' => true, 'message' => 'Asignación guardada', 'nombre' => $nombre]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar asignación']);
        }
    }

    // ── Quitar asignación ─────────────────────────────────────────
    if ($action === 'quitar_asignacion') {
        $programaId = (int)($_POST['programa_fds_id'] ?? 0);
        $rol        = sanitizeInput($_POST['rol'] ?? '');
        if (!$programaId || !$rol) {
            jsonResponse(['success' => false, 'message' => 'Datos incompletos']);
        }
        $pdo->prepare("DELETE FROM asignaciones_fds WHERE programa_fds_id=? AND rol=?")
            ->execute([$programaId, $rol]);
        jsonResponse(['success' => true, 'message' => 'Asignación eliminada']);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
