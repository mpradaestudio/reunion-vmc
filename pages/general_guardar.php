<?php
/**
 * Procesar formulario de configuración general
 */

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('general.php');
}

$nombreCongregacion = $_POST['nombre_congregacion'] ?? '';

if (empty($nombreCongregacion)) {
    redirect('general.php?msg=error');
}

$diasValidos = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

$diaEntreSemana  = in_array($_POST['dia_entre_semana']  ?? '', $diasValidos) ? $_POST['dia_entre_semana']  : null;
$horaEntreSemana = !empty($_POST['hora_entre_semana'])  ? sanitizeInput($_POST['hora_entre_semana'])  : null;
$diaFinSemana    = in_array($_POST['dia_fin_semana']    ?? '', $diasValidos) ? $_POST['dia_fin_semana']    : null;
$horaFinSemana   = !empty($_POST['hora_fin_semana'])    ? sanitizeInput($_POST['hora_fin_semana'])    : null;

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        UPDATE configuracion
        SET nombre_congregacion = ?,
            dia_entre_semana    = ?,
            hora_entre_semana   = ?,
            dia_fin_semana      = ?,
            hora_fin_semana     = ?
        WHERE id = 1
    ");

    $stmt->execute([
        sanitizeInput($nombreCongregacion),
        $diaEntreSemana,
        $horaEntreSemana,
        $diaFinSemana,
        $horaFinSemana,
    ]);

    redirect('general.php?msg=actualizada');

} catch (Exception $e) {
    error_log("Error al actualizar configuración general: " . $e->getMessage());
    redirect('general.php?msg=error');
}
