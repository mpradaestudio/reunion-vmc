<?php
$pageTitle = 'Fin de Semana – Detalle';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('fin-de-semana.php');

$semana = fetchOne("SELECT * FROM programas_fds WHERE id = ?", [$id]);
if (!$semana) redirect('fin-de-semana.php');

// Semana anterior / siguiente
$anterior  = fetchOne("SELECT id FROM programas_fds WHERE fecha_inicio < ? ORDER BY fecha_inicio DESC LIMIT 1", [$semana['fecha_inicio']]);
$siguiente = fetchOne("SELECT id FROM programas_fds WHERE fecha_inicio > ? ORDER BY fecha_inicio ASC  LIMIT 1", [$semana['fecha_inicio']]);

// Asignaciones actuales
$asigs = fetchAll("
    SELECT a.rol, a.persona_id, a.nombre_libre,
           CONCAT(p.nombre,' ',p.apellido) AS nombre_completo
    FROM asignaciones_fds a
    LEFT JOIN personas p ON p.id = a.persona_id
    WHERE a.programa_fds_id = ?
", [$id]);
$asignaciones = [];
foreach ($asigs as $a) {
    $asignaciones[$a['rol']] = $a;
}

// Personas por capacidad FDS (partes con prefijo FDS_)
$personasPorRol = [];
try {
    $rows = fetchAll("
        SELECT ppd.tipo_parte, p.id, CONCAT(p.nombre,' ',p.apellido) AS nombre_completo
        FROM persona_partes_disponibles ppd
        INNER JOIN personas p ON p.id = ppd.persona_id
        WHERE p.activo = 1 AND ppd.puede_presentar = 1
          AND ppd.tipo_parte LIKE 'FDS_%'
        ORDER BY p.nombre, p.apellido
    ");
    foreach ($rows as $r) {
        // Clave: FDS_Presidente → Presidente
        $rolKey = str_replace('FDS_', '', $r['tipo_parte']);
        $personasPorRol[$rolKey][] = $r;
    }
} catch (Exception $e) { }

// También personas para Orador (texto libre + selector combinado)
$todasPersonas = fetchAll("SELECT id, CONCAT(nombre,' ',apellido) AS nombre_completo FROM personas WHERE activo = 1 ORDER BY nombre, apellido");

// Fecha legible
$fi  = new DateTime($semana['fecha_inicio']);
$ff  = new DateTime($semana['fecha_fin']);
$mesNombre = [
    1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',
    5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',
    9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
];
$dIni = (int)$fi->format('d');
$dFin = (int)$ff->format('d');
$mes  = $mesNombre[(int)$fi->format('n')];
$mesFin = $mesNombre[(int)$ff->format('n')];
$fechaLabel = ($mes === $mesFin)
    ? "$dIni-$dFin de $mes {$fi->format('Y')}"
    : "$dIni de $mes – $dFin de $mesFin {$ff->format('Y')}";

// Helper: render options para select
function optionsFds(array $lista, ?int $selId): string {
    $html = '<option value="">Sin asignar</option>';
    foreach ($lista as $p) {
        $sel  = ($selId && $p['id'] == $selId) ? 'selected' : '';
        $html .= '<option value="' . $p['id'] . '" ' . $sel . '>' . htmlspecialchars($p['nombre_completo']) . '</option>';
    }
    return $html;
}
?>

<!-- Fila 1: navegación -->
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
    <a href="fin-de-semana.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div class="d-flex gap-2">
        <?php if ($anterior): ?>
        <a href="fin-de-semana-detalle.php?id=<?php echo $anterior['id']; ?>"
           class="btn btn-outline-primary">
            <i class="bi bi-chevron-left"></i> Anterior
        </a>
        <?php else: ?>
        <button class="btn btn-outline-primary" disabled><i class="bi bi-chevron-left"></i> Anterior</button>
        <?php endif; ?>

        <?php if ($siguiente): ?>
        <a href="fin-de-semana-detalle.php?id=<?php echo $siguiente['id']; ?>"
           class="btn btn-outline-primary">
            Siguiente <i class="bi bi-chevron-right"></i>
        </a>
        <?php else: ?>
        <button class="btn btn-outline-primary" disabled>Siguiente <i class="bi bi-chevron-right"></i></button>
        <?php endif; ?>
    </div>
    <a href="exportar_pdf_fds.php?id=<?php echo $id; ?>"
       class="btn btn-danger" target="_blank">
        <i class="bi bi-file-pdf"></i> Exportar PDF
    </a>
</div>

<!-- Fila 2: título -->
<div class="mb-4">
    <h1 class="h2 mb-0"><?php echo $fechaLabel; ?></h1>
</div>

<!-- ── DISCURSO PÚBLICO ──────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header text-white fw-bold"
         style="background-color:var(--vmc-primary);">
        <i class="bi bi-mic me-2"></i>DISCURSO PÚBLICO
    </div>
    <div class="card-body">
        <!-- Tema y canción (editable inline) -->
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label fw-bold">Tema</label>
                <input type="text" class="form-control fds-field" id="dp_tema"
                       data-campo="dp_tema"
                       value="<?php echo htmlspecialchars($semana['dp_tema'] ?? ''); ?>"
                       placeholder="Tema del discurso">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Canción</label>
                <input type="text" class="form-control fds-field" id="dp_cancion"
                       data-campo="dp_cancion"
                       value="<?php echo htmlspecialchars($semana['dp_cancion'] ?? ''); ?>"
                       placeholder="Nº canción">
            </div>
        </div>

        <!-- Asignaciones Discurso Público -->
        <div class="row g-3">
            <!-- Presidente DP -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Presidente</label>
                <select class="form-select fds-asig-select" data-rol="DP_Presidente">
                    <?php echo optionsFds($personasPorRol['Presidente'] ?? $todasPersonas,
                                          (int)($asignaciones['DP_Presidente']['persona_id'] ?? 0)); ?>
                </select>
            </div>
            <!-- Orador (puede ser persona o texto libre) -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Orador</label>
                <div class="mb-1">
                    <select class="form-select fds-asig-select" data-rol="DP_Orador" id="sel_dp_orador">
                        <?php echo optionsFds($todasPersonas,
                                              (int)($asignaciones['DP_Orador']['persona_id'] ?? 0)); ?>
                    </select>
                </div>
                <input type="text" class="form-control form-control-sm" id="txt_dp_orador"
                       placeholder="O escribe el nombre del orador (externo)"
                       value="<?php echo htmlspecialchars($asignaciones['DP_Orador']['nombre_libre'] ?? ''); ?>">
                <small class="text-muted">Usa el selector para miembros o escribe un nombre externo</small>
            </div>
        </div>
    </div>
</div>

<!-- ── ESTUDIO DE LA ATALAYA ────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header text-white fw-bold"
         style="background-color:var(--vmc-primary);">
        <i class="bi bi-book me-2"></i>ESTUDIO DE LA ATALAYA
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php
            $rolesEA = [
                'EA_Conductor'    => ['label' => 'Conductor',    'clave' => 'Conductor'],
                'EA_Lector'       => ['label' => 'Lector',       'clave' => 'Lector'],
                'EA_Oracion'      => ['label' => 'Oración',      'clave' => 'Oración'],
                'EA_Hospitalidad' => ['label' => 'Hospitalidad', 'clave' => null],  // texto libre
            ];
            foreach ($rolesEA as $rol => $cfg):
                $asig = $asignaciones[$rol] ?? null;
                $lista = $cfg['clave'] ? ($personasPorRol[$cfg['clave']] ?? $todasPersonas) : [];
            ?>
            <div class="col-md-6">
                <label class="form-label fw-bold"><?php echo $cfg['label']; ?></label>
                <?php if ($cfg['clave']): ?>
                <select class="form-select fds-asig-select" data-rol="<?php echo $rol; ?>">
                    <?php echo optionsFds($lista, (int)($asig['persona_id'] ?? 0)); ?>
                </select>
                <?php else: ?>
                <!-- Hospitalidad: texto libre -->
                <input type="text" class="form-control fds-asig-libre" data-rol="<?php echo $rol; ?>"
                       value="<?php echo htmlspecialchars($asig['nombre_libre'] ?? ''); ?>"
                       placeholder="Ej: Familia García">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<script>
const fdsId = <?php echo $id; ?>;

// ── Guardar campo de texto del programa (tema, canción) ──────
$(document).on('change blur', '.fds-field', function () {
    const campo = $(this).data('campo');
    const valor = $(this).val();
    $.ajax({
        url     : '../api/programas_fds.php',
        method  : 'POST',
        dataType: 'json',
        data    : { action: 'update', id: fdsId, <?php echo "fecha_inicio: '{$semana['fecha_inicio']}', fecha_fin: '{$semana['fecha_fin']}'"; ?>, [campo]: valor },
        success : function (res) {
            if (!res.success) APP.showNotification(res.message, 'danger');
        }
    });
});

// ── Guardar asignación por selector ─────────────────────────
$(document).on('change', '.fds-asig-select', function () {
    const rol       = $(this).data('rol');
    const personaId = $(this).val();
    // Para DP_Orador: limpiar texto libre si se elige persona
    if (rol === 'DP_Orador' && personaId) $('#txt_dp_orador').val('');

    $.ajax({
        url     : '../api/programas_fds.php',
        method  : 'POST',
        dataType: 'json',
        data    : { action: 'save_asignacion', programa_fds_id: fdsId, rol: rol, persona_id: personaId || '' },
        success : function (res) {
            if (res.success) {
                APP.showNotification(res.nombre ? 'Asignado: ' + res.nombre : 'Sin asignar', 'success');
            } else {
                APP.showNotification(res.message, 'danger');
            }
        }
    });
});

// ── Guardar asignación de orador externo (texto libre) ────────
let oradorTimer;
$('#txt_dp_orador').on('input', function () {
    const val = $(this).val().trim();
    clearTimeout(oradorTimer);
    oradorTimer = setTimeout(function () {
        // Si se escribe nombre libre, limpiar selector
        if (val) $('#sel_dp_orador').val('');
        $.ajax({
            url     : '../api/programas_fds.php',
            method  : 'POST',
            dataType: 'json',
            data    : { action: 'save_asignacion', programa_fds_id: fdsId, rol: 'DP_Orador',
                        persona_id: '', nombre_libre: val },
            success : function (res) { if (!res.success) APP.showNotification(res.message, 'danger'); }
        });
    }, 600);
});

// ── Guardar hospitalidad u otros campos de texto libre ────────
let libreTimers = {};
$(document).on('input', '.fds-asig-libre', function () {
    const rol = $(this).data('rol');
    const val = $(this).val().trim();
    clearTimeout(libreTimers[rol]);
    const $el = $(this);
    libreTimers[rol] = setTimeout(function () {
        $.ajax({
            url     : '../api/programas_fds.php',
            method  : 'POST',
            dataType: 'json',
            data    : { action: 'save_asignacion', programa_fds_id: fdsId, rol: rol,
                        persona_id: '', nombre_libre: val },
            success : function (res) { if (!res.success) APP.showNotification(res.message, 'danger'); }
        });
    }, 600);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
