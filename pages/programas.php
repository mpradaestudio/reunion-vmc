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
    <div class="modal-dialog modal-lg">
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
                    <small>Pega la <strong>URL de la semana</strong> que quieres importar desde jw.org y haz clic en <strong>Extraer</strong>. Así construyes tu calendario semana por semana.</small>
                </div>

                <label for="urlSemana" class="form-label fw-bold">
                    <i class="bi bi-link-45deg"></i> URL de la semana
                </label>
                <input type="text" class="form-control" id="urlSemana"
                       placeholder="https://www.jw.org/es/biblioteca/guia-actividades-reunion-testigos-jehova/...">
                <small class="text-muted d-block mt-1">
                    Ejemplo: …/julio-agosto-2026-mwb/Vida-y-Ministerio-Cristianos-6-a-12-de-julio-de-2026/
                </small>

                <div id="extraerEstado" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnExtraerUrl">
                    <i class="bi bi-cloud-download"></i> Extraer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Extraer una semana desde la URL pegada
$('#btnExtraerUrl').on('click', function() {
    const url = $('#urlSemana').val().trim();
    if (!url) {
        $('#extraerEstado').html('<div class="alert alert-warning mb-0">Pega la URL de una semana de jw.org</div>');
        return;
    }

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Extrayendo...');
    $('#extraerEstado').html('<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Descargando y procesando la semana desde jw.org...</div>');

    $.ajax({
        url: '../api/scraper.php',
        method: 'POST',
        data: { action: 'scrape_semana', url: url },
        dataType: 'json',
        timeout: 60000,
        success: function(response) {
            if (response.success) {
                $('#extraerEstado').html(
                    '<div class="alert alert-success mb-0">' +
                    '<i class="bi bi-check-circle"></i> ' + response.message +
                    ' (' + response.partes + ' partes). Actualizando...</div>'
                );
                // Cerrar y recargar para mostrar la semana importada
                setTimeout(function() {
                    window.location.href = 'programas.php?msg=extraido';
                }, 1200);
            } else {
                $('#extraerEstado').html(
                    '<div class="alert alert-danger mb-0">' +
                    '<i class="bi bi-exclamation-circle"></i> ' + response.message + '</div>'
                );
                btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
            }
        },
        error: function(xhr, status) {
            const msg = (status === 'timeout')
                ? 'La descarga tardó demasiado. Intenta de nuevo.'
                : 'Error al conectar con el servidor';
            $('#extraerEstado').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
        }
    });
});

// Limpiar estado al cerrar el modal
$('#modalExtraer').on('hidden.bs.modal', function() {
    $('#extraerEstado').empty();
    $('#urlSemana').val('');
    $('#btnExtraerUrl').prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
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
