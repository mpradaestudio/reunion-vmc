<?php
/**
 * API para Web Scraping de jw.org
 * Extrae información de las Guías de Actividades (Vida y Ministerio Cristianos)
 *
 * Estrategia de parsing:
 * - Solo se consideran ASIGNABLES las partes NUMERADAS (1., 2., 3., ...).
 * - La semana, la lectura bíblica y las canciones son solo informativas.
 * - Reglas de asignación:
 *     * SEAMOS MEJORES MAESTROS  -> 2 personas (Estudiante / Ayudante)
 *     * "Estudio bíblico de la congregación" -> 2 personas (Conductor / Lector)
 *     * Resto de partes numeradas -> 1 persona (Asignado)
 */

require_once __DIR__ . '/../config/config.php';

class JWOrgScraper {
    private $baseUrl = 'https://www.jw.org/es/biblioteca/guia-actividades-reunion-testigos-jehova/';
    private $pdo;

    // Nombres de las tres secciones tal como aparecen en jw.org
    private $seccionesConocidas = [
        'TESOROS DE LA BIBLIA',
        'SEAMOS MEJORES MAESTROS',
        'NUESTRA VIDA CRISTIANA',
    ];

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    /* ============================================================
     *  FLUJO PRINCIPAL
     * ============================================================ */

    public function extraerProgramas($periodo) {
        $url = $this->construirUrl($periodo);

        if (!$url) {
            return ['success' => false, 'message' => 'Período no válido'];
        }

        $html = $this->obtenerContenidoWeb($url);
        if (!$html) {
            return ['success' => false, 'message' => 'No se pudo conectar a jw.org. Verifica tu conexión a internet.'];
        }

        $urlsSemanas = $this->extraerUrlsSemanas($html);
        if (empty($urlsSemanas)) {
            return ['success' => false, 'message' => 'No se encontraron semanas en la página del período. La estructura del sitio pudo cambiar.'];
        }

        $programasExtraidos = 0;
        foreach ($urlsSemanas as $urlSemana) {
            if ($this->extraerProgramaSemanal($urlSemana)) {
                $programasExtraidos++;
            }
        }

        $this->registrarHistorial($url, $programasExtraidos, 'exitoso');

        return [
            'success' => true,
            'programas_extraidos' => $programasExtraidos,
            'message' => "Se extrajeron $programasExtraidos programas correctamente",
        ];
    }

    private function construirUrl($periodo) {
        $periodos = [
            'mayo-junio-2026'         => 'mayo-junio-2026-mwb',
            'julio-agosto-2026'       => 'julio-agosto-2026-mwb',
            'septiembre-octubre-2026' => 'septiembre-octubre-2026-mwb',
            'noviembre-diciembre-2026'=> 'noviembre-diciembre-2026-mwb',
        ];
        $periodo = strtolower($periodo);
        return isset($periodos[$periodo]) ? $this->baseUrl . $periodos[$periodo] . '/' : null;
    }

    /* ============================================================
     *  DESCARGA WEB (cURL con timeout y manejo de SSL)
     * ============================================================ */

