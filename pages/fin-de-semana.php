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
        ORDER BY p.fecha_inicio DESC
    ");
} catch (Exception $e) {
    $tableExists = false;
    $semanas = [];
}

$hoy = date('Y-m-d');
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
        <i class="bi bi-calendar2-week me-2"></i>Reunión Fin de Semana
    </h1>
    <?php if ($tableExists): ?>
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
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 programa-card <?php echo $estado; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0 pe-2 fw-bold"><?php echo $fechaLabel; ?></h5>
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
<?php endif; ?>


<!-- Modal: Nueva Semana -->
<div class="modal fade" id="modalNuevaSemana" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar2-week"></i> Nueva Semana Fin de Semana
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaSemana">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha inicio *</label>
                            <input type="date" class="form-control" id="fds_fecha_inicio"
                                   name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha fin *</label>
                            <input type="date" class="form-control" id="fds_fecha_fin"
                                   name="fecha_fin" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tema del discurso</label>
                            <input type="text" class="form-control" id="fds_tema"
                                   name="dp_tema" placeholder="Ej: Cómo tomar buenas decisiones"
                                   maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Canción</label>
                            <input type="text" class="form-control" id="fds_cancion"
                                   name="dp_cancion" placeholder="Ej: 52" maxlength="20">
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
// Crear semana
$(document).on('submit', '#formNuevaSemana', function (e) {
    e.preventDefault();
    const $btn = $('#btnCrearSemana').prop('disabled', true);

    $.ajax({
        url     : '../api/programas_fds.php',
        method  : 'POST',
        dataType: 'json',
        data    : $(this).serialize() + '&action=create',
        success : function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                window.location.href = 'fin-de-semana.php';
            } else {
                APP.showNotification(res.message, 'danger');
            }
        },
        error   : function () {
            $btn.prop('disabled', false);
            APP.showNotification('Error al conectar con el servidor', 'danger');
        }
    });
});

// Limpiar modal al cerrar
$(document).on('hidden.bs.modal', '#modalNuevaSemana', function () {
    $('#formNuevaSemana')[0].reset();
});

// Auto-completar fecha fin (domingo = inicio + 6 días)
$('#fds_fecha_inicio').on('change', function () {
    if ($('#fds_fecha_fin').val()) return;
    const d = new Date(this.value + 'T12:00:00');
    if (isNaN(d)) return;
    // Buscar el domingo de esa semana
    const dia = d.getDay();   // 0=dom
    const diff = dia === 0 ? 0 : 7 - dia;
    d.setDate(d.getDate() + diff);
    $('#fds_fecha_fin').val(d.toISOString().slice(0, 10));
});

// Eliminar semana
$(document).on('click', '.btn-eliminar-semana', function () {
    const id    = $(this).data('id');
    const fecha = $(this).data('fecha');
    if (!confirm('¿Eliminar la semana "' + fecha + '"?\n\nSe eliminarán todas las asignaciones.')) return;

    $.ajax({
        url     : '../api/programas_fds.php',
        method  : 'POST',
        dataType: 'json',
        data    : { action: 'delete', id: id },
        success : function (res) {
            if (res.success) {
                window.location.href = 'fin-de-semana.php?msg=eliminado';
            } else {
                APP.showNotification(res.message, 'danger');
            }
        },
        error   : function () { APP.showNotification('Error al conectar', 'danger'); }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
