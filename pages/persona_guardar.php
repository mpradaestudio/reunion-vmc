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

// Preparar datos
$datos = [
    'nombre' => $_POST['nombre'] ?? '',
    'apellido' => $_POST['apellido'] ?? '',
    'perfil_id' => $_POST['perfil_id'] ?? 0,
    'telefono' => $_POST['telefono'] ?? '',
    'email' => $_POST['email'] ?? '',
    'activo' => $_POST['activo'] ?? 1,
    'notas' => $_POST['notas'] ?? '',
    'partes_disponibles' => $_POST['partes_disponibles'] ?? []
];

try {
    $pdo = getDBConnection();
    
    if ($action === 'create') {
        // Crear nueva persona
        $stmt = $pdo->prepare("
            INSERT INTO personas (nombre, apellido, perfil_id, telefono, email, activo, notas)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['apellido']),
            $datos['perfil_id'],
            sanitizeInput($datos['telefono']),
            sanitizeInput($datos['email']),
            $datos['activo'],
            sanitizeInput($datos['notas'])
        ]);
        
        $personaId = $pdo->lastInsertId();
        $msg = 'creada';
        
    } elseif ($action === 'update' && $id) {
        // Actualizar persona existente
        $stmt = $pdo->prepare("
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
            sanitizeInput($datos['telefono']),
            sanitizeInput($datos['email']),
            $datos['activo'],
            sanitizeInput($datos['notas']),
            $id
        ]);
        
        $personaId = $id;
        $msg = 'actualizada';
        
    } else {
        throw new Exception('Acción no válida');
    }
    
    // Actualizar partes disponibles
    if ($personaId) {
        // Eliminar partes anteriores
        $pdo->prepare("DELETE FROM persona_partes_disponibles WHERE persona_id = ?")
            ->execute([$personaId]);
        
        // Insertar nuevas partes
        if (!empty($datos['partes_disponibles'])) {
            $stmtParte = $pdo->prepare("
                INSERT INTO persona_partes_disponibles (persona_id, tipo_parte, puede_presentar)
                VALUES (?, ?, 1)
            ");
            
            foreach ($datos['partes_disponibles'] as $parte) {
                $stmtParte->execute([$personaId, $parte]);
            }
        }
    }
    
    redirect('personas.php?msg=' . $msg);
    
} catch (Exception $e) {
    error_log("Error al guardar persona: " . $e->getMessage());
    redirect('personas.php?msg=error');
}
