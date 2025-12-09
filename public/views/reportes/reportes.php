<?php
// Configurar rutas base
$base_url = '/innpasantias2024/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2024/public/';

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
    
    // 1. Total de beneficiarios
    $query = "SELECT COUNT(*) as total FROM beneficiario";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_beneficiarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Beneficiarios por género
    $query = "SELECT 
                CASE 
                    WHEN genero = 'MASCULINO' THEN 'Hombres'
                    WHEN genero = 'FEMENINO' THEN 'Mujeres'
                    ELSE 'Otros'
                END as genero,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario)), 1) as porcentaje
              FROM beneficiario 
              GROUP BY genero";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['por_genero'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Distribución por edad
    $query = "SELECT 
                CASE 
                    WHEN edad < 5 THEN '0-4 años'
                    WHEN edad BETWEEN 5 AND 11 THEN '5-11 años'
                    WHEN edad BETWEEN 12 AND 17 THEN '12-17 años'
                    WHEN edad BETWEEN 18 AND 59 THEN '18-59 años'
                    ELSE '60+ años'
                END as rango_edad,
                COUNT(*) as cantidad
              FROM beneficiario 
              WHERE edad IS NOT NULL
              GROUP BY rango_edad
              ORDER BY 
                CASE rango_edad
                    WHEN '0-4 años' THEN 1
                    WHEN '5-11 años' THEN 2
                    WHEN '12-17 años' THEN 3
                    WHEN '18-59 años' THEN 4
                    WHEN '60+ años' THEN 5
                END";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['por_edad'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Estado nutricional
    $query = "SELECT 
                CASE 
                    WHEN situacion_dx = '1' THEN 'Normal'
                    WHEN situacion_dx = '2' THEN 'Desnutrición'
                    ELSE 'Sin diagnóstico'
                END as estado_nutricional,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM beneficiario)), 1) as porcentaje
              FROM beneficiario 
              GROUP BY situacion_dx";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['estado_nutricional'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Gestantes y lactantes
    $query = "SELECT 
                'Gestantes' as tipo,
                COUNT(*) as cantidad
              FROM beneficiario 
              WHERE gestante = 'SI'
              UNION ALL
              SELECT 
                'Lactantes',
                COUNT(*)
              FROM beneficiario 
              WHERE lactando = 'SI'";
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
                COUNT(*) as cantidad
              FROM beneficiario 
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
    
    // Obtener municipios desde carga_temporal
    $query = "SELECT DISTINCT municipio 
              FROM carga_temporal 
              WHERE municipio IS NOT NULL 
              AND municipio != ''
              ORDER BY municipio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $municipios = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Si no hay municipios en carga_temporal, usar algunos por defecto
    if (empty($municipios)) {
        $municipios = ['ANDRES ELOY BLANCO', 'PIO TAMAYO', 'YACAMBU', 'IRIBARREN'];
    }
    
    // Estadísticas por municipio
    $stats['por_municipio'] = [];
    foreach ($municipios as $municipio) {
        // Contar beneficiarios por municipio (esto es un ejemplo, necesitarías relacionar las tablas)
        $query = "SELECT COUNT(*) as cantidad FROM carga_temporal WHERE municipio = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$municipio]);
        $cantidad = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
        
        if ($cantidad > 0) {
            $stats['por_municipio'][] = [
                'municipio' => $municipio,
                'cantidad' => $cantidad
            ];
        }
    }
    
    // Ordenar municipios por cantidad descendente
    usort($stats['por_municipio'], function($a, $b) {
        return $b['cantidad'] - $a['cantidad'];
    });
    
    // Top 5 municipios
    $stats['top_municipios'] = array_slice($stats['por_municipio'], 0, 5);
    
    // ==================== DATOS PARA GRÁFICOS ====================
    
    // Datos para gráfico de género
    $charts_data['genero_labels'] = [];
    $charts_data['genero_values'] = [];
    $charts_data['genero_colors'] = ['#3B82F6', '#EF4444', '#10B981'];
    
    foreach ($stats['por_genero'] as $item) {
        $charts_data['genero_labels'][] = $item['genero'] . ' (' . $item['porcentaje'] . '%)';
        $charts_data['genero_values'][] = $item['cantidad'];
    }
    
    // Datos para gráfico de edad
    $charts_data['edad_labels'] = [];
    $charts_data['edad_values'] = [];
    
    foreach ($stats['por_edad'] as $item) {
        $charts_data['edad_labels'][] = $item['rango_edad'];
        $charts_data['edad_values'][] = $item['cantidad'];
    }
    
    // Datos para gráfico de estado nutricional
    $charts_data['nutricional_labels'] = [];
    $charts_data['nutricional_values'] = [];
    $charts_data['nutricional_colors'] = ['#10B981', '#F59E0B', '#6B7280'];
    
    foreach ($stats['estado_nutricional'] as $item) {
        $charts_data['nutricional_labels'][] = $item['estado_nutricional'] . ' (' . $item['porcentaje'] . '%)';
        $charts_data['nutricional_values'][] = $item['cantidad'];
    }
    
    // Datos para gráfico de municipios
    $charts_data['municipio_labels'] = [];
    $charts_data['municipio_values'] = [];
    $charts_data['municipio_colors'] = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'];
    
    foreach ($stats['top_municipios'] as $item) {
        $charts_data['municipio_labels'][] = $item['municipio'];
        $charts_data['municipio_values'][] = $item['cantidad'];
    }
    
    // ==================== REPORTES ESPECIALES ====================
    
    // Reporte 1: Beneficiarios con mayor riesgo (desnutrición y bajo peso)
    $query = "SELECT 
                cedula_beneficiario,
                CONCAT(nombres, ' ', apellidos) as nombre_completo,
                edad,
                imc,
                situacion_dx,
                CASE 
                    WHEN situacion_dx = '2' THEN 'Desnutrición'
                    ELSE 'Otro'
                END as riesgo
              FROM beneficiario 
              WHERE (situacion_dx = '2' OR imc < 18.5)
              AND (situacion_dx IS NOT NULL OR imc IS NOT NULL)
              ORDER BY imc ASC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reportes['alto_riesgo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reporte 2: Gestantes por semanas de gestación
    $query = "SELECT 
                cedula_beneficiario,
                CONCAT(nombres, ' ', apellidos) as nombre_completo,
                semanas_gestacion,
                imc,
                CASE 
                    WHEN semanas_gestacion < 13 THEN 'Primer trimestre'
                    WHEN semanas_gestacion BETWEEN 13 AND 26 THEN 'Segundo trimestre'
                    WHEN semanas_gestacion > 26 THEN 'Tercer trimestre'
                    ELSE 'Sin especificar'
                END as trimestre
              FROM beneficiario 
              WHERE gestante = 'SI'
              AND semanas_gestacion > 0
              ORDER BY semanas_gestacion DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reportes['gestantes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reporte 3: Distribución por municipio con más detalle
    $reportes['municipios_detalle'] = [];
    foreach ($municipios as $municipio) {
        // Solo procesar si hay datos
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN genero = 'FEMENINO' THEN 1 ELSE 0 END) as mujeres,
                    SUM(CASE WHEN genero = 'MASCULINO' THEN 1 ELSE 0 END) as hombres,
                    SUM(CASE WHEN gestante = 'SI' THEN 1 ELSE 0 END) as gestantes,
                    SUM(CASE WHEN lactando = 'SI' THEN 1 ELSE 0 END) as lactantes
                  FROM carga_temporal ct
                  WHERE ct.municipio = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$municipio]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($datos['total'] > 0) {
            $reportes['municipios_detalle'][] = [
                'municipio' => $municipio,
                'total' => $datos['total'],
                'mujeres' => $datos['mujeres'],
                'hombres' => $datos['hombres'],
                'gestantes' => $datos['gestantes'],
                'lactantes' => $datos['lactantes']
            ];
        }
    }
    
    // Ordenar por total descendente
    usort($reportes['municipios_detalle'], function($a, $b) {
        return $b['total'] - $a['total'];
    });
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Estadísticos - Instituto Nacional de Nutrición</title>
    <link href="<?php echo $base_url; ?>assets/css/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include $include_path . 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include $include_path . 'components/header.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <p><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Encabezado -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-800">Reportes Estadísticos</h1>
                    <p class="text-gray-600 mt-2">Información estadística general del sistema INN</p>
                </div>
                
                <!-- Tarjetas de estadísticas principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total beneficiarios -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Beneficiarios</p>
                                <h3 class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_beneficiarios']); ?></h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mujeres -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Mujeres</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <?php 
                                    $mujeres = 0;
                                    foreach ($stats['por_genero'] as $item) {
                                        if ($item['genero'] == 'Mujeres') {
                                            $mujeres = $item['cantidad'];
                                            break;
                                        }
                                    }
                                    echo number_format($mujeres);
                                    ?>
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php 
                                    foreach ($stats['por_genero'] as $item) {
                                        if ($item['genero'] == 'Mujeres') {
                                            echo $item['porcentaje'] . '% del total';
                                            break;
                                        }
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="bg-pink-100 p-3 rounded-full">
                                <i class="fas fa-female text-pink-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hombres -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Hombres</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <?php 
                                    $hombres = 0;
                                    foreach ($stats['por_genero'] as $item) {
                                        if ($item['genero'] == 'Hombres') {
                                            $hombres = $item['cantidad'];
                                            break;
                                        }
                                    }
                                    echo number_format($hombres);
                                    ?>
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php 
                                    foreach ($stats['por_genero'] as $item) {
                                        if ($item['genero'] == 'Hombres') {
                                            echo $item['porcentaje'] . '% del total';
                                            break;
                                        }
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-male text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Municipios -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Municipios</p>
                                <h3 class="text-3xl font-bold mt-2"><?php echo count($stats['por_municipio']); ?></h3>
                                <p class="text-sm text-gray-500 mt-1">Con beneficiarios registrados</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de Gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de Género -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Distribución por Género</h3>
                        <div class="h-64">
                            <canvas id="generoChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Edad -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Distribución por Edad</h3>
                        <div class="h-64">
                            <canvas id="edadChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Estado Nutricional -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Estado Nutricional</h3>
                        <div class="h-64">
                            <canvas id="nutricionalChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Municipios -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Top 5 Municipios</h3>
                        <div class="h-64">
                            <canvas id="municipioChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Reporte por Municipios -->
                <div class="bg-white rounded-xl shadow mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Reporte Detallado por Municipio</h3>
                        <p class="text-gray-600 mt-1">Estadísticas desglosadas por municipio</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Municipio
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Beneficiarios
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Mujeres
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Hombres
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Gestantes
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Lactantes
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($reportes['municipios_detalle'])): ?>
                                    <?php foreach($reportes['municipios_detalle'] as $municipio): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($municipio['municipio']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 font-semibold">
                                                <?php echo number_format($municipio['total']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['mujeres']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['total'] > 0 ? round(($municipio['mujeres'] / $municipio['total']) * 100, 1) : 0; ?>%
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['hombres']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['total'] > 0 ? round(($municipio['hombres'] / $municipio['total']) * 100, 1) : 0; ?>%
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['gestantes']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['mujeres'] > 0 ? round(($municipio['gestantes'] / $municipio['mujeres']) * 100, 1) : 0; ?>% de mujeres
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($municipio['lactantes']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $municipio['mujeres'] > 0 ? round(($municipio['lactantes'] / $municipio['mujeres']) * 100, 1) : 0; ?>% de mujeres
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            No hay datos de municipios disponibles
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Otros Reportes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Beneficiarios en Alto Riesgo -->
                    <div class="bg-white rounded-xl shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold">Beneficiarios en Alto Riesgo</h3>
                            <p class="text-gray-600 mt-1">Desnutrición o bajo peso (IMC < 18.5)</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Beneficiario
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Edad
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IMC
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Riesgo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($reportes['alto_riesgo'])): ?>
                                        <?php foreach($reportes['alto_riesgo'] as $beneficiario): ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($beneficiario['nombre_completo']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo $beneficiario['cedula_beneficiario']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $beneficiario['edad']; ?> años
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $beneficiario['imc'] ? number_format($beneficiario['imc'], 1) : 'N/A'; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium 
                                                    <?php echo $beneficiario['riesgo'] == 'Desnutrición' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo $beneficiario['riesgo']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                No hay beneficiarios identificados en alto riesgo
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Gestantes Registradas -->
                    <div class="bg-white rounded-xl shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold">Gestantes Registradas</h3>
                            <p class="text-gray-600 mt-1">Por semanas de gestación</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Gestante
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Semanas
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trimestre
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IMC
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($reportes['gestantes'])): ?>
                                        <?php foreach($reportes['gestantes'] as $gestante): ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($gestante['nombre_completo']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $gestante['semanas_gestacion']; ?> semanas
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php 
                                                $color_class = 'bg-blue-100 text-blue-800';
                                                if ($gestante['trimestre'] == 'Segundo trimestre') {
                                                    $color_class = 'bg-green-100 text-green-800';
                                                } elseif ($gestante['trimestre'] == 'Tercer trimestre') {
                                                    $color_class = 'bg-yellow-100 text-yellow-800';
                                                }
                                                ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo $color_class; ?>">
                                                    <?php echo $gestante['trimestre']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $gestante['imc'] ? number_format($gestante['imc'], 1) : 'N/A'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                No hay gestantes registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen Estadístico -->
                <div class="mt-8 bg-blue-50 rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">
                        <i class="fas fa-chart-bar mr-2"></i>Resumen Estadístico
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($stats['distribucion_imc'] as $imc): ?>
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-500"><?php echo $imc['categoria_imc']; ?></div>
                            <div class="text-xl font-bold mt-1"><?php echo $imc['cantidad']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-blue-700 mb-2">Distribución por Estado Nutricional</h4>
                            <ul class="space-y-1">
                                <?php foreach ($stats['estado_nutricional'] as $item): ?>
                                <li class="flex justify-between">
                                    <span class="text-gray-700"><?php echo $item['estado_nutricional']; ?></span>
                                    <span class="font-medium"><?php echo $item['cantidad']; ?> (<?php echo $item['porcentaje']; ?>%)</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-blue-700 mb-2">Gestantes y Lactantes</h4>
                            <ul class="space-y-1">
                                <?php foreach ($stats['gestantes_lactantes'] as $item): ?>
                                <li class="flex justify-between">
                                    <span class="text-gray-700"><?php echo $item['tipo']; ?></span>
                                    <span class="font-medium"><?php echo $item['cantidad']; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Esperar a que la página esté completamente cargada
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Reportes cargados - iniciando gráficos');
        
        // Gráfico de Género (Doughnut)
        const generoCanvas = document.getElementById('generoChart');
        if (generoCanvas && <?php echo !empty($charts_data['genero_values']) ? 'true' : 'false'; ?>) {
            const generoCtx = generoCanvas.getContext('2d');
            new Chart(generoCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($charts_data['genero_labels']); ?>,
                    datasets: [{
                        data: <?php echo json_encode($charts_data['genero_values']); ?>,
                        backgroundColor: <?php echo json_encode($charts_data['genero_colors']); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Gráfico de Edad (Bar)
        const edadCanvas = document.getElementById('edadChart');
        if (edadCanvas && <?php echo !empty($charts_data['edad_values']) ? 'true' : 'false'; ?>) {
            const edadCtx = edadCanvas.getContext('2d');
            new Chart(edadCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($charts_data['edad_labels']); ?>,
                    datasets: [{
                        label: 'Cantidad de Beneficiarios',
                        data: <?php echo json_encode($charts_data['edad_values']); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Estado Nutricional (Pie)
        const nutricionalCanvas = document.getElementById('nutricionalChart');
        if (nutricionalCanvas && <?php echo !empty($charts_data['nutricional_values']) ? 'true' : 'false'; ?>) {
            const nutricionalCtx = nutricionalCanvas.getContext('2d');
            new Chart(nutricionalCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($charts_data['nutricional_labels']); ?>,
                    datasets: [{
                        data: <?php echo json_encode($charts_data['nutricional_values']); ?>,
                        backgroundColor: <?php echo json_encode($charts_data['nutricional_colors']); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Gráfico de Municipios (Bar horizontal)
        const municipioCanvas = document.getElementById('municipioChart');
        if (municipioCanvas && <?php echo !empty($charts_data['municipio_values']) ? 'true' : 'false'; ?>) {
            const municipioCtx = municipioCanvas.getContext('2d');
            new Chart(municipioCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($charts_data['municipio_labels']); ?>,
                    datasets: [{
                        label: 'Beneficiarios',
                        data: <?php echo json_encode($charts_data['municipio_values']); ?>,
                        backgroundColor: <?php echo json_encode($charts_data['municipio_colors']); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>