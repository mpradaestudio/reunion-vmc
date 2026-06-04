<?php
require_once __DIR__ . '/../config/config.php';
$config      = getConfiguracion();
$pageTitle   = $pageTitle ?? 'Inicio';
$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($page, $current) {
    return $page === $current ? 'active' : '';
}

// Nombre completo → topbar
$nombreCompleto = $config['nombre_congregacion'];

// Nombre corto → sidebar: elimina el prefijo "Congregación" (acentuado o no)
// Ej: "Congregación El Caney" → "El Caney"
$nombreCorto = trim(preg_replace('/^congregaci[oó]n\s*/iu', '', $nombreCompleto));
if ($nombreCorto === '') {
    $nombreCorto = $nombreCompleto;   // fallback por si el nombre ES solo "Congregación"
}

// Iniciales del avatar (2 primeras letras del nombre completo)
$iniciales = mb_strtoupper(mb_substr($nombreCompleto, 0, 2, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' – ' . APP_NAME; ?></title>

    <!-- Anti-FOUC: tema + sidebar colapsado antes de pintar -->
    <script>
        (function () {
            // Tema
            try {
                var s = localStorage.getItem('vmc-theme');
                var t = s ? s : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch(e) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
            // Sidebar: bloquear transición durante la carga inicial
            // para evitar el "ghost" de animación 240px→72px
            document.documentElement.classList.add('sb-no-transition');
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <?php $cssVer = @filemtime(BASE_PATH . '/assets/css/style.css') ?: APP_VERSION; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $cssVer; ?>">

    <!-- CSS extra inyectado por la página (ej. Select2) -->
    <?php if (!empty($extraHeadHtml)) echo $extraHeadHtml; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>

<!-- Overlay móvil (se activa al abrir el sidebar en pantallas pequeñas) -->
<div class="sb-overlay" id="sbOverlay"></div>

<!-- ════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar" aria-label="Menú principal">

    <!-- Logo / Brand -->
    <a class="sidebar-brand" href="<?php echo BASE_URL; ?>index.php" title="Inicio">
        <i class="bi bi-people sb-icon"></i>
        <span class="sb-name"><?php echo htmlspecialchars($nombreCorto); ?></span>
    </a>

    <!-- Navegación principal -->
    <nav class="sidebar-nav" role="navigation">

        <a class="sidebar-item <?php echo navActive('index.php', $currentPage); ?>"
           href="<?php echo BASE_URL; ?>index.php"
           title="Inicio">
            <i class="bi bi-house-door sb-item-icon"></i>
            <span class="sb-item-label">Inicio</span>
        </a>

        <a class="sidebar-item <?php echo navActive('personas.php', $currentPage); ?>"
           href="<?php echo BASE_URL; ?>pages/personas.php"
           title="Personas">
            <i class="bi bi-people sb-item-icon"></i>
            <span class="sb-item-label">Personas</span>
        </a>

        <a class="sidebar-item <?php echo navActive('programas.php',  $currentPage) ?: navActive('programa_detalle.php', $currentPage) ?: navActive('seleccionar_exportar.php', $currentPage); ?>"
           href="<?php echo BASE_URL; ?>pages/programas.php"
           title="Reuniones">
            <i class="bi bi-calendar-check sb-item-icon"></i>
            <span class="sb-item-label">Reuniones</span>
        </a>

    </nav>

    <!-- Footer del sidebar -->
    <div class="sidebar-footer">
        <a class="sidebar-item <?php echo navActive('configuracion.php', $currentPage); ?>"
           href="<?php echo BASE_URL; ?>pages/configuracion.php"
           title="Configuración">
            <i class="bi bi-gear sb-item-icon"></i>
            <span class="sb-item-label">Configuración</span>
        </a>

        <button class="sb-theme-btn" id="themeToggle"
                aria-label="Cambiar tema claro/oscuro" title="Cambiar tema">
            <span class="sb-item-icon">
                <i class="bi bi-moon-stars-fill icon-moon"></i>
                <i class="bi bi-sun-fill icon-sun" style="display:none;"></i>
            </span>
            <span class="sb-item-label" id="themeLabel">Modo oscuro</span>
        </button>
    </div>

</aside>

<!-- ════════════════════════════════════════════
     TOPBAR
════════════════════════════════════════════ -->
<header class="topbar" id="topbar">

    <!-- Hamburguesa -->
    <button class="topbar-toggle" id="sidebarToggle" aria-label="Abrir/cerrar menú">
        <i class="bi bi-list"></i>
    </button>

    <!-- Nombre de la congregación (se oculta cuando sidebar está abierto en desktop) -->
    <h1 class="topbar-title" id="topbarTitle">
        <?php echo htmlspecialchars($nombreCompleto); ?>
    </h1>

    <!-- Acciones derechas -->
    <div class="topbar-actions">

        <!-- Búsqueda -->
        <div class="topbar-search d-none d-sm-flex">
            <i class="bi bi-search search-icon"></i>
            <input type="search" placeholder="Buscar…" id="globalSearch"
                   aria-label="Búsqueda global">
        </div>

        <!-- Notificaciones -->
        <button class="topbar-btn" id="btnNotificaciones"
                aria-label="Notificaciones" title="Notificaciones">
            <i class="bi bi-bell"></i>
            <span class="topbar-badge" id="notifBadge" style="display:none;"></span>
        </button>

        <!-- Avatar / usuario -->
        <span class="topbar-avatar" title="<?php echo htmlspecialchars($nombreCompleto); ?>">
            <?php echo $iniciales; ?>
        </span>

    </div>
</header>

<!-- ════════════════════════════════════════════
     CONTENT WRAPPER
════════════════════════════════════════════ -->
<div class="content-wrapper" id="contentWrapper">
    <main>
