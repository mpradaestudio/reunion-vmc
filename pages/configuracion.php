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
        
        <!-- ── Perfiles de Personas ───────────────────────────── -->
        <div class="card mb-4" id="card-perfiles">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Perfiles de Personas</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalNuevoPerfil">
                    <i class="bi bi-plus-circle"></i> Agregar
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Define los perfiles que clasifican a las personas
                    (p.&nbsp;ej. Anciano, Siervo Ministerial, Publicador).
                    Arrastra <i class="bi bi-grip-vertical"></i> para reordenar.
                </p>

                <?php
                $perfilesExist = true;
                try {
                    $perfiles = fetchAll("SELECT * FROM perfiles ORDER BY orden, nombre");
                } catch (Exception $e) {
                    $perfilesExist = false;
                    $perfiles = [];
                }
                ?>

                <?php if (!$perfilesExist): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Importa <code>database_update_v7.sql</code> para activar esta sección.
                </div>
                <?php elseif (empty($perfiles)): ?>
                <p class="text-muted text-center py-3 msg-empty-perfiles">
                    <i class="bi bi-inbox d-block mb-1" style="font-size:2rem;opacity:.5;"></i>
                    No hay perfiles definidos aún.
                </p>
                <?php else: ?>
                <div class="list-group sortable-list" id="lista-perfiles">
                    <?php foreach ($perfiles as $perf): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         id="perf-row-<?php echo $perf['id']; ?>"
                         data-id="<?php echo $perf['id']; ?>">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-grip-vertical drag-handle text-muted" style="cursor:grab;"></i>
                            <span class="fw-medium"><?php echo htmlspecialchars($perf['nombre']); ?></span>
                        </div>
                        <button class="btn btn-sm btn-outline-danger btn-eliminar-perfil"
                                data-id="<?php echo $perf['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($perf['nombre']); ?>"
                                title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                <div class="list-group sortable-list" id="lista-privilegios">
                    <?php foreach ($privilegios as $priv): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         id="priv-row-<?php echo $priv['id']; ?>"
                         data-id="<?php echo $priv['id']; ?>">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-grip-vertical drag-handle text-muted" style="cursor:grab;"></i>
                            <span class="fw-medium"><?php echo htmlspecialchars($priv['nombre']); ?></span>
                        </div>
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

        <!-- ── Bosquejos ──────────────────────────────────────── -->
        <div class="card mb-4" id="card-bosquejos">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-journals"></i> Bosquejos</h5>
                <div class="d-flex gap-2">
                    <a href="../importar_bosquejos.php" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-upload"></i> Importar CSV
                    </a>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalNuevoBosquejo">
                        <i class="bi bi-plus-circle"></i> Agregar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Discursos públicos disponibles. Se usan en Reunión Fin de Semana para seleccionar el tema del Discurso Público.
                    Puedes importarlos masivamente desde un CSV con el botón <strong>Importar CSV</strong>.
                </p>

                <?php
                $bosquejosExist = true;
                $totalBosquejos = 0;
                $muestraBosquejos = [];
                try {
                    $totalBosquejos  = (int)(fetchOne("SELECT COUNT(*) AS n FROM bosquejos WHERE activo=1")['n'] ?? 0);
                    $muestraBosquejos = fetchAll("SELECT id, numero, titulo FROM bosquejos WHERE activo=1 ORDER BY numero LIMIT 10");
                } catch (Exception $e) {
                    $bosquejosExist = false;
                }
                ?>

                <?php if (!$bosquejosExist): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Importa <code>database_update_v9.sql</code> en phpMyAdmin para activar esta sección.
                </div>

                <?php elseif ($totalBosquejos === 0): ?>
                <p class="text-muted text-center py-3 msg-empty-bosquejos">
                    <i class="bi bi-inbox d-block mb-1" style="font-size:2rem;opacity:.5;"></i>
                    No hay bosquejos. Usa <strong>Importar CSV</strong> para cargar todos de una vez.
                </p>

                <?php else: ?>
                <!-- Buscador rápido dentro de la lista -->
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="filtroBosquejos"
                           placeholder="Filtrar por número o título…">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Total: <strong><?php echo $totalBosquejos; ?></strong> bosquejos</small>
                    <small class="text-muted">Mostrando los últimos 10 — usa el buscador para encontrar más</small>
                </div>

                <div class="list-group" id="lista-bosquejos">
                    <?php foreach ($muestraBosquejos as $b): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         id="bosq-row-<?php echo $b['id']; ?>"
                         data-id="<?php echo $b['id']; ?>"
                         data-numero="<?php echo $b['numero']; ?>"
                         data-titulo="<?php echo htmlspecialchars($b['titulo']); ?>">
                        <span>
                            <span class="badge bg-secondary me-2"><?php echo $b['numero']; ?></span>
                            <?php echo htmlspecialchars($b['titulo']); ?>
                        </span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-xs btn-outline-primary btn-editar-bosquejo"
                                    data-id="<?php echo $b['id']; ?>"
                                    data-numero="<?php echo $b['numero']; ?>"
                                    data-titulo="<?php echo htmlspecialchars($b['titulo']); ?>"
                                    title="Editar" style="padding:.2rem .5rem;font-size:.75rem;">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-danger btn-eliminar-bosquejo"
                                    data-id="<?php echo $b['id']; ?>"
                                    data-numero="<?php echo $b['numero']; ?>"
                                    title="Eliminar" style="padding:.2rem .5rem;font-size:.75rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
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

