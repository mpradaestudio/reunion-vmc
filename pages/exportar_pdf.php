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

// Columnas — izq 54% / der 46% para dar más espacio a nombres largos
define('PDF_COL_L',    round(PDF_INNER_W * 0.54));   // ≈ 101 mm  contenido
define('PDF_COL_R',    round(PDF_INNER_W * 0.46));   // ≈  86 mm  asignaciones
define('PDF_COL_R_X',  PDF_MARGIN_L + PDF_COL_L);    // X inicio columna derecha

// Alturas de fila
define('ROW_H',        4.8);   // fix #4: reduce interlineado en MultiCell largos
define('ROW_H_SM',     4.2);   // fila compacta
define('SEC_H',        6.2);   // encabezado de sección

// Tamaños de fuente
define('FS_MONTH',     18);
define('FS_HEADER',    13);   // fix #1: congregación más grande
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
$COLOR_SONG     = [ 34, 139,  34];   // fix #3: verde para canciones
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

/**
 * SetFont wrapper que aplica bold automáticamente cuando la fuente
 * es el fallback 'helvetica' (que requiere estilo 'B' explícito).
 * Para Google Sans cargada como TTF, el estilo se deja en '' porque
 * ya está embebida como variante bold independiente.
 */
function fntBold(VMC_PDF $pdf, string $fntBold, float $size): void {
    $style = ($fntBold === 'helvetica') ? 'B' : '';
    $pdf->SetFont($fntBold, $style, $size);
}

