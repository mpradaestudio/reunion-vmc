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

        // Comprobar personas asignadas (tabla persona_perfiles o campo perfil_id)
        $usoNuevo = fetchOne(
            "SELECT COUNT(*) AS total FROM persona_perfiles WHERE perfil_id = ?", [$id]
        );
        $usoViejo = fetchOne(
            "SELECT COUNT(*) AS total FROM personas WHERE perfil_id = ?", [$id]
        );
        $total = ((int)($usoNuevo['total'] ?? 0)) + ((int)($usoViejo['total'] ?? 0));

        if ($total > 0) {
            jsonResponse([
                'success' => false,
                'message' => "No se puede eliminar: $total persona(s) tienen este perfil.",
            ]);
        }

        $pdo->prepare("DELETE FROM perfiles WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Perfil eliminado']);
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
