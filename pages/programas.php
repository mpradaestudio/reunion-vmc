<?php
$pageTitle = 'Programas';
require_once __DIR__ . '/../includes/header.php';

// Obtener programas
$programas = fetchAll("
    SELECT ps.*, 
           (SELECT COUNT(*) FROM programa_secciones WHERE programa_id = ps.id) as total_secciones,
           (SELECT COUNT(*) FROM asignaciones_roles WHERE programa_id = ps.id) as roles_asignados
    FROM programas_semanales ps
    ORDER BY ps.fecha_inicio DESC
");

// Procesar mensajes
$mensaje = '';
$tipoMensaje = 'success';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'extraido':
            $mensaje = 'Programas extraídos exitosamente';
            break;
        case 'eliminado':
            $mensaje = 'Programa eliminado exitosamente';
            break;
        case 'error':
            $mensaje = 'Ocurrió un error al procesar la solicitud';
            $tipoMensaje = 'danger';
            break;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="bi bi-calendar-week me-2"></i>
                Programas Semanales
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExtraer">
                <i class="bi bi-cloud-download"></i> Extraer Programas
            </button>
        </div>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Lista de programas -->
<div class="row">
    <div class="col-12">
        <?php if (count($programas) > 0): ?>
            <div class="row">
                <?php 
                $hoy = date('Y-m-d');
                foreach ($programas as $programa): 
                    // Determinar estado del programa
                    $claseEstado = '';
                    $badgeEstado = '';
                    if ($programa['fecha_fin'] < $hoy) {
                        $claseEstado = 'pasado';
                        $badgeEstado = '<span class="badge bg-secondary">Pasado</span>';
                    } elseif ($programa['fecha_inicio'] <= $hoy && $programa['fecha_fin'] >= $hoy) {
                        $claseEstado = 'actual';
                        $badgeEstado = '<span class="badge bg-success">Esta semana</span>';
                    } else {
                        $claseEstado = 'futuro';
                        $badgeEstado = '<span class="badge bg-primary">Próximo</span>';
                    }
                    
                    // Formatear fechas
                    $fecha_inicio = new DateTime($programa['fecha_inicio']);
                    $fecha_fin = new DateTime($programa['fecha_fin']);
                    
                    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain');
                    $mesNombre = [
                        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
                    ];
                    $mes = $mesNombre[(int)$fecha_inicio->format('n')];
                    $fechaFormato = $fecha_inicio->format('d') . '-' . $fecha_fin->format('d') . ' de ' . $mes . ' ' . $fecha_inicio->format('Y');
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card programa-card <?php echo $claseEstado; ?> h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($programa['titulo_semana']); ?></h5>
                                <?php echo $badgeEstado; ?>
                            </div>
                            
                            <p class="text-muted mb-2">
                                <i class="bi bi-calendar3"></i>
                                <small><?php echo $fechaFormato; ?></small>
                            </p>
                            
                            <?php if ($programa['referencia_biblica']): ?>
                            <p class="mb-2">
                                <i class="bi bi-book"></i>
                                <small><strong><?php echo htmlspecialchars($programa['referencia_biblica']); ?></strong></small>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-music-note"></i> 
                                    Canciones: <?php echo $programa['cancion_inicial']; ?>, 
                                    <?php echo $programa['cancion_media']; ?>, 
                                    <?php echo $programa['cancion_final']; ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <small>
                                    <i class="bi bi-list-check"></i> 
                                    <?php echo $programa['total_secciones']; ?> partes |
                                    <i class="bi bi-person-check"></i> 
                                    <?php echo $programa['roles_asignados']; ?> roles asignados
                                </small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="programa_detalle.php?id=<?php echo $programa['id']; ?>" 
                                   class="btn btn-sm btn-primary flex-fill">
                                    <i class="bi bi-eye"></i> Ver / Asignar
                                </a>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar-programa" 
                                        data-id="<?php echo $programa['id']; ?>"
                                        data-titulo="<?php echo htmlspecialchars($programa['titulo_semana']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h3>No hay programas disponibles</h3>
                        <p>Extrae los programas desde jw.org para comenzar a hacer asignaciones</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalExtraer">
                            <i class="bi bi-cloud-download"></i> Extraer Programas
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para extraer programas -->
<div class="modal fade" id="modalExtraer" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cloud-download"></i> Extraer Programas de jw.org
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <small>Se extraerán automáticamente todos los programas del período seleccionado desde jw.org</small>
                </div>
                
                <form id="formExtraer">
                    <div class="mb-3">
                        <label for="periodo" class="form-label">Seleccionar Período</label>
                        <select class="form-select" id="periodo" name="periodo" required>
                            <option value="">Seleccionar...</option>
                            <option value="mayo-junio-2026">Mayo - Junio 2026</option>
                            <option value="julio-agosto-2026">Julio - Agosto 2026</option>
                            <option value="septiembre-octubre-2026">Septiembre - Octubre 2026</option>
                            <option value="noviembre-diciembre-2026">Noviembre - Diciembre 2026</option>
                        </select>
                    </div>
                    
                    <div id="extraer-progress" class="d-none">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span>Extrayendo programas...</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnExtraer">
                    <i class="bi bi-cloud-download"></i> Extraer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Extraer programas
$('#btnExtraer').on('click', function() {
    const periodo = $('#periodo').val();
    
    if (!periodo) {
        APP.showNotification('Debe seleccionar un período', 'warning');
        return;
    }
    
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Extrayendo...');
    $('#extraer-progress').removeClass('d-none');
    
    $.ajax({
        url: '../api/scraper.php',
        method: 'POST',
        data: {
            action: 'scrape',
            periodo: periodo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                APP.showNotification(response.message, 'success');
                setTimeout(() => {
                    window.location.href = 'programas.php?msg=extraido';
                }, 2000);
            } else {
                APP.showNotification(response.message, 'danger');
                btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
                $('#extraer-progress').addClass('d-none');
            }
        },
        error: function() {
            APP.showNotification('Error al conectar con el servidor', 'danger');
            btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
            $('#extraer-progress').addClass('d-none');
        }
    });
});

// Eliminar programa
$('.btn-eliminar-programa').on('click', function() {
    const id = $(this).data('id');
    const titulo = $(this).data('titulo');
    
    if (confirm('¿Está seguro de eliminar el programa "' + titulo + '"?\n\nEsto eliminará todas las asignaciones asociadas.')) {
        $.post('../api/programas.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                window.location.href = 'programas.php?msg=eliminado';
            } else {
                APP.showNotification(response.message, 'danger');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
