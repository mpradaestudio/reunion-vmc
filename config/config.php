<?php
/**
 * Configuración general de la aplicación
 */

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Rutas
define('BASE_PATH', dirname(__DIR__));

// Calcular BASE_URL dinámicamente según la carpeta de instalación.
// Si la app está en C:\xampp\htdocs\reunion-vmc -> BASE_URL = "/reunion-vmc/"
// Así los enlaces del menú y los recursos (css/js) funcionan aunque
// la app viva en un subdirectorio de htdocs.
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', "/\\"));
$appRoot = str_replace('\\', '/', BASE_PATH);
$baseUrl = '/';
if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
    $sub = trim(substr($appRoot, strlen($docRoot)), '/');
    $baseUrl = ($sub === '') ? '/' : '/' . $sub . '/';
}
define('BASE_URL', $baseUrl);

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
    // Redirección relativa: las páginas que llaman a redirect() están en
    // /pages/, igual que sus destinos, así funciona en cualquier subcarpeta.
    header("Location: $page");
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
