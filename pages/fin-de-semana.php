<?php
$pageTitle = 'Fin de Semana';
require_once __DIR__ . '/../includes/header.php';

// Verificar que la tabla existe
$tableExists = true;
try {
    $semanas = fetchAll("
        SELECT p.*,
               COALESCE(
                   CONCAT(pe.nombre,' ',pe.apellido),
                   a.nombre_libre,
                   ''
               ) AS orador_nombre
        FROM programas_fds p
        LEFT JOIN asignaciones_fds a  ON a.programa_fds_id = p.id AND a.rol = 'DP_Orador'
        LEFT JOIN personas pe         ON pe.id = a.persona_id
        ORDER BY p.fecha_inicio ASC
    ");
} catch (Exception $e) {
    $tableExists = false;
    $semanas = [];
}

$hoy = date('Y-m-d');

// Contadores para los filtros
$cntTodos    = count($semanas);
$cntActual   = 0;
$cntProximos = 0;
$cntPasados  = 0;
foreach ($semanas as $s) {
    if ($s['fecha_fin'] < $hoy)                                  $cntPasados++;
    elseif ($s['fecha_inicio'] <= $hoy && $s['fecha_fin'] >= $hoy) $cntActual++;
    else                                                           $cntProximos++;
}
$mesNombre = [
    1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',
    5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',
    9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
];

$msg = $_GET['msg'] ?? '';
?>

<!-- Cabecera -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h2 mb-0">
        <i class="bi bi-calendar2-week me-2"></i>Reunión fin de semana
    </h1>
    <?php if ($tableExists && !empty($semanas)): ?>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Filtro pill-tabs -->
        <div class="filter-tabs" role="tablist" aria-label="Filtrar semanas">
            <button class="filter-tab" data-filter="todos" role="tab" aria-selected="false">
                Todos <span class="filter-count"><?php echo $cntTodos; ?></span>
            </button>
            <?php if ($cntActual > 0): ?>
            <button class="filter-tab" data-filter="actual" role="tab" aria-selected="false">
                Esta semana <span class="filter-count"><?php echo $cntActual; ?></span>
            </button>
            <?php endif; ?>
            <?php if ($cntProximos > 0): ?>
            <button class="filter-tab" data-filter="futuro" role="tab" aria-selected="false">
                Próximos <span class="filter-count"><?php echo $cntProximos; ?></span>
            </button>
            <?php endif; ?>
            <?php if ($cntPasados > 0): ?>
            <button class="filter-tab" data-filter="pasado" role="tab" aria-selected="false">
                Pasados <span class="filter-count"><?php echo $cntPasados; ?></span>
            </button>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaSemana">
            <i class="bi bi-plus-circle"></i> Nueva Semana
        </button>
    </div>
    <?php elseif ($tableExists): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaSemana">
        <i class="bi bi-plus-circle"></i> Nueva Semana
    </button>
    <?php endif; ?>
</div>

<?php if ($msg === 'eliminado'): ?>
<div class="alert alert-success alert-dismissible fade show">
    Semana eliminada exitosamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Falta un paso:</strong> importa <code>database_update_v8.sql</code> en phpMyAdmin para activar esta sección.
</div>

<?php elseif (empty($semanas)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h3>No hay semanas registradas</h3>
            <p>Crea la primera semana de Reunión Fin de Semana</p>
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevaSemana">
                <i class="bi bi-plus-circle"></i> Nueva Semana
            </button>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Grid de semanas -->
<div class="row g-4" id="semanasGrid">
    <?php foreach ($semanas as $semana):
        $fi   = new DateTime($semana['fecha_inicio']);
        $ff   = new DateTime($semana['fecha_fin']);
        $dIni = (int)$fi->format('d');
        $dFin = (int)$ff->format('d');
        $mes  = $mesNombre[(int)$fi->format('n')];
        $mesFin = $mesNombre[(int)$ff->format('n')];
        $fechaLabel = ($mes === $mesFin)
            ? "$dIni-$dFin de $mes {$fi->format('Y')}"
            : "$dIni de $mes – $dFin de $mesFin {$ff->format('Y')}";

        $estado = $semana['fecha_fin'] < $hoy ? 'pasado'
               : ($semana['fecha_inicio'] <= $hoy ? 'actual' : 'futuro');
        $badgeHtml = $estado === 'actual'
            ? '<span class="badge bg-success">Esta semana</span>'
            : ($estado === 'pasado'
                ? '<span class="badge bg-secondary">Pasado</span>'
                : '<span class="badge bg-primary">Próximo</span>');
    ?>
    <div class="col-md-6 col-lg-4 semana-item"
         data-estado="<?php echo $estado; ?>"
         data-id="<?php echo $semana['id']; ?>">
        <div class="card h-100 programa-card <?php echo $estado; ?> position-relative">

            <!-- Checkbox de selección -->
            <label class="programa-select-wrap" title="Seleccionar">
                <input type="checkbox" class="programa-checkbox"
                       value="<?php echo $semana['id']; ?>">
                <span class="programa-select-box">
                    <i class="bi bi-check2"></i>
                </span>
            </label>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0 pe-2"><?php echo $fechaLabel; ?></h5>
                    <?php echo $badgeHtml; ?>
                </div>

                <?php if (!empty($semana['dp_tema'])): ?>
                <p class="mb-1 text-muted small">
                    <i class="bi bi-mic"></i>
                    <?php echo htmlspecialchars($semana['dp_tema']); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($semana['orador_nombre'])): ?>
                <p class="mb-3 small">
                    <i class="bi bi-person"></i>
                    <?php echo htmlspecialchars($semana['orador_nombre']); ?>
                </p>
                <?php else: ?>
                <p class="mb-3 small text-muted fst-italic">Sin orador asignado</p>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="fin-de-semana-detalle.php?id=<?php echo $semana['id']; ?>"
                       class="btn btn-sm btn-primary flex-fill">
                        <i class="bi bi-eye"></i> Ver / Asignar
                    </a>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar-semana"
                            data-id="<?php echo $semana['id']; ?>"
                            data-fecha="<?php echo htmlspecialchars($fechaLabel); ?>"
                            title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<!-- Barra flotante de acciones en lote -->
<div class="bulk-bar" id="batchActions" style="display:none;">
    <span class="bulk-bar-count">
        <strong id="batchCount">0</strong> seleccionado(s)
    </span>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-light" id="btnDeselAll">
            <i class="bi bi-x-circle me-1"></i>Deseleccionar
        </button>
        <button class="btn btn-sm btn-danger" id="btnEliminarLote">
            <i class="bi bi-trash me-1"></i>Eliminar
        </button>
    </div>
</div>

<?php endif; ?>

<!-- Modal confirmación eliminación en lote -->
<div class="modal fade" id="modalConfirmLote" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Vas a eliminar <strong id="confirmLoteCount">0</strong> semana(s) y todas sus asignaciones.</p>
                <p class="text-muted mb-0"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmEliminarLote">
                    <i class="bi bi-trash"></i> Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmación eliminar individual -->
<div class="modal fade" id="modalConfirmIndividual" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Vas a eliminar la semana <strong id="confirmIndividualFecha"></strong> y todas sus asignaciones.</p>
                <p class="text-muted mb-0"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmIndividual">
                    <i class="bi bi-trash"></i> Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Reunión fin de semana -->
<div class="modal fade" id="modalNuevaSemana" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar2-week"></i> Reunión fin de semana
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaSemana">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha inicio *</label>
                            <input type="text" class="form-control" id="fds_fecha_inicio"
                                   name="fecha_inicio" placeholder="Selecciona una fecha" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha fin *</label>
                            <input type="text" class="form-control" id="fds_fecha_fin"
                                   name="fecha_fin" placeholder="Se completa automáticamente">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" name="notas" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnCrearSemana">
                        <i class="bi bi-plus-circle"></i> Crear Semana
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ── Crear semana ───────────────────────────────────────────── */
$(document).on('submit', '#formNuevaSemana', function (e) {
    e.preventDefault();
    const $btn = $('#btnCrearSemana').prop('disabled', true);
    $.ajax({
        url: '../api/programas_fds.php', method: 'POST', dataType: 'json',
        data: $(this).serialize() + '&action=create',
        success(res) {
            $btn.prop('disabled', false);
            if (res.success) { window.location.href = 'fin-de-semana.php'; }
            else { APP.showNotification(res.message, 'danger'); }
        },
        error() { $btn.prop('disabled', false); APP.showNotification('Error al conectar', 'danger'); }
    });
});
$(document).on('hidden.bs.modal', '#modalNuevaSemana', () => {
    $('#formNuevaSemana')[0].reset();
    // Destruir instancias Flatpickr al cerrar para forzar reinicio limpio
    const elIni = document.getElementById('fds_fecha_inicio');
    const elFin = document.getElementById('fds_fecha_fin');
    if (elIni._flatpickr) { elIni._flatpickr.destroy(); }
    if (elFin._flatpickr) { elFin._flatpickr.destroy(); }
});

/* ── Inicializar Flatpickr al abrir el modal — siempre fresco ── */
$(document).on('shown.bs.modal', '#modalNuevaSemana', function () {
    if (typeof flatpickr === 'undefined') return;

    const elIni = document.getElementById('fds_fecha_inicio');
    const elFin = document.getElementById('fds_fecha_fin');

    // Destruir instancia previa si existe (del init global de main.js)
    if (elIni._flatpickr) elIni._flatpickr.destroy();
    if (elFin._flatpickr) elFin._flatpickr.destroy();

    const fpBase = {
        locale    : 'es',
        dateFormat: 'Y-m-d',
        altInput  : true,
        altFormat : 'j M Y',
        allowInput: false,
    };

    // Inicializar fecha fin primero (sin onChange)
    const fpFin = flatpickr(elFin, fpBase);

    // Inicializar fecha inicio con onChange que auto-completa el domingo
    flatpickr(elIni, Object.assign({}, fpBase, {
        onChange: function (selectedDates) {
            if (!selectedDates.length) return;
            const d = new Date(selectedDates[0]);
            const diff = d.getDay() === 0 ? 0 : 7 - d.getDay();
            d.setDate(d.getDate() + diff);
            fpFin.setDate(d, true);
        }
    }));
});

/* ── Modales de confirmación (lazy) ────────────────────────── */
let _modalLote = null, _modalInd = null;
function getModalLote() {
    if (!_modalLote && window.bootstrap)
        _modalLote = new bootstrap.Modal(document.getElementById('modalConfirmLote'));
    return _modalLote;
}
function getModalInd() {
    if (!_modalInd && window.bootstrap)
        _modalInd = new bootstrap.Modal(document.getElementById('modalConfirmIndividual'));
    return _modalInd;
}

/* ── Filtro de tabs ─────────────────────────────────────────── */
(function () {
    const tabs  = document.querySelectorAll('.filter-tab');
    const items = document.querySelectorAll('.semana-item');
    if (!tabs.length) return;

    function applyFilter(filter) {
        items.forEach(item => {
            const estado = item.dataset.estado;
            const visible = filter === 'todos' || estado === filter;
            item.style.display = visible ? '' : 'none';
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
            tab.classList.add('active');
            tab.setAttribute('aria-selected','true');
            applyFilter(tab.dataset.filter);
        });
    });

    // Filtro inicial: siempre mostrar todos
    const filtroInicial = 'todos';
    const tabInicial = document.querySelector(`.filter-tab[data-filter="${filtroInicial}"]`)
                    || document.querySelector('.filter-tab[data-filter="todos"]');
    if (tabInicial) {
        tabInicial.classList.add('active');
        tabInicial.setAttribute('aria-selected', 'true');
        applyFilter(filtroInicial);
    }
})();

