<?php
/**
 * API REST — Perfiles de Personas
 * Acciones: list | create | delete | update | reorder
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $rows = fetchAll("SELECT * FROM perfiles ORDER BY orden, nombre");
        jsonResponse(['success' => true, 'data' => $rows]);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo    = getDBConnection();

    // ── Crear perfil ──────────────────────────────────────────────
    if ($action === 'create') {
        $nombre = trim(sanitizeInput($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            jsonResponse(['success' => false, 'message' => 'El nombre es obligatorio']);
        }

        $maxOrden  = fetchOne("SELECT COALESCE(MAX(orden),0)+1 AS sig FROM perfiles");
        $siguiente = $maxOrden['sig'] ?? 1;

        try {
            $pdo->prepare("INSERT INTO perfiles (nombre, descripcion, orden) VALUES (?, '', ?)")
                ->execute([$nombre, $siguiente]);
            $nuevo = fetchOne("SELECT * FROM perfiles WHERE id = ?", [$pdo->lastInsertId()]);
            jsonResponse(['success' => true, 'message' => 'Perfil creado', 'data' => $nuevo]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Ya existe un perfil con ese nombre']);
        }
    }

    // ── Eliminar perfil ───────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID no válido']);
        }

        // 1. Quitar de persona_perfiles (relación many-to-many)
        $pdo->prepare("DELETE FROM persona_perfiles WHERE perfil_id = ?")->execute([$id]);

        // 2. Limpiar personas.perfil_id (columna legacy) — si es el único perfil,
        //    intentar reasignar al primer perfil restante de persona_perfiles;
        //    si no queda ninguno, dejar el campo en NULL.
        $afectadas = $pdo->prepare("SELECT id FROM personas WHERE perfil_id = ?");
        $afectadas->execute([$id]);
        $personasAfectadas = $afectadas->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($personasAfectadas)) {
            $stmtReasig = $pdo->prepare("
                SELECT perfil_id FROM persona_perfiles
                WHERE persona_id = ?
                LIMIT 1
            ");
            $stmtUpdate = $pdo->prepare("UPDATE personas SET perfil_id = ? WHERE id = ?");

            foreach ($personasAfectadas as $pid) {
                $stmtReasig->execute([$pid]);
                $nuevo = $stmtReasig->fetchColumn(); // false si no queda ninguno
                $stmtUpdate->execute([$nuevo ?: null, $pid]);
            }
        }

        // 3. Eliminar el perfil
        $pdo->prepare("DELETE FROM perfiles WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Perfil eliminado correctamente']);
    }

    // ── Actualizar nombre ─────────────────────────────────────────
    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim(sanitizeInput($_POST['nombre'] ?? ''));
        if (!$id || $nombre === '') {
            jsonResponse(['success' => false, 'message' => 'Datos no válidos']);
        }
        try {
            $pdo->prepare("UPDATE perfiles SET nombre = ? WHERE id = ?")
                ->execute([$nombre, $id]);
            jsonResponse(['success' => true, 'message' => 'Perfil actualizado']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Nombre ya en uso']);
        }
    }

    // ── Reordenar (drag & drop) ───────────────────────────────────
    // Recibe ids[] con el nuevo orden
    if ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            jsonResponse(['success' => false, 'message' => 'IDs no válidos']);
        }
        $stmt = $pdo->prepare("UPDATE perfiles SET orden = ? WHERE id = ?");
        foreach ($ids as $pos => $id) {
            $stmt->execute([$pos + 1, (int)$id]);
        }
        jsonResponse(['success' => true, 'message' => 'Orden guardado']);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
