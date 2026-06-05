<?php
/**
 * API REST — Privilegios
 * Acciones: list | create | delete | toggle_activo
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $rows = fetchAll("SELECT * FROM privilegios ORDER BY orden, nombre");
        jsonResponse(['success' => true, 'data' => $rows]);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo    = getDBConnection();

    // ── Crear privilegio ──────────────────────────────────────────
    if ($action === 'create') {
        $nombre = trim(sanitizeInput($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            jsonResponse(['success' => false, 'message' => 'El nombre es obligatorio']);
        }

        // Calcular el siguiente orden
        $maxOrden = fetchOne("SELECT COALESCE(MAX(orden),0)+1 AS siguiente FROM privilegios");
        $siguiente = $maxOrden['siguiente'] ?? 1;

        try {
            $pdo->prepare("INSERT INTO privilegios (nombre, orden) VALUES (?, ?)")
                ->execute([$nombre, $siguiente]);
            $nuevo = fetchOne("SELECT * FROM privilegios WHERE id = ?", [$pdo->lastInsertId()]);
            jsonResponse(['success' => true, 'message' => 'Privilegio creado', 'data' => $nuevo]);
        } catch (Exception $e) {
            // Posible nombre duplicado
            jsonResponse(['success' => false, 'message' => 'Ya existe un privilegio con ese nombre']);
        }
    }

    // ── Eliminar privilegio ───────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID no válido']);
        }

        // Comprobar que no tenga personas asignadas
        $uso = fetchOne("SELECT COUNT(*) AS total FROM persona_privilegios WHERE privilegio_id = ?", [$id]);
        if ((int)$uso['total'] > 0) {
            jsonResponse([
                'success' => false,
                'message' => 'No se puede eliminar: hay ' . $uso['total'] . ' persona(s) con este privilegio.'
            ]);
        }

        $pdo->prepare("DELETE FROM privilegios WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Privilegio eliminado']);
    }

    // ── Actualizar nombre ─────────────────────────────────────────
    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim(sanitizeInput($_POST['nombre'] ?? ''));
        if (!$id || $nombre === '') {
            jsonResponse(['success' => false, 'message' => 'Datos no válidos']);
        }
        try {
            $pdo->prepare("UPDATE privilegios SET nombre = ? WHERE id = ?")
                ->execute([$nombre, $id]);
            jsonResponse(['success' => true, 'message' => 'Privilegio actualizado']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Nombre ya en uso']);
        }
    }

    // ── Reordenar (drag & drop) ───────────────────────────────────
    if ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            jsonResponse(['success' => false, 'message' => 'IDs no válidos']);
        }
        $stmt = $pdo->prepare("UPDATE privilegios SET orden = ? WHERE id = ?");
        foreach ($ids as $pos => $id) {
            $stmt->execute([$pos + 1, (int)$id]);
        }
        jsonResponse(['success' => true, 'message' => 'Orden guardado']);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
