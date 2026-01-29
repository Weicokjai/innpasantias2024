<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$currentPage = 'Beneficiarios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Rutas absolutas
$base_dir = dirname(__FILE__, 3);

// Incluir dependencias
require_once $base_dir . '/config/database.php';
require_once $base_dir . '/components/beneficiaries/BeneficiarioModel.php';
require_once $base_dir . '/components/beneficiaries/BeneficiarioController.php';

try {
    // Inicializar conexión
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    // Inicializar controlador
    $controller = new BeneficiarioController($db);
    
    // Manejar solicitudes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->handleRequest();
    }
    
    // Obtener datos para la vista
    $beneficiarios = $controller->getAllBeneficiariosFormatted();
    $nuevosIngresos = $controller->getNuevosIngresosFormatted();
    
    // Obtener datos para filtros
    $filtrosData = $controller->getFiltrosData();
    $municipios = $filtrosData['municipios'];
    $parroquiasPorMunicipio = $filtrosData['parroquias_por_municipio'];
    $sectoresPorParroquia = $filtrosData['sectores_por_parroquia'];
    
    // Convertir a JSON para JavaScript
    $parroquias_json = json_encode($parroquiasPorMunicipio);
    $sectores_json = json_encode($sectoresPorParroquia);
    
    // Contadores
    $totalBeneficiarios = count($beneficiarios);
    $totalNuevosIngresos = count($nuevosIngresos);
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Función para escape seguro de HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para formatear números
function formatearNumero($valor, $decimales = 2) {
    if ($valor === null || $valor === '' || $valor === false || $valor == 0) {
        return 'N/A';
    }
    $numero = floatval($valor);
    return number_format($numero, $decimales, ',', '.');
}

// Formatear datos de beneficiarios
foreach($beneficiarios as &$beneficiario) {
    // Mapear los campos correctamente
    $beneficiario['peso'] = formatearNumero($beneficiario['peso_kg'] ?? null, 1);
    $beneficiario['talla'] = formatearNumero($beneficiario['talla_cm'] ?? null, 1);
    $beneficiario['cbi'] = formatearNumero($beneficiario['cbi_mm'] ?? null, 1);
    $beneficiario['imc'] = formatearNumero($beneficiario['imc'] ?? null, 2);
    $beneficiario['caso'] = $beneficiario['situacion_dx'] ?? 'N/A';
    $beneficiario['estado'] = $beneficiario['status'] ?? 'Activo';
    // Asegurar que el género tenga un valor por defecto
    $beneficiario['genero'] = $beneficiario['genero'] ?? '';
    // Asegurar que las condiciones femeninas tengan valores por defecto
    $beneficiario['condicion_femenina'] = $beneficiario['condicion_femenina'] ?? 'nada';
    $beneficiario['fecha_nacimiento_bebe'] = $beneficiario['fecha_nacimiento_bebe'] ?? '';
    $beneficiario['semanas_gestacion'] = $beneficiario['semanas_gestacion'] ?? '';
}

