<?php
// generar_formato_imprimir_excel_estado.php - VERSIÓN CON TABLAS
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_clean();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once __DIR__ . '/../../config/database.php';

$filtros = [
    'municipio' => $_GET['municipio'] ?? '',
    'parroquia' => $_GET['parroquia'] ?? '',
    'sector' => $_GET['sector'] ?? '',
    'caso' => $_GET['caso'] ?? ''
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // CONSULTA CON TABLAS INDIVIDUALES - SIN VISTA_CONSOLIDADO
    $sql = "SELECT 
        b.`cedula_beneficiario` AS nro_procura,
        u.`municipio`,
        u.`parroquia`,
        u.`sector`,
        r.`cedula_representante`,
        CONCAT(r.`nombres`, ' ', r.`apellidos`) AS nombre_representante,
        r.`numero_contacto`,
        b.`cedula_beneficiario`,
        CONCAT(b.`nombres`, ' ', b.`apellidos`) AS nombre_beneficiario,
        b.`genero`,
        DATE_FORMAT(b.`fecha_nacimiento`, '%d/%m/%Y') as fecha_nacimiento,
        TIMESTAMPDIFF(YEAR, b.`fecha_nacimiento`, CURDATE()) AS edad,
        b.`situacion_dx` AS diagnostico
        
    FROM `beneficiario` b
    LEFT JOIN `ubicacion` u ON b.`id_ubicacion` = u.`id_ubicacion`
    LEFT JOIN `representante` r ON b.`id_representante` = r.`id_representante`
    WHERE 1=1";
    
    // FILTRAR POR NRO_PROCURA (USAMOS CÉDULA COMO PROCURA)
    $sql .= " AND (b.`cedula_beneficiario` IS NOT NULL AND b.`cedula_beneficiario` != '')";
    
    $params = [];
    
    if (!empty($filtros['municipio'])) {
        $sql .= " AND u.`municipio` = ?";
        $params[] = $filtros['municipio'];
    }
    if (!empty($filtros['parroquia'])) {
        $sql .= " AND u.`parroquia` = ?";
        $params[] = $filtros['parroquia'];
    }
    if (!empty($filtros['sector'])) {
        $sql .= " AND u.`sector` = ?";
        $params[] = $filtros['sector'];
    }
    if (!empty($filtros['caso'])) {
        $sql .= " AND b.`situacion_dx` LIKE ?";
        $params[] = '%Caso ' . $filtros['caso'] . '%';
    }
    
    $sql .= " ORDER BY u.`municipio`, u.`parroquia`, u.`sector`, b.`cedula_beneficiario`";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FILTRAR A NIVEL DE PHP TAMBIÉN POR SEGURIDAD
    $registros_filtrados = [];
    foreach ($registros as $row) {
        if (!empty($row['nro_procura']) && trim($row['nro_procura']) !== '') {
            $registros_filtrados[] = $row;
        }
    }
    $registros = $registros_filtrados;
    
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
    
    // FUNCIÓN PARA FORMATEAR GÉNERO
    function formatearGenero($genero) {
        if (empty($genero)) return '';
        $genero = strtoupper(trim($genero));
        if ($genero == 'M' || $genero == 'MASCULINO') return 'M';
        if ($genero == 'F' || $genero == 'FEMENINO') return 'F';
        return $genero;
    }
    
    // Configurar cabeceras para Excel
    $filename = 'formato_imprimir_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Iniciar salida HTML
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="excel-export" content="true">
        <title>FORMATO PARA IMPRIMIR</title>
        <style>
            /* ESTILOS PARA EXCEL */
            body {
                font-family: Arial, sans-serif;
                font-size: 11px;
                margin: 0;
                padding: 0;
                width: 100%;
            }
            
            /* FORZAR HORIZONTAL EN EXCEL */
            @page {
                size: landscape;
                margin: 0.5cm;
            }
            
            /* CONTENEDOR PRINCIPAL */
            .contenedor-principal {
                width: 100%;
                margin: 0 auto;
            }
            
            /* TÍTULOS */
            .titulo-principal {
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                padding: 8px 0;
            }
            .titulo-secundario {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                padding: 6px 0;
            }
            .info-reporte {
                font-size: 10px;
                text-align: center;
                font-style: italic;
                padding: 4px 0;
                color: #555;
                margin-top: 15px;
                margin-bottom: 15px;
                background: #f5f5f5;
                border-radius: 3px;
                padding: 8px;
            }
            
            /* TABLA PRINCIPAL - CELDAS MÁS GRANDES Y MEJOR ESPACIADO */
            .tabla-principal {
                border-collapse: collapse;
                width: 100%;
                table-layout: auto;
                margin-top: 15px;
                font-size: 11px;
            }
            .tabla-principal th {
                background-color: #D9D9D9;
                font-weight: bold;
                text-align: center;
                vertical-align: middle;
                border: 1px solid #000;
                padding: 6px 4px;
                height: 40px;
                font-size: 10px;
                word-wrap: break-word;
                white-space: normal;
            }
            .tabla-principal td {
                border: 1px solid #000;
                padding: 5px 3px;
                text-align: center;
                vertical-align: middle;
                height: 25px;
                font-size: 10px;
                overflow: hidden;
                white-space: nowrap;
            }
            .celda-vacia {
                background-color: #F2F2F2;
            }
            .celda-vacia-oscura {
                background-color: #E8E8E8;
            }
            .texto-izquierda {
                text-align: left;
                padding-left: 5px;
            }
            .texto-centro {
                text-align: center;
            }
            .texto-derecha {
                text-align: right;
                padding-right: 5px;
            }
            
            /* ANCHOS DE COLUMNA MÁS GRANDES Y MEJOR DISTRIBUIDOS */
            .col-1 { width: 5%; min-width: 40px; }   /* N° PROCURA */
            .col-2 { width: 10%; min-width: 80px; }  /* SECTOR */
            .col-3 { width: 6%; min-width: 50px; }   /* CLAP */
            .col-4 { width: 6%; min-width: 50px; }   /* COMUNA */
            .col-5 { width: 12%; min-width: 100px; } /* REPRESENTANTE */
            .col-6 { width: 7%; min-width: 60px; }   /* REP CÉDULA */
            .col-7 { width: 12%; min-width: 100px; } /* BENEFICIARIO */
            .col-8 { width: 5%; min-width: 40px; }   /* GÉNERO */
            .col-9 { width: 7%; min-width: 60px; }   /* BEN CÉDULA */
            .col-10 { width: 7%; min-width: 60px; }  /* FECHA NAC */
            .col-11 { width: 4%; min-width: 35px; }  /* EDAD */
            .col-12 { width: 8%; min-width: 70px; }  /* CONTACTO */
            .col-13 { width: 6%; min-width: 50px; }  /* Lactante - VACÍO */
            .col-14 { width: 6%; min-width: 50px; }  /* Gestante - VACÍO */
            .col-15 { width: 6%; min-width: 50px; }  /* CBI (mm) - VACÍO */
            .col-16 { width: 6%; min-width: 50px; }  /* Peso (Kg) - VACÍO */
            .col-17 { width: 6%; min-width: 50px; }  /* Talla (cm) - VACÍO */
            .col-18 { width: 6%; min-width: 50px; }  /* CCI - VACÍO */
            .col-19 { width: 8%; min-width: 70px; }  /* DIAGNÓSTICO - VACÍO */
            .col-20 { width: 8%; min-width: 70px; }  /* FIRMA - VACÍO */
            
            /* ESTILOS ESPECÍFICOS PARA IMPRESIÓN */
            @media print {
                body {
                    font-size: 10px !important;
                    margin: 0 !important;
                    padding: 0.5cm !important;
                }
                
                .contenedor-principal {
                    width: 100% !important;
                    transform-origin: 0 0;
                }
                
                .tabla-principal {
                    page-break-inside: auto !important;
                    font-size: 9px !important;
                }
                
                .tabla-principal th,
                .tabla-principal td {
                    padding: 4px 3px !important;
                    font-size: 9px !important;
                    line-height: 1.3 !important;
                }
                
                /* Asegurar que la tabla no se divida entre páginas */
                tr {
                    page-break-inside: avoid !important;
                    page-break-after: auto !important;
                }
                
                /* Número de página */
                .pagina-numero {
                    position: fixed;
                    bottom: 0;
                    right: 0;
                    font-size: 8px;
                    color: #666;
                }
            }
            
            /* SALTO DE PÁGINA */
            .salto-pagina {
                page-break-after: always;
            }
            
            .no-salto-pagina {
                page-break-inside: avoid;
            }
        </style>
    </head>
    <body>';
    
    echo '<div class="contenedor-principal">';
    
    // INFORMACIÓN DEL REPORTE
    $filtrosTexto = [];
    if (!empty($filtros['municipio'])) $filtrosTexto[] = 'Municipio: ' . $filtros['municipio'];
    if (!empty($filtros['parroquia'])) $filtrosTexto[] = 'Parroquia: ' . $filtros['parroquia'];
    if (!empty($filtros['sector'])) $filtrosTexto[] = 'Sector: ' . $filtros['sector'];
    if (!empty($filtros['caso'])) $filtrosTexto[] = 'Caso: ' . $filtros['caso'];
    
    echo '<div class="titulo-principal">INSTITUTO NACIONAL DE NUTRICIÓN</div>';
    echo '<div class="titulo-secundario">FORMATO DE REGISTRO PARA IMPRIMIR</div>';
    
    if (!empty($filtrosTexto)) {
        echo '<div class="info-reporte">';
        echo 'Generado: ' . date('d/m/Y H:i:s');
        echo ' | Filtros: ' . implode(', ', $filtrosTexto);
        echo ' | Total registros: ' . count($registros);
        echo '</div>';
    }
    
    // TABLA PRINCIPAL (20 columnas - eliminamos las 2 columnas de apellidos)
    echo '<table class="tabla-principal" border="1" cellpadding="1" cellspacing="0">';
    
    // ENCABEZADOS (20 columnas)
    echo '<thead><tr>';
    
    $headers = [
        'N° DE PROCURA',
        'SECTOR',
        'CLAP',
        'COMUNA',
        'REPRESENTANTE',
        'REPRESENTANTE CÉDULA',
        'BENEFICIARIO',
        'GENERO',
        'BENEFICIARIO CÉDULA',
        'FECHA DE NACIMIENTO',
        'EDAD',
        'NUMERO DE CONTACTO',
        'Lactante',
        'Gestante',
        'CBI (mm)',
        'Peso (Kg)',
        'Talla (cm)',
        'CCI',
        'Situación Dx',
        'FIRMA DEL BENEFICIARIO'
    ];
    
    for ($i = 0; $i < count($headers); $i++) {
        $colClass = 'col-' . ($i + 1);
        // Marcar encabezados de columnas vacías con color diferente
        if ($i >= 12) { // Columnas 13 en adelante (índice 12)
            $bgColor = ' style="background-color: #C0C0C0;"';
        } else {
            $bgColor = '';
        }
        echo '<th class="' . $colClass . '"' . $bgColor . '>' . $headers[$i] . '</th>';
    }
    echo '</tr></thead>';
    
    // CUERPO DE LA TABLA
    echo '<tbody>';
    
    if (count($registros) > 0) {
        $contador = 0;
        foreach ($registros as $row) {
            $contador++;
            echo '<tr>';
            
            // 1. N° DE PROCURA (usamos la cédula como número de procura)
            echo '<td class="col-1 texto-centro">' . htmlspecialchars($row['nro_procura'] ?? '') . '</td>';
            
            // 2. SECTOR
            echo '<td class="col-2 texto-izquierda">' . htmlspecialchars($row['sector'] ?? '') . '</td>';
            
            // 3. CLAP (vacío)
            echo '<td class="col-3 celda-vacia"></td>';
            
            // 4. COMUNA (vacío)
            echo '<td class="col-4 celda-vacia"></td>';
            
            // 5. REPRESENTANTE (nombre completo)
            echo '<td class="col-5 texto-izquierda">' . htmlspecialchars($row['nombre_representante'] ?? '') . '</td>';
            
            // 6. REPRESENTANTE CÉDULA
            echo '<td class="col-6 texto-centro">' . htmlspecialchars($row['cedula_representante'] ?? '') . '</td>';
            
            // 7. BENEFICIARIO (nombre completo)
            echo '<td class="col-7 texto-izquierda">' . htmlspecialchars($row['nombre_beneficiario'] ?? '') . '</td>';
            
            // 8. GÉNERO
            $genero = formatearGenero($row['genero'] ?? '');
            echo '<td class="col-8 texto-centro">' . htmlspecialchars($genero) . '</td>';
            
            // 9. BENEFICIARIO CÉDULA
            echo '<td class="col-9 texto-centro">' . htmlspecialchars($row['cedula_beneficiario'] ?? '') . '</td>';
            
            // 10. FECHA DE NACIMIENTO
            echo '<td class="col-10 texto-centro">' . htmlspecialchars($row['fecha_nacimiento'] ?? '') . '</td>';
            
            // 11. EDAD
            echo '<td class="col-11 texto-centro">' . htmlspecialchars($row['edad'] ?? '') . '</td>';
            
            // 12. NÚMERO DE CONTACTO
            $telefono = formatearTelefono($row['numero_contacto'] ?? '');
            echo '<td class="col-12 texto-centro">' . htmlspecialchars($telefono) . '</td>';
            
            // 13-20. CAMPOS VACÍOS PARA LLENAR MANUALMENTE
            for ($j = 13; $j <= 20; $j++) {
                $colClass = 'col-' . $j;
                echo '<td class="' . $colClass . ' celda-vacia-oscura"></td>';
            }
            
            echo '</tr>';
            
            // Insertar salto de página cada 30 filas para mejor impresión
            if ($contador % 30 == 0 && $contador < count($registros)) {
                echo '</tbody></table>';
                echo '<div class="salto-pagina"></div>';
                echo '<table class="tabla-principal" border="1" cellpadding="1" cellspacing="0">';
                echo '<thead><tr>';
                for ($i = 0; $i < count($headers); $i++) {
                    $colClass = 'col-' . ($i + 1);
                    if ($i >= 12) {
                        $bgColor = ' style="background-color: #C0C0C0;"';
                    } else {
                        $bgColor = '';
                    }
                    echo '<th class="' . $colClass . '"' . $bgColor . '>' . $headers[$i] . '</th>';
                }
                echo '</tr></thead>';
                echo '<tbody>';
            }
        }
        
        // Agregar filas vacías si hay pocos registros para mejor impresión
        if (count($registros) < 15) {
            $filas_vacias = 15 - count($registros);
            for ($i = 0; $i < $filas_vacias; $i++) {
                echo '<tr>';
                for ($j = 1; $j <= 20; $j++) {
                    $colClass = 'col-' . $j;
                    // Columnas 13-20 tienen fondo oscuro
                    if ($j >= 13) {
                        echo '<td class="' . $colClass . ' celda-vacia-oscura"></td>';
                    } else {
                        echo '<td class="' . $colClass . ' celda-vacia"></td>';
                    }
                }
                echo '</tr>';
            }
        }
        
    } else {
        // Sin registros - fila completa vacía
        echo '<tr><td colspan="20" class="texto-centro">No hay registros que coincidan con los filtros</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // PIE DEL DOCUMENTO
    echo '<div style="margin-top: 15px; font-size: 9px; color: #666; text-align: center;">';
    echo 'Documento generado automáticamente por el Sistema INN | ';
    echo 'Total de registros: ' . count($registros);
    echo '</div>';
    
    echo '</div>'; // Cierre contenedor-principal
    
    // INSTRUCCIONES PARA EL USUARIO
    echo '<div style="margin-top: 15px; font-size: 9px; color: #0066cc; text-align: center; border: 1px dashed #0066cc; padding: 8px; background: #f0f8ff;">';
    echo '<strong>INSTRUCCIONES PARA IMPRIMIR:</strong> Para que todas las columnas quepan en una página, configurar la impresión en ORIENTACIÓN HORIZONTAL. Columnas grises para completar manualmente.';
    echo '</div>';
    
    // INSTRUCCIONES PARA CONFIGURAR PÁGINA EN EXCEL
    echo '<div style="margin-top: 5px; font-size: 8px; color: #666; text-align: center;">';
    echo 'Configurar página: Archivo → Imprimir → Configurar página → Orientación: Horizontal → Márgenes: Estrechos → Ajustar a: 1 página de ancho';
    echo '</div>';
    
    echo '</body></html>';
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error al generar Excel</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
            .error-box { background: #fff; border: 1px solid #dc3545; border-radius: 5px; padding: 20px; margin: 20px 0; }
            .error-title { color: #dc3545; margin-top: 0; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error-title">Error al generar el archivo Excel</h2>
            <p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p><strong>Archivo:</strong> ' . $e->getFile() . '</p>
            <p><strong>Línea:</strong> ' . $e->getLine() . '</p>';
    
    if (isset($sql)) {
        echo '<h3>Consulta SQL utilizada:</h3>
              <pre>' . htmlspecialchars($sql) . '</pre>';
    }
    
    echo '<p><strong>Parámetros:</strong> ' . (isset($params) ? implode(', ', $params) : 'Ninguno') . '</p>
        </div>
    </body>
    </html>';
}