/* ================================================================
   HELPER genérico: petición AJAX a una API
================================================================ */
function apiPost(url, data, onSuccess) {
    $.ajax({
        url     : url,
        method  : 'POST',
        dataType: 'json',
        data    : data,
        success : function (res) {
            if (res.success) {
                if (onSuccess) onSuccess(res);
            } else {
                APP.showNotification(res.message || 'Error', 'danger');
            }
        },
        error   : function () {
            APP.showNotification('Error al conectar con el servidor', 'danger');
        }
    });
}

/* ================================================================
   HELPER: construye HTML de una fila (perfil o privilegio)
================================================================ */
function buildRow(id, nombre, prefix, btnClass) {
    const nombreE = $('<span>').text(nombre).html();
    return `
        <div class="list-group-item d-flex justify-content-between align-items-center"
             id="${prefix}-row-${id}" data-id="${id}">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-grip-vertical drag-handle text-muted" style="cursor:grab;"></i>
                <span class="fw-medium">${nombreE}</span>
            </div>
            <button class="btn btn-sm btn-outline-danger ${btnClass}"
                    data-id="${id}" data-nombre="${nombreE}" title="Eliminar">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
}

/* ================================================================
   DRAG & DROP — SortableJS
   Se inicializa en DOMContentLoaded (Bootstrap ya cargado en footer,
   pero SortableJS no depende de él).
================================================================ */
document.addEventListener('DOMContentLoaded', function () {

    function initSortable(listId, apiUrl) {
        const el = document.getElementById(listId);
        if (!el || typeof Sortable === 'undefined') return;

        Sortable.create(el, {
            handle    : '.drag-handle',
            animation : 150,
            ghostClass: 'sortable-ghost',
            onEnd     : function () {
                const ids = [...el.querySelectorAll('[data-id]')]
                              .map(r => r.dataset.id);
                apiPost(apiUrl, { action: 'reorder', 'ids[]': ids },
                    function() { /* orden guardado silenciosamente */ });
                // jQuery serializa arrays como ids[]=1&ids[]=2
                // pero $.ajax con data objeto necesita el truco de arriba.
                // Usamos fetch para evitar ese problema:
                const fd = new FormData();
                fd.append('action', 'reorder');
                ids.forEach(id => fd.append('ids[]', id));
                fetch(apiUrl, { method: 'POST', body: fd })
                    .catch(() => {});   // fallo silencioso — el orden visual ya está
            }
        });
    }

    initSortable('lista-perfiles',    '../api/perfiles.php');
    initSortable('lista-privilegios', '../api/privilegios.php');
});

/* ================================================================
   PERFILES — CRUD
================================================================ */
$(document).on('submit', '#formNuevoPerfil', function (e) {
    e.preventDefault();
    const nombre = $.trim($('#nombrePerfil').val());
    const $btn   = $(this).find('button[type="submit"]');
    if (!nombre) return;
    $btn.prop('disabled', true);

    apiPost('../api/perfiles.php', { action: 'create', nombre: nombre }, function (res) {
        $btn.prop('disabled', false);
        const html = buildRow(res.data.id, res.data.nombre, 'perf', 'btn-eliminar-perfil');
        const $lista = $('#lista-perfiles');
        if ($lista.length === 0) {
            $('#card-perfiles .card-body .msg-empty-perfiles').replaceWith(
                '<div class="list-group sortable-list" id="lista-perfiles">' + html + '</div>'
            );
            initSortableAfterCreate('lista-perfiles', '../api/perfiles.php');
        } else {
            $lista.append(html);
        }
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoPerfil'))?.hide();
        APP.showNotification('Perfil "' + res.data.nombre + '" creado', 'success');
    });
    $btn.prop('disabled', false);
});

$(document).on('hidden.bs.modal', '#modalNuevoPerfil', function () {
    $('#nombrePerfil').val('');
});

$(document).on('click', '.btn-eliminar-perfil', function () {
    const id     = $(this).data('id');
    const nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar el perfil "' + nombre + '"?\n\nSolo se puede eliminar si ninguna persona lo tiene asignado.')) return;

    apiPost('../api/perfiles.php', { action: 'delete', id: id }, function () {
        $('#perf-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Perfil eliminado', 'success');
    });
});

/* ================================================================
   PRIVILEGIOS — CRUD
================================================================ */
$(document).on('submit', '#formNuevoPrivilegio', function (e) {
    e.preventDefault();
    const nombre = $.trim($('#nombrePrivilegio').val());
    const $btn   = $(this).find('button[type="submit"]');
    if (!nombre) return;
    $btn.prop('disabled', true);

    apiPost('../api/privilegios.php', { action: 'create', nombre: nombre }, function (res) {
        $btn.prop('disabled', false);
        const html = buildRow(res.data.id, res.data.nombre, 'priv', 'btn-eliminar-privilegio');
        const $lista = $('#lista-privilegios');
        if ($lista.length === 0) {
            $('#card-privilegios .card-body p.text-muted').replaceWith(
                '<div class="list-group sortable-list" id="lista-privilegios">' + html + '</div>'
            );
            initSortableAfterCreate('lista-privilegios', '../api/privilegios.php');
        } else {
            $lista.append(html);
        }
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoPrivilegio'))?.hide();
        APP.showNotification('Privilegio "' + res.data.nombre + '" creado', 'success');
    });
    $btn.prop('disabled', false);
});

$(document).on('hidden.bs.modal', '#modalNuevoPrivilegio', function () {
    $('#nombrePrivilegio').val('');
});

$(document).on('click', '.btn-eliminar-privilegio', function () {
    const id     = $(this).data('id');
    const nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar el privilegio "' + nombre + '"?\n\nSolo se puede eliminar si ninguna persona lo tiene asignado.')) return;

    apiPost('../api/privilegios.php', { action: 'delete', id: id }, function () {
        $('#priv-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Privilegio eliminado', 'success');
    });
});

/* ================================================================
   BOSQUEJOS — CRUD + filtro en vivo
================================================================ */

// Filtro en vivo: busca en BD vía AJAX al escribir
let bosquejoFiltroTimer;
$(document).on('input', '#filtroBosquejos', function () {
    const q = $.trim($(this).val());
    clearTimeout(bosquejoFiltroTimer);
    bosquejoFiltroTimer = setTimeout(function () {
        $.ajax({
            url     : '../api/bosquejos.php',
            method  : 'GET',
            dataType: 'json',
            data    : { action: 'search', q: q },
            success : function (res) {
                const $lista = $('#lista-bosquejos');
                $lista.empty();
                if (!res.results || res.results.length === 0) {
                    $lista.html('<div class="list-group-item text-muted text-center">Sin resultados</div>');
                    return;
                }
                res.results.forEach(function (b) {
                    const tituloE = $('<span>').text(b.titulo).html();
                    $lista.append(`
                        <div class="list-group-item d-flex justify-content-between align-items-center"
                             id="bosq-row-${b.id}" data-id="${b.id}" data-numero="${b.numero}" data-titulo="${tituloE}">
                            <span>
                                <span class="badge bg-secondary me-2">${b.numero}</span>${tituloE}
                            </span>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline-primary btn-editar-bosquejo"
                                        data-id="${b.id}" data-numero="${b.numero}" data-titulo="${tituloE}"
                                        title="Editar" style="padding:.2rem .5rem;font-size:.75rem;">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-eliminar-bosquejo"
                                        data-id="${b.id}" data-numero="${b.numero}"
                                        title="Eliminar" style="padding:.2rem .5rem;font-size:.75rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>`);
                });
            }
        });
    }, 350);
});

