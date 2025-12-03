<?php
session_start();
$currentPage = 'Beneficiarios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Incluir dependencias
include_once '../../config/database.php';
include_once '../../components/beneficiaries/BeneficiarioModel.php';
include_once '../../components/beneficiaries/BeneficiarioController.php';

// Inicializar
$database = new Database();
$db = $database->getConnection();
$controller = new BeneficiarioController($db);

// Manejar solicitudes
$controller->handleRequest();

// Obtener datos
$beneficiarios = $controller->getAllBeneficiariosFormatted();

// Función para formatear números
function formatearNumero($valor, $decimales = 2) {
    if ($valor === null || $valor === '') {
        return '';
    }
    // Convertir a float y formatear
    $numero = floatval($valor);
    return number_format($numero, $decimales, ',', '.');
}

// Formatear los datos de beneficiarios
foreach($beneficiarios as &$beneficiario) {
    $beneficiario['peso'] = formatearNumero($beneficiario['peso'], 1);
    $beneficiario['talla'] = formatearNumero($beneficiario['talla'], 1);
    $beneficiario['cbi'] = formatearNumero($beneficiario['cbi'], 1);
    $beneficiario['imc'] = formatearNumero($beneficiario['imc'], 2);
}

// Obtener datos únicos para los filtros y para el modal
$municipios = [];
$parroquiasPorMunicipio = [];
$sectoresPorParroquia = [];

foreach($beneficiarios as $beneficiario) {
    // Municipios
    if (!in_array($beneficiario['municipio'], $municipios)) {
        $municipios[] = $beneficiario['municipio'];
    }
    
    // Parroquias por municipio
    if (!empty($beneficiario['municipio']) && !empty($beneficiario['parroquia'])) {
        if (!isset($parroquiasPorMunicipio[$beneficiario['municipio']])) {
            $parroquiasPorMunicipio[$beneficiario['municipio']] = [];
        }
        if (!in_array($beneficiario['parroquia'], $parroquiasPorMunicipio[$beneficiario['municipio']])) {
            $parroquiasPorMunicipio[$beneficiario['municipio']][] = $beneficiario['parroquia'];
        }
    }
    
    // Sectores por parroquia
    if (!empty($beneficiario['parroquia']) && !empty($beneficiario['sector'])) {
        if (!isset($sectoresPorParroquia[$beneficiario['parroquia']])) {
            $sectoresPorParroquia[$beneficiario['parroquia']] = [];
        }
        if (!in_array($beneficiario['sector'], $sectoresPorParroquia[$beneficiario['parroquia']])) {
            $sectoresPorParroquia[$beneficiario['parroquia']][] = $beneficiario['sector'];
        }
    }
}

sort($municipios);
foreach ($parroquiasPorMunicipio as $municipio => $parroquias) {
    sort($parroquiasPorMunicipio[$municipio]);
}
foreach ($sectoresPorParroquia as $parroquia => $sectores) {
    sort($sectoresPorParroquia[$parroquia]);
}

// Convertir a JSON para usar en JavaScript
$parroquias_json = json_encode($parroquiasPorMunicipio);
$sectores_json = json_encode($sectoresPorParroquia);

// Datos para filtros independientes (sin dependencia)
$parroquias = [];
$sectores = [];
foreach($beneficiarios as $beneficiario) {
    if (!in_array($beneficiario['parroquia'], $parroquias)) {
        $parroquias[] = $beneficiario['parroquia'];
    }
    if (!in_array($beneficiario['sector'], $sectores)) {
        $sectores[] = $beneficiario['sector'];
    }
}
sort($parroquias);
sort($sectores);

