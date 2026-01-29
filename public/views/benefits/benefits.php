<?php
// Configurar rutas base
$base_url = '/innpasantias2026/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2026/public/';

// Incluir configuración de base de datos
include_once '../../config/database.php';

$currentPage = 'Beneficios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Conexión a base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener datos de beneficios de la base de datos
$benefits = [];
$distribuidores_data = [];

try {
    // 1. Obtener tipos de beneficios desde la tabla tipo_beneficio
    $query = "SELECT id_beneficio, nombre_beneficio FROM tipo_beneficio ORDER BY id_beneficio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tipos_beneficios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Obtener empresas distribuidoras únicas desde procura_entrega
    $query = "SELECT DISTINCT empresa_distribuidora 
              FROM procura_entrega 
              WHERE empresa_distribuidora IS NOT NULL 
              AND empresa_distribuidora != ''
              ORDER BY empresa_distribuidora";
    
    $stmt2 = $db->prepare($query);
    $stmt2->execute();
    $distribuidores_data = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

// Funciones auxiliares para obtener descripción e icono
function getDescripcionPorTipo($nombre) {
    $nombre_lower = strtolower($nombre);
    
    if (strpos($nombre_lower, 'fruta') !== false || 
        strpos($nombre_lower, 'verdura') !== false ||
        strpos($nombre_lower, 'fruver') !== false ||
        strpos($nombre_lower, 'hortaliza') !== false) {
        return 'Entrega de frutas y verduras frescas para una alimentación balanceada';
    } elseif (strpos($nombre_lower, 'prote') !== false || 
              strpos($nombre_lower, 'carne') !== false ||
              strpos($nombre_lower, 'pollo') !== false ||
              strpos($nombre_lower, 'pescado') !== false ||
              strpos($nombre_lower, 'legumbre') !== false) {
        return 'Entrega de alimentos proteicos para el desarrollo muscular';
    } elseif (strpos($nombre_lower, 'suple') !== false || 
              strpos($nombre_lower, 'vitamina') !== false ||
              strpos($nombre_lower, 'mineral') !== false ||
              strpos($nombre_lower, 'nutricional') !== false) {
        return 'Suplementos nutricionales y vitamínicos esenciales';
    } elseif (strpos($nombre_lower, 'lácteo') !== false || 
              strpos($nombre_lower, 'leche') !== false ||
              strpos($nombre_lower, 'queso') !== false ||
              strpos($nombre_lower, 'yogurt') !== false) {
        return 'Productos lácteos para el desarrollo óseo';
    } elseif (strpos($nombre_lower, 'cereal') !== false || 
              strpos($nombre_lower, 'arroz') !== false ||
              strpos($nombre_lower, 'pasta') !== false ||
              strpos($nombre_lower, 'harina') !== false ||
              strpos($nombre_lower, 'pan') !== false) {
        return 'Carbohidratos y cereales para energía diaria';
    } else {
        return 'Beneficio nutricional disponible para los beneficiarios';
    }
}

function getIconoPorTipo($nombre) {
    $nombre_lower = strtolower($nombre);
    
    if (strpos($nombre_lower, 'fruta') !== false || 
        strpos($nombre_lower, 'verdura') !== false ||
        strpos($nombre_lower, 'fruver') !== false) {
        return 'apple-alt';
    } elseif (strpos($nombre_lower, 'prote') !== false || 
              strpos($nombre_lower, 'carne') !== false ||
              strpos($nombre_lower, 'pollo') !== false ||
              strpos($nombre_lower, 'pescado') !== false) {
        return 'drumstick-bite';
    } elseif (strpos($nombre_lower, 'suple') !== false || 
              strpos($nombre_lower, 'vitamina') !== false ||
              strpos($nombre_lower, 'mineral') !== false) {
        return 'pills';
    } elseif (strpos($nombre_lower, 'lácteo') !== false || 
              strpos($nombre_lower, 'leche') !== false ||
              strpos($nombre_lower, 'queso') !== false) {
        return 'cheese';
    } elseif (strpos($nombre_lower, 'cereal') !== false || 
              strpos($nombre_lower, 'arroz') !== false ||
              strpos($nombre_lower, 'pasta') !== false) {
        return 'bread-slice';
    } else {
        return 'gift';
    }
}

// Procesar los tipos de beneficios
$benefits = [];

// Si hay datos de tipo_beneficio, procesarlos
if (!empty($tipos_beneficios)) {
    foreach ($tipos_beneficios as $tipo) {
        $id = $tipo['id_beneficio'];
        $nombre = $tipo['nombre_beneficio'];
        
        // Crear descripción basada en el tipo de beneficio
        $descripcion = getDescripcionPorTipo($nombre);
        
        // Asignar icono según el tipo de beneficio
        $icon = getIconoPorTipo($nombre);
        
        $benefits[] = [
            'id' => $id,
            'beneficio' => $nombre,
            'descripcion' => $descripcion,
            'distribuidor' => 'INN - Instituto Nacional de Nutrición',
            'estado' => 'activo',
            'tipo' => 'beneficio',
            'icon' => $icon
        ];
    }
} else {
    // Si no hay datos en tipo_beneficio, usar datos por defecto
    $benefits_default = [
        [
            'id' => 1,
            'beneficio' => 'Frutas y Verduras',
            'descripcion' => 'Entrega de frutas y verduras frescas para una alimentación balanceada',
            'distribuidor' => 'INN - Instituto Nacional de Nutrición',
            'estado' => 'activo',
            'tipo' => 'beneficio',
            'icon' => 'apple-alt'
        ],
        [
            'id' => 2,
            'beneficio' => 'Proteínas',
            'descripcion' => 'Entrega de alimentos proteicos como carnes, pollo, pescado y legumbres',
            'distribuidor' => 'INN - Instituto Nacional de Nutrición',
            'estado' => 'activo',
            'tipo' => 'beneficio',
            'icon' => 'drumstick-bite'
        ],
        [
            'id' => 3,
            'beneficio' => 'Suplementos Nutricionales',
            'descripcion' => 'Entrega de suplementos vitamínicos y minerales esenciales',
            'distribuidor' => 'INN - Instituto Nacional de Nutrición',
            'estado' => 'activo',
            'tipo' => 'beneficio',
            'icon' => 'pills'
        ]
    ];
    
    // Agregar beneficios por defecto al array
    foreach ($benefits_default as $beneficio) {
        $benefits[] = $beneficio;
    }
}

// Agregar distribuidores como servicios
$id_counter = count($benefits) + 1;
foreach ($distribuidores_data as $distribuidor) {
    if (!empty($distribuidor['empresa_distribuidora'])) {
        $benefits[] = [
            'id' => 'D' . $id_counter++,
            'beneficio' => 'Servicio de Distribución',
            'descripcion' => 'Distribución autorizada de beneficios nutricionales',
            'distribuidor' => $distribuidor['empresa_distribuidora'],
            'estado' => 'activo',
            'tipo' => 'distribuidor',
            'icon' => 'truck'
        ];
    }
}

// Calcular estadísticas
$tipos_count = array_reduce($benefits, function($carry, $item) {
    return $carry + ($item['tipo'] === 'beneficio' ? 1 : 0);
}, 0);

$distribuidores_count = array_reduce($benefits, function($carry, $item) {
    return $carry + ($item['tipo'] === 'distribuidor' ? 1 : 0);
}, 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficios - Instituto Nacional de Nutrición</title>
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
        .progress-bar {
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
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
        .badge-success {
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
        }
        .badge-warning {
            background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
        }
        .badge-info {
            background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);
        }
        .header-gradient-green {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
        }
        .header-gradient-blue {
            background: linear-gradient(135deg, #1D4ED8 0%, #3B82F6 100%);
        }
        .icon-circle-lg {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .shadow-card {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .border-gradient-green {
            border-left: 4px solid #10B981;
        }
        .border-gradient-blue {
            border-left: 4px solid #3B82F6;
        }
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .table-row-hover:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50">
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
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 fade-in">
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
                <div class="mb-8 fade-in">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Gestión de Beneficios</h1>
                            <p class="text-gray-600 mt-2">Catálogo de beneficios nutricionales y distribuidores</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 pulse-animation">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Actualizado: <?php echo date('d/m/Y H:i'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjetas de estadísticas principales -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                    <!-- Total Beneficios -->
                    <div class="card-stat bg-white rounded-xl shadow-card p-4 md:p-6 border-gradient-green hover-lift">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Tipos de Beneficios</p>
                                <h3 class="stat-number mt-2 text-green-600">
                                    <?php echo number_format($tipos_count); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Nutricionales registrados</p>
                            </div>
                            <div class="icon-circle-lg bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="progress-bar bg-gray-200">
                                <div class="bg-green-500 h-full" style="width: <?php echo min(100, ($tipos_count / max(1, count($benefits))) * 100); ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs mt-2 text-gray-600">
                                <span><?php echo number_format($tipos_count); ?> beneficios</span>
                                <span><?php echo count($benefits) > 0 ? round(($tipos_count / count($benefits)) * 100, 1) : 0; ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Distribuidores -->
                    <div class="card-stat bg-white rounded-xl shadow-card p-4 md:p-6 border-gradient-blue hover-lift">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Empresas Distribuidoras</p>
                                <h3 class="stat-number mt-2 text-blue-600">
                                    <?php echo number_format($distribuidores_count); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Socios comerciales</p>
                            </div>
                            <div class="icon-circle-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="progress-bar bg-gray-200">
                                <div class="bg-blue-500 h-full" style="width: <?php echo min(100, ($distribuidores_count / max(1, count($benefits))) * 100); ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs mt-2 text-gray-600">
                                <span><?php echo number_format($distribuidores_count); ?> empresas</span>
                                <span><?php echo count($benefits) > 0 ? round(($distribuidores_count / count($benefits)) * 100, 1) : 0; ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Registros -->
                    <div class="card-stat bg-white rounded-xl shadow-card p-4 md:p-6 border-l-4 border-purple-500 hover-lift">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Registros</p>
                                <h3 class="stat-number mt-2 text-purple-600">
                                    <?php echo number_format(count($benefits)); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">En el sistema</p>
                            </div>
                            <div class="icon-circle-lg bg-gradient-to-br from-purple-500 to-purple-600 text-white">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="progress-bar bg-gray-200">
                                <div class="bg-purple-500 h-full" style="width: 100%"></div>
                            </div>
                            <div class="flex justify-between text-xs mt-2 text-gray-600">
                                <span>100% activos</span>
                                <span><?php echo number_format(count($benefits)); ?> registros</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado del Sistema -->
                    <div class="card-stat bg-white rounded-xl shadow-card p-4 md:p-6 border-l-4 border-amber-500 hover-lift">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Estado del Sistema</p>
                                <h3 class="text-xl font-bold mt-2 text-green-600">Operativo</h3>
                                <p class="text-xs text-gray-500 mt-1">Todos los servicios activos</p>
                            </div>
                            <div class="icon-circle-lg bg-gradient-to-br from-green-400 to-emerald-500 text-white">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full pulse-animation"></div>
                                <span class="text-xs text-green-600">Conectado a la base de datos</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de Beneficios Nutricionales -->
                <div class="bg-white rounded-xl shadow-card mb-8 overflow-hidden fade-in hover-lift">
                    <div class="px-4 md:px-6 py-4 header-gradient-green">
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Beneficios Nutricionales</h3>
                                <p class="text-green-100 text-sm mt-1">Catálogo completo de beneficios disponibles para beneficiarios</p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-green-700">
                                    <i class="fas fa-apple-alt mr-2"></i>
                                    <?php echo $tipos_count; ?> tipos disponibles
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-gift text-green-500 mr-2"></i>Beneficio
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-info-circle text-green-500 mr-2"></i>Descripción
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-building text-green-500 mr-2"></i>Proveedor
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                // Filtrar solo los beneficios (no distribuidores)
                                $beneficios_filtrados = array_filter($benefits, function($item) {
                                    return $item['tipo'] === 'beneficio';
                                });
                                
                                if (count($beneficios_filtrados) > 0): 
                                    $counter = 0;
                                    foreach($beneficios_filtrados as $benefit): 
                                        $counter++;
                                        $row_class = $counter % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                                ?>
                                <tr class="<?php echo $row_class; ?> table-row-hover transition duration-150">
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-<?php echo $benefit['icon']; ?> text-green-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($benefit['beneficio']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: B-<?php echo str_pad($benefit['id'], 3, '0', STR_PAD_LEFT); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($benefit['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-blue-50 rounded-full flex items-center justify-center">
                                                <i class="fas fa-hospital text-blue-500 text-xs"></i>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($benefit['distribuidor']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <?php echo $benefit['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-gift text-3xl mb-2 text-gray-300"></i>
                                        <p>No hay beneficios registrados</p>
                                        <p class="text-sm mt-1">Agrega nuevos beneficios al sistema</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tabla de Distribuidores -->
                <div class="bg-white rounded-xl shadow-card overflow-hidden fade-in hover-lift">
                    <div class="px-4 md:px-6 py-4 header-gradient-blue">
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Empresas Distribuidoras</h3>
                                <p class="text-blue-100 text-sm mt-1">Socios comerciales autorizados para distribución de beneficios</p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-blue-700">
                                    <i class="fas fa-truck mr-2"></i>
                                    <?php echo $distribuidores_count; ?> empresas activas
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-truck text-blue-500 mr-2"></i>Empresa Distribuidora
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-cogs text-blue-500 mr-2"></i>Servicio
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>Descripción
                                    </th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-shield-alt text-blue-500 mr-2"></i>Autorización
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                // Filtrar solo los distribuidores
                                $distribuidores_filtrados = array_filter($benefits, function($item) {
                                    return $item['tipo'] === 'distribuidor';
                                });
                                
                                if (count($distribuidores_filtrados) > 0): 
                                    $counter = 0;
                                    foreach($distribuidores_filtrados as $distribuidor): 
                                        $counter++;
                                        $row_class = $counter % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                                ?>
                                <tr class="<?php echo $row_class; ?> table-row-hover transition duration-150">
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-truck text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($distribuidor['distribuidor']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: <?php echo $distribuidor['id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($distribuidor['beneficio']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($distribuidor['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Autorizado
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-truck text-3xl mb-2 text-gray-300"></i>
                                        <p>No hay distribuidores registrados</p>
                                        <p class="text-sm mt-1">Registra nuevas empresas distribuidoras</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                    
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Inicializar efectos interactivos
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Página de beneficios cargada');
        
        // Agregar efecto hover a las filas de tabla
        document.querySelectorAll('tbody tr.table-row-hover').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f3f4f6';
                this.style.cursor = 'pointer';
            });
            row.addEventListener('mouseleave', function() {
                const rowIndex = Array.from(this.parentNode.children).indexOf(this);
                if (rowIndex % 2 === 0) {
                    this.style.backgroundColor = '#ffffff';
                } else {
                    this.style.backgroundColor = '#f9fafb';
                }
            });
        });
        
        // Agregar tooltips a las celdas con datos importantes
        document.querySelectorAll('.data-cell').forEach(cell => {
            cell.addEventListener('mouseenter', function(e) {
                const tooltip = this.querySelector('.data-tooltip');
                if (tooltip) {
                    tooltip.style.display = 'block';
                }
            });
            
            cell.addEventListener('mouseleave', function(e) {
                const tooltip = this.querySelector('.data-tooltip');
                if (tooltip) {
                    tooltip.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>