<?php
// generar_formato_imprimir_pdf.php - VERSIÓN PARA PAPEL OFICIO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpiar buffer
if (ob_get_length() > 0) {
    ob_clean();
}

// Desactivar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once __DIR__ . '/../../config/database.php';

// Verificar TCPDF
$tcpdf_path = __DIR__ . '/../../tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    die("Error: No se encuentra TCPDF en: $tcpdf_path");
}

// Filtros
$filtros = [
    'municipio' => $_GET['municipio'] ?? '',
    'parroquia' => $_GET['parroquia'] ?? '',
    'sector' => $_GET['sector'] ?? '',
    'caso' => $_GET['caso'] ?? ''
];

try {
    require_once $tcpdf_path;

    // Conexión DB
    $database = new Database();
    $db = $database->getConnection();

    // Consulta
    $sql = "SELECT 
        `nro_procura`,
        `municipio`,
        `parroquia`,
        `sector`,
        `cedula_representante`,
        `nombre_representante`,
        `numero_contacto`,
        `cedula_beneficiario`,
        `nombre_beneficiario`,
        DATE_FORMAT(`fecha_nacimiento`, '%d/%m/%Y') as `fecha_nacimiento`,
        `edad_actual` as `edad`
    FROM `vista_consolidado` 
    WHERE 1=1";

    $params = [];
    
    if (!empty($filtros['municipio'])) {
        $sql .= " AND municipio = ?";
        $params[] = $filtros['municipio'];
    }
    if (!empty($filtros['parroquia'])) {
        $sql .= " AND parroquia = ?";
        $params[] = $filtros['parroquia'];
    }
    if (!empty($filtros['sector'])) {
        $sql .= " AND sector = ?";
        $params[] = $filtros['sector'];
    }
    if (!empty($filtros['caso'])) {
        $sql .= " AND diagnostico LIKE ?";
        $params[] = '%Caso ' . $filtros['caso'] . '%';
    }

    $sql .= " ORDER BY municipio, parroquia, sector, nro_procura";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TAMAÑO OFICIO: 215.9 x 279.4 mm (8.5 x 11 pulgadas)
    // Crear PDF en LANDSCAPE con papel OFICIO
    $pdf = new TCPDF('L', 'mm', array(279.4, 215.9), true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator('Sistema INN');
    $pdf->SetAuthor('Instituto Nacional de Nutrición');
    $pdf->SetTitle('FORMATO PARA IMPRIMIR - TERCERA ENTREGA ALI PRIMERA');
    
    // Márgenes muy pequeños para aprovechar todo el espacio
    $pdf->SetMargins(2, 12, 2);
    $pdf->SetAutoPageBreak(TRUE, 5);
    $pdf->SetFont('helvetica', '', 6);
    
    // Agregar página
    $pdf->AddPage();
    
    // Título principal
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'INSTITUTO NACIONAL DE NUTRICIÓN', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 5, 'FORMATO PARA IMPRIMIR - TERCERA ENTREGA ALI PRIMERA', 0, 1, 'C');
    
    // Información del reporte
    $pdf->SetFont('helvetica', 'I', 6);
    $pdf->Cell(0, 4, 'Generado: ' . date('d/m/Y H:i:s') . ' | Registros: ' . count($registros), 0, 1, 'C');
    
    // Mostrar filtros
    $filtrosTexto = [];
    if (!empty($filtros['municipio'])) $filtrosTexto[] = 'Municipio: ' . $filtros['municipio'];
    if (!empty($filtros['parroquia'])) $filtrosTexto[] = 'Parroquia: ' . $filtros['parroquia'];
    if (!empty($filtros['sector'])) $filtrosTexto[] = 'Sector: ' . $filtros['sector'];
    if (!empty($filtros['caso'])) $filtrosTexto[] = 'Caso: ' . $filtros['caso'];
    
    if (!empty($filtrosTexto)) {
        $pdf->Cell(0, 4, 'Filtros: ' . implode(', ', $filtrosTexto), 0, 1, 'C');
    }
    
    $pdf->Ln(3);
    
    // Calcular ancho de página para distribución de columnas
    $pageWidth = 279.4 - 4; // Ancho total menos márgenes
    
    // Crear tabla con HTML optimizado para OFICIO
    $html = '
    <style>
    /* ESTILOS PARA PAPEL OFICIO - MÁXIMO APROVECHAMIENTO */
    .table-main {
        font-size: 5.5pt;
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }
    .header-cell {
        font-size: 5.2pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        background-color: #e0e0e0;
        border: 0.3px solid #000;
        padding: 1px;
        height: 22px;
        word-wrap: break-word;
        line-height: 1.1;
    }
    .data-cell {
        font-size: 5.5pt;
        text-align: center;
        vertical-align: middle;
        border: 0.3px solid #000;
        padding: 1px;
        height: 16px;
        word-wrap: break-word;
        overflow: hidden;
    }
    .left-align {
        text-align: left;
        padding-left: 2px;
    }
    .empty-cell {
        background-color: #f8f8f8;
    }
    .center-cell {
        text-align: center;
    }
    /* DISTRIBUCIÓN DE ANCHOS PARA 28 COLUMNAS EN OFICIO */
    .col-1 { width: 3.2%; }  /* N° PROCURA */
    .col-2 { width: 4.5%; }  /* MUNICIPIO */
    .col-3 { width: 5.5%; }  /* PARROQUIA */
    .col-4 { width: 4.0%; }  /* SECTOR */
    .col-5 { width: 3.0%; }  /* CLAP */
    .col-6 { width: 3.0%; }  /* COMUNA */
    .col-7 { width: 5.0%; }  /* REP NOMBRE */
    .col-8 { width: 5.5%; }  /* REP APELLIDOS */
    .col-9 { width: 4.0%; }  /* REP CÉDULA */
    .col-10 { width: 5.0%; } /* BEN NOMBRE */
    .col-11 { width: 5.5%; } /* BEN APELLIDO */
    .col-12 { width: 2.8%; } /* GÉNERO */
    .col-13 { width: 4.0%; } /* BEN CÉDULA */
    .col-14 { width: 3.8%; } /* FECHA NAC */
    .col-15 { width: 2.5%; } /* EDAD */
    .col-16 { width: 4.0%; } /* CONTACTO */
    .col-17 { width: 2.8%; } /* Lactante */
    .col-18 { width: 2.8%; } /* Gestante */
    .col-19 { width: 2.8%; } /* CBI */
    .col-20 { width: 2.8%; } /* Peso */
    .col-21 { width: 2.8%; } /* Talla */
    .col-22 { width: 2.8%; } /* CCI */
    .col-23 { width: 4.0%; } /* DIAGNÓSTICO */
    .col-24 { width: 4.5%; } /* FIRMA */
    .col-25 { width: 5.0%; } /* RESPONSABLE */
    .col-26 { width: 5.0%; } /* FRUVERH */
    .col-27 { width: 5.0%; } /* PROTEÍNA */
    .col-28 { width: 5.0%; } /* VÍVERES */
    </style>
    
    <table class="table-main" cellpadding="1" cellspacing="0">
    <thead>
    <tr>';
    
    // ENCABEZADOS CON ANCHOS ESPECÍFICOS
    $headers = [
        'N°<br>PROCURA',
        'MUNICIPIO',
        'PARROQUIA',
        'SECTOR',
        'CLAP',
        'COMUNA',
        'REPRESENTANTE<br>NOMBRE',
        'REPRESENTANTE<br>APELLIDOS',
        'REPRESENTANTE<br>CÉDULA',
        'BENEFICIARIO<br>NOMBRE',
        'BENEFICIARIO<br>APELLIDO',
        'GÉNERO',
        'BENEFICIARIO<br>CÉDULA',
        'FECHA<br>NACIMIENTO',
        'EDAD',
        'TELÉFONO<br>CONTACTO',
        'Lactante',
        'Gestante',
        'CBI<br>(mm)',
        'Peso<br>(Kg)',
        'Talla<br>(cm)',
        'CCI',
        'SITUACIÓN<br>DIAGNÓSTICO',
        'FIRMA<br>BENEFICIARIO',
        'RESPONSABLE<br>ENTREGA',
        'ÚLTIMA<br>FRUTAS/VEG.',
        'ÚLTIMA<br>PROTEÍNA',
        'ÚLTIMA<br>VÍVERES'
    ];
    
    for ($i = 0; $i < count($headers); $i++) {
        $colClass = 'col-' . ($i + 1);
        $html .= '<th class="header-cell ' . $colClass . '">' . $headers[$i] . '</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    // FUNCIÓN PARA PROCESAR NOMBRES
    function procesarNombreCompleto($nombre_completo) {
        $nombre_completo = trim($nombre_completo);
        if (empty($nombre_completo)) {
            return ['nombre' => '', 'apellido' => ''];
        }
        
        $palabras = explode(' ', $nombre_completo);
        if (count($palabras) == 1) {
            return ['nombre' => $palabras[0], 'apellido' => ''];
        }
        
        // Tomar primera palabra como nombre
        $nombre = $palabras[0];
        
        // El resto como apellido (limitado a 2 palabras máximo)
        $apellido_parts = array_slice($palabras, 1, 2);
        $apellido = implode(' ', $apellido_parts);
        
        return ['nombre' => $nombre, 'apellido' => $apellido];
    }
    
    // FUNCIÓN PARA ACORTAR TEXTO
    function acortarTexto($texto, $maximo = 15) {
        if (strlen($texto) <= $maximo) {
            return $texto;
        }
        return substr($texto, 0, $maximo - 3) . '...';
    }
    
    // FUNCIÓN PARA FORMATEAR TELÉFONO
    function formatearTelefono($telefono) {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($telefono) == 11) { // 0414-1234567
            return substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        } elseif (strlen($telefono) == 10) { // 0412-123456
            return substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        } elseif (strlen($telefono) == 7) { // 123-4567
            return substr($telefono, 0, 3) . '-' . substr($telefono, 3);
        }
        return $telefono;
    }
    
    // Agregar filas de datos
    if (count($registros) > 0) {
        foreach($registros as $row) {
            $html .= '<tr>';
            
            // 1. N° DE PROCURA
            $html .= '<td class="data-cell col-1 center-cell">' . htmlspecialchars($row['nro_procura'] ?? '') . '</td>';
            
            // 2. MUNICIPIO
            $municipio = acortarTexto($row['municipio'] ?? '', 12);
            $html .= '<td class="data-cell col-2 left-align">' . htmlspecialchars($municipio) . '</td>';
            
            // 3. PARROQUIA
            $parroquia = acortarTexto($row['parroquia'] ?? '', 15);
            $html .= '<td class="data-cell col-3 left-align">' . htmlspecialchars($parroquia) . '</td>';
            
            // 4. SECTOR
            $sector = acortarTexto($row['sector'] ?? '', 10);
            $html .= '<td class="data-cell col-4 left-align">' . htmlspecialchars($sector) . '</td>';
            
            // 5. CLAP (vacío)
            $html .= '<td class="data-cell col-5 empty-cell"></td>';
            
            // 6. COMUNA (vacío)
            $html .= '<td class="data-cell col-6 empty-cell"></td>';
            
            // 7. REPRESENTANTE NOMBRE
            $rep_info = procesarNombreCompleto($row['nombre_representante'] ?? '');
            $rep_nombre = acortarTexto($rep_info['nombre'], 10);
            $html .= '<td class="data-cell col-7 left-align">' . htmlspecialchars($rep_nombre) . '</td>';
            
            // 8. REPRESENTANTE APELLIDOS
            $rep_apellido = acortarTexto($rep_info['apellido'], 12);
            $html .= '<td class="data-cell col-8 left-align">' . htmlspecialchars($rep_apellido) . '</td>';
            
            // 9. REPRESENTANTE CÉDULA
            $cedula_rep = $row['cedula_representante'] ?? '';
            $html .= '<td class="data-cell col-9 center-cell">' . htmlspecialchars($cedula_rep) . '</td>';
            
            // 10. BENEFICIARIO NOMBRE
            $ben_info = procesarNombreCompleto($row['nombre_beneficiario'] ?? '');
            $ben_nombre = acortarTexto($ben_info['nombre'], 10);
            $html .= '<td class="data-cell col-10 left-align">' . htmlspecialchars($ben_nombre) . '</td>';
            
            // 11. BENEFICIARIO APELLIDO
            $ben_apellido = acortarTexto($ben_info['apellido'], 12);
            $html .= '<td class="data-cell col-11 left-align">' . htmlspecialchars($ben_apellido) . '</td>';
            
            // 12. GÉNERO (vacío)
            $html .= '<td class="data-cell col-12 empty-cell"></td>';
            
            // 13. BENEFICIARIO CÉDULA
            $cedula_ben = $row['cedula_beneficiario'] ?? '';
            $html .= '<td class="data-cell col-13 center-cell">' . htmlspecialchars($cedula_ben) . '</td>';
            
            // 14. FECHA DE NACIMIENTO
            $fecha_nac = $row['fecha_nacimiento'] ?? '';
            $html .= '<td class="data-cell col-14 center-cell">' . htmlspecialchars($fecha_nac) . '</td>';
            
            // 15. EDAD
            $edad = $row['edad'] ?? '';
            $html .= '<td class="data-cell col-15 center-cell">' . htmlspecialchars($edad) . '</td>';
            
            // 16. NÚMERO DE CONTACTO
            $telefono = formatearTelefono($row['numero_contacto'] ?? '');
            $html .= '<td class="data-cell col-16 center-cell">' . htmlspecialchars($telefono) . '</td>';
            
            // 17-28. CAMPOS VACÍOS
            for ($i = 17; $i <= 28; $i++) {
                $colClass = 'col-' . $i;
                $html .= '<td class="data-cell ' . $colClass . ' empty-cell"></td>';
            }
            
            $html .= '</tr>';
        }
        
        // Agregar filas vacías para completar página
        $filas_por_pagina = 40; // Más filas en oficio
        $filas_actuales = count($registros);
        $filas_restantes = $filas_por_pagina - ($filas_actuales % $filas_por_pagina);
        
        if ($filas_restantes > 0 && $filas_restantes < $filas_por_pagina) {
            for ($i = 0; $i < $filas_restantes; $i++) {
                $html .= '<tr>';
                for ($j = 1; $j <= 28; $j++) {
                    $colClass = 'col-' . $j;
                    $html .= '<td class="data-cell ' . $colClass . ' empty-cell"></td>';
                }
                $html .= '</tr>';
            }
        }
    } else {
        // Sin registros
        $html .= '<tr><td colspan="28" class="data-cell" style="text-align: center;">No hay registros que coincidan con los filtros</td></tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Escribir HTML
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Pie de página
    $pdf->SetY(-10);
    $pdf->SetFont('helvetica', 'I', 6);
    $pdf->Cell(0, 6, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Generar PDF
    $filename = 'formato_imprimir_oficio_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'I');
    
} catch (Exception $e) {
    if (ob_get_length() > 0) {
        ob_clean();
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error al generar PDF</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
            .error-box { background: #fff; border: 1px solid #dc3545; border-radius: 5px; padding: 20px; margin: 20px 0; }
            .error-title { color: #dc3545; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error-title">Error al generar el PDF</h2>
            <p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p><strong>Archivo:</strong> ' . $e->getFile() . '</p>
            <p><strong>Línea:</strong> ' . $e->getLine() . '</p>
        </div>
    </body>
    </html>';
}