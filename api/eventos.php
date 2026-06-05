<?php
/**
 * API Eventos Especiales
 * Acciones: list, create, delete
 *
 * Límites por año:
 *   regional      → 1
 *   circuito      → 2
 *   visita        → 2
 *   conmemoracion → 1
 */

require_once __DIR__ . '/../config/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$datos  = [];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
} else {
    $datos  = $_POST;
    $action = $datos['action'] ?? '';
    if (empty($datos)) {
        $raw   = file_get_contents('php://input');
        $datos = json_decode($raw, true) ?? [];
        $action = $datos['action'] ?? $action;
    }
}

/* ── Límites por tipo ───────────────────────────────────────── */
$LIMITES = [
    'regional'      => 1,
    'circuito'      => 2,
    'visita'        => 2,
    'conmemoracion' => 1,
];

/* ── Labels para mensajes ───────────────────────────────────── */
$LABELS = [
    'regional'      => 'Asamblea Regional',
    'circuito'      => 'Asamblea de Circuito',
    'visita'        => 'Visita de Circuito',
    'conmemoracion' => 'Conmemoración',
];

/* ── Tipos de 1 día (fecha_fin = fecha_inicio) ──────────────── */
$UN_DIA = ['circuito', 'conmemoracion'];

/* ── list ───────────────────────────────────────────────────── */
if ($action === 'list') {
    try {
        $rows = fetchAll("
            SELECT * FROM eventos_especiales
            ORDER BY fecha_inicio ASC
        ");
        // Agrupar por tipo para facilitar el render en el front
        $agrupado = [];
        foreach ($rows as $r) {
            $agrupado[$r['tipo']][] = $r;
        }
        jsonResponse(['success' => true, 'data' => $rows, 'agrupado' => $agrupado]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Tabla no existe. Importa database_update_v12.sql']);
    }
}

/* ── create ─────────────────────────────────────────────────── */
if ($action === 'create') {
    $tipo        = $datos['tipo']        ?? '';
    $fechaInicio = $datos['fecha_inicio'] ?? '';
    $fechaFin    = $datos['fecha_fin']    ?? $fechaInicio;
    $notas       = trim($datos['notas']  ?? '');

    // Validar tipo
    if (!array_key_exists($tipo, $LIMITES)) {
        jsonResponse(['success' => false, 'message' => 'Tipo de evento no válido']);
    }

    // Validar fechas
    if (empty($fechaInicio)) {
        jsonResponse(['success' => false, 'message' => 'La fecha de inicio es obligatoria']);
    }

    // Para eventos de 1 día, fecha_fin = fecha_inicio
    if (in_array($tipo, $UN_DIA)) {
        $fechaFin = $fechaInicio;
    }

    if (empty($fechaFin)) {
        $fechaFin = $fechaInicio;
    }

    // Validar que fecha_fin >= fecha_inicio
    if ($fechaFin < $fechaInicio) {
        jsonResponse(['success' => false, 'message' => 'La fecha fin no puede ser anterior a la fecha inicio']);
    }

    try {
        $pdo = getDBConnection();

        // Verificar límite por tipo en el mismo año
        $anio = (int)date('Y', strtotime($fechaInicio));
        $count = fetchOne("
            SELECT COUNT(*) AS total FROM eventos_especiales
            WHERE tipo = ? AND YEAR(fecha_inicio) = ?
        ", [$tipo, $anio]);

        $limite = $LIMITES[$tipo];
        if ((int)$count['total'] >= $limite) {
            $label = $LABELS[$tipo];
            jsonResponse([
                'success' => false,
                'message' => "Solo se permite $limite evento(s) de tipo \"$label\" por año. Ya tienes $limite registrado(s) para $anio.",
            ]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO eventos_especiales (tipo, fecha_inicio, fecha_fin, notas)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $fechaInicio, $fechaFin, $notas ?: null]);
        $newId = $pdo->lastInsertId();

        $nuevo = fetchOne("SELECT * FROM eventos_especiales WHERE id = ?", [$newId]);
        jsonResponse(['success' => true, 'message' => 'Evento guardado', 'data' => $nuevo]);

    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Error al guardar el evento']);
    }
}

/* ── delete ─────────────────────────────────────────────────── */
if ($action === 'delete') {
    $id = (int)($datos['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID no válido']);

    try {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM eventos_especiales WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Evento eliminado']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar']);
    }
}

jsonResponse(['success' => false, 'message' => 'Acción no válida']);
