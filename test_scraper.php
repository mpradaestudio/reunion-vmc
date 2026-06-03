<?php
/**
 * DIAGNÓSTICO DEL SCRAPER
 * -----------------------
 * Permite probar la extracción de UNA semana específica y ver qué detecta
 * el parser, sin guardar nada en la base de datos.
 *
 * Uso:
 *   http://localhost/reunion-vmc/test_scraper.php
 *   http://localhost/reunion-vmc/test_scraper.php?url=URL_DE_LA_SEMANA
 *   http://localhost/reunion-vmc/test_scraper.php?url=...&lineas=1   (ver texto crudo)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/scraper.php';

// URL por defecto (la que mencionaste)
$urlDefault = 'https://www.jw.org/es/biblioteca/guia-actividades-reunion-testigos-jehova/julio-agosto-2026-mwb/Vida-y-Ministerio-Cristianos-6-a-12-de-julio-de-2026/';
$url = $_GET['url'] ?? $urlDefault;
$verLineas = isset($_GET['lineas']);

// Acceder a métodos privados de descarga mediante reflexión (solo para diagnóstico)
$scraper = new JWOrgScraper();

function llamarPrivado($obj, $metodo, $args = []) {
    $ref = new ReflectionMethod($obj, $metodo);
    $ref->setAccessible(true);
    return $ref->invokeArgs($obj, $args);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico del Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background:#f8f9fa; }
        pre { background:#1e1e1e; color:#d4d4d4; padding:15px; border-radius:8px; max-height:500px; overflow:auto; font-size:13px; }
        .tesoros { background:#6c757d; color:#fff; }
        .maestros { background:#d4a01e; color:#fff; }
        .vida { background:#8b1538; color:#fff; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="h3 mb-3"><i class="bi bi-bug"></i> Diagnóstico del Scraper</h1>

    <form method="GET" class="card card-body mb-4">
        <label class="form-label fw-bold">URL de la semana a analizar:</label>
        <input type="text" name="url" class="form-control mb-2" value="<?php echo htmlspecialchars($url); ?>">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="lineas" value="1" id="chkLineas" <?php echo $verLineas ? 'checked' : ''; ?>>
            <label class="form-check-label" for="chkLineas">Mostrar texto crudo extraído (para depurar)</label>
        </div>
        <button class="btn btn-primary"><i class="bi bi-search"></i> Analizar</button>
    </form>

<?php
$inicio = microtime(true);
$html = llamarPrivado($scraper, 'obtenerContenidoWeb', [$url]);
$tiempo = round(microtime(true) - $inicio, 2);

if (!$html) {
    echo '<div class="alert alert-danger"><strong>❌ No se pudo descargar la página.</strong><br>';
    echo 'Verifica tu conexión a internet y que la URL sea correcta. Revisa también el log de errores de PHP.</div>';
    echo '</div></body></html>';
    exit;
}

echo '<div class="alert alert-success">✅ Página descargada en ' . $tiempo . 's (' . number_format(strlen($html)) . ' bytes)</div>';

// Parsear
$datos = $scraper->parsearSemana($html, $url);
?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><i class="bi bi-info-circle"></i> Información general (solo informativa)</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th style="width:220px">Título &lt;h1&gt; original</th><td><?php echo htmlspecialchars($datos['titulo_h1']); ?></td></tr>
                <tr><th>Semana (construida)</th><td><strong><?php echo htmlspecialchars($datos['titulo']); ?></strong></td></tr>
                <tr><th>Fecha inicio</th><td><?php echo htmlspecialchars($datos['fecha_inicio'] ?? '—'); ?></td></tr>
                <tr><th>Fecha fin</th><td><?php echo htmlspecialchars($datos['fecha_fin'] ?? '—'); ?></td></tr>
                <tr><th>Lectura bíblica</th><td><strong><?php echo htmlspecialchars($datos['referencia'] ?: '—'); ?></strong></td></tr>
                <tr><th>Canción inicial</th><td><?php echo htmlspecialchars($datos['canciones']['inicial'] ?? '—'); ?></td></tr>
                <tr><th>Canción media</th><td><?php echo htmlspecialchars($datos['canciones']['media'] ?? '—'); ?></td></tr>
                <tr><th>Canción final</th><td><?php echo htmlspecialchars($datos['canciones']['final'] ?? '—'); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <i class="bi bi-list-ol"></i> Partes numeradas detectadas (asignables): 
            <span class="badge bg-light text-dark"><?php echo count($datos['secciones']); ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($datos['secciones'])): ?>
                <div class="alert alert-warning mb-0">
                    ⚠️ No se detectaron partes numeradas. Marca la casilla "Mostrar texto crudo" 
                    y cópiame el resultado para ajustar el parser a la estructura real.
                </div>
            <?php else: ?>
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Sección</th><th>Título</th><th>Duración</th><th>Personas a asignar</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['secciones'] as $i => $p):
                        $clase = '';
                        if ($p['seccion'] === 'TESOROS DE LA BIBLIA') $clase = 'tesoros';
                        elseif ($p['seccion'] === 'SEAMOS MEJORES MAESTROS') $clase = 'maestros';
                        elseif ($p['seccion'] === 'NUESTRA VIDA CRISTIANA') $clase = 'vida';
                        $numPersonas = in_array($p['tipo_asignacion'], ['Estudiante/Ayudante','Conductor/Lector']) ? 2 : 1;
                    ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><span class="badge <?php echo $clase; ?>"><?php echo htmlspecialchars($p['seccion']); ?></span></td>
                        <td><?php echo htmlspecialchars($p['titulo']); ?></td>
                        <td><?php echo $p['duracion'] ? $p['duracion'].' min' : '—'; ?></td>
                        <td>
                            <strong><?php echo $numPersonas; ?></strong>
                            <small class="text-muted">(<?php echo htmlspecialchars($p['tipo_asignacion']); ?>)</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($verLineas):
        $lineas = llamarPrivado($scraper, 'htmlALineas', [$html]);
    ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <i class="bi bi-file-text"></i> Texto crudo extraído (<?php echo count($lineas); ?> líneas)
        </div>
        <div class="card-body">
            <p class="text-muted small">Esto es lo que "ve" el parser. Si las partes numeradas no salen bien, cópiame esta sección.</p>
            <pre><?php
                foreach ($lineas as $n => $l) {
                    echo str_pad($n + 1, 4, ' ', STR_PAD_LEFT) . ' | ' . htmlspecialchars($l) . "\n";
                }
            ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="bi bi-lightbulb"></i> Este script <strong>no guarda nada</strong> en la base de datos. 
        Solo sirve para verificar la extracción. Cuando los datos se vean correctos, usa el botón 
        <strong>"Extraer Programas"</strong> en la página de Programas.
    </div>
</div>
</body>
</html>
