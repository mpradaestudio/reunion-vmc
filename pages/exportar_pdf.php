<?php
/**
 * Exportar programas a PDF
 * Diseño: fiel al formato oficial impreso (2 semanas por página LETTER)
 * Fuente: Google Sans (TTF local) con fallback a helvetica
 * Layout: columna izquierda = contenido, columna derecha = asignaciones
 */

require_once __DIR__ . '/../config/config.php';

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Error: TCPDF no está instalado. Ejecuta: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';

/* ============================================================
   PARÁMETROS
   ============================================================ */
$mes        = isset($_GET['mes'])         ? (int)$_GET['mes']         : (int)date('n');
$anio       = isset($_GET['anio'])        ? (int)$_GET['anio']        : (int)date('Y');
$programaId = $_GET['programa_id']        ?? null;
$config     = getConfiguracion();

/* ============================================================
   DATOS
   ============================================================ */
if ($programaId) {
    $programas = [fetchOne("SELECT * FROM programas_semanales WHERE id = ?", [$programaId])];
} else {
    $programas = fetchAll("
        SELECT * FROM programas_semanales
        WHERE MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?
        ORDER BY fecha_inicio
    ", [$mes, $anio]);
}

if (empty($programas)) {
    die('No hay programas para exportar');
}

/* ============================================================
   CONSTANTES DE DISEÑO
   ============================================================ */
// Márgenes página LETTER (215.9 x 279.4 mm)
define('PDF_MARGIN_L',  14);
define('PDF_MARGIN_R',  14);
define('PDF_MARGIN_T',  12);
define('PDF_MARGIN_B',  12);
define('PDF_PAGE_W',   215.9);
define('PDF_INNER_W',  PDF_PAGE_W - PDF_MARGIN_L - PDF_MARGIN_R);  // ≈ 187.9 mm

// Columnas (proporción izq 58% / der 42% del ancho útil)
define('PDF_COL_L',    round(PDF_INNER_W * 0.58));   // ≈ 109 mm  contenido
define('PDF_COL_R',    round(PDF_INNER_W * 0.42));   // ≈  79 mm  asignaciones
define('PDF_COL_R_X',  PDF_MARGIN_L + PDF_COL_L);    // X inicio columna derecha

// Alturas de fila
define('ROW_H',        5.2);   // fila normal
define('ROW_H_SM',     4.5);   // fila compacta (segunda asignación)
define('SEC_H',        6.2);   // encabezado de sección

// Tamaños de fuente
define('FS_MONTH',     18);
define('FS_HEADER',    10);
define('FS_WEEK',      12);
define('FS_LABEL',      9);
define('FS_BODY',       8.5);
define('FS_SONG',       9);
define('FS_ASSIGN',     8.5);

// Colores secciones (RGB)
$COLOR_TESOROS  = [108, 117, 125];
$COLOR_MAESTROS = [212, 160,  30];
$COLOR_VIDA     = [139,  21,  56];
$COLOR_GRAY_LBL = [130, 130, 130];   // gris etiquetas
$COLOR_BLACK    = [  0,   0,   0];
$COLOR_WHITE    = [255, 255, 255];
$COLOR_SONG     = [ 90,  90,  90];   // gris canciones
$COLOR_DIVIDER  = [210, 210, 210];   // línea separadora

$MESES = [
    1=>'ENERO', 2=>'FEBRERO',  3=>'MARZO',     4=>'ABRIL',
    5=>'MAYO',  6=>'JUNIO',    7=>'JULIO',     8=>'AGOSTO',
    9=>'SEPTIEMBRE', 10=>'OCTUBRE', 11=>'NOVIEMBRE', 12=>'DICIEMBRE'
];

/* ============================================================
   CLASE PDF
   ============================================================ */
class VMC_PDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

/* ============================================================
   REGISTRO DE FUENTES
   Intenta cargar Google Sans desde assets/fonts/.
   Si no existe, usa helvetica (idéntica métrica en TCPDF).
   ============================================================ */
$fontsDir = realpath(__DIR__ . '/../assets/fonts');

function registrarFuente(VMC_PDF $pdf, string $fontsDir, string $ttfFile, string $alias): string {
    $path = $fontsDir . DIRECTORY_SEPARATOR . $ttfFile;
    if ($fontsDir && file_exists($path)) {
        try {
            $name = $pdf->addTTFfont($path, 'TrueTypeUnicode', '', 32);
            if ($name) {
                return $name;
            }
        } catch (Exception $e) {
            // Fallback a helvetica
        }
    }
    return 'helvetica';
}

$pdf = new VMC_PDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

// Fuentes (devuelven el nombre TCPDF para usar con SetFont)
$FNT_REG  = registrarFuente($pdf, $fontsDir, 'GoogleSans-Regular.ttf',  'googlesans');
$FNT_BOLD = registrarFuente($pdf, $fontsDir, 'GoogleSans-Bold.ttf',     'googlesansbold');
// Para el símbolo ♪ usamos DejaVu (incluida en TCPDF, soporta Unicode musical)
$FNT_ICON = 'dejavusans';

$pdf->SetCreator('Programador VMC');
$pdf->SetAuthor($config['nombre_congregacion']);
$pdf->SetTitle('Programa – ' . $MESES[$mes] . ' ' . $anio);
$pdf->SetMargins(PDF_MARGIN_L, PDF_MARGIN_T, PDF_MARGIN_R);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_B);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

