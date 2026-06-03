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

// Contadores por estado (para las píldoras del filtro)
$hoy = date('Y-m-d');
$cntTodos    = count($programas);
$cntProximos = 0;
$cntActual   = 0;
$cntPasados  = 0;
foreach ($programas as $p) {
    if ($p['fecha_fin'] < $hoy)                                          $cntPasados++;
    elseif ($p['fecha_inicio'] <= $hoy && $p['fecha_fin'] >= $hoy)       $cntActual++;
    else                                                                  $cntProximos++;
}

// Procesar mensajes
$mensaje = '';
$tipoMensaje = 'success';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'extraido':  $mensaje = 'Programa extraído exitosamente'; break;
        case 'eliminado': $mensaje = 'Programa(s) eliminado(s) exitosamente'; break;
        case 'error':     $mensaje = 'Ocurrió un error al procesar la solicitud'; $tipoMensaje = 'danger'; break;
    }
}

$mesNombre = [
    1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
    7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
];
?>

<!-- Cabecera: título + filtro + botón extraer (misma línea) -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h2 mb-0">
        <i class="bi bi-calendar-week me-2"></i>Programas Semanales
    </h1>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if (count($programas) > 0): ?>
        <!-- Filtro pill-tabs -->
        <div class="filter-tabs" role="tablist" aria-label="Filtrar programas">
            <button class="filter-tab active" data-filter="todos" role="tab" aria-selected="true">
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
        <?php endif; ?>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExtraer">
            <i class="bi bi-cloud-download"></i> Extraer Programa
        </button>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (count($programas) > 0): ?>

<!-- Barra de acciones en lote (visible al seleccionar al menos 1) -->
<div class="batch-actions mb-4" id="batchActions" style="display:none;">
    <span class="batch-count" id="batchCount">0 seleccionados</span>
    <button class="btn btn-sm btn-outline-secondary" id="btnDeselAll">
        <i class="bi bi-x-circle"></i> Deseleccionar
    </button>
    <button class="btn btn-sm btn-danger" id="btnEliminarLote">
        <i class="bi bi-trash"></i> Eliminar seleccionados
    </button>
</div>

<!-- ── Grid de tarjetas ─────────────────────────────────────────────── -->
<div class="row g-4" id="programasGrid">
    <?php foreach ($programas as $programa):
        // Estado
        $claseEstado = '';
        $badgeHtml   = '';
        if ($programa['fecha_fin'] < $hoy) {
            $claseEstado = 'pasado';
            $badgeHtml   = '<span class="badge bg-secondary">Pasado</span>';
        } elseif ($programa['fecha_inicio'] <= $hoy && $programa['fecha_fin'] >= $hoy) {
            $claseEstado = 'actual';
            $badgeHtml   = '<span class="badge bg-success">Esta semana</span>';
        } else {
            $claseEstado = 'futuro';
            $badgeHtml   = '<span class="badge bg-primary">Próximo</span>';
        }

        // Fecha legible
        $fi  = new DateTime($programa['fecha_inicio']);
        $ff  = new DateTime($programa['fecha_fin']);
        $mes = $mesNombre[(int)$fi->format('n')];
        $mesFin = $mesNombre[(int)$ff->format('n')];
        if ($mes === $mesFin) {
            $fechaFormato = $fi->format('d') . '-' . $ff->format('d') . ' de ' . $mes . ' ' . $fi->format('Y');
        } else {
            $fechaFormato = $fi->format('d') . ' de ' . $mes . ' – ' . $ff->format('d') . ' de ' . $mesFin . ' ' . $ff->format('Y');
        }
    ?>
    <div class="col-md-6 col-lg-4 programa-item"
         data-estado="<?php echo $claseEstado; ?>"
         data-id="<?php echo $programa['id']; ?>">

        <div class="card programa-card <?php echo $claseEstado; ?> h-100 position-relative">

            <!-- Checkbox de selección -->
            <label class="programa-select-wrap" title="Seleccionar">
                <input type="checkbox" class="programa-checkbox"
                       value="<?php echo $programa['id']; ?>"
                       aria-label="Seleccionar <?php echo htmlspecialchars($programa['titulo_semana']); ?>">
                <span class="programa-select-box">
                    <i class="bi bi-check2"></i>
                </span>
            </label>

            <div class="card-body">
                <!-- Título + badge estado -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0 pe-2">
                        <?php echo htmlspecialchars($programa['titulo_semana']); ?>
                    </h5>
                    <?php echo $badgeHtml; ?>
                </div>

                <!-- Fecha -->
                <p class="text-muted mb-2">
                    <i class="bi bi-calendar3"></i>
                    <small><?php echo $fechaFormato; ?></small>
                </p>

                <!-- Referencia bíblica -->
                <?php if ($programa['referencia_biblica']): ?>
                <p class="mb-2">
                    <i class="bi bi-book"></i>
                    <small><strong><?php echo htmlspecialchars($programa['referencia_biblica']); ?></strong></small>
                </p>
                <?php endif; ?>

                <!-- Canciones -->
                <div class="mb-3">
                    <small class="text-muted">
                        <i class="bi bi-music-note"></i>
                        Canciones: <?php echo $programa['cancion_inicial']; ?>,
                        <?php echo $programa['cancion_media']; ?>,
                        <?php echo $programa['cancion_final']; ?>
                    </small>
                </div>

                <!-- Partes / roles -->
                <div class="mb-3">
                    <small>
                        <i class="bi bi-list-check"></i>
                        <?php echo $programa['total_secciones']; ?> partes |
                        <i class="bi bi-person-check"></i>
                        <?php echo $programa['roles_asignados']; ?> roles asignados
                    </small>
                </div>

                <!-- Acciones -->
                <div class="d-flex gap-2">
                    <a href="programa_detalle.php?id=<?php echo $programa['id']; ?>"
                       class="btn btn-sm btn-primary flex-fill">
                        <i class="bi bi-eye"></i> Ver / Asignar
                    </a>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar-programa"
                            data-id="<?php echo $programa['id']; ?>"
                            data-titulo="<?php echo htmlspecialchars($programa['titulo_semana']); ?>"
                            title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div><!-- /card-body -->
        </div><!-- /card -->
    </div><!-- /col -->
    <?php endforeach; ?>
