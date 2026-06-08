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
 * Aquí gestionamos el botón y la persistencia.
 */
const Theme = {
    KEY: 'vmc-theme',

    get() {
        return document.documentElement.getAttribute('data-bs-theme') || 'light';
    },

    set(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        try { localStorage.setItem(this.KEY, theme); } catch(e) {}
    },

    toggle() {
        this.set(this.get() === 'dark' ? 'light' : 'dark');
        // Notificar al Sidebar para que sincronice el label e iconos
        if (typeof Sidebar !== 'undefined' && Sidebar.syncThemeLabel) {
            Sidebar.syncThemeLabel();
        }
    },

    init() {
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', () => this.toggle());
        }
        // Seguir preferencia del sistema si no hay elección manual
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let stored = null;
                try { stored = localStorage.getItem(this.KEY); } catch(err) {}
                if (!stored) {
                    document.documentElement.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
                    if (typeof Sidebar !== 'undefined') Sidebar.syncThemeLabel();
                }
            });
        }
    }
};

/* ================================================================
   SIDEBAR — toggle, persistencia, offcanvas móvil, tema label
================================================================ */
const Sidebar = {
    KEY_COLLAPSED: 'vmc-sb-collapsed',
    MOBILE_BP    : 768,

    sidebar  : null,
    topbar   : null,
    wrapper  : null,
    overlay  : null,
    toggleBtn: null,
    themeLabel: null,

    isMobile() {
        return window.innerWidth < this.MOBILE_BP;
    },

    isStoredCollapsed() {
        try { return localStorage.getItem(this.KEY_COLLAPSED) === '1'; } catch(e) { return false; }
    },

    saveCollapsed(v) {
        try { localStorage.setItem(this.KEY_COLLAPSED, v ? '1' : '0'); } catch(e) {}
    },

    applyDesktop(collapsed) {
        this.sidebar.classList.toggle('collapsed', collapsed);
        this.topbar.classList.toggle('sb-collapsed', collapsed);
        this.wrapper.classList.toggle('sb-collapsed', collapsed);
        this.saveCollapsed(collapsed);
    },

    openMobile() {
        this.sidebar.classList.add('mobile-open');
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    closeMobile() {
        this.sidebar.classList.remove('mobile-open');
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    toggle() {
        if (this.isMobile()) {
            this.sidebar.classList.contains('mobile-open')
                ? this.closeMobile()
                : this.openMobile();
        } else {
            this.applyDesktop(!this.sidebar.classList.contains('collapsed'));
        }
    },

    syncThemeLabel() {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

        // Etiqueta en el sidebar
        if (this.themeLabel) {
            this.themeLabel.textContent = dark ? 'Modo claro' : 'Modo oscuro';
        }

        // Iconos luna/sol en el botón del sidebar
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

        if (!this.sidebar) {
            // Quitar la clase aunque no haya sidebar (ej. exportar_pdf)
            document.documentElement.classList.remove('sb-no-transition');
            return;
        }

        // Aplicar estado inicial SIN transición (el anti-FOUC ya bloqueó las transitions)
        if (!this.isMobile() && this.isStoredCollapsed()) {
            this.applyDesktop(true);
        }

        // Rehabilitar transiciones después de que el navegador haya pintado
        // el estado correcto (doble rAF garantiza 2 frames de diferencia)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                document.documentElement.classList.remove('sb-no-transition');
            });
        });

        // Botón hamburguesa
        this.toggleBtn?.addEventListener('click', () => this.toggle());

        // Overlay móvil
        this.overlay?.addEventListener('click', () => this.closeMobile());

        // Redimensionado
        window.addEventListener('resize', () => {
            if (!this.isMobile()) {
                this.closeMobile();
                this.applyDesktop(this.isStoredCollapsed());
            }
        });

        // Etiqueta e iconos de tema al inicio
        this.syncThemeLabel();
    }
};

/* ── Sidebar groups: toggle de submenús ─────────────────────── */
const SidebarGroups = {
    KEY: 'vmc-sb-groups',   // JSON {groupId: bool (collapsed)}

    load() {
        try { return JSON.parse(localStorage.getItem(this.KEY) || '{}'); } catch(e) { return {}; }
    },
    save(state) {
        try { localStorage.setItem(this.KEY, JSON.stringify(state)); } catch(e) {}
    },

    init() {
        const state = this.load();

        document.querySelectorAll('.sidebar-group-toggle').forEach(btn => {
            const targetId = btn.dataset.target;
            const items    = document.getElementById(targetId);
            if (!items) return;

            // Restaurar estado guardado
            if (state[targetId]) {
                items.classList.add('sb-group-collapsed');
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', () => {
                // En sidebar colapsado, no colapsar subgrupos (solo iconos visibles)
                if (document.getElementById('sidebar')?.classList.contains('collapsed')) return;

                const isCollapsed = items.classList.toggle('sb-group-collapsed');
                btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                const s = this.load();
                s[targetId] = isCollapsed;
                this.save(s);
            });
        });
    }
};

