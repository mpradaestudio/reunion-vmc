<?php
require_once __DIR__ . '/../config/config.php';
$config = getConfiguracion();
$pageTitle = $pageTitle ?? 'Inicio';

// Determinar la página actual para resaltar el enlace activo del menú
$currentPage = basename($_SERVER['PHP_SELF']);
function navActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>

    <!-- Inicialización de tema (anti-FOUC): se ejecuta antes de pintar la página -->
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('vmc-theme');
                var theme = stored
                    ? stored
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Google Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS (con cache-busting por fecha de modificación) -->
    <?php $cssVer = @filemtime(BASE_PATH . '/assets/css/style.css') ?: APP_VERSION; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $cssVer; ?>">

    <!-- CSS adicional inyectado por la página (ej. Select2) -->
    <?php if (!empty($extraHeadHtml)) echo $extraHeadHtml; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="bi bi-calendar-week me-2"></i>
                <?php echo htmlspecialchars($config['nombre_congregacion']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Mostrar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo navActive('index.php', $currentPage); ?>" href="<?php echo BASE_URL; ?>index.php">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo navActive('programas.php', $currentPage); ?>" href="<?php echo BASE_URL; ?>pages/programas.php">
                            <i class="bi bi-calendar-check"></i> Programas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo navActive('personas.php', $currentPage); ?>" href="<?php echo BASE_URL; ?>pages/personas.php">
                            <i class="bi bi-people"></i> Personas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo navActive('configuracion.php', $currentPage); ?>" href="<?php echo BASE_URL; ?>pages/configuracion.php">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <button type="button" id="themeToggle" class="theme-toggle"
                                aria-label="Cambiar tema claro/oscuro" title="Cambiar tema">
                            <i class="bi bi-moon-stars-fill icon-moon"></i>
                            <i class="bi bi-sun-fill icon-sun"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