</div><!-- /row -->

<!-- Estado vacío cuando el filtro no devuelve resultados -->
<div id="emptyFilter" class="text-center py-5" style="display:none;">
    <i class="bi bi-funnel" style="font-size:3rem; opacity:.4;"></i>
    <p class="mt-3 text-muted">No hay programas con ese filtro.</p>
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


<!-- ── Modal extraer programa ───────────────────────────────────────── -->
<div class="modal fade" id="modalExtraer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cloud-download"></i> Extraer Programa de jw.org
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    <small>Pega la <strong>URL de la semana</strong> que quieres importar desde jw.org y haz clic en <strong>Extraer</strong>.</small>
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

<!-- ── Modal confirmación eliminación en lote ───────────────────────── -->
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
                <p>Vas a eliminar <strong id="confirmLoteCount">0</strong> programa(s) y todas sus asignaciones.</p>
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


<script>
/* ================================================================
   FILTRO PILL-TABS
   Muestra/oculta las tarjetas (.programa-item) animando su opacidad
   según el data-estado del item.
================================================================ */
(function () {
    const tabs   = document.querySelectorAll('.filter-tab');
    const items  = document.querySelectorAll('.programa-item');
    const empty  = document.getElementById('emptyFilter');

    function applyFilter(filter) {
        let visible = 0;
        items.forEach(item => {
            const matches = filter === 'todos' || item.dataset.estado === filter;
            if (matches) {
                item.style.display = '';
                // Reiniciar la animación de entrada en cada filtrado
                item.classList.remove('animate-in');
                void item.offsetWidth;        // forzar reflow para reproducir de nuevo
                item.classList.add('animate-in');
                visible++;
            } else {
                item.style.display = 'none';
                // Desmarcar checkbox de tarjetas ocultas
                const cb = item.querySelector('.programa-checkbox');
                if (cb && cb.checked) {
                    cb.checked = false;
                    const card = item.querySelector('.card');
                    if (card) card.classList.remove('card-selected');
                }
            }
        });
        empty.style.display = visible === 0 ? 'block' : 'none';
        updateBatchBar();
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
            tab.classList.add('active');
            tab.setAttribute('aria-selected','true');
            applyFilter(tab.dataset.filter);
        });
    });
})();

/* ================================================================
   SELECCIÓN EN LOTE
================================================================ */
const batchActions  = document.getElementById('batchActions');
const batchCountEl  = document.getElementById('batchCount');
const btnDeselAll   = document.getElementById('btnDeselAll');
const btnElimLote   = document.getElementById('btnEliminarLote');
const confirmModal  = new bootstrap.Modal(document.getElementById('modalConfirmLote'));
const confirmCount  = document.getElementById('confirmLoteCount');
const btnConfirm    = document.getElementById('btnConfirmEliminarLote');

