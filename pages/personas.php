<?php
$pageTitle = 'Personas';
require_once __DIR__ . '/../includes/header.php';

// Obtener lista de perfiles (para checkboxes y filtro)
$perfiles = fetchAll("SELECT * FROM perfiles ORDER BY id");

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

// Definición de las partes por sección (para el modal)
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
                                <th>Nombre</th>
                                <th>Perfil(es)</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personas as $persona): ?>
                            <tr>
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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

                <div class="modal-body">
                    <!-- Nombre / Apellido -->
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

                    <!-- Perfil (checkboxes) / Estado -->
                    <div class="row">
                        <div class="col-md-8 mb-3">
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
                        <div class="col-md-4 mb-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3"><i class="bi bi-check2-square"></i> Partes que presenta</h6>

                    <!-- Presidente / Oración -->
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

                    <!-- Secciones con sus partes -->
                    <?php foreach ($seccionesPartes as $key => $sec):
                        $grupoId = 'grupo_' . $key;
                    ?>
                    <div class="card mb-2">
                        <div class="card-header <?php echo $sec['clase']; ?> py-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input chk-todos" type="checkbox"
                                       id="all_<?php echo $key; ?>" data-grupo="<?php echo $grupoId; ?>">
                                <label class="form-check-label fw-bold text-white" for="all_<?php echo $key; ?>">
                                    <?php echo $sec['titulo']; ?>
                                </label>
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

                    <!-- Notas -->
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
    $('#modalPersonaTitulo').html('<i class="bi bi-person-plus"></i> Agregar Persona');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
