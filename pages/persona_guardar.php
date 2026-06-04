<?php
/**
 * Procesar formulario de persona
 */

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('personas.php');
}

$action = $_POST['action'] ?? 'create';
$id = $_POST['id'] ?? null;

// Perfiles seleccionados (checkboxes) -> array de ids
$perfilIds = $_POST['perfil_ids'] ?? [];
$perfilIds = array_values(array_unique(array_filter(array_map('intval', (array)$perfilIds))));

// Debe seleccionarse al menos un perfil (perfil_id principal es NOT NULL)
if (empty($perfilIds)) {
    redirect('personas.php?msg=error');
}
$perfilPrincipal = $perfilIds[0];

// Preparar datos
$datos = [
    'nombre'   => $_POST['nombre'] ?? '',
    'apellido' => $_POST['apellido'] ?? '',
    'telefono' => $_POST['telefono'] ?? '',
    'activo'   => $_POST['activo'] ?? 1,
    'notas'    => $_POST['notas'] ?? '',
    'partes_disponibles' => $_POST['partes_disponibles'] ?? []
];

try {
    $pdo = getDBConnection();

    if ($action === 'create') {
        // Crear nueva persona (email queda NULL)
        $stmt = $pdo->prepare("
            INSERT INTO personas (nombre, apellido, perfil_id, telefono, activo, notas)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['apellido']),
            $perfilPrincipal,
            sanitizeInput($datos['telefono']),
            $datos['activo'],
            sanitizeInput($datos['notas'])
        ]);
        $personaId = $pdo->lastInsertId();
        $msg = 'creada';

    } elseif ($action === 'update' && $id) {
        // Actualizar persona existente
        $stmt = $pdo->prepare("
            UPDATE personas SET
                nombre = ?, apellido = ?, perfil_id = ?, telefono = ?, activo = ?, notas = ?
            WHERE id = ?
        ");
        $stmt->execute([
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['apellido']),
            $perfilPrincipal,
            sanitizeInput($datos['telefono']),
            $datos['activo'],
            sanitizeInput($datos['notas']),
            $id
        ]);
        $personaId = $id;
        $msg = 'actualizada';

    } else {
        throw new Exception('Acción no válida');
    }

    if ($personaId) {
        // --- Perfiles (tabla persona_perfiles) ---
        $pdo->prepare("DELETE FROM persona_perfiles WHERE persona_id = ?")->execute([$personaId]);
        $stmtPerfil = $pdo->prepare("
            INSERT INTO persona_perfiles (persona_id, perfil_id) VALUES (?, ?)
        ");
        foreach ($perfilIds as $pid) {
            $stmtPerfil->execute([$personaId, $pid]);
        }

        // --- Partes que puede presentar ---
        $pdo->prepare("DELETE FROM persona_partes_disponibles WHERE persona_id = ?")
            ->execute([$personaId]);
        if (!empty($datos['partes_disponibles'])) {
            $stmtParte = $pdo->prepare("
                INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar)
                VALUES (?, ?, 1)
            ");
            foreach ($datos['partes_disponibles'] as $parte) {
                $stmtParte->execute([$personaId, sanitizeInput($parte)]);
            }
        }

        // --- Privilegios ---
        $privilegioIds = array_values(array_unique(
            array_filter(array_map('intval', (array)($_POST['privilegio_ids'] ?? [])))
        ));
        $pdo->prepare("DELETE FROM persona_privilegios WHERE persona_id = ?")->execute([$personaId]);
        if (!empty($privilegioIds)) {
            $stmtPriv = $pdo->prepare("
                INSERT IGNORE INTO persona_privilegios (persona_id, privilegio_id) VALUES (?, ?)
            ");
            foreach ($privilegioIds as $pvid) {
                $stmtPriv->execute([$personaId, $pvid]);
            }
        }
    }

    redirect('personas.php?msg=' . $msg);

} catch (Exception $e) {
    error_log("Error al guardar persona: " . $e->getMessage());
    redirect('personas.php?msg=error');
}
