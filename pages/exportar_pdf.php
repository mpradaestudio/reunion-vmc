<?php
/**
 * Exportar programas a PDF con formato profesional
 */

require_once __DIR__ . '/../config/config.php';

// Verificar si existe TCPDF
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Error: TCPDF no está instalado. Ejecuta: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Obtener parámetros
$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');
$programaId = $_GET['programa_id'] ?? null;

// Obtener configuración
$config = getConfiguracion();

// Obtener programas
if ($programaId) {
    // Un solo programa
    $programas = [fetchOne("SELECT * FROM programas_semanales WHERE id = ?", [$programaId])];
} else {
    // Todos los programas del mes
    $programas = fetchAll("
        SELECT * FROM programas_semanales 
        WHERE MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?
        ORDER BY fecha_inicio
    ", [$mes, $anio]);
}

if (empty($programas)) {
    die('No hay programas para exportar');
}

// Crear PDF
class PDF extends TCPDF {
    private $congregacion;
    
    public function setNombreCongregacion($nombre) {
        $this->congregacion = $nombre;
    }
    
    // Header
    public function Header() {
        // No header por defecto
    }
    
    // Footer
    public function Footer() {
        // No footer por defecto
    }
}

// Crear instancia de PDF
$pdf = new PDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->setNombreCongregacion($config['nombre_congregacion']);

// Configuración del documento
$pdf->SetCreator('Sistema de Programación');
$pdf->SetAuthor($config['nombre_congregacion']);
$pdf->SetTitle('Programa de Reuniones - ' . date('F Y', mktime(0, 0, 0, $mes, 1, $anio)));
$pdf->SetSubject('Programa para la reunión entre semana');

// Configuración de márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Nombre de meses en español
$mesesNombres = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];

// Colores de las secciones
$colores = [
    'TESOROS DE LA BIBLIA' => ['r' => 108, 'g' => 117, 'b' => 125],
    'SEAMOS MEJORES MAESTROS' => ['r' => 212, 'g' => 160, 'b' => 30],
    'NUESTRA VIDA CRISTIANA' => ['r' => 139, 'g' => 21, 'b' => 56]
];