/* ── Selección en lote ──────────────────────────────────────── */
function getChecked() {
    return [...document.querySelectorAll('.programa-checkbox:checked')];
}
function updateBatchBar() {
    const checked = getChecked();
    const bar = document.getElementById('batchActions');
    if (!bar) return;
    if (checked.length > 0) {
        document.getElementById('batchCount').textContent =
            checked.length + ' seleccionado' + (checked.length > 1 ? 's' : '');
        $(bar).fadeIn(150);
    } else {
        $(bar).fadeOut(150);
    }
}

document.getElementById('semanasGrid')?.addEventListener('change', e => {
    if (e.target.classList.contains('programa-checkbox')) {
        e.target.closest('.semana-item').querySelector('.card')
            .classList.toggle('card-selected', e.target.checked);
        updateBatchBar();
    }
});

document.getElementById('btnDeselAll')?.addEventListener('click', () => {
    document.querySelectorAll('.programa-checkbox:checked').forEach(cb => {
        cb.checked = false;
        cb.closest('.semana-item').querySelector('.card').classList.remove('card-selected');
    });
    updateBatchBar();
});

/* ── Eliminar en lote ───────────────────────────────────────── */
document.getElementById('btnEliminarLote')?.addEventListener('click', () => {
    const n = getChecked().length;
    if (!n) return;
    document.getElementById('confirmLoteCount').textContent = n;
    getModalLote()?.show();
});

