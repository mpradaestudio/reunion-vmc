/**
 * JavaScript principal para el Programador de Reuniones
 */

// Configuración global
const APP = {
    baseUrl: '/',
    
    // Mostrar notificación
    showNotification: function(message, type = 'success') {
        const alertClass = `alert-${type}`;
        const icon = type === 'success' ? 'check-circle' : 
                     type === 'danger' ? 'exclamation-circle' : 
                     type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                 role="alert" style="z-index: 9999; min-width: 300px;">
                <i class="bi bi-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    },
    
    // Confirmar acción
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    // Mostrar loading
    showLoading: function(element) {
        $(element).html('<div class="spinner-border spinner-border-sm me-2"></div>Cargando...');
        $(element).prop('disabled', true);
    },
    
    // Ocultar loading
    hideLoading: function(element, text) {
        $(element).html(text);
        $(element).prop('disabled', false);
    },
    
    // Formatear fecha
    formatDate: function(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('es-ES', options);
    },
    
    // Validar formulario
    validateForm: function(formId) {
        const form = document.getElementById(formId);
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return false;
        }
        return true;
    }
};

// Inicialización al cargar el documento
$(document).ready(function() {
    
    // Activar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Activar popovers de Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts después de 5 segundos
    setTimeout(() => {
        $('.alert:not(.alert-permanent)').fadeOut();
    }, 5000);
    
    // Confirmación de eliminación
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        const message = $(this).data('confirm') || '¿Está seguro de eliminar este registro?';
        
        APP.confirm(message, function() {
            window.location.href = url;
        });
    });
    
    // Prevenir doble submit en formularios
    $('form').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
    });
    
});

