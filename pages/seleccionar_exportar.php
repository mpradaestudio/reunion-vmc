<?php
$pageTitle = 'Exportar PDF';
require_once __DIR__ . '/../includes/header.php';

// Obtener meses disponibles
$mesesDisponibles = fetchAll("
    SELECT DISTINCT 
        MONTH(fecha_inicio) as mes,
        YEAR(fecha_inicio) as anio,
        COUNT(*) as num_programas
    FROM programas_semanales
    GROUP BY YEAR(fecha_inicio), MONTH(fecha_inicio)
    ORDER BY anio DESC, mes DESC
");

$mesesNombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="bi bi-file-pdf me-2"></i>
                Exportar Programas a PDF
            </h1>
            <a href="programas.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-month"></i> Seleccionar Período</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    El PDF se generará con el formato oficial, incluyendo todos los programas del mes seleccionado.
                </div>
                
                <?php if (count($mesesDisponibles) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($mesesDisponibles as $mes): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar3 text-primary" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">
                                    <?php echo $mesesNombres[$mes['mes']] . ' ' . $mes['anio']; ?>
                                </h5>
                                <p class="text-muted mb-3">
                                    <?php echo $mes['num_programas']; ?> 
                                    <?php echo ($mes['num_programas'] == 1) ? 'programa' : 'programas'; ?>
                                </p>
                                <a href="exportar_pdf.php?mes=<?php echo $mes['mes']; ?>&anio=<?php echo $mes['anio']; ?>" 
                                   class="btn btn-danger" target="_blank">
                                    <i class="bi bi-file-pdf"></i> Exportar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h3>No hay programas disponibles</h3>
                    <p>Primero debes extraer programas desde jw.org</p>
                    <a href="programas.php" class="btn btn-primary">
                        <i class="bi bi-cloud-download"></i> Ir a Programas
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Instrucciones -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <h6>¿Qué incluye el PDF?</h6>
                <ul>
                    <li>Todos los programas del mes seleccionado</li>
                    <li>Secciones con colores oficiales (TESOROS, SEAMOS MEJORES MAESTROS, NUESTRA VIDA)</li>
                    <li>Números de canciones</li>
                    <li>Espacios para asignar roles (Presidente, Oraciones)</li>
                    <li>Espacios para asignar personas a cada parte</li>
                    <li>Formato profesional con Google Sans</li>
                </ul>
                
                <hr>
                
                <h6>Requisitos:</h6>
                <ul>
                    <li>Asegúrate de haber extraído los programas desde jw.org</li>
                    <li>TCPDF debe estar instalado (ejecuta: <code>composer install</code>)</li>
                    <li>El PDF se abrirá en una nueva pestaña</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
