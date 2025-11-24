<?php
// Configurar rutas base
$base_url = '/innpasantias2024/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2024/public/';

$currentPage = 'Dashboard';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Datos para los componentes
$stats = [
    'total_patients' => ['value' => '1,248', 'change' => '12.5%', 'trend' => 'up'],
    'avg_weight_loss' => ['value' => '3.2 kg', 'change' => '8.2%', 'trend' => 'up'],
    'active_plans' => ['value' => '892', 'change' => '5.7%', 'trend' => 'up'],
    'daily_appointments' => ['value' => '24', 'change' => '2.1%', 'trend' => 'down']
];

$patients = [
    ['initials' => 'MJ', 'name' => 'María Jiménez', 'bmi' => '24.3', 'status' => 'En progreso', 'status_color' => 'green'],
    ['initials' => 'RP', 'name' => 'Roberto Pérez', 'bmi' => '28.7', 'status' => 'Evaluación', 'status_color' => 'yellow'],
    ['initials' => 'AG', 'name' => 'Ana García', 'bmi' => '22.1', 'status' => 'Completado', 'status_color' => 'green'],
    ['initials' => 'LM', 'name' => 'Luis Martínez', 'bmi' => '26.4', 'status' => 'Nuevo', 'status_color' => 'blue']
];

$activities = [
    [
        'icon' => 'weight',
        'color' => 'green',
        'title' => 'Nuevo registro de peso',
        'description' => 'María Jiménez perdió 1.2kg esta semana',
        'time' => 'Hace 2 horas'
    ],
    [
        'icon' => 'user-plus', 
        'color' => 'blue',
        'title' => 'Nuevo paciente',
        'description' => 'Luis Martínez se registró en el sistema',
        'time' => 'Hace 5 horas'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Instituto Nacional de Nutrición</title>
    <!-- Ruta CORRECTA para CSS -->
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
                <!-- Stats Cards -->
                <?php include  '../../components/dashboard/stats-cards.php'; ?>

<!-- En la sección de gráficos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- BMI Distribution Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Distribución de IMC</h3>
        <div class="h-80">
            <canvas id="bmiChart" width="400" height="400"></canvas>
        </div>
    </div>

    <!-- Nutrition Goals Chart -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Cumplimiento de Objetivos</h3>
        <div class="h-80">
            <canvas id="goalsChart" width="400" height="400"></canvas>
        </div>
    </div>
</div>


<script>
    // Esperar a que la página esté completamente cargada
    window.addEventListener('load', function() {
        console.log('Página completamente cargada - iniciando gráficos');
        
        // BMI Distribution Chart
        const bmiCanvas = document.getElementById('bmiChart');
        if (bmiCanvas) {
            console.log('Canvas IMC encontrado');
            const bmiCtx = bmiCanvas.getContext('2d');
            new Chart(bmiCtx, {
                type: 'bar',
                data: {
                    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',],
                    datasets: [{
                        label: 'Casos B por Mes',
                        data: [400, 320, 420, 280, 120, 63, 500, 500, 680, 700, 440, 490],
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        } else {
            console.error('NO se encontró el canvas IMC');
        }

        // Nutrition Goals Chart
        const goalsCanvas = document.getElementById('goalsChart');
        if (goalsCanvas) {
            console.log('Canvas objetivos encontrado');
            const goalsCtx = goalsCanvas.getContext('2d');
            new Chart(goalsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Alcanzadas', 'En progreso', 'No alcanzadas'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 205, 86, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        } else {
            console.error('NO se encontró el canvas objetivos');
        }
    });
    </script>
</body>
</html>