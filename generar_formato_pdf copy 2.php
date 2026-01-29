<?php
// generar_formato_imprimir_pdf_compacto.php
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

    // Consulta - SIN MUNICIPIO NI PARROQUIA
    $sql = "SELECT 
        nro_procura,
        sector,
        cedula_representante,
        nombre_representante,
        numero_contacto,
        cedula_beneficiario,
        nombre_beneficiario,
        genero,
        DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento,
        edad_actual as edad
    FROM vista_consolidado 
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
    
    $sql .= " ORDER BY nro_procura";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // **USAR PAPEL OFICIO EN HORIZONTAL** - 279.4 x 215.9 mm
    $pdf = new TCPDF('L', 'mm', array(279.4, 215.9), true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator('Sistema INN');
    $pdf->SetAuthor('Instituto Nacional de Nutrición');
    $pdf->SetTitle('FORMATO PARA IMPRIMIR - TERCERA ENTREGA ALI PRIMERA');
    
    // **MÁRGENES MUY PEQUEÑOS PARA MÁXIMO ESPACIO**
    $pdf->SetMargins(2, 15, 2);
    $pdf->SetAutoPageBreak(TRUE, 5);
    $pdf->SetFont('helvetica', '', 6); // Fuente más pequeña globalmente
    
    // Agregar página
    $pdf->AddPage();
    
    // **ENCABEZADO COMPACTO**
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetXY(2, 3);
    $pdf->Cell(30, 6, 'INN - VENEZUELA', 1, 0, 'C', 1);
    
    // Información del estado
    $municipio_mostrar = '';
    if (!empty($filtros['municipio'])) {
        $municipio_mostrar = $filtros['municipio'];
    } elseif (count($registros) > 0) {
        $sql_municipio = "SELECT municipio FROM vista_consolidado WHERE sector = ? LIMIT 1";
        $stmt_muni = $db->prepare($sql_municipio);
        if (!empty($registros[0]['sector'])) {
            $stmt_muni->bindValue(1, $registros[0]['sector']);
            $stmt_muni->execute();
            $municipio_data = $stmt_muni->fetch(PDO::FETCH_ASSOC);
            $municipio_mostrar = $municipio_data['municipio'] ?? '';
        }
    }
    
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetXY(247.4, 3); // Muy a la derecha
    $pdf->Cell(30, 6, "LARA - " . strtoupper(substr($municipio_mostrar, 0, 10)), 1, 0, 'C', 1);
    
    // **TÍTULOS PRINCIPALES - MUY COMPACTOS**
    $pdf->SetY(12);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 4, 'INSTITUTO NACIONAL DE NUTRICIÓN', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 3, 'FORMATO PARA IMPRIMIR - TERCERA ENTREGA ALI PRIMERA', 0, 1, 'C');
    
    // **INFORMACIÓN DEL REPORTE - UNA LÍNEA**
    $pdf->SetFont('helvetica', 'I', 6);
    $info = date('d/m/Y H:i') . ' | Registros: ' . count($registros);
    
    if (!empty($filtros['sector'])) {
        $info .= ' | Sector: ' . substr($filtros['sector'], 0, 15);
    }
    if (!empty($filtros['caso'])) {
        $info .= ' | Caso: ' . $filtros['caso'];
    }
    
    $pdf->Cell(0, 3, $info, 0, 1, 'C');
    $pdf->Ln(1);
    
    // **FUNCIONES AUXILIARES OPTIMIZADAS**
    function separarNombreCompleto($nombre_completo) {
        $nombre_completo = trim($nombre_completo);
        if (empty($nombre_completo)) {
            return ['nombre' => '', 'apellido' => ''];
        }
        
        $partes = explode(' ', $nombre_completo);
        
        if (count($partes) == 1) {
            return ['nombre' => $partes[0], 'apellido' => ''];
        }
        
        $nombre = $partes[0];
        // Solo una palabra para apellido (más compacto)
        $apellido = $partes[1] ?? '';
        
        return ['nombre' => $nombre, 'apellido' => $apellido];
    }
    
    function formatearTelefono($telefono) {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($telefono) == 11) {
            return substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        } elseif (strlen($telefono) == 10) {
            return substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        }
        return $telefono;
    }
    
    function acortarTexto($texto, $maximo = 12) { // Más corto
        $texto = trim($texto);
        if (strlen($texto) <= $maximo) {
            return $texto;
        }
        return substr($texto, 0, $maximo - 2) . '..';
    }
    
    function formatearGenero($genero) {
        if (empty($genero)) return '';
        $genero = strtoupper(trim($genero));
        if ($genero == 'M' || $genero == 'MASCULINO') return 'M';
        if ($genero == 'F' || $genero == 'FEMENINO') return 'F';
        return $genero;
    }
    
    // **CREAR TABLA MUY COMPACTA (REDUCIDA A 16 COLUMNAS)**
    $html = '
    <style>
    .tabla-excel {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 5.5pt; /* FUENTE MUY PEQUEÑA */
        table-layout: fixed;
    }
    .tabla-excel th {
        background-color: #D9D9D9;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        border: 0.4px solid #000000;
        padding: 1px;
        height: 18px; /* MUY COMPACTO */
        font-size: 5.3pt;
        word-wrap: break-word;
        line-height: 0.9;
    }
    .tabla-excel th.vacio {
        background-color: #C0C0C0;
    }
    .tabla-excel td {
        border: 0.4px solid #000000;
        padding: 1px;
        text-align: center;
        vertical-align: middle;
        height: 14px; /* MUY COMPACTO */
        font-size: 5.5pt;
        word-wrap: break-word;
        overflow: hidden;
    }
    .tabla-excel td.izquierda {
        text-align: left;
        padding-left: 1px;
    }
    .tabla-excel td.centro {
        text-align: center;
    }
    .tabla-excel td.vacio {
        background-color: #F2F2F2;
    }
    .tabla-excel td.vacio-oscuro {
        background-color: #E8E8E8;
    }
    /* **SOLO 16 COLUMNAS CRÍTICAS** */
    .col-1 { width: 4.0%; }   /* N° PROCURA */
    .col-2 { width: 10.0%; }  /* SECTOR */
    .col-3 { width: 7.0%; }   /* REP. NOMBRE */
    .col-4 { width: 8.0%; }   /* REP. APELLIDO */
    .col-5 { width: 7.0%; }   /* REP. CÉDULA */
    .col-6 { width: 7.0%; }   /* BEN. NOMBRE */
    .col-7 { width: 8.0%; }   /* BEN. APELLIDO */
    .col-8 { width: 3.0%; }   /* GEN */
    .col-9 { width: 7.0%; }   /* BEN. CÉDULA */
    .col-10 { width: 6.0%; }  /* FECHA NAC */
    .col-11 { width: 3.0%; }  /* EDAD */
    .col-12 { width: 7.0%; }  /* CONTACTO */
    .col-13 { width: 4.5%; }  /* Peso */
    .col-14 { width: 4.5%; }  /* Talla */
    .col-15 { width: 6.0%; }  /* Diagnóstico */
    .col-16 { width: 8.0%; }  /* Firma */
    </style>
    
    <table class="tabla-excel" cellpadding="0" cellspacing="0">
    <thead>
    <tr>';
    
    // **SOLO 16 COLUMNAS ESENCIALES**
    $headers = [
        'N°',
        'SECTOR',
        'REP. NOM',
        'REP. APE',
        'REP. CÉD',
        'BEN. NOM',
        'BEN. APE',
        'GEN',
        'BEN. CÉD',
        'F. NAC',
        'EDAD',
        'CONTACTO',
        'PESO',
        'TALLA',
        'DX',
        'FIRMA'
    ];
    
    for ($i = 0; $i < count($headers); $i++) {
        $colClass = 'col-' . ($i + 1);
        // **COLUMNAS 13-16 CON FONDO GRIS OSCURO (campos a completar)**
        $headerClass = ($i >= 12) ? 'vacio' : '';
        $html .= '<th class="' . $colClass . ' ' . $headerClass . '">' . $headers[$i] . '</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    // **CUERPO DE LA TABLA SUPER COMPACTO**
    if (count($registros) > 0) {
        foreach ($registros as $row) {
            $html .= '<tr>';
            
            // 1. N° DE PROCURA
            $html .= '<td class="col-1 centro">' . htmlspecialchars($row['nro_procura'] ?? '') . '</td>';
            
            // 2. SECTOR
            $sector = acortarTexto($row['sector'] ?? '', 15);
            $html .= '<td class="col-2 izquierda">' . htmlspecialchars($sector) . '</td>';
            
            // 3-4. REPRESENTANTE NOMBRE Y APELLIDO
            $rep_info = separarNombreCompleto($row['nombre_representante'] ?? '');
            $rep_nombre = acortarTexto($rep_info['nombre'], 8);
            $rep_apellido = acortarTexto($rep_info['apellido'], 10);
            
            $html .= '<td class="col-3 izquierda">' . htmlspecialchars($rep_nombre) . '</td>';
            $html .= '<td class="col-4 izquierda">' . htmlspecialchars($rep_apellido) . '</td>';
            
            // 5. REPRESENTANTE CÉDULA
            $cedula_rep = $row['cedula_representante'] ?? '';
            $html .= '<td class="col-5 centro">' . htmlspecialchars($cedula_rep) . '</td>';
            
            // 6-7. BENEFICIARIO NOMBRE Y APELLIDO
            $ben_info = separarNombreCompleto($row['nombre_beneficiario'] ?? '');
            $ben_nombre = acortarTexto($ben_info['nombre'], 8);
            $ben_apellido = acortarTexto($ben_info['apellido'], 10);
            
            $html .= '<td class="col-6 izquierda">' . htmlspecialchars($ben_nombre) . '</td>';
            $html .= '<td class="col-7 izquierda">' . htmlspecialchars($ben_apellido) . '</td>';
            
            // 8. GÉNERO
            $genero = formatearGenero($row['genero'] ?? '');
            $html .= '<td class="col-8 centro">' . htmlspecialchars($genero) . '</td>';
            
            // 9. BENEFICIARIO CÉDULA
            $cedula_ben = $row['cedula_beneficiario'] ?? '';
            $html .= '<td class="col-9 centro">' . htmlspecialchars($cedula_ben) . '</td>';
            
            // 10. FECHA DE NACIMIENTO
            $fecha_nac = $row['fecha_nacimiento'] ?? '';
            $html .= '<td class="col-10 centro">' . htmlspecialchars($fecha_nac) . '</td>';
            
            // 11. EDAD
            $edad = $row['edad'] ?? '';
            $html .= '<td class="col-11 centro">' . htmlspecialchars($edad) . '</td>';
            
            // 12. CONTACTO
            $telefono = formatearTelefono($row['numero_contacto'] ?? '');
            $telefono_corto = substr($telefono, 0, 12);
            $html .= '<td class="col-12 centro">' . htmlspecialchars($telefono_corto) . '</td>';
            
            // **13-16. CAMPOS VACÍOS PARA LLENAR (solo los más importantes)**
            // 13. Peso (Kg) (vacío)
            $html .= '<td class="col-13 vacio-oscuro"></td>';
            
            // 14. Talla (cm) (vacío)
            $html .= '<td class="col-14 vacio-oscuro"></td>';
            
            // 15. Diagnóstico (vacío)
            $html .= '<td class="col-15 vacio-oscuro"></td>';
            
            // 16. Firma (vacío)
            $html .= '<td class="col-16 vacio-oscuro"></td>';
            
            $html .= '</tr>';
        }
    } else {
        // Sin registros
        $html .= '<tr><td colspan="16" style="text-align: center; font-size: 6pt;">No hay registros</td></tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Escribir HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // **PIE DE PÁGINA MINIMALISTA**
    $pdf->SetY(-8);
    $pdf->SetFont('helvetica', 'I', 5);
    $pdf->Cell(0, 3, 'Sistema INN - Pág ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Generar PDF
    $filename = 'formato_imprimir_compacto_' . date('Y-m-d_H-i-s') . '.pdf';
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