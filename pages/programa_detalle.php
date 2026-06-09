<?php
$pageTitle = 'Detalle del Programa';

// Select2: CSS en el <head> vía buffer de salida antes de que header.php escriba el HTML
$extraHeadHtml = '
    <!-- Select2 CSS (solo programa_detalle) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        /* ── Layout ─────────────────────────────────────────────────────── */
        .input-group .select2-container { flex: 1 1 auto; min-width: 0; }
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: var(--vmc-radius-sm);
        }

        /* ── Eliminar border y shadow en focus/open ──────────────────────
           Sustituye el anillo azul del tema Bootstrap-5 por el primario
           del proyecto, sin sombra extra.                                  */
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open  .select2-selection {
            border-color: var(--vmc-primary) !important;
            box-shadow : none !important;
        }

        /* ── Search field dentro del dropdown ───────────────────────────── */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-search .select2-search__field:focus {
            border-color: var(--vmc-primary) !important;
            box-shadow : none !important;
            outline    : none;
        }

        /* ── Opción seleccionada (fondo azul del proyecto) ───────────────
           Reemplaza el azul Bootstrap por --vmc-primary (#4a6da7).         */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option.select2-results__option--selected,
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option[aria-selected=true]:not(.select2-results__option--highlighted) {
            background-color: var(--vmc-primary-soft) !important;
            color           : var(--vmc-primary)      !important;
        }

        /* ── Opción destacada al hacer hover ─────────────────────────────  */
        .select2-container--bootstrap-5 .select2-dropdown
            .select2-results__options
            .select2-results__option--highlighted {
            background-color: var(--vmc-primary) !important;
            color           : #ffffff             !important;
        }

        /* ── MODO OSCURO ─────────────────────────────────────────────────  */
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
            .select2-results__options
            .select2-results__option--highlighted {
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
    </style>
';

require_once __DIR__ . '/../includes/header.php';

$programaId = $_GET['id'] ?? null;

if (!$programaId) {
    redirect('entre-semana.php');
}

// Obtener programa completo
$programa = fetchOne("SELECT * FROM programas_semanales WHERE id = ?", [$programaId]);

if (!$programa) {
    redirect('entre-semana.php');
}

// Obtener secciones (incluye titulo_visita para semanas de visita de circuito)
$secciones = fetchAll("
    SELECT * FROM programa_secciones 
    WHERE programa_id = ? 
    ORDER BY orden
", [$programaId]);

// Obtener asignaciones de roles
$rolesAsignados = [];
$roles = fetchAll("
    SELECT ar.*, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
    FROM asignaciones_roles ar
    LEFT JOIN personas p ON ar.persona_id = p.id
    WHERE ar.programa_id = ?
", [$programaId]);

foreach ($roles as $rol) {
    $rolesAsignados[$rol['rol']] = $rol;
}

// Obtener personas activas agrupadas por la parte que pueden presentar
$personasPorCapacidad = [];
try {
    $rows = fetchAll("
        SELECT ppd.tipo_parte, p.id, CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
        FROM persona_partes_disponibles ppd
        INNER JOIN personas p ON p.id = ppd.persona_id
        WHERE p.activo = 1 AND ppd.puede_presentar = 1
        ORDER BY p.nombre, p.apellido
    ");
    foreach ($rows as $r) {
        $personasPorCapacidad[$r['tipo_parte']][] = $r;
    }
} catch (Exception $e) {
    $personasPorCapacidad = [];
}

/**
 * Lista de personas habilitadas para una capacidad (tipo_parte) dada.
 */
function personasPara($capacidad) {
    global $personasPorCapacidad;
    return $personasPorCapacidad[$capacidad] ?? [];
}

/**
 * Determina la capacidad requerida para una parte del programa según su
 * sección, título y el orden del presentador (1 o 2).
 * Debe coincidir con los valores marcados en el modal de Personas.
 */
function capacidadRequerida($seccion, $titulo, $orden) {
    $t = mb_strtolower($titulo, 'UTF-8');

    if ($seccion === 'TESOROS DE LA BIBLIA') {
        if (mb_strpos($t, 'perlas') !== false)  return 'Busquemos perlas escondidas';
        if (mb_strpos($t, 'lectura') !== false) return 'Lectura de la Biblia';
        return 'Discurso Tesoros';
    }
    if ($seccion === 'SEAMOS MEJORES MAESTROS') {
        return ($orden == 1) ? 'Estudiante' : 'Ayudante';
    }
    if ($seccion === 'NUESTRA VIDA CRISTIANA') {
        if (mb_strpos($t, 'estudio b') !== false) {
            return ($orden == 1) ? 'Conductor' : 'Lector';
        }
        if (mb_strpos($t, 'necesidades') !== false) return 'Necesidades';
        return 'Partes';
    }
    return null;
}

/**
 * Genera las <option> de un <select> a partir de la lista filtrada.
 * Si la persona ya asignada no está en la lista (perdió la capacidad),
 * se agrega igualmente para no perder la selección.
 */
function renderOpciones($lista, $selId, $selNombre = '') {
    $html = '<option value="">Sin asignar</option>';
    $encontrado = false;
    foreach ($lista as $p) {
        $sel = ($selId && $p['id'] == $selId) ? 'selected' : '';
        if ($sel) $encontrado = true;
        $html .= '<option value="' . $p['id'] . '" ' . $sel . '>' . htmlspecialchars($p['nombre_completo']) . '</option>';
    }
    if ($selId && !$encontrado) {
        $html .= '<option value="' . $selId . '" selected>' . htmlspecialchars($selNombre) . ' (no habilitado)</option>';
    }
    return $html;
}

// Formatear fecha
$fecha_inicio = new DateTime($programa['fecha_inicio']);
$fecha_fin    = new DateTime($programa['fecha_fin']);
$mesNombre = [
    1=>'enero', 2=>'febrero',  3=>'marzo',    4=>'abril',
    5=>'mayo',  6=>'junio',    7=>'julio',    8=>'agosto',
    9=>'septiembre', 10=>'octubre', 11=>'noviembre', 12=>'diciembre'
];
$mes        = $mesNombre[(int)$fecha_inicio->format('n')];
$fechaFormato = (int)$fecha_inicio->format('d') . '-' . (int)$fecha_fin->format('d')
              . ' de ' . $mes . ' ' . $fecha_inicio->format('Y');

// Navegación: semana anterior y siguiente (por fecha_inicio)
$semanaAnterior = fetchOne(
    "SELECT id FROM programas_semanales WHERE fecha_inicio < ? ORDER BY fecha_inicio DESC LIMIT 1",
    [$programa['fecha_inicio']]
);
$semanaSiguiente = fetchOne(
    "SELECT id FROM programas_semanales WHERE fecha_inicio > ? ORDER BY fecha_inicio ASC LIMIT 1",
    [$programa['fecha_inicio']]
);

// ── Detectar si esta semana cae dentro de una Visita de Circuito ─────────
$esVisitaCircuito    = false;
$visitaActiva        = null;
$superCircuitoNombre = '';
try {
    $visitaActiva = fetchOne("
        SELECT * FROM eventos_especiales
        WHERE tipo = 'visita'
          AND ? BETWEEN DATE_SUB(fecha_inicio, INTERVAL 1 DAY) AND fecha_fin
        LIMIT 1
    ", [$programa['fecha_inicio']]);
    if (!empty($visitaActiva)) {
        $esVisitaCircuito = true;
        // notas puede ser JSON (visita) o texto plano
        $notasRaw = $visitaActiva['notas'] ?? '';
        $notasData = json_decode($notasRaw, true);
        $superCircuitoNombre = is_array($notasData)
            ? ($notasData['nombre'] ?? '')
            : $notasRaw;
    }
} catch (Exception $e) { }
?>

<!-- ── Fila 1: Volver | ← semana anterior · siguiente → | Exportar PDF ── -->
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">

    <!-- Extremo izquierdo -->
    <a href="entre-semana.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>

    <!-- Centro: navegación entre semanas -->
    <div class="d-flex gap-2">
        <?php if ($semanaAnterior): ?>
        <a href="programa_detalle.php?id=<?php echo $semanaAnterior['id']; ?>"
           class="btn btn-outline-primary" title="Semana anterior">
            <i class="bi bi-chevron-left"></i> Anterior
        </a>
        <?php else: ?>
        <button class="btn btn-outline-primary" disabled>
            <i class="bi bi-chevron-left"></i> Anterior
        </button>
        <?php endif; ?>

        <?php if ($semanaSiguiente): ?>
        <a href="programa_detalle.php?id=<?php echo $semanaSiguiente['id']; ?>"
           class="btn btn-outline-primary" title="Semana siguiente">
            Siguiente <i class="bi bi-chevron-right"></i>
        </a>
        <?php else: ?>
        <button class="btn btn-outline-primary" disabled>
            Siguiente <i class="bi bi-chevron-right"></i>
        </button>
        <?php endif; ?>
    </div>

    <!-- Extremo derecho -->
    <a href="exportar_pdf.php?programa_id=<?php echo $programaId; ?>"
       class="btn btn-danger" target="_blank">
        <i class="bi bi-file-pdf"></i> Exportar PDF
    </a>
</div>

<!-- ── Fila 2: Título de la semana + referencia bíblica + botón Autollenado ── -->
<div class="d-flex justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h1 class="h2 mb-0">
            <?php echo htmlspecialchars($programa['titulo_semana']); ?>
            <?php if (!empty($programa['referencia_biblica'])): ?>
            <span class="text-muted fw-normal"> | <?php echo htmlspecialchars(
                mb_convert_case($programa['referencia_biblica'], MB_CASE_TITLE, 'UTF-8')
            ); ?></span>
            <?php endif; ?>
        </h1>
    </div>
    <button class="btn btn-outline-primary" id="btnAutollenado">
        <i class="bi bi-magic me-1"></i> Autollenado
    </button>
</div>

<!-- Roles generales -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Roles Generales</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Presidente -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Presidente:</label>
                        <select class="form-select asignar-rol" data-rol="Presidente" style="width:100%;">
                            <?php echo renderOpciones(
                                personasPara('Presidente'),
                                $rolesAsignados['Presidente']['persona_id'] ?? null,
                                $rolesAsignados['Presidente']['nombre_completo'] ?? ''
                            ); ?>
                        </select>
                    </div>

                    <!-- Oración inicial -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Oración inicial:</label>
                        <select class="form-select asignar-rol" data-rol="Oración inicial" style="width:100%;">
                            <?php echo renderOpciones(
                                personasPara('Oración'),
                                $rolesAsignados['Oración inicial']['persona_id'] ?? null,
                                $rolesAsignados['Oración inicial']['nombre_completo'] ?? ''
                            ); ?>
                        </select>
                    </div>

                    <!-- Oración final -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Oración final:</label>
                        <select class="form-select asignar-rol" data-rol="Oración final" style="width:100%;">
                            <?php echo renderOpciones(
                                personasPara('Oración'),
                                $rolesAsignados['Oración final']['persona_id'] ?? null,
                                $rolesAsignados['Oración final']['nombre_completo'] ?? ''
                            ); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Canciones -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-around text-center align-items-center">
                    <div>
                        <i class="bi bi-music-note"></i>
                        <strong>Canción inicial:</strong> <?php echo $programa['cancion_inicial']; ?>
                    </div>
                    <div>
                        <i class="bi bi-music-note"></i>
                        <strong>Canción media:</strong> <?php echo $programa['cancion_media']; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-music-note"></i>
                        <strong>Canción final:</strong>
                        <?php if ($esVisitaCircuito): ?>
                        <input type="text" id="cancionFinalVisita"
                               class="form-control form-control-sm"
                               style="width:100px;"
                               placeholder="Nº canción"
                               value="<?php echo htmlspecialchars($programa['cancion_final_visita'] ?? ''); ?>">
                        <?php else: ?>
                        <?php echo $programa['cancion_final']; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secciones del programa -->
<div class="row">
    <div class="col-12">
        <?php
        $seccionActual = '';
        foreach ($secciones as $seccion):
            // Obtener asignaciones de esta sección
            // nombre_libre se incluye solo si la columna ya existe (database_update_v13.sql)
            try {
                $asignaciones = fetchAll("
                    SELECT ap.id, ap.seccion_id, ap.persona_id, ap.rol,
                           ap.orden_presentador, ap.nombre_libre,
                           CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                    FROM asignaciones_partes ap
                    LEFT JOIN personas p ON ap.persona_id = p.id
                    WHERE ap.seccion_id = ?
                    ORDER BY ap.orden_presentador
                ", [$seccion['id']]);
            } catch (Exception $e) {
                // Fallback si nombre_libre aún no existe en la BD
                $asignaciones = fetchAll("
                    SELECT ap.id, ap.seccion_id, ap.persona_id, ap.rol,
                           ap.orden_presentador, NULL AS nombre_libre,
                           CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                    FROM asignaciones_partes ap
                    LEFT JOIN personas p ON ap.persona_id = p.id
                    WHERE ap.seccion_id = ?
                    ORDER BY ap.orden_presentador
                ", [$seccion['id']]);
            }
            
            // Agrupar asignaciones por orden
            $asignacionesPorOrden = [];
            foreach ($asignaciones as $asig) {
                $asignacionesPorOrden[$asig['orden_presentador']] = $asig;
            }
            
            // Nueva sección
            if ($seccionActual !== $seccion['seccion']):
                if ($seccionActual !== ''): ?>
                    </div></div></div>
                <?php endif;
                
                $seccionActual = $seccion['seccion'];
                $claseSeccion = '';
                if ($seccion['seccion'] === 'TESOROS DE LA BIBLIA') {
                    $claseSeccion = 'seccion-tesoros';
                } elseif ($seccion['seccion'] === 'SEAMOS MEJORES MAESTROS') {
                    $claseSeccion = 'seccion-maestros';
                } elseif ($seccion['seccion'] === 'NUESTRA VIDA CRISTIANA') {
                    $claseSeccion = 'seccion-vida';
                }
                ?>
                <div class="card mb-3">
                    <div class="card-header <?php echo $claseSeccion; ?>">
                        <h5 class="mb-0"><?php echo $seccion['seccion']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
            <?php endif; ?>
            
            <!-- Parte individual -->
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <?php
                        $esConductorLector = ($seccion['tipo_asignacion'] === 'Conductor/Lector');
                        $esTituloEditable  = $esVisitaCircuito && $esConductorLector;
                        $tituloMostrado    = ($esTituloEditable && !empty($seccion['titulo_visita']))
                                            ? $seccion['titulo_visita']
                                            : $seccion['titulo'];
                        ?>
                        <?php if ($esTituloEditable): ?>
                        <input type="text" class="form-control form-control-sm fw-bold titulo-visita-input"
                               data-seccion-id="<?php echo $seccion['id']; ?>"
                               value="<?php echo htmlspecialchars($tituloMostrado); ?>"
                               placeholder="Título del estudio (visita de circuito)">
                        <?php if ($seccion['duracion']): ?>
                            <span class="badge bg-secondary ms-1 mt-1"><?php echo $seccion['duracion']; ?> min.</span>
                        <?php endif; ?>
                        <?php else: ?>
                        <strong><?php echo htmlspecialchars($tituloMostrado); ?></strong>
                        <?php if ($seccion['duracion']): ?>
                            <span class="badge bg-secondary ms-2"><?php echo $seccion['duracion']; ?> min.</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php
                        $mostrarSubtipo = false;
                        if (!empty($seccion['subtipo_actividad'])
                            && $seccion['seccion'] !== 'TESOROS DE LA BIBLIA'
                            && $seccion['tipo_asignacion'] !== 'Conductor/Lector'
                        ) { $mostrarSubtipo = true; }
                        ?>
                        <?php if ($mostrarSubtipo): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($seccion['subtipo_actividad']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $tipo        = $seccion['tipo_asignacion'];
                        $dosPersonas = in_array($tipo, ['Estudiante/Ayudante', 'Conductor/Lector']);
                        $numAsignaciones = $dosPersonas ? 2 : 1;
                        $etiquetas = ['Asignado:', 'Asignado:'];
                        if ($tipo === 'Estudiante/Ayudante') $etiquetas = ['Estudiante:', 'Ayudante:'];
                        elseif ($tipo === 'Conductor/Lector') $etiquetas = ['Conductor:', 'Lector:'];
                        $colClase = $dosPersonas ? 'col-6' : 'col-12';
                        $usarTextoLibre = $esVisitaCircuito && ($tipo === 'Conductor/Lector');
                        ?>
                        <?php if ($usarTextoLibre): ?>
                        <!-- Visita de Circuito: un solo input "Super de Circuito" -->
                        <?php
                        $asigVisita  = $asignacionesPorOrden[1] ?? null;
                        $nombreLibre = $asigVisita['nombre_libre'] ?? '';
                        $selNombre   = $asigVisita['nombre_completo'] ?? '';
                        // Prioridad: 1) nombre_libre guardado  2) notas del evento (super)  3) vacío
                        $valorInicial = ($nombreLibre !== '')
                                        ? $nombreLibre
                                        : $superCircuitoNombre;
                        ?>
                        <label class="form-label small mb-1">Super de Circuito:</label>
                        <input type="text"
                               class="form-control nombre-libre-visita"
                               data-seccion-id="<?php echo $seccion['id']; ?>"
                               data-orden="1"
                               data-rol="<?php echo htmlspecialchars($tipo); ?>"
                               value="<?php echo htmlspecialchars($valorInicial); ?>"
                               placeholder="Nombre del superintendente">
                        <?php else: ?>
                        <div class="row g-2">
                        <?php
                        for ($i = 1; $i <= $numAsignaciones; $i++):
                            $asignacionActual = $asignacionesPorOrden[$i] ?? null;
                            $cap         = capacidadRequerida($seccion['seccion'], $seccion['titulo'], $i);
                            $lista       = $cap ? personasPara($cap) : [];
                            $selId       = $asignacionActual['persona_id'] ?? null;
                            $selNombre   = $asignacionActual['nombre_completo'] ?? '';
                        ?>
                            <div class="<?php echo $colClase; ?>">
                                <label class="form-label small mb-1"><?php echo $etiquetas[$i - 1]; ?></label>
                                <select class="form-select asignar-parte"
                                        style="width:100%;"
                                        data-seccion-id="<?php echo $seccion['id']; ?>"
                                        data-orden="<?php echo $i; ?>"
                                        data-tipo="<?php echo htmlspecialchars($seccion['tipo_asignacion']); ?>">
                                    <?php echo renderOpciones($lista, $selId, $selNombre); ?>
                                </select>
                                <?php if ($cap && empty($lista)): ?>
                                    <small class="text-muted">Nadie habilitado para esta parte</small>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ($seccionActual !== ''): ?>
                    </div></div></div>
        <?php endif; ?>
    </div>
</div>

<script>
const programaId = <?php echo $programaId; ?>;

// ── Roles generales: asignar / desasignar via Select2 (change) ──
$('.asignar-rol').on('change', function () {
    const rol       = $(this).data('rol');
    const personaId = $(this).val();

    if (!personaId) {
        $.post('../api/asignaciones.php', {
            action: 'desasignar_rol', programa_id: programaId, rol
        }, (r) => {
            if (r.success) { APP.showNotification('Rol desasignado', 'success'); setTimeout(() => location.reload(), 800); }
            else APP.showNotification(r.message, 'danger');
        });
    } else {
        $.post('../api/asignaciones.php', {
            action: 'asignar_rol', programa_id: programaId, rol, persona_id: personaId
        }, (r) => {
            if (r.success) { APP.showNotification('Rol asignado', 'success'); setTimeout(() => location.reload(), 800); }
            else APP.showNotification(r.message, 'danger');
        });
    }
});

// ── Partes del programa: asignar / desasignar via Select2 (change) ──
$('.asignar-parte').on('change', function () {
    const seccionId = $(this).data('seccion-id');
    const orden     = $(this).data('orden');
    const tipo      = $(this).data('tipo');
    const personaId = $(this).val();

    if (!personaId) {
        // X limpia → desasignar
        $.post('../api/asignaciones.php', {
            action: 'desasignar_parte', seccion_id: seccionId, orden
        }, (r) => {
            if (r.success) { APP.showNotification('Asignación eliminada', 'success'); setTimeout(() => location.reload(), 800); }
            else APP.showNotification(r.message, 'danger');
        });
    } else {
        $.post('../api/asignaciones.php', {
            action: 'asignar_parte', seccion_id: seccionId, persona_id: personaId, rol: tipo, orden
        }, (r) => {
            if (r.success) { APP.showNotification('Persona asignada', 'success'); setTimeout(() => location.reload(), 800); }
            else APP.showNotification(r.message, 'danger');
        });
    }
});

// ── Visita de Circuito: campos editables ─────────────────────

// Canción final libre (debounce 600ms)
let cancionFinalTimer;
$(document).on('input', '#cancionFinalVisita', function () {
    const val = $(this).val().trim();
    clearTimeout(cancionFinalTimer);
    cancionFinalTimer = setTimeout(() => {
        $.post('../api/programas.php', {
            action: 'save_cancion_final_visita', programa_id: programaId, cancion_final_visita: val
        }, r => { if (!r.success) APP.showNotification(r.message, 'danger'); });
    }, 600);
});

// Título personalizado del Estudio Bíblico (debounce 600ms)
let tituloVisitaTimers = {};
$(document).on('input', '.titulo-visita-input', function () {
    const seccionId = $(this).data('seccion-id');
    const val       = $(this).val().trim();
    clearTimeout(tituloVisitaTimers[seccionId]);
    tituloVisitaTimers[seccionId] = setTimeout(() => {
        $.post('../api/programas.php', {
            action: 'save_titulo_visita', seccion_id: seccionId, titulo_visita: val
        }, r => { if (!r.success) APP.showNotification(r.message, 'danger'); });
    }, 600);
});

// Nombre libre de Conductor/Lector (debounce 600ms)
let nombreLibreTimers = {};
$(document).on('input', '.nombre-libre-visita', function () {
    const seccionId = $(this).data('seccion-id');
    const orden     = $(this).data('orden');
    const rol       = $(this).data('rol');
    const val       = $(this).val().trim();
    const key       = seccionId + '_' + orden;
    clearTimeout(nombreLibreTimers[key]);
    nombreLibreTimers[key] = setTimeout(() => {
        $.post('../api/programas.php', {
            action: 'save_nombre_libre_visita', seccion_id: seccionId,
            orden, rol, nombre_libre: val
        }, r => { if (!r.success) APP.showNotification(r.message, 'danger'); });
    }, 600);
});

// ── Autollenado ──────────────────────────────────────────────
$('#btnAutollenado').on('click', function () {
    const $btn = $(this).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span> Llenando…');

    $.post('../api/programas.php',
        { action: 'autofill', programa_id: programaId },
        function (res) {
            if (res.success) {
                APP.showNotification('Autollenado completado', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                APP.showNotification(res.message || 'Error en autollenado', 'danger');
                $btn.prop('disabled', false)
                    .html('<i class="bi bi-magic me-1"></i> Autollenado');
            }
        }
    ).fail(function () {
        APP.showNotification('Error al conectar con el servidor', 'danger');
        $btn.prop('disabled', false)
            .html('<i class="bi bi-magic me-1"></i> Autollenado');
    });
});
</script>

<!-- Select2 JS (solo programa_detalle) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {

    const s2Cfg = {
        theme     : 'bootstrap-5',
        language  : 'es',
        allowClear: true,
        width     : '100%',
        placeholder: function () {
            return $(this).find('option[value=""]').text() || 'Sin asignar';
        }
    };

    $('.asignar-rol').select2(s2Cfg);
    $('.asignar-parte').select2(s2Cfg);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
