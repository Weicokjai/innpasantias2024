<?php
// generar_formato_imprimir_excel_estado.php
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
    
    // CONSULTA SIMPLE - TODO DESDE VISTA_CONSOLIDADO
    $sql = "SELECT 
        nro_procura,
        municipio,
        parroquia,
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
    
    $sql .= " ORDER BY municipio, parroquia, sector, nro_procura";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FUNCIÓN PARA SEPARAR NOMBRE COMPLETO EN NOMBRE Y APELLIDO
    function separarNombreCompleto($nombre_completo) {
        $nombre_completo = trim($nombre_completo);
        if (empty($nombre_completo)) {
            return ['nombre' => '', 'apellido' => ''];
        }
        
        $partes = explode(' ', $nombre_completo);
        
        // Si solo tiene una palabra
        if (count($partes) == 1) {
            return ['nombre' => $partes[0], 'apellido' => ''];
        }
        
        // Tomar primera palabra como nombre
        $nombre = $partes[0];
        
        // Resto como apellido (máximo 2 palabras)
        $apellido_parts = array_slice($partes, 1, 2);
        $apellido = implode(' ', $apellido_parts);
        
        return ['nombre' => $nombre, 'apellido' => $apellido];
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
    
    // FUNCIÓN PARA ACORTAR TEXTO
    function acortarTexto($texto, $maximo = 15) {
        if (strlen($texto) <= $maximo) {
            return $texto;
        }
        return substr($texto, 0, $maximo - 3) . '...';
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
        <title>FORMATO PARA IMPRIMIR</title>
        <style>
            /* ESTILOS PARA EXCEL */
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
                margin: 0;
                padding: 10px;
                position: relative;
            }
            .encabezado-superior {
                width: 100%;
                margin-bottom: 15px;
                position: relative;
            }
            .titulo-principal {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                padding: 5px 0;
            }
            .titulo-secundario {
                font-size: 12px;
                font-weight: bold;
                text-align: center;
                padding: 3px 0;
            }
            .info-estado {
                position: absolute;
                top: 0;
                right: 0;
                text-align: right;
                font-size: 10px;
                font-weight: bold;
                border: 1px solid #000;
                padding: 8px 12px;
                background-color: #f0f0f0;
                border-radius: 4px;
                line-height: 1.3;
            }
            .info-reporte {
                font-size: 9px;
                text-align: center;
                font-style: italic;
                padding: 2px 0;
                color: #555;
                margin-top: 40px; /* Espacio para el info-estado */
            }
            .tabla-principal {
                border-collapse: collapse;
                width: 100%;
                margin-top: 10px;
                table-layout: fixed;
            }
            .tabla-principal th {
                background-color: #D9D9D9;
                font-weight: bold;
                text-align: center;
                vertical-align: middle;
                border: 1px solid #000;
                padding: 3px;
                height: 35px;
                font-size: 8px;
                word-wrap: break-word;
            }
            .tabla-principal td {
                border: 1px solid #000;
                padding: 2px;
                text-align: center;
                vertical-align: middle;
                height: 20px;
                font-size: 9px;
                overflow: hidden;
            }
            .celda-vacia {
                background-color: #F2F2F2;
            }
            .celda-vacia-oscura {
                background-color: #E8E8E8;
            }
            .texto-izquierda {
                text-align: left;
                padding-left: 4px;
            }
            .texto-centro {
                text-align: center;
            }
            .texto-derecha {
                text-align: right;
                padding-right: 4px;
            }
            /* ANCHOS DE COLUMNA (24 columnas total) */
            .col-1 { width: 4%; }   /* N° PROCURA */
            .col-2 { width: 6%; }   /* MUNICIPIO */
            .col-3 { width: 7%; }   /* PARROQUIA */
            .col-4 { width: 5%; }   /* SECTOR */
            .col-5 { width: 4%; }   /* CLAP */
            .col-6 { width: 4%; }   /* COMUNA */
            .col-7 { width: 6%; }   /* REP NOMBRE */
            .col-8 { width: 7%; }   /* REP APELLIDO */
            .col-9 { width: 5%; }   /* REP CÉDULA */
            .col-10 { width: 6%; }  /* BEN NOMBRE */
            .col-11 { width: 7%; }  /* BEN APELLIDO */
            .col-12 { width: 4%; }  /* GÉNERO */
            .col-13 { width: 5%; }  /* BEN CÉDULA */
            .col-14 { width: 5%; }  /* FECHA NAC */
            .col-15 { width: 3%; }  /* EDAD */
            .col-16 { width: 5%; }  /* CONTACTO */
            .col-17 { width: 4%; }  /* Lactante - VACÍO */
            .col-18 { width: 4%; }  /* Gestante - VACÍO */
            .col-19 { width: 4%; }  /* CBI - VACÍO */
            .col-20 { width: 4%; }  /* Peso - VACÍO */
            .col-21 { width: 4%; }  /* Talla - VACÍO */
            .col-22 { width: 4%; }  /* CCI - VACÍO */
            .col-23 { width: 6%; }  /* DIAGNÓSTICO - VACÍO */
            .col-24 { width: 7%; }  /* FIRMA - VACÍO */
            .logo-izquierda {
                position: absolute;
                top: 0;
                left: 0;
                font-size: 10px;
                font-weight: bold;
                border: 1px solid #000;
                padding: 8px 12px;
                background-color: #f0f0f0;
                border-radius: 4px;
                line-height: 1.3;
            }
        </style>
    </head>
    <body>';
    
    // ENCABEZADO SUPERIOR CON LOGO IZQUIERDO Y ESTADO DERECHO
    echo '<div class="encabezado-superior">';
    
    // Logo/Info izquierda (opcional, puedes personalizar)
    echo '<div class="logo-izquierda">';
    echo 'INN<br>VENEZUELA';
    echo '</div>';
    
    // Información del estado a la derecha
    echo '<div class="info-estado">';
    echo 'ESTADO: LARA<br>';
    
    // Mostrar el municipio del filtro o el primero de los resultados
    $municipio_mostrar = '';
    if (!empty($filtros['municipio'])) {
        $municipio_mostrar = $filtros['municipio'];
    } elseif (count($registros) > 0 && !empty($registros[0]['municipio'])) {
        $municipio_mostrar = $registros[0]['municipio'];
    } else {
        $municipio_mostrar = '[SELECCIONAR]';
    }
    
    echo 'MUNICIPIO: ' . strtoupper($municipio_mostrar);
    echo '</div>';
    
    echo '</div>'; // Cierre encabezado-superior
    
    // TÍTULO PRINCIPAL
    echo '<div class="titulo-principal">INSTITUTO NACIONAL DE NUTRICIÓN</div>';
    echo '<div class="titulo-secundario">FORMATO PARA IMPRIMIR - TERCERA ENTREGA ALI PRIMERA</div>';
    
    // INFORMACIÓN DEL REPORTE
    $info = 'Generado: ' . date('d/m/Y H:i:s') . ' | Registros: ' . count($registros);
    
    $filtrosTexto = [];
    if (!empty($filtros['parroquia'])) $filtrosTexto[] = 'Parroquia: ' . $filtros['parroquia'];
    if (!empty($filtros['sector'])) $filtrosTexto[] = 'Sector: ' . $filtros['sector'];
    if (!empty($filtros['caso'])) $filtrosTexto[] = 'Caso: ' . $filtros['caso'];
    
    if (!empty($filtrosTexto)) {
        $info .= ' | Filtros: ' . implode(', ', $filtrosTexto);
    }
    
    echo '<div class="info-reporte">' . $info . '</div>';
    
    // TABLA PRINCIPAL
    echo '<table class="tabla-principal" border="1" cellpadding="2" cellspacing="0">';
    
    // ENCABEZADOS (24 columnas)
    echo '<thead><tr>';
    
    $headers = [
        'N° DE PROCURA',
        'MUNICIPIO',
        'PARROQUIA',
        'SECTOR',
        'CLAP',
        'COMUNA',
        'REPRESENTANTE NOMBRE',
        'REPRESENTANTES APELLIDOS',
        'REPRESENTANTE CÉDULA',
        'BENEFICIARIO NOMBRE',
        'BENEFICIARIO APELLIDO',
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
        if ($i >= 16) { // Columnas 17 en adelante (índice 16)
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
        foreach ($registros as $row) {
            echo '<tr>';
            
            // 1. N° DE PROCURA
            echo '<td class="col-1 texto-centro">' . htmlspecialchars($row['nro_procura'] ?? '') . '</td>';
            
            // 2. MUNICIPIO
            $municipio = acortarTexto($row['municipio'] ?? '', 12);
            echo '<td class="col-2 texto-izquierda">' . htmlspecialchars($municipio) . '</td>';
            
            // 3. PARROQUIA
            $parroquia = acortarTexto($row['parroquia'] ?? '', 15);
            echo '<td class="col-3 texto-izquierda">' . htmlspecialchars($parroquia) . '</td>';
            
            // 4. SECTOR
            $sector = acortarTexto($row['sector'] ?? '', 10);
            echo '<td class="col-4 texto-izquierda">' . htmlspecialchars($sector) . '</td>';
            
            // 5. CLAP (vacío)
            echo '<td class="col-5 celda-vacia"></td>';
            
            // 6. COMUNA (vacío)
            echo '<td class="col-6 celda-vacia"></td>';
            
            // 7-8. REPRESENTANTE NOMBRE Y APELLIDO
            $rep_info = separarNombreCompleto($row['nombre_representante'] ?? '');
            $rep_nombre = acortarTexto($rep_info['nombre'], 12);
            $rep_apellido = acortarTexto($rep_info['apellido'], 15);
            
            echo '<td class="col-7 texto-izquierda">' . htmlspecialchars($rep_nombre) . '</td>';
            echo '<td class="col-8 texto-izquierda">' . htmlspecialchars($rep_apellido) . '</td>';
            
            // 9. REPRESENTANTE CÉDULA
            $cedula_rep = $row['cedula_representante'] ?? '';
            echo '<td class="col-9 texto-centro">' . htmlspecialchars($cedula_rep) . '</td>';
            
            // 10-11. BENEFICIARIO NOMBRE Y APELLIDO
            $ben_info = separarNombreCompleto($row['nombre_beneficiario'] ?? '');
            $ben_nombre = acortarTexto($ben_info['nombre'], 12);
            $ben_apellido = acortarTexto($ben_info['apellido'], 15);
            
            echo '<td class="col-10 texto-izquierda">' . htmlspecialchars($ben_nombre) . '</td>';
            echo '<td class="col-11 texto-izquierda">' . htmlspecialchars($ben_apellido) . '</td>';
            
            // 12. GÉNERO
            $genero = formatearGenero($row['genero'] ?? '');
            echo '<td class="col-12 texto-centro">' . htmlspecialchars($genero) . '</td>';
            
            // 13. BENEFICIARIO CÉDULA
            $cedula_ben = $row['cedula_beneficiario'] ?? '';
            echo '<td class="col-13 texto-centro">' . htmlspecialchars($cedula_ben) . '</td>';
            
            // 14. FECHA DE NACIMIENTO
            $fecha_nac = $row['fecha_nacimiento'] ?? '';
            echo '<td class="col-14 texto-centro">' . htmlspecialchars($fecha_nac) . '</td>';
            
            // 15. EDAD
            $edad = $row['edad'] ?? '';
            echo '<td class="col-15 texto-centro">' . htmlspecialchars($edad) . '</td>';
            
            // 16. NÚMERO DE CONTACTO
            $telefono = formatearTelefono($row['numero_contacto'] ?? '');
            echo '<td class="col-16 texto-centro">' . htmlspecialchars($telefono) . '</td>';
            
            // 17-24. CAMPOS VACÍOS PARA LLENAR MANUALMENTE (8 campos)
            // Lactante (vacío)
            echo '<td class="col-17 celda-vacia-oscura"></td>';
            
            // Gestante (vacío)
            echo '<td class="col-18 celda-vacia-oscura"></td>';
            
            // CBI (mm) (vacío)
            echo '<td class="col-19 celda-vacia-oscura"></td>';
            
            // Peso (Kg) (vacío)
            echo '<td class="col-20 celda-vacia-oscura"></td>';
            
            // Talla (cm) (vacío)
            echo '<td class="col-21 celda-vacia-oscura"></td>';
            
            // CCI (vacío)
            echo '<td class="col-22 celda-vacia-oscura"></td>';
            
            // Situación Dx (vacío)
            echo '<td class="col-23 celda-vacia-oscura"></td>';
            
            // FIRMA DEL BENEFICIARIO (vacío)
            echo '<td class="col-24 celda-vacia-oscura"></td>';
            
            echo '</tr>';
        }
        
        // Agregar filas vacías si hay pocos registros para mejor impresión
        if (count($registros) < 10) {
            $filas_vacias = 10 - count($registros);
            for ($i = 0; $i < $filas_vacias; $i++) {
                echo '<tr>';
                for ($j = 1; $j <= 24; $j++) {
                    $colClass = 'col-' . $j;
                    // Columnas 17-24 tienen fondo oscuro
                    if ($j >= 17) {
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
        echo '<tr><td colspan="24" class="texto-centro">No hay registros que coincidan con los filtros</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // PIE DEL DOCUMENTO
    echo '<div style="margin-top: 15px; font-size: 8px; color: #666; text-align: center;">';
    echo 'Documento generado automáticamente por el Sistema INN | ';
    echo 'Total de registros: ' . count($registros) . ' | ';
    echo 'Página 1 de 1';
    echo '</div>';
    
    // INSTRUCCIONES PARA EL USUARIO
    echo '<div style="margin-top: 10px; font-size: 8px; color: #0066cc; text-align: center; border: 1px dashed #0066cc; padding: 5px; background: #f0f8ff;">';
    echo '<strong>INSTRUCCIONES:</strong> Las columnas marcadas con fondo gris oscuro deben completarse manualmente durante la evaluación médica.';
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
            <p><strong>Línea:</strong> ' . $e->getLine() . '</p>
            <h3>Consulta SQL utilizada:</h3>
            <pre>' . htmlspecialchars($sql ?? 'No definida') . '</pre>
            <p><strong>Parámetros:</strong> ' . implode(', ', $params) . '</p>
        </div>
    </body>
    </html>';
}