// Procesar cada programa
$primerPrograma = true;
foreach ($programas as $index => $programa) {
    
    // Obtener secciones agrupadas
    $secciones = fetchAll("
        SELECT * FROM programa_secciones 
        WHERE programa_id = ? 
        ORDER BY orden
    ", [$programa['id']]);
    
    // Agrupar secciones por tipo
    $seccionesPorTipo = [
        'TESOROS DE LA BIBLIA' => [],
        'SEAMOS MEJORES MAESTROS' => [],
        'NUESTRA VIDA CRISTIANA' => []
    ];
    
    foreach ($secciones as $seccion) {
        $seccionesPorTipo[$seccion['seccion']][] = $seccion;
    }
    
    // Obtener roles
    $roles = fetchAll("
        SELECT ar.rol, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
        FROM asignaciones_roles ar
        LEFT JOIN personas p ON ar.persona_id = p.id
        WHERE ar.programa_id = ?
    ", [$programa['id']]);
    
    $rolesAsignados = [];
    foreach ($roles as $rol) {
        $rolesAsignados[$rol['rol']] = $rol['nombre_completo'] ?? '';
    }
    
    // Nueva página
    if ($primerPrograma) {
        $pdf->AddPage();
        $primerPrograma = false;
    } else {
        $pdf->AddPage();
    }
    
    // Mes y año en la esquina superior derecha
    $mesPrograma = (int)date('n', strtotime($programa['fecha_inicio']));
    $anioPrograma = date('Y', strtotime($programa['fecha_inicio']));
    
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, $mesesNombres[$mesPrograma] . ' ' . $anioPrograma, 0, 1, 'R');
    $pdf->Ln(2);
    
    // Congregación y título principal
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(95, 6, $config['nombre_congregacion'], 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(95, 6, 'Programa para la reunión entre semana', 0, 1, 'R');
    $pdf->Ln(5);
    
    // Título de la semana
    $fechaInicio = new DateTime($programa['fecha_inicio']);
    $fechaFin = new DateTime($programa['fecha_fin']);
    $diaInicio = $fechaInicio->format('d');
    $diaFin = $fechaFin->format('d');
    $mesNombre = $mesesNombres[$mesPrograma];
    
    $pdf->SetFont('helvetica', 'B', 13);
    $tituloSemana = $diaInicio . '-' . $diaFin . ' DE ' . $mesNombre;
    if ($programa['referencia_biblica']) {
        $tituloSemana .= ' | ' . strtoupper($programa['referencia_biblica']);
    }
    $pdf->Cell(0, 8, $tituloSemana, 0, 1, 'L');
    $pdf->Ln(1);
    
    // Canción inicial
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(95, 5, html_entity_decode('♫', ENT_QUOTES, 'UTF-8') . ' Canción ' . $programa['cancion_inicial'], 0, 0, 'L');
    
    // Roles en columna derecha
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(40, 5, 'Presidente:', 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, '', 0, 1, 'L');
    
    $pdf->Cell(95, 5, '', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(40, 5, 'Oración:', 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, '', 0, 1, 'L');
    $pdf->Ln(3);
    
    // TESOROS DE LA BIBLIA
    $color = $colores['TESOROS DE LA BIBLIA'];
    $pdf->SetFillColor($color['r'], $color['g'], $color['b']);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'TESOROS DE LA BIBLIA', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(1);
    
    foreach ($seccionesPorTipo['TESOROS DE LA BIBLIA'] as $seccion) {
        imprimirParte($pdf, $seccion);
    }
    
    $pdf->Ln(2);
    
    // SEAMOS MEJORES MAESTROS
    $color = $colores['SEAMOS MEJORES MAESTROS'];
    $pdf->SetFillColor($color['r'], $color['g'], $color['b']);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'SEAMOS MEJORES MAESTROS', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(1);
    
    foreach ($seccionesPorTipo['SEAMOS MEJORES MAESTROS'] as $seccion) {
        imprimirParte($pdf, $seccion);
    }
    
    $pdf->Ln(2);
    
    // NUESTRA VIDA CRISTIANA
    $color = $colores['NUESTRA VIDA CRISTIANA'];
    $pdf->SetFillColor($color['r'], $color['g'], $color['b']);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'NUESTRA VIDA CRISTIANA', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(1);
    
    // Canción media al inicio de NUESTRA VIDA
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 5, html_entity_decode('♫', ENT_QUOTES, 'UTF-8') . ' Canción ' . $programa['cancion_media'], 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(1);
    
    foreach ($seccionesPorTipo['NUESTRA VIDA CRISTIANA'] as $seccion) {
        imprimirParte($pdf, $seccion);
    }
    
    // Canción final
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(95, 5, html_entity_decode('♫', ENT_QUOTES, 'UTF-8') . ' Canción ' . $programa['cancion_final'], 0, 0, 'L');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(40, 5, 'Oración:', 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, '', 0, 1, 'L');
    
    // Línea separadora entre programas
    if ($index < count($programas) - 1) {
        $pdf->Ln(8);
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    }
}

// Función auxiliar para imprimir partes
function imprimirParte($pdf, $seccion) {
    // Obtener asignaciones
    $asignaciones = fetchAll("
        SELECT ap.orden_presentador, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
        FROM asignaciones_partes ap
        LEFT JOIN personas p ON ap.persona_id = p.id
        WHERE ap.seccion_id = ?
        ORDER BY ap.orden_presentador
    ", [$seccion['id']]);
    
    $asignacionesPorOrden = [];
    foreach ($asignaciones as $asig) {
        $asignacionesPorOrden[$asig['orden_presentador']] = $asig['nombre_completo'] ?? '';
    }
    
    $pdf->SetFont('helvetica', '', 9);
    $titulo = '● ' . $seccion['titulo'];
    if ($seccion['duracion']) {
        $titulo .= ' (' . $seccion['duracion'] . ' min.)';
    }
    
    // Determinar número de asignaciones
    $numAsignaciones = ($seccion['tipo_asignacion'] === 'Estudiante/Ayudante') ? 2 : 1;
    
    $y = $pdf->GetY();
    $pdf->MultiCell(110, 5, $titulo, 0, 'L', false, 0);
    
    // Asignaciones
    $pdf->SetXY(125, $y);
    
    for ($i = 1; $i <= $numAsignaciones; $i++) {
        $label = '';
        if ($seccion['tipo_asignacion'] === 'Estudiante/Ayudante') {
            $label = 'Estudiante / Ayudante:';
        } elseif ($seccion['tipo_asignacion'] === 'Conductor/Lector') {
            $label = 'Conductor / Lector:';
        } else {
            $label = 'Asignado:';
        }
        
        if ($i == 2) {
            $label = 'Estudiante / Ayudante:';
        }
        
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(30, 5, $label, 0, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, '', 0, 1, 'L');
        
        if ($i < $numAsignaciones) {
            $pdf->SetX(125);
        }
    }
    
    $pdf->Ln(1);
}

// Salida del PDF
$nombreArchivo = 'Programa_' . $mesesNombres[$mes] . '_' . $anio . '.pdf';
$pdf->Output($nombreArchivo, 'I');
