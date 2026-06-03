<?php
$pageTitle = 'Personas';
require_once __DIR__ . '/../includes/header.php';

// Obtener lista de perfiles
$perfiles = fetchAll("SELECT * FROM perfiles ORDER BY nombre");

// Obtener personas
$personas = fetchAll("SELECT * FROM vista_personas_completa ORDER BY nombre, apellido");

// Procesar mensajes
$mensaje = '';
$tipoMensaje = 'success';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creada':
            $mensaje = 'Persona agregada exitosamente';
            break;
        case 'actualizada':
            $mensaje = 'Persona actualizada exitosamente';
            break;
        case 'eliminada':
            $mensaje = 'Persona eliminada exitosamente';
            break;
        case 'error':
            $mensaje = 'Ocurrió un error al procesar la solicitud';
            $tipoMensaje = 'danger';
            break;
    }
}
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
                                    <?php echo (isset($_GET['perfil_id']) && $_GET['perfil_id'] == $perfil['id']) ? 'selected' : ''; ?>>
                                    <?php echo $perfil['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="activo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="1" <?php echo (isset($_GET['activo']) && $_GET['activo'] == '1') ? 'selected' : ''; ?>>
                                Activos
                            </option>
                            <option value="0" <?php echo (isset($_GET['activo']) && $_GET['activo'] == '0') ? 'selected' : ''; ?>>
                                Inactivos
                            </option>
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
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Perfil</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personas as $persona): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($persona['nombre_completo']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $persona['perfil']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($persona['telefono'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($persona['email'] ?? '-'); ?></td>
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
    <div class="modal-dialog modal-lg">
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
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="perfil_id" class="form-label">Perfil *</label>
                            <select class="form-select" id="perfil_id" name="perfil_id" required>
                                <option value="">Seleccionar perfil...</option>
                                <?php foreach ($perfiles as $perfil): ?>
                                    <option value="<?php echo $perfil['id']; ?>">
                                        <?php echo $perfil['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Partes que puede presentar</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="partes_disponibles[]" 
                                           value="Presidente" id="parte_presidente">
                                    <label class="form-check-label" for="parte_presidente">Presidente</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="partes_disponibles[]" 
                                           value="Oración" id="parte_oracion">
                                    <label class="form-check-label" for="parte_oracion">Oración</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="partes_disponibles[]" 
                                           value="Conductor/Lector" id="parte_conductor">
                                    <label class="form-check-label" for="parte_conductor">Conductor/Lector</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="partes_disponibles[]" 
                                           value="Asignado" id="parte_asignado">
                                    <label class="form-check-label" for="parte_asignado">Asignado (Tesoros/Vida)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="partes_disponibles[]" 
                                           value="Estudiante/Ayudante" id="parte_estudiante">
                                    <label class="form-check-label" for="parte_estudiante">Estudiante/Ayudante</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
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
// Manejar edición de persona
$('.btn-editar').on('click', function() {
    const id = $(this).data('id');
    
    // Cargar datos de la persona
    $.get('../api/personas.php?action=get&id=' + id, function(response) {
        if (response.success) {
            const persona = response.data;
            
            $('#modalPersonaTitulo').html('<i class="bi bi-pencil"></i> Editar Persona');
            $('#persona_id').val(persona.id);
            $('#persona_action').val('update');
            $('#nombre').val(persona.nombre);
            $('#apellido').val(persona.apellido);
            $('#perfil_id').val(persona.perfil_id);
            $('#activo').val(persona.activo);
            $('#telefono').val(persona.telefono || '');
            $('#email').val(persona.email || '');
            $('#notas').val(persona.notas || '');
            
            // Limpiar checkboxes
            $('input[name="partes_disponibles[]"]').prop('checked', false);
            
            // Marcar partes disponibles
            if (persona.partes_disponibles) {
                persona.partes_disponibles.forEach(function(parte) {
                    $('input[value="' + parte.tipo_parte + '"]').prop('checked', true);
                });
            }
            
            $('#modalPersona').modal('show');
        } else {
            APP.showNotification(response.message, 'danger');
        }
    });
});

// Manejar eliminación de persona
$('.btn-eliminar').on('click', function() {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');
    
    if (confirm('¿Está seguro de eliminar a ' + nombre + '?')) {
        $.post('../api/personas.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                window.location.href = 'personas.php?msg=eliminada';
            } else {
                APP.showNotification(response.message, 'danger');
            }
        });
    }
});

// Limpiar modal al cerrar
$('#modalPersona').on('hidden.bs.modal', function() {
    $('#formPersona')[0].reset();
    $('#persona_id').val('');
    $('#persona_action').val('create');
    $('#modalPersonaTitulo').html('<i class="bi bi-person-plus"></i> Agregar Persona');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