// Formatear datos de nuevos ingresos
foreach($nuevosIngresos as &$ingreso) {
    $ingreso['peso'] = formatearNumero($ingreso['peso_kg'] ?? $ingreso['peso'] ?? null, 1);
    $ingreso['talla'] = formatearNumero($ingreso['talla_cm'] ?? $ingreso['talla'] ?? null, 1);
    $ingreso['cbi'] = formatearNumero($ingreso['cbi_mm'] ?? $ingreso['cbi'] ?? null, 1);
    $ingreso['imc'] = formatearNumero($ingreso['imc'] ?? null, 2);
    $ingreso['caso'] = $ingreso['situacion_dx'] ?? 'N/A';
    // Asegurar que el género tenga un valor por defecto
    $ingreso['genero'] = $ingreso['genero'] ?? '';
    // Asegurar que las condiciones femeninas tengan valores por defecto
    $ingreso['condicion_femenina'] = $ingreso['condicion_femenina'] ?? 'nada';
    $ingreso['fecha_nacimiento_bebe'] = $ingreso['fecha_nacimiento_bebe'] ?? '';
    $ingreso['semanas_gestacion'] = $ingreso['semanas_gestacion'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiarios - Instituto Nacional de Nutrición</title>
    <link href="/innpasantias2024/public/assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        /* ESTILOS GENERALES */
        .modal-backup { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 10000; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .modal-content-backup { 
            background: white; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 900px; 
            max-height: 90vh; 
            overflow-y: auto; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); 
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid #d1d5db;
            border-bottom: none;
            border-radius: 0.5rem 0.5rem 0 0;
            margin-right: 0.5rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .tab-button.active {
            background: #16a34a;
            color: white;
            border-color: #16a34a;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .badge-online {
            background-color: #dcfce7;
            color: #16a34a;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .badge-offline {
            background-color: #fef3c7;
            color: #d97706;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .filter-active {
            background-color: #dcfce7 !important;
            border-color: #22c55e !important;
        }
        
        .status-offline {
            background-color: #fef3c7 !important;
            color: #d97706 !important;
        }
        
        /* Estilos específicos para modal de edición */
        .modal-edit {
            z-index: 10001;
        }
        
        .modal-content-edit {
            max-width: 1000px;
        }
        
        /* Personalización de DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #4b5563 !important;
            font-size: 0.875rem !important;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
            margin-left: 0.5rem !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
            margin: 0 0.125rem !important;
            background: white !important;
            color: #4b5563 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #16a34a !important;
            color: white !important;
            border-color: #16a34a !important;
        }
        
        /* TABLA CON SCROLL HORIZONTAL */
        .dataTables_wrapper {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #tabla-beneficiarios {
            width: 100% !important;
            min-width: 1200px;
            border-collapse: collapse !important;
        }
        
        /* Estilos de la tabla */
        #tabla-beneficiarios thead th {
            background-color: #f9fafb !important;
            border-bottom: 2px solid #e5e7eb !important;
            padding: 0.75rem 1rem !important;
            text-align: left !important;
            font-weight: 600 !important;
            color: #374151 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        
        #tabla-beneficiarios tbody td {
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid #e5e7eb !important;
            vertical-align: top !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: normal !important;
            word-wrap: break-word !important;
        }
        
        #tabla-beneficiarios tbody tr:hover {
            background-color: #f9fafb !important;
        }
        
        /* Deshabilitar responsive de DataTables */
        .dtr-details,
        .dtr-title,
        .dtr-data,
        .dtr-inline,
        .dtr-column {
            display: none !important;
        }
        
        /* Forzar que todas las columnas sean visibles */
        #tabla-beneficiarios th,
        #tabla-beneficiarios td {
            display: table-cell !important;
        }
        
        /* Contenedor con scroll horizontal */
        .tabla-contenedor {
            width: 100%;
            max-width: 100%;
            overflow-x: auto !important;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Estilos para números */
        .numero-formateado {
            font-family: monospace;
            font-weight: 600;
        }
        
        .imc-bajo {
            color: #f59e0b;
            font-weight: 700;
        }
        
        .imc-normal {
            color: #10b981;
            font-weight: 700;
        }
        
        .imc-sobrepeso {
            color: #f97316;
            font-weight: 700;
        }
        
        .imc-obeso {
            color: #ef4444;
            font-weight: 700;
        }
        
        /* Distribución de columnas */
        #tabla-beneficiarios th:nth-child(1),
        #tabla-beneficiarios td:nth-child(1) {
            width: 15% !important;
            min-width: 150px;
        }
        
        #tabla-beneficiarios th:nth-child(2),
        #tabla-beneficiarios td:nth-child(2) {
            width: 18% !important;
            min-width: 180px;
        }
        
        #tabla-beneficiarios th:nth-child(3),
        #tabla-beneficiarios td:nth-child(3) {
            width: 20% !important;
            min-width: 200px;
        }
        
        #tabla-beneficiarios th:nth-child(4),
        #tabla-beneficiarios td:nth-child(4) {
            width: 20% !important;
            min-width: 200px;
        }
        
        #tabla-beneficiarios th:nth-child(5),
        #tabla-beneficiarios td:nth-child(5) {
            width: 12% !important;
            min-width: 120px;
        }
        
        #tabla-beneficiarios th:nth-child(6),
        #tabla-beneficiarios td:nth-child(6) {
            width: 15% !important;
            min-width: 150px;
            text-align: center;
        }
        
        /* Texto truncado */
        .texto-truncado {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            max-width: 100%;
        }
        
        /* ==================== */
        /* BOTONES DE DATATABLES CORREGIDOS */
        /* ==================== */
        
        /* Contenedor de botones */
        .dt-buttons {
            display: inline-block !important;
            float: none !important;
            margin: 0 0 15px 0 !important;
            padding: 0 !important;
            background: none !important;
            border: none !important;
        }
        
        /* Botones principales */
        .dt-button {
            position: relative !important;
            display: inline-block !important;
            min-width: 80px !important;
            padding: 10px 16px !important;
            margin: 0 5px 5px 0 !important;
            font-family: inherit !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            text-align: center !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
            cursor: pointer !important;
            user-select: none !important;
            border: 1px solid transparent !important;
            border-radius: 8px !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
            color: white !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Hover effect */
        .dt-button:hover {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15) !important;
        }
        
        /* Active state */
        .dt-button:active {
            transform: translateY(0) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Botón Excel */
        .dt-button.buttons-excel {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
        
        .dt-button.buttons-excel:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
        }
        
        /* Botón Imprimir */
        .dt-button.buttons-print {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        }
        
        .dt-button.buttons-print:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        }
        
        /* Botón Copiar */
        .dt-button.buttons-copy {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        }
        
        .dt-button.buttons-copy:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
        }
        
        /* Botón PDF */
        .dt-button.buttons-pdf {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        
        .dt-button.buttons-pdf:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        }
        
        /* Botón Exportar Consolidado */
        .dt-button.buttons-consolidado {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        }
        
        .dt-button.buttons-consolidado:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        }
        
        /* Botón Formato PDF */
        .dt-button.buttons-formatopdf {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        }
        
        .dt-button.buttons-formatopdf:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%) !important;
        }
        
        /* Iconos dentro de botones */
        .dt-button i {
            margin-right: 6px !important;
            font-size: 14px !important;
        }
        
        /* Color púrpura para botones personalizados */
        .bg-purple-600 {
            background-color: #7c3aed !important;
        }
        
        .bg-purple-600:hover {
            background-color: #6d28d9 !important;
        }
        
        /* Responsive para botones */
        @media screen and (max-width: 768px) {
            .dt-buttons {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                justify-content: center !important;
            }
            
            .dt-button {
                min-width: 70px !important;
                padding: 8px 12px !important;
                font-size: 13px !important;
                margin: 0 !important;
            }
        }
        
        @media screen and (max-width: 640px) {
            .dt-button {
                padding: 6px 10px !important;
                font-size: 12px !important;
                min-width: 60px !important;
            }
            
            .dt-button i {
                margin-right: 4px !important;
                font-size: 12px !important;
            }
            
            /* Ajustes de tabla en móviles */
            #tabla-beneficiarios th,
            #tabla-beneficiarios td {
                padding: 0.5rem 0.75rem !important;
                font-size: 0.875rem !important;
            }
            
            #tabla-beneficiarios th:nth-child(1),
            #tabla-beneficiarios td:nth-child(1) { min-width: 140px !important; }
            
            #tabla-beneficiarios th:nth-child(2),
            #tabla-beneficiarios td:nth-child(2) { min-width: 160px !important; }
            
            #tabla-beneficiarios th:nth-child(3),
            #tabla-beneficiarios td:nth-child(3) { min-width: 180px !important; }
            
            #tabla-beneficiarios th:nth-child(4),
            #tabla-beneficiarios td:nth-child(4) { min-width: 180px !important; }
            
            #tabla-beneficiarios th:nth-child(5),
            #tabla-beneficiarios td:nth-child(5) { min-width: 110px !important; }
            
            #tabla-beneficiarios th:nth-child(6),
            #tabla-beneficiarios td:nth-child(6) { min-width: 140px !important; }
        }
        
        /* Botón personalizado para exportar consolidado */
        .btn-exportar-consolidado {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: white !important;
            border: none !important;
            padding: 10px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        
        .btn-exportar-consolidado:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15) !important;
        }
        
        /* Botón personalizado para formato PDF */
        .btn-formato-pdf {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
            border: none !important;
            padding: 10px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        
        .btn-formato-pdf:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15) !important;
        }
        
        /* Mensaje de filtros aplicados */
        .mensaje-filtros {
            background-color: #dbeafe;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .mensaje-filtros.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .mensaje-filtros strong {
            color: #1e40af;
        }
        
        /* Loader para exportación */
        .export-loader {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4F81BD;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            font-weight: bold;
        }
        
        /* Loader para formato PDF */
        .pdf-loader {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc2626;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            font-weight: bold;
        }
        
        /* ==================== */
        /* ESTILOS PARA MODAL NUEVO INGRESO */
        /* ==================== */
        
        .modal-nuevo-ingreso {
            z-index: 10002;
        }
        
        .modal-header-nuevo-ingreso {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header-nuevo-ingreso h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-header-nuevo-ingreso button {
            color: white;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .modal-header-nuevo-ingreso button:hover {
            opacity: 0.8;
        }
        
        /* Estilos para secciones del formulario */
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .form-section-title i {
            color: #f59e0b;
        }
        
        /* Estilos para grupos de formulario */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group .required::after {
            content: " *";
            color: #ef4444;
        }
        
        .form-group .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-group .form-control:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .form-group .form-control:disabled {
            background-color: #f9fafb;
            color: #6b7280;
        }
        
        .form-group .form-control[readonly] {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        
        /* Estilos para radio buttons personalizados */
        .radio-group {
            display: flex;
            gap: 2rem;
            margin-top: 0.5rem;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem 0;
        }
        
        .radio-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            cursor: pointer;
            accent-color: #f59e0b;
        }
        
        .radio-text {
            font-size: 1rem;
            color: #374151;
            font-weight: 500;
        }
        
        /* Estilos para condicionales de género */
        .condicional-genero {
            display: none;
            animation: fadeIn 0.3s ease-in;
            background-color: #fffbeb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #fef3c7;
            margin-top: 1.5rem;
        }
        
        .condicional-genero.show {
            display: block;
        }
        
        .condicional-title {
            font-size: 1rem;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Estilos para opciones de condición femenina */
        .condicion-femenina-group {
            margin-top: 1rem;
        }
        
        .condicion-femenina-radio {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .condicion-femenina-radio input[type="radio"] {
            margin-right: 0.5rem;
            accent-color: #d97706;
        }
        
        .condicion-femenina-radio label {
            font-weight: 500;
            color: #374151;
            cursor: pointer;
        }
        
        .condicion-subseccion {
            display: none;
            margin-top: 1rem;
            padding-left: 1.5rem;
            border-left: 2px solid #fbbf24;
            background-color: #fffbeb;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .condicion-subseccion.show {
            display: block;
            animation: slideIn 0.3s ease-in;
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateX(-10px);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Estilos para sección de antropometría con colores */
        .antropometria-section {
            background-color: #eff6ff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #bfdbfe;
        }
        
        .antropometria-title {
            color: #1e40af;
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }
        
        .antropometria-subtitle {
            color: #3b82f6;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        
        .antropometria-input {
            border-color: #93c5fd !important;
            background-color: white;
        }
        
        .antropometria-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        .imc-calculado {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            font-weight: 600 !important;
            border-color: #bfdbfe !important;
        }
        
        /* Estilos para sección de situación */
        .situacion-section {
            background-color: #f0fdf4;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #bbf7d0;
        }
        
        .situacion-title {
            color: #15803d;
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        .caso-option {
            background-color: white;
            border: 2px solid #d1fae5;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .caso-option:hover {
            border-color: #34d399;
            transform: translateY(-2px);
        }
        
        .caso-option.selected {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .caso-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .caso-radio {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: #10b981;
        }
        
        .caso-name {
            font-weight: 600;
            color: #065f46;
            font-size: 1.125rem;
        }
        
        .caso-description {
            color: #047857;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        /* Estilos para botones del modal */
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .btn-cancelar {
            padding: 0.75rem 1.5rem;
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            color: #374151;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-cancelar:hover {
            background-color: #f9fafb;
            border-color: #9ca3af;
        }
        
        .btn-guardar-nuevo-ingreso {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-guardar-nuevo-ingreso:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(214, 158, 46, 0.2);
        }
        
        /* Estilos para botones en la pestaña de nuevos ingresos */
        .btn-registrar-nuevo-ingreso {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-registrar-nuevo-ingreso:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(214, 158, 46, 0.2);
        }
        
        .btn-incorporar-todo {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-incorporar-todo:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        
        /* Estilos para iconos dentro de los botones */
        .btn-icon {
            font-size: 1rem;
        }
        
        /* Grid responsive para formulario */
        @media (max-width: 768px) {
            .grid-responsive {
                grid-template-columns: 1fr !important;
            }
        }
        
        /* Estilos para campos requeridos */
        .required-label {
            position: relative;
        }
        
        .required-label::after {
            content: "*";
            color: #ef4444;
            margin-left: 4px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include '../../components/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Beneficiarios</h2>
                </div>
            </header>

            <main class="flex-1 overflow-x-auto p-6">
                <?php if(isset($_SESSION['message'])): ?>
                <div class="bg-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                    <?php echo e($_SESSION['message']); ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>

                <!-- Pestañas -->
                <div class="mb-6">
                    <div class="flex border-b border-gray-200">
                        <button class="tab-button active" onclick="cambiarTab('beneficiarios')">
                            <i class="fas fa-users mr-2"></i>Beneficiarios Registrados
                            <span class="badge-online"><?php echo e($totalBeneficiarios); ?></span>
                        </button>
                        <button class="tab-button" onclick="cambiarTab('nuevos-ingresos')">
                            <i class="fas fa-user-plus mr-2"></i>Nuevos Ingresos
                            <span class="badge-offline"><?php echo e($totalNuevosIngresos); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Contenido de Beneficiarios Registrados -->
                <div id="tab-beneficiarios" class="tab-content active">
                    <!-- Filtros para Beneficiarios -->
                    <div class="bg-white rounded-xl shadow p-6 mb-6">
                        <div class="mb-4">
                            <h4 class="text-lg font-medium text-gray-800 mb-2">Filtros de Búsqueda</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                                <select id="filtro-municipio" class="filtro-select w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todos</option>
                                    <?php foreach($municipios as $municipio): ?>
                                        <option value="<?php echo e($municipio); ?>"><?php echo e($municipio); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parroquia</label>
                                <select id="filtro-parroquia" class="filtro-select w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                    <option value="">Primero seleccione municipio</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sector</label>
                                <select id="filtro-sector" class="filtro-select w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                    <option value="">Primero seleccione parroquia</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso</label>
                                <select id="filtro-caso" class="filtro-select w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todos</option>
                                    <option value="1">Caso 1</option>
                                    <option value="2">Caso 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <button id="btn-aplicar-filtros" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 whitespace-nowrap">
                                <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                            </button>
                            <button id="btn-limpiar-filtros" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200 whitespace-nowrap">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </button>
                            <!-- BOTÓN NUEVO PARA EXPORTAR CONSOLIDADO CON COLOR PÚRPURA -->
                            <button onclick="exportarConsolidadoExcel()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200 whitespace-nowrap">
                                <i class="fas fa-file-excel mr-2"></i>Exportar Consolidado
                            </button>
                            <!-- BOTÓN PARA GENERAR FORMATO PDF -->
                            <button onclick="generarFormatoPDF()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200 whitespace-nowrap">
                                <i class="fas fa-file-pdf mr-2"></i>Formato para Imprimir
                            </button>
                        </div>
                    </div>

                    <!-- Mensaje de filtros aplicados -->
                    <div id="mensaje-filtros" class="mensaje-filtros">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Filtros aplicados:</strong> <span id="texto-filtros">Sin filtros</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Beneficiarios -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold">Lista de Beneficiarios Registrados</h3>
                                    <p class="text-sm text-gray-600">Total: <span id="total-registros"><?php echo e($totalBeneficiarios); ?></span> registros</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6 tabla-contenedor">
                            <?php if(empty($beneficiarios)): ?>
                                <div class="text-center py-12">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                                        <i class="fas fa-users text-green-600 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 mb-2">No hay beneficiarios registrados</h4>
                                    <p class="text-gray-600 max-w-md mx-auto">
                                        No se han encontrado beneficiarios activos en la base de datos.
                                    </p>
                                    <div class="mt-6">
                                        <button onclick="abrirModalNuevoBeneficiario()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                            <i class="fas fa-user-plus mr-2"></i>Registrar Primer Beneficiario
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <table id="tabla-beneficiarios" class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Representante</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Antropometría</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caso</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach($beneficiarios as $beneficiario): 
                                            // Determinar clase CSS para IMC
                                            $imc_class = 'numero-formateado';
                                            if (!empty($beneficiario['imc'])) {
                                                $imc_valor = floatval(str_replace(',', '.', $beneficiario['imc']));
                                                
                                                if ($imc_valor < 18.5) {
                                                    $imc_class .= ' imc-bajo';
                                                } elseif ($imc_valor >= 18.5 && $imc_valor < 25) {
                                                    $imc_class .= ' imc-normal';
                                                } elseif ($imc_valor >= 25 && $imc_valor < 30) {
                                                    $imc_class .= ' imc-sobrepeso';
                                                } elseif ($imc_valor >= 30) {
                                                    $imc_class .= ' imc-obeso';
                                                }
                                            }
                                        ?>
                                        <tr class="hover:bg-gray-50" 
                                            data-municipio="<?php echo e($beneficiario['municipio'] ?? ''); ?>"
                                            data-parroquia="<?php echo e($beneficiario['parroquia'] ?? ''); ?>"
                                            data-sector="<?php echo e($beneficiario['sector'] ?? ''); ?>"
                                            data-caso="<?php echo e($beneficiario['caso'] ?? ''); ?>"
                                            data-id="<?php echo e($beneficiario['id_beneficiario'] ?? ''); ?>"
                                            data-peso="<?php echo e(!empty($beneficiario['peso']) ? str_replace(',', '.', $beneficiario['peso']) : ''); ?>"
                                            data-talla="<?php echo e(!empty($beneficiario['talla']) ? str_replace(',', '.', $beneficiario['talla']) : ''); ?>"
                                            data-cbi="<?php echo e(!empty($beneficiario['cbi']) ? str_replace(',', '.', $beneficiario['cbi']) : ''); ?>"
                                            data-imc="<?php echo e(!empty($beneficiario['imc']) ? str_replace(',', '.', $beneficiario['imc']) : ''); ?>"
                                            data-nombre="<?php echo e($beneficiario['nombre_beneficiario'] ?? ''); ?>"
                                            data-apellido="<?php echo e($beneficiario['apellido_beneficiario'] ?? ''); ?>"
                                            data-cedula="<?php echo e($beneficiario['cedula_beneficiario'] ?? ''); ?>"
                                            data-edad="<?php echo e($beneficiario['edad'] ?? ''); ?>"
                                            data-genero="<?php echo e($beneficiario['genero'] ?? ''); ?>"
                                            data-fecha-nacimiento="<?php echo e($beneficiario['fecha_nacimiento'] ?? ''); ?>"
                                            data-condicion-femenina="<?php echo e($beneficiario['condicion_femenina'] ?? ''); ?>"
                                            data-fecha-nacimiento-bebe="<?php echo e($beneficiario['fecha_nacimiento_bebe'] ?? ''); ?>"
                                            data-semanas-gestacion="<?php echo e($beneficiario['semanas_gestacion'] ?? ''); ?>"
                                            data-nombre-representante="<?php echo e($beneficiario['nombre_representante'] ?? ''); ?>"
                                            data-apellido-representante="<?php echo e($beneficiario['apellido_representante'] ?? ''); ?>"
                                            data-cedula-representante="<?php echo e($beneficiario['cedula_representante'] ?? ''); ?>"
                                            data-telefono-representante="<?php echo e($beneficiario['telefono_representante'] ?? ''); ?>"
                                            data-nombre-clap="<?php echo e($beneficiario['nombre_clap'] ?? ''); ?>"
                                            data-nombre-comuna="<?php echo e($beneficiario['nombre_comuna'] ?? ''); ?>">
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-900 texto-truncado" title="<?php echo e($beneficiario['municipio'] ?? 'Sin municipio'); ?>">
                                                    <?php echo e($beneficiario['municipio'] ?? 'Sin municipio'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 texto-truncado" title="<?php echo e($beneficiario['parroquia'] ?? 'Sin parroquia'); ?>">
                                                    <?php echo e($beneficiario['parroquia'] ?? 'Sin parroquia'); ?>
                                                </div>
                                                <div class="text-xs text-gray-400 texto-truncado" title="<?php echo e($beneficiario['sector'] ?? 'Sin sector'); ?>">
                                                    <?php echo e($beneficiario['sector'] ?? 'Sin sector'); ?>
                                                </div>
                                            </td>
                                            
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-900 texto-truncado" title="<?php echo e($beneficiario['nombre_representante'] ?? ''); ?>">
                                                    <?php echo e($beneficiario['nombre_representante'] ?? 'Sin representante'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 texto-truncado" title="<?php echo e($beneficiario['cedula_representante'] ?? ''); ?>">
                                                    <?php echo e($beneficiario['cedula_representante'] ?? 'Sin cédula'); ?>
                                                </div>
                                            </td>
                                            
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-child text-blue-600 text-xs"></i>
                                                    </div>
                                                    <div class="ml-3 min-w-0">
                                                        <div class="text-sm font-medium text-gray-900 texto-truncado" title="<?php echo e(($beneficiario['nombre_beneficiario'] ?? '') . ' ' . ($beneficiario['apellido_beneficiario'] ?? '')); ?>">
                                                            <?php echo e(($beneficiario['nombre_beneficiario'] ?? 'Sin nombre') . ' ' . ($beneficiario['apellido_beneficiario'] ?? '')); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500 texto-truncado" title="<?php echo e($beneficiario['cedula_beneficiario'] ?? ''); ?>">
                                                            <?php echo e($beneficiario['cedula_beneficiario'] ?? 'Sin cédula'); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-400">
                                                            <?php echo e($beneficiario['edad'] ?? ''); ?> años
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td class="px-4 py-3">
                                                <div class="text-xs">
                                                    <div class="flex justify-between">
                                                        <span>Peso:</span> 
                                                        <span class="font-medium numero-formateado"><?php echo e($beneficiario['peso'] ?? 'N/A'); ?> kg</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Talla:</span> 
                                                        <span class="font-medium numero-formateado"><?php echo e($beneficiario['talla'] ?? 'N/A'); ?> cm</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>CBI:</span> 
                                                        <span class="font-medium numero-formateado"><?php echo e($beneficiario['cbi'] ?? 'N/A'); ?> mm</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>IMC:</span> 
                                                        <span class="font-medium <?php echo $imc_class; ?>"><?php echo e($beneficiario['imc'] ?? 'N/A'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($beneficiario['caso'] ?? '') === '1' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                    Caso <?php echo e($beneficiario['caso'] ?? 'N/A'); ?>
                                                </span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?php echo e($beneficiario['estado'] ?? 'Activo'); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            
                                            <td class="px-4 py-3 text-sm font-medium">
                                                <div class="flex space-x-1 justify-center">
                                                    <button onclick="editarBeneficiario('<?php echo e($beneficiario['id_beneficiario']); ?>', this)" 
                                                            class="btn-editar-beneficiario text-blue-600 hover:text-blue-900 transition duration-150 p-1" 
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="text-orange-600 hover:text-orange-900 transition duration-150 p-1" title="Asignar beneficio">
                                                        <i class="fas fa-gift"></i>
                                                    </button>
                                                    <button onclick="eliminarBeneficiario('<?php echo e($beneficiario['cedula_beneficiario']); ?>', '<?php echo e($beneficiario['nombre_beneficiario']); ?>')" class="text-red-600 hover:text-red-900 transition duration-150 p-1" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contenido de Nuevos Ingresos -->
                <div id="tab-nuevos-ingresos" class="tab-content">
                    <!-- Botón Nuevo Ingreso -->
                    <div class="mb-6 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Registre aquí a personas que no están en la base de datos principal
                        </div>
                        <div class="flex gap-2">
                            <button onclick="abrirModalNuevoIngreso()" class="btn-registrar-nuevo-ingreso">
                                <i class="fas fa-user-plus btn-icon"></i>Registrar Nuevo Ingreso
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Nuevos Ingresos -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold">Registros de Nuevos Ingresos</h3>
                            <p class="text-sm text-gray-600">Total: <span id="total-registros-ni"><?php echo e($totalNuevosIngresos); ?></span> personas pendientes de incorporar</p>
                        </div>
                        
                        <div class="p-6">
                            <?php if(empty($nuevosIngresos)): ?>
                                <div class="text-center py-12">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
                                        <i class="fas fa-user-plus text-yellow-600 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 mb-2">No hay nuevos ingresos registrados</h4>
                                    <p class="text-gray-600 max-w-md mx-auto">
                                        Registre aquí a personas que no se encuentran en la base de datos principal.
                                        Estos registros se guardarán temporalmente hasta que sean incorporados al sistema principal.
                                    </p>
                                    <div class="mt-6">
                                        <button onclick="abrirModalNuevoIngreso()" class="btn-registrar-nuevo-ingreso">
                                            <i class="fas fa-user-plus btn-icon"></i>Registrar Primer Nuevo Ingreso
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="tabla-contenedor">
                                    <table id="tabla-nuevos-ingresos" class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Registro</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach($nuevosIngresos as $index => $registro): ?>
                                            <tr class="hover:bg-gray-50" data-index="<?php echo $index; ?>">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo e(date('d/m/Y H:i', strtotime($registro['fecha_registro']))); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                            <i class="fas fa-user-clock text-yellow-600 text-xs"></i>
                                                        </div>
                                                        <div class="ml-3 min-w-0">
                                                            <div class="text-sm font-medium text-gray-900 texto-truncado">
                                                                <?php echo e($registro['nombre_beneficiario'] . ' ' . $registro['apellido_beneficiario']); ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500 texto-truncado">
                                                                <?php echo e($registro['cedula_beneficiario']); ?>
                                                            </div>
                                                            <div class="text-xs text-gray-400">
                                                                <?php echo e($registro['edad']); ?> años
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm text-gray-900 texto-truncado"><?php echo e($registro['sector']); ?></div>
                                                    <div class="text-xs text-gray-500 texto-truncado"><?php echo e($registro['parroquia']); ?></div>
                                                    <div class="text-xs text-gray-400 texto-truncado"><?php echo e($registro['municipio']); ?></div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-offline">
                                                        <i class="fas fa-clock mr-1"></i> Pendiente
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm font-medium">
                                                    <div class="flex space-x-1 justify-center">
                                                        <button onclick="incorporarRegistro('<?php echo $registro['cedula_beneficiario']; ?>')" class="text-blue-600 hover:text-blue-900 transition duration-150 p-1" title="Incorporar a base principal">
                                                            <i class="fas fa-database"></i>
                                                        </button>
                                                        <button onclick="editarRegistroNI('<?php echo $registro['cedula_beneficiario']; ?>')" class="text-green-600 hover:text-green-900 transition duration-150 p-1" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button onclick="eliminarRegistroNI('<?php echo $registro['cedula_beneficiario']; ?>')" class="text-red-600 hover:text-red-900 transition duration-150 p-1" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo Beneficiario (Online) -->
    <div id="nuevoBeneficiarioModal" class="modal-backup">
        <div class="modal-content-backup">
            <div class="bg-green-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-xl font-semibold" id="modal-titulo">Nuevo Beneficiario</h3>
                <button onclick="cerrarModal()" class="text-white hover:text-gray-200 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formNuevoBeneficiario" method="POST" class="p-6">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="tipo" id="form-tipo" value="online">
                <input type="hidden" id="parroquias_data" value='<?php echo e($parroquias_json); ?>'>
                <input type="hidden" id="sectores_data" value='<?php echo e($sectores_json); ?>'>
                
                <!-- Ubicación -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Ubicación y CLAP</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="municipio" class="block text-sm font-medium text-gray-700 mb-2 required">Municipio</label>
                            <select id="municipio" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar municipio</option>
                                <?php foreach($municipios as $municipio): ?>
                                    <option value="<?php echo e($municipio); ?>"><?php echo e($municipio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="parroquia" class="block text-sm font-medium text-gray-700 mb-2 required">Parroquia</label>
                            <select id="parroquia" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Primero seleccione municipio</option>
                            </select>
                        </div>
                        <div>
                            <label for="sector" class="block text-sm font-medium text-gray-700 mb-2 required">Sector donde vive</label>
                            <select id="sector" name="sector" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Primero seleccione parroquia</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="nombre_clap" class="block text-sm font-medium text-gray-700 mb-2">Nombre CLAP</label>
                            <input type="text" id="nombre_clap" name="nombre_clap" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: CLAP Libertador">
                        </div>
                        <div>
                            <label for="nombre_comuna" class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Comuna</label>
                            <input type="text" id="nombre_comuna" name="nombre_comuna" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Comuna 1">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Representante -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Datos del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="representante_nombre" class="block text-sm font-medium text-gray-700 mb-2 required">Nombre</label>
                            <input type="text" id="representante_nombre" name="representante_nombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: María">
                        </div>
                        <div>
                            <label for="representante_apellido" class="block text-sm font-medium text-gray-700 mb-2 required">Apellido</label>
                            <input type="text" id="representante_apellido" name="representante_apellido" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: González">
                        </div>
                        <div>
                            <label for="representante_cedula" class="block text-sm font-medium text-gray-700 mb-2 required">Cédula de Identidad</label>
                            <input type="text" id="representante_cedula" name="representante_cedula" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-12345678">
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full md:w-1/2">
                            <label for="representante_telefono" class="block text-sm font-medium text-gray-700 mb-2 required">Teléfono de Contacto</label>
                            <input type="tel" id="representante_telefono" name="representante_telefono" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 0412-1234567">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Beneficiario -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Datos del Beneficiario</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="nombres" class="block text-sm font-medium text-gray-700 mb-2 required">Nombre</label>
                            <input type="text" id="nombres" name="nombres" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Carlos">
                        </div>
                        <div>
                            <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2 required">Apellido</label>
                            <input type="text" id="apellidos" name="apellidos" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Rodríguez">
                        </div>
                        <div>
                            <label for="cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2 required">Cédula de Identidad</label>
                            <input type="text" id="cedula_beneficiario" name="cedula_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-87654321">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2 required">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="genero" class="block text-sm font-medium text-gray-700 mb-2 required">Género</label>
                            <select id="genero" name="genero" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" onchange="mostrarCondicionFemeninaNuevo()">
                                <option value="">Seleccionar</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div>
                            <label for="edad" class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <input type="number" id="edad" name="edad" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    
                    <!-- Condición femenina (solo si es Femenino) -->
                    <div id="condicion_femenina_nuevo" class="condicional-genero">
                        <div class="condicional-title">
                            <i class="fas fa-female"></i>
                            Condición Especial (Femenino)
                        </div>
                        <div class="condicion-femenina-group">
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="condicion_nada_nuevo" name="condicion_femenina" value="nada" checked onchange="mostrarSubcondicionNuevo()">
                                <label for="condicion_nada_nuevo">Ninguna condición especial</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="condicion_lactante_nuevo" name="condicion_femenina" value="lactante" onchange="mostrarSubcondicionNuevo()">
                                <label for="condicion_lactante_nuevo">Lactante</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="condicion_gestante_nuevo" name="condicion_femenina" value="gestante" onchange="mostrarSubcondicionNuevo()">
                                <label for="condicion_gestante_nuevo">Gestante</label>
                            </div>
                        </div>
                        
                        <!-- Subcondición para lactante -->
                        <div id="subcondicion_lactante_nuevo" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="fecha_nacimiento_bebe_nuevo" class="required-label">Fecha de nacimiento del bebé</label>
                                <input type="date" id="fecha_nacimiento_bebe_nuevo" name="fecha_nacimiento_bebe" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Se registrará la fecha de nacimiento del bebé para seguimiento.
                            </div>
                        </div>
                        
                        <!-- Subcondición para gestante -->
                        <div id="subcondicion_gestante_nuevo" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="semanas_gestacion_nuevo" class="required-label">Semanas de gestación</label>
                                <input type="number" id="semanas_gestacion_nuevo" name="semanas_gestacion" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" min="1" max="42" placeholder="Ej: 24">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Indique las semanas completas de gestación.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Antropometría -->
                <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-blue-800 mb-4 border-b pb-2">Antropometría</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="cbi_mm" class="block text-sm font-medium text-blue-700 mb-2 required">CBI (mm)</label>
                            <input type="number" id="cbi_mm" name="cbi_mm" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 12.5">
                        </div>
                        <div>
                            <label for="peso_kg" class="block text-sm font-medium text-blue-700 mb-2 required">Peso (Kg)</label>
                            <input type="number" id="peso_kg" name="peso_kg" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 25.5">
                        </div>
                        <div>
                            <label for="talla_cm" class="block text-sm font-medium text-blue-700 mb-2 required">Talla (cm)</label>
                            <input type="number" id="talla_cm" name="talla_cm" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 120.0">
                        </div>
                        <div>
                            <label for="imc" class="block text-sm font-medium text-blue-700 mb-2">IMC (Calculado)</label>
                            <input type="text" id="imc" name="imc" readonly class="w-full border border-blue-300 bg-blue-100 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                </div>
                
                <!-- Situación -->
                <div class="mb-6 bg-green-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-green-800 mb-4 border-b pb-2">Situación</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" id="caso_1" name="situacion_dx" value="1" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="caso_1" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 1
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="caso_2" name="situacion_dx" value="2" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="caso_2" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 2
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i><span id="btn-guardar-texto">Guardar Beneficiario</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Nuevo Ingreso (Offline) -->
    <div id="nuevoIngresoModal" class="modal-backup modal-nuevo-ingreso">
        <div class="modal-content-backup">
            <!-- Header del Modal -->
            <div class="modal-header-nuevo-ingreso">
                <h3>
                    <i class="fas fa-user-plus"></i>
                    Registrar Nuevo Ingreso
                </h3>
                <button onclick="cerrarModalNuevoIngreso()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Formulario -->
            <form id="formNuevoIngreso" method="POST" class="p-0">
                <input type="hidden" name="action" value="create_nuevo_ingreso">
                <input type="hidden" name="tipo" value="offline">
                
                <!-- Ubicación -->
                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Ubicación
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 grid-responsive">
                        <div class="form-group">
                            <label for="ni_municipio" class="required-label">Municipio</label>
                            <select id="ni_municipio" name="municipio" required class="form-control" onchange="cargarParroquiasNuevoIngreso()">
                                <option value="">Seleccionar municipio</option>
                                <?php foreach($municipios as $municipio): ?>
                                    <option value="<?php echo e($municipio); ?>"><?php echo e($municipio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ni_parroquia" class="required-label">Parroquia</label>
                            <select id="ni_parroquia" name="parroquia" required class="form-control" disabled onchange="cargarSectoresNuevoIngreso()">
                                <option value="">Primero seleccione municipio</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ni_sector" class="required-label">Sector</label>
                            <select id="ni_sector" name="sector" required class="form-control" disabled>
                                <option value="">Primero seleccione parroquia</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="form-group">
                            <label for="ni_clap">Nombre CLAP</label>
                            <input type="text" id="ni_clap" name="nombre_clap" class="form-control" placeholder="Ej: CLAP Libertador">
                        </div>
                        <div class="form-group">
                            <label for="ni_comuna">Nombre de la Comuna</label>
                            <input type="text" id="ni_comuna" name="nombre_comuna" class="form-control" placeholder="Ej: Comuna 1">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Representante -->
                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-user-tie"></i>
                        Datos del Representante
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 grid-responsive">
                        <div class="form-group">
                            <label for="ni_representante_nombre" class="required-label">Nombre</label>
                            <input type="text" id="ni_representante_nombre" name="representante_nombre" required class="form-control" placeholder="Ej: María">
                        </div>
                        <div class="form-group">
                            <label for="ni_representante_apellido" class="required-label">Apellido</label>
                            <input type="text" id="ni_representante_apellido" name="representante_apellido" required class="form-control" placeholder="Ej: González">
                        </div>
                        <div class="form-group">
                            <label for="ni_representante_cedula" class="required-label">Cédula de Identidad</label>
                            <input type="text" id="ni_representante_cedula" name="representante_cedula" required class="form-control" placeholder="Ej: V-12345678">
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="form-group">
                            <label for="ni_representante_telefono" class="required-label">Teléfono de Contacto</label>
                            <input type="tel" id="ni_representante_telefono" name="representante_telefono" required class="form-control" placeholder="Ej: 0412-1234567">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Beneficiario -->
                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-child"></i>
                        Datos del Beneficiario
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 grid-responsive">
                        <div class="form-group">
                            <label for="ni_nombres" class="required-label">Nombre</label>
                            <input type="text" id="ni_nombres" name="nombres" required class="form-control" placeholder="Ej: Carlos">
                        </div>
                        <div class="form-group">
                            <label for="ni_apellidos" class="required-label">Apellido</label>
                            <input type="text" id="ni_apellidos" name="apellidos" required class="form-control" placeholder="Ej: Rodríguez">
                        </div>
                        <div class="form-group">
                            <label for="ni_cedula_beneficiario" class="required-label">Cédula de Identidad</label>
                            <input type="text" id="ni_cedula_beneficiario" name="cedula_beneficiario" required class="form-control" placeholder="Ej: V-87654321">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 grid-responsive">
                        <div class="form-group">
                            <label for="ni_fecha_nacimiento" class="required-label">Fecha de Nacimiento</label>
                            <input type="date" id="ni_fecha_nacimiento" name="fecha_nacimiento" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ni_genero" class="required-label">Género</label>
                            <select id="ni_genero" name="genero" required class="form-control" onchange="mostrarCondicionFemeninaNI()">
                                <option value="">Seleccionar</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ni_edad">Edad (Calculada)</label>
                            <input type="text" id="ni_edad" name="edad" readonly class="form-control" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    
                    <!-- Condición femenina (solo si es Femenino) -->
                    <div id="ni_condicion_femenina" class="condicional-genero">
                        <div class="condicional-title">
                            <i class="fas fa-female"></i>
                            Condición Especial (Femenino)
                        </div>
                        <div class="condicion-femenina-group">
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="ni_condicion_nada" name="condicion_femenina" value="nada" checked onchange="mostrarSubcondicionNI()">
                                <label for="ni_condicion_nada">Ninguna condición especial</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="ni_condicion_lactante" name="condicion_femenina" value="lactante" onchange="mostrarSubcondicionNI()">
                                <label for="ni_condicion_lactante">Lactante</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="ni_condicion_gestante" name="condicion_femenina" value="gestante" onchange="mostrarSubcondicionNI()">
                                <label for="ni_condicion_gestante">Gestante</label>
                            </div>
                        </div>
                        
                        <!-- Subcondición para lactante -->
                        <div id="ni_subcondicion_lactante" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="ni_fecha_nacimiento_bebe" class="required-label">Fecha de nacimiento del bebé</label>
                                <input type="date" id="ni_fecha_nacimiento_bebe" name="fecha_nacimiento_bebe" class="form-control">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Se registrará la fecha de nacimiento del bebé para seguimiento.
                            </div>
                        </div>
                        
                        <!-- Subcondición para gestante -->
                        <div id="ni_subcondicion_gestante" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="ni_semanas_gestacion" class="required-label">Semanas de gestación</label>
                                <input type="number" id="ni_semanas_gestacion" name="semanas_gestacion" class="form-control" min="1" max="42" placeholder="Ej: 24">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Indique las semanas completas de gestación.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Antropometría -->
                <div class="form-section antropometria-section">
                    <div class="antropometria-title">
                        <i class="fas fa-ruler-combined mr-2"></i>Antropometría
                    </div>
                    <div class="antropometria-subtitle">
                        Complete las medidas antropométricas del beneficiario
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 grid-responsive">
                        <div class="form-group">
                            <label for="ni_cbi_mm" class="required-label">CBI (mm)</label>
                            <input type="number" id="ni_cbi_mm" name="cbi_mm" step="0.1" required 
                                   class="form-control antropometria-input" placeholder="Ej: 12.5">
                        </div>
                        <div class="form-group">
                            <label for="ni_peso_kg" class="required-label">Peso (Kg)</label>
                            <input type="number" id="ni_peso_kg" name="peso_kg" step="0.1" required 
                                   class="form-control antropometria-input" placeholder="Ej: 25.5">
                        </div>
                        <div class="form-group">
                            <label for="ni_talla_cm" class="required-label">Talla (cm)</label>
                            <input type="number" id="ni_talla_cm" name="talla_cm" step="0.1" required 
                                   class="form-control antropometria-input" placeholder="Ej: 120.0">
                        </div>
                        <div class="form-group">
                            <label for="ni_imc">IMC (Calculado)</label>
                            <input type="text" id="ni_imc" name="imc" readonly 
                                   class="form-control imc-calculado" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                </div>
                
                <!-- Situación -->
                <div class="form-section situacion-section">
                    <div class="situacion-title">
                        <i class="fas fa-clipboard-check mr-2"></i>Situación
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" id="ni_caso_1" name="situacion_dx" value="1" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="ni_caso_1" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 1
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="ni_caso_2" name="situacion_dx" value="2" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="ni_caso_2" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 2
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Footer del Modal -->
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalNuevoIngreso()" class="btn-cancelar">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn-guardar-nuevo-ingreso">
                        <i class="fas fa-save"></i>Guardar Nuevo Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Beneficiario -->
    <div id="editarBeneficiarioModal" class="modal-backup modal-edit">
        <div class="modal-content-backup modal-content-edit">
            <div class="bg-blue-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-xl font-semibold" id="modal-titulo-editar">Editar Beneficiario</h3>
                <button onclick="cerrarModalEditar()" class="text-white hover:text-gray-200 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formEditarBeneficiario" method="POST" class="p-6">
                <input type="hidden" name="action" id="form-action-editar" value="update">
                <input type="hidden" name="beneficiario_id" id="beneficiario_id">
                <input type="hidden" id="parroquias_data_editar" value='<?php echo e($parroquias_json); ?>'>
                <input type="hidden" id="sectores_data_editar" value='<?php echo e($sectores_json); ?>'>
                
                <!-- Ubicación -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Ubicación y CLAP</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="editar_municipio" class="block text-sm font-medium text-gray-700 mb-2 required">Municipio</label>
                            <select id="editar_municipio" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar municipio</option>
                                <?php foreach($municipios as $municipio): ?>
                                    <option value="<?php echo e($municipio); ?>"><?php echo e($municipio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="editar_parroquia" class="block text-sm font-medium text-gray-700 mb-2 required">Parroquia</label>
                            <select id="editar_parroquia" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                                <option value="">Primero seleccione municipio</option>
                            </select>
                        </div>
                        <div>
                            <label for="editar_sector" class="block text-sm font-medium text-gray-700 mb-2 required">Sector donde vive</label>
                            <select id="editar_sector" name="sector" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                                <option value="">Primero seleccione parroquia</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="editar_nombre_clap" class="block text-sm font-medium text-gray-700 mb-2">Nombre CLAP</label>
                            <input type="text" id="editar_nombre_clap" name="nombre_clap" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: CLAP Libertador">
                        </div>
                        <div>
                            <label for="editar_nombre_comuna" class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Comuna</label>
                            <input type="text" id="editar_nombre_comuna" name="nombre_comuna" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Comuna 1">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Representante -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Datos del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="editar_representante_nombre" class="block text-sm font-medium text-gray-700 mb-2 required">Nombre</label>
                            <input type="text" id="editar_representante_nombre" name="representante_nombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: María">
                        </div>
                        <div>
                            <label for="editar_representante_apellido" class="block text-sm font-medium text-gray-700 mb-2 required">Apellido</label>
                            <input type="text" id="editar_representante_apellido" name="representante_apellido" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: González">
                        </div>
                        <div>
                            <label for="editar_representante_cedula" class="block text-sm font-medium text-gray-700 mb-2 required">Cédula de Identidad</label>
                            <input type="text" id="editar_representante_cedula" name="representante_cedula" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: V-12345678">
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full md:w-1/2">
                            <label for="editar_representante_telefono" class="block text-sm font-medium text-gray-700 mb-2 required">Teléfono de Contacto</label>
                            <input type="tel" id="editar_representante_telefono" name="representante_telefono" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 0412-1234567">
                        </div>
                    </div>
                </div>
                
                <!-- Datos del Beneficiario -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Datos del Beneficiario</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="editar_nombres" class="block text-sm font-medium text-gray-700 mb-2 required">Nombre</label>
                            <input type="text" id="editar_nombres" name="nombres" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Carlos">
                        </div>
                        <div>
                            <label for="editar_apellidos" class="block text-sm font-medium text-gray-700 mb-2 required">Apellido</label>
                            <input type="text" id="editar_apellidos" name="apellidos" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Rodríguez">
                        </div>
                        <div>
                            <label for="editar_cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2 required">Cédula de Identidad</label>
                            <input type="text" id="editar_cedula_beneficiario" name="cedula_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: V-87654321">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="editar_fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2 required">Fecha de Nacimiento</label>
                            <input type="date" id="editar_fecha_nacimiento" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="editar_genero" class="block text-sm font-medium text-gray-700 mb-2 required">Género</label>
                            <select id="editar_genero" name="genero" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="mostrarCondicionFemeninaEditar()">
                                <option value="">Seleccionar</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div>
                            <label for="editar_edad" class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <input type="number" id="editar_edad" name="edad" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    
                    <!-- Condición femenina (solo si es Femenino) -->
                    <div id="editar_condicion_femenina" class="condicional-genero">
                        <div class="condicional-title">
                            <i class="fas fa-female"></i>
                            Condición Especial (Femenino)
                        </div>
                        <div class="condicion-femenina-group">
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="editar_condicion_nada" name="condicion_femenina" value="nada" checked onchange="mostrarSubcondicionEditar()">
                                <label for="editar_condicion_nada">Ninguna condición especial</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="editar_condicion_lactante" name="condicion_femenina" value="lactante" onchange="mostrarSubcondicionEditar()">
                                <label for="editar_condicion_lactante">Lactante</label>
                            </div>
                            <div class="condicion-femenina-radio">
                                <input type="radio" id="editar_condicion_gestante" name="condicion_femenina" value="gestante" onchange="mostrarSubcondicionEditar()">
                                <label for="editar_condicion_gestante">Gestante</label>
                            </div>
                        </div>
                        
                        <!-- Subcondición para lactante -->
                        <div id="editar_subcondicion_lactante" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="editar_fecha_nacimiento_bebe" class="required-label">Fecha de nacimiento del bebé</label>
                                <input type="date" id="editar_fecha_nacimiento_bebe" name="fecha_nacimiento_bebe" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Se registrará la fecha de nacimiento del bebé para seguimiento.
                            </div>
                        </div>
                        
                        <!-- Subcondición para gestante -->
                        <div id="editar_subcondicion_gestante" class="condicion-subseccion">
                            <div class="form-group">
                                <label for="editar_semanas_gestacion" class="required-label">Semanas de gestación</label>
                                <input type="number" id="editar_semanas_gestacion" name="semanas_gestacion" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" max="42" placeholder="Ej: 24">
                            </div>
                            <div class="text-xs text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Indique las semanas completas de gestación.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Antropometría -->
                <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-blue-800 mb-4 border-b pb-2">Antropometría</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="editar_cbi_mm" class="block text-sm font-medium text-blue-700 mb-2 required">CBI (mm)</label>
                            <input type="number" id="editar_cbi_mm" name="cbi_mm" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 12.5">
                        </div>
                        <div>
                            <label for="editar_peso_kg" class="block text-sm font-medium text-blue-700 mb-2 required">Peso (Kg)</label>
                            <input type="number" id="editar_peso_kg" name="peso_kg" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 25.5">
                        </div>
                        <div>
                            <label for="editar_talla_cm" class="block text-sm font-medium text-blue-700 mb-2 required">Talla (cm)</label>
                            <input type="number" id="editar_talla_cm" name="talla_cm" step="0.1" required class="w-full border border-blue-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 120.0">
                        </div>
                        <div>
                            <label for="editar_imc" class="block text-sm font-medium text-blue-700 mb-2">IMC (Calculado)</label>
                            <input type="text" id="editar_imc" name="imc" readonly class="w-full border border-blue-300 bg-blue-100 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                </div>
                
                <!-- Situación -->
                <div class="mb-6 bg-green-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-green-800 mb-4 border-b pb-2">Situación</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" id="editar_caso_1" name="situacion_dx" value="1" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="editar_caso_1" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 1
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="editar_caso_2" name="situacion_dx" value="2" required class="h-4 w-4 text-green-600 focus:ring-green-500">
                            <label for="editar_caso_2" class="ml-3 block text-sm font-medium text-green-700">
                                Caso 2
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="cerrarModalEditar()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i>Actualizar Beneficiario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Scripts para exportar a Excel -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    
    <script>
        // =====================================================================
        // FUNCIONES GLOBALES
        // =====================================================================

        // Variable global para la tabla
        var tablaBeneficiarios = null;
        var tablaNuevosIngresos = null;
        var filtroActivo = null;

        // Datos de parroquias y sectores
        let parroquiasData = {};
        let sectoresData = {};
        
        try {
            parroquiasData = JSON.parse(document.getElementById('parroquias_data').value || '{}');
            sectoresData = JSON.parse(document.getElementById('sectores_data').value || '{}');
        } catch (error) {
            console.error('Error al parsear datos JSON:', error);
        }

        // =====================================================================
        // FUNCIONES GENERALES
        // =====================================================================

        function cambiarTab(tabName) {
            console.log('Cambiando a pestaña:', tabName);
            
            // Actualizar botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Mapear nombres de pestañas
            const tabMapping = {
                'beneficiarios': 'beneficiarios',
                'nuevos-ingresos': 'nuevos-ingresos'
            };
            
            const mappedTabName = tabMapping[tabName] || tabName;
            document.querySelector(`[onclick="cambiarTab('${tabName}')"]`).classList.add('active');
            
            // Actualizar contenido
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`tab-${mappedTabName}`).classList.add('active');
            
            // Redibujar DataTable si es necesario
            if (tabName === 'beneficiarios' && tablaBeneficiarios) {
                setTimeout(() => {
                    try {
                        tablaBeneficiarios.columns.adjust();
                    } catch (error) {
                        console.error('Error al redibujar tabla:', error);
                    }
                }, 100);
            }
            
            if (tabName === 'nuevos-ingresos' && tablaNuevosIngresos) {
                setTimeout(() => {
                    try {
                        tablaNuevosIngresos.columns.adjust();
                    } catch (error) {
                        console.error('Error al redibujar tabla nuevos ingresos:', error);
                    }
                }, 100);
            }
        }

        function abrirModalNuevoBeneficiario() {
            console.log('Abriendo modal para nuevo beneficiario online');
            
            // Resetear formulario
            document.getElementById('formNuevoBeneficiario').reset();
            
            // Configurar selects
            document.getElementById('parroquia').innerHTML = '<option value="">Primero seleccione municipio</option>';
            document.getElementById('parroquia').disabled = true;
            document.getElementById('sector').innerHTML = '<option value="">Primero seleccione parroquia</option>';
            document.getElementById('sector').disabled = true;
            
            // Configurar el modal
            document.getElementById('modal-titulo').textContent = 'Nuevo Beneficiario';
            document.getElementById('btn-guardar-texto').textContent = 'Guardar Beneficiario';
            document.getElementById('form-action').value = 'create';
            document.getElementById('form-tipo').value = 'online';
            
            // Ocultar condición femenina por defecto
            document.getElementById('condicion_femenina_nuevo').classList.remove('show');
            document.getElementById('subcondicion_lactante_nuevo').classList.remove('show');
            document.getElementById('subcondicion_gestante_nuevo').classList.remove('show');
            document.getElementById('condicion_nada_nuevo').checked = true;
            
            // Mostrar modal
            document.getElementById('nuevoBeneficiarioModal').style.display = 'flex';
            
            // Calcular edad por defecto (5 años atrás)
            const hoy = new Date();
            const fechaHace5Anos = new Date(hoy.getFullYear() - 5, hoy.getMonth(), hoy.getDate());
            document.getElementById('fecha_nacimiento').value = fechaHace5Anos.toISOString().split('T')[0];
            calcularEdadBeneficiario();
        }
        
        function cerrarModal() {
            console.log('Cerrando modal nuevo beneficiario');
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
        }

        function abrirModalNuevoIngreso() {
            console.log('Abriendo modal para nuevo ingreso');
            
            // Resetear formulario
            document.getElementById('formNuevoIngreso').reset();
            
            // Resetear selects
            document.getElementById('ni_parroquia').innerHTML = '<option value="">Primero seleccione municipio</option>';
            document.getElementById('ni_parroquia').disabled = true;
            document.getElementById('ni_sector').innerHTML = '<option value="">Primero seleccione parroquia</option>';
            document.getElementById('ni_sector').disabled = true;
            
            // Resetear condición femenina
            document.getElementById('ni_condicion_femenina').classList.remove('show');
            document.getElementById('ni_subcondicion_lactante').classList.remove('show');
            document.getElementById('ni_subcondicion_gestante').classList.remove('show');
            document.getElementById('ni_condicion_nada').checked = true;
            
            // Mostrar modal
            document.getElementById('nuevoIngresoModal').style.display = 'flex';
            
            // Calcular edad por defecto (5 años atrás)
            const hoy = new Date();
            const fechaHace5Anos = new Date(hoy.getFullYear() - 5, hoy.getMonth(), hoy.getDate());
            document.getElementById('ni_fecha_nacimiento').value = fechaHace5Anos.toISOString().split('T')[0];
            calcularEdadNuevoIngreso();
            
            // Enfocar el primer campo
            setTimeout(() => {
                document.getElementById('ni_municipio').focus();
            }, 100);
        }

        function cerrarModalNuevoIngreso() {
            console.log('Cerrando modal nuevo ingreso');
            document.getElementById('nuevoIngresoModal').style.display = 'none';
        }

        // =====================================================================
        // FUNCIONES PARA CONDICIÓN FEMENINA - NUEVO BENEFICIARIO
        // =====================================================================

        function mostrarCondicionFemeninaNuevo() {
            const genero = document.getElementById('genero').value;
            const condicionFemenina = document.getElementById('condicion_femenina_nuevo');
            
            if (genero === 'F') {
                condicionFemenina.classList.add('show');
            } else {
                condicionFemenina.classList.remove('show');
                // Ocultar subcondiciones
                document.getElementById('subcondicion_lactante_nuevo').classList.remove('show');
                document.getElementById('subcondicion_gestante_nuevo').classList.remove('show');
                // Resetear radio buttons
                document.getElementById('condicion_nada_nuevo').checked = true;
            }
        }

        function mostrarSubcondicionNuevo() {
            const condicionLactante = document.getElementById('subcondicion_lactante_nuevo');
            const condicionGestante = document.getElementById('subcondicion_gestante_nuevo');
            
            // Ocultar ambas primero
            condicionLactante.classList.remove('show');
            condicionGestante.classList.remove('show');
            
            // Mostrar la correspondiente
            if (document.getElementById('condicion_lactante_nuevo').checked) {
                condicionLactante.classList.add('show');
            } else if (document.getElementById('condicion_gestante_nuevo').checked) {
                condicionGestante.classList.add('show');
            }
        }

        // =====================================================================
        // FUNCIONES PARA CONDICIÓN FEMENINA - NUEVO INGRESO
        // =====================================================================

        function mostrarCondicionFemeninaNI() {
            const genero = document.getElementById('ni_genero').value;
            const condicionFemenina = document.getElementById('ni_condicion_femenina');
            
            if (genero === 'F') {
                condicionFemenina.classList.add('show');
            } else {
                condicionFemenina.classList.remove('show');
                // Ocultar subcondiciones
                document.getElementById('ni_subcondicion_lactante').classList.remove('show');
                document.getElementById('ni_subcondicion_gestante').classList.remove('show');
                // Resetear radio buttons
                document.getElementById('ni_condicion_nada').checked = true;
            }
        }

        function mostrarSubcondicionNI() {
            const condicionLactante = document.getElementById('ni_subcondicion_lactante');
            const condicionGestante = document.getElementById('ni_subcondicion_gestante');
            
            // Ocultar ambas primero
            condicionLactante.classList.remove('show');
            condicionGestante.classList.remove('show');
            
            // Mostrar la correspondiente
            if (document.getElementById('ni_condicion_lactante').checked) {
                condicionLactante.classList.add('show');
            } else if (document.getElementById('ni_condicion_gestante').checked) {
                condicionGestante.classList.add('show');
            }
        }

        // =====================================================================
        // FUNCIONES PARA EDICIÓN DE BENEFICIARIOS
        // =====================================================================

        function editarBeneficiario(beneficiarioId, button) {
            console.log('Editando beneficiario con ID:', beneficiarioId);
            
            // Encontrar la fila del beneficiario
            const fila = $(button).closest('tr');
            
            // Obtener todos los datos de la fila
            const datos = {
                id: fila.data('id'),
                peso: fila.data('peso'),
                talla: fila.data('talla'),
                cbi: fila.data('cbi'),
                imc: fila.data('imc'),
                municipio: fila.data('municipio'),
                parroquia: fila.data('parroquia'),
                sector: fila.data('sector'),
                caso: fila.data('caso'),
                nombre: fila.data('nombre'),
                apellido: fila.data('apellido'),
                cedula: fila.data('cedula'),
                edad: fila.data('edad'),
                genero: fila.data('genero'),
                fechaNacimiento: fila.data('fecha-nacimiento'),
                condicionFemenina: fila.data('condicion-femenina'),
                fechaNacimientoBebe: fila.data('fecha-nacimiento-bebe'),
                semanasGestacion: fila.data('semanas-gestacion'),
                nombreRepresentante: fila.data('nombre-representante'),
                apellidoRepresentante: fila.data('apellido-representante'),
                cedulaRepresentante: fila.data('cedula-representante'),
                telefonoRepresentante: fila.data('telefono-representante'),
                nombreClap: fila.data('nombre-clap'),
                nombreComuna: fila.data('nombre-comuna')
            };
            
            console.log('Datos del beneficiario:', datos);
            
            // Llenar el formulario de edición
            document.getElementById('beneficiario_id').value = datos.id;
            
            // Ubicación
            document.getElementById('editar_municipio').value = datos.municipio || '';
            
            // Cargar parroquias para el municipio seleccionado
            cargarParroquiasEditar(datos.municipio, datos.parroquia);
            
            // Sector se cargará automáticamente después de parroquia
            setTimeout(() => {
                document.getElementById('editar_sector').value = datos.sector || '';
            }, 100);
            
            document.getElementById('editar_nombre_clap').value = datos.nombreClap || '';
            document.getElementById('editar_nombre_comuna').value = datos.nombreComuna || '';
            
            // Datos del representante
            document.getElementById('editar_representante_nombre').value = datos.nombreRepresentante || '';
            document.getElementById('editar_representante_apellido').value = datos.apellidoRepresentante || '';
            document.getElementById('editar_representante_cedula').value = datos.cedulaRepresentante || '';
            document.getElementById('editar_representante_telefono').value = datos.telefonoRepresentante || '';
            
            // Datos del beneficiario
            document.getElementById('editar_nombres').value = datos.nombre || '';
            document.getElementById('editar_apellidos').value = datos.apellido || '';
            document.getElementById('editar_cedula_beneficiario').value = datos.cedula || '';
            document.getElementById('editar_fecha_nacimiento').value = datos.fechaNacimiento || '';
            document.getElementById('editar_genero').value = datos.genero || '';
            document.getElementById('editar_edad').value = datos.edad || '';
            
            // Manejar condición femenina
            mostrarCondicionFemeninaEditar(datos.genero, datos.condicionFemenina, datos.fechaNacimientoBebe, datos.semanasGestacion);
            
            // Antropometría
            document.getElementById('editar_cbi_mm').value = datos.cbi || '';
            document.getElementById('editar_peso_kg').value = datos.peso || '';
            document.getElementById('editar_talla_cm').value = datos.talla || '';
            document.getElementById('editar_imc').value = datos.imc || '';
            
            // Situación (caso)
            if (datos.caso === '1') {
                document.getElementById('editar_caso_1').checked = true;
            } else if (datos.caso === '2') {
                document.getElementById('editar_caso_2').checked = true;
            }
            
            // Mostrar el modal de edición
            document.getElementById('editarBeneficiarioModal').style.display = 'flex';
        }

        function mostrarCondicionFemeninaEditar(genero = '', condicionFemenina = '', fechaBebe = '', semanasGestacion = '') {
            const condicionFemeninaDiv = document.getElementById('editar_condicion_femenina');
            
            if (genero === 'F') {
                condicionFemeninaDiv.classList.add('show');
                
                // Configurar la condición femenina seleccionada
                if (condicionFemenina === 'lactante') {
                    document.getElementById('editar_condicion_lactante').checked = true;
                    document.getElementById('editar_subcondicion_lactante').classList.add('show');
                    document.getElementById('editar_fecha_nacimiento_bebe').value = fechaBebe || '';
                } else if (condicionFemenina === 'gestante') {
                    document.getElementById('editar_condicion_gestante').checked = true;
                    document.getElementById('editar_subcondicion_gestante').classList.add('show');
                    document.getElementById('editar_semanas_gestacion').value = semanasGestacion || '';
                } else {
                    document.getElementById('editar_condicion_nada').checked = true;
                }
            } else {
                condicionFemeninaDiv.classList.remove('show');
                // Ocultar subcondiciones
                document.getElementById('editar_subcondicion_lactante').classList.remove('show');
                document.getElementById('editar_subcondicion_gestante').classList.remove('show');
                // Resetear radio buttons
                document.getElementById('editar_condicion_nada').checked = true;
            }
        }

        function mostrarSubcondicionEditar() {
            const condicionLactante = document.getElementById('editar_subcondicion_lactante');
            const condicionGestante = document.getElementById('editar_subcondicion_gestante');
            
            // Ocultar ambas primero
            condicionLactante.classList.remove('show');
            condicionGestante.classList.remove('show');
            
            // Mostrar la correspondiente
            if (document.getElementById('editar_condicion_lactante').checked) {
                condicionLactante.classList.add('show');
            } else if (document.getElementById('editar_condicion_gestante').checked) {
                condicionGestante.classList.add('show');
            }
        }

        function cerrarModalEditar() {
            console.log('Cerrando modal editar');
            document.getElementById('editarBeneficiarioModal').style.display = 'none';
        }

        function cargarParroquiasEditar(municipioSeleccionado, parroquiaSeleccionada = '') {
            const parroquiaSelect = document.getElementById('editar_parroquia');
            const sectorSelect = document.getElementById('editar_sector');
            
            // Limpiar y resetear parroquia
            parroquiaSelect.innerHTML = '<option value="">Seleccionar parroquia</option>';
            parroquiaSelect.disabled = !municipioSeleccionado;
            
            // Limpiar sector
            sectorSelect.innerHTML = '<option value="">Seleccionar sector</option>';
            sectorSelect.disabled = true;
            
            // Si hay municipio seleccionado, cargar sus parroquias
            if (municipioSeleccionado && parroquiasData[municipioSeleccionado]) {
                const parroquias = parroquiasData[municipioSeleccionado];
                
                // Ordenar alfabéticamente
                parroquias.sort().forEach(function(parroquia) {
                    const option = document.createElement('option');
                    option.value = parroquia;
                    option.textContent = parroquia;
                    if (parroquia === parroquiaSeleccionada) {
                        option.selected = true;
                    }
                    parroquiaSelect.appendChild(option);
                });
                
                // Habilitar el select de parroquias
                parroquiaSelect.disabled = false;
                
                // Si hay parroquia seleccionada, cargar sus sectores
                if (parroquiaSeleccionada) {
                    cargarSectoresEditar(parroquiaSeleccionada);
                }
            }
        }

        function cargarSectoresEditar(parroquiaSeleccionada, sectorSeleccionado = '') {
            const sectorSelect = document.getElementById('editar_sector');
            
            // Limpiar y resetear sector
            sectorSelect.innerHTML = '<option value="">Seleccionar sector</option>';
            sectorSelect.disabled = !parroquiaSeleccionada;
            
            // Si hay parroquia seleccionada, cargar sus sectores
            if (parroquiaSeleccionada && sectoresData[parroquiaSeleccionada]) {
                const sectores = sectoresData[parroquiaSeleccionada];
                
                // Ordenar alfabéticamente
                sectores.sort().forEach(function(sector) {
                    const option = document.createElement('option');
                    option.value = sector;
                    option.textContent = sector;
                    if (sector === sectorSeleccionado) {
                        option.selected = true;
                    }
                    sectorSelect.appendChild(option);
                });
                
                // Habilitar el select de sectores
                sectorSelect.disabled = false;
            }
        }

        // Configurar eventos para los selects de edición
        document.addEventListener('DOMContentLoaded', function() {
            // Municipio editar
            const editarMunicipio = document.getElementById('editar_municipio');
            if (editarMunicipio) {
                editarMunicipio.addEventListener('change', function() {
                    cargarParroquiasEditar(this.value);
                });
            }
            
            // Parroquia editar
            const editarParroquia = document.getElementById('editar_parroquia');
            if (editarParroquia) {
                editarParroquia.addEventListener('change', function() {
                    cargarSectoresEditar(this.value);
                });
            }
            
            // Cálculo de IMC en edición
            const editarPeso = document.getElementById('editar_peso_kg');
            const editarTalla = document.getElementById('editar_talla_cm');
            
            if (editarPeso && editarTalla) {
                editarPeso.addEventListener('input', calcularIMCEditar);
                editarTalla.addEventListener('input', calcularIMCEditar);
            }
            
            // Cálculo de edad en edición
            const editarFechaNacimiento = document.getElementById('editar_fecha_nacimiento');
            if (editarFechaNacimiento) {
                editarFechaNacimiento.addEventListener('change', calcularEdadEditar);
            }
            
            // Género en edición
            const editarGenero = document.getElementById('editar_genero');
            if (editarGenero) {
                editarGenero.addEventListener('change', function() {
                    mostrarCondicionFemeninaEditar(this.value);
                });
            }
            
            // Radio buttons para condición femenina en edición
            const condicionLactanteEditar = document.getElementById('editar_condicion_lactante');
            const condicionGestanteEditar = document.getElementById('editar_condicion_gestante');
            const condicionNadaEditar = document.getElementById('editar_condicion_nada');
            
            if (condicionLactanteEditar) {
                condicionLactanteEditar.addEventListener('change', mostrarSubcondicionEditar);
            }
            if (condicionGestanteEditar) {
                condicionGestanteEditar.addEventListener('change', mostrarSubcondicionEditar);
            }
            if (condicionNadaEditar) {
                condicionNadaEditar.addEventListener('change', mostrarSubcondicionEditar);
            }
        });

        function calcularIMCEditar() {
            const peso = parseFloat(document.getElementById('editar_peso_kg').value);
            const talla = parseFloat(document.getElementById('editar_talla_cm').value);
            
            if (peso && talla && talla > 0) {
                // Convertir talla de cm a m
                const tallaMetros = talla / 100;
                const imc = peso / (tallaMetros * tallaMetros);
                document.getElementById('editar_imc').value = imc.toFixed(2);
            } else {
                document.getElementById('editar_imc').value = '';
            }
        }

        function calcularEdadEditar() {
            const fechaNacimiento = document.getElementById('editar_fecha_nacimiento').value;
            if (!fechaNacimiento) return;
            
            const nacimiento = new Date(fechaNacimiento);
            const hoy = new Date();
            
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            
            document.getElementById('editar_edad').value = edad;
        }

        // =====================================================================
        // FUNCIONES DE CÁLCULO
        // =====================================================================

        function calcularEdadBeneficiario() {
            const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
            if (!fechaNacimiento) return;
            
            const nacimiento = new Date(fechaNacimiento);
            const hoy = new Date();
            
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            
            document.getElementById('edad').value = edad;
            
            // Calcular IMC automáticamente
            calcularIMCBeneficiario();
        }

        function calcularEdadNuevoIngreso() {
            const fechaNacimiento = document.getElementById('ni_fecha_nacimiento').value;
            if (!fechaNacimiento) return;
            
            const nacimiento = new Date(fechaNacimiento);
            const hoy = new Date();
            
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            
            document.getElementById('ni_edad').value = edad + ' años';
            
            // Calcular IMC automáticamente
            calcularIMCNuevoIngreso();
        }

        function calcularIMCBeneficiario() {
            const peso = parseFloat(document.getElementById('peso_kg').value);
            const talla = parseFloat(document.getElementById('talla_cm').value);
            
            if (peso && talla && talla > 0) {
                // Convertir talla de cm a m
                const tallaMetros = talla / 100;
                const imc = peso / (tallaMetros * tallaMetros);
                document.getElementById('imc').value = imc.toFixed(2);
            } else {
                document.getElementById('imc').value = '';
            }
        }

        function calcularIMCNuevoIngreso() {
            const peso = parseFloat(document.getElementById('ni_peso_kg').value);
            const talla = parseFloat(document.getElementById('ni_talla_cm').value);
            
            if (peso && talla && talla > 0) {
                // Convertir talla de cm a m
                const tallaMetros = talla / 100;
                const imc = peso / (tallaMetros * tallaMetros);
                document.getElementById('ni_imc').value = imc.toFixed(2);
            } else {
                document.getElementById('ni_imc').value = '';
            }
        }

        // =====================================================================
        // FUNCIONES PARA FILTROS DEPENDIENTES EN MODALES
        // =====================================================================

        function cargarParroquias(selectMunicipioId, selectParroquiaId) {
            const municipioSelect = document.getElementById(selectMunicipioId);
            const parroquiaSelect = document.getElementById(selectParroquiaId);
            
            if (!municipioSelect || !parroquiaSelect) return;
            
            const municipioSeleccionado = municipioSelect.value;
            
            // Limpiar y resetear parroquia
            parroquiaSelect.innerHTML = '<option value="">Seleccionar parroquia</option>';
            parroquiaSelect.disabled = !municipioSeleccionado;
            
            // Si hay municipio seleccionado, cargar sus parroquias
            if (municipioSeleccionado && parroquiasData[municipioSeleccionado]) {
                const parroquias = parroquiasData[municipioSeleccionado];
                
                // Ordenar alfabéticamente
                parroquias.sort().forEach(function(parroquia) {
                    const option = document.createElement('option');
                    option.value = parroquia;
                    option.textContent = parroquia;
                    parroquiaSelect.appendChild(option);
                });
                
                // Habilitar el select de parroquias
                parroquiaSelect.disabled = false;
            }
            
            // Resetear sector
            if (selectParroquiaId === 'parroquia') {
                cargarSectores('parroquia', 'sector');
            }
        }

        function cargarSectores(selectParroquiaId, selectSectorId) {
            const parroquiaSelect = document.getElementById(selectParroquiaId);
            const sectorSelect = document.getElementById(selectSectorId);
            
            if (!parroquiaSelect || !sectorSelect) return;
            
            const parroquiaSeleccionada = parroquiaSelect.value;
            
            // Limpiar y resetear sector
            sectorSelect.innerHTML = '<option value="">Seleccionar sector</option>';
            sectorSelect.disabled = !parroquiaSeleccionada;
            
            // Si hay parroquia seleccionada, cargar sus sectores
            if (parroquiaSeleccionada && sectoresData[parroquiaSeleccionada]) {
                const sectores = sectoresData[parroquiaSeleccionada];
                
                // Ordenar alfabéticamente
                sectores.sort().forEach(function(sector) {
                    const option = document.createElement('option');
                    option.value = sector;
                    option.textContent = sector;
                    sectorSelect.appendChild(option);
                });
                
                // Habilitar el select de sectores
                sectorSelect.disabled = false;
            }
        }

        function cargarParroquiasNuevoIngreso() {
            cargarParroquias('ni_municipio', 'ni_parroquia');
        }

        function cargarSectoresNuevoIngreso() {
            cargarSectores('ni_parroquia', 'ni_sector');
        }

        // =====================================================================
        // FUNCIONES PARA FILTROS DEPENDIENTES EN LOS FILTROS DE BÚSQUEDA
        // =====================================================================

        function cargarParroquiasFiltro() {
            const municipioSeleccionado = $('#filtro-municipio').val();
            const parroquiaSelect = $('#filtro-parroquia');
            const sectorSelect = $('#filtro-sector');
            
            // Limpiar parroquia
            parroquiaSelect.html('<option value="">Todas las parroquias</option>');
            parroquiaSelect.prop('disabled', !municipioSeleccionado);
            
            // Limpiar sector
            sectorSelect.html('<option value="">Todos los sectores</option>');
            sectorSelect.prop('disabled', true);
            
            // Si se seleccionó un municipio, cargar sus parroquias
            if (municipioSeleccionado && parroquiasData[municipioSeleccionado]) {
                const parroquias = parroquiasData[municipioSeleccionado];
                
                // Ordenar alfabéticamente
                parroquias.sort().forEach(function(parroquia) {
                    parroquiaSelect.append($('<option>', {
                        value: parroquia,
                        text: parroquia
                    }));
                });
                
                // Habilitar parroquia
                parroquiaSelect.prop('disabled', false);
            }
            
            // Aplicar filtros si hay tabla
            if (tablaBeneficiarios) {
                aplicarFiltros();
            }
        }

        function cargarSectoresFiltro() {
            const municipioSeleccionado = $('#filtro-municipio').val();
            const parroquiaSeleccionada = $('#filtro-parroquia').val();
            const sectorSelect = $('#filtro-sector');
            
            // Limpiar sector
            sectorSelect.html('<option value="">Todos los sectores</option>');
            sectorSelect.prop('disabled', !parroquiaSeleccionada);
            
            // Si se seleccionó una parroquia, cargar sus sectores
            if (parroquiaSeleccionada && sectoresData[parroquiaSeleccionada]) {
                const sectores = sectoresData[parroquiaSeleccionada];
                
                // Ordenar alfabéticamente
                sectores.sort().forEach(function(sector) {
                    sectorSelect.append($('<option>', {
                        value: sector,
                        text: sector
                    }));
                });
                
                // Habilitar sector
                sectorSelect.prop('disabled', false);
            }
            
            // Aplicar filtros si hay tabla
            if (tablaBeneficiarios) {
                aplicarFiltros();
            }
        }

        // =====================================================================
        // FUNCIONES DE ELIMINACIÓN
        // =====================================================================

        function eliminarBeneficiario(cedula, nombre) {
            if (confirm(`¿Está seguro de que desea eliminar al beneficiario "${nombre}" (${cedula})?\n\nEsta acción marcará al beneficiario como "inactivo".`)) {
                // Crear formulario dinámico para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'delete';
                
                const inputCedula = document.createElement('input');
                inputCedula.type = 'hidden';
                inputCedula.name = 'cedula_beneficiario';
                inputCedula.value = cedula;
                
                form.appendChild(inputAction);
                form.appendChild(inputCedula);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function incorporarTodo() {
            if (confirm('¿Está seguro de que desea incorporar todos los nuevos ingresos a la base de datos principal?')) {
                // Crear formulario dinámico para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'incorporar_todos';
                
                form.appendChild(inputAction);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function incorporarRegistro(cedula) {
            if (confirm('¿Incorporar este registro a la base de datos principal?')) {
                // Crear formulario dinámico para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'incorporar';
                
                const inputCedula = document.createElement('input');
                inputCedula.type = 'hidden';
                inputCedula.name = 'cedula_beneficiario';
                inputCedula.value = cedula;
                
                form.appendChild(inputAction);
                form.appendChild(inputCedula);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editarRegistroNI(cedula) {
            alert('Editando nuevo ingreso con cédula: ' + cedula + '\n\nEsta funcionalidad se implementará en una futura versión.');
        }

        function eliminarRegistroNI(cedula) {
            if (confirm('¿Eliminar este registro de nuevo ingreso?\n\nEsta acción eliminará permanentemente el registro.')) {
                // Crear formulario dinámico para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'delete_nuevo_ingreso';
                
                const inputCedula = document.createElement('input');
                inputCedula.type = 'hidden';
                inputCedula.name = 'cedula_beneficiario';
                inputCedula.value = cedula;
                
                form.appendChild(inputAction);
                form.appendChild(inputCedula);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // =====================================================================
        // FUNCIONES DE EXPORTACIÓN
        // =====================================================================

        function obtenerFiltrosAplicados() {
            const filtros = [];
            const municipio = $('#filtro-municipio').val();
            const parroquia = $('#filtro-parroquia').val();
            const sector = $('#filtro-sector').val();
            const caso = $('#filtro-caso').val();
            
            if (municipio) filtros.push('Municipio: ' + municipio);
            if (parroquia) filtros.push('Parroquia: ' + parroquia);
            if (sector) filtros.push('Sector: ' + sector);
            if (caso) filtros.push('Caso: ' + (caso === '1' ? 'Caso 1' : 'Caso 2'));
            
            return filtros.length > 0 ? filtros.join(', ') : 'Sin filtros';
        }

        function exportarConsolidadoExcel() {
            console.log('Iniciando exportación Excel...');
            
            // Obtener valores de los filtros actuales
            const municipio = $('#filtro-municipio').val() || '';
            const parroquia = $('#filtro-parroquia').val() || '';
            const sector = $('#filtro-sector').val() || '';
            const caso = $('#filtro-caso').val() || '';
            
            // Construir URL con los filtros
            let url = 'exportar_consolidado_excel.php?';
            let params = [];
            
            if (municipio) params.push('municipio=' + encodeURIComponent(municipio));
            if (parroquia) params.push('parroquia=' + encodeURIComponent(parroquia));
            if (sector) params.push('sector=' + encodeURIComponent(sector));
            if (caso) params.push('caso=' + encodeURIComponent(caso));
            
            if (params.length > 0) {
                url += params.join('&');
            }
            
            // Mensaje de confirmación
            let confirmMsg = '¿Exportar reporte consolidado a Excel?\n\n';
            confirmMsg += 'Se generará un archivo Excel con todos los datos de beneficiarios activos.\n\n';
            
            if (municipio || parroquia || sector || caso) {
                confirmMsg += 'Filtros activos:\n';
                if (municipio) confirmMsg += '• Municipio: ' + municipio + '\n';
                if (parroquia) confirmMsg += '• Parroquia: ' + parroquia + '\n';
                if (sector) confirmMsg += '• Sector: ' + sector + '\n';
                if (caso) confirmMsg += '• Caso: ' + (caso === '1' ? 'Caso 1' : 'Caso 2') + '\n';
            }
            
            confirmMsg += '\n¿Desea continuar?';
            
            if (confirm(confirmMsg)) {
                // Mostrar indicador de carga
                mostrarLoaderExportacion();
                
                // Crear un enlace temporal para la descarga
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.download = 'reporte_consolidado_' + new Date().toISOString().slice(0,10) + '.xls';
                
                // Simular clic para iniciar descarga
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Ocultar loader después de 2 segundos
                setTimeout(() => {
                    ocultarLoaderExportacion();
                    
                    // Mostrar mensaje de éxito
                    alert('✅ ¡Reporte exportado exitosamente!\n\n' +
                          'El archivo Excel se está descargando con todos los datos.\n' +
                          'Nombre del archivo: reporte_consolidado_[fecha].xls');
                }, 2000);
            }
        }

        function generarFormatoPDF() {
            console.log('Generando formato PDF...');
            
            // Obtener valores de los filtros actuales
            const municipio = $('#filtro-municipio').val() || '';
            const parroquia = $('#filtro-parroquia').val() || '';
            const sector = $('#filtro-sector').val() || '';
            const caso = $('#filtro-caso').val() || '';
            
            // Construir URL con los filtros
            let url = 'generar_formato_pdf.php?';
            let params = [];
            
            if (municipio) params.push('municipio=' + encodeURIComponent(municipio));
            if (parroquia) params.push('parroquia=' + encodeURIComponent(parroquia));
            if (sector) params.push('sector=' + encodeURIComponent(sector));
            if (caso) params.push('caso=' + encodeURIComponent(caso));
            
            if (params.length > 0) {
                url += params.join('&');
            }
            
            // Mensaje de confirmación
            let confirmMsg = '¿Generar formato para imprimir en PDF?\n\n';
            confirmMsg += 'Se generará un documento PDF con formato de entrega.\n\n';
            
            if (municipio || parroquia || sector || caso) {
                confirmMsg += 'Filtros activos:\n';
                if (municipio) confirmMsg += '• Municipio: ' + municipio + '\n';
                if (parroquia) confirmMsg += '• Parroquia: ' + parroquia + '\n';
                if (sector) confirmMsg += '• Sector: ' + sector + '\n';
                if (caso) confirmMsg += '• Caso: ' + (caso === '1' ? 'Caso 1' : 'Caso 2') + '\n';
            }
            
            confirmMsg += '\n¿Desea continuar?';
            
            if (confirm(confirmMsg)) {
                // Mostrar indicador de carga
                mostrarLoaderPDF();
                
                // Abrir la URL en una nueva pestaña
                window.open(url, '_blank');
                
                // Ocultar loader después de 3 segundos
                setTimeout(() => {
                    ocultarLoaderPDF();
                }, 3000);
            }
        }

        function mostrarLoaderExportacion() {
            let loader = document.getElementById('export-loader');
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'export-loader';
                loader.className = 'export-loader';
                loader.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generando Excel...';
                document.body.appendChild(loader);
            }
        }

        function ocultarLoaderExportacion() {
            const loader = document.getElementById('export-loader');
            if (loader) {
                loader.remove();
            }
        }

        function mostrarLoaderPDF() {
            let loader = document.getElementById('pdf-loader');
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'pdf-loader';
                loader.className = 'pdf-loader';
                loader.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generando PDF...';
                document.body.appendChild(loader);
            }
        }

        function ocultarLoaderPDF() {
            const loader = document.getElementById('pdf-loader');
            if (loader) {
                loader.remove();
            }
        }

        // =====================================================================
        // FUNCIONES PARA FILTROS DE LA TABLA
        // =====================================================================

        function aplicarFiltros() {
            var municipio = $('#filtro-municipio').val();
            var parroquia = $('#filtro-parroquia').val();
            var sector = $('#filtro-sector').val();
            var caso = $('#filtro-caso').val();
            
            console.log('Aplicando filtros:', { municipio, parroquia, sector, caso });
            
            if (!tablaBeneficiarios) {
                console.error('La tabla no está inicializada');
                return;
            }
            
            // Limpiar filtro anterior si existe
            if (filtroActivo !== null) {
                $.fn.dataTable.ext.search.pop();
                filtroActivo = null;
            }
            
            // Crear función de filtrado
            filtroActivo = function(settings, data, dataIndex) {
                if (!tablaBeneficiarios) return true;
                
                var row = tablaBeneficiarios.row(dataIndex).node();
                if (!row) return true;
                
                var rowMunicipio = $(row).data('municipio');
                var rowParroquia = $(row).data('parroquia');
                var rowSector = $(row).data('sector');
                var rowCaso = $(row).data('caso');
                
                // Verificar cada filtro
                var municipioMatch = !municipio || rowMunicipio === municipio;
                var parroquiaMatch = !parroquia || rowParroquia === parroquia;
                var sectorMatch = !sector || rowSector === sector;
                var casoMatch = !caso || rowCaso == caso; // Usar == para comparación flexible
                
                return municipioMatch && parroquiaMatch && sectorMatch && casoMatch;
            };
            
            // Aplicar el filtro
            $.fn.dataTable.ext.search.push(filtroActivo);
            
            // Redibujar la tabla
            tablaBeneficiarios.draw();
            
            // Actualizar UI
            actualizarUI();
            actualizarMensajeFiltros();
            actualizarContador();
        }

        function limpiarFiltros() {
            console.log('Limpiando filtros');
            
            if (!tablaBeneficiarios) {
                console.error('La tabla no está inicializada');
                return;
            }
            
            // Limpiar selects
            $('#filtro-municipio').val('');
            $('#filtro-parroquia').val('');
            $('#filtro-sector').val('');
            $('#filtro-caso').val('');
            
            // Resetear filtros dependientes
            cargarParroquiasFiltro();
            
            // Limpiar filtro de DataTable
            if (filtroActivo !== null) {
                $.fn.dataTable.ext.search.pop();
                filtroActivo = null;
            }
            
            // Limpiar búsqueda de DataTable
            tablaBeneficiarios.search('');
            
            // Redibujar tabla
            tablaBeneficiarios.draw();
            
            // Actualizar UI
            actualizarUI();
            $('#mensaje-filtros').removeClass('show');
            actualizarContador();
        }

        function actualizarUI() {
            // Quitar clase activa de todos
            $('#filtro-municipio, #filtro-parroquia, #filtro-sector, #filtro-caso').removeClass('filter-active');
            
            // Agregar clase activa a los que tienen valor
            if ($('#filtro-municipio').val()) $('#filtro-municipio').addClass('filter-active');
            if ($('#filtro-parroquia').val()) $('#filtro-parroquia').addClass('filter-active');
            if ($('#filtro-sector').val()) $('#filtro-sector').addClass('filter-active');
            if ($('#filtro-caso').val()) $('#filtro-caso').addClass('filter-active');
        }

        function actualizarMensajeFiltros() {
            const filtrosTexto = obtenerFiltrosAplicados();
            const mensajeFiltros = $('#mensaje-filtros');
            const textoFiltros = $('#texto-filtros');
            
            textoFiltros.text(filtrosTexto);
            
            if (filtrosTexto !== 'Sin filtros') {
                mensajeFiltros.addClass('show');
            } else {
                mensajeFiltros.removeClass('show');
            }
        }

        function actualizarContador() {
            if (!tablaBeneficiarios) {
                console.warn('La tabla no está inicializada en actualizarContador');
                // Mostrar el total de PHP como fallback
                $('#total-registros').text(<?php echo e($totalBeneficiarios); ?>);
                return;
            }
            
            try {
                var totalFiltrado = tablaBeneficiarios.rows({ search: 'applied' }).count();
                $('#total-registros').text(totalFiltrado);
            } catch (error) {
                console.warn('Error en actualizarContador:', error);
                // Usar el contador de PHP como respaldo
                $('#total-registros').text(<?php echo e($totalBeneficiarios); ?>);
            }
        }

        // =====================================================================
        // INICIALIZACIÓN
        // =====================================================================

        $(document).ready(function() {
            console.log('DOM cargado, inicializando DataTables...');
            
            // Inicializar DataTable de beneficiarios si hay datos
            if ($('#tabla-beneficiarios').length && $('#tabla-beneficiarios tbody tr').length > 0) {
                console.log('Tabla beneficiarios encontrada, inicializando...');
                
                // Configuración simplificada de DataTables
                tablaBeneficiarios = $('#tabla-beneficiarios').DataTable({
                    responsive: false,
                    scrollX: false,
                    autoWidth: false,
                    destroy: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    language: {
                        "decimal": ",",
                        "emptyTable": "No hay datos disponibles",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                        "infoFiltered": "(filtrado de _MAX_ registros Totales)",
                        "infoPostFix": "",
                        "thousands": ".",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "No se encontraron registros coincidentes",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: [[0, 'asc']],
                    dom: 'Bfrtip',
                    buttons: [
                    ],
                    columnDefs: [
                        { 
                            targets: [5], // Columna de acciones
                            orderable: false,
                            searchable: false
                        },
                        { width: "15%", targets: 0 },
                        { width: "18%", targets: 1 },
                        { width: "20%", targets: 2 },
                        { width: "20%", targets: 3 },
                        { width: "12%", targets: 4 },
                        { width: "15%", targets: 5 }
                    ],
                    initComplete: function() {
                        console.log('DataTable de beneficiarios inicializado correctamente');
                        this.api().columns.adjust();
                    },
                    drawCallback: function() {
                        actualizarContador();
                    }
                });
            } else {
                console.log('No hay datos para inicializar DataTable de beneficiarios');
            }
            
            // Inicializar DataTable de nuevos ingresos si hay datos
            if ($('#tabla-nuevos-ingresos').length && $('#tabla-nuevos-ingresos tbody tr').length > 0) {
                tablaNuevosIngresos = $('#tabla-nuevos-ingresos').DataTable({
                    responsive: false,
                    scrollX: false,
                    autoWidth: false,
                    language: {
                        "decimal": ",",
                        "emptyTable": "No hay datos disponibles",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                        "infoFiltered": "(filtrado de _MAX_ registros Totales)",
                        "infoPostFix": "",
                        "thousands": ".",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "No se encontraron registros coincidentes",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: [[0, 'desc']],
                    columnDefs: [
                        { 
                            targets: [4], // Columna de acciones
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
            }
            
            // Configurar eventos para los filtros dependientes en modales
            $('#municipio').on('change', function() {
                cargarParroquias('municipio', 'parroquia');
            });
            
            $('#parroquia').on('change', function() {
                cargarSectores('parroquia', 'sector');
            });
            
            // Configurar eventos para los filtros de búsqueda
            $('#filtro-municipio').on('change', function() {
                cargarParroquiasFiltro();
            });
            
            $('#filtro-parroquia').on('change', function() {
                cargarSectoresFiltro();
            });
            
            // Configurar eventos para cálculo automático
            $('#fecha_nacimiento').on('change', calcularEdadBeneficiario);
            $('#peso_kg, #talla_cm').on('input', calcularIMCBeneficiario);
            
            $('#ni_fecha_nacimiento').on('change', calcularEdadNuevoIngreso);
            $('#ni_peso_kg, #ni_talla_cm').on('input', calcularIMCNuevoIngreso);
            
            // Configurar eventos para condición femenina en nuevo beneficiario
            $('#genero').on('change', mostrarCondicionFemeninaNuevo);
            $('#condicion_lactante_nuevo, #condicion_gestante_nuevo, #condicion_nada_nuevo').on('change', mostrarSubcondicionNuevo);
            
            // Configurar eventos para condición femenina en nuevo ingreso
            $('#ni_genero').on('change', mostrarCondicionFemeninaNI);
            $('#ni_condicion_lactante, #ni_condicion_gestante, #ni_condicion_nada').on('change', mostrarSubcondicionNI);
            
            // Event listeners para filtros
            $('#btn-aplicar-filtros').on('click', aplicarFiltros);
            $('#btn-limpiar-filtros').on('click', limpiarFiltros);
            
            // Aplicar filtros automáticamente cuando cambian los selects (excepto municipio y parroquia que ya tienen sus propios handlers)
            $('#filtro-sector, #filtro-caso').on('change', aplicarFiltros);
            
            // Configurar eventos para formularios
            $('#formNuevoBeneficiario').on('submit', function(e) {
                e.preventDefault();
                
                // Validar formulario
                const cedula = $('#cedula_beneficiario').val();
                const nombres = $('#nombres').val();
                const apellidos = $('#apellidos').val();
                const genero = $('#genero').val();
                const condicion = $('input[name="condicion_femenina"]:checked').val();
                
                // Validaciones básicas
                if (!cedula || !nombres || !apellidos || !genero) {
                    alert('Por favor, complete los campos requeridos: cédula, nombres, apellidos y género');
                    return;
                }
                
                // Validaciones específicas para género femenino
                if (genero === 'F') {
                    if (condicion === 'lactante') {
                        const fechaBebe = $('#fecha_nacimiento_bebe_nuevo').val();
                        if (!fechaBebe) {
                            alert('Por favor, ingrese la fecha de nacimiento del bebé para la beneficiaria lactante');
                            return;
                        }
                    } else if (condicion === 'gestante') {
                        const semanas = $('#semanas_gestacion_nuevo').val();
                        if (!semanas || semanas < 1 || semanas > 42) {
                            alert('Por favor, ingrese un número válido de semanas de gestación (1-42)');
                            return;
                        }
                    }
                }
                
                // Enviar formulario
                this.submit();
            });
            
            $('#formEditarBeneficiario').on('submit', function(e) {
                e.preventDefault();
                
                // Validar formulario
                const cedula = $('#editar_cedula_beneficiario').val();
                const nombres = $('#editar_nombres').val();
                const apellidos = $('#editar_apellidos').val();
                const genero = $('#editar_genero').val();
                const condicion = $('input[name="condicion_femenina"]:checked').val();
                
                // Validaciones básicas
                if (!cedula || !nombres || !apellidos || !genero) {
                    alert('Por favor, complete los campos requeridos: cédula, nombres, apellidos y género');
                    return;
                }
                
                // Validaciones específicas para género femenino
                if (genero === 'F') {
                    if (condicion === 'lactante') {
                        const fechaBebe = $('#editar_fecha_nacimiento_bebe').val();
                        if (!fechaBebe) {
                            alert('Por favor, ingrese la fecha de nacimiento del bebé para la beneficiaria lactante');
                            return;
                        }
                    } else if (condicion === 'gestante') {
                        const semanas = $('#editar_semanas_gestacion').val();
                        if (!semanas || semanas < 1 || semanas > 42) {
                            alert('Por favor, ingrese un número válido de semanas de gestación (1-42)');
                            return;
                        }
                    }
                }
                
                // Enviar formulario
                this.submit();
            });
            
            $('#formNuevoIngreso').on('submit', function(e) {
                e.preventDefault();
                
                // Validar formulario
                const cedula = $('#ni_cedula_beneficiario').val();
                const nombres = $('#ni_nombres').val();
                const apellidos = $('#ni_apellidos').val();
                const genero = $('#ni_genero').val();
                const condicion = $('input[name="condicion_femenina"]:checked').val();
                
                // Validaciones básicas
                if (!cedula || !nombres || !apellidos || !genero) {
                    alert('Por favor, complete los campos requeridos: cédula, nombres, apellidos y género');
                    return;
                }
                
                // Validaciones específicas para género femenino
                if (genero === 'F') {
                    if (condicion === 'lactante') {
                        const fechaBebe = $('#ni_fecha_nacimiento_bebe').val();
                        if (!fechaBebe) {
                            alert('Por favor, ingrese la fecha de nacimiento del bebé para la beneficiaria lactante');
                            return;
                        }
                    } else if (condicion === 'gestante') {
                        const semanas = $('#ni_semanas_gestacion').val();
                        if (!semanas || semanas < 1 || semanas > 42) {
                            alert('Por favor, ingrese un número válido de semanas de gestación (1-42)');
                            return;
                        }
                    }
                }
                
                // Confirmar
                if (confirm('¿Está seguro de guardar este nuevo ingreso?\n\nEste registro se guardará en la sección de "Nuevos Ingresos" y podrá ser incorporado posteriormente a la base de datos principal.')) {
                    this.submit();
                }
            });
            
            // Ocultar modales al inicio
            $('#nuevoBeneficiarioModal, #nuevoIngresoModal, #editarBeneficiarioModal').hide();
            
            // Ocultar mensaje de filtros inicialmente
            $('#mensaje-filtros').removeClass('show');
            
            console.log('Sistema de beneficiarios inicializado correctamente');
        });
    </script>
</body>
</html>