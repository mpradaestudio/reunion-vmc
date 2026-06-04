<?php
$pageTitle = 'Configuración';
require_once __DIR__ . '/../includes/header.php';

// Obtener configuración actual
$config = getConfiguracion();

// Procesar mensajes
$mensaje = '';
$tipoMensaje = 'success';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'actualizada':
            $mensaje = 'Configuración actualizada exitosamente';
            break;
        case 'error':
            $mensaje = 'Ocurrió un error al actualizar la configuración';
            $tipoMensaje = 'danger';
            break;
    }
}

// Obtener estadísticas del sistema
$stats = [
    'personas' => fetchOne("SELECT COUNT(*) as total FROM personas")['total'],
    'personas_activas' => fetchOne("SELECT COUNT(*) as total FROM personas WHERE activo = 1")['total'],
    'programas' => fetchOne("SELECT COUNT(*) as total FROM programas_semanales")['total'],
    'asignaciones' => fetchOne("SELECT COUNT(*) as total FROM asignaciones_partes")['total'],
    'ultimo_scraping' => fetchOne("SELECT MAX(fecha_scraping) as fecha FROM historial_scraping")['fecha'] ?? 'Nunca'
];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="bi bi-gear me-2"></i>
                Configuración del Sistema
            </h1>
        </div>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Configuración General -->
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders"></i> Configuración General</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracion_guardar.php">
                    <div class="mb-3">
                        <label for="nombre_congregacion" class="form-label">
                            <i class="bi bi-building"></i> Nombre de la Congregación
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_congregacion" 
                               name="nombre_congregacion" 
                               value="<?php echo htmlspecialchars($config['nombre_congregacion']); ?>"
                               required>
                        <div class="form-text">
                            Este nombre aparecerá en los PDFs exportados y en el encabezado del sistema
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6><i class="bi bi-info-circle"></i> Información del Sistema</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Versión:</strong></td>
                                    <td><?php echo APP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Última actualización de config:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($config['ultima_actualizacion'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Base de datos:</strong></td>
                                    <td><?php echo DB_NAME; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Gestión de Perfiles -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Perfiles de Personas</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Los siguientes perfiles están disponibles en el sistema:</p>
                <?php $perfiles = fetchAll("SELECT * FROM perfiles ORDER BY nombre"); ?>
                <div class="list-group">
                    <?php foreach ($perfiles as $perfil): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($perfil['nombre']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($perfil['descripcion']); ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                <?php
                                $numPersonas = fetchOne("SELECT COUNT(*) as total FROM personas WHERE perfil_id = ?", [$perfil['id']])['total'];
                                echo $numPersonas . ' ' . ($numPersonas == 1 ? 'persona' : 'personas');
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Privilegios ─────────────────────────────────────── -->
        <div class="card mb-4" id="card-privilegios">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Privilegios</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalNuevoPrivilegio">
                    <i class="bi bi-plus-circle"></i> Agregar
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Define los privilegios que pueden asignarse a las personas
                    (p.&nbsp;ej. Acomodador, Vigilancia, Micrófonos).
                </p>

                <?php
                $tableExists = true;
                try {
                    $privilegios = fetchAll("SELECT * FROM privilegios ORDER BY orden, nombre");
                } catch (Exception $e) {
                    $tableExists = false;
                    $privilegios = [];
                }
                ?>

                <?php if (!$tableExists): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Importa <code>database_update_v6.sql</code> en phpMyAdmin para activar esta sección.
                </div>
                <?php elseif (empty($privilegios)): ?>
                <p class="text-muted text-center py-3">
                    <i class="bi bi-inbox d-block mb-1" style="font-size:2rem;opacity:.5;"></i>
                    No hay privilegios definidos aún.
                </p>
                <?php else: ?>
                <div class="list-group" id="lista-privilegios">
                    <?php foreach ($privilegios as $priv): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         id="priv-row-<?php echo $priv['id']; ?>">
                        <span class="fw-medium"><?php echo htmlspecialchars($priv['nombre']); ?></span>
                        <button class="btn btn-sm btn-outline-danger btn-eliminar-privilegio"
                                data-id="<?php echo $priv['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($priv['nombre']); ?>"
                                title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Estadísticas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Personas totales</span>
                        <strong><?php echo $stats['personas']; ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Personas activas</span>
                        <strong><?php echo $stats['personas_activas']; ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" 
                             style="width: <?php echo $stats['personas'] > 0 ? ($stats['personas_activas'] / $stats['personas'] * 100) : 0; ?>%">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Programas guardados</span>
                        <strong><?php echo $stats['programas']; ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Asignaciones realizadas</span>
                        <strong><?php echo $stats['asignaciones']; ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-0">
                    <small class="text-muted">
                        <i class="bi bi-clock-history"></i> Último scraping:
                        <br>
                        <strong>
                            <?php 
                            if ($stats['ultimo_scraping'] !== 'Nunca') {
                                echo date('d/m/Y H:i', strtotime($stats['ultimo_scraping']));
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </strong>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Mantenimiento -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-tools"></i> Mantenimiento</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Herramientas para mantener el sistema en óptimas condiciones.
                </p>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning btn-sm" 
                            onclick="limpiarProgramasPasados()">
                        <i class="bi bi-trash"></i> Limpiar programas pasados
                    </button>
                    
                    <button class="btn btn-outline-info btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalHistorial">
                        <i class="bi bi-clock-history"></i> Ver historial de scraping
                    </button>
                    
                    <a href="../database.sql" 
                       class="btn btn-outline-secondary btn-sm" 
                       download>
                        <i class="bi bi-download"></i> Descargar script SQL
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Ayuda -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-question-circle"></i> Ayuda</h5>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>¿Necesitas ayuda?</strong></p>
                <ul class="small ps-3 mb-0">
                    <li>Consulta el archivo README.md</li>
                    <li>Verifica que XAMPP esté ejecutándose</li>
                    <li>Asegúrate de ejecutar <code>composer install</code></li>
                    <li>Importa el archivo database.sql</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial de Scraping -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history"></i> Historial de Scraping
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $historial = fetchAll("
                    SELECT * FROM historial_scraping 
                    ORDER BY fecha_scraping DESC 
                    LIMIT 20
                ");
                ?>
                
                <?php if (count($historial) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>URL</th>
                                <th>Programas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($h['fecha_scraping'])); ?></small>
                                </td>
                                <td>
                                    <small class="text-truncate d-inline-block" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($h['url_procesada']); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $h['num_programas_extraidos']; ?></span>
                                </td>
                                <td>
                                    <?php if ($h['estado'] === 'exitoso'): ?>
                                        <span class="badge bg-success">Exitoso</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Error</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p>No hay historial de scraping</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarProgramasPasados() {
    if (confirm('¿Está seguro de eliminar todos los programas pasados?\n\nEsto eliminará programas cuya fecha ya haya transcurrido.')) {
        $.post('../api/programas.php', {
            action: 'limpiar_pasados'
        }, function(response) {
            if (response.success) {
                APP.showNotification('Programas pasados eliminados', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                APP.showNotification(response.message || 'Error al limpiar programas', 'danger');
            }
        }).fail(function() {
            APP.showNotification('Error al conectar con el servidor', 'danger');
        });
    }
}

/* ── Privilegios ─────────────────────────────────────────── */

// Agregar nuevo privilegio
$('#formNuevoPrivilegio').on('submit', function (e) {
    e.preventDefault();
    const nombre = $.trim($('#nombrePrivilegio').val());
    if (!nombre) return;

    $.post('../api/privilegios.php', { action: 'create', nombre: nombre }, function (res) {
        if (res.success) {
            // Insertar fila en la lista sin recargar
            const id = res.data.id;
            const html = `
                <div class="list-group-item d-flex justify-content-between align-items-center"
                     id="priv-row-${id}">
                    <span class="fw-medium">${$('<span>').text(res.data.nombre).html()}</span>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar-privilegio"
                            data-id="${id}" data-nombre="${$('<span>').text(res.data.nombre).html()}"
                            title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>`;

            // Si la lista no existe aún, crearla
            if ($('#lista-privilegios').length === 0) {
                $('#card-privilegios .card-body').html('<div class="list-group" id="lista-privilegios">' + html + '</div>');
            } else {
                $('#lista-privilegios').append(html);
            }

            $('#modalNuevoPrivilegio').modal('hide');
            APP.showNotification('Privilegio "' + res.data.nombre + '" creado', 'success');
        } else {
            APP.showNotification(res.message, 'danger');
        }
    }).fail(function () {
        APP.showNotification('Error al conectar con el servidor', 'danger');
    });
});

// Limpiar modal al cerrar
$('#modalNuevoPrivilegio').on('hidden.bs.modal', function () {
    $('#nombrePrivilegio').val('');
});

// Eliminar privilegio
$(document).on('click', '.btn-eliminar-privilegio', function () {
    const id     = $(this).data('id');
    const nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar el privilegio "' + nombre + '"?\n\nSolo se puede eliminar si ninguna persona lo tiene asignado.')) return;

    $.post('../api/privilegios.php', { action: 'delete', id: id }, function (res) {
        if (res.success) {
            $('#priv-row-' + id).fadeOut(200, function () { $(this).remove(); });
            APP.showNotification('Privilegio eliminado', 'success');
        } else {
            APP.showNotification(res.message, 'danger');
        }
    }).fail(function () {
        APP.showNotification('Error al conectar con el servidor', 'danger');
    });
});
</script>

<!-- Modal: nuevo privilegio -->
<div class="modal fade" id="modalNuevoPrivilegio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-check"></i> Nuevo Privilegio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoPrivilegio">
                <div class="modal-body">
                    <label for="nombrePrivilegio" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="nombrePrivilegio"
                           placeholder="Ej: Acomodador" required maxlength="100">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