/* ============================================================
   HELPERS
   ============================================================ */

/** Aplica color de texto RGB */
function setTxt(VMC_PDF $pdf, array $rgb): void {
    $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
}

/** Aplica color de relleno RGB */
function setFill(VMC_PDF $pdf, array $rgb): void {
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
}

/** Devuelve nombre del mes en minúsculas para fechas */
function mesMin(int $n): string {
    $m = ['','enero','febrero','marzo','abril','mayo','junio',
          'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return $m[$n] ?? '';
}

/**
 * Dibuja la línea de una canción.
 * Símbolo ♪ en DejaVu, texto en la fuente principal.
 */
function drawSong(VMC_PDF $pdf, string $fntIcon, string $fntReg,
                  array $colorSong, string $numero, float $xOffset = 4): void {
    global $COLOR_BLACK;
    $pdf->SetX(PDF_MARGIN_L + $xOffset);
    setTxt($pdf, $colorSong);

    // Símbolo musical con DejaVu para garantizar Unicode
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(5, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');   // ♪ UTF-8

    $pdf->SetFont($fntReg, '', FS_SONG);
    $pdf->Cell(0, ROW_H, ' Canción ' . $numero, 0, 1, 'L');
    setTxt($pdf, $COLOR_BLACK);
}

/**
 * Dibuja el encabezado de sección (banda de color con texto blanco).
 */
function drawSeccionHeader(VMC_PDF $pdf, string $fntBold, string $nombre,
                           array $color, array $colorWhite): void {
    setFill($pdf, $color);
    setTxt($pdf, $colorWhite);
    $pdf->SetFont($fntBold, '', FS_LABEL);
    $pdf->SetX(PDF_MARGIN_L);
    $pdf->Cell(PDF_INNER_W, SEC_H, $nombre, 0, 1, 'L', true);
    setTxt($pdf, [0,0,0]);
    $pdf->Ln(1);
}

/**
 * Dibuja una parte del programa en layout de dos columnas.
 *
 * Columna izquierda: ● Título (duración)
 * Columna derecha:   Etiqueta: [gris]  Nombre [negro]  (en 1 línea)
 *                    Si hay 2 personas: segunda fila debajo
 */
function drawParte(VMC_PDF $pdf, array $seccion,
                   string $fntReg, string $fntBold, string $fntIcon,
                   array $colorGray, array $colorBlack): void {

    // Asignaciones de la BD
    $asignaciones = fetchAll("
        SELECT ap.orden_presentador,
               CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
        FROM asignaciones_partes ap
        LEFT JOIN personas p ON ap.persona_id = p.id
        WHERE ap.seccion_id = ?
        ORDER BY ap.orden_presentador
    ", [$seccion['id']]);

    $porOrden = [];
    foreach ($asignaciones as $a) {
        $porOrden[$a['orden_presentador']] = $a['nombre_completo'] ?? '';
    }

    // Tipo y etiquetas
    $tipo      = $seccion['tipo_asignacion'];
    $doble     = in_array($tipo, ['Estudiante/Ayudante', 'Conductor/Lector']);

    if ($tipo === 'Estudiante/Ayudante') {
        $etiquetas = ['Estudiante / Ayudante:'];  // etiqueta única combinada
    } elseif ($tipo === 'Conductor/Lector') {
        $etiquetas = ['Conductor / Lector:'];
    } else {
        $etiquetas = ['Asignado:'];
    }

    // Nombre(s): si hay 2, unir con " / "
    if ($doble) {
        $n1 = $porOrden[1] ?? '';
        $n2 = $porOrden[2] ?? '';
        $nombreStr = trim($n1 . ($n1 && $n2 ? ' / ' : '') . $n2);
        $etiquetaStr = $etiquetas[0];
        $filas = 1;
    } else {
        $nombreStr   = $porOrden[1] ?? '';
        $etiquetaStr = $etiquetas[0];
        $filas = 1;
    }

    // Construir texto de la columna izquierda
    $titulo = '●  ' . $seccion['titulo'];
    if ($seccion['duracion']) {
        $titulo .= '  (' . $seccion['duracion'] . ' min.)';
    }

    // Guardar Y antes de escribir columna izquierda
    $yStart = $pdf->GetY();

    // ── Columna izquierda: título ──────────────────────────────────
    $pdf->SetFont($fntReg, '', FS_BODY);
    setTxt($pdf, $colorBlack);
    $pdf->SetX(PDF_MARGIN_L);
    $pdf->MultiCell(PDF_COL_L - 2, ROW_H, $titulo, 0, 'L', false, 0);
    $yAfterLeft = $pdf->GetY();

    // ── Columna derecha: etiqueta (gris) + nombre (negro) ──────────
    $pdf->SetXY(PDF_COL_R_X, $yStart);

    // Etiqueta en gris
    $pdf->SetFont($fntBold, '', FS_ASSIGN - 0.5);
    setTxt($pdf, $colorGray);
    $pdf->Cell(
        min(strlen($etiquetaStr) * 2.1 + 2, 48),
        ROW_H, $etiquetaStr, 0, 0, 'L'
    );

    // Nombre en negro
    $pdf->SetFont($fntReg, '', FS_ASSIGN);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(0, ROW_H, $nombreStr, 0, 1, 'L');

    // Avanzar al mayor de los dos Y
    $yEnd = max($yAfterLeft, $pdf->GetY());
    $pdf->SetY($yEnd);
    $pdf->Ln(0.8);
}

/**
 * Dibuja el bloque de una semana completa dentro de la página.
 * Retorna el Y final.
 */
function drawSemana(VMC_PDF $pdf, array $programa, array $rolesAsignados,
                    array $seccionesPorTipo, array $MESES,
                    string $fntReg, string $fntBold, string $fntIcon,
                    array $colorTesoros, array $colorMaestros, array $colorVida,
                    array $colorGray, array $colorBlack, array $colorWhite,
                    array $colorSong, string $congregacion): void {

    $mesP  = (int)date('n', strtotime($programa['fecha_inicio']));
    $anioP = (int)date('Y', strtotime($programa['fecha_inicio']));
    $mesPF = (int)date('n', strtotime($programa['fecha_fin']));

    $fi    = new DateTime($programa['fecha_inicio']);
    $ff    = new DateTime($programa['fecha_fin']);
    $dIni  = (int)$fi->format('d');
    $dFin  = (int)$ff->format('d');

    // Título de la semana: "1-7 DE JUNIO | JEREMÍAS 1-3"
    $tituloSemana = $dIni . '-' . $dFin . ' DE ' . $MESES[$mesP];
    if ($mesP !== $mesPF) {
        $tituloSemana = $dIni . ' DE ' . $MESES[$mesP] . ' - ' . $dFin . ' DE ' . $MESES[$mesPF];
    }
    if (!empty($programa['referencia_biblica'])) {
        $tituloSemana .= '  |  ' . strtoupper($programa['referencia_biblica']);
    }

    // ── Semana: título ─────────────────────────────────────────────
    $pdf->SetFont($fntBold, '', FS_WEEK);
    setTxt($pdf, $colorBlack);
    $pdf->SetX(PDF_MARGIN_L);
    // Columna izquierda: título semana
    $pdf->Cell(PDF_COL_L, 7, $tituloSemana, 0, 0, 'L');
    // Columna derecha: etiqueta Presidente
    $xRight = PDF_COL_R_X;
    $pdf->SetX($xRight);
    $pdf->SetFont($fntBold, '', FS_ASSIGN - 0.5);
    setTxt($pdf, $colorGray);
    $pdf->Cell(22, 7, 'Presidente:', 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_ASSIGN);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(0, 7, $rolesAsignados['Presidente'] ?? '', 0, 1, 'L');

    // ── Canción inicial + Oración inicial ──────────────────────────
    // Canción inicial (izquierda)
    $yRow = $pdf->GetY();
    $pdf->SetX(PDF_MARGIN_L + 2);
    setTxt($pdf, $colorSong);
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(4, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_SONG);
    $pdf->Cell(PDF_COL_L - 6, ROW_H, 'Canción ' . $programa['cancion_inicial'], 0, 0, 'L');
    // Oración inicial (derecha)
    $pdf->SetXY($xRight, $yRow);
    $pdf->SetFont($fntBold, '', FS_ASSIGN - 0.5);
    setTxt($pdf, $colorGray);
    $pdf->Cell(22, ROW_H, 'Oración:', 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_ASSIGN);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(0, ROW_H, $rolesAsignados['Oración inicial'] ?? '', 0, 1, 'L');
    $pdf->Ln(2);

    // ── TESOROS DE LA BIBLIA ───────────────────────────────────────
    drawSeccionHeader($pdf, $fntBold, 'TESOROS DE LA BIBLIA', $colorTesoros, $colorWhite);
    foreach ($seccionesPorTipo['TESOROS DE LA BIBLIA'] as $s) {
        drawParte($pdf, $s, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack);
    }
    $pdf->Ln(1.5);

    // ── SEAMOS MEJORES MAESTROS ────────────────────────────────────
    drawSeccionHeader($pdf, $fntBold, 'SEAMOS MEJORES MAESTROS', $colorMaestros, $colorBlack);
    foreach ($seccionesPorTipo['SEAMOS MEJORES MAESTROS'] as $s) {
        drawParte($pdf, $s, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack);
    }
    $pdf->Ln(1.5);

    // ── NUESTRA VIDA CRISTIANA ─────────────────────────────────────
    drawSeccionHeader($pdf, $fntBold, 'NUESTRA VIDA CRISTIANA', $colorVida, $colorWhite);

    // Canción media
    $pdf->SetX(PDF_MARGIN_L + 2);
    setTxt($pdf, $colorSong);
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(4, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_SONG);
    $pdf->Cell(0, ROW_H, 'Canción ' . $programa['cancion_media'], 0, 1, 'L');
    setTxt($pdf, $colorBlack);
    $pdf->Ln(1);

    foreach ($seccionesPorTipo['NUESTRA VIDA CRISTIANA'] as $s) {
        drawParte($pdf, $s, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack);
    }

    // Canción final + Oración final
    $pdf->Ln(1);
    $yRow = $pdf->GetY();
    $pdf->SetX(PDF_MARGIN_L + 2);
    setTxt($pdf, $colorSong);
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(4, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_SONG);
    $pdf->Cell(PDF_COL_L - 6, ROW_H, 'Canción ' . $programa['cancion_final'], 0, 0, 'L');
    $pdf->SetXY($xRight, $yRow);
    $pdf->SetFont($fntBold, '', FS_ASSIGN - 0.5);
    setTxt($pdf, $colorGray);
    $pdf->Cell(22, ROW_H, 'Oración:', 0, 0, 'L');
    $pdf->SetFont($fntReg, '', FS_ASSIGN);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(0, ROW_H, $rolesAsignados['Oración final'] ?? '', 0, 1, 'L');
}

/* ============================================================
   RENDER: 2 SEMANAS POR PÁGINA
   ============================================================ */

// Agrupar de a 2
$grupos = array_chunk($programas, 2);

foreach ($grupos as $grupo) {
    $pdf->AddPage();

    // ── Encabezado de página: MES AÑO (der) + Congregación / Título (izq/der) ──
    $mesEncabezado = (int)date('n', strtotime($grupo[0]['fecha_inicio']));
    $anioEncabezado = (int)date('Y', strtotime($grupo[0]['fecha_inicio']));

    $pdf->SetFont($FNT_BOLD, '', FS_MONTH);
    setTxt($pdf, [0,0,0]);
    $pdf->Cell(0, 10, $MESES[$mesEncabezado] . ' ' . $anioEncabezado, 0, 1, 'R');
    $pdf->Ln(1);

    // Sub-encabezado: Congregación (izq, negrita) | "Programa para la reunión entre semana" (der)
    $pdf->SetFont($FNT_BOLD, '', FS_HEADER);
    $pdf->Cell(round(PDF_INNER_W * 0.45), 6, $config['nombre_congregacion'], 0, 0, 'L');
    $pdf->SetFont($FNT_REG, '', FS_HEADER);
    $pdf->Cell(0, 6, 'Programa para la reunión entre semana', 0, 1, 'R');

    // Línea decorativa bajo el encabezado
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(PDF_MARGIN_L, $pdf->GetY() + 1, PDF_PAGE_W - PDF_MARGIN_R, $pdf->GetY() + 1);
    $pdf->Ln(5);

    foreach ($grupo as $idxInGrupo => $programa) {

        // Secciones del programa
        $secciones = fetchAll("
            SELECT * FROM programa_secciones
            WHERE programa_id = ?
            ORDER BY orden
        ", [$programa['id']]);

        $spT = [
            'TESOROS DE LA BIBLIA'    => [],
            'SEAMOS MEJORES MAESTROS' => [],
            'NUESTRA VIDA CRISTIANA'  => [],
        ];
        foreach ($secciones as $s) {
            if (isset($spT[$s['seccion']])) {
                $spT[$s['seccion']][] = $s;
            }
        }

        // Roles generales
        $roles = fetchAll("
            SELECT ar.rol, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
            FROM asignaciones_roles ar
            LEFT JOIN personas p ON ar.persona_id = p.id
            WHERE ar.programa_id = ?
        ", [$programa['id']]);
        $rolesMap = [];
        foreach ($roles as $r) {
            $rolesMap[$r['rol']] = $r['nombre_completo'] ?? '';
        }

        // Dibujar semana
        drawSemana(
            $pdf, $programa, $rolesMap, $spT, $MESES,
            $FNT_REG, $FNT_BOLD, $FNT_ICON,
            $COLOR_TESOROS, $COLOR_MAESTROS, $COLOR_VIDA,
            $COLOR_GRAY_LBL, $COLOR_BLACK, $COLOR_WHITE, $COLOR_SONG,
            $config['nombre_congregacion']
        );

        // Separador entre las 2 semanas (solo después de la primera)
        if ($idxInGrupo === 0 && isset($grupo[1])) {
            $pdf->Ln(4);
            $pdf->SetDrawColor($COLOR_DIVIDER[0], $COLOR_DIVIDER[1], $COLOR_DIVIDER[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(PDF_MARGIN_L, $pdf->GetY(), PDF_PAGE_W - PDF_MARGIN_R, $pdf->GetY());
            $pdf->Ln(6);
        }
    }
}

/* ============================================================
   SALIDA
   ============================================================ */
$nombreArchivo = 'Programa_' . $MESES[$mes] . '_' . $anio . '.pdf';
$pdf->Output($nombreArchivo, 'I');
