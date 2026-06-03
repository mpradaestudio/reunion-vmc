<?php
$pageTitle = 'Inicio';
require_once 'includes/header.php';

// Obtener estadísticas
$totalPersonas = fetchOne("SELECT COUNT(*) as total FROM personas WHERE activo = 1")['total'];
$totalProgramas = fetchOne("SELECT COUNT(*) as total FROM programas_semanales")['total'];
$programasProximos = fetchOne("SELECT COUNT(*) as total FROM programas_semanales WHERE fecha_inicio >= CURDATE()")['total'];
$asignacionesPendientes = fetchOne("
    SELECT COUNT(*) as total 
    FROM programa_secciones ps
    INNER JOIN programas_semanales p ON ps.programa_id = p.id
    LEFT JOIN asignaciones_partes ap ON ps.id = ap.seccion_id
    WHERE p.fecha_inicio >= CURDATE() AND ap.id IS NULL
")['total'];

// Obtener próximos programas
$proximosProgramas = fetchAll("
    SELECT * FROM programas_semanales 
    WHERE fecha_inicio >= CURDATE() 
    ORDER BY fecha_inicio ASC 
    LIMIT 4
");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="bi bi-house-door me-2"></i>
                Panel de Control
            </h1>
            <div>
                <a href="pages/programas.php" class="btn btn-primary">
                    <i class="bi bi-calendar-plus"></i> Ver Programas
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas de estadísticas (KPI) -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon stat-accent-primary">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="stat-card-value"><?php echo $totalPersonas; ?></div>
                <p class="stat-card-label">Personas Activas</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon stat-accent-success">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="stat-card-value"><?php echo $totalProgramas; ?></div>
                <p class="stat-card-label">Programas Totales</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon stat-accent-warning">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <div class="stat-card-value"><?php echo $programasProximos; ?></div>
                <p class="stat-card-label">Próximos Programas</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon stat-accent-danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div>
                <div class="stat-card-value"><?php echo $asignacionesPendientes; ?></div>
                <p class="stat-card-label">Asignaciones Pendientes</p>
            </div>
        </div>
    </div>
</div>

<!-- Próximos programas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-week me-2"></i>
                    Próximos Programas
                </h5>
                <a href="pages/programas.php" class="btn btn-sm btn-outline-primary">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (count($proximosProgramas) > 0): ?>
                    <div class="row">
                        <?php foreach ($proximosProgramas as $programa): 
                            $hoy = date('Y-m-d');
                            $claseEstado = '';
                            if ($programa['fecha_inicio'] <= $hoy && $programa['fecha_fin'] >= $hoy) {
                                $claseEstado = 'actual';
                            } elseif ($programa['fecha_inicio'] > $hoy) {
                                $claseEstado = 'futuro';
                            }
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card programa-card <?php echo $claseEstado; ?>">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold"><?php echo $programa['titulo_semana']; ?></h6>
                                    <p class="card-text text-muted mb-2">
                                        <i class="bi bi-calendar3"></i>
                                        <?php 
                                            $fecha_inicio = new DateTime($programa['fecha_inicio']);
                                            $fecha_fin = new DateTime($programa['fecha_fin']);
                                            $mesesNombre = [
                                                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                                                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                                                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
                                            ];
                                            $mesIdx = (int)$fecha_inicio->format('n');
                                            echo $fecha_inicio->format('d') . '-' . $fecha_fin->format('d') . ' de ' . 
                                                 $mesesNombre[$mesIdx] . ' ' . $fecha_inicio->format('Y');
                                        ?>
                                    </p>
                                    <?php if ($programa['referencia_biblica']): ?>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-book"></i>
                                            <small><?php echo $programa['referencia_biblica']; ?></small>
                                        </p>
                                    <?php endif; ?>
                                    <a href="pages/programa_detalle.php?id=<?php echo $programa['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <p>No hay programas próximos</p>
                        <a href="pages/programas.php" class="btn btn-primary">
                            <i class="bi bi-cloud-download"></i> Extraer Programas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>
                    Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-3 col-sm-6">
                        <a href="pages/personas.php" class="quick-tile">
                            <i class="bi bi-person-plus icon-primary"></i>
                            <span>Agregar Persona</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="pages/programas.php" class="quick-tile">
                            <i class="bi bi-cloud-download icon-success"></i>
                            <span>Extraer Programas</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="pages/programas.php" class="quick-tile">
                            <i class="bi bi-person-check icon-warning"></i>
                            <span>Asignar Partes</span>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="pages/seleccionar_exportar.php" class="quick-tile">
                            <i class="bi bi-file-pdf icon-danger"></i>
                            <span>Exportar PDF</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
