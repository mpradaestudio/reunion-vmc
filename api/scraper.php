<?php
/**
 * API para Web Scraping de jw.org
 * Extrae información de las Guías de Actividades
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

class JWOrgScraper {
    private $baseUrl = 'https://www.jw.org/es/biblioteca/guia-actividades-reunion-testigos-jehova/';
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Extraer programas de un período específico
     */
    public function extraerProgramas($periodo) {
        try {
            // Construir URL según el período
            $url = $this->construirUrl($periodo);
            
            if (!$url) {
                return ['success' => false, 'message' => 'Período no válido'];
            }
            
            // Obtener la página principal del período
            $html = $this->obtenerContenidoWeb($url);
            
            if (!$html) {
                return ['success' => false, 'message' => 'No se pudo conectar a jw.org'];
            }
            
            // Extraer URLs de las semanas individuales
            $urlsSemanas = $this->extraerUrlsSemanas($html);
            
            $programasExtraidos = 0;
            
            foreach ($urlsSemanas as $urlSemana) {
                $resultado = $this->extraerProgramaSemanal($urlSemana);
                if ($resultado) {
                    $programasExtraidos++;
                }
            }
            
            // Registrar en historial
            $this->registrarHistorial($url, $programasExtraidos, 'exitoso');
            
            return [
                'success' => true, 
                'programas_extraidos' => $programasExtraidos,
                'message' => "Se extrajeron $programasExtraidos programas correctamente"
            ];
            
        } catch (Exception $e) {
            $this->registrarHistorial($url ?? '', 0, 'error', $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Construir URL según el período
     */
    private function construirUrl($periodo) {
        $periodos = [
            'mayo-junio-2026' => 'mayo-junio-2026-mwb',
            'julio-agosto-2026' => 'julio-agosto-2026-mwb',
            'septiembre-octubre-2026' => 'septiembre-octubre-2026-mwb',
            'noviembre-diciembre-2026' => 'noviembre-diciembre-2026-mwb',
        ];
        
        $periodo = strtolower($periodo);
        
        if (isset($periodos[$periodo])) {
            return $this->baseUrl . $periodos[$periodo] . '/';
        }
        
        return null;
    }
    
    /**
     * Obtener contenido HTML de una URL
     */
    private function obtenerContenidoWeb($url) {
        $opciones = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n" .
                           "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                           "Accept-Language: es-ES,es;q=0.9\r\n"
            ]
        ];
        
        $contexto = stream_context_create($opciones);
        $html = @file_get_contents($url, false, $contexto);
        
        return $html;
    }
    
    /**
     * Extraer URLs de las semanas desde la página principal del período
     */
    private function extraerUrlsSemanas($html) {
        $urls = [];
        
        // Patrón para encontrar enlaces a programas semanales
        preg_match_all('/href="([^"]*Vida-y-Ministerio-Cristianos[^"]*)"/', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                // Convertir URL relativa a absoluta
                if (strpos($url, 'http') !== 0) {
                    $url = 'https://www.jw.org' . $url;
                }
                
                // Evitar duplicados
                if (!in_array($url, $urls)) {
                    $urls[] = $url;
                }
            }
        }
        
        return $urls;
    }
    
    /**
     * Extraer información de un programa semanal específico
     */
    private function extraerProgramaSemanal($url) {
        try {
            $html = $this->obtenerContenidoWeb($url);
            
            if (!$html) {
                return false;
            }
            
            // Extraer título y fecha
            $titulo = $this->extraerTitulo($html);
            $fechas = $this->extraerFechas($titulo);
            $referencia = $this->extraerReferenciaBiblica($titulo);
            
            if (!$fechas) {
                return false;
            }
            
            // Extraer canciones
            $canciones = $this->extraerCanciones($html);
            
            // Extraer secciones y partes
            $secciones = $this->extraerSecciones($html);
            
            // Guardar en base de datos
            return $this->guardarPrograma([
                'titulo' => $titulo,
                'fecha_inicio' => $fechas['inicio'],
                'fecha_fin' => $fechas['fin'],
                'referencia' => $referencia,
                'canciones' => $canciones,
                'secciones' => $secciones,
                'url' => $url
            ]);
            
        } catch (Exception $e) {
            error_log("Error al extraer programa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extraer título del programa
     */
    private function extraerTitulo($html) {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return strip_tags($match[1]);
        }
        return '';
    }
    
    /**
     * Extraer fechas del título
     */
    private function extraerFechas($titulo) {
        // Patrón: "1-7 DE JUNIO" o similar
        if (preg_match('/(\d+)-(\d+)\s+DE\s+(\w+)/i', $titulo, $match)) {
            $diaInicio = $match[1];
            $diaFin = $match[2];
            $mes = $this->convertirMesTextoANumero($match[3]);
            
            // Extraer año del título si está presente
            $anio = 2026; // Por defecto
            if (preg_match('/202\d/', $titulo, $matchAnio)) {
                $anio = $matchAnio[0];
            }
            
            return [
                'inicio' => sprintf('%04d-%02d-%02d', $anio, $mes, $diaInicio),
                'fin' => sprintf('%04d-%02d-%02d', $anio, $mes, $diaFin)
            ];
        }
        
        return null;
    }
    
    /**
     * Extraer referencia bíblica del título
     */
    private function extraerReferenciaBiblica($titulo) {
        // Patrón: "| JEREMÍAS 1-3" o similar
        if (preg_match('/\|\s*([A-ZÁÉÍÓÚ\s]+\d+[:-]\d+)/i', $titulo, $match)) {
            return trim($match[1]);
        }
        return '';
    }
    
    /**
     * Extraer números de canciones
     */
    private function extraerCanciones($html) {
        $canciones = [];
        
        // Buscar "Canción XX"
        preg_match_all('/Canción\s+(\d+)/i', $html, $matches);
        
        if (!empty($matches[1])) {
            // Primera canción
            $canciones['inicial'] = isset($matches[1][0]) ? (int)$matches[1][0] : null;
            // Canción media
            $canciones['media'] = isset($matches[1][1]) ? (int)$matches[1][1] : null;
            // Canción final
            $canciones['final'] = isset($matches[1][2]) ? (int)$matches[1][2] : null;
        }
        
        return $canciones;
    }
    
    /**
     * Extraer secciones del programa
     */
    private function extraerSecciones($html) {
        $secciones = [];
        $orden = 0;
        
        // Extraer TESOROS DE LA BIBLIA
        $tesoros = $this->extraerSeccionTesoros($html, $orden);
        $secciones = array_merge($secciones, $tesoros);
        $orden += count($tesoros);
        
        // Extraer SEAMOS MEJORES MAESTROS
        $maestros = $this->extraerSeccionMaestros($html, $orden);
        $secciones = array_merge($secciones, $maestros);
        $orden += count($maestros);
        
        // Extraer NUESTRA VIDA CRISTIANA
        $vida = $this->extraerSeccionVida($html, $orden);
        $secciones = array_merge($secciones, $vida);
        
        return $secciones;
    }
    
    /**
     * Extraer sección Tesoros de la Biblia
     */
    private function extraerSeccionTesoros($html, $ordenInicial) {
        $partes = [];
        $orden = $ordenInicial;
        
        // Buscar la sección
        if (preg_match('/TESOROS DE LA BIBLIA(.*?)(?=SEAMOS MEJORES MAESTROS|$)/is', $html, $match)) {
            $contenido = $match[1];
            
            // Extraer partes individuales con viñetas (●)
            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $contenido, $items);
            
            foreach ($items[1] as $item) {
                $textoLimpio = strip_tags($item);
                
                // Extraer duración
                $duracion = null;
                if (preg_match('/\((\d+)\s*min\.?\)/i', $textoLimpio, $matchDur)) {
                    $duracion = (int)$matchDur[1];
                }
                
                // Limpiar texto
                $titulo = preg_replace('/\(\d+\s*min\.?\)/i', '', $textoLimpio);
                $titulo = trim($titulo);
                
                if ($titulo) {
                    $partes[] = [
                        'orden' => $orden++,
                        'seccion' => 'TESOROS DE LA BIBLIA',
                        'titulo' => $titulo,
                        'duracion' => $duracion,
                        'tipo_asignacion' => 'Asignado'
                    ];
                }
            }
        }
        
        return $partes;
    }
    
    /**
     * Extraer sección Seamos Mejores Maestros
     */
    private function extraerSeccionMaestros($html, $ordenInicial) {
        $partes = [];
        $orden = $ordenInicial;
        
        // Buscar la sección
        if (preg_match('/SEAMOS MEJORES MAESTROS(.*?)(?=NUESTRA VIDA CRISTIANA|$)/is', $html, $match)) {
            $contenido = $match[1];
            
            // Extraer partes individuales
            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $contenido, $items);
            
            foreach ($items[1] as $item) {
                $textoLimpio = strip_tags($item);
                
                // Extraer duración
                $duracion = null;
                if (preg_match('/\((\d+)\s*min\.?\)/i', $textoLimpio, $matchDur)) {
                    $duracion = (int)$matchDur[1];
                }
                
                // Limpiar texto
                $titulo = preg_replace('/\(\d+\s*min\.?\)/i', '', $textoLimpio);
                $titulo = trim($titulo);
                
                if ($titulo) {
                    $partes[] = [
                        'orden' => $orden++,
                        'seccion' => 'SEAMOS MEJORES MAESTROS',
                        'titulo' => $titulo,
                        'duracion' => $duracion,
                        'tipo_asignacion' => 'Estudiante/Ayudante'
                    ];
                }
            }
        }
        
        return $partes;
    }
    
    /**
     * Extraer sección Nuestra Vida Cristiana
     */
    private function extraerSeccionVida($html, $ordenInicial) {
        $partes = [];
        $orden = $ordenInicial;
        
        // Buscar la sección
        if (preg_match('/NUESTRA VIDA CRISTIANA(.*?)$/is', $html, $match)) {
            $contenido = $match[1];
            
            // Extraer partes individuales
            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $contenido, $items);
            
            foreach ($items[1] as $item) {
                $textoLimpio = strip_tags($item);
                
                // Extraer duración
                $duracion = null;
                if (preg_match('/\((\d+)\s*min\.?\)/i', $textoLimpio, $matchDur)) {
                    $duracion = (int)$matchDur[1];
                }
                
                // Limpiar texto
                $titulo = preg_replace('/\(\d+\s*min\.?\)/i', '', $textoLimpio);
                $titulo = trim($titulo);
                
                // Determinar tipo de asignación
                $tipoAsignacion = 'Asignado';
                if (stripos($titulo, 'Estudio bíblico') !== false) {
                    $tipoAsignacion = 'Conductor/Lector';
                }
                
                if ($titulo) {
                    $partes[] = [
                        'orden' => $orden++,
                        'seccion' => 'NUESTRA VIDA CRISTIANA',
                        'titulo' => $titulo,
                        'duracion' => $duracion,
                        'tipo_asignacion' => $tipoAsignacion
                    ];
                }
            }
        }
        
        return $partes;
    }
    
    /**
     * Guardar programa en la base de datos
     */
    private function guardarPrograma($datos) {
        try {
            // Verificar si ya existe
            $existe = fetchOne(
                "SELECT id FROM programas_semanales WHERE fecha_inicio = ?",
                [$datos['fecha_inicio']]
            );
            
            if ($existe) {
                // Actualizar
                $stmt = $this->pdo->prepare("
                    UPDATE programas_semanales SET
                        fecha_fin = ?,
                        titulo_semana = ?,
                        referencia_biblica = ?,
                        cancion_inicial = ?,
                        cancion_media = ?,
                        cancion_final = ?,
                        contenido_json = ?,
                        url_fuente = ?
                    WHERE fecha_inicio = ?
                ");
                
                $stmt->execute([
                    $datos['fecha_fin'],
                    $datos['titulo'],
                    $datos['referencia'],
                    $datos['canciones']['inicial'] ?? null,
                    $datos['canciones']['media'] ?? null,
                    $datos['canciones']['final'] ?? null,
                    json_encode($datos['secciones'], JSON_UNESCAPED_UNICODE),
                    $datos['url'],
                    $datos['fecha_inicio']
                ]);
                
                $programaId = $existe['id'];
                
            } else {
                // Insertar nuevo
                $stmt = $this->pdo->prepare("
                    INSERT INTO programas_semanales (
                        fecha_inicio, fecha_fin, titulo_semana, referencia_biblica,
                        cancion_inicial, cancion_media, cancion_final,
                        contenido_json, url_fuente
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $datos['fecha_inicio'],
                    $datos['fecha_fin'],
                    $datos['titulo'],
                    $datos['referencia'],
                    $datos['canciones']['inicial'] ?? null,
                    $datos['canciones']['media'] ?? null,
                    $datos['canciones']['final'] ?? null,
                    json_encode($datos['secciones'], JSON_UNESCAPED_UNICODE),
                    $datos['url']
                ]);
                
                $programaId = $this->pdo->lastInsertId();
            }
            
            // Eliminar secciones anteriores
            $this->pdo->prepare("DELETE FROM programa_secciones WHERE programa_id = ?")
                      ->execute([$programaId]);
            
            // Insertar secciones
            $stmtSeccion = $this->pdo->prepare("
                INSERT INTO programa_secciones (
                    programa_id, orden, seccion, titulo, duracion, tipo_asignacion
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($datos['secciones'] as $seccion) {
                $stmtSeccion->execute([
                    $programaId,
                    $seccion['orden'],
                    $seccion['seccion'],
                    $seccion['titulo'],
                    $seccion['duracion'],
                    $seccion['tipo_asignacion']
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al guardar programa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convertir nombre de mes a número
     */
    private function convertirMesTextoANumero($mes) {
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        
        $mes = strtolower($mes);
        return $meses[$mes] ?? 1;
    }
    
    /**
     * Registrar en historial de scraping
     */
    private function registrarHistorial($url, $numProgramas, $estado, $mensaje = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO historial_scraping (
                    url_procesada, num_programas_extraidos, estado, mensaje
                ) VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$url, $numProgramas, $estado, $mensaje]);
        } catch (Exception $e) {
            error_log("Error al registrar historial: " . $e->getMessage());
        }
    }
}

// Procesar petición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'scrape') {
        $periodo = $_POST['periodo'] ?? '';
        
        if (empty($periodo)) {
            jsonResponse(['success' => false, 'message' => 'Debe especificar un período']);
        }
        
        $scraper = new JWOrgScraper();
        $resultado = $scraper->extraerProgramas($periodo);
        
        jsonResponse($resultado);
    }
    
} else {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}
