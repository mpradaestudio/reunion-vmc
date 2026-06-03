<?php
/**
 * Configuración general de la aplicación
 */

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Rutas
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/');

// Configuración de la aplicación
define('APP_NAME', 'Programador de Reuniones');
define('APP_VERSION', '1.0.0');

// Incluir base de datos
require_once BASE_PATH . '/config/database.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funciones auxiliares globales
function redirect($page) {
    header("Location: " . BASE_URL . $page);
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getConfiguracion() {
    return fetchOne("SELECT * FROM configuracion WHERE id = 1");
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
