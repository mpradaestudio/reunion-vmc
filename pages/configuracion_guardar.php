<?php
/**
 * Procesar formulario de configuración
 */

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('configuracion.php');
}

$nombreCongregacion = $_POST['nombre_congregacion'] ?? '';

if (empty($nombreCongregacion)) {
    redirect('configuracion.php?msg=error');
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        UPDATE configuracion 
        SET nombre_congregacion = ? 
        WHERE id = 1
    ");
    
    $stmt->execute([sanitizeInput($nombreCongregacion)]);
    
    redirect('configuracion.php?msg=actualizada');
    
} catch (Exception $e) {
    error_log("Error al actualizar configuración: " . $e->getMessage());
    redirect('configuracion.php?msg=error');
}
