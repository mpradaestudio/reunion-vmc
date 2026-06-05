<?php
$pageTitle = 'Fin de Semana – Detalle';

// Select2 CSS — idéntico a programa_detalle.php
$extraHeadHtml = '
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        /* Ajuste dentro de input-group */
        .input-group .select2-container { flex: 1 1 auto; min-width: 0; }
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: var(--vmc-radius-sm);
        }

        /* Sin sombra en focus/open — borde primario */
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open  .select2-selection {
            border-color: var(--vmc-primary) !important;
            box-shadow : none !important;
        }

        /* Campo de búsqueda dentro del dropdown */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-search .select2-search__field:focus {
            border-color: var(--vmc-primary) !important;
            box-shadow : none !important;
            outline    : none;
        }

        /* Opción seleccionada */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option.select2-results__option--selected,
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option[aria-selected=true]:not(.select2-results__option--highlighted) {
            background-color: var(--vmc-primary-soft) !important;
            color           : var(--vmc-primary)      !important;
        }

        /* Opción con hover */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option--highlighted {
            background-color: var(--vmc-primary) !important;
            color           : #ffffff             !important;
        }

        /* Dark mode */
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
        [data-bs-theme="dark"] .select2-dropdown {
            background-color: var(--vmc-surface-2);
            border-color    : var(--vmc-border-strong);
            color           : var(--vmc-text);
        }
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
            color: var(--vmc-text);
        }
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
            background-color: var(--vmc-surface-2);
            color           : var(--vmc-text);
        }
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options .select2-results__option--highlighted {
            background-color: var(--vmc-primary) !important;
            color           : #fff               !important;
        }
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option[aria-selected=true]:not(.select2-results__option--highlighted) {
            background-color: rgba(74,109,167,.22) !important;
            color           : #9bb6e6              !important;
        }
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-search__field {
            background-color: var(--vmc-surface-3);
            border-color    : var(--vmc-border-strong);
            color           : var(--vmc-text);
        }

        /* Badge de número en resultados del bosquejo */
        .bosquejo-result-num {
            display      : inline-block;
            background   : var(--vmc-primary);
            color        : #fff;
            border-radius: 4px;
            padding      : 1px 6px;
            font-size    : .78rem;
            font-weight  : 700;
            margin-right : 6px;
        }
    </style>
';

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

// Bosquejo seleccionado actualmente (para pre-cargar Select2)
$bosquejoActual = null;
if (!empty($semana['dp_bosquejo_id'])) {
    try {
        $bosquejoActual = fetchOne(
            "SELECT id, numero, titulo FROM bosquejos WHERE id = ?",
            [$semana['dp_bosquejo_id']]
        );
    } catch (Exception $e) { /* tabla aún no existe */ }
}

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
        <!-- Tema (Select2 busca por número o palabras) + canción -->
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label fw-bold">Tema del discurso</label>
                <select class="form-select" id="sel_dp_bosquejo" style="width:100%;">
                    <?php if ($bosquejoActual): ?>
                    <option value="<?php echo $bosquejoActual['id']; ?>" selected>
                        <?php echo $bosquejoActual['numero'] . ' — ' . htmlspecialchars($bosquejoActual['titulo']); ?>
                    </option>
                    <?php else: ?>
                    <option value="">Buscar bosquejo…</option>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Escribe el número o palabras del título para buscar</small>

                <!-- Warning: bosquejo marcado como "No presentar"
                     alert-permanent evita que main.js lo oculte con fadeOut -->
                <div id="alertNoPresentar" class="alert alert-warning alert-permanent mt-2 mb-0"
                     style="display:none;">
                    <div id="alertNoPresentarNota" class="small"></div>
                </div>
                <?php
                // Pre-renderizar el warning si el bosquejo ya está seleccionado y tiene no_presentar=1
                if ($bosquejoActual) {
                    try {
                        $bNota = fetchOne(
                            "SELECT no_presentar, nota_no_presentar FROM bosquejos WHERE id=?",
                            [$bosquejoActual['id']]
                        );
                        if ($bNota && (int)$bNota['no_presentar'] === 1) {
                            echo '<script>
                                document.getElementById("alertNoPresentar").style.display="block";
                                document.getElementById("alertNoPresentarNota").textContent='
                                . json_encode($bNota['nota_no_presentar'] ?? '') . ';
                            </script>';
                        }
                    } catch (Exception $e) {}
                }
                ?>
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
            <!-- Orador -->
            <div class="col-md-6">
                <!-- Label + checkbox "Local" en la misma línea -->
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-bold mb-0">Orador</label>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="chkOradorLocal"
                               <?php echo !empty($asignaciones['DP_Orador']['persona_id']) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="chkOradorLocal">Local</label>
                    </div>
                </div>

                <!-- Input texto libre (orador externo) — visible por defecto -->
                <input type="text" class="form-control" id="txt_dp_orador"
                       placeholder="Escribe el nombre del orador"
                       value="<?php echo htmlspecialchars($asignaciones['DP_Orador']['nombre_libre'] ?? ''); ?>"
                       <?php echo !empty($asignaciones['DP_Orador']['persona_id']) ? 'style="display:none;"' : ''; ?>>

                <!-- Selector local (Select2) — visible cuando "Local" está marcado -->
                <select class="form-select fds-asig-select" data-rol="DP_Orador" id="sel_dp_orador"
                        style="width:100%; <?php echo empty($asignaciones['DP_Orador']['persona_id']) ? 'display:none;' : ''; ?>">
                    <?php echo optionsFds($todasPersonas,
                                         (int)($asignaciones['DP_Orador']['persona_id'] ?? 0)); ?>
                </select>
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

