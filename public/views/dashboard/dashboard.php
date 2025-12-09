<?php
// Incluir configuración de base de datos
include_once '../../config/database.php';

// Configurar rutas base
$base_url = '/innpasantias2024/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2024/public/';

$currentPage = 'Dashboard';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Conexión a base de datos
$database = new Database();
$db = $database->getConnection();

// Si no hay conexión, mostrar error y salir
if (!$db) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-4">
            <strong>Error:</strong> No se pudo conectar a la base de datos. Verifica:
            <ul class="mt-2 list-disc list-inside">
                <li>Que la base de datos "inn" exista en MySQL</li>
                <li>Que el usuario "root" tenga acceso</li>
                <li>Que las credenciales en config/database.php sean correctas</li>
            </ul>
          </div>';
    exit();
}

// Obtener estadísticas REALES de la base de datos
try {
    // Total de beneficiarios
    $query = "SELECT COUNT(*) as total FROM beneficiario";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_beneficiarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Beneficiarios con diagnóstico de desnutrición (situacion_dx = '2')
    $query = "SELECT COUNT(*) as total FROM beneficiario WHERE situacion_dx = '2'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $desnutricion = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Beneficiarios gestantes
    $query = "SELECT COUNT(*) as total FROM beneficiario WHERE gestante = 'SI'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $gestantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Beneficiarios lactantes
    $query = "SELECT COUNT(*) as total FROM beneficiario WHERE lactando = 'SI'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lactantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Distribución por género para gráfico
    $query = "SELECT 
                CASE 
                    WHEN genero = 'MASCULINO' THEN 'Hombres'
                    WHEN genero = 'FEMENINO' THEN 'Mujeres'
                    ELSE 'Otros'
                END as genero,
                COUNT(*) as cantidad 
              FROM beneficiario 
              GROUP BY genero";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $genero_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Distribución por rangos de edad para gráfico
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
    $edad_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Beneficiarios más recientes para la tabla
    $query = "SELECT 
                cedula_beneficiario,
                CONCAT(nombres, ' ', apellidos) as nombre_completo,
                imc,
                situacion_dx,
                CASE 
                    WHEN situacion_dx = '1' THEN 'Normal'
                    WHEN situacion_dx = '2' THEN 'Desnutrición'
                    ELSE 'Sin diagnóstico'
                END as estado_nutricional,
                CASE 
                    WHEN situacion_dx = '1' THEN 'bg-green-100 text-green-800'
                    WHEN situacion_dx = '2' THEN 'bg-yellow-100 text-yellow-800'
                    ELSE 'bg-gray-100 text-gray-800'
                END as estado_clase
              FROM beneficiario 
              ORDER BY cedula_beneficiario DESC 
              LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $beneficiarios_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para gráfico de género
    $genero_labels = [];
    $genero_values = [];
    $genero_colors = ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(75, 192, 192, 0.7)'];
    
    foreach ($genero_data as $genero) {
        $genero_labels[] = $genero['genero'];
        $genero_values[] = $genero['cantidad'];
    }
    
    // Preparar datos para gráfico de edad
    $edad_labels = [];
    $edad_values = [];
    
    foreach ($edad_data as $edad) {
        $edad_labels[] = $edad['rango_edad'];
        $edad_values[] = $edad['cantidad'];
    }
    
    // Estadísticas para las tarjetas
    $stats = [
        'total_patients' => [
            'value' => number_format($total_beneficiarios),
            'change' => '+12.5%', 
            'trend' => 'up',
            'label' => 'Total Beneficiarios',
            'icon' => 'users'
        ],
        'gestantes' => [
            'value' => $gestantes,
            'change' => 'Gestantes', 
            'trend' => 'up',
            'label' => 'Gestantes',
            'icon' => 'baby'
        ],
        'desnutricion' => [
            'value' => $desnutricion,
            'change' => 'Casos', 
            'trend' => 'warning',
            'label' => 'Con Desnutrición',
            'icon' => 'exclamation-triangle'
        ],
        'lactantes' => [
            'value' => $lactantes,
            'change' => 'Lactantes', 
            'trend' => 'up',
            'label' => 'Lactantes',
            'icon' => 'child'
        ]
    ];
    
} catch (PDOException $e) {
    // En caso de error en consultas
    $error = "Error en consulta: " . $e->getMessage();
    $stats = [
        'total_patients' => ['value' => 'Error', 'change' => 'N/A', 'trend' => 'down', 'label' => 'Total', 'icon' => 'exclamation-circle'],
        'gestantes' => ['value' => 'Error', 'change' => 'N/A', 'trend' => 'down', 'label' => 'Gestantes', 'icon' => 'exclamation-circle'],
        'desnutricion' => ['value' => 'Error', 'change' => 'N/A', 'trend' => 'down', 'label' => 'Desnutrición', 'icon' => 'exclamation-circle'],
        'lactantes' => ['value' => 'Error', 'change' => 'N/A', 'trend' => 'down', 'label' => 'Lactantes', 'icon' => 'exclamation-circle']
    ];
    $beneficiarios_recientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Instituto Nacional de Nutrición</title>
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
                <?php if (isset($error)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <p><?php echo $error; ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <?php foreach ($stats as $key => $stat): ?>
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm"><?php echo $stat['label']; ?></p>
                                <h3 class="text-3xl font-bold mt-2"><?php echo $stat['value']; ?></h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-<?php echo $stat['icon']; ?> text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center">
                            <?php if ($stat['trend'] == 'up'): ?>
                            <span class="text-green-600 text-sm font-medium">
                                <i class="fas fa-arrow-up mr-1"></i><?php echo $stat['change']; ?>
                            </span>
                            <?php elseif ($stat['trend'] == 'warning'): ?>
                            <span class="text-yellow-600 text-sm font-medium">
                                <i class="fas fa-exclamation-triangle mr-1"></i><?php echo $stat['change']; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-red-600 text-sm font-medium">
                                <i class="fas fa-arrow-down mr-1"></i><?php echo $stat['change']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sección de gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Distribución por Edad -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Distribución por Edad</h3>
                        <div class="h-80">
                            <canvas id="edadChart"></canvas>
                        </div>
                    </div>

                    <!-- Distribución por Género -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Distribución por Género</h3>
                        <div class="h-80">
                            <canvas id="generoChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Beneficiarios Recientes -->
                <div class="bg-white rounded-xl shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Beneficiarios Recientes</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IMC</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Nutricional</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($beneficiarios_recientes) > 0): ?>
                                    <?php foreach ($beneficiarios_recientes as $beneficiario): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-medium">
                                                        <?php echo substr($beneficiario['nombre_completo'], 0, 2); ?>
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($beneficiario['nombre_completo']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($beneficiario['cedula_beneficiario']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            if ($beneficiario['imc'] && $beneficiario['imc'] > 0) {
                                                echo number_format($beneficiario['imc'], 1);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $beneficiario['estado_clase']; ?>">
                                                <?php echo $beneficiario['estado_nutricional']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            No hay beneficiarios registrados o error en la consulta
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Esperar a que la página esté completamente cargada
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard cargado - iniciando gráficos');
        
        // Gráfico de Distribución por Edad
        const edadCanvas = document.getElementById('edadChart');
        if (edadCanvas) {
            const edadCtx = edadCanvas.getContext('2d');
            new Chart(edadCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($edad_labels); ?>,
                    datasets: [{
                        label: 'Cantidad de Beneficiarios',
                        data: <?php echo json_encode($edad_values); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgb(75, 192, 192)',
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
        
        // Gráfico de Distribución por Género
        const generoCanvas = document.getElementById('generoChart');
        if (generoCanvas) {
            const generoCtx = generoCanvas.getContext('2d');
            new Chart(generoCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($genero_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($genero_values); ?>,
                        backgroundColor: <?php echo json_encode($genero_colors); ?>,
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
    });
    </script>
</body>
</html>