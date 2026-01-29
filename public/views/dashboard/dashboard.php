<?php
// Incluir configuración de base de datos
include_once '../../config/database.php';

// Configurar rutas base
$base_url = '/innpasantias2026/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2026/public/';

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
    
    // Distribución por género para gráfico - VERSIÓN CORREGIDA CON COLORES ESPECÍFICOS
    $query = "SELECT 
                genero,
                COUNT(*) as cantidad 
              FROM beneficiario 
              WHERE genero IS NOT NULL AND genero != ''
              GROUP BY genero";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $genero_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para gráfico de género
    $genero_labels = [];
    $genero_values = [];
    $genero_colors = [];
    
    // Definir colores específicos para cada género
    $color_masculino = 'rgba(54, 162, 235, 0.7)';  // Azul para hombres
    $color_femenino = 'rgba(255, 99, 132, 0.7)';   // Rosa para mujeres
    $color_otro = 'rgba(75, 192, 192, 0.7)';       // Verde para otros
    
    foreach ($genero_data as $genero) {
        $genero_db = strtoupper(trim($genero['genero']));
        
        // Convertir a nombres completos y asignar colores correctos
        if (in_array($genero_db, ['M', 'MASCULINO', 'HOMBRE', 'VARÓN'])) {
            $genero_labels[] = 'Hombres';
            $genero_colors[] = $color_masculino;
        } elseif (in_array($genero_db, ['F', 'FEMENINO', 'MUJER', 'SEÑORA', 'DAMA'])) {
            $genero_labels[] = 'Mujeres';
            $genero_colors[] = $color_femenino;
        } else {
            $genero_labels[] = 'Otros';
            $genero_colors[] = $color_otro;
        }
        
        $genero_values[] = $genero['cantidad'];
    }
    
    // Si no hay datos, mostrar valores por defecto
    if (empty($genero_labels)) {
        $genero_labels = ['Hombres', 'Mujeres'];
        $genero_values = [0, 0];
        $genero_colors = [$color_masculino, $color_femenino];
    }
    
    // Distribución por edad
    $query = "SELECT 
                CASE 
                    WHEN edad >= 0 AND edad < 6 THEN '0-72 meses'
                    WHEN edad >= 5 AND edad <= 10 THEN '5-10 años'
                    WHEN edad >= 11 AND edad <= 17 THEN '11-17 años'
                    WHEN edad >= 18 AND edad <= 55 THEN '18-55 años'
                    WHEN edad >= 56 THEN '56+ años'
                    ELSE 'Edad no registrada'
                END as rango_edad,
                COUNT(*) as cantidad 
              FROM beneficiario 
              WHERE edad IS NOT NULL
              GROUP BY rango_edad
              ORDER BY 
                CASE rango_edad
                    WHEN '0-72 meses' THEN 1
                    WHEN '5-10 años' THEN 2
                    WHEN '11-17 años' THEN 3
                    WHEN '18-55 años' THEN 4
                    WHEN '56+ años' THEN 5
                    WHEN 'Edad no registrada' THEN 6
                END";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $edad_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para gráfico de edad
    $edad_labels = [];
    $edad_values = [];
    
    foreach ($edad_data as $edad) {
        $edad_labels[] = $edad['rango_edad'];
        $edad_values[] = $edad['cantidad'];
    }
    
    // Beneficiarios más recientes para la tabla
    $query = "SELECT 
                cedula_beneficiario,
                CONCAT(nombres, ' ', apellidos) as nombre_completo,
                imc,
                situacion_dx,
                CASE 
                    WHEN situacion_dx = '1' THEN 'Normal'
                    WHEN situacion_dx = '2' THEN 'Defisis'
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
    
    // Estadísticas para las tarjetas
    $stats = [
        'total_patients' => [
            'value' => number_format($total_beneficiarios),
            'change' => '+12.5%', 
            'trend' => 'up',
            'label' => 'Total Beneficiarios',
            'icon' => 'users',
            'color' => 'green'
        ],
        'gestantes' => [
            'value' => $gestantes,
            'change' => 'Gestantes', 
            'trend' => 'up',
            'label' => 'Gestantes',
            'icon' => 'baby',
            'color' => 'pink'
        ],
        'desnutricion' => [
            'value' => $desnutricion,
            'change' => 'Casos', 
            'trend' => 'warning',
            'label' => 'Con Defisis',
            'icon' => 'exclamation-triangle',
            'color' => 'yellow'
        ],
        'lactantes' => [
            'value' => $lactantes,
            'change' => 'Lactantes', 
            'trend' => 'up',
            'label' => 'Lactantes',
            'icon' => 'child',
            'color' => 'blue'
        ]
    ];
    
} catch (PDOException $e) {
    // En caso de error en consultas
    $error = "Error en consulta: " . $e->getMessage();
    $beneficiarios_recientes = [];
    $genero_labels = ['Hombres', 'Mujeres'];
    $genero_values = [0, 0];
    $genero_colors = ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)'];
    $edad_labels = [];
    $edad_values = [];
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
    <style>
        /* Estilos mejorados para gráficos más grandes */
        body {
            background-color: #f9fafb;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card-green { border-left: 4px solid #10b981; }
        .stat-card-pink { border-left: 4px solid #ec4899; }
        .stat-card-yellow { border-left: 4px solid #f59e0b; }
        .stat-card-blue { border-left: 4px solid #3b82f6; }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .icon-green { background-color: #d1fae5; color: #10b981; }
        .icon-pink { background-color: #fce7f3; color: #ec4899; }
        .icon-yellow { background-color: #fef3c7; color: #f59e0b; }
        .icon-blue { background-color: #dbeafe; color: #3b82f6; }
        
        /* Contenedores de gráficos más grandes */
        .chart-section {
            margin-top: 32px;
            margin-bottom: 32px;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .chart-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            height: 500px;
            display: flex;
            flex-direction: column;
        }
        
        .chart-wrapper {
            flex: 1;
            position: relative;
            min-height: 400px;
        }
        
        .chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
            max-height: 400px;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
        }
        
        .stats-grid {
            grid-column: span 12;
        }
        
        .chart-grid {
            grid-column: span 6;
        }
        
        @media (max-width: 1024px) {
            .chart-grid {
                grid-column: span 12;
            }
            
            .chart-container {
                height: 450px;
            }
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 400px;
            }
        }
        
        /* Welcome banner */
        .welcome-banner {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            border-left: 4px solid #10b981;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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
            <main class="flex-1 overflow-y-auto p-6">
                <div class="dashboard-container">
                    <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error en la consulta</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Welcome Banner -->
                    <div class="welcome-banner">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                                <p class="text-gray-600 mt-1">Resumen general del sistema de nutrición</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Bienvenido,</p>
                                <p class="font-medium text-gray-900"><?php echo $currentUser['name']; ?></p>
                                <p class="text-sm text-gray-600"><?php echo $currentUser['role']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-grid mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($stats as $key => $stat): 
                                $card_class = 'stat-card stat-card-' . $stat['color'];
                                $icon_class = 'stat-icon icon-' . $stat['color'];
                            ?>
                            <div class="<?php echo $card_class; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-600 mb-2"><?php echo $stat['label']; ?></p>
                                        <h3 class="text-3xl font-bold text-gray-900 mb-3"><?php echo $stat['value']; ?></h3>
                                        
                                        <div class="flex items-center">
                                            <?php if ($stat['trend'] == 'up'): ?>
                                            <span class="trend-badge trend-up">
                                                <i class="fas fa-arrow-up mr-1 text-xs"></i><?php echo $stat['change']; ?>
                                            </span>
                                            <?php elseif ($stat['trend'] == 'warning'): ?>
                                            <span class="trend-badge trend-warning">
                                                <i class="fas fa-exclamation-triangle mr-1 text-xs"></i><?php echo $stat['change']; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="trend-badge trend-down">
                                                <i class="fas fa-arrow-down mr-1 text-xs"></i><?php echo $stat['change']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="<?php echo $icon_class; ?> ml-3">
                                        <i class="fas fa-<?php echo $stat['icon']; ?>"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="main-grid">
                        <!-- Distribución por Edad -->
                        <div class="chart-grid">
                            <div class="chart-container">
                                <div>
                                    <h3 class="chart-title">Distribución por Edad</h3>
                                    <p class="chart-subtitle">Rangos de edad de los beneficiarios</p>
                                </div>
                                
                                <div class="chart-wrapper">
                                    <canvas id="edadChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Distribución por Género -->
                        <div class="chart-grid">
                            <div class="chart-container">
                                <div>
                                    <h3 class="chart-title">Distribución por Género</h3>
                                    <p class="chart-subtitle">Composición de género de los beneficiarios</p>
                                </div>
                                
                                <div class="chart-wrapper">
                                    <canvas id="generoChart"></canvas>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Esperar a que la página esté completamente cargada
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico de Distribución por Edad
        const edadCanvas = document.getElementById('edadChart');
        if (edadCanvas) {
            const edadCtx = edadCanvas.getContext('2d');
            new Chart(edadCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($edad_labels); ?>,
                    datasets: [{
                        label: 'Beneficiarios',
                        data: <?php echo json_encode($edad_values); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 6,
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(229, 231, 235, 0.5)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                },
                                stepSize: 1,
                                padding: 8
                            },
                            title: {
                                display: true,
                                text: 'Cantidad',
                                color: '#6b7280',
                                font: {
                                    size: 12,
                                    weight: 'normal'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                },
                                padding: 8
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Distribución por Género - CON COLORES CORRECTOS
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
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#4b5563',
                                font: {
                                    size: 12
                                },
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    label += value + ' (' + percentage + '%)';
                                    return label;
                                }
                            }
                        }
                    },
                    layout: {
                        padding: 10
                    }
                }
            });
        }
    });
    </script>
</body>
</html>