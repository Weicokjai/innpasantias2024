<?php
// exportar_consolidado_excel.php - VERSIÓN EXCEL FORMATEADO SIN COMPOSER CON TABLAS
session_start();

require_once '../../config/database.php';

// Conexión
$database = new Database();
$db = $database->getConnection();

// CONSULTA COMPLETA CON JOIN DE TABLAS
// Versión corregida usando solo las tablas que tienes
$sql = "SELECT 
    b.`cedula_beneficiario` AS nro_procura,  -- Usando cédula como identificador temporal
    b.`fecha_registro` AS fecha_entrega,     -- Usando fecha_registro como fecha_entrega temporal
    
    u.`estado`, 
    u.`municipio`, 
    u.`parroquia`, 
    u.`sector`,
    
    rep.`cedula_representante`, 
    CONCAT(rep.`nombres`, ' ', rep.`apellidos`) AS nombre_representante,
    rep.`numero_contacto`,
    
    b.`cedula_beneficiario`,
    CONCAT(b.`nombres`, ' ', b.`apellidos`) AS nombre_beneficiario,
    b.`genero`,
    b.`fecha_nacimiento`,
    TIMESTAMPDIFF(YEAR, b.`fecha_nacimiento`, CURDATE()) AS edad_actual,
    
    b.`peso_kg`,
    b.`talla_cm`,
    b.`imc`,
    b.`situacion_dx` AS diagnostico,
    b.`cbi_mm`,
    b.`cci_cintura`,
    b.`lactando`,
    b.`gestante`,
    b.`semanas_gestacion`,
    b.`status` AS observacion_novedad
    
FROM `beneficiario` b
LEFT JOIN `ubicacion` u ON b.`id_ubicacion` = u.`id_ubicacion`
LEFT JOIN `representante` rep ON b.`id_representante` = rep.`id_representante`
WHERE 1";

// Aplicar filtros
$filtros = $_GET;
$params = [];
$conditions = [];

if (!empty($filtros['municipio'])) {
    $conditions[] = "u.`municipio` = ?";
    $params[] = $filtros['municipio'];
}

if (!empty($filtros['parroquia'])) {
    $conditions[] = "u.`parroquia` = ?";
    $params[] = $filtros['parroquia'];
}

if (!empty($filtros['sector'])) {
    $conditions[] = "u.`sector` = ?";
    $params[] = $filtros['sector'];
}

