<?php
// Configurar rutas base
$base_url = '/innpasantias2024/public/';
$include_path = $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2024/public/';

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
$tipos_beneficios = [];

try {
    // Primero verificar qué columnas tiene la tabla tipo_beneficio
    $query = "SHOW COLUMNS FROM tipo_beneficio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<!-- Debug: Columnas encontradas en tipo_beneficio: " . implode(', ', $columnas) . " -->";
    
    // Construir la consulta dinámicamente basada en las columnas disponibles
    $columnas_select = [];
    if (in_array('id_tipo_beneficio', $columnas)) {
        $columnas_select[] = 'id_tipo_beneficio';
    } elseif (in_array('id', $columnas)) {
        $columnas_select[] = 'id';
    }
    
    if (in_array('nombre_beneficio', $columnas)) {
        $columnas_select[] = 'nombre_beneficio';
    } elseif (in_array('nombre', $columnas)) {
        $columnas_select[] = 'nombre';
    } elseif (in_array('tipo_beneficio', $columnas)) {
        $columnas_select[] = 'tipo_beneficio';
    }
    
    if (in_array('descripcion', $columnas)) {
        $columnas_select[] = 'descripcion';
    }
    
    if (in_array('estado', $columnas)) {
        $columnas_select[] = 'estado';
    } elseif (in_array('activo', $columnas)) {
        $columnas_select[] = 'activo';
    }
    
    // Si hay columnas para seleccionar, hacer la consulta
    if (!empty($columnas_select)) {
        $query = "SELECT " . implode(', ', $columnas_select) . " FROM tipo_beneficio ORDER BY " . $columnas_select[0];
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tipos_beneficios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Consultar empresas distribuidoras únicas de carga_temporal
    $query = "SELECT DISTINCT empresa_distribuidora 
              FROM carga_temporal 
              WHERE empresa_distribuidora IS NOT NULL 
              AND empresa_distribuidora != ''
              ORDER BY empresa_distribuidora";
    
    $stmt2 = $db->prepare($query);
    $stmt2->execute();
    $distribuidores_data = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

// Procesar los tipos de beneficios
$benefits = [];
$id_counter = 1;

// Si hay datos de tipos_beneficio, procesarlos
if (!empty($tipos_beneficios)) {
    foreach ($tipos_beneficios as $tipo) {
        // Determinar los valores basados en las columnas disponibles
        $id = isset($tipo['id_tipo_beneficio']) ? $tipo['id_tipo_beneficio'] : 
              (isset($tipo['id']) ? $tipo['id'] : $id_counter++);
        
        $nombre = isset($tipo['nombre_beneficio']) ? $tipo['nombre_beneficio'] : 
                 (isset($tipo['nombre']) ? $tipo['nombre'] : 
                 (isset($tipo['tipo_beneficio']) ? $tipo['tipo_beneficio'] : 'Beneficio ' . $id));
        
        $descripcion = isset($tipo['descripcion']) ? $tipo['descripcion'] : 'Beneficio nutricional disponible';
        
        $estado = 'activo';
        if (isset($tipo['estado'])) {
            $estado = ($tipo['estado'] == 1 || $tipo['estado'] === '1' || strtolower($tipo['estado']) === 'activo') ? 'activo' : 'inactivo';
        } elseif (isset($tipo['activo'])) {
            $estado = ($tipo['activo'] == 1 || $tipo['activo'] === '1' || strtolower($tipo['activo']) === 'si') ? 'activo' : 'inactivo';
        }
        
        // Asignar icono según el tipo de beneficio
        $icon = 'gift';
        $nombre_lower = strtolower($nombre);
        
        if (strpos($nombre_lower, 'fruta') !== false || 
            strpos($nombre_lower, 'verdura') !== false ||
            strpos($nombre_lower, 'fruver') !== false) {
            $icon = 'apple-alt';
        } elseif (strpos($nombre_lower, 'prote') !== false || 
                  strpos($nombre_lower, 'carne') !== false ||
                  strpos($nombre_lower, 'pollo') !== false ||
                  strpos($nombre_lower, 'pescado') !== false) {
            $icon = 'drumstick-bite';
        } elseif (strpos($nombre_lower, 'suple') !== false || 
                  strpos($nombre_lower, 'vitamina') !== false ||
                  strpos($nombre_lower, 'mineral') !== false) {
            $icon = 'pills';
        }
        
        $benefits[] = [
            'id' => $id,
            'beneficio' => $nombre,
            'descripcion' => $descripcion,
            'distribuidor' => 'INN - Instituto Nacional de Nutrición',
            'estado' => $estado,
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficios - Instituto Nacional de Nutrición</title>
    <!-- RUTA CORREGIDA para CSS -->
    <link href="<?php echo $base_url; ?>assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
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
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <p><strong>Nota:</strong> Error al cargar datos: <?php echo htmlspecialchars($error_message); ?></p>
                    <p class="mt-1 text-sm">Usando datos de ejemplo para la visualización.</p>
                </div>
                <?php endif; ?>      
                <!-- Estadísticas de beneficios -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Tipos de Beneficios</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <?php 
                                    $tipos_count = array_reduce($benefits, function($carry, $item) {
                                        return $carry + ($item['tipo'] === 'beneficio' ? 1 : 0);
                                    }, 0);
                                    echo $tipos_count;
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-gift text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-gray-500 <?php echo empty($tipos_beneficios) ? 'text-red-500' : 'text-green-500'; ?>">
                                <?php echo empty($tipos_beneficios) ? '⚠️ Datos por defecto' : '✓ Desde base de datos'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Distribuidores Activos</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <?php 
                                    $distribuidores_count = array_reduce($benefits, function($carry, $item) {
                                        return $carry + ($item['tipo'] === 'distribuidor' ? 1 : 0);
                                    }, 0);
                                    echo $distribuidores_count;
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-truck text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-gray-500 <?php echo empty($distribuidores_data) ? 'text-red-500' : 'text-green-500'; ?>">
                                <?php echo empty($distribuidores_data) ? '⚠️ Sin distribuidores' : '✓ Desde base de datos'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Registros</p>
                                <h3 class="text-3xl font-bold mt-2"><?php echo count($benefits); ?></h3>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-boxes text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-gray-500">
                                Beneficios + Distribuidores
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Beneficios -->
                <div class="bg-white rounded-xl shadow mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold">Catálogo de Beneficios Nutricionales</h3>
                                <p class="text-gray-600 mt-1">
                                    <?php if (!empty($tipos_beneficios)): ?>
                                        Tipos de beneficios disponibles desde la base de datos
                                    <?php else: ?>
                                        <span class="text-yellow-600">⚠️ Mostrando tipos de beneficios por defecto</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if (empty($tipos_beneficios)): ?>
                            <button class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-medium">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Datos por defecto
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo de Beneficio
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                // Filtrar solo los beneficios (no distribuidores)
                                $beneficios_filtrados = array_filter($benefits, function($item) {
                                    return $item['tipo'] === 'beneficio';
                                });
                                
                                if (count($beneficios_filtrados) > 0): 
                                    foreach($beneficios_filtrados as $benefit): 
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $benefit['id']; ?></div>
                                        <div class="text-xs text-gray-500">ID</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-<?php echo $benefit['icon']; ?> text-green-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($benefit['beneficio']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <i class="fas fa-<?php echo $benefit['icon']; ?> mr-1"></i>Beneficio nutricional
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($benefit['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($benefit['distribuidor']); ?></div>
                                        <div class="text-xs text-gray-500">Proveedor oficial</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            <?php echo $benefit['estado'] === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <span class="w-2 h-2 mr-2 rounded-full 
                                                <?php echo $benefit['estado'] === 'activo' ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                            <?php echo $benefit['estado'] === 'activo' ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-exclamation-triangle text-3xl mb-3 text-yellow-500"></i>
                                            <p class="text-lg font-medium">No se encontraron tipos de beneficios</p>
                                            <p class="mt-1">Verifica que la tabla 'tipo_beneficio' tenga datos</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla de Distribuidores -->
                <div class="bg-white rounded-xl shadow">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold">Empresas Distribuidoras Autorizadas</h3>
                                <p class="text-gray-600 mt-1">
                                    <?php if (!empty($distribuidores_data)): ?>
                                        Empresas que distribuyen beneficios según tabla: carga_temporal
                                    <?php else: ?>
                                        <span class="text-yellow-600">⚠️ No se encontraron distribuidores en la base de datos</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if (empty($distribuidores_data)): ?>
                            <button class="bg-red-100 text-red-800 px-3 py-1 rounded text-sm font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i>Sin distribuidores
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Empresa Distribuidora
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Servicio
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                // Filtrar solo los distribuidores
                                $distribuidores_filtrados = array_filter($benefits, function($item) {
                                    return $item['tipo'] === 'distribuidor';
                                });
                                
                                if (count($distribuidores_filtrados) > 0): 
                                    foreach($distribuidores_filtrados as $distribuidor): 
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-truck text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($distribuidor['distribuidor']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Empresa distribuidora
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($distribuidor['beneficio']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <i class="fas fa-truck mr-1"></i>Servicio de distribución
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($distribuidor['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            <?php echo $distribuidor['estado'] === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <span class="w-2 h-2 mr-2 rounded-full 
                                                <?php echo $distribuidor['estado'] === 'activo' ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                            <?php echo $distribuidor['estado'] === 'activo' ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-truck text-3xl mb-3 text-blue-500"></i>
                                            <p class="text-lg font-medium">No se encontraron distribuidores</p>
                                            <p class="mt-1">Verifica que la tabla 'carga_temporal' tenga datos en 'empresa_distribuidora'</p>
                                            <p class="mt-2 text-sm">Ejemplo de empresas distribuidoras esperadas: PDVAL, NutriHealth S.A., BioNutrientes, etc.</p>
                                        </div>
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
</body>
</html>