    private function obtenerContenidoWeb($url) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,  // XAMPP normalmente no trae certificados CA
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING       => '',     // aceptar gzip/deflate
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.9',
                ],
            ]);
            $html = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($html === false || $html === '') {
                error_log("cURL error al obtener $url: $err");
                return false;
            }
            return $html;
        }

        // Respaldo: file_get_contents con timeout
        $contexto = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 30,
                'header'  => "User-Agent: Mozilla/5.0\r\nAccept-Language: es-ES,es;q=0.9\r\n",
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);
        $html = @file_get_contents($url, false, $contexto);
        return $html ?: false;
    }

    private function extraerUrlsSemanas($html) {
        $urls = [];
        preg_match_all('/href="([^"]*Vida-y-Ministerio-Cristianos[^"]*)"/i', $html, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                if (strpos($url, 'http') !== 0) {
                    $url = 'https://www.jw.org' . $url;
                }
                // Limpiar fragmentos (#...) y parámetros
                $url = preg_replace('/#.*$/', '', $url);
                if (!in_array($url, $urls)) {
                    $urls[] = $url;
                }
            }
        }
        return $urls;
    }

    /* ============================================================
     *  PARSING DE UNA SEMANA
     * ============================================================ */

    private function extraerProgramaSemanal($url) {
        $html = $this->obtenerContenidoWeb($url);
        if (!$html) {
            return false;
        }

        $datos = $this->parsearSemana($html, $url);
        if (!$datos || !$datos['fecha_inicio']) {
            return false;
        }

        return $this->guardarPrograma($datos);
    }

    /**
     * Parsea el HTML de una semana y devuelve toda la información estructurada.
     * Es público para poder reutilizarlo desde el diagnóstico.
     */
    public function parsearSemana($html, $url = '') {
        $titulo      = $this->extraerTitulo($html);
        $fechas      = $this->extraerFechas($url, $titulo);
        $referencia  = $this->extraerReferencia($titulo);
        $canciones   = $this->extraerCanciones($html);
        $partes      = $this->extraerPartesNumeradas($html);

        // Construir un título de semana legible a partir de las fechas
        $tituloSemana = $titulo;
        if ($fechas) {
            $tituloSemana = $this->construirTituloSemana($fechas);
        }

        return [
            'titulo'        => $tituloSemana,
            'titulo_h1'     => $titulo,
            'fecha_inicio'  => $fechas['inicio'] ?? null,
            'fecha_fin'     => $fechas['fin'] ?? null,
            'referencia'    => $referencia,
            'canciones'     => $canciones,
            'secciones'     => $partes,
            'url'           => $url,
        ];
    }

    private function extraerTitulo($html) {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            $t = html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(preg_replace('/\s+/u', ' ', $t));
        }
        return '';
    }

    /**
     * Obtiene las fechas. Primero intenta desde la URL (muy fiable),
     * luego desde el título como respaldo.
     * URL ejemplo: ...Vida-y-Ministerio-Cristianos-6-a-12-de-julio-de-2026/
     */
    private function extraerFechas($url, $titulo) {
        // 1) Desde la URL
        if ($url && preg_match('/(\d{1,2})-a-(\d{1,2})-de-([a-zñáéíóú]+)-de-(\d{4})/iu', $url, $m)) {
            $mes = $this->convertirMesTextoANumero($m[3]);
            return [
                'inicio' => sprintf('%04d-%02d-%02d', (int)$m[4], $mes, (int)$m[1]),
                'fin'    => sprintf('%04d-%02d-%02d', (int)$m[4], $mes, (int)$m[2]),
                'dia_inicio' => (int)$m[1],
                'dia_fin'    => (int)$m[2],
                'mes'        => $mes,
                'anio'       => (int)$m[4],
            ];
        }

        // 2) Desde el título: "6-12 de julio ..." o "6 a 12 de julio ..."
        if (preg_match('/(\d{1,2})\s*(?:-|a)\s*(\d{1,2})\s+de\s+([a-zñáéíóú]+)/iu', $titulo, $m)) {
            $mes  = $this->convertirMesTextoANumero($m[3]);
            $anio = 2026;
            if (preg_match('/(20\d{2})/', $titulo, $ma)) {
                $anio = (int)$ma[1];
            }
            return [
                'inicio' => sprintf('%04d-%02d-%02d', $anio, $mes, (int)$m[1]),
                'fin'    => sprintf('%04d-%02d-%02d', $anio, $mes, (int)$m[2]),
                'dia_inicio' => (int)$m[1],
                'dia_fin'    => (int)$m[2],
                'mes'        => $mes,
                'anio'       => $anio,
            ];
        }

        return null;
    }

    private function construirTituloSemana($fechas) {
        $meses = [
            1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
            7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
        ];
        $mes = $meses[$fechas['mes']] ?? '';
        return $fechas['dia_inicio'] . '-' . $fechas['dia_fin'] . ' de ' . $mes;
    }

    /**
     * Extrae la referencia bíblica (lectura asignada) del título.
     * Ej: "6-12 de julio | Jeremías 13-15" -> "Jeremías 13-15"
     */
    private function extraerReferencia($titulo) {
        if (strpos($titulo, '|') !== false) {
            $partes = explode('|', $titulo);
            return trim(end($partes));
        }
        // Respaldo: buscar patrón Libro + capítulos al final
        if (preg_match('/([1-3]?\s?[A-ZÁÉÍÓÚ][a-zñáéíóú]+)\s+\d+(?:[:\-]\d+)?(?:-\d+)?\s*$/u', $titulo, $m)) {
            return trim($m[0]);
        }
        return '';
    }

    /**
     * Extrae los 3 números de canción (inicial, media, final).
     */
    private function extraerCanciones($html) {
        $texto = $this->htmlATextoPlano($html);
        preg_match_all('/Canci[oó]n\s+(\d{1,3})/iu', $texto, $matches);

        $nums = $matches[1] ?? [];
        return [
            'inicial' => isset($nums[0]) ? (int)$nums[0] : null,
            'media'   => isset($nums[1]) ? (int)$nums[1] : null,
            'final'   => isset($nums[2]) ? (int)$nums[2] : null,
        ];
    }

    /* ============================================================
     *  EXTRACCIÓN DE PARTES NUMERADAS (el corazón del parser)
     * ============================================================ */

    /**
     * Recorre el contenido línea por línea, detecta los encabezados de
     * sección y, dentro de cada sección, captura solo las partes que
     * empiezan con un número (1., 2., ...).
     */
    public function extraerPartesNumeradas($html) {
        $lineas = $this->htmlALineas($html);

        $partes        = [];
        $seccionActual = null;
        $orden         = 0;

        foreach ($lineas as $linea) {
            // ¿Es un encabezado de sección?
            $seccionDetectada = $this->detectarSeccion($linea);
            if ($seccionDetectada !== null) {
                $seccionActual = $seccionDetectada;
                continue;
            }

            // Solo capturamos partes dentro de una sección reconocida
            if ($seccionActual === null) {
                continue;
            }

            // ¿La línea empieza con "N." (parte numerada)?
            if (preg_match('/^(\d{1,2})\.\s*(\S.*)$/u', $linea, $m)) {
                $resto = trim($m[2]);

                // Duración: "(10 mins.)" / "(4 min.)"
                $duracion = null;
                if (preg_match('/\((\d{1,3})\s*mins?\.?\)/iu', $resto, $md)) {
                    $duracion = (int)$md[1];
                }

                // El título es el texto antes del primer paréntesis
                $titulo = $resto;
                $pos = mb_strpos($resto, '(', 0, 'UTF-8');
                if ($pos !== false) {
                    $titulo = mb_substr($resto, 0, $pos, 'UTF-8');
                }
                $titulo = trim($titulo, " .\t\r\n");

                if ($titulo === '') {
                    continue;
                }

                $tipoAsignacion = $this->determinarTipoAsignacion($seccionActual, $titulo);

                $partes[] = [
                    'orden'           => $orden++,
                    'seccion'         => $seccionActual,
                    'titulo'          => $titulo,
                    'duracion'        => $duracion,
                    'tipo_asignacion' => $tipoAsignacion,
                ];
            }
        }

        return $partes;
    }

    /**
     * Devuelve el tipo de asignación según las reglas del usuario.
     */
    private function determinarTipoAsignacion($seccion, $titulo) {
        // "Estudio bíblico de la congregación" -> Conductor / Lector (2 personas)
        if (mb_stripos($titulo, 'Estudio b', 0, 'UTF-8') !== false &&
            mb_stripos($titulo, 'congrega', 0, 'UTF-8') !== false) {
            return 'Conductor/Lector';
        }

        // Toda la sección SEAMOS MEJORES MAESTROS -> Estudiante / Ayudante (2 personas)
        if ($seccion === 'SEAMOS MEJORES MAESTROS') {
            return 'Estudiante/Ayudante';
        }

        // Resto -> 1 persona
        return 'Asignado';
    }

    /**
     * Detecta si una línea corresponde a un encabezado de sección.
     */
    private function detectarSeccion($linea) {
        $upper = mb_strtoupper($linea, 'UTF-8');
        // Quitar acentos para comparar de forma tolerante
        $upperSinAcentos = strtr($upper, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U']);

        foreach ($this->seccionesConocidas as $seccion) {
            $clave = strtr($seccion, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U']);
            // La línea debe contener el nombre de la sección y ser relativamente corta
            if (mb_strpos($upperSinAcentos, $clave) !== false && mb_strlen($linea, 'UTF-8') <= 45) {
                return $seccion;
            }
        }
        return null;
    }

    /* ============================================================
     *  UTILIDADES DE LIMPIEZA HTML
     * ============================================================ */

    /**
     * Convierte HTML en un arreglo de líneas limpias, insertando saltos
     * de línea en las etiquetas de bloque para preservar la estructura.
     */
    private function htmlALineas($html) {
        // Quitar scripts y estilos
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);

        // Insertar saltos de línea en elementos de bloque
        $html = preg_replace('#<(br|/p|/div|/li|/h1|/h2|/h3|/h4|/h5|/h6|/tr|/section|/article)\b[^>]*>#i', "\n", $html);

        $texto = strip_tags($html);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lineasRaw = preg_split('/\n+/', $texto);
        $lineas = [];
        foreach ($lineasRaw as $linea) {
            $linea = preg_replace('/\s+/u', ' ', $linea);
            $linea = trim($linea);
            if ($linea !== '') {
                $lineas[] = $linea;
            }
        }
        return $lineas;
    }

    private function htmlATextoPlano($html) {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);
        $texto = strip_tags($html);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/u', ' ', $texto);
    }

    private function convertirMesTextoANumero($mes) {
        $meses = [
            'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
            'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,
            'noviembre'=>11,'diciembre'=>12
        ];
        $mes = mb_strtolower(trim($mes), 'UTF-8');
        return $meses[$mes] ?? 1;
    }

    /* ============================================================
     *  PERSISTENCIA
     * ============================================================ */

    private function guardarPrograma($datos) {
        try {
            $existe = fetchOne(
                "SELECT id FROM programas_semanales WHERE fecha_inicio = ?",
                [$datos['fecha_inicio']]
            );

            if ($existe) {
                $stmt = $this->pdo->prepare("
                    UPDATE programas_semanales SET
                        fecha_fin = ?, titulo_semana = ?, referencia_biblica = ?,
                        cancion_inicial = ?, cancion_media = ?, cancion_final = ?,
                        contenido_json = ?, url_fuente = ?
                    WHERE fecha_inicio = ?
                ");
                $stmt->execute([
                    $datos['fecha_fin'], $datos['titulo'], $datos['referencia'],
                    $datos['canciones']['inicial'] ?? null,
                    $datos['canciones']['media'] ?? null,
                    $datos['canciones']['final'] ?? null,
                    json_encode($datos['secciones'], JSON_UNESCAPED_UNICODE),
                    $datos['url'], $datos['fecha_inicio'],
                ]);
                $programaId = $existe['id'];
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO programas_semanales (
                        fecha_inicio, fecha_fin, titulo_semana, referencia_biblica,
                        cancion_inicial, cancion_media, cancion_final, contenido_json, url_fuente
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $datos['fecha_inicio'], $datos['fecha_fin'], $datos['titulo'], $datos['referencia'],
                    $datos['canciones']['inicial'] ?? null,
                    $datos['canciones']['media'] ?? null,
                    $datos['canciones']['final'] ?? null,
                    json_encode($datos['secciones'], JSON_UNESCAPED_UNICODE),
                    $datos['url'],
                ]);
                $programaId = $this->pdo->lastInsertId();
            }

            // Regenerar secciones, conservando asignaciones existentes cuando sea posible
            $this->pdo->prepare("DELETE FROM programa_secciones WHERE programa_id = ?")
                      ->execute([$programaId]);

            $stmtSeccion = $this->pdo->prepare("
                INSERT INTO programa_secciones (programa_id, orden, seccion, titulo, duracion, tipo_asignacion)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($datos['secciones'] as $s) {
                $stmtSeccion->execute([
                    $programaId, $s['orden'], $s['seccion'], $s['titulo'], $s['duracion'], $s['tipo_asignacion'],
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error al guardar programa: " . $e->getMessage());
            return false;
        }
    }

    private function registrarHistorial($url, $numProgramas, $estado, $mensaje = '') {
        try {
            $this->pdo->prepare("
                INSERT INTO historial_scraping (url_procesada, num_programas_extraidos, estado, mensaje)
                VALUES (?, ?, ?, ?)
            ")->execute([$url, $numProgramas, $estado, $mensaje]);
        } catch (Exception $e) {
            error_log("Error al registrar historial: " . $e->getMessage());
        }
    }
}

/* ================================================================
 *  ENRUTAMIENTO DE LA PETICIÓN
 *  Solo se ejecuta cuando se accede DIRECTAMENTE a este archivo,
 *  no cuando se incluye desde otro (p. ej. test_scraper.php).
 * ================================================================ */

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'scrape') {
            $periodo = $_POST['periodo'] ?? '';
            if (empty($periodo)) {
                jsonResponse(['success' => false, 'message' => 'Debe especificar un período']);
            }
            $scraper = new JWOrgScraper();
            jsonResponse($scraper->extraerProgramas($periodo));
        }

        jsonResponse(['success' => false, 'message' => 'Acción no válida']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
    }
}