function fntReg(VMC_PDF $pdf, string $fntReg, float $size): void {
    $pdf->SetFont($fntReg, '', $size);
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
 * Dibuja el encabezado de sección (banda de color con texto blanco/negro).
 */
function drawSeccionHeader(VMC_PDF $pdf, string $fntBold, string $nombre,
                           array $color, array $colorTxt): void {
    setFill($pdf, $color);
    setTxt($pdf, $colorTxt);
    fntBold($pdf, $fntBold, FS_LABEL);
    $pdf->SetX(PDF_MARGIN_L);
    $pdf->Cell(PDF_INNER_W, SEC_H, $nombre, 0, 1, 'L', true);
    setTxt($pdf, [0, 0, 0]);
    $pdf->Ln(1);
}

/**
 * Dibuja una parte del programa en layout de dos columnas.
 *
 * Columna izquierda : • Título (duración)   — bullet via DejaVu, texto en fuente principal
 * Columna derecha   : [Etiqueta alineada R] [Nombre alineado L con wrap]
 *
 * Anchos fijos de la columna derecha (total ≈ PDF_COL_R mm):
 *   LBL_W = 32 mm  → etiqueta alineada a la derecha
 *   NOM_W = PDF_COL_R - LBL_W - 1 mm  → nombre con MultiCell para evitar overflow
 */
function drawParte(VMC_PDF $pdf, array $seccion,
                   string $fntReg, string $fntBold, string $fntIcon,
                   array $colorGray, array $colorBlack): void {

    // Anchos columna derecha — fix #5: LBL más ancho, NOM más estrecho
    // para que "Conductor / Lector:" + "Nombre1 / Nombre2" quepan en 1 línea
    $LBL_W = 36;
    $NOM_W = PDF_COL_R - $LBL_W - 2;

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

    // Etiqueta y nombre(s)
    $tipo  = $seccion['tipo_asignacion'];
    $doble = in_array($tipo, ['Estudiante/Ayudante', 'Conductor/Lector']);

    if ($tipo === 'Estudiante/Ayudante') {
        $etiquetaStr = 'Estudiante / Ayudante:';
    } elseif ($tipo === 'Conductor/Lector') {
        $etiquetaStr = 'Conductor / Lector:';
    } else {
        $etiquetaStr = 'Asignado:';
    }

    if ($doble) {
        $n1 = $porOrden[1] ?? '';
        $n2 = $porOrden[2] ?? '';
        $nombreStr = trim($n1 . ($n1 && $n2 ? ' / ' : '') . $n2);
    } else {
        $nombreStr = $porOrden[1] ?? '';
    }

    // ── Columna izquierda: bullet + título ────────────────────────
    // Guardamos Y de inicio para alinear la columna derecha al mismo nivel.
    $yStart = $pdf->GetY();
    $pdf->SetX(PDF_MARGIN_L);

    // Bullet con DejaVu (unicode-safe)
    $pdf->SetFont($fntIcon, '', FS_BODY);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(4, ROW_H, "\xe2\x80\xa2", 0, 0, 'L');   // • U+2022

    // Título en fuente principal — MultiCell con $ln=1 para que el cursor
    // quede al FINAL del bloque (altura real calculada por TCPDF).
    fntReg($pdf, $fntReg, FS_BODY);
    $tituloStr = $seccion['titulo'];
    if ($seccion['duracion']) {
        $tituloStr .= '  (' . $seccion['duracion'] . ' min.)';
    }
    $pdf->MultiCell(PDF_COL_L - 6, ROW_H, $tituloStr, 0, 'L', false, 1);
    $yAfterLeft = $pdf->GetY();   // Y real después del bloque completo

    // ── Columna derecha: etiqueta (R) + nombre (L con wrap) ────────
    // Volvemos al Y de inicio para que etiqueta y título arranquen a la misma altura.
    $pdf->SetXY(PDF_COL_R_X, $yStart);

    // Etiqueta alineada a la derecha dentro de LBL_W
    fntBold($pdf, $fntBold, FS_ASSIGN - 0.5);
    setTxt($pdf, $colorGray);
    $pdf->Cell($LBL_W, ROW_H, $etiquetaStr, 0, 0, 'R');

    // Nombre con MultiCell — ancho suficiente para evitar wrap en nombres dobles
    fntReg($pdf, $fntReg, FS_ASSIGN);
    setTxt($pdf, $colorBlack);
    $pdf->Cell(1, ROW_H, '', 0, 0);           // separador visual de 1 mm
    $pdf->MultiCell($NOM_W, ROW_H, $nombreStr, 0, 'L', false, 1);
    $yAfterRight = $pdf->GetY();

    // Avanzar al mayor Y: garantiza que la siguiente parte nunca se monte
    $pdf->SetY(max($yAfterLeft, $yAfterRight));
    $pdf->Ln(0.6);
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
    $mesPF = (int)date('n', strtotime($programa['fecha_fin']));

    $fi   = new DateTime($programa['fecha_inicio']);
    $ff   = new DateTime($programa['fecha_fin']);
    $dIni = (int)$fi->format('d');
    $dFin = (int)$ff->format('d');

    // Título de la semana
    $tituloSemana = $dIni . '-' . $dFin . ' DE ' . $MESES[$mesP];
    if ($mesP !== $mesPF) {
        $tituloSemana = $dIni . ' DE ' . $MESES[$mesP] . ' - ' . $dFin . ' DE ' . $MESES[$mesPF];
    }
    if (!empty($programa['referencia_biblica'])) {
        $tituloSemana .= '  |  ' . strtoupper($programa['referencia_biblica']);
    }

    // Ancho fijo etiqueta de roles — mismo que drawParte (fix #5)
    $LBL_W = 36;
    $NOM_W = PDF_COL_R - $LBL_W - 2;
    $xRight = PDF_COL_R_X;

    // Helper local para dibujar una fila de rol (etiqueta R + nombre L)
    $drawRol = function(string $etiqueta, string $nombre, float $yPos)
               use ($pdf, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack,
                    $xRight, $LBL_W, $NOM_W): void {
        $pdf->SetXY($xRight, $yPos);
        fntBold($pdf, $fntBold, FS_ASSIGN - 0.5);
        setTxt($pdf, $colorGray);
        $pdf->Cell($LBL_W, ROW_H, $etiqueta, 0, 0, 'R');
        $pdf->Cell(1, ROW_H, '', 0, 0);
        fntReg($pdf, $fntReg, FS_ASSIGN);
        setTxt($pdf, $colorBlack);
        $pdf->MultiCell($NOM_W, ROW_H, $nombre, 0, 'L', false, 1);
    };

    // ── Título semana + Presidente ─────────────────────────────────
    $yRow = $pdf->GetY();
    fntBold($pdf, $fntBold, FS_WEEK);
    setTxt($pdf, $colorBlack);
    $pdf->SetX(PDF_MARGIN_L);
    $pdf->MultiCell(PDF_COL_L, 7, $tituloSemana, 0, 'L', false, 0);
    $yAfterTitle = $pdf->GetY();
    $drawRol('Presidente:', $rolesAsignados['Presidente'] ?? '', $yRow);
    $pdf->SetY(max($yAfterTitle, $pdf->GetY()));

    // ── Canción inicial + Oración inicial ──────────────────────────
    $yRow = $pdf->GetY();
    $pdf->SetX(PDF_MARGIN_L + 2);
    setTxt($pdf, $colorSong);
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(4, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');
    fntReg($pdf, $fntReg, FS_SONG);
    $pdf->Cell(PDF_COL_L - 6, ROW_H, 'Canción ' . $programa['cancion_inicial'], 0, 0, 'L');
    $yAfterSong = $pdf->GetY() + ROW_H;
    $drawRol('Oración:', $rolesAsignados['Oración inicial'] ?? '', $yRow);
    $pdf->SetY(max($yAfterSong, $pdf->GetY()));
    setTxt($pdf, $colorBlack);
    $pdf->Ln(2);

    // ── TESOROS DE LA BIBLIA ───────────────────────────────────────
    drawSeccionHeader($pdf, $fntBold, 'TESOROS DE LA BIBLIA', $colorTesoros, $colorWhite);
    foreach ($seccionesPorTipo['TESOROS DE LA BIBLIA'] as $s) {
        drawParte($pdf, $s, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack);
    }
    $pdf->Ln(1.5);

    // ── SEAMOS MEJORES MAESTROS ────────────────────────────────────
    drawSeccionHeader($pdf, $fntBold, 'SEAMOS MEJORES MAESTROS', $colorMaestros, $colorWhite);
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
    fntReg($pdf, $fntReg, FS_SONG);
    $pdf->Cell(0, ROW_H, 'Canción ' . $programa['cancion_media'], 0, 1, 'L');
    setTxt($pdf, $colorBlack);
    $pdf->Ln(1);

    foreach ($seccionesPorTipo['NUESTRA VIDA CRISTIANA'] as $s) {
        drawParte($pdf, $s, $fntReg, $fntBold, $fntIcon, $colorGray, $colorBlack);
    }

    // ── Canción final + Oración final ──────────────────────────────
    $pdf->Ln(1);
    $yRow = $pdf->GetY();
    $pdf->SetX(PDF_MARGIN_L + 2);
    setTxt($pdf, $colorSong);
    $pdf->SetFont($fntIcon, '', FS_SONG - 0.5);
    $pdf->Cell(4, ROW_H, "\xe2\x99\xaa", 0, 0, 'L');
    fntReg($pdf, $fntReg, FS_SONG);
    $pdf->Cell(PDF_COL_L - 6, ROW_H, 'Canción ' . $programa['cancion_final'], 0, 0, 'L');
    $yAfterSong2 = $pdf->GetY() + ROW_H;
    $drawRol('Oración:', $rolesAsignados['Oración final'] ?? '', $yRow);
    $pdf->SetY(max($yAfterSong2, $pdf->GetY()));
    setTxt($pdf, $colorBlack);
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

    fntBold($pdf, $FNT_BOLD, FS_MONTH);
    setTxt($pdf, [0,0,0]);
    $pdf->Cell(0, 10, $MESES[$mesEncabezado] . ' ' . $anioEncabezado, 0, 1, 'R');
    $pdf->Ln(1);

    // Sub-encabezado: Congregación (izq, negrita) | "Programa para la reunión entre semana" (der)
    fntBold($pdf, $FNT_BOLD, FS_HEADER);
    $pdf->Cell(round(PDF_INNER_W * 0.45), 6, $config['nombre_congregacion'], 0, 0, 'L');
    fntReg($pdf, $FNT_REG, FS_HEADER);
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
