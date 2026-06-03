<?php
/**
 * Script de verificación de instalación
 * Ejecuta este script para verificar que todo esté correctamente instalado
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test de Instalación</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <div class='row'>
        <div class='col-md-8 offset-md-2'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h3 class='mb-0'><i class='bi bi-check-circle'></i> Test de Instalación</h3>
                </div>
                <div class='card-body'>";

$errores = [];
$advertencias = [];
$ok = [];

// 1. Verificar versión de PHP
echo "<h5><i class='bi bi-1-circle'></i> Versión de PHP</h5>";
$phpVersion = phpversion();
echo "<p>Versión actual: <strong>$phpVersion</strong></p>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> PHP 7.4 o superior detectado</div>";
    $ok[] = "PHP versión correcta";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Se requiere PHP 7.4 o superior</div>";
    $errores[] = "PHP versión incorrecta";
}

// 2. Verificar archivos de configuración
echo "<hr><h5><i class='bi bi-2-circle'></i> Archivos de Configuración</h5>";
$archivos = [
    'config/config.php' => 'Configuración general',
    'config/database.php' => 'Configuración de base de datos',
    'database.sql' => 'Script SQL de instalación',
    'composer.json' => 'Archivo de dependencias'
];

foreach ($archivos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<p class='text-success'><i class='bi bi-check'></i> $descripcion - <code>$archivo</code></p>";
        $ok[] = $archivo;
    } else {
        echo "<p class='text-danger'><i class='bi bi-x'></i> $descripcion - <code>$archivo</code> NO ENCONTRADO</p>";
        $errores[] = $archivo;
    }
}

// 3. Verificar conexión a base de datos
echo "<hr><h5><i class='bi bi-3-circle'></i> Conexión a Base de Datos</h5>";
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> Conexión exitosa a la base de datos</div>";
    $ok[] = "Conexión a BD";
    
    // Verificar tablas
    $tablas = ['personas', 'programas_semanales', 'configuracion', 'perfiles'];
    echo "<p><strong>Tablas encontradas:</strong></p><ul>";
    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "<li class='text-success'><i class='bi bi-check'></i> $tabla</li>";
            $ok[] = "Tabla $tabla";
        } else {
            echo "<li class='text-danger'><i class='bi bi-x'></i> $tabla NO ENCONTRADA</li>";
            $errores[] = "Tabla $tabla no existe";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Error de conexión: " . $e->getMessage() . "</div>";
    echo "<p><strong>Posibles soluciones:</strong></p>";
    echo "<ul>";
    echo "<li>Verifica que MySQL esté corriendo en XAMPP</li>";
    echo "<li>Verifica las credenciales en config/database.php</li>";
    echo "<li>Asegúrate de haber importado database.sql</li>";
    echo "</ul>";
    $errores[] = "Conexión a BD fallida";
}

// 4. Verificar Composer y dependencias
echo "<hr><h5><i class='bi bi-4-circle'></i> Dependencias (Composer)</h5>";
if (file_exists('vendor/autoload.php')) {
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> Dependencias instaladas correctamente</div>";
    require_once 'vendor/autoload.php';
    
    // Verificar TCPDF
    if (class_exists('TCPDF')) {
        echo "<p class='text-success'><i class='bi bi-check'></i> TCPDF disponible (generación de PDF)</p>";
        $ok[] = "TCPDF instalado";
    } else {
        echo "<p class='text-warning'><i class='bi bi-exclamation-triangle'></i> TCPDF no disponible</p>";
        $advertencias[] = "TCPDF no disponible";
    }
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Dependencias NO instaladas</div>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<pre class='bg-dark text-white p-3'>cd " . __DIR__ . "\ncomposer install</pre>";
    $errores[] = "Dependencias no instaladas";
}

// 5. Verificar permisos de escritura
echo "<hr><h5><i class='bi bi-5-circle'></i> Permisos de Escritura</h5>";
$directorios = ['.'];
foreach ($directorios as $dir) {
    if (is_writable($dir)) {
        echo "<p class='text-success'><i class='bi bi-check'></i> Directorio <code>$dir</code> tiene permisos de escritura</p>";
        $ok[] = "Permisos $dir";
    } else {
        echo "<p class='text-warning'><i class='bi bi-exclamation-triangle'></i> Directorio <code>$dir</code> podría no tener permisos de escritura</p>";
        $advertencias[] = "Permisos $dir";
    }
}

// 6. Verificar extensiones de PHP
echo "<hr><h5><i class='bi bi-6-circle'></i> Extensiones de PHP</h5>";
$extensiones = [
    'pdo_mysql' => 'PDO MySQL (requerido)',
    'mbstring' => 'Multibyte String (requerido)',
    'curl' => 'cURL (requerido para scraping)',
    'gd' => 'GD (opcional para imágenes en PDF)'
];

foreach ($extensiones as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "<p class='text-success'><i class='bi bi-check'></i> $desc</p>";
        $ok[] = "Extensión $ext";
    } else {
        if ($ext === 'gd') {
            echo "<p class='text-warning'><i class='bi bi-exclamation-triangle'></i> $desc (opcional)</p>";
            $advertencias[] = "Extensión $ext no disponible";
        } else {
            echo "<p class='text-danger'><i class='bi bi-x'></i> $desc</p>";
            $errores[] = "Extensión $ext no disponible";
        }
    }
}

// Resumen final
echo "<hr><h4><i class='bi bi-clipboard-check'></i> Resumen</h4>";
echo "<div class='row'>";
echo "<div class='col-md-4 text-center'>";
echo "<div class='alert alert-success'>";
echo "<h1>" . count($ok) . "</h1>";
echo "<p class='mb-0'>OK</p>";
echo "</div></div>";

echo "<div class='col-md-4 text-center'>";
echo "<div class='alert alert-warning'>";
echo "<h1>" . count($advertencias) . "</h1>";
echo "<p class='mb-0'>Advertencias</p>";
echo "</div></div>";

echo "<div class='col-md-4 text-center'>";
echo "<div class='alert alert-danger'>";
echo "<h1>" . count($errores) . "</h1>";
echo "<p class='mb-0'>Errores</p>";
echo "</div></div>";
echo "</div>";

if (count($errores) === 0) {
    echo "<div class='alert alert-success mt-3'>";
    echo "<h4><i class='bi bi-check-circle-fill'></i> ¡Sistema listo para usar!</h4>";
    echo "<p class='mb-0'>Todos los componentes están correctamente instalados.</p>";
    echo "<a href='index.php' class='btn btn-success mt-3'><i class='bi bi-arrow-right'></i> Ir al Sistema</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger mt-3'>";
    echo "<h4><i class='bi bi-x-circle-fill'></i> Se encontraron errores</h4>";
    echo "<p>Por favor, revisa los errores marcados arriba y sigue las instrucciones en <strong>INSTALL.md</strong></p>";
    echo "</div>";
}

echo "</div></div></div></div></div></body></html>";
?>