/* ── Inicialización única al cargar el DOM ──────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    Theme.init();
    Sidebar.init();
    SidebarGroups.init();
});


/* ── Select2: ocultar X cuando no hay valor (placeholder) ──────
   Aplica a todas las páginas. La X solo debe verse cuando hay
   una persona/valor asignado, no cuando muestra "Sin asignar".
   ─────────────────────────────────────────────────────────────── */
$(document).on('select2:open select2:select select2:clear select2:unselect', function () {
    // pequeño delay para que Select2 actualice el DOM
    setTimeout(s2SyncClearButtons, 10);
});

$(document).ready(function () {
    // Estado inicial al cargar la página
    setTimeout(s2SyncClearButtons, 200);
});

function s2SyncClearButtons() {
    document.querySelectorAll('.select2-container--bootstrap-5').forEach(function (container) {
        const clearBtn = container.querySelector('.select2-selection__clear');
        if (!clearBtn) return;

        // Si el rendered muestra el placeholder → ocultar X
        const rendered = container.querySelector('.select2-selection__rendered');
        const isPlaceholder = rendered &&
            (rendered.classList.contains('select2-selection__placeholder') ||
             rendered.querySelector('.select2-selection__placeholder') !== null ||
             (rendered.getAttribute('title') || '').trim() === '' ||
             (rendered.textContent || '').trim() === '');

        clearBtn.style.setProperty('display', isPlaceholder ? 'none' : '', 'important');
    });
}


/* ── Flatpickr — inicialización global ─────────────────────────
   Se activa en DOMContentLoaded y también al abrir modales de BS
   para que los inputs dentro de modales se inicialicen bien.

   Convenciones de data-attributes:
     data-fp-mode="range"   → selector de rango (inicio–fin)
     data-fp-mode="single"  → día único (default)
     data-fp-linked="<id>"  → para pares inicio/fin (rango en 2 inputs)
     data-fp-min-date       → fecha mínima (YYYY-MM-DD o "today")
   ─────────────────────────────────────────────────────────────── */
(function () {
    'use strict';

    const FP_INSTANCES = new WeakMap();

    const BASE_CFG = {
        locale     : 'es',
        dateFormat : 'Y-m-d',          // mantiene formato nativo del input
        altInput   : true,             // muestra formato amigable al usuario
        altFormat  : 'j M Y',         // ej: "3 ene 2026"
        allowInput : false,
        disableMobile: false,
    };

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function initInput(el) {
        if (!el || FP_INSTANCES.has(el)) return;
        if (typeof flatpickr === 'undefined') return;

        const mode    = el.dataset.fpMode || 'single';
        const minDate = el.dataset.fpMin   || null;
        const linkedId = el.dataset.fpLinked || null;

        const cfg = Object.assign({}, BASE_CFG, {
            mode   : mode === 'range' ? 'range' : 'single',
        });

        if (minDate) cfg.minDate = minDate;

        // Pares inicio→fin vinculados: al elegir inicio, ajustar minDate del fin
        if (linkedId) {
            const linkedEl = document.getElementById(linkedId);
            if (linkedEl) {
                cfg.onChange = function (selectedDates) {
                    if (selectedDates.length > 0 && FP_INSTANCES.has(linkedEl)) {
                        FP_INSTANCES.get(linkedEl).set('minDate', selectedDates[0]);
                    }
                };
            }
        }

        const instance = flatpickr(el, cfg);
        FP_INSTANCES.set(el, instance);
    }

    function initAll(root) {
        const ctx = root || document;
        ctx.querySelectorAll('input[type="date"]:not(.flatpickr-input)').forEach(initInput);
    }

    // Al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof flatpickr !== 'undefined') {
            flatpickr.localize(flatpickr.l10ns.es);
            initAll();
        } else {
            // Flatpickr aún no cargó (footer) — esperar
            window.addEventListener('load', function () {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr.localize(flatpickr.l10ns.es);
                    initAll();
                }
            });
        }
    });

    // Cuando se abre un modal de Bootstrap (los inputs dentro no existían aún)
    document.addEventListener('shown.bs.modal', function (e) {
        if (typeof flatpickr !== 'undefined') initAll(e.target);
    });

    // Exponer para uso manual si hace falta
    window.VMC_initFlatpickr = initAll;
}());
