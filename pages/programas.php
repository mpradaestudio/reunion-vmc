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
                    <small>Elige un período y carga las semanas. Luego extrae <strong>semana por semana</strong> con un clic. También puedes pegar la URL de una semana específica.</small>
                </div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-8">
                        <label for="periodo" class="form-label">Seleccionar Período</label>
                        <select class="form-select" id="periodo" name="periodo">
                            <option value="">Seleccionar...</option>
                            <option value="mayo-junio-2026">Mayo - Junio 2026</option>
                            <option value="julio-agosto-2026">Julio - Agosto 2026</option>
                            <option value="septiembre-octubre-2026">Septiembre - Octubre 2026</option>
                            <option value="noviembre-diciembre-2026">Noviembre - Diciembre 2026</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="btnCargarSemanas">
                            <i class="bi bi-list-ul"></i> Cargar semanas
                        </button>
                    </div>
                </div>

                <!-- Lista de semanas del período -->
                <div id="listaSemanas"></div>

                <hr>

                <!-- Extraer por URL específica -->
                <label for="urlSemana" class="form-label">
                    <i class="bi bi-link-45deg"></i> O pega la URL de una semana específica
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="urlSemana"
                           placeholder="https://www.jw.org/es/biblioteca/guia-actividades-reunion-testigos-jehova/...">
                    <button type="button" class="btn btn-success" id="btnExtraerUrl">
                        <i class="bi bi-cloud-download"></i> Extraer
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="programas.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar lista
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let huboExtraccion = false;

// Cargar la lista de semanas del período seleccionado
$('#btnCargarSemanas').on('click', function() {
    const periodo = $('#periodo').val();
    if (!periodo) {
        APP.showNotification('Debe seleccionar un período', 'warning');
        return;
    }

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Cargando...');
    $('#listaSemanas').html('<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Buscando semanas en jw.org...</div>');

    $.ajax({
        url: '../api/scraper.php',
        method: 'POST',
        data: { action: 'listar_semanas', periodo: periodo },
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html('<i class="bi bi-list-ul"></i> Cargar semanas');

            if (!response.success) {
                $('#listaSemanas').html('<div class="alert alert-danger mb-0">' + response.message + '</div>');
                return;
            }

            renderSemanas(response.semanas);
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="bi bi-list-ul"></i> Cargar semanas');
            $('#listaSemanas').html('<div class="alert alert-danger mb-0">Error al conectar con el servidor</div>');
        }
    });
});

// Dibujar la lista de semanas con botón individual
function renderSemanas(semanas) {
    if (!semanas || semanas.length === 0) {
        $('#listaSemanas').html('<div class="alert alert-warning mb-0">No se encontraron semanas.</div>');
        return;
    }

    let html = '<div class="list-group mb-2">';
    semanas.forEach(function(s, i) {
        const badge = s.ya_existe
            ? '<span class="badge bg-success ms-2">Ya extraída</span>'
            : '';
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center" id="semana-${i}">
                <div>
                    <i class="bi bi-calendar-week text-primary"></i>
                    <strong class="text-capitalize">${s.label}</strong>
                    ${badge}
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary btn-extraer-semana"
                        data-url="${s.url}" data-idx="${i}">
                    <i class="bi bi-cloud-download"></i> ${s.ya_existe ? 'Re-extraer' : 'Extraer'}
                </button>
            </div>`;
    });
    html += '</div>';
    $('#listaSemanas').html(html);
}

// Extraer una semana individual (desde la lista)
$(document).on('click', '.btn-extraer-semana', function() {
    const btn = $(this);
    const url = btn.data('url');
    const idx = btn.data('idx');
    extraerSemana(url, btn, '#semana-' + idx);
});

// Extraer una semana desde URL pegada
$('#btnExtraerUrl').on('click', function() {
    const url = $('#urlSemana').val().trim();
    if (!url) {
        APP.showNotification('Pega la URL de una semana', 'warning');
        return;
    }
    extraerSemana(url, $(this), null);
});

// Función común de extracción de una semana
function extraerSemana(url, btn, contenedor) {
    const htmlOriginal = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: '../api/scraper.php',
        method: 'POST',
        data: { action: 'scrape_semana', url: url },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                huboExtraccion = true;
                APP.showNotification(response.message + ' (' + response.partes + ' partes)', 'success');
                if (contenedor) {
                    $(contenedor).find('.btn-extraer-semana')
                        .removeClass('btn-outline-primary').addClass('btn-success')
                        .html('<i class="bi bi-check-lg"></i> Listo')
                        .prop('disabled', false);
                } else {
                    btn.prop('disabled', false).html(htmlOriginal);
                    $('#urlSemana').val('');
                }
            } else {
                APP.showNotification(response.message, 'danger');
                btn.prop('disabled', false).html(htmlOriginal);
            }
        },
        error: function() {
            APP.showNotification('Error al conectar con el servidor', 'danger');
            btn.prop('disabled', false).html(htmlOriginal);
        }
    });
}

// Al cerrar el modal, si hubo extracciones, recargar para ver los programas
$('#modalExtraer').on('hidden.bs.modal', function() {
    if (huboExtraccion) {
        window.location.href = 'programas.php?msg=extraido';
    }
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
