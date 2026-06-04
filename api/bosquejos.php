<?php
/**
 * API REST — Bosquejos
 * GET  ?action=list              → todos (activos)
 * GET  ?action=search&q=texto   → búsqueda por número o palabras del título
 * POST action=create             → nuevo bosquejo
 * POST action=update             → editar número/título
 * POST action=delete             → eliminar (si no está en uso)
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();

/* ── GET ─────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    // ── Listar todos ─────────────────────────────────────────────
    if ($action === 'list') {
        $rows = fetchAll("SELECT id, numero, titulo FROM bosquejos WHERE activo=1 ORDER BY numero");
        jsonResponse(['success' => true, 'data' => $rows]);
    }

    // ── Búsqueda para Select2 y paginador ────────────────────────
    // Devuelve {results:[{id,text,numero,titulo}], pagination:{more}, total}
    if ($action === 'search') {
        $q       = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 30));
        // per_page=9999 significa "todo" (desde configuracion.php)
        if ($perPage >= 9999) { $page = 1; $perPage = 99999; }
        $offset = ($page - 1) * $perPage;

        if ($q === '') {
            $rows  = fetchAll("SELECT id, numero, titulo FROM bosquejos WHERE activo=1 ORDER BY numero LIMIT ? OFFSET ?", [$perPage, $offset]);
            $total = (int)(fetchOne("SELECT COUNT(*) AS n FROM bosquejos WHERE activo=1")['n'] ?? 0);
        } else {
            $like  = '%' . $q . '%';
            $rows  = fetchAll("
                SELECT id, numero, titulo
                FROM bosquejos
                WHERE activo=1
                  AND (CAST(numero AS CHAR) LIKE ? OR titulo LIKE ?)
                ORDER BY
                    CASE WHEN CAST(numero AS CHAR) LIKE ? THEN 0 ELSE 1 END,
                    numero
                LIMIT ? OFFSET ?
            ", [$like, $like, $like, $perPage, $offset]);
            $total = (int)(fetchOne("
                SELECT COUNT(*) AS n FROM bosquejos
                WHERE activo=1 AND (CAST(numero AS CHAR) LIKE ? OR titulo LIKE ?)
            ", [$like, $like])['n'] ?? 0);
        }

        $items = array_map(fn($r) => [
            'id'               => $r['id'],
            'text'             => $r['numero'] . ' — ' . $r['titulo'],
            'numero'           => $r['numero'],
            'titulo'           => $r['titulo'],
            'no_presentar'     => (int)($r['no_presentar'] ?? 0),
            'nota_no_presentar'=> $r['nota_no_presentar'] ?? '',
        ], $rows);

        jsonResponse([
            'results'    => $items,
            'total'      => $total,
            'pagination' => ['more' => ($offset + $perPage) < $total],
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

/* ── POST ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Crear ─────────────────────────────────────────────────────
    if ($action === 'create') {
        $numero = (int)($_POST['numero'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');   // sin sanitizeInput: guardamos el texto real
        if ($numero <= 0 || $titulo === '') {
            jsonResponse(['success' => false, 'message' => 'Número y título son obligatorios']);
        }
        try {
            $pdo->prepare("INSERT INTO bosquejos (numero, titulo) VALUES (?, ?)")
                ->execute([$numero, $titulo]);
            $nuevo = fetchOne("SELECT * FROM bosquejos WHERE id=?", [$pdo->lastInsertId()]);
            jsonResponse(['success' => true, 'message' => 'Bosquejo creado', 'data' => $nuevo]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Ya existe un bosquejo con ese número']);
        }
    }

    // ── Actualizar ────────────────────────────────────────────────
    if ($action === 'update') {
        $id              = (int)($_POST['id'] ?? 0);
        $numero          = (int)($_POST['numero'] ?? 0);
        $titulo          = trim($_POST['titulo'] ?? '');
        $noPresentar     = isset($_POST['no_presentar']) ? 1 : 0;
        $notaNoPresentar = trim($_POST['nota_no_presentar'] ?? '');

        if (!$id || $numero <= 0 || $titulo === '') {
            jsonResponse(['success' => false, 'message' => 'Datos no válidos']);
        }

        // Verificar conflicto de número con OTRO registro (no con el mismo)
        $conflicto = fetchOne(
            "SELECT id FROM bosquejos WHERE numero = ? AND id != ?",
            [$numero, $id]
        );
        if ($conflicto) {
            jsonResponse(['success' => false, 'message' => 'Número ya en uso por otro bosquejo']);
        }

        // Actualizar sin riesgo de falso conflicto en el índice UNIQUE
        $pdo->prepare("
            UPDATE bosquejos
            SET numero=?, titulo=?, no_presentar=?, nota_no_presentar=?
            WHERE id=?
        ")->execute([$numero, $titulo, $noPresentar, $notaNoPresentar ?: null, $id]);

        jsonResponse(['success' => true, 'message' => 'Bosquejo actualizado']);
    }

    // ── Eliminar ──────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID no válido']);

        // Verificar si está en uso en programas_fds (como dp_bosquejo_id)
        try {
            $uso = fetchOne("SELECT COUNT(*) AS n FROM programas_fds WHERE dp_bosquejo_id=?", [$id]);
            if ((int)($uso['n'] ?? 0) > 0) {
                jsonResponse(['success' => false,
                    'message' => 'No se puede eliminar: está asignado a ' . $uso['n'] . ' semana(s)']);
            }
        } catch (Exception $e) { /* columna aún no existe, ignorar */ }

        $pdo->prepare("DELETE FROM bosquejos WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Bosquejo eliminado']);
    }

    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
