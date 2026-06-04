<?php
/**
 * Script de importación de bosquejos desde CSV
 * Uso: coloca el archivo bosquejos.csv en la misma carpeta y accede a este script
 * El CSV debe tener formato: numero,titulo  (una línea por bosquejo)
 * Codificación: UTF-8 (sin BOM)
 *
 * ESTE ARCHIVO ES DE USO ÚNICO — elimínalo después de importar.
 */

require_once __DIR__ . '/config/config.php';

// Solo accesible desde localhost por seguridad
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!in_array($ip, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Acceso solo desde localhost.');
}

$csvPath = __DIR__ . '/bosquejos.csv';
$pdo     = getDBConnection();

$modo    = $_POST['accion'] ?? 'preview';   // preview | importar | limpiar_e_importar

// ── Leer y parsear el CSV ────────────────────────────────────
function parsearCsv(string $path): array {
    if (!file_exists($path)) {
        return ['error' => 'No se encontró el archivo bosquejos.csv en ' . $path];
    }

    // Detectar y eliminar BOM UTF-8 si existe
    $contenido = file_get_contents($path);
    if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") {
        $contenido = substr($contenido, 3);
    }

    // Forzar codificación UTF-8
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }

    $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
    $rows   = [];
    $errores = [];

    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if ($linea === '') continue;

        // Parsear con fgetcsv (simulado con str_getcsv)
        $cols = str_getcsv($linea, ',', '"');
        if (count($cols) < 2) {
            $errores[] = "Línea " . ($i + 1) . " ignorada (formato incorrecto): $linea";
            continue;
        }

        $numero = (int) trim($cols[0]);
        $titulo = trim($cols[1]);

        if ($numero <= 0 || $titulo === '') {
            $errores[] = "Línea " . ($i + 1) . " ignorada (número o título vacío)";
            continue;
        }

        $rows[] = ['numero' => $numero, 'titulo' => $titulo];
    }

    return ['rows' => $rows, 'errores' => $errores];
}

$resultado = parsearCsv($csvPath);
$filas     = $resultado['rows']   ?? [];
$errores   = $resultado['errores'] ?? [];
$errorCsv  = $resultado['error']  ?? null;

$log = [];
$importados = 0;
$omitidos   = 0;

// ── Ejecutar importación ─────────────────────────────────────
if (!$errorCsv && in_array($modo, ['importar', 'limpiar_e_importar'])) {
    if ($modo === 'limpiar_e_importar') {
        $pdo->exec("TRUNCATE TABLE bosquejos");
        $log[] = '⚠️  Tabla vaciada antes de importar.';
    }

    $stmt = $pdo->prepare("
        INSERT INTO bosquejos (numero, titulo)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE titulo = VALUES(titulo)
    ");

    foreach ($filas as $fila) {
        try {
            $stmt->execute([$fila['numero'], $fila['titulo']]);
            if ($stmt->rowCount() > 0) {
                $importados++;
            } else {
                $omitidos++;
            }
        } catch (Exception $e) {
            $log[] = "❌ Error en #{$fila['numero']}: " . $e->getMessage();
        }
    }

    $log[] = "✅ Importados/actualizados: $importados";
    if ($omitidos) $log[] = "⏭ Sin cambios (ya existían): $omitidos";
}

// ── Contar registros actuales en BD ─────────────────────────
$totalBd = 0;
try {
    $totalBd = (int) fetchOne("SELECT COUNT(*) AS n FROM bosquejos")['n'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Bosquejos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:760px;">
    <h2 class="mb-1">📥 Importar Bosquejos</h2>
    <p class="text-muted mb-4">
        Coloca el archivo <code>bosquejos.csv</code> en la raíz del proyecto y usa los botones de abajo.
        <br>Formato CSV: <code>numero,titulo</code> — una línea por bosquejo, codificación UTF-8.
    </p>

    <?php if ($errorCsv): ?>
    <div class="alert alert-danger">
        <strong>No se encontró el CSV:</strong> <?php echo htmlspecialchars($errorCsv); ?>
        <hr>
        <ol class="mb-0">
            <li>Descarga el Google Sheet: <strong>Archivo → Descargar → CSV</strong></li>
            <li>Renómbralo a <code>bosquejos.csv</code></li>
            <li>Cópialo a <code>C:\xampp\htdocs\reunion-vmc\bosquejos.csv</code></li>
            <li>Recarga esta página</li>
        </ol>
    </div>

    <?php else: ?>

    <!-- Resumen del CSV -->
    <div class="card mb-4">
        <div class="card-header fw-bold">Resumen del archivo CSV</div>
        <div class="card-body">
            <p class="mb-1">📄 Bosquejos encontrados en CSV: <strong><?php echo count($filas); ?></strong></p>
            <p class="mb-1">🗄  Bosquejos actuales en BD: <strong><?php echo $totalBd; ?></strong></p>
            <?php if ($errores): ?>
            <div class="alert alert-warning mt-3 mb-0">
                <strong>Líneas con problemas (serán omitidas):</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($errores as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log de la importación -->
    <?php if ($log): ?>
    <div class="alert <?php echo $importados > 0 ? 'alert-success' : 'alert-info'; ?> mb-4">
        <?php foreach ($log as $l): ?>
        <div><?php echo htmlspecialchars($l); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Vista previa (primeros 20) -->
    <?php if ($filas): ?>
    <div class="card mb-4">
        <div class="card-header fw-bold">Vista previa (primeros 20 de <?php echo count($filas); ?>)</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>#</th><th>Título</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($filas, 0, 20) as $f): ?>
                    <tr>
                        <td><?php echo $f['numero']; ?></td>
                        <td><?php echo htmlspecialchars($f['titulo']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Acciones -->
    <form method="POST" class="d-flex gap-2 flex-wrap">
        <button name="accion" value="importar" class="btn btn-primary">
            ✅ Importar / Actualizar
            <small class="d-block" style="font-size:.75rem;">Agrega nuevos, actualiza existentes</small>
        </button>
        <button name="accion" value="limpiar_e_importar" class="btn btn-warning"
                onclick="return confirm('¿Vaciar la tabla y reimportar todo?')">
            🔄 Limpiar y reimportar
            <small class="d-block" style="font-size:.75rem;">Borra todo y carga desde cero</small>
        </button>
        <a href="pages/configuracion.php" class="btn btn-outline-secondary">
            ← Volver a Configuración
        </a>
    </form>

    <?php endif; ?>

    <hr class="mt-5">
    <p class="text-muted small">
        ⚠️ <strong>Seguridad:</strong> elimina este archivo (<code>importar_bosquejos.php</code>)
        después de terminar la importación.
    </p>
</div>
</body>
</html>
