<?php
$pageTitle = 'General';
require_once __DIR__ . '/../includes/header.php';

$config = getConfiguracion();

$mensaje     = '';
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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="bi bi-building me-2"></i>General</h1>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">

        <!-- ── Nombre + Horarios ─────────────────────────────── -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Congregación</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="general_guardar.php">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label for="nombre_congregacion" class="form-label fw-bold">
                            <i class="bi bi-building"></i> Nombre de la Congregación
                        </label>
                        <input type="text" class="form-control"
                               id="nombre_congregacion" name="nombre_congregacion"
                               value="<?php echo htmlspecialchars($config['nombre_congregacion']); ?>"
                               required>
                        <div class="form-text">
                            Aparece en los PDFs exportados y en el encabezado del sistema.
                        </div>
                    </div>

                    <hr>

                    <!-- Horarios — 2 columnas -->
                    <?php $diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo']; ?>
                    <div class="row g-3 mb-3">

                        <!-- Entre Semana -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 vmc-reunion-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-calendar-week vmc-icon-primary"></i>
                                    <span class="fw-bold">Entre Semana</span>
                                </div>
                                <div class="mb-2">
                                    <label for="dia_entre_semana" class="form-label small text-muted mb-1">Día</label>
                                    <select class="form-select vmc-select-primary"
                                            id="dia_entre_semana" name="dia_entre_semana">
                                        <option value="">— Sin definir —</option>
                                        <?php foreach ($diasSemana as $d):
                                            $sel = (($config['dia_entre_semana'] ?? '') === $d) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $d; ?>" <?php echo $sel; ?>><?php echo $d; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="hora_entre_semana" class="form-label small text-muted mb-1">Hora</label>
                                    <input type="time" class="form-control vmc-time-primary"
                                           id="hora_entre_semana" name="hora_entre_semana"
                                           value="<?php echo htmlspecialchars($config['hora_entre_semana'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Fin de Semana -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 vmc-reunion-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-calendar-heart vmc-icon-primary"></i>
                                    <span class="fw-bold">Fin de Semana</span>
                                </div>
                                <div class="mb-2">
                                    <label for="dia_fin_semana" class="form-label small text-muted mb-1">Día</label>
                                    <select class="form-select vmc-select-primary"
                                            id="dia_fin_semana" name="dia_fin_semana">
                                        <option value="">— Sin definir —</option>
                                        <?php foreach ($diasSemana as $d):
                                            $sel = (($config['dia_fin_semana'] ?? '') === $d) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $d; ?>" <?php echo $sel; ?>><?php echo $d; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="hora_fin_semana" class="form-label small text-muted mb-1">Hora</label>
                                    <input type="time" class="form-control vmc-time-primary"
                                           id="hora_fin_semana" name="hora_fin_semana"
                                           value="<?php echo htmlspecialchars($config['hora_fin_semana'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- ── Perfiles de Personas ──────────────────────────── -->
        <div class="card mb-4" id="card-perfiles">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Perfiles de Personas</h5>
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

        <!-- ── Privilegios ──────────────────────────────────── -->
        <div class="card mb-4" id="card-privilegios">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Privilegios</h5>
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

    </div><!-- /col-lg-8 -->
</div><!-- /row -->

<!-- ── Modales ───────────────────────────────────────────────── -->

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

<!-- ── JS ───────────────────────────────────────────────────── -->
<script>
/* ── AM/PM picker ───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.vmc-time-primary').forEach(function (input) {
        const val24 = input.value;
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';

        const selHora = document.createElement('select');
        selHora.className = 'form-select vmc-select-primary vmc-time-h';
        const selMin = document.createElement('select');
        selMin.className = 'form-select vmc-select-primary vmc-time-m';
        const selAmPm = document.createElement('select');
        selAmPm.className = 'form-select vmc-select-primary vmc-time-ampm';

        for (let h = 1; h <= 12; h++) {
            const o = document.createElement('option');
            o.value = String(h); o.textContent = String(h).padStart(2, '0');
            selHora.appendChild(o);
        }
        ['00','05','10','15','20','25','30','35','40','45','50','55'].forEach(function (m) {
            const o = document.createElement('option');
            o.value = m; o.textContent = m; selMin.appendChild(o);
        });
        ['AM','PM'].forEach(function (ap) {
            const o = document.createElement('option');
            o.value = ap; o.textContent = ap; selAmPm.appendChild(o);
        });

        if (val24) {
            const parts = val24.split(':');
            let h = parseInt(parts[0], 10);
            const m = parts[1] || '00';
            const ampm = h >= 12 ? 'PM' : 'AM';
            if (h === 0) h = 12; else if (h > 12) h -= 12;
            selHora.value = String(h); selMin.value = m; selAmPm.value = ampm;
        }

        input.type = 'hidden';
        input.style.display = 'none';

        function syncHidden() {
            let h = parseInt(selHora.value, 10);
            const m = selMin.value, ap = selAmPm.value;
            if (ap === 'AM') { if (h === 12) h = 0; }
            else             { if (h !== 12) h += 12; }
            input.value = String(h).padStart(2, '0') + ':' + m;
        }
        selHora.addEventListener('change', syncHidden);
        selMin.addEventListener('change',  syncHidden);
        selAmPm.addEventListener('change', syncHidden);
        syncHidden();

        wrapper.appendChild(selHora);
        wrapper.appendChild(selMin);
        wrapper.appendChild(selAmPm);
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
    });
});

/* ── Helper AJAX ────────────────────────────────────────────── */
function apiPost(url, data, onSuccess, onError) {
    $.ajax({
        url: url, method: 'POST', dataType: 'json', data: data,
        success: function (res) {
            if (res.success) { if (onSuccess) onSuccess(res); }
            else { APP.showNotification(res.message || 'Error', 'danger'); if (onError) onError(); }
        },
        error: function () {
            APP.showNotification('Error al conectar con el servidor', 'danger');
            if (onError) onError();
        }
    });
}