function getChecked() {
    return [...document.querySelectorAll('.programa-checkbox:checked')];
}

function updateBatchBar() {
    const checked = getChecked();
    if (checked.length > 0) {
        batchCountEl.textContent = checked.length + ' seleccionado' + (checked.length > 1 ? 's' : '');
        batchActions.style.display = 'flex';
    } else {
        batchActions.style.display = 'none';
    }
}

// Delegación: cualquier checkbox cambia → actualizar barra
document.getElementById('programasGrid')?.addEventListener('change', e => {
    if (e.target.classList.contains('programa-checkbox')) {
        const card = e.target.closest('.programa-item');
        // Resaltar visualmente la card seleccionada
        card.querySelector('.card').classList.toggle('card-selected', e.target.checked);
        updateBatchBar();
    }
});

// Deseleccionar todos
btnDeselAll?.addEventListener('click', () => {
    document.querySelectorAll('.programa-checkbox:checked').forEach(cb => {
        cb.checked = false;
        cb.closest('.programa-item').querySelector('.card').classList.remove('card-selected');
    });
    updateBatchBar();
});

// Abrir modal de confirmación
btnElimLote?.addEventListener('click', () => {
    const n = getChecked().length;
    if (n === 0) return;
    confirmCount.textContent = n;
    confirmModal.show();
});

// Confirmar y ejecutar eliminación en lote
btnConfirm?.addEventListener('click', function () {
    const ids = getChecked().map(cb => cb.value);
    if (ids.length === 0) return;

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';

    $.post('../api/programas.php', {
        action : 'delete_batch',
        ids    : ids
    }, function (response) {
        if (response.success) {
            window.location.href = 'programas.php?msg=eliminado';
        } else {
            confirmModal.hide();
            APP.showNotification(response.message, 'danger');
            document.getElementById('btnConfirmEliminarLote').disabled = false;
            document.getElementById('btnConfirmEliminarLote').innerHTML = '<i class="bi bi-trash"></i> Sí, eliminar';
        }
    }).fail(function () {
        confirmModal.hide();
        APP.showNotification('Error al conectar con el servidor', 'danger');
    });
});

// Limpiar modal confirmación al cerrar
document.getElementById('modalConfirmLote').addEventListener('hidden.bs.modal', function () {
    const btn = document.getElementById('btnConfirmEliminarLote');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-trash"></i> Sí, eliminar';
});

/* ================================================================
   EXTRAER PROGRAMA
================================================================ */
$('#btnExtraerUrl').on('click', function () {
    const url = $('#urlSemana').val().trim();
    if (!url) {
        $('#extraerEstado').html('<div class="alert alert-warning mb-0">Pega la URL de una semana de jw.org</div>');
        return;
    }
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Extrayendo...');
    $('#extraerEstado').html('<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Descargando desde jw.org...</div>');

    $.ajax({
        url      : '../api/scraper.php',
        method   : 'POST',
        data     : { action: 'scrape_semana', url: url },
        dataType : 'json',
        timeout  : 60000,
        success  : function (response) {
            if (response.success) {
                $('#extraerEstado').html(
                    '<div class="alert alert-success mb-0"><i class="bi bi-check-circle"></i> ' +
                    response.message + ' (' + response.partes + ' partes). Actualizando...</div>'
                );
                setTimeout(() => { window.location.href = 'programas.php?msg=extraido'; }, 1200);
            } else {
                $('#extraerEstado').html(
                    '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-circle"></i> ' +
                    response.message + '</div>'
                );
                btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
            }
        },
        error: function (xhr, status) {
            const msg = status === 'timeout' ? 'La descarga tardó demasiado. Intenta de nuevo.' : 'Error al conectar con el servidor';
            $('#extraerEstado').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
        }
    });
});

$('#modalExtraer').on('hidden.bs.modal', function () {
    $('#extraerEstado').empty();
    $('#urlSemana').val('');
    $('#btnExtraerUrl').prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Extraer');
});

/* ================================================================
   ELIMINAR INDIVIDUAL
================================================================ */
$(document).on('click', '.btn-eliminar-programa', function () {
    const id     = $(this).data('id');
    const titulo = $(this).data('titulo');
    if (confirm('¿Eliminar el programa "' + titulo + '"?\n\nSe eliminarán todas las asignaciones asociadas.')) {
        $.post('../api/programas.php', { action: 'delete', id: id }, function (response) {
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