// Crear bosquejo
$(document).on('submit', '#formNuevoBosquejo', function (e) {
    e.preventDefault();
    const numero = $.trim($('#bosquejoNumero').val());
    const titulo = $.trim($('#bosquejoTitulo').val());
    const $btn   = $(this).find('button[type="submit"]').prop('disabled', true);

    apiPost('../api/bosquejos.php', { action: 'create', numero: numero, titulo: titulo }, function (res) {
        $btn.prop('disabled', false);
        const b = res.data;
        const tituloE = $('<span>').text(b.titulo).html();
        const html = `
            <div class="list-group-item d-flex justify-content-between align-items-center"
                 id="bosq-row-${b.id}" data-id="${b.id}" data-numero="${b.numero}" data-titulo="${tituloE}">
                <span>
                    <span class="badge bg-secondary me-2">${b.numero}</span>${tituloE}
                </span>
                <div class="d-flex gap-1">
                    <button class="btn btn-outline-primary btn-editar-bosquejo"
                            data-id="${b.id}" data-numero="${b.numero}" data-titulo="${tituloE}"
                            title="Editar" style="padding:.2rem .5rem;font-size:.75rem;">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-eliminar-bosquejo"
                            data-id="${b.id}" data-numero="${b.numero}"
                            title="Eliminar" style="padding:.2rem .5rem;font-size:.75rem;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>`;

        if ($('#lista-bosquejos').length === 0) {
            $('#card-bosquejos .card-body .msg-empty-bosquejos').replaceWith(
                '<div class="list-group" id="lista-bosquejos">' + html + '</div>'
            );
        } else {
            $('#lista-bosquejos').prepend(html);
        }
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoBosquejo'))?.hide();
        APP.showNotification('Bosquejo #' + b.numero + ' creado', 'success');
    });
    $btn.prop('disabled', false);
});

