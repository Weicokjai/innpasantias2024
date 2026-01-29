<?php
// Configurar rutas base
$base_url = '/innpasantias2026/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2026/public/';

// Incluir configuración de base de datos
include_once '../../config/database.php';

$currentPage = 'Reportes';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Conexión a base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar variables
$stats = [];
$charts_data = [];
$reportes = [];
$error_message = null;

try {
    // ==================== ESTADÍSTICAS PRINCIPALES ====================
    
    // 1. Total de beneficiarios activos (status = 'ACTIVO' o similar)
    $query = "SELECT COUNT(*) as total FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_beneficiarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Beneficiarios por género - CORREGIDO para 'M' y 'F'
    $query = "SELECT 
                CASE 
                    WHEN genero = 'F' THEN 'Mujeres'
                    WHEN genero = 'M' THEN 'Hombres'
                    WHEN genero = 'FEMENINO' THEN 'Mujeres'
                    WHEN genero = 'MASCULINO' THEN 'Hombres'
                    ELSE 'Otros'
                END as genero,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje
              FROM beneficiario 
              WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
              GROUP BY genero
              ORDER BY cantidad DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['por_genero'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay datos de género en la consulta anterior, hacer una consulta más específica
    if (empty($stats['por_genero'])) {
        $query = "SELECT 
                    CASE 
                        WHEN UPPER(genero) = 'F' OR UPPER(genero) = 'FEMENINO' THEN 'Mujeres'
                        WHEN UPPER(genero) = 'M' OR UPPER(genero) = 'MASCULINO' THEN 'Hombres'
                        ELSE 'Otros'
                    END as genero,
                    COUNT(*) as cantidad,
                    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje
                  FROM beneficiario 
                  WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
                  AND genero IS NOT NULL AND genero != ''
                  GROUP BY 
                    CASE 
                        WHEN UPPER(genero) = 'F' OR UPPER(genero) = 'FEMENINO' THEN 'Mujeres'
                        WHEN UPPER(genero) = 'M' OR UPPER(genero) = 'MASCULINO' THEN 'Hombres'
                        ELSE 'Otros'
                    END
                  ORDER BY cantidad DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['por_genero'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. Distribución por edad - CORREGIDO según especificaciones (0-5, 6-10, 11-17, 18-55, 56+)
    $query = "SELECT 
                CASE 
                    WHEN edad < 6 THEN '0-5 años (0-72 meses)'
                    WHEN edad BETWEEN 6 AND 10 THEN '6-10 años'
                    WHEN edad BETWEEN 11 AND 17 THEN '11-17 años'
                    WHEN edad BETWEEN 18 AND 55 THEN '18-55 años'
                    ELSE '56+ años'
                END as rango_edad,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO' AND edad IS NOT NULL)), 1) as porcentaje
              FROM beneficiario 
              WHERE (status LIKE '%ACTIVO%' OR status = 'ACTIVO') AND edad IS NOT NULL
              GROUP BY 
                CASE 
                    WHEN edad < 6 THEN '0-5 años (0-72 meses)'
                    WHEN edad BETWEEN 6 AND 10 THEN '6-10 años'
                    WHEN edad BETWEEN 11 AND 17 THEN '11-17 años'
                    WHEN edad BETWEEN 18 AND 55 THEN '18-55 años'
                    ELSE '56+ años'
                END
              ORDER BY 
                CASE 
                    WHEN edad < 6 THEN 1
                    WHEN edad BETWEEN 6 AND 10 THEN 2
                    WHEN edad BETWEEN 11 AND 17 THEN 3
                    WHEN edad BETWEEN 18 AND 55 THEN 4
                    ELSE 5
                END";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['por_edad'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Estado nutricional - ajustado para valores 1,2,3,4 o texto
    $query = "SELECT 
                CASE 
                    WHEN situacion_dx = '1' OR UPPER(situacion_dx) LIKE '%NORMAL%' THEN 'Normal'
                    WHEN situacion_dx = '2' OR UPPER(situacion_dx) LIKE '%DEFICIT%' OR UPPER(situacion_dx) LIKE '%DEFISIS%' THEN 'Defisis'
                    WHEN situacion_dx = '3' OR UPPER(situacion_dx) LIKE '%SOBREPESO%' THEN 'Sobrepeso'
                    WHEN situacion_dx = '4' OR UPPER(situacion_dx) LIKE '%OBESIDAD%' THEN 'Obesidad'
                    ELSE 'Sin diagnóstico'
                END as estado_nutricional,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje
              FROM beneficiario 
              WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
              GROUP BY 
                CASE 
                    WHEN situacion_dx = '1' OR UPPER(situacion_dx) LIKE '%NORMAL%' THEN 'Normal'
                    WHEN situacion_dx = '2' OR UPPER(situacion_dx) LIKE '%DEFICIT%' OR UPPER(situacion_dx) LIKE '%DEFISIS%' THEN 'Defisis'
                    WHEN situacion_dx = '3' OR UPPER(situacion_dx) LIKE '%SOBREPESO%' THEN 'Sobrepeso'
                    WHEN situacion_dx = '4' OR UPPER(situacion_dx) LIKE '%OBESIDAD%' THEN 'Obesidad'
                    ELSE 'Sin diagnóstico'
                END
              ORDER BY cantidad DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['estado_nutricional'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Gestantes y lactantes - CORREGIDO para ENUM('SI','NO')
    $query = "SELECT 
                'Gestantes' as tipo,
                COUNT(*) as cantidad
              FROM beneficiario 
              WHERE (status LIKE '%ACTIVO%' OR status = 'ACTIVO') AND gestante = 'SI'
              UNION ALL
              SELECT 
                'Lactantes',
                COUNT(*)
              FROM beneficiario 
              WHERE (status LIKE '%ACTIVO%' OR status = 'ACTIVO') AND lactando = 'SI'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['gestantes_lactantes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Distribución de IMC
    $query = "SELECT 
                CASE 
                    WHEN imc < 18.5 THEN 'Bajo peso'
                    WHEN imc BETWEEN 18.5 AND 24.9 THEN 'Peso normal'
                    WHEN imc BETWEEN 25 AND 29.9 THEN 'Sobrepeso'
                    WHEN imc >= 30 THEN 'Obesidad'
                    ELSE 'Sin medición'
                END as categoria_imc,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje
              FROM beneficiario 
              WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
              GROUP BY categoria_imc
              ORDER BY 
                CASE categoria_imc
                    WHEN 'Bajo peso' THEN 1
                    WHEN 'Peso normal' THEN 2
                    WHEN 'Sobrepeso' THEN 3
                    WHEN 'Obesidad' THEN 4
                    WHEN 'Sin medición' THEN 5
                END";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['distribucion_imc'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ==================== ESTADÍSTICAS POR MUNICIPIO ====================
    
    // Verificar si hay datos en la tabla ubicacion
    $query = "SELECT COUNT(*) as total FROM ubicacion";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_ubicaciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Si hay ubicaciones, usar JOIN, si no, usar datos directos de beneficiario
    if ($total_ubicaciones > 0) {
        // Estadísticas por municipio usando JOIN con ubicacion
        $query = "SELECT 
                    u.municipio,
                    COUNT(b.id_beneficiario) as cantidad,
                    ROUND((COUNT(b.id_beneficiario) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje,
                    ROUND(AVG(b.edad), 1) as edad_promedio,
                    ROUND(AVG(b.imc), 1) as imc_promedio,
                    SUM(CASE WHEN UPPER(b.genero) = 'F' OR UPPER(b.genero) = 'FEMENINO' THEN 1 ELSE 0 END) as mujeres,
                    SUM(CASE WHEN UPPER(b.genero) = 'M' OR UPPER(b.genero) = 'MASCULINO' THEN 1 ELSE 0 END) as hombres,
                    SUM(CASE WHEN b.gestante = 'SI' THEN 1 ELSE 0 END) as gestantes,
                    SUM(CASE WHEN b.lactando = 'SI' THEN 1 ELSE 0 END) as lactantes
                  FROM ubicacion u
                  INNER JOIN beneficiario b ON u.id_ubicacion = b.id_ubicacion 
                    AND (b.status LIKE '%ACTIVO%' OR b.status = 'ACTIVO')
                  WHERE u.municipio IS NOT NULL AND TRIM(u.municipio) != ''
                  GROUP BY u.municipio
                  HAVING COUNT(b.id_beneficiario) > 0
                  ORDER BY cantidad DESC";
    } else {
        // Si no hay tabla ubicacion, intentar obtener municipios de otra manera
        $query = "SELECT 
                    'PALAVECINO' as municipio,  -- Municipio por defecto según tu ejemplo
                    COUNT(*) as cantidad,
                    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO')), 1) as porcentaje,
                    ROUND(AVG(edad), 1) as edad_promedio,
                    ROUND(AVG(imc), 1) as imc_promedio,
                    SUM(CASE WHEN UPPER(genero) = 'F' OR UPPER(genero) = 'FEMENINO' THEN 1 ELSE 0 END) as mujeres,
                    SUM(CASE WHEN UPPER(genero) = 'M' OR UPPER(genero) = 'MASCULINO' THEN 1 ELSE 0 END) as hombres,
                    SUM(CASE WHEN gestante = 'SI' THEN 1 ELSE 0 END) as gestantes,
                    SUM(CASE WHEN lactando = 'SI' THEN 1 ELSE 0 END) as lactantes
                  FROM beneficiario 
                  WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
                  GROUP BY 1
                  ORDER BY cantidad DESC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['por_municipio'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si todavía no hay datos de municipio, crear datos básicos
    if (empty($stats['por_municipio'])) {
        // Consultar datos generales para crear un municipio "GENERAL"
        $query = "SELECT 
                    COUNT(*) as cantidad,
                    ROUND(AVG(edad), 1) as edad_promedio,
                    ROUND(AVG(imc), 1) as imc_promedio,
                    SUM(CASE WHEN UPPER(genero) = 'F' OR UPPER(genero) = 'FEMENINO' THEN 1 ELSE 0 END) as mujeres,
                    SUM(CASE WHEN UPPER(genero) = 'M' OR UPPER(genero) = 'MASCULINO' THEN 1 ELSE 0 END) as hombres,
                    SUM(CASE WHEN gestante = 'SI' THEN 1 ELSE 0 END) as gestantes,
                    SUM(CASE WHEN lactando = 'SI' THEN 1 ELSE 0 END) as lactantes
                  FROM beneficiario 
                  WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $datos_generales = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($datos_generales['cantidad'] > 0) {
            $stats['por_municipio'] = [[
                'municipio' => 'GENERAL',
                'cantidad' => $datos_generales['cantidad'],
                'porcentaje' => 100,
                'edad_promedio' => $datos_generales['edad_promedio'],
                'imc_promedio' => $datos_generales['imc_promedio'],
                'mujeres' => $datos_generales['mujeres'],
                'hombres' => $datos_generales['hombres'],
                'gestantes' => $datos_generales['gestantes'],
                'lactantes' => $datos_generales['lactantes']
            ]];
        }
    }
    
    // Top 5 municipios
    $stats['top_municipios'] = array_slice($stats['por_municipio'], 0, 5);
    
    // ==================== DATOS PARA GRÁFICOS ====================
    
    // Datos para gráfico de género
    $charts_data['genero_labels'] = [];
    $charts_data['genero_values'] = [];
    $charts_data['genero_colors'] = ['#EF4444', '#3B82F6', '#10B981']; // Mujeres, Hombres, Otros
    
    // Calcular total de hombres y mujeres desde los datos de municipios si la consulta directa no funciona
    $total_mujeres = 0;
    $total_hombres = 0;
    foreach ($stats['por_municipio'] as $municipio) {
        $total_mujeres += $municipio['mujeres'];
        $total_hombres += $municipio['hombres'];
    }
    
    if (!empty($stats['por_genero'])) {
        foreach ($stats['por_genero'] as $item) {
            $charts_data['genero_labels'][] = $item['genero'] . ' (' . $item['porcentaje'] . '%)';
            $charts_data['genero_values'][] = $item['cantidad'];
        }
    } else if ($total_mujeres > 0 || $total_hombres > 0) {
        // Usar datos calculados de municipios
        $total_genero = $total_mujeres + $total_hombres;
        $porcentaje_mujeres = $total_genero > 0 ? round(($total_mujeres / $total_genero) * 100, 1) : 0;
        $porcentaje_hombres = $total_genero > 0 ? round(($total_hombres / $total_genero) * 100, 1) : 0;
        
        if ($total_mujeres > 0) {
            $charts_data['genero_labels'][] = 'Mujeres (' . $porcentaje_mujeres . '%)';
            $charts_data['genero_values'][] = $total_mujeres;
        }
        
        if ($total_hombres > 0) {
            $charts_data['genero_labels'][] = 'Hombres (' . $porcentaje_hombres . '%)';
            $charts_data['genero_values'][] = $total_hombres;
        }
    }
    
    // Datos para gráfico de edad
    $charts_data['edad_labels'] = [];
    $charts_data['edad_values'] = [];
    
    if (!empty($stats['por_edad'])) {
        foreach ($stats['por_edad'] as $item) {
            $charts_data['edad_labels'][] = $item['rango_edad'];
            $charts_data['edad_values'][] = $item['cantidad'];
        }
    }
    
    // Datos para gráfico de estado nutricional
    $charts_data['nutricional_labels'] = [];
    $charts_data['nutricional_values'] = [];
    $charts_data['nutricional_colors'] = ['#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280'];
    
    if (!empty($stats['estado_nutricional'])) {
        foreach ($stats['estado_nutricional'] as $item) {
            $charts_data['nutricional_labels'][] = $item['estado_nutricional'] . ' (' . $item['porcentaje'] . '%)';
            $charts_data['nutricional_values'][] = $item['cantidad'];
        }
    }
    
    // Datos para gráfico de municipios
    $charts_data['municipio_labels'] = [];
    $charts_data['municipio_values'] = [];
    $charts_data['municipio_colors'] = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'];
    
    if (!empty($stats['top_municipios'])) {
        foreach ($stats['top_municipios'] as $item) {
            $charts_data['municipio_labels'][] = $item['municipio'] . ' (' . $item['porcentaje'] . '%)';
            $charts_data['municipio_values'][] = $item['cantidad'];
        }
    }
    
    // ==================== REPORTES ESPECIALES ====================
    
    // Reporte 1: Beneficiarios con mayor riesgo (desnutrición y bajo peso)
    $query = "SELECT 
                b.cedula_beneficiario,
                CONCAT(b.nombres, ' ', b.apellidos) as nombre_completo,
                b.edad,
                b.imc,
                b.situacion_dx,
                b.gestante,
                b.lactando,
                CASE 
                    WHEN b.situacion_dx = '2' OR UPPER(b.situacion_dx) LIKE '%DEFICIT%' OR UPPER(b.situacion_dx) LIKE '%DEFISIS%' THEN 'Defisis'
                    WHEN b.imc < 18.5 THEN 'Bajo peso'
                    ELSE 'Otro'
                END as riesgo
              FROM beneficiario b
              WHERE (b.status LIKE '%ACTIVO%' OR b.status = 'ACTIVO')
                AND (b.situacion_dx = '2' OR b.imc < 18.5 
                     OR UPPER(b.situacion_dx) LIKE '%DEFICIT%' 
                     OR UPPER(b.situacion_dx) LIKE '%DEFISIS%')
              ORDER BY b.imc ASC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reportes['alto_riesgo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reporte 2: Gestantes por semanas de gestación
    $query = "SELECT 
                b.cedula_beneficiario,
                CONCAT(b.nombres, ' ', b.apellidos) as nombre_completo,
                b.semanas_gestacion,
                b.imc,
                b.edad,
                CASE 
                    WHEN b.semanas_gestacion < 13 THEN 'Primer trimestre'
                    WHEN b.semanas_gestacion BETWEEN 13 AND 26 THEN 'Segundo trimestre'
                    WHEN b.semanas_gestacion > 26 THEN 'Tercer trimestre'
                    ELSE 'Sin especificar'
                END as trimestre
              FROM beneficiario b
              WHERE (b.status LIKE '%ACTIVO%' OR b.status = 'ACTIVO') 
                AND b.gestante = 'SI'
                AND b.semanas_gestacion > 0
              ORDER BY b.semanas_gestacion DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reportes['gestantes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reporte 3: Distribución por municipio (usar datos ya calculados)
    $reportes['municipios_detalle'] = $stats['por_municipio'];
    
    // Reporte 4: Representantes con más beneficiarios
    $query = "SELECT 
                r.id_representante,
                r.cedula_representante,
                CONCAT(r.nombres, ' ', r.apellidos) as nombre_representante,
                r.numero_contacto,
                COUNT(b.id_beneficiario) as total_beneficiarios
              FROM representante r
              LEFT JOIN beneficiario b ON r.id_representante = b.id_representante 
                AND (b.status LIKE '%ACTIVO%' OR b.status = 'ACTIVO')
              GROUP BY r.id_representante
              HAVING COUNT(b.id_beneficiario) > 0
              ORDER BY total_beneficiarios DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reportes['top_representantes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas de representantes
    $query = "SELECT 
                COUNT(DISTINCT r.id_representante) as total_representantes,
                COALESCE(AVG(rep_stats.total_beneficiarios), 0) as promedio_por_representante,
                COALESCE(MAX(rep_stats.total_beneficiarios), 0) as maximo_beneficiarios
              FROM representante r
              LEFT JOIN (
                  SELECT id_representante, COUNT(*) as total_beneficiarios
                  FROM beneficiario 
                  WHERE status LIKE '%ACTIVO%' OR status = 'ACTIVO'
                  GROUP BY id_representante
              ) rep_stats ON r.id_representante = rep_stats.id_representante";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['representantes'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
    error_log("Error en reportes: " . $e->getMessage());
}

// DEBUG: Ver datos obtenidos
error_log("Total beneficiarios: " . ($stats['total_beneficiarios'] ?? 0));
error_log("Datos por género: " . print_r($stats['por_genero'] ?? [], true));
error_log("Datos por municipio: " . print_r($stats['por_municipio'] ?? [], true));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Estadísticos - Sistema de Nutrición</title>
    <link href="<?php echo $base_url; ?>assets/css/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-stat {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .risk-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .progress-bar {
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
        .imc-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .imc-bajo { background-color: #EF4444; }
        .imc-normal { background-color: #10B981; }
        .imc-sobrepeso { background-color: #F59E0B; }
        .imc-obesidad { background-color: #DC2626; }
        .data-cell {
            position: relative;
        }
        .data-cell:hover .data-tooltip {
            display: block;
        }
        .data-tooltip {
            display: none;
            position: absolute;
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 100;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include $include_path . 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include $include_path . 'components/header.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Error al cargar datos:</strong> <?php echo htmlspecialchars($error_message); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Encabezado -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard de Reportes</h1>
                            <p class="text-gray-600 mt-2">Estadísticas y análisis del sistema de nutrición</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Actualizado: <?php echo date('d/m/Y H:i'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjetas de estadísticas principales -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                    <!-- Total beneficiarios -->
                    <div class="card-stat bg-white rounded-xl shadow p-4 md:p-6 border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Beneficiarios Activos</p>
                                <h3 class="text-2xl md:text-3xl font-bold mt-2 text-blue-600">
                                    <?php echo number_format($stats['total_beneficiarios'] ?? 0); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Registrados en el sistema</p>
                            </div>
                            <div class="bg-blue-50 p-2 md:p-3 rounded-full">
                                <i class="fas fa-users text-blue-500 text-xl md:text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <?php 
                            $hombres = 0;
                            $mujeres = 0;
                            
                            // Calcular desde datos de género
                            if (!empty($stats['por_genero'])) {
                                foreach ($stats['por_genero'] as $item) {
                                    if ($item['genero'] == 'Hombres') $hombres = $item['cantidad'];
                                    if ($item['genero'] == 'Mujeres') $mujeres = $item['cantidad'];
                                }
                            }
                            
                            // O desde datos de municipios
                            if ($hombres == 0 && $mujeres == 0 && !empty($stats['por_municipio'])) {
                                foreach ($stats['por_municipio'] as $municipio) {
                                    $mujeres += $municipio['mujeres'];
                                    $hombres += $municipio['hombres'];
                                }
                            }
                            ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Hombres</span>
                                <span class="font-medium"><?php echo number_format($hombres); ?></span>
                            </div>
                            <div class="progress-bar bg-gray-200 mt-1">
                                <div class="bg-blue-500 h-full" 
                                     style="width: <?php echo ($stats['total_beneficiarios'] ?? 0) > 0 ? ($hombres / $stats['total_beneficiarios']) * 100 : 0; ?>%"></div>
                            </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-gray-600">Mujeres</span>
                                <span class="font-medium"><?php echo number_format($mujeres); ?></span>
                            </div>
                            <div class="progress-bar bg-gray-200 mt-1">
                                <div class="bg-pink-500 h-full" 
                                     style="width: <?php echo ($stats['total_beneficiarios'] ?? 0) > 0 ? ($mujeres / $stats['total_beneficiarios']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Municipios -->
                    <div class="card-stat bg-white rounded-xl shadow p-4 md:p-6 border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Municipios Activos</p>
                                <h3 class="text-2xl md:text-3xl font-bold mt-2 text-green-600">
                                    <?php echo count($stats['por_municipio'] ?? []); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Con beneficiarios registrados</p>
                            </div>
                            <div class="bg-green-50 p-2 md:p-3 rounded-full">
                                <i class="fas fa-map-marker-alt text-green-500 text-xl md:text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-star text-yellow-500 mr-1"></i>
                                Municipio con más beneficiarios:
                            </div>
                            <div class="text-sm font-medium mt-1 truncate">
                                <?php 
                                if (!empty($stats['top_municipios'])) {
                                    echo htmlspecialchars($stats['top_municipios'][0]['municipio']) . ' (' . number_format($stats['top_municipios'][0]['cantidad']) . ')';
                                } else {
                                    echo 'No disponible';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Representantes -->
                    <div class="card-stat bg-white rounded-xl shadow p-4 md:p-6 border-l-4 border-purple-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Representantes</p>
                                <h3 class="text-2xl md:text-3xl font-bold mt-2 text-purple-600">
                                    <?php echo number_format($stats['representantes']['total_representantes'] ?? 0); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Registrados en el sistema</p>
                            </div>
                            <div class="bg-purple-50 p-2 md:p-3 rounded-full">
                                <i class="fas fa-user-friends text-purple-500 text-xl md:text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Promedio por rep.</span>
                                <span class="font-medium">
                                    <?php echo number_format($stats['representantes']['promedio_por_representante'] ?? 0, 1); ?> beneficiarios
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gestantes y Lactantes -->
                    <div class="card-stat bg-white rounded-xl shadow p-4 md:p-6 border-l-4 border-pink-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Gestantes & Lactantes</p>
                                <h3 class="text-2xl md:text-3xl font-bold mt-2 text-pink-600">
                                    <?php 
                                    $total_especial = 0;
                                    if (!empty($stats['gestantes_lactantes'])) {
                                        foreach ($stats['gestantes_lactantes'] as $item) {
                                            $total_especial += $item['cantidad'];
                                        }
                                    }
                                    echo number_format($total_especial);
                                    ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Casos especiales</p>
                            </div>
                            <div class="bg-pink-50 p-2 md:p-3 rounded-full">
                                <i class="fas fa-baby text-pink-500 text-xl md:text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <?php if (!empty($stats['gestantes_lactantes'])): ?>
                                    <?php foreach ($stats['gestantes_lactantes'] as $item): ?>
                                    <div class="text-center">
                                        <div class="font-medium"><?php echo number_format($item['cantidad']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $item['tipo']; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center">
                                        <div class="font-medium">0</div>
                                        <div class="text-xs text-gray-500">Gestantes</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">0</div>
                                        <div class="text-xs text-gray-500">Lactantes</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de Gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
                    <!-- Gráfico de Género -->
                    <div class="bg-white rounded-xl shadow p-4 md:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Distribución por Género</h3>
                            <div class="text-sm text-gray-500">
                                Total: <?php echo number_format($stats['total_beneficiarios'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="generoChart"></canvas>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-center text-sm">
                            <?php if (!empty($charts_data['genero_values'])): ?>
                                <?php for($i = 0; $i < min(2, count($charts_data['genero_values'])); $i++): ?>
                                <div>
                                    <div class="font-medium"><?php echo number_format($charts_data['genero_values'][$i]); ?></div>
                                    <div class="text-gray-500"><?php echo str_replace([' (', '%)'], '', $charts_data['genero_labels'][$i]); ?></div>
                                </div>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Estado Nutricional -->
                    <div class="bg-white rounded-xl shadow p-4 md:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Estado Nutricional</h3>
                            <div class="text-sm text-gray-500">
                                Basado en diagnóstico
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="nutricionalChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Edad -->
                    <div class="bg-white rounded-xl shadow p-4 md:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Distribución por Edad</h3>
                        </div>
                        <div class="h-64">
                            <canvas id="edadChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Municipios -->
                    <div class="bg-white rounded-xl shadow p-4 md:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Top Municipios</h3>
                            <div class="text-sm text-gray-500">
                                <?php echo count($stats['top_municipios'] ?? []); ?> municipios
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="municipioChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de Municipios -->
                <div class="bg-white rounded-xl shadow mb-8 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Reporte por Municipio</h3>
                                <p class="text-sm text-gray-600 mt-1">Estadísticas detalladas por ubicación geográfica</p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="text-sm text-gray-500">
                                    Mostrando <?php echo min(10, count($reportes['municipios_detalle'] ?? [])); ?> de <?php echo count($reportes['municipios_detalle'] ?? []); ?> municipios
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Municipio
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Mujeres
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Hombres
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Edad Prom.
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        IMC Prom.
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Gestantes
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (!empty($reportes['municipios_detalle'])): ?>
                                    <?php foreach(array_slice($reportes['municipios_detalle'], 0, 10) as $index => $municipio): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150 <?php echo $index % 2 == 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                        <td class="px-4 md:px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-bold"><?php echo $index + 1; ?></span>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($municipio['municipio']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <?php echo number_format($municipio['cantidad']); ?> beneficiarios
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 data-cell">
                                            <div class="text-sm font-semibold text-gray-900">
                                                <?php echo number_format($municipio['cantidad']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['porcentaje'] ?? 0; ?>%
                                            </div>
                                            <div class="data-tooltip">
                                                <?php echo $municipio['porcentaje'] ?? 0; ?>% del total
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 data-cell">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['mujeres']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['cantidad'] > 0 ? round(($municipio['mujeres'] / $municipio['cantidad']) * 100, 1) : 0; ?>%
                                            </div>
                                            <div class="data-tooltip">
                                                <?php echo $municipio['cantidad'] > 0 ? round(($municipio['mujeres'] / $municipio['cantidad']) * 100, 1) : 0; ?>% del municipio
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 data-cell">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['hombres']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['cantidad'] > 0 ? round(($municipio['hombres'] / $municipio['cantidad']) * 100, 1) : 0; ?>%
                                            </div>
                                            <div class="data-tooltip">
                                                <?php echo $municipio['cantidad'] > 0 ? round(($municipio['hombres'] / $municipio['cantidad']) * 100, 1) : 0; ?>% del municipio
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['edad_promedio'] ?? 0, 1); ?> años
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4">
                                            <div class="flex items-center">
                                                <?php 
                                                $imc_class = 'imc-normal';
                                                $imc_text = 'Normal';
                                                if ($municipio['imc_promedio'] < 18.5) {
                                                    $imc_class = 'imc-bajo';
                                                    $imc_text = 'Bajo peso';
                                                } elseif ($municipio['imc_promedio'] <= 24.9) {
                                                    $imc_class = 'imc-normal';
                                                    $imc_text = 'Normal';
                                                } elseif ($municipio['imc_promedio'] <= 29.9) {
                                                    $imc_class = 'imc-sobrepeso';
                                                    $imc_text = 'Sobrepeso';
                                                } elseif ($municipio['imc_promedio'] > 29.9) {
                                                    $imc_class = 'imc-obesidad';
                                                    $imc_text = 'Obesidad';
                                                }
                                                ?>
                                                <span class="imc-indicator <?php echo $imc_class; ?>"></span>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo number_format($municipio['imc_promedio'] ?? 0, 1); ?>
                                                    </div>
                                                    <div class="text-xs <?php 
                                                        echo $imc_text == 'Bajo peso' ? 'text-red-600' : 
                                                             ($imc_text == 'Sobrepeso' ? 'text-yellow-600' : 
                                                             ($imc_text == 'Obesidad' ? 'text-red-600' : 'text-green-600')); ?>">
                                                        <?php echo $imc_text; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 data-cell">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['gestantes']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['mujeres'] > 0 ? round(($municipio['gestantes'] / $municipio['mujeres']) * 100, 1) : 0; ?>%
                                            </div>
                                            <div class="data-tooltip">
                                                <?php echo $municipio['mujeres'] > 0 ? round(($municipio['gestantes'] / $municipio['mujeres']) * 100, 1) : 0; ?>% de mujeres
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                                            <p>No hay datos de municipios disponibles</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Reportes de Riesgo y Gestantes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
                    <!-- Beneficiarios en Alto Riesgo -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-orange-50">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Beneficiarios en Alto Riesgo</h3>
                                    <p class="text-sm text-gray-600 mt-1">Defisís o bajo peso (IMC < 18.5)</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <?php echo count($reportes['alto_riesgo'] ?? []); ?> casos
                                </span>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Beneficiario
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Edad
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IMC
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Riesgo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (!empty($reportes['alto_riesgo'])): ?>
                                        <?php foreach($reportes['alto_riesgo'] as $beneficiario): ?>
                                        <tr class="hover:bg-red-50 transition duration-150">
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($beneficiario['nombre_completo'] ?? ''); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo $beneficiario['cedula_beneficiario'] ?? ''; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $beneficiario['edad'] ?? ''; ?> años
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="text-sm font-semibold <?php echo ($beneficiario['imc'] ?? 0) < 18.5 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                                    <?php echo isset($beneficiario['imc']) ? number_format($beneficiario['imc'], 1) : 'N/A'; ?>
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium 
                                                    <?php echo ($beneficiario['riesgo'] ?? '') == 'Desnutrición' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo $beneficiario['riesgo'] ?? ''; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-check-circle text-3xl mb-2 text-green-300"></i>
                                                <p>No hay beneficiarios identificados en alto riesgo</p>
                                                <p class="text-sm mt-1">¡Excelente trabajo!</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Gestantes Registradas -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Gestantes Registradas</h3>
                                    <p class="text-sm text-gray-600 mt-1">Seguimiento por semanas de gestación</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo count($reportes['gestantes'] ?? []); ?> gestantes
                                </span>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Gestante
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Semanas
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trimestre
                                        </th>
                                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IMC
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (!empty($reportes['gestantes'])): ?>
                                        <?php foreach($reportes['gestantes'] as $gestante): ?>
                                        <tr class="hover:bg-blue-50 transition duration-150">
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-baby text-blue-600 text-sm"></i>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($gestante['nombre_completo'] ?? ''); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo $gestante['cedula_beneficiario'] ?? ''; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?php echo $gestante['semanas_gestacion'] ?? 0; ?> semanas
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <?php 
                                                $color_class = 'bg-blue-100 text-blue-800';
                                                $icon = 'fas fa-circle text-blue-500';
                                                $trimestre = $gestante['trimestre'] ?? 'Sin especificar';
                                                
                                                if ($trimestre == 'Segundo trimestre') {
                                                    $color_class = 'bg-green-100 text-green-800';
                                                    $icon = 'fas fa-circle text-green-500';
                                                } elseif ($trimestre == 'Tercer trimestre') {
                                                    $color_class = 'bg-yellow-100 text-yellow-800';
                                                    $icon = 'fas fa-circle text-yellow-500';
                                                }
                                                ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $color_class; ?>">
                                                    <i class="<?php echo $icon; ?> mr-1 text-xs"></i>
                                                    <?php echo $trimestre; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 md:px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo isset($gestante['imc']) ? number_format($gestante['imc'], 1) : 'N/A'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-female text-3xl mb-2 text-gray-300"></i>
                                                <p>No hay gestantes registradas actualmente</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen Estadístico -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow p-4 md:p-6 mb-8 border border-blue-100">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                        <i class="fas fa-chart-line mr-2"></i>Resumen Estadístico Completo
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Distribución IMC -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Distribución por IMC</h4>
                            <div class="space-y-2">
                                <?php if (!empty($stats['distribucion_imc'])): ?>
                                    <?php foreach ($stats['distribucion_imc'] as $imc): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-600"><?php echo $imc['categoria_imc']; ?></span>
                                        <div class="flex items-center">
                                            <span class="text-xs font-medium mr-2"><?php echo $imc['cantidad']; ?></span>
                                            <span class="text-xs text-gray-500">(<?php echo $imc['porcentaje']; ?>%)</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-2 text-gray-500 text-sm">
                                        Sin datos de IMC
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Estado Nutricional -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Estado Nutricional</h4>
                            <div class="space-y-2">
                                <?php if (!empty($stats['estado_nutricional'])): ?>
                                    <?php foreach ($stats['estado_nutricional'] as $item): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-600"><?php echo $item['estado_nutricional']; ?></span>
                                        <div class="flex items-center">
                                            <span class="text-xs font-medium mr-2"><?php echo $item['cantidad']; ?></span>
                                            <span class="text-xs text-gray-500">(<?php echo $item['porcentaje']; ?>%)</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-2 text-gray-500 text-sm">
                                        Sin diagnóstico nutricional
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Gestantes y Lactantes -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Gestantes y Lactantes</h4>
                            <div class="space-y-2">
                                <?php if (!empty($stats['gestantes_lactantes'])): ?>
                                    <?php foreach ($stats['gestantes_lactantes'] as $item): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-600"><?php echo $item['tipo']; ?></span>
                                        <div class="flex items-center">
                                            <span class="text-xs font-medium"><?php echo $item['cantidad']; ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-2 text-gray-500 text-sm">
                                        Sin gestantes/lactantes
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Distribución por Edad -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Distribución por Edad</h4>
                            <div class="space-y-2">
                                <?php if (!empty($stats['por_edad'])): ?>
                                    <?php foreach ($stats['por_edad'] as $edad): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-600"><?php echo $edad['rango_edad']; ?></span>
                                        <div class="flex items-center">
                                            <span class="text-xs font-medium mr-2"><?php echo $edad['cantidad']; ?></span>
                                            <span class="text-xs text-gray-500">(<?php echo $edad['porcentaje']; ?>%)</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-2 text-gray-500 text-sm">
                                        Sin datos de edad
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                
                </div>
            </main>
        </div>
    </div>

    <script>
    // Inicializar gráficos cuando el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard cargado - iniciando gráficos');
        
        // Función para inicializar gráficos
        function initChart(canvasId, type, data, options) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;
            
            const ctx = canvas.getContext('2d');
            return new Chart(ctx, {
                type: type,
                data: data,
                options: options
            });
        }
        
        // 1. Gráfico de Género (Doughnut)
        const generoData = {
            labels: <?php echo !empty($charts_data['genero_labels']) ? json_encode($charts_data['genero_labels']) : '["Sin datos"]'; ?>,
            datasets: [{
                data: <?php echo !empty($charts_data['genero_values']) ? json_encode($charts_data['genero_values']) : '[1]'; ?>,
                backgroundColor: <?php echo !empty($charts_data['genero_colors']) ? json_encode($charts_data['genero_colors']) : '["#6B7280"]'; ?>,
                borderWidth: 1,
                borderColor: '#fff'
            }]
        };
        
        initChart('generoChart', 'doughnut', generoData, {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label = label.split(' (')[0] + ': ';
                            }
                            label += context.raw + ' beneficiarios';
                            return label;
                        }
                    }
                }
            }
        });
        
        // 2. Gráfico de Estado Nutricional (Pie)
        const nutricionalData = {
            labels: <?php echo !empty($charts_data['nutricional_labels']) ? json_encode($charts_data['nutricional_labels']) : '["Sin datos"]'; ?>,
            datasets: [{
                data: <?php echo !empty($charts_data['nutricional_values']) ? json_encode($charts_data['nutricional_values']) : '[1]'; ?>,
                backgroundColor: <?php echo !empty($charts_data['nutricional_colors']) ? json_encode($charts_data['nutricional_colors']) : '["#6B7280"]'; ?>,
                borderWidth: 1,
                borderColor: '#fff'
            }]
        };
        
        initChart('nutricionalChart', 'pie', nutricionalData, {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        });
        
        // 3. Gráfico de Edad (Bar)
        const edadData = {
            labels: <?php echo !empty($charts_data['edad_labels']) ? json_encode($charts_data['edad_labels']) : '["Sin datos"]'; ?>,
            datasets: [{
                label: 'Cantidad de Beneficiarios',
                data: <?php echo !empty($charts_data['edad_values']) ? json_encode($charts_data['edad_values']) : '[0]'; ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 4
            }]
        };
        
        initChart('edadChart', 'bar', edadData, {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        display: true
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        });
        
        // 4. Gráfico de Municipios (Bar Horizontal)
        const municipioData = {
            labels: <?php echo !empty($charts_data['municipio_labels']) ? json_encode($charts_data['municipio_labels']) : '["Sin datos"]'; ?>,
            datasets: [{
                label: 'Beneficiarios',
                data: <?php echo !empty($charts_data['municipio_values']) ? json_encode($charts_data['municipio_values']) : '[0]'; ?>,
                backgroundColor: <?php echo !empty($charts_data['municipio_colors']) ? json_encode($charts_data['municipio_colors']) : '["#6B7280"]'; ?>,
                borderWidth: 1,
                borderRadius: 4
            }]
        };
        
        initChart('municipioChart', 'bar', municipioData, {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: true
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        });
        
        // Agregar efecto hover a las filas de la tabla
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f9fafb';
            });
            row.addEventListener('mouseleave', function() {
                if (Array.from(this.parentNode.children).indexOf(this) % 2 === 0) {
                    this.style.backgroundColor = '#ffffff';
                } else {
                    this.style.backgroundColor = '#f9fafb';
                }
            });
        });
    });
    </script>
</body>
</html>