// Funciones para gestión de personas
const Personas = {
    
    // Cargar lista de personas
    loadList: function() {
        $.ajax({
            url: APP.baseUrl + 'api/personas.php?action=list',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Personas.renderList(response.data);
                } else {
                    APP.showNotification(response.message, 'danger');
                }
            },
            error: function() {
                APP.showNotification('Error al cargar personas', 'danger');
            }
        });
    },
    
    // Renderizar lista
    renderList: function(personas) {
        const container = $('#personas-list');
        container.empty();
        
        if (personas.length === 0) {
            container.html(`
                <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    <p>No hay personas registradas</p>
                </div>
            `);
            return;
        }
        
        personas.forEach(persona => {
            const card = $(`
                <div class="col-md-4 mb-3">
                    <div class="card persona-card">
                        <div class="card-body">
                            <h5 class="card-title">${persona.nombre_completo}</h5>
                            <p class="card-text">
                                <span class="badge bg-primary">${persona.perfil}</span>
                                ${persona.activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'}
                            </p>
                            <div class="btn-group btn-group-sm">
                                <a href="persona_editar.php?id=${persona.id}" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="#" class="btn btn-outline-danger btn-delete" data-url="api/personas.php?action=delete&id=${persona.id}">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            container.append(card);
        });
    }
};

// Funciones para gestión de programas
const Programas = {
    
    // Cargar programa semanal
    loadPrograma: function(programaId) {
        $.ajax({
            url: APP.baseUrl + 'api/programas.php?action=get&id=' + programaId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Programas.renderPrograma(response.data);
                } else {
                    APP.showNotification(response.message, 'danger');
                }
            },
            error: function() {
                APP.showNotification('Error al cargar programa', 'danger');
            }
        });
    },
    
    // Asignar persona a parte
    asignarPersona: function(seccionId, personaId, rol = 'Asignado') {
        $.ajax({
            url: APP.baseUrl + 'api/asignaciones.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'asignar_parte',
                seccion_id: seccionId,
                persona_id: personaId,
                rol: rol
            },
            success: function(response) {
                if (response.success) {
                    APP.showNotification('Persona asignada correctamente', 'success');
                    location.reload();
                } else {
                    APP.showNotification(response.message, 'danger');
                }
            },
            error: function() {
                APP.showNotification('Error al asignar persona', 'danger');
            }
        });
    },
    
    // Desasignar persona
    desasignarPersona: function(asignacionId) {
        APP.confirm('¿Desea quitar esta asignación?', function() {
            $.ajax({
                url: APP.baseUrl + 'api/asignaciones.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'desasignar',
                    asignacion_id: asignacionId
                },
                success: function(response) {
                    if (response.success) {
                        APP.showNotification('Asignación eliminada', 'success');
                        location.reload();
                    } else {
                        APP.showNotification(response.message, 'danger');
                    }
                },
                error: function() {
                    APP.showNotification('Error al eliminar asignación', 'danger');
                }
            });
        });
    }
};

// Funciones para scraping
const Scraper = {
    
    // Ejecutar scraping
    ejecutar: function(url) {
        const btn = $('#btn-scraping');
        APP.showLoading(btn);
        
        $.ajax({
            url: APP.baseUrl + 'api/scraper.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'scrape',
                url: url
            },
            success: function(response) {
                APP.hideLoading(btn, '<i class="bi bi-cloud-download"></i> Extraer Programas');
                
                if (response.success) {
                    APP.showNotification(`Programas extraídos: ${response.programas_extraidos}`, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    APP.showNotification(response.message, 'danger');
                }
            },
            error: function() {
                APP.hideLoading(btn, '<i class="bi bi-cloud-download"></i> Extraer Programas');
                APP.showNotification('Error al extraer programas', 'danger');
            }
        });
    }
};


/**
 * Gestión del tema claro/oscuro.
 * El atributo data-bs-theme ya se aplica en <head> (anti-FOUC).
 * Aquí solo gestionamos el botón de alternancia y la persistencia.
 */
const Theme = {
    KEY: 'vmc-theme',

    get: function () {
        return document.documentElement.getAttribute('data-bs-theme') || 'light';
    },

    set: function (theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        try {
            localStorage.setItem(this.KEY, theme);
        } catch (e) {
            /* localStorage no disponible: el tema seguirá aplicado en la sesión */
        }
    },

    toggle: function () {
        this.set(this.get() === 'dark' ? 'light' : 'dark');
    },

    init: function () {
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', () => Theme.toggle());
        }

        // Si el usuario no ha elegido un tema manualmente, seguir la preferencia del sistema
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let stored = null;
                try { stored = localStorage.getItem(Theme.KEY); } catch (err) {}
                if (!stored) {
                    document.documentElement.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    Theme.init();
});


/* ================================================================
   SIDEBAR — toggle, persistencia, offcanvas móvil, tema label
================================================================ */
const Sidebar = {
    KEY_COLLAPSED : 'vmc-sb-collapsed',
    MOBILE_BP     : 768,      // px — por debajo es offcanvas

    sidebar       : null,
    topbar        : null,
    wrapper       : null,
    overlay       : null,
    toggleBtn     : null,
    themeLabel    : null,

    isMobile() {
        return window.innerWidth < this.MOBILE_BP;
    },

    /* Lee el estado guardado (solo desktop) */
    isStoredCollapsed() {
        try { return localStorage.getItem(this.KEY_COLLAPSED) === '1'; } catch(e) { return false; }
    },

    saveCollapsed(v) {
        try { localStorage.setItem(this.KEY_COLLAPSED, v ? '1' : '0'); } catch(e) {}
    },

    /* Aplica estado colapsado/expandido en desktop */
    applyDesktop(collapsed) {
        this.sidebar.classList.toggle('collapsed', collapsed);
        this.topbar.classList.toggle('sb-collapsed', collapsed);
        this.wrapper.classList.toggle('sb-collapsed', collapsed);
        this.saveCollapsed(collapsed);
    },

    /* Abre el offcanvas en móvil */
    openMobile() {
        this.sidebar.classList.add('mobile-open');
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /* Cierra el offcanvas en móvil */
    closeMobile() {
        this.sidebar.classList.remove('mobile-open');
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    /* Toggle según contexto */
    toggle() {
        if (this.isMobile()) {
            this.sidebar.classList.contains('mobile-open')
                ? this.closeMobile()
                : this.openMobile();
        } else {
            const nowCollapsed = !this.sidebar.classList.contains('collapsed');
            this.applyDesktop(nowCollapsed);
        }
    },

    /* Sincroniza la etiqueta del botón de tema */
    syncThemeLabel() {
        if (!this.themeLabel) return;
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        this.themeLabel.textContent = dark ? 'Modo claro' : 'Modo oscuro';

        // Icono luna/sol dentro del sidebar
        const moon = document.querySelector('#themeToggle .icon-moon');
        const sun  = document.querySelector('#themeToggle .icon-sun');
        if (moon) moon.style.display = dark ? 'none'         : 'inline-block';
        if (sun)  sun.style.display  = dark ? 'inline-block' : 'none';
    },

    init() {
        this.sidebar    = document.getElementById('sidebar');
        this.topbar     = document.getElementById('topbar');
        this.wrapper    = document.getElementById('contentWrapper');
        this.overlay    = document.getElementById('sbOverlay');
        this.toggleBtn  = document.getElementById('sidebarToggle');
        this.themeLabel = document.getElementById('themeLabel');

        if (!this.sidebar) return;   // página sin sidebar (ej. exportar_pdf)

        /* Estado inicial */
        if (!this.isMobile() && this.isStoredCollapsed()) {
            this.applyDesktop(true);
        }

        /* Botón hamburguesa */
        this.toggleBtn?.addEventListener('click', () => this.toggle());

        /* Overlay: cerrar en móvil al hacer clic fuera */
        this.overlay?.addEventListener('click', () => this.closeMobile());

        /* Reajustar al cambiar tamaño de ventana */
        window.addEventListener('resize', () => {
            if (!this.isMobile()) {
                this.closeMobile();          // limpiar estado móvil
                // restaurar estado desktop persistido
                this.applyDesktop(this.isStoredCollapsed());
            }
        });

        /* Sincronizar etiqueta de tema al inicio */
        this.syncThemeLabel();
    }
};

/* Inicializar sidebar cuando el DOM esté listo */
document.addEventListener('DOMContentLoaded', function () {
    Sidebar.init();
    Theme.init();      // ya definido más arriba en este archivo
});

/* Extender Theme.toggle para actualizar la etiqueta del sidebar */
const _origToggle = Theme.toggle.bind(Theme);
Theme.toggle = function () {
    _origToggle();
    Sidebar.syncThemeLabel();
};