$(document).on('hidden.bs.modal', '#modalNuevoBosquejo', function () {
    $('#bosquejoNumero').val('');
    $('#bosquejoTitulo').val('');
});

// Editar bosquejo — reutilizamos el modal de crear cambiando el action
$(document).on('click', '.btn-editar-bosquejo', function () {
    const id     = $(this).data('id');
    const numero = $(this).data('numero');
    const titulo = $(this).data('titulo');

    $('#bosquejoEditId').val(id);
    $('#bosquejoEditNumero').val(numero);
    $('#bosquejoEditTitulo').val(titulo);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarBosquejo')).show();
});

$(document).on('submit', '#formEditarBosquejo', function (e) {
    e.preventDefault();
    const id     = $('#bosquejoEditId').val();
    const numero = $.trim($('#bosquejoEditNumero').val());
    const titulo = $.trim($('#bosquejoEditTitulo').val());
    const $btn   = $(this).find('button[type="submit"]').prop('disabled', true);

    apiPost('../api/bosquejos.php', { action: 'update', id: id, numero: numero, titulo: titulo }, function () {
        $btn.prop('disabled', false);
        // Actualizar fila en la lista
        const $row = $('#bosq-row-' + id);
        const tituloE = $('<span>').text(titulo).html();
        $row.find('span').first().html(`<span class="badge bg-secondary me-2">${numero}</span>${tituloE}`);
        $row.data('numero', numero).data('titulo', titulo);
        $row.find('.btn-editar-bosquejo').data('numero', numero).data('titulo', titulo);
        $row.find('.btn-eliminar-bosquejo').data('numero', numero);
        bootstrap.Modal.getInstance(document.getElementById('modalEditarBosquejo'))?.hide();
        APP.showNotification('Bosquejo actualizado', 'success');
    });
    $btn.prop('disabled', false);
});

// Eliminar bosquejo
$(document).on('click', '.btn-eliminar-bosquejo', function () {
    const id     = $(this).data('id');
    const numero = $(this).data('numero');
    if (!confirm('¿Eliminar el bosquejo #' + numero + '?')) return;

    apiPost('../api/bosquejos.php', { action: 'delete', id: id }, function () {
        $('#bosq-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Bosquejo eliminado', 'success');
    });
});
</script>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

<!-- Modal: nuevo bosquejo -->
<div class="modal fade" id="modalNuevoBosquejo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journals"></i> Nuevo Bosquejo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoBosquejo">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">N° *</label>
                            <input type="number" class="form-control" id="bosquejoNumero"
                                   name="numero" min="1" required placeholder="Ej: 41">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" id="bosquejoTitulo"
                                   name="titulo" required maxlength="255"
                                   placeholder="Título del bosquejo">
                        </div>
                    </div>
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

<!-- Modal: editar bosquejo -->
<div class="modal fade" id="modalEditarBosquejo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Bosquejo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarBosquejo">
                <input type="hidden" id="bosquejoEditId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">N° *</label>
                            <input type="number" class="form-control" id="bosquejoEditNumero"
                                   name="numero" min="1" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" id="bosquejoEditTitulo"
                                   name="titulo" required maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: nuevo perfil -->
<div class="modal fade" id="modalNuevoPerfil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge"></i> Nuevo Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoPerfil">
                <div class="modal-body">
                    <label for="nombrePerfil" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="nombrePerfil"
                           placeholder="Ej: Publicador" required maxlength="50">
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