document.getElementById('btnConfirmEliminarLote')?.addEventListener('click', function () {
    const ids = getChecked().map(cb => cb.value);
    if (!ids.length) return;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';

    $.post('../api/programas_fds.php', { action: 'delete_batch', ids },
        function (res) {
            if (res.success) {
                window.location.href = 'fin-de-semana.php?msg=eliminado';
            } else {
                getModalLote()?.hide();
                APP.showNotification(res.message, 'danger');
                const btn = document.getElementById('btnConfirmEliminarLote');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash"></i> Sí, eliminar';
            }
        }
    ).fail(() => {
        getModalLote()?.hide();
        APP.showNotification('Error al conectar', 'danger');
    });
});

document.getElementById('modalConfirmLote')?.addEventListener('hidden.bs.modal', () => {
    const btn = document.getElementById('btnConfirmEliminarLote');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-trash"></i> Sí, eliminar';
});

/* ── Eliminar individual ────────────────────────────────────── */
let _pendingDeleteId = null;

$(document).on('click', '.btn-eliminar-semana', function () {
    _pendingDeleteId = $(this).data('id');
    const fecha = $(this).data('fecha');
    document.getElementById('confirmIndividualFecha').textContent = fecha;
    getModalInd()?.show();
});

document.getElementById('btnConfirmIndividual')?.addEventListener('click', function () {
    if (!_pendingDeleteId) return;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';

    $.post('../api/programas_fds.php', { action: 'delete', id: _pendingDeleteId },
        function (res) {
            if (res.success) {
                window.location.href = 'fin-de-semana.php?msg=eliminado';
            } else {
                getModalInd()?.hide();
                APP.showNotification(res.message, 'danger');
            }
        }
    ).fail(() => {
        getModalInd()?.hide();
        APP.showNotification('Error al conectar', 'danger');
    });
});

document.getElementById('modalConfirmIndividual')?.addEventListener('hidden.bs.modal', () => {
    _pendingDeleteId = null;
    const btn = document.getElementById('btnConfirmIndividual');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-trash"></i> Sí, eliminar';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