// ── Toggle "Local" del Orador ────────────────────────────────
$('#chkOradorLocal').on('change', function () {
    if (this.checked) {
        // Mostrar selector local, ocultar input libre
        $('#txt_dp_orador').hide().val('');
        $('#sel_dp_orador').show();
        // Reinicializar Select2 (puede estar en display:none al inicializar)
        if ($('#sel_dp_orador').hasClass('select2-hidden-accessible')) {
            $('#sel_dp_orador').trigger('change');
        }
    } else {
        // Mostrar input libre, ocultar selector y limpiar asignación persona
        $('#sel_dp_orador').val('').trigger('change');
        $('#sel_dp_orador').hide();
        $('#txt_dp_orador').show().val('').focus();

        // Desasignar persona en BD
        $.ajax({
            url     : '../api/programas_fds.php',
            method  : 'POST',
            dataType: 'json',
            data    : { action: 'save_asignacion', programa_fds_id: fdsId,
                        rol: 'DP_Orador', persona_id: '', nombre_libre: '' },
        });
    }
});

// ── Guardar asignación por selector ─────────────────────────
$(document).on('change', '.fds-asig-select', function () {
    const rol       = $(this).data('rol');
    const personaId = $(this).val();

    $.ajax({
        url     : '../api/programas_fds.php',
        method  : 'POST',
        dataType: 'json',
        data    : { action: 'save_asignacion', programa_fds_id: fdsId, rol, persona_id: personaId || '' },
        success : (res) => {
            if (res.success) APP.showNotification(res.nombre ? 'Asignado: ' + res.nombre : 'Sin asignar', 'success');
            else APP.showNotification(res.message, 'danger');
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

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {

    /* ── Configuración base compartida ──────────────────────────── */
    const s2Base = {
        theme      : 'bootstrap-5',
        language   : 'es',
        allowClear : true,
        width      : '100%',
        placeholder: 'Sin asignar',
    };

    /* ── Select2 para bosquejo (búsqueda AJAX) ───────────────────── */
    $('#sel_dp_bosquejo').select2($.extend({}, s2Base, {
        placeholder       : 'Buscar bosquejo por número o título…',
        minimumInputLength: 0,
        ajax: {
            url        : '../api/bosquejos.php',
            dataType   : 'json',
            delay      : 300,
            data       : (p) => ({ action: 'search', q: p.term || '', page: p.page || 1 }),
            processResults: (d) => ({ results: d.results, pagination: d.pagination }),
            cache      : true,
        },
        templateResult  : function (b) {
            if (b.loading) return b.text;
            if (!b.numero) return b.text;
            return $('<span>').append(
                $('<span class="bosquejo-result-num">').text(b.numero),
                document.createTextNode(b.titulo)
            );
        },
        templateSelection: (b) => b.numero ? (b.numero + ' — ' + b.titulo) : b.text,
    }));

    /* ── Select2 para selectores de personas ────────────────────── */
    // Incluye .fds-asig-select: Presidente, Orador (selector), Conductor, Lector, Oración
    // El select del Orador puede estar oculto (display:none) al inicializar —
    // Select2 funciona en elementos ocultos; width:100% se aplica al mostrarse.
    $('.fds-asig-select').each(function () {
        $(this).select2($.extend({}, s2Base, {
            placeholder: $(this).find('option[value=""]').text() || 'Sin asignar',
        }));
    });

    // Estado inicial: forzar visibilidad según checkbox (checked o no)
    if ($('#chkOradorLocal').is(':checked')) {
        $('#txt_dp_orador').hide();
        $('#sel_dp_orador').show();
    } else {
        $('#txt_dp_orador').show();
        $('#sel_dp_orador').hide();
        // Asegurarse de que Select2 no quede en estado inconsistente
        if ($('#sel_dp_orador').hasClass('select2-hidden-accessible')) {
            $('#sel_dp_orador').val('').trigger('change.select2');
        }
    }

    /* ── Eventos ────────────────────────────────────────────────── */

    // Bosquejo: warning + guardar
    $('#sel_dp_bosquejo').on('select2:select select2:unselect select2:clear', function (e) {
        const bosquejoId = $(this).val() || '';

        if (e.type === 'select2:select' && e.params && e.params.data) {
            const d = e.params.data;
            if (parseInt(d.no_presentar) === 1) {
                $('#alertNoPresentarNota').text(d.nota_no_presentar || '');
                $('#alertNoPresentar').css('display', 'block');
            } else {
                $('#alertNoPresentar').css('display', 'none');
            }
        } else {
            $('#alertNoPresentar').css('display', 'none');
        }

        $.ajax({
            url     : '../api/programas_fds.php',
            method  : 'POST',
            dataType: 'json',
            data    : {
                action        : 'update',
                id            : fdsId,
                fecha_inicio  : '<?php echo $semana['fecha_inicio']; ?>',
                fecha_fin     : '<?php echo $semana['fecha_fin']; ?>',
                dp_bosquejo_id: bosquejoId,
                dp_cancion    : $('#dp_cancion').val()
            },
            success: (res) => { if (!res.success) APP.showNotification(res.message, 'danger'); }
        });
    });

    // Selectores de personas: Select2 emite 'change' nativo tras seleccionar/limpiar
    // Los listeners jQuery existentes (.fds-asig-select) siguen funcionando sin cambios.

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
