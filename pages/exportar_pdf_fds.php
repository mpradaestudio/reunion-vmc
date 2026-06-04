<?php
/**
 * PDF Reunión Fin de Semana
 * Formato: tabla mes completo con todas las semanas disponibles
 * 2 secciones: DISCURSO PÚBLICO + ESTUDIO DE LA ATALAYA
 */

require_once __DIR__ . '/../config/config.php';

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Error: TCPDF no instalado. Ejecuta composer install.');
}
require_once __DIR__ . '/../vendor/autoload.php';

/* ── Parámetros ─────────────────────────────────────────────── */
$id  = (int)($_GET['id']  ?? 0);   // una semana específica
$mes = (int)($_GET['mes'] ?? 0);   // o un mes completo
$anio = (int)($_GET['anio'] ?? date('Y'));

$config = getConfiguracion();

/* ── Datos ──────────────────────────────────────────────────── */
if ($id) {
    $semanas = fetchAll("SELECT * FROM programas_fds WHERE id = ? ORDER BY fecha_inicio", [$id]);
} elseif ($mes) {
    $semanas = fetchAll("
        SELECT * FROM programas_fds
        WHERE MONTH(fecha_inicio)=? AND YEAR(fecha_inicio)=?
        ORDER BY fecha_inicio
    ", [$mes, $anio]);
} else {
    die('Especifica ?id= o ?mes=&anio=');
}

if (empty($semanas)) {
    die('No hay semanas para exportar.');
}

// Obtener asignaciones de cada semana
foreach ($semanas as &$s) {
    $asigs = fetchAll("
        SELECT a.rol, a.persona_id, a.nombre_libre,
               CONCAT(p.nombre,' ',p.apellido) AS nombre_completo
        FROM asignaciones_fds a
        LEFT JOIN personas p ON p.id = a.persona_id
        WHERE a.programa_fds_id = ?
    ", [$s['id']]);
    $s['asig'] = [];
    foreach ($asigs as $a) {
        $nombre = $a['persona_id']
            ? ($a['nombre_completo'] ?? '')
            : ($a['nombre_libre']    ?? '');
        $s['asig'][$a['rol']] = $nombre;
    }
}
unset($s);

/* ── Constantes diseño ──────────────────────────────────────── */
define('FDS_ML',  14);
define('FDS_MR',  14);
define('FDS_MT',  12);
define('FDS_MB',  12);
define('FDS_PW', 215.9);
define('FDS_IW', FDS_PW - FDS_ML - FDS_MR);

// Color primario del proyecto
$C_PRIMARY = [74, 109, 167];
$C_WHITE   = [255, 255, 255];
$C_BLACK   = [0, 0, 0];
$C_GRAY    = [100, 100, 100];
$C_HEADER_BG = [230, 237, 248];   // azul muy claro para filas de cabecera de tabla
$C_ROW_ALT   = [245, 248, 252];   // alternado filas
$C_BORDER    = [200, 213, 232];

/* ── Fuentes ────────────────────────────────────────────────── */
$fontsDir = realpath(__DIR__ . '/../assets/fonts');

class FDS_PDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new FDS_PDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

function regFont(FDS_PDF $pdf, string $dir, string $file): string {
    if ($dir && file_exists($dir . DIRECTORY_SEPARATOR . $file)) {
        try {
            $n = $pdf->addTTFfont($dir . DIRECTORY_SEPARATOR . $file, 'TrueTypeUnicode', '', 32);
            if ($n) return $n;
        } catch (Exception $e) {}
    }
    return 'helvetica';
}

$FNT_REG  = regFont($pdf, $fontsDir, 'GoogleSans-Regular.ttf');
$FNT_BOLD = regFont($pdf, $fontsDir, 'GoogleSans-Bold.ttf');
$FNT_ICON = 'dejavusans';

$pdf->SetCreator('Programador VMC');
$pdf->SetAuthor($config['nombre_congregacion']);
$pdf->SetTitle('Reunión Fin de Semana');
$pdf->SetMargins(FDS_ML, FDS_MT, FDS_MR);
$pdf->SetAutoPageBreak(true, FDS_MB);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$styleBold = ($FNT_BOLD === 'helvetica') ? 'B' : '';
$styleReg  = '';

/* ── Meses en español ───────────────────────────────────────── */
$MESES = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',
          7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];

/* ── Helper: draw colored header cell ──────────────────────── */
function hdCell(FDS_PDF $pdf, float $w, float $h, string $txt,
                array $bg, array $fg, string $fnt, string $sty, float $sz,
                string $align = 'C'): void {
    $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
    $pdf->SetTextColor($fg[0], $fg[1], $fg[2]);
    $pdf->SetFont($fnt, $sty, $sz);
    $pdf->Cell($w, $h, $txt, 1, 0, $align, true);
}

function dataCell(FDS_PDF $pdf, float $w, float $h, string $txt,
                  string $fnt, string $sty, float $sz, bool $fill = false,
                  array $fillColor = [255,255,255], string $align = 'L',
                  int $border = 1): void {
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont($fnt, $sty, $sz);
    $pdf->Cell($w, $h, $txt, $border, 0, $align, $fill);
}

/* ═══════════════════════════════════════════════════════════════
   RENDER
═══════════════════════════════════════════════════════════════ */
$pdf->AddPage();

// Determinar mes/año del primer programa
$fi0 = new DateTime($semanas[0]['fecha_inicio']);
$mesLabel  = $MESES[(int)$fi0->format('n')];
$anioLabel = $fi0->format('Y');

// ── Título del documento ─────────────────────────────────────
$pdf->SetFont($FNT_BOLD, $styleBold, 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, $config['nombre_congregacion'], 0, 1, 'C');

$pdf->SetFont($FNT_BOLD, $styleBold, 14);
$pdf->Cell(0, 7, 'REUNIÓN FIN DE SEMANA — ' . $mesLabel . ' ' . $anioLabel, 0, 1, 'C');
$pdf->Ln(5);

/* ─────────────────────────────────────────────────────────────
   SECCIÓN 1: DISCURSO PÚBLICO
───────────────────────────────────────────────────────────── */
$pdf->SetFont($FNT_BOLD, $styleBold, 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 7, 'DISCURSO PÚBLICO', 0, 1, 'C');
$pdf->Ln(2);

// Anchos columnas: Fecha | Tema | Canción | Orador
$wFecha  = 15;
$wCancion = 18;
$wOrador = 52;
$wTema   = FDS_IW - $wFecha - $wCancion - $wOrador;
$rowH = 7;

// Cabecera tabla DP
$pdf->SetDrawColor($C_BORDER[0], $C_BORDER[1], $C_BORDER[2]);
$pdf->SetLineWidth(0.3);

hdCell($pdf, $wFecha,   $rowH, "\xe2\x98\x85",   $C_PRIMARY, $C_WHITE, $FNT_ICON, '', 10, 'C'); // ★ icono
hdCell($pdf, $wTema,    $rowH, 'TEMA',            $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 9, 'C');
hdCell($pdf, $wCancion, $rowH, "\xe2\x99\xaa",    $C_PRIMARY, $C_WHITE, $FNT_ICON, '', 10, 'C'); // ♪
hdCell($pdf, $wOrador,  $rowH, 'ORADOR',          $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 9, 'C');
$pdf->Ln();

// Filas datos DP
foreach ($semanas as $idx => $s) {
    $fi  = new DateTime($s['fecha_inicio']);
    $dia = (int)$fi->format('d');

    $altBg = ($idx % 2 === 1) ? $C_ROW_ALT : [255,255,255];

    dataCell($pdf, $wFecha,   $rowH, (string)$dia,                  $FNT_BOLD, $styleBold, 9,  true, $C_HEADER_BG, 'C');
    dataCell($pdf, $wTema,    $rowH, $s['dp_tema']    ?? '',         $FNT_REG,  $styleReg,  8.5, true, $altBg, 'L');
    dataCell($pdf, $wCancion, $rowH, $s['dp_cancion'] ?? '',         $FNT_REG,  $styleReg,  8.5, true, $altBg, 'C');
    dataCell($pdf, $wOrador,  $rowH, $s['asig']['DP_Orador']    ?? '', $FNT_REG, $styleReg, 8.5, true, $altBg, 'L');
    $pdf->Ln();
}

$pdf->Ln(8);

/* ─────────────────────────────────────────────────────────────
   SECCIÓN 2: ESTUDIO DE LA ATALAYA
───────────────────────────────────────────────────────────── */
$pdf->SetFont($FNT_BOLD, $styleBold, 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 7, 'ESTUDIO DE LA ATALAYA', 0, 1, 'C');
$pdf->Ln(2);

// Anchos columnas EA: Fecha | Presidente | Lector | Oración | Hospitalidad
$wF   = 15;
$wP   = round((FDS_IW - $wF) * 0.25);
$wL   = round((FDS_IW - $wF) * 0.22);
$wO   = round((FDS_IW - $wF) * 0.22);
$wH   = FDS_IW - $wF - $wP - $wL - $wO;

// Cabecera EA
hdCell($pdf, $wF, $rowH, "\xe2\x98\x85",    $C_PRIMARY, $C_WHITE, $FNT_ICON, '', 10, 'C');
hdCell($pdf, $wP, $rowH, 'PRESIDENTE*',     $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 8, 'C');
hdCell($pdf, $wL, $rowH, 'LECTOR#',         $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 8, 'C');
hdCell($pdf, $wO, $rowH, 'ORACIÓN',         $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 8, 'C');
hdCell($pdf, $wH, $rowH, 'HOSPITALIDAD',    $C_PRIMARY, $C_WHITE, $FNT_BOLD, $styleBold, 8, 'C');
$pdf->Ln();

// Filas datos EA
foreach ($semanas as $idx => $s) {
    $fi  = new DateTime($s['fecha_inicio']);
    $dia = (int)$fi->format('d');
    $altBg = ($idx % 2 === 1) ? $C_ROW_ALT : [255,255,255];

    // Mapear roles → columna
    $pres  = $s['asig']['EA_Conductor']    ?? '';   // conductor = presidente en este formato
    $lect  = $s['asig']['EA_Lector']       ?? '';
    $orac  = $s['asig']['EA_Oracion']      ?? '';
    $hosp  = $s['asig']['EA_Hospitalidad'] ?? '';

    dataCell($pdf, $wF, $rowH, (string)$dia, $FNT_BOLD, $styleBold, 9,   true, $C_HEADER_BG, 'C');
    dataCell($pdf, $wP, $rowH, $pres,        $FNT_REG,  $styleReg,  8.5, true, $altBg, 'L');
    dataCell($pdf, $wL, $rowH, $lect,        $FNT_REG,  $styleReg,  8.5, true, $altBg, 'L');
    dataCell($pdf, $wO, $rowH, $orac,        $FNT_REG,  $styleReg,  8.5, true, $altBg, 'L');
    dataCell($pdf, $wH, $rowH, $hosp,        $FNT_REG,  $styleReg,  8.5, true, $altBg, 'L');
    $pdf->Ln();
}

// Notas al pie de tabla
$pdf->Ln(2);
$pdf->SetFont($FNT_REG, $styleReg, 7.5);
$pdf->SetTextColor($C_GRAY[0], $C_GRAY[1], $C_GRAY[2]);
$pdf->Cell(0, 5, '* Conduce el Estudio de La Atalaya    # Lee el párrafo', 0, 1, 'L');

/* ── Salida ─────────────────────────────────────────────────── */
$nombreArchivo = 'FDS_' . $mesLabel . '_' . $anioLabel . '.pdf';
$pdf->Output($nombreArchivo, 'I');
