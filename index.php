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

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card blue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $totalPersonas; ?></h3>
                    <p>Personas Activas</p>
                </div>
                <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card green">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $totalProgramas; ?></h3>
                    <p>Programas Totales</p>
                </div>
                <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card orange">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $programasProximos; ?></h3>
                    <p>Próximos Programas</p>
                </div>
                <i class="bi bi-calendar-event" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card red">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $asignacionesPendientes; ?></h3>
                    <p>Asignaciones Pendientes</p>
                </div>
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.5;"></i>
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
                                            echo $fecha_inicio->format('d') . '-' . $fecha_fin->format('d') . ' de ' . 
                                                 strftime('%B %Y', $fecha_inicio->getTimestamp());
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
                <div class="row text-center">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="pages/personas.php" class="text-decoration-none">
                            <div class="p-4 border rounded hover-shadow">
                                <i class="bi bi-person-plus" style="font-size: 3rem; color: #1a73e8;"></i>
                                <p class="mt-2 mb-0 fw-bold">Agregar Persona</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="pages/programas.php" class="text-decoration-none">
                            <div class="p-4 border rounded hover-shadow">
                                <i class="bi bi-cloud-download" style="font-size: 3rem; color: #34a853;"></i>
                                <p class="mt-2 mb-0 fw-bold">Extraer Programas</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="pages/programas.php" class="text-decoration-none">
                            <div class="p-4 border rounded hover-shadow">
                                <i class="bi bi-person-check" style="font-size: 3rem; color: #fbbc04;"></i>
                                <p class="mt-2 mb-0 fw-bold">Asignar Partes</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="pages/seleccionar_exportar.php" class="text-decoration-none">
                            <div class="p-4 border rounded hover-shadow">
                                <i class="bi bi-file-pdf" style="font-size: 3rem; color: #ea4335;"></i>
                                <p class="mt-2 mb-0 fw-bold">Exportar PDF</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