/* ── Helper: construir fila ─────────────────────────────────── */
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

/* ── Drag & Drop ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    function initSortable(listId, apiUrl) {
        const el = document.getElementById(listId);
        if (!el || typeof Sortable === 'undefined') return;
        Sortable.create(el, {
            handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost',
            onEnd: function () {
                const ids = [...el.querySelectorAll('[data-id]')].map(r => r.dataset.id);
                const fd = new FormData();
                fd.append('action', 'reorder');
                ids.forEach(id => fd.append('ids[]', id));
                fetch(apiUrl, { method: 'POST', body: fd }).catch(() => {});
            }
        });
    }
    initSortable('lista-perfiles',    '../api/perfiles.php');
    initSortable('lista-privilegios', '../api/privilegios.php');
});

/* ── Perfiles CRUD ──────────────────────────────────────────── */
$(document).on('submit', '#formNuevoPerfil', function (e) {
    e.preventDefault();
    const nombre = $.trim($('#nombrePerfil').val());
    const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
    if (!nombre) { $btn.prop('disabled', false); return; }

    apiPost('../api/perfiles.php', { action: 'create', nombre: nombre }, function (res) {
        $btn.prop('disabled', false);
        const html = buildRow(res.data.id, res.data.nombre, 'perf', 'btn-eliminar-perfil');
        const $lista = $('#lista-perfiles');
        if ($lista.length === 0) {
            $('#card-perfiles .card-body .msg-empty-perfiles').replaceWith(
                '<div class="list-group sortable-list" id="lista-perfiles">' + html + '</div>'
            );
        } else { $lista.append(html); }
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoPerfil'))?.hide();
        APP.showNotification('Perfil "' + res.data.nombre + '" creado', 'success');
    }, function () { $btn.prop('disabled', false); });
});
$(document).on('hidden.bs.modal', '#modalNuevoPerfil', function () { $('#nombrePerfil').val(''); });
$(document).on('click', '.btn-eliminar-perfil', function () {
    const id = $(this).data('id'), nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar el perfil "' + nombre + '"?')) return;
    apiPost('../api/perfiles.php', { action: 'delete', id: id }, function () {
        $('#perf-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Perfil eliminado', 'success');
    });
});

/* ── Privilegios CRUD ───────────────────────────────────────── */
$(document).on('submit', '#formNuevoPrivilegio', function (e) {
    e.preventDefault();
    const nombre = $.trim($('#nombrePrivilegio').val());
    const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
    if (!nombre) { $btn.prop('disabled', false); return; }

    apiPost('../api/privilegios.php', { action: 'create', nombre: nombre }, function (res) {
        $btn.prop('disabled', false);
        const html = buildRow(res.data.id, res.data.nombre, 'priv', 'btn-eliminar-privilegio');
        const $lista = $('#lista-privilegios');
        if ($lista.length === 0) {
            $('#card-privilegios .card-body p.text-muted').replaceWith(
                '<div class="list-group sortable-list" id="lista-privilegios">' + html + '</div>'
            );
        } else { $lista.append(html); }
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoPrivilegio'))?.hide();
        APP.showNotification('Privilegio "' + res.data.nombre + '" creado', 'success');
    }, function () { $btn.prop('disabled', false); });
});
$(document).on('hidden.bs.modal', '#modalNuevoPrivilegio', function () { $('#nombrePrivilegio').val(''); });
$(document).on('click', '.btn-eliminar-privilegio', function () {
    const id = $(this).data('id'), nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar el privilegio "' + nombre + '"?')) return;
    apiPost('../api/privilegios.php', { action: 'delete', id: id }, function () {
        $('#priv-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Privilegio eliminado', 'success');
    });
});
</script>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