// Simular datos para fuera de línea (en producción vendrían de localStorage o IndexedDB)
$fueraDeLinea = [];
if (isset($_SESSION['beneficiarios_fuera_linea'])) {
    $fueraDeLinea = $_SESSION['beneficiarios_fuera_linea'];
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <style>
        .modal-backup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
        .modal-content-backup { background: white; border-radius: 12px; width: 100%; max-width: 800px; max-height: 90vh; overflow: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        
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
        
        .badge-empty {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
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
            background-color: #fee2e2;
            color: #dc2626;
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
        
        /* Estilos para DataTables responsive */
        .dataTables_wrapper {
            padding: 0 !important;
        }
        
        .dataTables_filter {
            margin-bottom: 1rem !important;
        }
        
        .dataTables_length {
            margin-bottom: 1rem !important;
        }
        
        /* Estilos para tabla responsive en móviles */
        @media screen and (max-width: 767px) {
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: center !important;
                float: none !important;
                margin-top: 1rem !important;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-bottom: 0.5rem !important;
            }
            
            .dataTables_wrapper .dataTables_length select {
                width: 100% !important;
                margin-bottom: 0.5rem !important;
            }
        }
        
        /* Estilos para filas responsive de DataTables */
        .dtr-details {
            width: 100%;
        }
        
        .dtr-details li {
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem 0;
        }
        
        .dtr-title {
            font-weight: 600;
            color: #4b5563;
            min-width: 120px;
            display: inline-block;
        }
        
        .dtr-data {
            color: #111827;
        }
        
        /* Asegurar que la tabla ocupe el 100% */
        #tabla-beneficiarios_wrapper {
            width: 100% !important;
        }
        
        #tabla-beneficiarios {
            width: 100% !important;
        }
        
        /* Estilos para números formateados */
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
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>

                <!-- Pestañas -->
                <div class="mb-6">
                    <div class="flex border-b border-gray-200">
                        <button class="tab-button active" onclick="cambiarTab('beneficiarios')">
                            <i class="fas fa-users mr-2"></i>Beneficiarios
                            <span class="badge-online"><?php echo count($beneficiarios); ?></span>
                        </button>
                        <button class="tab-button" onclick="cambiarTab('fuera-linea')">
                            <i class="fas fa-wifi-slash mr-2"></i>Fuera de Línea
                            <span class="badge-offline"><?php echo count($fueraDeLinea); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Contenido de Beneficiarios -->
                <div id="tab-beneficiarios" class="tab-content active">
                    <!-- Botón Nuevo Beneficiario -->
                    <div class="mb-6 flex justify-between items-center">
                        <div></div>
                        <div class="flex gap-2">
                            <button onclick="abrirModal('online')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i>Nuevo Beneficiario
                            </button>
                        </div>
                    </div>

                    <!-- Filtros para Beneficiarios -->
                    <div class="bg-white rounded-xl shadow p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Filtros</h3>
                            <div class="flex gap-2">
                                <button id="btn-aplicar-filtros" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 whitespace-nowrap">
                                    <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                                </button>
                                <button id="btn-limpiar-filtros" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200 whitespace-nowrap">
                                    <i class="fas fa-times mr-2"></i>Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                                <select id="filtro-municipio" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todos</option>
                                    <?php foreach($municipios as $municipio): ?>
                                        <option value="<?php echo htmlspecialchars($municipio); ?>"><?php echo htmlspecialchars($municipio); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parroquia</label>
                                <select id="filtro-parroquia" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todas</option>
                                    <?php foreach($parroquias as $parroquia): ?>
                                        <option value="<?php echo htmlspecialchars($parroquia); ?>"><?php echo htmlspecialchars($parroquia); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sector</label>
                                <select id="filtro-sector" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todos</option>
                                    <?php foreach($sectores as $sector): ?>
                                        <option value="<?php echo htmlspecialchars($sector); ?>"><?php echo htmlspecialchars($sector); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso</label>
                                <select id="filtro-caso" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todos</option>
                                    <option value="1">Caso 1</option>
                                    <option value="2">Caso 2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Beneficiarios -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold">Lista de Beneficiarios</h3>
                            <p class="text-sm text-gray-600">Total: <span id="total-registros"><?php echo count($beneficiarios); ?></span> registros</p>
                        </div>
                        
                        <div class="p-6 overflow-x-auto">
                            <table id="tabla-beneficiarios" class="display responsive nowrap" style="width:100%">
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
                                    ?>
                                    <tr class="hover:bg-gray-50" 
                                        data-municipio="<?php echo htmlspecialchars($beneficiario['municipio']); ?>"
                                        data-parroquia="<?php echo htmlspecialchars($beneficiario['parroquia']); ?>"
                                        data-sector="<?php echo htmlspecialchars($beneficiario['sector']); ?>"
                                        data-caso="<?php echo htmlspecialchars($beneficiario['caso']); ?>"
                                        data-id="<?php echo htmlspecialchars($beneficiario['id'] ?? ''); ?>"
                                        data-peso="<?php echo htmlspecialchars(str_replace(',', '.', $beneficiario['peso'])); ?>"
                                        data-talla="<?php echo htmlspecialchars(str_replace(',', '.', $beneficiario['talla'])); ?>"
                                        data-cbi="<?php echo htmlspecialchars(str_replace(',', '.', $beneficiario['cbi'])); ?>"
                                        data-imc="<?php echo htmlspecialchars(str_replace(',', '.', $beneficiario['imc'])); ?>">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['municipio']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['parroquia']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($beneficiario['sector']); ?></div>
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['nombre_representante']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['cedula_representante']); ?></div>
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-child text-blue-600 text-xs"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['nombre_beneficiario'] . ' ' . $beneficiario['apellido_beneficiario']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['cedula_beneficiario']); ?></div>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($beneficiario['edad']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <div class="text-xs">
                                                <div class="flex justify-between">
                                                    <span>Peso:</span> 
                                                    <span class="font-medium numero-formateado"><?php echo htmlspecialchars($beneficiario['peso']); ?> kg</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>Talla:</span> 
                                                    <span class="font-medium numero-formateado"><?php echo htmlspecialchars($beneficiario['talla']); ?> cm</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>CBI:</span> 
                                                    <span class="font-medium numero-formateado"><?php echo htmlspecialchars($beneficiario['cbi']); ?> mm</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>IMC:</span> 
                                                    <span class="font-medium <?php echo $imc_class; ?>"><?php echo htmlspecialchars($beneficiario['imc']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $beneficiario['caso'] === '1' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                Caso <?php echo htmlspecialchars($beneficiario['caso']); ?>
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($beneficiario['estado']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3 text-sm font-medium">
                                            <div class="flex space-x-1">
                                                <button onclick="editarBeneficiario('<?php echo $beneficiario['id'] ?? $beneficiario['cedula_beneficiario']; ?>')" class="text-blue-600 hover:text-blue-900 transition duration-150 p-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-orange-600 hover:text-orange-900 transition duration-150 p-1" title="Asignar beneficio">
                                                    <i class="fas fa-gift"></i>
                                                </button>
                                                <button onclick="eliminarBeneficiario('<?php echo $beneficiario['cedula_beneficiario']; ?>', '<?php echo htmlspecialchars($beneficiario['nombre_beneficiario']); ?>')" class="text-red-600 hover:text-red-900 transition duration-150 p-1" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contenido de Fuera de Línea -->
                <div id="tab-fuera-linea" class="tab-content">
                    <!-- Botón Nuevo Beneficiario Fuera de Línea -->
                    <div class="mb-6 flex justify-between items-center">
                        <div></div>
                        <div class="flex gap-2">
                            <button onclick="abrirModal('offline')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i>Nuevo Beneficiario
                            </button>
                            <button onclick="sincronizarTodo()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center">
                                <i class="fas fa-sync-alt mr-2"></i>Sincronizar Todo
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Fuera de Línea -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold">Registros Fuera de Línea</h3>
                            <p class="text-sm text-gray-600">Total: <span id="total-registros-fdl"><?php echo count($fueraDeLinea); ?></span> registros pendientes de sincronización</p>
                        </div>
                        
                        <div class="p-6">
                            <?php if(empty($fueraDeLinea)): ?>
                                <div class="text-center py-12">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                                        <i class="fas fa-wifi-slash text-blue-600 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 mb-2">No hay registros fuera de línea</h4>
                                    <p class="text-gray-600 max-w-md mx-auto">
                                        Crea nuevos beneficiarios mientras estés sin conexión a internet.
                                        Se guardarán localmente y podrás sincronizarlos cuando vuelvas a tener conexión.
                                    </p>
                                    <div class="mt-6">
                                        <button onclick="abrirModal('offline')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                            <i class="fas fa-plus mr-2"></i>Crear Primer Beneficiario
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <table class="w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach($fueraDeLinea as $index => $registro): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($registro['fecha_creacion'] ?? date('d/m/Y H:i')); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user-clock text-yellow-600 text-xs"></i>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($registro['nombre_beneficiario'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($registro['cedula_beneficiario'] ?? 'Sin cédula'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($registro['sector'] ?? 'N/A'); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($registro['parroquia'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-offline">
                                                    <i class="fas fa-wifi-slash mr-1"></i> Pendiente
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium">
                                                <div class="flex space-x-1">
                                                    <button onclick="sincronizarRegistro(<?php echo $index; ?>)" class="text-blue-600 hover:text-blue-900 transition duration-150 p-1" title="Sincronizar">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <button onclick="editarRegistroFDL(<?php echo $index; ?>)" class="text-green-600 hover:text-green-900 transition duration-150 p-1" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="eliminarRegistroFDL(<?php echo $index; ?>)" class="text-red-600 hover:text-red-900 transition duration-150 p-1" title="Eliminar">
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
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo Beneficiario -->
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
                <input type="hidden" id="parroquias_data" value='<?php echo htmlspecialchars($parroquias_json); ?>'>
                <input type="hidden" id="sectores_data" value='<?php echo htmlspecialchars($sectores_json); ?>'>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Ubicación y CLAP</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="municipio" class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                            <select id="municipio" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar municipio</option>
                                <?php foreach($municipios as $municipio): ?>
                                    <option value="<?php echo htmlspecialchars($municipio); ?>"><?php echo htmlspecialchars($municipio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="parroquia" class="block text-sm font-medium text-gray-700 mb-2">Parroquia *</label>
                            <select id="parroquia" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Primero seleccione municipio</option>
                            </select>
                        </div>
                        <div>
                            <label for="sector" class="block text-sm font-medium text-gray-700 mb-2">Sector donde vive *</label>
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
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="nombre_representante" class="block text-sm font-medium text-gray-700 mb-2">Nombre Representante *</label>
                            <input type="text" id="nombre_representante" name="nombre_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Carlos">
                        </div>
                        <div>
                            <label for="apellido_representante" class="block text-sm font-medium text-gray-700 mb-2">Apellido Representante *</label>
                            <input type="text" id="apellido_representante" name="apellido_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Pérez">
                        </div>
                        <div>
                            <label for="cedula_representante" class="block text-sm font-medium text-gray-700 mb-2">Cédula Representante *</label>
                            <input type="text" id="cedula_representante" name="cedula_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-12345678">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="numero_contacto" class="block text-sm font-medium text-gray-700 mb-2">Número de Contacto *</label>
                        <input type="tel" id="numero_contacto" name="numero_contacto" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 0412-1234567">
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Beneficiario (Estudiante)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="nombre_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Nombre Beneficiario *</label>
                            <input type="text" id="nombre_beneficiario" name="nombre_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: María">
                        </div>
                        <div>
                            <label for="apellido_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Apellido Beneficiario *</label>
                            <input type="text" id="apellido_beneficiario" name="apellido_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: González">
                        </div>
                        <div>
                            <label for="genero" class="block text-sm font-medium text-gray-700 mb-2">Género M/F *</label>
                            <select id="genero" name="genero" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar género</option>
                                <option value="MASCULINO">MASCULINO (M)</option>
                                <option value="FEMENINO">FEMENINO (F)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Cédula Beneficiario (si la tiene)</label>
                            <input type="text" id="cedula_beneficiario" name="cedula_beneficiario" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-87654321">
                        </div>
                        <div>
                            <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento *</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                <span id="edad_display" class="text-gray-700">-- años</span>
                                <input type="hidden" id="edad" name="edad">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección específica para mujeres -->
                    <div id="seccion-mujer" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200" style="display: none;">
                        <h5 class="text-md font-medium text-gray-800 mb-3">Información Específica para Mujeres</h5>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Condición Especial *</label>
                                <div class="space-y-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="condicion_mujer" value="ninguna" checked class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Ninguna</span>
                                    </label>
                                    <label class="inline-flex items-center ml-6">
                                        <input type="radio" name="condicion_mujer" value="gestante" class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Gestante</span>
                                    </label>
                                    <label class="inline-flex items-center ml-6">
                                        <input type="radio" name="condicion_mujer" value="lactante" class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Lactante</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Campos para gestantes -->
                            <div id="campos-gestante" class="space-y-3 p-3 bg-yellow-50 rounded border border-yellow-200" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="semanas_gestacion" class="block text-sm font-medium text-gray-700 mb-2">Semanas de Gestación *</label>
                                        <input type="number" id="semanas_gestacion" name="semanas_gestacion" min="1" max="42" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28">
                                        <p class="text-xs text-gray-500 mt-1">Ingrese número de semanas (1-42)</p>
                                    </div>
                                    <div>
                                        <label for="fecha_probable_parto" class="block text-sm font-medium text-gray-700 mb-2">Fecha Probable de Parto</label>
                                        <input type="date" id="fecha_probable_parto" name="fecha_probable_parto" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <p class="text-xs text-gray-500 mt-1">Se calculará automáticamente</p>
                                    </div>
                                </div>
                                <div>
                                    <label for="observaciones_gestacion" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <textarea id="observaciones_gestacion" name="observaciones_gestacion" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Observaciones relevantes sobre la gestación"></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para lactantes -->
                            <div id="campos-lactante" class="space-y-3 p-3 bg-purple-50 rounded border border-purple-200" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="fecha_nacimiento_nino" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento del Niño *</label>
                                        <input type="date" id="fecha_nacimiento_nino" name="fecha_nacimiento_nino" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Edad del Niño</label>
                                        <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                            <span id="edad_nino_display" class="text-gray-700">--</span>
                                            <input type="hidden" id="edad_nino" name="edad_nino">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Lactancia</label>
                                    <div class="space-y-1">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia" value="exclusiva" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Materna Exclusiva</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia" value="mixta" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Mixta</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia" value="complementaria" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Complementaria</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label for="observaciones_lactancia" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <textarea id="observaciones_lactancia" name="observaciones_lactancia" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Observaciones relevantes sobre la lactancia"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Antropométrica</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="cbi_mm" class="block text-sm font-medium text-gray-700 mb-2">CBI (mm)</label>
                            <input type="number" id="cbi_mm" name="cbi_mm" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 142,0">
                        </div>
                        <div>
                            <label for="peso_kg" class="block text-sm font-medium text-gray-700 mb-2">Peso (Kg) *</label>
                            <input type="number" id="peso_kg" name="peso_kg" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28,5">
                        </div>
                        <div>
                            <label for="talla_cm" class="block text-sm font-medium text-gray-700 mb-2">Talla (cm) *</label>
                            <input type="number" id="talla_cm" name="talla_cm" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 125,0">
                        </div>
                        <div>
                            <label for="cci" class="block text-sm font-medium text-gray-700 mb-2">Cci</label>
                            <input type="text" id="cci" name="cci" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Calculado automáticamente">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">IMC</label>
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                <span id="imc_display" class="text-gray-700">--</span>
                                <input type="hidden" id="imc" name="imc">
                            </div>
                        </div>
                        <div>
                            <label for="situacion_dx" class="block text-sm font-medium text-gray-700 mb-2">Situación DX *</label>
                            <select id="situacion_dx" name="situacion_dx" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar situación</option>
                                <option value="1">Caso 1</option>
                                <option value="2">Caso 2</option>
                            </select>
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
                <input type="hidden" id="parroquias_data_editar" value='<?php echo htmlspecialchars($parroquias_json); ?>'>
                <input type="hidden" id="sectores_data_editar" value='<?php echo htmlspecialchars($sectores_json); ?>'>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Ubicación y CLAP</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="municipio_editar" class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                            <select id="municipio_editar" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar municipio</option>
                                <?php foreach($municipios as $municipio): ?>
                                    <option value="<?php echo htmlspecialchars($municipio); ?>"><?php echo htmlspecialchars($municipio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="parroquia_editar" class="block text-sm font-medium text-gray-700 mb-2">Parroquia *</label>
                            <select id="parroquia_editar" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Primero seleccione municipio</option>
                            </select>
                        </div>
                        <div>
                            <label for="sector_editar" class="block text-sm font-medium text-gray-700 mb-2">Sector donde vive *</label>
                            <select id="sector_editar" name="sector" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Primero seleccione parroquia</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="nombre_clap_editar" class="block text-sm font-medium text-gray-700 mb-2">Nombre CLAP</label>
                            <input type="text" id="nombre_clap_editar" name="nombre_clap" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: CLAP Libertador">
                        </div>
                        <div>
                            <label for="nombre_comuna_editar" class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Comuna</label>
                            <input type="text" id="nombre_comuna_editar" name="nombre_comuna" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Comuna 1">
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="nombre_representante_editar" class="block text-sm font-medium text-gray-700 mb-2">Nombre Representante *</label>
                            <input type="text" id="nombre_representante_editar" name="nombre_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Carlos">
                        </div>
                        <div>
                            <label for="apellido_representante_editar" class="block text-sm font-medium text-gray-700 mb-2">Apellido Representante *</label>
                            <input type="text" id="apellido_representante_editar" name="apellido_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Pérez">
                        </div>
                        <div>
                            <label for="cedula_representante_editar" class="block text-sm font-medium text-gray-700 mb-2">Cédula Representante *</label>
                            <input type="text" id="cedula_representante_editar" name="cedula_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-12345678">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="numero_contacto_editar" class="block text-sm font-medium text-gray-700 mb-2">Número de Contacto *</label>
                        <input type="tel" id="numero_contacto_editar" name="numero_contacto" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 0412-1234567">
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Beneficiario (Estudiante)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="nombre_beneficiario_editar" class="block text-sm font-medium text-gray-700 mb-2">Nombre Beneficiario *</label>
                            <input type="text" id="nombre_beneficiario_editar" name="nombre_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: María">
                        </div>
                        <div>
                            <label for="apellido_beneficiario_editar" class="block text-sm font-medium text-gray-700 mb-2">Apellido Beneficiario *</label>
                            <input type="text" id="apellido_beneficiario_editar" name="apellido_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: González">
                        </div>
                        <div>
                            <label for="genero_editar" class="block text-sm font-medium text-gray-700 mb-2">Género M/F *</label>
                            <select id="genero_editar" name="genero" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar género</option>
                                <option value="MASCULINO">MASCULINO (M)</option>
                                <option value="FEMENINO">FEMENINO (F)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="cedula_beneficiario_editar" class="block text-sm font-medium text-gray-700 mb-2">Cédula Beneficiario (si la tiene)</label>
                            <input type="text" id="cedula_beneficiario_editar" name="cedula_beneficiario" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-87654321">
                        </div>
                        <div>
                            <label for="fecha_nacimiento_editar" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento *</label>
                            <input type="date" id="fecha_nacimiento_editar" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                <span id="edad_display_editar" class="text-gray-700">-- años</span>
                                <input type="hidden" id="edad_editar" name="edad">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección específica para mujeres (editar) -->
                    <div id="seccion-mujer_editar" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200" style="display: none;">
                        <h5 class="text-md font-medium text-gray-800 mb-3">Información Específica para Mujeres</h5>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Condición Especial *</label>
                                <div class="space-y-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="condicion_mujer_editar" value="ninguna" class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Ninguna</span>
                                    </label>
                                    <label class="inline-flex items-center ml-6">
                                        <input type="radio" name="condicion_mujer_editar" value="gestante" class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Gestante</span>
                                    </label>
                                    <label class="inline-flex items-center ml-6">
                                        <input type="radio" name="condicion_mujer_editar" value="lactante" class="form-radio text-green-600">
                                        <span class="ml-2 text-sm text-gray-700">Lactante</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Campos para gestantes (editar) -->
                            <div id="campos-gestante_editar" class="space-y-3 p-3 bg-yellow-50 rounded border border-yellow-200" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="semanas_gestacion_editar" class="block text-sm font-medium text-gray-700 mb-2">Semanas de Gestación *</label>
                                        <input type="number" id="semanas_gestacion_editar" name="semanas_gestacion" min="1" max="42" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28">
                                        <p class="text-xs text-gray-500 mt-1">Ingrese número de semanas (1-42)</p>
                                    </div>
                                    <div>
                                        <label for="fecha_probable_parto_editar" class="block text-sm font-medium text-gray-700 mb-2">Fecha Probable de Parto</label>
                                        <input type="date" id="fecha_probable_parto_editar" name="fecha_probable_parto" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <p class="text-xs text-gray-500 mt-1">Se calculará automáticamente</p>
                                    </div>
                                </div>
                                <div>
                                    <label for="observaciones_gestacion_editar" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <textarea id="observaciones_gestacion_editar" name="observaciones_gestacion" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Observaciones relevantes sobre la gestación"></textarea>
                                </div>
                            </div>
                            
                            <!-- Campos para lactantes (editar) -->
                            <div id="campos-lactante_editar" class="space-y-3 p-3 bg-purple-50 rounded border border-purple-200" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="fecha_nacimiento_nino_editar" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento del Niño *</label>
                                        <input type="date" id="fecha_nacimiento_nino_editar" name="fecha_nacimiento_nino" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Edad del Niño</label>
                                        <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                            <span id="edad_nino_display_editar" class="text-gray-700">--</span>
                                            <input type="hidden" id="edad_nino_editar" name="edad_nino">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Lactancia</label>
                                    <div class="space-y-1">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia_editar" value="exclusiva" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Materna Exclusiva</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia_editar" value="mixta" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Mixta</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="tipo_lactancia_editar" value="complementaria" class="form-radio text-green-600">
                                            <span class="ml-2 text-sm text-gray-700">Lactancia Complementaria</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label for="observaciones_lactancia_editar" class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <textarea id="observaciones_lactancia_editar" name="observaciones_lactancia" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Observaciones relevantes sobre la lactancia"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Antropométrica</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="cbi_mm_editar" class="block text-sm font-medium text-gray-700 mb-2">CBI (mm)</label>
                            <input type="number" id="cbi_mm_editar" name="cbi_mm" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 142,0">
                        </div>
                        <div>
                            <label for="peso_kg_editar" class="block text-sm font-medium text-gray-700 mb-2">Peso (Kg) *</label>
                            <input type="number" id="peso_kg_editar" name="peso_kg" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28,5">
                        </div>
                        <div>
                            <label for="talla_cm_editar" class="block text-sm font-medium text-gray-700 mb-2">Talla (cm) *</label>
                            <input type="number" id="talla_cm_editar" name="talla_cm" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 125,0">
                        </div>
                        <div>
                            <label for="cci_editar" class="block text-sm font-medium text-gray-700 mb-2">Cci</label>
                            <input type="text" id="cci_editar" name="cci" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Calculado automáticamente">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">IMC</label>
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                <span id="imc_display_editar" class="text-gray-700">--</span>
                                <input type="hidden" id="imc_editar" name="imc">
                            </div>
                        </div>
                        <div>
                            <label for="situacion_dx_editar" class="block text-sm font-medium text-gray-700 mb-2">Situación DX *</label>
                            <select id="situacion_dx_editar" name="situacion_dx" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar situación</option>
                                <option value="1">Caso 1</option>
                                <option value="2">Caso 2</option>
                            </select>
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
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        // Variable global para la tabla
        var tablaBeneficiarios = null;
        var filtroActivo = null;
        var tipoModalActual = 'online';
        var beneficiarioEditando = null;

        $(document).ready(function() {
            console.log('DOM cargado, inicializando DataTable...');
            
            // Verificar que la tabla existe en el DOM
            if ($('#tabla-beneficiarios').length === 0) {
                console.error('No se encontró la tabla con id #tabla-beneficiarios');
                return;
            }
            
            // Inicializar DataTable con responsive
            try {
                tablaBeneficiarios = $('#tabla-beneficiarios').DataTable({
                    responsive: true,
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
                    order: [[0, 'asc']],
                    columnDefs: [
                        { 
                            targets: [5],
                            orderable: false,
                            searchable: false,
                            responsivePriority: 1
                        },
                        { 
                            targets: [0],
                            responsivePriority: 2
                        },
                        { 
                            targets: [2],
                            responsivePriority: 3
                        },
                        { 
                            targets: [3],
                            responsivePriority: 4
                        },
                        { 
                            targets: [1],
                            responsivePriority: 5
                        },
                        { 
                            targets: [4],
                            responsivePriority: 6
                        }
                    ],
                    initComplete: function() {
                        console.log('DataTable inicializado correctamente');
                        // Llamar a actualizarContador con verificación
                        if (tablaBeneficiarios) {
                            actualizarContador();
                        }
                    },
                    drawCallback: function() {
                        // Verificar que la tabla existe antes de actualizar
                        if (tablaBeneficiarios) {
                            actualizarContador();
                        }
                    }
                });
                
                console.log('DataTable creada con responsive:', tablaBeneficiarios);
                
                // Ajustar la tabla cuando cambie el tamaño de la ventana
                $(window).on('resize', function() {
                    if (tablaBeneficiarios) {
                        tablaBeneficiarios.columns.adjust().responsive.recalc();
                    }
                });
                
            } catch (error) {
                console.error('Error al inicializar DataTable:', error);
                return;
            }

            // Función para aplicar filtros
            function aplicarFiltros() {
                var municipio = $('#filtro-municipio').val();
                var parroquia = $('#filtro-parroquia').val();
                var sector = $('#filtro-sector').val();
                var caso = $('#filtro-caso').val();
                
                console.log('Aplicando filtros:', { municipio, parroquia, sector, caso });
                
                // Verificar que la tabla existe
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
                    // Verificar que la tabla existe
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
                    var casoMatch = !caso || rowCaso === caso;
                    
                    // Retornar true si todos los filtros coinciden
                    return municipioMatch && parroquiaMatch && sectorMatch && casoMatch;
                };
                
                // Aplicar el filtro
                $.fn.dataTable.ext.search.push(filtroActivo);
                
                // Redibujar la tabla
                tablaBeneficiarios.draw();
                
                // Actualizar UI
                actualizarUI();
            }

            // Función para limpiar filtros
            function limpiarFiltros() {
                console.log('Limpiando filtros');
                
                // Verificar que la tabla existe
                if (!tablaBeneficiarios) {
                    console.error('La tabla no está inicializada');
                    return;
                }
                
                // Limpiar selects
                $('#filtro-municipio, #filtro-parroquia, #filtro-sector, #filtro-caso').val('');
                
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
            }

            // Función para actualizar UI
            function actualizarUI() {
                // Quitar clase activa de todos
                $('#filtro-municipio, #filtro-parroquia, #filtro-sector, #filtro-caso').removeClass('filter-active');
                
                // Agregar clase activa a los que tienen valor
                if ($('#filtro-municipio').val()) $('#filtro-municipio').addClass('filter-active');
                if ($('#filtro-parroquia').val()) $('#filtro-parroquia').addClass('filter-active');
                if ($('#filtro-sector').val()) $('#filtro-sector').addClass('filter-active');
                if ($('#filtro-caso').val()) $('#filtro-caso').addClass('filter-active');
            }

            // Función para actualizar contador
            function actualizarContador() {
                // Verificar que la tabla existe
                if (!tablaBeneficiarios) {
                    console.error('La tabla no está inicializada en actualizarContador');
                    return;
                }
                
                try {
                    var totalFiltrado = tablaBeneficiarios.rows({ search: 'applied' }).count();
                    $('#total-registros').text(totalFiltrado);
                    console.log('Contador actualizado:', totalFiltrado);
                } catch (error) {
                    console.error('Error en actualizarContador:', error);
                }
            }

            // Event listeners
            $('#btn-aplicar-filtros').on('click', function() {
                console.log('Botón aplicar filtros clickeado');
                aplicarFiltros();
            });
            
            $('#btn-limpiar-filtros').on('click', function() {
                console.log('Botón limpiar filtros clickeado');
                limpiarFiltros();
            });
            
            // Aplicar filtros automáticamente cuando cambian los selects
            $('#filtro-municipio, #filtro-parroquia, #filtro-sector, #filtro-caso').on('change', function() {
                console.log('Select cambiado:', this.id);
                aplicarFiltros();
            });
        });

        // =====================================================================
        // FUNCIONALIDAD PARA EL MODAL (Municipio -> Parroquia -> Sector)
        // =====================================================================

        // Datos de parroquias y sectores (desde PHP)
        let parroquiasData = {};
        let sectoresData = {};
        
        try {
            parroquiasData = JSON.parse(document.getElementById('parroquias_data').value || '{}');
            sectoresData = JSON.parse(document.getElementById('sectores_data').value || '{}');
        } catch (error) {
            console.error('Error al parsear datos JSON:', error);
        }

        // Elementos del formulario del modal
        let municipioSelect = null;
        let parroquiaSelect = null;
        let sectorSelect = null;

        // Función para inicializar el sistema de dependencias del modal
        function inicializarDependenciasModal() {
            municipioSelect = document.getElementById('municipio');
            parroquiaSelect = document.getElementById('parroquia');
            sectorSelect = document.getElementById('sector');
            
            if (!municipioSelect || !parroquiaSelect || !sectorSelect) {
                console.error('No se encontraron todos los elementos del formulario');
                return;
            }
            
            // Evento cuando cambia el municipio
            municipioSelect.addEventListener('change', function() {
                const municipioSeleccionado = this.value;
                
                // Limpiar y resetear parroquia
                parroquiaSelect.innerHTML = '<option value="">Seleccionar parroquia</option>';
                parroquiaSelect.disabled = !municipioSeleccionado;
                parroquiaSelect.value = '';
                
                // Limpiar y resetear sector
                sectorSelect.innerHTML = '<option value="">Primero seleccione parroquia</option>';
                sectorSelect.disabled = true;
                sectorSelect.value = '';
                
                // Si hay municipio seleccionado, cargar sus parroquias
                if (municipioSeleccionado && parroquiasData[municipioSeleccionado]) {
                    const parroquias = parroquiasData[municipioSeleccionado];
                    
                    parroquias.forEach(function(parroquia) {
                        const option = document.createElement('option');
                        option.value = parroquia;
                        option.textContent = parroquia;
                        parroquiaSelect.appendChild(option);
                    });
                }
            });

            // Evento cuando cambia la parroquia
            parroquiaSelect.addEventListener('change', function() {
                const parroquiaSeleccionada = this.value;
                
                // Limpiar y resetear sector
                sectorSelect.innerHTML = '<option value="">Seleccionar sector</option>';
                sectorSelect.disabled = !parroquiaSeleccionada;
                sectorSelect.value = '';
                
                // Si hay parroquia seleccionada, cargar sus sectores
                if (parroquiaSeleccionada && sectoresData[parroquiaSeleccionada]) {
                    const sectores = sectoresData[parroquiaSeleccionada];
                    
                    sectores.forEach(function(sector) {
                        const option = document.createElement('option');
                        option.value = sector;
                        option.textContent = sector;
                        sectorSelect.appendChild(option);
                    });
                    
                    // Agregar opción para nuevo sector
                    const optionNuevoSector = document.createElement('option');
                    optionNuevoSector.value = 'nuevo';
                    optionNuevoSector.textContent = '➕ Agregar nuevo sector';
                    sectorSelect.appendChild(optionNuevoSector);
                }
            });

            // Evento para agregar nuevo sector
            sectorSelect.addEventListener('change', function() {
                if (this.value === 'nuevo') {
                    const nuevoSector = prompt('Ingrese el nombre del nuevo sector:');
                    
                    if (nuevoSector && nuevoSector.trim() !== '') {
                        // Crear nueva opción
                        const option = document.createElement('option');
                        option.value = nuevoSector.trim();
                        option.textContent = nuevoSector.trim();
                        option.selected = true;
                        
                        // Reemplazar la opción "nuevo" con el nuevo sector
                        this.innerHTML = '<option value="">Seleccionar sector</option>';
                        this.appendChild(option);
                        
                        // También añadir opción para agregar otro nuevo
                        const optionNuevoSector = document.createElement('option');
                        optionNuevoSector.value = 'nuevo';
                        optionNuevoSector.textContent = '➕ Agregar nuevo sector';
                        this.appendChild(optionNuevoSector);
                    } else {
                        // Si canceló, volver a la selección anterior
                        this.value = '';
                    }
                }
            });
            
            console.log('Sistema de dependencias del modal inicializado');
        }

        // =====================================================================
        // FUNCIONALIDAD PARA EL MODAL DE EDITAR (Municipio -> Parroquia -> Sector)
        // =====================================================================

        function inicializarDependenciasModalEditar() {
            const municipioSelectEditar = document.getElementById('municipio_editar');
            const parroquiaSelectEditar = document.getElementById('parroquia_editar');
            const sectorSelectEditar = document.getElementById('sector_editar');
            
            if (!municipioSelectEditar || !parroquiaSelectEditar || !sectorSelectEditar) {
                console.error('No se encontraron todos los elementos del formulario de edición');
                return;
            }
            
            // Evento cuando cambia el municipio en edición
            municipioSelectEditar.addEventListener('change', function() {
                const municipioSeleccionado = this.value;
                
                // Limpiar y resetear parroquia
                parroquiaSelectEditar.innerHTML = '<option value="">Seleccionar parroquia</option>';
                parroquiaSelectEditar.disabled = !municipioSeleccionado;
                parroquiaSelectEditar.value = '';
                
                // Limpiar y resetear sector
                sectorSelectEditar.innerHTML = '<option value="">Primero seleccione parroquia</option>';
                sectorSelectEditar.disabled = true;
                sectorSelectEditar.value = '';
                
                // Si hay municipio seleccionado, cargar sus parroquias
                if (municipioSeleccionado && parroquiasData[municipioSeleccionado]) {
                    const parroquias = parroquiasData[municipioSeleccionado];
                    
                    parroquias.forEach(function(parroquia) {
                        const option = document.createElement('option');
                        option.value = parroquia;
                        option.textContent = parroquia;
                        parroquiaSelectEditar.appendChild(option);
                    });
                }
            });

            // Evento cuando cambia la parroquia en edición
            parroquiaSelectEditar.addEventListener('change', function() {
                const parroquiaSeleccionada = this.value;
                
                // Limpiar y resetear sector
                sectorSelectEditar.innerHTML = '<option value="">Seleccionar sector</option>';
                sectorSelectEditar.disabled = !parroquiaSeleccionada;
                sectorSelectEditar.value = '';
                
                // Si hay parroquia seleccionada, cargar sus sectores
                if (parroquiaSeleccionada && sectoresData[parroquiaSeleccionada]) {
                    const sectores = sectoresData[parroquiaSeleccionada];
                    
                    sectores.forEach(function(sector) {
                        const option = document.createElement('option');
                        option.value = sector;
                        option.textContent = sector;
                        sectorSelectEditar.appendChild(option);
                    });
                    
                    // Agregar opción para nuevo sector
                    const optionNuevoSector = document.createElement('option');
                    optionNuevoSector.value = 'nuevo';
                    optionNuevoSector.textContent = '➕ Agregar nuevo sector';
                    sectorSelectEditar.appendChild(optionNuevoSector);
                }
            });

            // Evento para agregar nuevo sector en edición
            sectorSelectEditar.addEventListener('change', function() {
                if (this.value === 'nuevo') {
                    const nuevoSector = prompt('Ingrese el nombre del nuevo sector:');
                    
                    if (nuevoSector && nuevoSector.trim() !== '') {
                        // Crear nueva opción
                        const option = document.createElement('option');
                        option.value = nuevoSector.trim();
                        option.textContent = nuevoSector.trim();
                        option.selected = true;
                        
                        // Reemplazar la opción "nuevo" con el nuevo sector
                        this.innerHTML = '<option value="">Seleccionar sector</option>';
                        this.appendChild(option);
                        
                        // También añadir opción para agregar otro nuevo
                        const optionNuevoSector = document.createElement('option');
                        optionNuevoSector.value = 'nuevo';
                        optionNuevoSector.textContent = '➕ Agregar nuevo sector';
                        this.appendChild(optionNuevoSector);
                    } else {
                        // Si canceló, volver a la selección anterior
                        this.value = '';
                    }
                }
            });
            
            console.log('Sistema de dependencias del modal de edición inicializado');
        }

        // =====================================================================
        // FUNCIONALIDAD PARA CALCULAR EDAD E IMC (CORREGIDA)
        // =====================================================================

        // Función para formatear números en JavaScript
        function formatearNumeroJS(valor, decimales = 2) {
            if (valor === null || valor === '' || isNaN(valor)) {
                return '--';
            }
            // Usar punto como separador decimal para cálculos, coma para visualización
            return parseFloat(valor).toFixed(decimales).replace('.', ',');
        }

        // Inicializar cálculos de edad e IMC para modal nuevo
        function inicializarCalculos() {
            const fechaNacimiento = document.getElementById('fecha_nacimiento');
            const pesoInput = document.getElementById('peso_kg');
            const tallaInput = document.getElementById('talla_cm');
            
            if (fechaNacimiento) {
                fechaNacimiento.addEventListener('change', calcularEdad);
            }
            
            if (pesoInput && tallaInput) {
                pesoInput.addEventListener('input', calcularIMC);
                tallaInput.addEventListener('input', calcularIMC);
            }
        }

        // Inicializar cálculos de edad e IMC para modal editar
        function inicializarCalculosEditar() {
            const fechaNacimientoEditar = document.getElementById('fecha_nacimiento_editar');
            const pesoInputEditar = document.getElementById('peso_kg_editar');
            const tallaInputEditar = document.getElementById('talla_cm_editar');
            
            if (fechaNacimientoEditar) {
                fechaNacimientoEditar.addEventListener('change', calcularEdadEditar);
            }
            
            if (pesoInputEditar && tallaInputEditar) {
                pesoInputEditar.addEventListener('input', calcularIMCEditar);
                tallaInputEditar.addEventListener('input', calcularIMCEditar);
            }
        }

        function calcularEdad() {
            const fechaNacimiento = new Date(document.getElementById('fecha_nacimiento').value);
            const hoy = new Date();
            
            if (fechaNacimiento && fechaNacimiento <= hoy) {
                let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
                const mes = hoy.getMonth() - fechaNacimiento.getMonth();
                
                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
                    edad--;
                }
                
                // Mostrar edad directamente
                document.getElementById('edad_display').textContent = edad + ' años';
                document.getElementById('edad').value = edad;
            } else {
                document.getElementById('edad_display').textContent = '-- años';
                document.getElementById('edad').value = '';
            }
        }

        function calcularIMC() {
            // Obtener valores reemplazando coma por punto para cálculos
            const pesoValor = document.getElementById('peso_kg').value.replace(',', '.');
            const tallaValor = document.getElementById('talla_cm').value.replace(',', '.');
            
            const peso = parseFloat(pesoValor);
            const talla = parseFloat(tallaValor) / 100; // convertir a metros
            
            if (!isNaN(peso) && !isNaN(talla) && talla > 0) {
                const imc = peso / (talla * talla);
                // Formatear a 2 decimales con coma
                const imcFormateado = formatearNumeroJS(imc, 2);
                
                // Mostrar IMC formateado
                document.getElementById('imc_display').textContent = imcFormateado;
                // Guardar con punto para cálculos
                document.getElementById('imc').value = imc.toFixed(4);
                
                // Colorear según categoría de IMC
                const imcDisplay = document.getElementById('imc_display');
                imcDisplay.className = 'text-gray-700';
                
                if (imc < 18.5) {
                    imcDisplay.className = 'imc-bajo';
                } else if (imc >= 18.5 && imc < 25) {
                    imcDisplay.className = 'imc-normal';
                } else if (imc >= 25 && imc < 30) {
                    imcDisplay.className = 'imc-sobrepeso';
                } else if (imc >= 30) {
                    imcDisplay.className = 'imc-obeso';
                }
            } else {
                document.getElementById('imc_display').textContent = '--';
                document.getElementById('imc').value = '';
            }
        }

        function calcularEdadEditar() {
            const fechaNacimiento = new Date(document.getElementById('fecha_nacimiento_editar').value);
            const hoy = new Date();
            
            if (fechaNacimiento && fechaNacimiento <= hoy) {
                let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
                const mes = hoy.getMonth() - fechaNacimiento.getMonth();
                
                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
                    edad--;
                }
                
                // Mostrar edad directamente
                document.getElementById('edad_display_editar').textContent = edad + ' años';
                document.getElementById('edad_editar').value = edad;
            } else {
                document.getElementById('edad_display_editar').textContent = '-- años';
                document.getElementById('edad_editar').value = '';
            }
        }

        function calcularIMCEditar() {
            // Obtener valores reemplazando coma por punto para cálculos
            const pesoValor = document.getElementById('peso_kg_editar').value.replace(',', '.');
            const tallaValor = document.getElementById('talla_cm_editar').value.replace(',', '.');
            
            const peso = parseFloat(pesoValor);
            const talla = parseFloat(tallaValor) / 100; // convertir a metros
            
            if (!isNaN(peso) && !isNaN(talla) && talla > 0) {
                const imc = peso / (talla * talla);
                // Formatear a 2 decimales con coma
                const imcFormateado = formatearNumeroJS(imc, 2);
                
                // Mostrar IMC formateado
                document.getElementById('imc_display_editar').textContent = imcFormateado;
                // Guardar con punto para cálculos
                document.getElementById('imc_editar').value = imc.toFixed(4);
                
                // Colorear según categoría de IMC
                const imcDisplay = document.getElementById('imc_display_editar');
                imcDisplay.className = 'text-gray-700';
                
                if (imc < 18.5) {
                    imcDisplay.className = 'imc-bajo';
                } else if (imc >= 18.5 && imc < 25) {
                    imcDisplay.className = 'imc-normal';
                } else if (imc >= 25 && imc < 30) {
                    imcDisplay.className = 'imc-sobrepeso';
                } else if (imc >= 30) {
                    imcDisplay.className = 'imc-obeso';
                }
            } else {
                document.getElementById('imc_display_editar').textContent = '--';
                document.getElementById('imc_editar').value = '';
            }
        }

        // =====================================================================
        // FUNCIONALIDAD ESPECÍFICA PARA MUJERES (GESTANTES Y LACTANTES)
        // =====================================================================

        // Inicializar funcionalidad de mujeres para modal nuevo
        function inicializarFuncionalidadMujeres() {
            const generoSelect = document.getElementById('genero');
            const seccionMujer = document.getElementById('seccion-mujer');
            const camposGestante = document.getElementById('campos-gestante');
            const camposLactante = document.getElementById('campos-lactante');
            
            if (!generoSelect || !seccionMujer) {
                console.error('No se encontraron elementos para funcionalidad de mujeres');
                return;
            }
            
            // Evento cuando cambia el género
            generoSelect.addEventListener('change', function() {
                const generoSeleccionado = this.value;
                
                // Mostrar/ocultar sección de mujeres
                if (generoSeleccionado === 'FEMENINO') {
                    seccionMujer.style.display = 'block';
                    // Limpiar valores anteriores
                    limpiarCamposMujeres();
                } else {
                    seccionMujer.style.display = 'none';
                    limpiarCamposMujeres();
                }
            });
            
            // Evento para condición de mujer (gestante/lactante)
            document.querySelectorAll('input[name="condicion_mujer"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const condicion = this.value;
                    
                    // Mostrar/ocultar campos según condición
                    if (condicion === 'gestante') {
                        camposGestante.style.display = 'block';
                        camposLactante.style.display = 'none';
                    } else if (condicion === 'lactante') {
                        camposGestante.style.display = 'none';
                        camposLactante.style.display = 'block';
                    } else {
                        // Ninguna condición
                        camposGestante.style.display = 'none';
                        camposLactante.style.display = 'none';
                    }
                });
            });
        }

        // Inicializar funcionalidad de mujeres para modal editar
        function inicializarFuncionalidadMujeresEditar() {
            const generoSelectEditar = document.getElementById('genero_editar');
            const seccionMujerEditar = document.getElementById('seccion-mujer_editar');
            const camposGestanteEditar = document.getElementById('campos-gestante_editar');
            const camposLactanteEditar = document.getElementById('campos-lactante_editar');
            
            if (!generoSelectEditar || !seccionMujerEditar) {
                console.error('No se encontraron elementos para funcionalidad de mujeres en edición');
                return;
            }
            
            // Evento cuando cambia el género en edición
            generoSelectEditar.addEventListener('change', function() {
                const generoSeleccionado = this.value;
                
                // Mostrar/ocultar sección de mujeres
                if (generoSeleccionado === 'FEMENINO') {
                    seccionMujerEditar.style.display = 'block';
                } else {
                    seccionMujerEditar.style.display = 'none';
                    limpiarCamposMujeresEditar();
                }
            });
            
            // Evento para condición de mujer (gestante/lactante) en edición
            document.querySelectorAll('input[name="condicion_mujer_editar"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const condicion = this.value;
                    
                    // Mostrar/ocultar campos según condición
                    if (condicion === 'gestante') {
                        camposGestanteEditar.style.display = 'block';
                        camposLactanteEditar.style.display = 'none';
                    } else if (condicion === 'lactante') {
                        camposGestanteEditar.style.display = 'none';
                        camposLactanteEditar.style.display = 'block';
                    } else {
                        // Ninguna condición
                        camposGestanteEditar.style.display = 'none';
                        camposLactanteEditar.style.display = 'none';
                    }
                });
            });
        }

        // Función para limpiar campos específicos de mujeres
        function limpiarCamposMujeres() {
            // Resetear radios
            document.querySelector('input[name="condicion_mujer"][value="ninguna"]').checked = true;
            
            // Ocultar campos específicos
            const camposGestante = document.getElementById('campos-gestante');
            const camposLactante = document.getElementById('campos-lactante');
            if (camposGestante) camposGestante.style.display = 'none';
            if (camposLactante) camposLactante.style.display = 'none';
            
            // Limpiar inputs
            document.getElementById('semanas_gestacion').value = '';
            document.getElementById('fecha_probable_parto').value = '';
            document.getElementById('fecha_nacimiento_nino').value = '';
            document.getElementById('edad_nino_display').textContent = '--';
            if (document.getElementById('edad_nino')) document.getElementById('edad_nino').value = '';
            
            // Limpiar textareas
            document.getElementById('observaciones_gestacion').value = '';
            document.getElementById('observaciones_lactancia').value = '';
            
            // Resetear radios de tipo lactancia
            document.querySelectorAll('input[name="tipo_lactancia"]').forEach(radio => {
                radio.checked = false;
            });
        }

        // Función para limpiar campos específicos de mujeres en edición
        function limpiarCamposMujeresEditar() {
            // Resetear radios
            document.querySelector('input[name="condicion_mujer_editar"][value="ninguna"]').checked = true;
            
            // Ocultar campos específicos
            const camposGestanteEditar = document.getElementById('campos-gestante_editar');
            const camposLactanteEditar = document.getElementById('campos-lactante_editar');
            if (camposGestanteEditar) camposGestanteEditar.style.display = 'none';
            if (camposLactanteEditar) camposLactanteEditar.style.display = 'none';
            
            // Limpiar inputs
            document.getElementById('semanas_gestacion_editar').value = '';
            document.getElementById('fecha_probable_parto_editar').value = '';
            document.getElementById('fecha_nacimiento_nino_editar').value = '';
            document.getElementById('edad_nino_display_editar').textContent = '--';
            if (document.getElementById('edad_nino_editar')) document.getElementById('edad_nino_editar').value = '';
            
            // Limpiar textareas
            document.getElementById('observaciones_gestacion_editar').value = '';
            document.getElementById('observaciones_lactancia_editar').value = '';
            
            // Resetear radios de tipo lactancia
            document.querySelectorAll('input[name="tipo_lactancia_editar"]').forEach(radio => {
                radio.checked = false;
            });
        }

        // =====================================================================
        // FUNCIONES DEL MODAL NUEVO
        // =====================================================================

        function abrirModal(tipo = 'online') {
            console.log('Abriendo modal para:', tipo);
            tipoModalActual = tipo;
            
            // Configurar el modal según el tipo
            const modalTitulo = document.getElementById('modal-titulo');
            const btnGuardarTexto = document.getElementById('btn-guardar-texto');
            const formTipo = document.getElementById('form-tipo');
            
            if (tipo === 'online') {
                modalTitulo.textContent = 'Nuevo Beneficiario';
                btnGuardarTexto.textContent = 'Guardar Beneficiario';
                formTipo.value = 'online';
                document.getElementById('form-action').value = 'create';
            } else {
                modalTitulo.textContent = 'Nuevo Beneficiario (Fuera de Línea)';
                btnGuardarTexto.textContent = 'Guardar Localmente';
                formTipo.value = 'offline';
                document.getElementById('form-action').value = 'create_offline';
            }
            
            document.getElementById('nuevoBeneficiarioModal').style.display = 'flex';
            
            // Inicializar dependencias del modal
            inicializarDependenciasModal();
            
            // Inicializar cálculos
            inicializarCalculos();
            
            // Inicializar funcionalidad de mujeres
            inicializarFuncionalidadMujeres();
            
            // Resetear el formulario
            const form = document.getElementById('formNuevoBeneficiario');
            if (form) {
                form.reset();
            }
            
            // Resetear valores de visualización
            document.getElementById('edad_display').textContent = '-- años';
            document.getElementById('imc_display').textContent = '--';
            document.getElementById('edad').value = '';
            document.getElementById('imc').value = '';
            
            // Resetear campos de mujeres
            limpiarCamposMujeres();
            
            // Resetear selects dependientes
            if (parroquiaSelect) {
                parroquiaSelect.innerHTML = '<option value="">Primero seleccione municipio</option>';
                parroquiaSelect.disabled = true;
            }
            
            if (sectorSelect) {
                sectorSelect.innerHTML = '<option value="">Primero seleccione parroquia</option>';
                sectorSelect.disabled = true;
            }
            
            // Ocultar sección de mujeres por defecto
            const seccionMujer = document.getElementById('seccion-mujer');
            if (seccionMujer) {
                seccionMujer.style.display = 'none';
            }
        }
        
        function cerrarModal() {
            console.log('Cerrando modal nuevo');
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
        }

        // =====================================================================
        // FUNCIONES DEL MODAL EDITAR
        // =====================================================================

        function abrirModalEditar(beneficiarioId) {
            console.log('Abriendo modal para editar beneficiario:', beneficiarioId);
            beneficiarioEditando = beneficiarioId;
            
            // Cerrar cualquier modal abierto
            cerrarModal();
            cerrarModalEditar();
            
            // Mostrar modal de edición
            document.getElementById('editarBeneficiarioModal').style.display = 'flex';
            
            // Inicializar dependencias del modal de edición
            inicializarDependenciasModalEditar();
            
            // Inicializar cálculos para edición
            inicializarCalculosEditar();
            
            // Inicializar funcionalidad de mujeres para edición
            inicializarFuncionalidadMujeresEditar();
            
            // Buscar y cargar datos del beneficiario
            cargarDatosBeneficiario(beneficiarioId);
        }
        
        function cerrarModalEditar() {
            console.log('Cerrando modal editar');
            document.getElementById('editarBeneficiarioModal').style.display = 'none';
        }

        // Función para cargar datos del beneficiario en el formulario de edición
        function cargarDatosBeneficiario(beneficiarioId) {
            // Aquí normalmente harías una petición AJAX para obtener los datos
            // Por ahora, simulamos con datos de la tabla
            
            const fila = document.querySelector(`tr[data-id="${beneficiarioId}"]`);
            if (!fila) {
                console.error('No se encontró la fila del beneficiario');
                return;
            }
            
            // Obtener datos de la fila
            const municipio = fila.dataset.municipio;
            const parroquia = fila.dataset.parroquia;
            const sector = fila.dataset.sector;
            const caso = fila.dataset.caso;
            
            // Obtener datos de las celdas
            const celdas = fila.cells;
            
            // Datos de ubicación
            const ubicacionDivs = celdas[0].querySelectorAll('div');
            const municipioText = ubicacionDivs[0].textContent;
            const parroquiaText = ubicacionDivs[1].textContent;
            const sectorText = ubicacionDivs[2].textContent;
            
            // Datos del representante
            const representanteDivs = celdas[1].querySelectorAll('div');
            const nombreRepresentante = representanteDivs[0].textContent;
            const cedulaRepresentante = representanteDivs[1].textContent;
            
            // Datos del beneficiario
            const beneficiarioDiv = celdas[2].querySelector('.ml-3');
            const nombreCompleto = beneficiarioDiv.querySelector('div:nth-child(1)').textContent;
            const cedulaBeneficiario = beneficiarioDiv.querySelector('div:nth-child(2)').textContent;
            const edadText = beneficiarioDiv.querySelector('div:nth-child(3)').textContent;
            
            // Extraer nombre y apellido
            const [nombreBeneficiario, apellidoBeneficiario] = nombreCompleto.split(' ');
            
            // Datos antropométricos - obtener valores numéricos de los atributos data
            const peso = parseFloat(fila.dataset.peso || '0');
            const talla = parseFloat(fila.dataset.talla || '0');
            const cbi = parseFloat(fila.dataset.cbi || '0');
            const imc = parseFloat(fila.dataset.imc || '0');
            
            // Datos del caso
            const casoSpan = celdas[4].querySelector('span');
            const casoText = casoSpan.textContent.replace('Caso ', '');
            
            // Llenar el formulario con los datos
            document.getElementById('beneficiario_id').value = beneficiarioId;
            document.getElementById('municipio_editar').value = municipioText;
            
            // Cargar parroquias del municipio
            const municipioSelectEditar = document.getElementById('municipio_editar');
            const parroquiaSelectEditar = document.getElementById('parroquia_editar');
            
            if (municipioText && parroquiasData[municipioText]) {
                // Habilitar y cargar parroquias
                parroquiaSelectEditar.disabled = false;
                parroquiaSelectEditar.innerHTML = '<option value="">Seleccionar parroquia</option>';
                
                const parroquias = parroquiasData[municipioText];
                parroquias.forEach(function(parroquia) {
                    const option = document.createElement('option');
                    option.value = parroquia;
                    option.textContent = parroquia;
                    parroquiaSelectEditar.appendChild(option);
                });
                
                // Seleccionar la parroquia correcta
                parroquiaSelectEditar.value = parroquiaText;
                
                // Cargar sectores de la parroquia
                const sectorSelectEditar = document.getElementById('sector_editar');
                
                if (parroquiaText && sectoresData[parroquiaText]) {
                    // Habilitar y cargar sectores
                    sectorSelectEditar.disabled = false;
                    sectorSelectEditar.innerHTML = '<option value="">Seleccionar sector</option>';
                    
                    const sectores = sectoresData[parroquiaText];
                    sectores.forEach(function(sector) {
                        const option = document.createElement('option');
                        option.value = sector;
                        option.textContent = sector;
                        sectorSelectEditar.appendChild(option);
                    });
                    
                    // Agregar opción para nuevo sector
                    const optionNuevoSector = document.createElement('option');
                    optionNuevoSector.value = 'nuevo';
                    optionNuevoSector.textContent = '➕ Agregar nuevo sector';
                    sectorSelectEditar.appendChild(optionNuevoSector);
                    
                    // Seleccionar el sector correcto
                    sectorSelectEditar.value = sectorText;
                }
            }
            
            // Llenar otros campos
            document.getElementById('nombre_representante_editar').value = nombreRepresentante;
            document.getElementById('cedula_representante_editar').value = cedulaRepresentante;
            document.getElementById('nombre_beneficiario_editar').value = nombreBeneficiario;
            document.getElementById('apellido_beneficiario_editar').value = apellidoBeneficiario || '';
            document.getElementById('cedula_beneficiario_editar').value = cedulaBeneficiario;
            document.getElementById('edad_display_editar').textContent = edadText;
            
            // Formatear números para mostrar con coma
            document.getElementById('peso_kg_editar').value = isNaN(peso) ? '' : peso.toFixed(1).replace('.', ',');
            document.getElementById('talla_cm_editar').value = isNaN(talla) ? '' : talla.toFixed(1).replace('.', ',');
            document.getElementById('cbi_mm_editar').value = isNaN(cbi) ? '' : cbi.toFixed(1).replace('.', ',');
            document.getElementById('situacion_dx_editar').value = casoText;
            
            // Calcular IMC automáticamente
            setTimeout(() => {
                calcularIMCEditar();
            }, 100);
            
            console.log('Datos del beneficiario cargados en el formulario de edición');
        }

        // =====================================================================
        // FUNCIONES GENERALES DE LOS MODALES
        // =====================================================================

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalEditar();
            }
        });

        // Cerrar modales al hacer clic fuera
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('nuevoBeneficiarioModal');
            const modalEditar = document.getElementById('editarBeneficiarioModal');
            
            if (e.target === modal) {
                cerrarModal();
            }
            if (e.target === modalEditar) {
                cerrarModalEditar();
            }
        });

        // Manejar envío del formulario de nuevo beneficiario
        document.getElementById('formNuevoBeneficiario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Convertir comas a puntos en los campos numéricos antes de enviar
            const camposNumericos = ['peso_kg', 'talla_cm', 'cbi_mm', 'cci'];
            camposNumericos.forEach(campo => {
                const input = document.getElementById(campo);
                if (input && input.value) {
                    input.value = input.value.replace(',', '.');
                }
            });
            
            if (tipoModalActual === 'offline') {
                // Guardar localmente (fuera de línea)
                guardarLocalmente();
            } else {
                // Enviar al servidor (en línea)
                this.submit();
            }
        });

        // Manejar envío del formulario de editar beneficiario
        document.getElementById('formEditarBeneficiario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Convertir comas a puntos en los campos numéricos antes de enviar
            const camposNumericos = ['peso_kg_editar', 'talla_cm_editar', 'cbi_mm_editar', 'cci_editar'];
            camposNumericos.forEach(campo => {
                const input = document.getElementById(campo);
                if (input && input.value) {
                    input.value = input.value.replace(',', '.');
                }
            });
            
            // Aquí podrías agregar validación adicional
            console.log('Actualizando beneficiario:', beneficiarioEditando);
            
            // Enviar formulario
            this.submit();
        });

        // =====================================================================
        // FUNCIONES PARA FUERA DE LÍNEA
        // =====================================================================

        function guardarLocalmente() {
            const formData = new FormData(document.getElementById('formNuevoBeneficiario'));
            const data = {};
            
            // Convertir FormData a objeto
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Agregar fecha de creación
            data.fecha_creacion = new Date().toLocaleString('es-VE');
            
            // Aquí normalmente guardarías en localStorage o IndexedDB
            // Por ahora simulamos con un prompt
            console.log('Datos para guardar localmente:', data);
            
            alert('Beneficiario guardado localmente. Se sincronizará cuando haya conexión.');
            cerrarModal();
            
            // Recargar la página para mostrar el nuevo registro
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        function sincronizarTodo() {
            if (confirm('¿Está seguro de que desea sincronizar todos los registros fuera de línea?')) {
                // Aquí iría la lógica para sincronizar con el servidor
                alert('Sincronizando registros...');
                // Después de sincronizar, recargar la página
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        }

        function sincronizarRegistro(index) {
            if (confirm('¿Sincronizar este registro?')) {
                // Aquí iría la lógica para sincronizar un registro específico
                alert('Sincronizando registro...');
                // Después de sincronizar, recargar la página
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        function editarRegistroFDL(index) {
            alert('Editando registro fuera de línea #' + index);
            // Aquí abrirías el modal con los datos del registro
        }

        function eliminarRegistroFDL(index) {
            if (confirm('¿Eliminar este registro fuera de línea?')) {
                // Aquí eliminarías el registro del almacenamiento local
                alert('Registro eliminado');
                // Recargar la página
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }

        // =====================================================================
        // OTRAS FUNCIONES
        // =====================================================================

        function editarBeneficiario(id) {
            console.log('Editando beneficiario con ID:', id);
            abrirModalEditar(id);
        }

        function eliminarBeneficiario(cedula, nombre) {
            if (confirm(`¿Está seguro de que desea eliminar al beneficiario "${nombre}"?`)) {
                // Crear formulario para eliminar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const cedulaInput = document.createElement('input');
                cedulaInput.type = 'hidden';
                cedulaInput.name = 'cedula_beneficiario';
                cedulaInput.value = cedula;
                
                form.appendChild(actionInput);
                form.appendChild(cedulaInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function cambiarTab(tabName) {
            console.log('Cambiando a pestaña:', tabName);
            
            // Actualizar botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            document.querySelector(`[onclick="cambiarTab('${tabName}')"]`).classList.add('active');
            
            // Actualizar contenido
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Redibujar DataTable si es necesario
            if (tabName === 'beneficiarios' && tablaBeneficiarios) {
                setTimeout(() => {
                    try {
                        tablaBeneficiarios.columns.adjust().responsive.recalc();
                        console.log('Tabla redibujada');
                    } catch (error) {
                        console.error('Error al redibujar tabla:', error);
                    }
                }, 100);
            }
        }

        // =====================================================================
        // INICIALIZACIÓN
        // =====================================================================

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM completamente cargado');
            
            // Ocultar modales al inicio
            if (document.getElementById('nuevoBeneficiarioModal')) {
                document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
            } else {
                console.warn('Modal nuevo no encontrado');
            }
            
            if (document.getElementById('editarBeneficiarioModal')) {
                document.getElementById('editarBeneficiarioModal').style.display = 'none';
            } else {
                console.warn('Modal editar no encontrado');
            }
            
            // Verificar que jQuery esté cargado
            if (typeof jQuery === 'undefined') {
                console.error('jQuery no está cargado');
            } else {
                console.log('jQuery versión:', jQuery.fn.jquery);
            }
            
            // Verificar que DataTables esté cargado
            if (typeof $.fn.dataTable === 'undefined') {
                console.error('DataTables no está cargado');
            } else {
                console.log('DataTables cargado correctamente');
            }
            
            // Verificar que Responsive esté cargado
            if (typeof $.fn.dataTable.Responsive === 'undefined') {
                console.error('DataTables Responsive no está cargado');
            } else {
                console.log('DataTables Responsive cargado correctamente');
            }
        });
    </script>
</body>
</html>