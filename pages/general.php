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

                    <p class="fw-bold mb-2"><i class="bi bi-clock me-1 vmc-icon-primary"></i>Horarios de Reunión</p>

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

    <!-- ── Columna derecha: Eventos Especiales ──────────────── -->
    <div class="col-lg-4">

        <?php
        // Cargar eventos agrupados por tipo
        $eventosPorTipo = ['regional' => [], 'circuito' => [], 'visita' => [], 'conmemoracion' => []];
        $eventosTableExists = true;
        try {
            $eventosRows = fetchAll("SELECT * FROM eventos_especiales ORDER BY fecha_inicio ASC");
            foreach ($eventosRows as $ev) {
                $eventosPorTipo[$ev['tipo']][] = $ev;
            }
        } catch (Exception $e) {
            $eventosTableExists = false;
        }

        // Configuración de cada tipo
        $eventoConfig = [
            'regional' => [
                'label'  => 'Asamblea Regional',
                'icon'   => 'bi-building-fill',
                'limite' => 1,
                'un_dia' => false,
                'hint'   => '3 días',
            ],
            'circuito' => [
                'label'  => 'Asamblea de Circuito',
                'icon'   => 'bi-people-fill',
                'limite' => 2,
                'un_dia' => true,
                'hint'   => '1 día',
            ],
            'visita' => [
                'label'  => 'Visita de Circuito',
                'icon'   => 'bi-person-check-fill',
                'limite' => 2,
                'un_dia' => false,
                'hint'   => 'Martes a domingo',
            ],
            'conmemoracion' => [
                'label'  => 'Conmemoración',
                'icon'   => 'bi-heart-fill',
                'limite' => 1,
                'un_dia' => true,
                'hint'   => '1 día',
            ],
        ];

        $meses = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',
                  7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];

        function fmtFecha(string $fecha, array $meses): string {
            $d  = new DateTime($fecha);
            return (int)$d->format('d') . ' ' . $meses[(int)$d->format('n')] . ' ' . $d->format('Y');
        }
        function fmtRango(string $ini, string $fin, array $meses): string {
            if ($ini === $fin) return fmtFecha($ini, $meses);
            $dI = new DateTime($ini); $dF = new DateTime($fin);
            $dInum = (int)$dI->format('d'); $dFnum = (int)$dF->format('d');
            $mI = (int)$dI->format('n'); $mF = (int)$dF->format('n');
            if ($mI === $mF) {
                return $dInum . '–' . $dFnum . ' ' . $meses[$mI] . ' ' . $dI->format('Y');
            }
            return fmtFecha($ini, $meses) . ' – ' . fmtFecha($fin, $meses);
        }
        ?>

        <?php if (!$eventosTableExists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Importa <code>database_update_v12.sql</code> para activar esta sección.
        </div>
        <?php else: ?>

        <?php foreach ($eventoConfig as $tipo => $cfg):
            $lista   = $eventosPorTipo[$tipo];
            $lleno   = count($lista) >= $cfg['limite'];
        ?>
        <div class="card mb-3" id="card-evento-<?php echo $tipo; ?>"
             data-tipo="<?php echo $tipo; ?>"
             data-limite="<?php echo $cfg['limite']; ?>">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold small">
                    <i class="bi <?php echo $cfg['icon']; ?> me-1 vmc-icon-primary"></i>
                    <?php echo $cfg['label']; ?>
                </span>
                <?php if (!$lleno): ?>
                <button class="btn btn-sm btn-primary py-0 px-2 btn-agregar-evento"
                        data-tipo="<?php echo $tipo; ?>"
                        data-un-dia="<?php echo $cfg['un_dia'] ? '1' : '0'; ?>"
                        data-label="<?php echo htmlspecialchars($cfg['label']); ?>"
                        data-hint="<?php echo htmlspecialchars($cfg['hint']); ?>"
                        style="font-size:.75rem;">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-2" id="eventos-lista-<?php echo $tipo; ?>">
                <?php if (empty($lista)): ?>
                <p class="text-muted small mb-0 text-center py-2 msg-empty-evento">
                    Sin fechas registradas
                </p>
                <?php else: ?>
                <?php foreach ($lista as $ev): ?>
                <div class="d-flex justify-content-between align-items-center
                            border rounded px-2 py-1 mb-1 evento-row"
                     id="evento-row-<?php echo $ev['id']; ?>">
                    <span class="small">
                        <i class="bi bi-calendar3 me-1 text-muted"></i>
                        <?php echo fmtRango($ev['fecha_inicio'], $ev['fecha_fin'], $meses); ?>
                        <?php if (!empty($ev['notas'])): ?>
                        <span class="badge bg-info-subtle text-info ms-1" style="font-size:.7rem;">
                            <?php echo htmlspecialchars($ev['notas']); ?>
                        </span>
                        <?php endif; ?>
                    </span>
                    <button class="btn btn-link btn-sm text-danger p-0 ms-2 btn-eliminar-evento"
                            data-id="<?php echo $ev['id']; ?>"
                            title="Eliminar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>

    </div><!-- /col-lg-4 -->

</div><!-- /row -->

<!-- ── Modal: Agregar Evento ────────────────────────────────── -->
<div class="modal fade" id="modalAgregarEvento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="modalEventoTitulo"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAgregarEvento">
                <input type="hidden" id="eventoTipo">
                <input type="hidden" id="eventoUnDia">
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="eventoHint"></p>

                    <div class="mb-3">
                        <label for="eventoFechaInicio" class="form-label fw-semibold small">
                            Fecha inicio
                        </label>
                        <input type="date" class="form-control form-control-sm vmc-select-primary"
                               id="eventoFechaInicio"
                               data-fp-mode="single"
                               data-fp-linked="eventoFechaFin"
                               required>
                    </div>

                    <div id="eventoFechaFinWrap" class="mb-3">
                        <label for="eventoFechaFin" class="form-label fw-semibold small">
                            Fecha fin
                        </label>
                        <input type="date" class="form-control form-control-sm vmc-select-primary"
                               id="eventoFechaFin"
                               data-fp-mode="single">
                    </div>

                    <!-- Solo para Asamblea de Circuito -->
                    <div id="eventoCircuitoOpciones" class="mb-1" style="display:none;">
                        <label class="form-label fw-semibold small mb-1">Tipo de visita</label>
                        <div class="d-flex flex-column gap-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="circuito_tipo" id="circuito_representante"
                                       value="Con representante de la sucursal">
                                <label class="form-check-label small" for="circuito_representante">
                                    Con representante de la sucursal
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="circuito_tipo" id="circuito_superintendente"
                                       value="Con superintendente de circuito">
                                <label class="form-check-label small" for="circuito_superintendente">
                                    Con superintendente de circuito
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnEventoGuardar">
                        <i class="bi bi-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
let _pendingPerfil = null;
$(document).on('click', '.btn-eliminar-perfil', function () {
    _pendingPerfil = { id: $(this).data('id'), nombre: $(this).data('nombre') };
    $('#confirmPerfilNombre').text(_pendingPerfil.nombre);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmPerfil')).show();
});
$(document).on('click', '#btnConfirmPerfil', function () {
    if (!_pendingPerfil) return;
    const { id } = _pendingPerfil;
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
    apiPost('../api/perfiles.php', { action: 'delete', id }, function () {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmPerfil'))?.hide();
        $('#perf-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Perfil eliminado', 'success');
    });
});
$(document).on('hidden.bs.modal', '#modalConfirmPerfil', function () {
    _pendingPerfil = null;
    $('#btnConfirmPerfil').prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Sí, eliminar');
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
let _pendingPrivilegio = null;
$(document).on('click', '.btn-eliminar-privilegio', function () {
    _pendingPrivilegio = { id: $(this).data('id'), nombre: $(this).data('nombre') };
    $('#confirmPrivilegioNombre').text(_pendingPrivilegio.nombre);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmPrivilegio')).show();
});
$(document).on('click', '#btnConfirmPrivilegio', function () {
    if (!_pendingPrivilegio) return;
    const { id } = _pendingPrivilegio;
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
    apiPost('../api/privilegios.php', { action: 'delete', id }, function () {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmPrivilegio'))?.hide();
        $('#priv-row-' + id).fadeOut(200, function () { $(this).remove(); });
        APP.showNotification('Privilegio eliminado', 'success');
    });
});
$(document).on('hidden.bs.modal', '#modalConfirmPrivilegio', function () {
    _pendingPrivilegio = null;
    $('#btnConfirmPrivilegio').prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Sí, eliminar');
});
</script>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

<script>
/* ── Eventos Especiales ─────────────────────────────────────── */
const MESES_CORTOS = ['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];

function fmtFechaJS(str) {
    if (!str) return '';
    const [y, m, d] = str.split('-');
    return parseInt(d) + ' ' + MESES_CORTOS[parseInt(m)] + ' ' + y;
}
function fmtRangoJS(ini, fin) {
    if (!fin || ini === fin) return fmtFechaJS(ini);
    const [yI, mI, dI] = ini.split('-').map(Number);
    const [yF, mF, dF] = fin.split('-').map(Number);
    if (mI === mF && yI === yF) return dI + '–' + dF + ' ' + MESES_CORTOS[mI] + ' ' + yI;
    return fmtFechaJS(ini) + ' – ' + fmtFechaJS(fin);
}

function buildEventoRow(ev) {
    const label = fmtRangoJS(ev.fecha_inicio, ev.fecha_fin);
    const notaBadge = ev.notas
        ? `<span class="badge bg-info-subtle text-info ms-1" style="font-size:.7rem;">${$('<span>').text(ev.notas).html()}</span>`
        : '';
    return `
        <div class="d-flex justify-content-between align-items-center
                    border rounded px-2 py-1 mb-1 evento-row"
             id="evento-row-${ev.id}">
            <span class="small">
                <i class="bi bi-calendar3 me-1 text-muted"></i>${label}${notaBadge}
            </span>
            <button class="btn btn-link btn-sm text-danger p-0 ms-2 btn-eliminar-evento"
                    data-id="${ev.id}" title="Eliminar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`;
}

// Abrir modal
$(document).on('click', '.btn-agregar-evento', function () {
    const tipo  = $(this).data('tipo');
    const unDia = $(this).data('un-dia') === 1 || $(this).data('un-dia') === '1';
    const label = $(this).data('label');
    const hint  = $(this).data('hint');

    $('#eventoTipo').val(tipo);
    $('#eventoUnDia').val(unDia ? '1' : '0');
    $('#modalEventoTitulo').text(label);
    $('#eventoHint').text(hint);

    // Limpiar campos y Flatpickr
    const fpIni = document.getElementById('eventoFechaInicio')._flatpickr;
    const fpFin = document.getElementById('eventoFechaFin')._flatpickr;
    if (fpIni) fpIni.clear(); else $('#eventoFechaInicio').val('');
    if (fpFin) fpFin.clear(); else $('#eventoFechaFin').val('');

    // Eventos de 1 día: ocultar fecha fin
    $('#eventoFechaFinWrap').toggle(!unDia);

    // Opciones de circuito: solo para Asamblea de Circuito
    $('input[name="circuito_tipo"]').prop('checked', false);
    $('#eventoCircuitoOpciones').toggle(tipo === 'circuito');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgregarEvento')).show();
});

// Auto-relleno de fecha fin según tipo de evento
$('#eventoFechaInicio').on('change', function () {
    const tipo  = $('#eventoTipo').val();
    const unDia = $('#eventoUnDia').val() === '1';
    if (unDia || !this.value) return;

    const d = new Date(this.value + 'T12:00:00');
    if (isNaN(d)) return;

    let fechaFin = null;

    if (tipo === 'regional') {
        // 3 días consecutivos: inicio + 2
        d.setDate(d.getDate() + 2);
        fechaFin = d.toISOString().slice(0, 10);
    } else if (tipo === 'visita') {
        // Martes a domingo: buscar el domingo de esa semana
        // getDay(): 0=dom,1=lun,2=mar,...,6=sab
        const diaSemana = d.getDay(); // debe ser martes (2)
        const diffDomingo = diaSemana === 0 ? 0 : 7 - diaSemana;
        d.setDate(d.getDate() + diffDomingo);
        fechaFin = d.toISOString().slice(0, 10);
    }

    if (fechaFin) {
        const fpFin = document.getElementById('eventoFechaFin')._flatpickr;
        if (fpFin) fpFin.setDate(fechaFin, true);
        else $('#eventoFechaFin').val(fechaFin);
    }
});

// Guardar evento
$(document).on('submit', '#formAgregarEvento', function (e) {
    e.preventDefault();
    const tipo     = $('#eventoTipo').val();
    const unDia    = $('#eventoUnDia').val() === '1';
    const fechaIni = $('#eventoFechaInicio').val();
    const fechaFin = unDia ? fechaIni : ($('#eventoFechaFin').val() || fechaIni);
    const $btn     = $('#btnEventoGuardar').prop('disabled', true);

    if (!fechaIni) {
        APP.showNotification('Ingresa la fecha de inicio', 'warning');
        $btn.prop('disabled', false); return;
    }
    if (!unDia && fechaFin && fechaFin < fechaIni) {
        APP.showNotification('La fecha fin no puede ser anterior a la fecha inicio', 'warning');
        $btn.prop('disabled', false); return;
    }

    apiPost('../api/eventos.php',
        { action: 'create', tipo, fecha_inicio: fechaIni, fecha_fin: fechaFin,
          notas: tipo === 'circuito' ? ($('input[name="circuito_tipo"]:checked').val() || '') : '' },
        function (res) {
            $btn.prop('disabled', false);
            bootstrap.Modal.getInstance(document.getElementById('modalAgregarEvento'))?.hide();
            $('#eventoFechaInicio, #eventoFechaFin').val('');

            const $lista = $(`#eventos-lista-${tipo}`);
            $lista.find('.msg-empty-evento').remove();
            $lista.append(buildEventoRow(res.data));

            // Ocultar botón "+" si se llegó al límite (contar filas actuales)
            const $card  = $(`#card-evento-${tipo}`);
            const limite = parseInt($card.data('limite') || 99);
            if ($lista.find('.evento-row').length >= limite) {
                $card.find('.btn-agregar-evento').hide();
            }

            APP.showNotification('Evento guardado', 'success');
        },
        function () { $btn.prop('disabled', false); }
    );
});

// Eliminar evento
let _pendingEvento = null;
$(document).on('click', '.btn-eliminar-evento', function () {
    const id    = $(this).data('id');
    const $row  = $(`#evento-row-${id}`);
    const tipo  = $row.closest('.card').data('tipo') || '';
    const fecha = $row.find('span.small').text().trim();
    _pendingEvento = { id, tipo };
    $('#confirmEventoFecha').text(fecha);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmEvento')).show();
});
$(document).on('click', '#btnConfirmEvento', function () {
    if (!_pendingEvento) return;
    const { id, tipo } = _pendingEvento;
    const $row = $(`#evento-row-${id}`);
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
    apiPost('../api/eventos.php', { action: 'delete', id }, function () {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmEvento'))?.hide();
        $row.fadeOut(200, function () {
            $(this).remove();
            const $lista = $(`#eventos-lista-${tipo}`);
            if ($lista.find('.evento-row').length === 0) {
                $lista.html('<p class="text-muted small mb-0 text-center py-2 msg-empty-evento">Sin fechas registradas</p>');
            }
            $(`#card-evento-${tipo} .btn-agregar-evento`).show();
        });
        APP.showNotification('Evento eliminado', 'success');
    });
});
$(document).on('hidden.bs.modal', '#modalConfirmEvento', function () {
    _pendingEvento = null;
    $('#btnConfirmEvento').prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Sí, eliminar');
});
</script>

<!-- ── Modales de confirmación ────────────────────────────────── -->

<!-- Eliminar perfil -->
<div class="modal fade" id="modalConfirmPerfil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar el perfil <strong id="confirmPerfilNombre"></strong>?</p>
                <p class="text-muted mb-0 small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmPerfil">
                    <i class="bi bi-trash me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Eliminar privilegio -->
<div class="modal fade" id="modalConfirmPrivilegio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar privilegio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar el privilegio <strong id="confirmPrivilegioNombre"></strong>?</p>
                <p class="text-muted mb-0 small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmPrivilegio">
                    <i class="bi bi-trash me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Eliminar evento -->
<div class="modal fade" id="modalConfirmEvento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar el evento del <strong id="confirmEventoFecha"></strong>?</p>
                <p class="text-muted mb-0 small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmEvento">
                    <i class="bi bi-trash me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
