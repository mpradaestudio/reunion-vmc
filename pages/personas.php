<?php
$pageTitle = 'Personas';
require_once __DIR__ . '/../includes/header.php';

// Obtener lista de perfiles (para checkboxes y filtro)
$perfiles = fetchAll("SELECT * FROM perfiles ORDER BY orden, nombre");

// Filtros
$filtroPerfil = (isset($_GET['perfil_id']) && $_GET['perfil_id'] !== '') ? (int)$_GET['perfil_id'] : null;
$filtroActivo = (isset($_GET['activo']) && $_GET['activo'] !== '') ? (int)$_GET['activo'] : null;

$where  = [];
$params = [];
if ($filtroActivo !== null) {
    $where[]  = "p.activo = ?";
    $params[] = $filtroActivo;
}
if ($filtroPerfil !== null) {
    $where[]  = "EXISTS (SELECT 1 FROM persona_perfiles pp2 WHERE pp2.persona_id = p.id AND pp2.perfil_id = ?)";
    $params[] = $filtroPerfil;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Consultar personas con sus perfiles múltiples. Si la tabla persona_perfiles
// aún no existe (falta correr la migración), usamos el respaldo de la vista.
$necesitaMigracion = false;
try {
    $personas = fetchAll("
        SELECT p.id, p.nombre, p.apellido,
               CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo,
               p.telefono, p.activo,
               GROUP_CONCAT(DISTINCT pf.nombre ORDER BY pf.id SEPARATOR '|') AS perfiles
        FROM personas p
        LEFT JOIN persona_perfiles pp ON pp.persona_id = p.id
        LEFT JOIN perfiles pf ON pf.id = pp.perfil_id
        $whereSql
        GROUP BY p.id, p.nombre, p.apellido, p.telefono, p.activo
        ORDER BY p.nombre, p.apellido
    ", $params);
} catch (Exception $e) {
    $necesitaMigracion = true;
    $personas = fetchAll("
        SELECT id, nombre, apellido,
               CONCAT(nombre, ' ', apellido) AS nombre_completo,
               telefono, activo, perfil AS perfiles
        FROM vista_personas_completa
        ORDER BY nombre, apellido
    ");
}

// Mensajes
$mensaje = '';
$tipoMensaje = 'success';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creada':      $mensaje = 'Persona agregada exitosamente'; break;
        case 'actualizada': $mensaje = 'Persona actualizada exitosamente'; break;
        case 'eliminada':   $mensaje = 'Persona eliminada exitosamente'; break;
        case 'error':       $mensaje = 'Ocurrió un error. Verifica que seleccionaste al menos un perfil.'; $tipoMensaje = 'danger'; break;
    }
}

// Obtener privilegios para el modal
$privilegiosModal = [];
try {
    $privilegiosModal = fetchAll("SELECT id, nombre FROM privilegios WHERE activo = 1 ORDER BY orden, nombre");
} catch (Exception $e) {
    // Tabla aún no existe (migración pendiente)
    $privilegiosModal = [];
}
$seccionesPartes = [
    'tesoros' => [
        'titulo' => 'TESOROS DE LA BIBLIA',
        'clase'  => 'seccion-tesoros',
        'partes' => ['Discurso Tesoros', 'Busquemos perlas escondidas', 'Lectura de la Biblia'],
    ],
    'maestros' => [
        'titulo' => 'SEAMOS MEJORES MAESTROS',
        'clase'  => 'seccion-maestros',
        'partes' => ['Estudiante', 'Ayudante'],
    ],
    'vida' => [
        'titulo' => 'NUESTRA VIDA CRISTIANA',
        'clase'  => 'seccion-vida',
        'partes' => ['Partes', 'Necesidades', 'Conductor', 'Lector'],
    ],
];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="bi bi-people me-2"></i>
                Gestión de Personas
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPersona">
                <i class="bi bi-person-plus"></i> Agregar Persona
            </button>
        </div>
    </div>
</div>

<?php if ($necesitaMigracion): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Falta un paso:</strong> importa el archivo <code>database_update_v2.sql</code> en phpMyAdmin
    para activar los perfiles múltiples. Mientras tanto se muestra el perfil principal.
</div>
<?php endif; ?>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Perfil</label>
                        <select name="perfil_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los perfiles</option>
                            <?php foreach ($perfiles as $perfil): ?>
                                <option value="<?php echo $perfil['id']; ?>"
                                    <?php echo ($filtroPerfil === (int)$perfil['id']) ? 'selected' : ''; ?>>
                                    <?php echo $perfil['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="activo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="1" <?php echo ($filtroActivo === 1) ? 'selected' : ''; ?>>Activos</option>
                            <option value="0" <?php echo ($filtroActivo === 0) ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="personas.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar Filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de personas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Lista de Personas (<?php echo count($personas); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($personas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input class="form-check-input" type="checkbox" id="chkSelectAll" title="Seleccionar todo">
                                </th>
                                <th>Nombre</th>
                                <th>Perfil(es)</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personas as $persona): ?>
                            <tr class="persona-row" data-id="<?php echo $persona['id']; ?>">
                                <td>
                                    <input class="form-check-input chk-persona" type="checkbox"
                                           value="<?php echo $persona['id']; ?>">
                                </td>
                                <td><strong><?php echo htmlspecialchars($persona['nombre_completo']); ?></strong></td>
                                <td>
                                    <?php
                                    $listaPerfiles = !empty($persona['perfiles']) ? explode('|', $persona['perfiles']) : [];
                                    if ($listaPerfiles) {
                                        foreach ($listaPerfiles as $pf) {
                                            echo '<span class="badge bg-primary me-1">' . htmlspecialchars($pf) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">—</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($persona['telefono'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($persona['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-editar"
                                                data-id="<?php echo $persona['id']; ?>"
                                                data-bs-toggle="tooltip" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-eliminar"
                                                data-id="<?php echo $persona['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($persona['nombre_completo']); ?>"
                                                data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    <p>No hay personas registradas</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPersona">
                        <i class="bi bi-person-plus"></i> Agregar Primera Persona
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar/editar persona -->
<div class="modal fade" id="modalPersona" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPersonaTitulo">
                    <i class="bi bi-person-plus"></i> Agregar Persona
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPersona" method="POST" action="persona_guardar.php">
                <input type="hidden" name="id" id="persona_id">
                <input type="hidden" name="action" id="persona_action" value="create">

                <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">

                    <!-- 1. Nombre / Apellido -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido *</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                    </div>

                    <!-- 2. Estado / Teléfono -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                    </div>

                    <!-- 3. Perfil (checkboxes) -->
                    <div class="mb-3">
                        <label class="form-label">Perfil * <small class="text-muted">(uno o varios)</small></label>
                        <div class="d-flex flex-wrap gap-3 border rounded p-2">
                            <?php foreach ($perfiles as $perfil): ?>
                            <div class="form-check">
                                <input class="form-check-input chk-perfil" type="checkbox"
                                       name="perfil_ids[]" value="<?php echo $perfil['id']; ?>"
                                       id="perfil_<?php echo $perfil['id']; ?>">
                                <label class="form-check-label" for="perfil_<?php echo $perfil['id']; ?>">
                                    <?php echo $perfil['nombre']; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 4. Privilegios (inmediatamente después de Perfil) -->
                    <?php if (!empty($privilegiosModal)): ?>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield-check"></i> Privilegios</label>
                        <div class="d-flex flex-wrap gap-3 border rounded p-2">
                            <?php foreach ($privilegiosModal as $priv): ?>
                            <div class="form-check">
                                <input class="form-check-input chk-privilegio" type="checkbox"
                                       name="privilegio_ids[]" value="<?php echo $priv['id']; ?>"
                                       id="priv_<?php echo $priv['id']; ?>">
                                <label class="form-check-label" for="priv_<?php echo $priv['id']; ?>">
                                    <?php echo htmlspecialchars($priv['nombre']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr>

                    <!-- 5. Reunión entre semana (renombrado) -->
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-calendar-week"></i> Reunión entre semana
                    </h6>

                    <!-- Presidente / Oración (partes sueltas) -->
                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input chk-parte-suelta" type="checkbox"
                                   name="partes_disponibles[]" value="Presidente" id="p_presidente">
                            <label class="form-check-label" for="p_presidente">Presidente</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input chk-parte-suelta" type="checkbox"
                                   name="partes_disponibles[]" value="Oración" id="p_oracion">
                            <label class="form-check-label" for="p_oracion">Oración</label>
                        </div>
                    </div>

                    <!-- Secciones Entre Semana -->
                    <?php foreach ($seccionesPartes as $key => $sec):
                        $grupoId = 'grupo_' . $key;
                    ?>
                    <div class="card mb-2">
                        <div class="card-header <?php echo $sec['clase']; ?> py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><?php echo $sec['titulo']; ?></span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input chk-todos" type="checkbox"
                                       id="all_<?php echo $key; ?>" data-grupo="<?php echo $grupoId; ?>"
                                       title="Seleccionar todo">
                                <label class="form-check-label small" for="all_<?php echo $key; ?>">Todas</label>
                            </div>
                        </div>
                        <div class="card-body py-2 grupo-partes" id="<?php echo $grupoId; ?>">
                            <div class="row">
                                <?php foreach ($sec['partes'] as $i => $parte):
                                    $inputId = 'p_' . $key . '_' . $i;
                                ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-parte" type="checkbox"
                                               name="partes_disponibles[]" value="<?php echo htmlspecialchars($parte); ?>"
                                               id="<?php echo $inputId; ?>">
                                        <label class="form-check-label" for="<?php echo $inputId; ?>">
                                            <?php echo htmlspecialchars($parte); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- 6. Reunión fin de semana -->
                    <hr>
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-calendar2-week"></i> Reunión fin de semana
                    </h6>
                    <div class="card mb-2">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center"
                             style="background-color:var(--vmc-primary);color:#fff;">
                            <span class="fw-bold">FIN DE SEMANA</span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input chk-todos" type="checkbox"
                                       id="all_fds" data-grupo="grupo_fds"
                                       title="Seleccionar todo">
                                <label class="form-check-label text-white small" for="all_fds">Todas</label>
                            </div>
                        </div>
                        <div class="card-body py-2 grupo-partes" id="grupo_fds">
                            <div class="row">
                                <?php
                                $partesFds = ['Presidente', 'Oración', 'Conductor', 'Lector'];
                                foreach ($partesFds as $i => $parte):
                                    $inputId = 'p_fds_' . $i;
                                ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-parte" type="checkbox"
                                               name="partes_disponibles[]"
                                               value="FDS_<?php echo htmlspecialchars($parte); ?>"
                                               id="<?php echo $inputId; ?>">
                                        <label class="form-check-label" for="<?php echo $inputId; ?>">
                                            <?php echo htmlspecialchars($parte); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Notas -->
                    <div class="mb-2 mt-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="2"></textarea>
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

<!-- ── Barra flotante bulk-edit ──────────────────────────────── -->
<div id="bulkBar" class="bulk-bar" style="display:none;">
    <span class="bulk-bar-count">
        <strong id="bulkCount">0</strong> seleccionado(s)
    </span>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-light" id="btnBulkPerfiles">
            <i class="bi bi-person-badge me-1"></i>Editar Perfiles
        </button>
        <button class="btn btn-sm btn-outline-light" id="btnBulkPrivilegios">
            <i class="bi bi-shield-check me-1"></i>Editar Privilegios
        </button>
        <button class="btn btn-sm btn-danger" id="btnBulkEliminar">
            <i class="bi bi-trash me-1"></i>Eliminar
        </button>
    </div>
    <button class="btn btn-sm btn-link text-white ms-2 p-0" id="btnBulkCancelar" title="Cancelar selección">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<!-- ── Modal: Editar Perfiles en lote ───────────────────────── -->
<div class="modal fade" id="modalBulkPerfiles" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Editar Perfiles en lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Personas seleccionadas: <strong id="bulkPerfilesCount">0</strong>
                </p>
                <!-- Toggle Agregar / Reemplazar -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="small fw-semibold">Modo:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="bulkPerfilesMode" id="bpModeAgregar" value="add" checked>
                        <label class="btn btn-outline-primary" for="bpModeAgregar">Agregar</label>
                        <input type="radio" class="btn-check" name="bulkPerfilesMode" id="bpModeReemplazar" value="replace">
                        <label class="btn btn-outline-primary" for="bpModeReemplazar">Reemplazar</label>
                    </div>
                </div>
                <!-- Checkboxes de perfiles -->
                <div class="d-flex flex-wrap gap-2 border rounded p-2" id="bulkPerfilesLista">
                    <?php foreach ($perfiles as $pf): ?>
                    <div class="form-check">
                        <input class="form-check-input chk-bulk-perfil" type="checkbox"
                               value="<?php echo $pf['id']; ?>"
                               id="bpf_<?php echo $pf['id']; ?>">
                        <label class="form-check-label" for="bpf_<?php echo $pf['id']; ?>">
                            <?php echo htmlspecialchars($pf['nombre']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnBulkPerfilesGuardar">
                    <i class="bi bi-save me-1"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Editar Privilegios en lote ────────────────────── -->
<div class="modal fade" id="modalBulkPrivilegios" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Editar Privilegios en lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Personas seleccionadas: <strong id="bulkPrivilegiosCount">0</strong>
                </p>
                <!-- Toggle Agregar / Reemplazar -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="small fw-semibold">Modo:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="bulkPrivilegiosMode" id="bpvModeAgregar" value="add" checked>
                        <label class="btn btn-outline-primary" for="bpvModeAgregar">Agregar</label>
                        <input type="radio" class="btn-check" name="bulkPrivilegiosMode" id="bpvModeReemplazar" value="replace">
                        <label class="btn btn-outline-primary" for="bpvModeReemplazar">Reemplazar</label>
                    </div>
                </div>
                <!-- Checkboxes de privilegios -->
                <div class="d-flex flex-wrap gap-2 border rounded p-2" id="bulkPrivilegiosLista">
                    <?php if (!empty($privilegiosModal)): ?>
                        <?php foreach ($privilegiosModal as $prv): ?>
                        <div class="form-check">
                            <input class="form-check-input chk-bulk-privilegio" type="checkbox"
                                   value="<?php echo $prv['id']; ?>"
                                   id="bpv_<?php echo $prv['id']; ?>">
                            <label class="form-check-label" for="bpv_<?php echo $prv['id']; ?>">
                                <?php echo htmlspecialchars($prv['nombre']); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No hay privilegios definidos.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnBulkPrivilegiosGuardar">
                    <i class="bi bi-save me-1"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* ════════════════════════════════════════════════════════════
   BULK EDIT
════════════════════════════════════════════════════════════ */
const BulkEdit = {

    getIds() {
        return $('.chk-persona:checked').map(function () {
            return parseInt(this.value);
        }).get();
    },

    updateBar() {
        const ids = this.getIds();
        const n   = ids.length;
        if (n > 0) {
            $('#bulkCount').text(n);
            $('#bulkPerfilesCount').text(n);
            $('#bulkPrivilegiosCount').text(n);
            $('#bulkBar').fadeIn(150);
        } else {
            $('#bulkBar').fadeOut(150);
        }
        // Sincronizar "seleccionar todo"
        const total = $('.chk-persona').length;
        $('#chkSelectAll').prop('indeterminate', n > 0 && n < total);
        $('#chkSelectAll').prop('checked', n > 0 && n === total);
        // Resaltar filas
        $('.persona-row').each(function () {
            const checked = $(this).find('.chk-persona').is(':checked');
            $(this).toggleClass('table-active', checked);
        });
    },

    clearSelection() {
        $('.chk-persona, #chkSelectAll').prop('checked', false);
        $('#chkSelectAll').prop('indeterminate', false);
        $('.persona-row').removeClass('table-active');
        $('#bulkBar').fadeOut(150);
    },

    init() {
        // Seleccionar todo
        $(document).on('change', '#chkSelectAll', function () {
            $('.chk-persona').prop('checked', this.checked);
            BulkEdit.updateBar();
        });

        // Checkbox individual
        $(document).on('change', '.chk-persona', function () {
            BulkEdit.updateBar();
        });

        // Cancelar selección
        $('#btnBulkCancelar').on('click', () => this.clearSelection());

        // Abrir modales
        $('#btnBulkPerfiles').on('click', () => {
            $('.chk-bulk-perfil').prop('checked', false);
            $('input[name="bulkPerfilesMode"][value="add"]').prop('checked', true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBulkPerfiles')).show();
        });
        $('#btnBulkPrivilegios').on('click', () => {
            $('.chk-bulk-privilegio').prop('checked', false);
            $('input[name="bulkPrivilegiosMode"][value="add"]').prop('checked', true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBulkPrivilegios')).show();
        });

        // Eliminar en lote
        $('#btnBulkEliminar').on('click', () => {
            const ids = BulkEdit.getIds();
            if (!ids.length) return;
            if (!confirm(`¿Eliminar permanentemente a las ${ids.length} persona(s) seleccionada(s)?\n\nEsta acción no se puede deshacer.`)) return;
            $.ajax({
                url: '../api/personas.php', method: 'POST', dataType: 'json',
                data: { action: 'bulk_delete', ids: ids },
                success(res) {
                    if (res.success) {
                        APP.showNotification(res.message, 'success');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        APP.showNotification(res.message || 'Error al eliminar', 'danger');
                    }
                },
                error() { APP.showNotification('Error al conectar con el servidor', 'danger'); }
            });
        });

        // Guardar perfiles en lote
        $('#btnBulkPerfilesGuardar').on('click', () => {
            const ids        = BulkEdit.getIds();
            const perfilIds  = $('.chk-bulk-perfil:checked').map(function () { return parseInt(this.value); }).get();
            const mode       = $('input[name="bulkPerfilesMode"]:checked').val();
            if (!ids.length) return;
            if (!perfilIds.length) {
                APP.showNotification('Selecciona al menos un perfil', 'warning'); return;
            }
            const $btn = $('#btnBulkPerfilesGuardar').prop('disabled', true);
            $.ajax({
                url: '../api/personas.php', method: 'POST', dataType: 'json',
                data: { action: 'bulk_perfiles', ids: ids, perfil_ids: perfilIds, mode: mode },
                success(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalBulkPerfiles'))?.hide();
                        APP.showNotification(res.message, 'success');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        APP.showNotification(res.message || 'Error', 'danger');
                    }
                },
                error() { $btn.prop('disabled', false); APP.showNotification('Error al conectar con el servidor', 'danger'); }
            });
        });

        // Guardar privilegios en lote
        $('#btnBulkPrivilegiosGuardar').on('click', () => {
            const ids          = BulkEdit.getIds();
            const privilegioIds = $('.chk-bulk-privilegio:checked').map(function () { return parseInt(this.value); }).get();
            const mode          = $('input[name="bulkPrivilegiosMode"]:checked').val();
            if (!ids.length) return;
            if (!privilegioIds.length) {
                APP.showNotification('Selecciona al menos un privilegio', 'warning'); return;
            }
            const $btn = $('#btnBulkPrivilegiosGuardar').prop('disabled', true);
            $.ajax({
                url: '../api/personas.php', method: 'POST', dataType: 'json',
                data: { action: 'bulk_privilegios', ids: ids, privilegio_ids: privilegioIds, mode: mode },
                success(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalBulkPrivilegios'))?.hide();
                        APP.showNotification(res.message, 'success');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        APP.showNotification(res.message || 'Error', 'danger');
                    }
                },
                error() { $btn.prop('disabled', false); APP.showNotification('Error al conectar con el servidor', 'danger'); }
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => BulkEdit.init());
</script>

<script>
// ---- Seleccionar todas las partes de una sección ----
$(document).on('change', '.chk-todos', function () {
    const grupo = $(this).data('grupo');
    $('#' + grupo + ' .chk-parte').prop('checked', this.checked);
});

// Al cambiar una parte, sincronizar el "seleccionar todo" de su sección
$(document).on('change', '.chk-parte', function () {
    sincronizarMaster($(this).closest('.grupo-partes'));
});

function sincronizarMaster($grupo) {
    if (!$grupo.length) return;
    const grupoId = $grupo.attr('id');
    const total = $grupo.find('.chk-parte').length;
    const marcados = $grupo.find('.chk-parte:checked').length;
    $('.chk-todos[data-grupo="' + grupoId + '"]').prop('checked', total > 0 && total === marcados);
}

function sincronizarTodosLosMasters() {
    $('.grupo-partes').each(function () { sincronizarMaster($(this)); });
}

// ---- Editar persona ----
$('.btn-editar').on('click', function () {
    const id = $(this).data('id');

    $.get('../api/personas.php?action=get&id=' + id, function (response) {
        if (!response.success) {
            APP.showNotification(response.message, 'danger');
            return;
        }
        const p = response.data;

        $('#modalPersonaTitulo').html('<i class="bi bi-pencil"></i> Editar Persona');
        $('#persona_id').val(p.id);
        $('#persona_action').val('update');
        $('#nombre').val(p.nombre);
        $('#apellido').val(p.apellido);
        $('#telefono').val(p.telefono || '');
        $('#activo').val(p.activo);
        $('#notas').val(p.notas || '');

        // Perfiles (checkboxes)
        $('.chk-perfil').prop('checked', false);
        if (p.perfil_ids) {
            p.perfil_ids.forEach(function (pid) {
                $('#perfil_' + pid).prop('checked', true);
            });
        }

        // Partes que presenta
        $('input[name="partes_disponibles[]"]').prop('checked', false);
        if (p.partes_disponibles) {
            p.partes_disponibles.forEach(function (parte) {
                $('input[name="partes_disponibles[]"]').filter(function () {
                    return this.value === parte.tipo_parte;
                }).prop('checked', true);
            });
        }
        sincronizarTodosLosMasters();

        // Privilegios
        $('.chk-privilegio').prop('checked', false);
        if (p.privilegio_ids) {
            p.privilegio_ids.forEach(function (pvid) {
                $('#priv_' + pvid).prop('checked', true);
            });
        }

        $('#modalPersona').modal('show');
    });
});

// ---- Eliminar persona ----
$('.btn-eliminar').on('click', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');
    if (confirm('¿Está seguro de eliminar a ' + nombre + '?')) {
        $.post('../api/personas.php', { action: 'delete', id: id }, function (response) {
            if (response.success) {
                window.location.href = 'personas.php?msg=eliminada';
            } else {
                APP.showNotification(response.message, 'danger');
            }
        });
    }
});

// ---- Validar al menos un perfil antes de enviar ----
$('#formPersona').on('submit', function (e) {
    if ($('.chk-perfil:checked').length === 0) {
        e.preventDefault();
        e.stopImmediatePropagation();
        APP.showNotification('Selecciona al menos un perfil', 'warning');
        $(this).find('button[type="submit"]').prop('disabled', false);
        return false;
    }
});

// ---- Limpiar modal al cerrar ----
$('#modalPersona').on('hidden.bs.modal', function () {
    $('#formPersona')[0].reset();
    $('#persona_id').val('');
    $('#persona_action').val('create');
    $('.chk-todos').prop('checked', false);
    $('.chk-privilegio').prop('checked', false);
    $('#modalPersonaTitulo').html('<i class="bi bi-person-plus"></i> Agregar Persona');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