if (!empty($filtros['caso'])) {
    $conditions[] = "b.`situacion_dx` LIKE ?";
    $params[] = '%Caso ' . $filtros['caso'] . '%';
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY u.`municipio`, u.`parroquia`, u.`sector`";

try {
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
    }
    
    $stmt->execute();
    
    // HEADERS PARA EXCEL HTML (formateado)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_consolidado_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // INICIAR HTML PARA EXCEL
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Consolidado</title>
        <style>
            /* ESTILOS PARA EXCEL */
            body { font-family: Arial, sans-serif; font-size: 11px; }
            .titulo { 
                background-color: #1F497D; 
                color: white; 
                text-align: center; 
                padding: 15px; 
                font-size: 16px; 
                font-weight: bold;
            }
            .subtitulo { 
                background-color: #4F81BD; 
                color: white; 
                text-align: center; 
                padding: 10px; 
                font-size: 14px; 
                font-weight: bold;
            }
            .fecha { 
                text-align: center; 
                font-style: italic; 
                padding: 8px;
                background-color: #E6F2FF;
            }
            .filtros { 
                background-color: #F2F2F2; 
                padding: 8px; 
                font-size: 12px; 
                border: 1px solid #CCC;
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
                margin-top: 10px;
            }
            th { 
                background-color: #4F81BD; 
                color: white; 
                font-weight: bold; 
                padding: 8px; 
                border: 1px solid #1F497D;
                text-align: center;
                white-space: nowrap;
            }
            td { 
                padding: 6px; 
                border: 1px solid #CCC; 
                font-size: 11px;
            }
            .numero { text-align: right; }
            .fecha-celda { text-align: center; }
            .texto { text-align: left; }
            .centrado { text-align: center; }
            .total { 
                background-color: #C5D9F1; 
                font-weight: bold; 
                padding: 10px; 
                text-align: right;
                border: 1px solid #4F81BD;
            }
            .caso1 { background-color: #E2EFDA; }
            .caso2 { background-color: #FFF2CC; }
            .caso3 { background-color: #FCE4D6; }
            
            /* COLORES PARA IMC */
            .imc-bajo { color: #FF9900; font-weight: bold; }
            .imc-normal { color: #00B050; font-weight: bold; }
            .imc-sobrepeso { color: #FF6600; font-weight: bold; }
            .imc-obeso { color: #FF0000; font-weight: bold; }
            
            /* ANCHOS DE COLUMNAS */
            .col-nro { width: 80px; }
            .col-fecha { width: 100px; }
            .col-estado { width: 80px; }
            .col-municipio { width: 120px; }
            .col-parroquia { width: 120px; }
            .col-sector { width: 120px; }
            .col-cedula { width: 120px; }
            .col-nombre { width: 150px; }
            .col-telefono { width: 100px; }
            .col-genero { width: 80px; }
            .col-edad { width: 60px; }
            .col-peso { width: 70px; }
            .col-talla { width: 70px; }
            .col-imc { width: 70px; }
            .col-diagnostico { width: 150px; }
            .col-cbi { width: 70px; }
            .col-cci { width: 70px; }
            .col-lactando { width: 70px; }
            .col-gestante { width: 70px; }
            .col-semanas { width: 80px; }
            .col-observacion { width: 200px; }
        </style>
    </head>
    <body>';
    
    // CABECERA DEL REPORTE
    echo '<div class="titulo">INSTITUTO NACIONAL DE NUTRICIÓN</div>';
    echo '<div class="subtitulo">REPORTE CONSOLIDADO DE BENEFICIARIOS</div>';
    echo '<div class="fecha">Generado: ' . date('d/m/Y H:i:s') . '</div>';
    
    // FILTROS APLICADOS
    if (!empty($filtros)) {
        $filtros_texto = [];
        if (!empty($filtros['municipio'])) $filtros_texto[] = '<strong>Municipio:</strong> ' . $filtros['municipio'];
        if (!empty($filtros['parroquia'])) $filtros_texto[] = '<strong>Parroquia:</strong> ' . $filtros['parroquia'];
        if (!empty($filtros['sector'])) $filtros_texto[] = '<strong>Sector:</strong> ' . $filtros['sector'];
        if (!empty($filtros['caso'])) $filtros_texto[] = '<strong>Caso:</strong> ' . $filtros['caso'];
        
        echo '<div class="filtros"><strong>Filtros aplicados:</strong> ' . implode(' | ', $filtros_texto) . '</div>';
    }
    
    // TABLA DE DATOS
    echo '<table>';
    
    // ENCABEZADOS
    echo '<thead><tr>';
    echo '<th class="col-nro">Cédula Beneficiario</th>';  // Cambiado de Nro Procura
    echo '<th class="col-fecha">Fecha Registro</th>';      // Cambiado de Fecha Entrega
    echo '<th class="col-estado">Estado</th>';
    echo '<th class="col-municipio">Municipio</th>';
    echo '<th class="col-parroquia">Parroquia</th>';
    echo '<th class="col-sector">Sector</th>';
    echo '<th class="col-cedula">Cédula Representante</th>';
    echo '<th class="col-nombre">Nombre Representante</th>';
    echo '<th class="col-telefono">Número Contacto</th>';
    echo '<th class="col-cedula">Cédula Beneficiario</th>';
    echo '<th class="col-nombre">Nombre Beneficiario</th>';
    echo '<th class="col-genero">Género</th>';
    echo '<th class="col-fecha">Fecha Nacimiento</th>';
    echo '<th class="col-edad">Edad Actual</th>';
    echo '<th class="col-peso">Peso (kg)</th>';
    echo '<th class="col-talla">Talla (cm)</th>';
    echo '<th class="col-imc">IMC</th>';
    echo '<th class="col-diagnostico">Diagnóstico</th>';
    echo '<th class="col-cbi">CBI (mm)</th>';
    echo '<th class="col-cci">CCI Cintura</th>';
    echo '<th class="col-lactando">Lactando</th>';
    echo '<th class="col-gestante">Gestante</th>';
    echo '<th class="col-semanas">Semanas Gestación</th>';
    echo '<th class="col-observacion">Observación/Status</th>';
    echo '</tr></thead>';
    
    echo '<tbody>';
    
    $contador = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determinar clase según caso
        $clase_fila = '';
        if (strpos($row['diagnostico'] ?? '', 'Caso 1') !== false) {
            $clase_fila = 'caso1';
        } elseif (strpos($row['diagnostico'] ?? '', 'Caso 2') !== false) {
            $clase_fila = 'caso2';
        } elseif (strpos($row['diagnostico'] ?? '', 'Caso 3') !== false) {
            $clase_fila = 'caso3';
        }
        
        // Determinar clase IMC
        $imc_clase = '';
        $imc_valor = floatval($row['imc'] ?? 0);
        if ($imc_valor > 0) {
            if ($imc_valor < 18.5) $imc_clase = 'imc-bajo';
            elseif ($imc_valor < 25) $imc_clase = 'imc-normal';
            elseif ($imc_valor < 30) $imc_clase = 'imc-sobrepeso';
            else $imc_clase = 'imc-obeso';
        }
        
        echo '<tr class="' . $clase_fila . '">';
        
        // Nro Procura (usando cédula)
        echo '<td class="centrado">' . htmlspecialchars($row['nro_procura'] ?? '') . '</td>';
        
        // Fecha Entrega (usando fecha_registro)
        echo '<td class="fecha-celda">' . formatearFecha($row['fecha_entrega'] ?? '') . '</td>';
        
        // Estado
        echo '<td class="centrado">' . htmlspecialchars($row['estado'] ?? '') . '</td>';
        
        // Municipio
        echo '<td class="texto">' . htmlspecialchars($row['municipio'] ?? '') . '</td>';
        
        // Parroquia
        echo '<td class="texto">' . htmlspecialchars($row['parroquia'] ?? '') . '</td>';
        
        // Sector
        echo '<td class="texto">' . htmlspecialchars($row['sector'] ?? '') . '</td>';
        
        // Cédula Representante
        echo '<td class="texto">' . htmlspecialchars($row['cedula_representante'] ?? '') . '</td>';
        
        // Nombre Representante
        echo '<td class="texto">' . htmlspecialchars($row['nombre_representante'] ?? '') . '</td>';
        
        // Número Contacto
        echo '<td class="texto">' . htmlspecialchars($row['numero_contacto'] ?? '') . '</td>';
        
        // Cédula Beneficiario
        echo '<td class="texto">' . htmlspecialchars($row['cedula_beneficiario'] ?? '') . '</td>';
        
        // Nombre Beneficiario
        echo '<td class="texto">' . htmlspecialchars($row['nombre_beneficiario'] ?? '') . '</td>';
        
        // Género
        echo '<td class="centrado">' . formatearGenero($row['genero'] ?? '') . '</td>';
        
        // Fecha Nacimiento
        echo '<td class="fecha-celda">' . formatearFecha($row['fecha_nacimiento'] ?? '') . '</td>';
        
        // Edad Actual
        echo '<td class="centrado numero">' . htmlspecialchars($row['edad_actual'] ?? '') . '</td>';
        
        // Peso (kg)
        echo '<td class="numero">' . formatearNumero($row['peso_kg'] ?? '', 1) . '</td>';
        
        // Talla (cm)
        echo '<td class="numero">' . formatearNumero($row['talla_cm'] ?? '', 1) . '</td>';
        
        // IMC
        echo '<td class="numero ' . $imc_clase . '">' . formatearNumero($row['imc'] ?? '', 2) . '</td>';
        
        // Diagnóstico
        echo '<td class="texto">' . htmlspecialchars($row['diagnostico'] ?? '') . '</td>';
        
        // CBI (mm)
        echo '<td class="numero">' . formatearNumero($row['cbi_mm'] ?? '', 1) . '</td>';
        
        // CCI Cintura
        echo '<td class="numero">' . formatearNumero($row['cci_cintura'] ?? '', 1) . '</td>';
        
        // Lactando
        echo '<td class="centrado">' . formatearSiNo($row['lactando'] ?? '') . '</td>';
        
        // Gestante
        echo '<td class="centrado">' . formatearSiNo($row['gestante'] ?? '') . '</td>';
        
        // Semanas Gestación
        echo '<td class="centrado">' . htmlspecialchars($row['semanas_gestacion'] ?? '') . '</td>';
        
        // Observación Novedad
        echo '<td class="texto">' . htmlspecialchars($row['observacion_novedad'] ?? '') . '</td>';
        
        echo '</tr>';
        $contador++;
    }
    
    echo '</tbody>';
    
    // PIE DE TABLA
    echo '<tfoot>';
    echo '<tr><td colspan="24" class="total">Total de registros: ' . $contador . '</td></tr>';
    echo '</tfoot>';
    
    echo '</table>';
    
    // PIE DE PÁGINA
    echo '<div style="margin-top: 20px; font-size: 10px; color: #666; text-align: center;">';
    echo 'Reporte generado automáticamente por el Sistema de Beneficiarios del INN<br>';
    echo '© ' . date('Y') . ' - Instituto Nacional de Nutrición';
    echo '</div>';
    
    echo '</body></html>';
    
} catch (Exception $e) {
    // Mostrar error más detallado
    echo "Error en la consulta SQL: " . $e->getMessage() . "<br>";
    echo "Consulta SQL: " . $sql . "<br>";
    echo "Parámetros: " . print_r($params, true) . "<br>";
}

// FUNCIONES AUXILIARES
function formatearFecha($fecha) {
    if (empty($fecha) || $fecha == '0000-00-00') {
        return '';
    }
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }
    
    return date('d/m/Y', $timestamp);
}

function formatearNumero($valor, $decimales = 0) {
    if (!is_numeric($valor)) {
        return $valor;
    }
    
    return number_format(floatval($valor), $decimales, ',', '.');
}

function formatearGenero($genero) {
    $genero = strtoupper(trim($genero));
    
    if ($genero == 'M' || $genero == 'MASC' || $genero == 'MASCULINO') {
        return 'M';
    } elseif ($genero == 'F' || $genero == 'FEM' || $genero == 'FEMENINO') {
        return 'F';
    }
    
    return $genero;
}

function formatearSiNo($valor) {
    $valor = strtolower(trim($valor));
    
    if ($valor == '1' || $valor == 'si' || $valor == 'sí' || $valor == 'true') {
        return 'Sí';
    } elseif ($valor == '0' || $valor == 'no' || $valor == 'false') {
        return 'No';
    }
    
    return $valor;
}
?>