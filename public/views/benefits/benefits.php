<?php
$currentPage = 'Beneficios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Datos de ejemplo para la tabla
$benefits = [
    [
        'id' => 1,
        'beneficio' => 'Suplemento Proteico',
        'distribuidor' => 'NutriHealth S.A.',
        'estado' => 'activo'
    ],
    [
        'id' => 2,
        'beneficio' => 'Vitaminas Complejo B',
        'distribuidor' => 'BioNutrientes',
        'estado' => 'activo'
    ],
    [
        'id' => 3,
        'beneficio' => 'Minerales Esenciales',
        'distribuidor' => 'HealthPlus',
        'estado' => 'activo'
    ],
    [
        'id' => 4,
        'beneficio' => 'Omega 3',
        'distribuidor' => 'NutriCare',
        'estado' => 'inactivo'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficios - Instituto Nacional de Nutrición</title>
    <!-- RUTA CORREGIDA para CSS -->
 <link href="../../assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Gestión de Beneficios</h2>
                    <div class="flex items-center space-x-4">
                        <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Nuevo Beneficio
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">

                <!-- Tabla de Beneficios -->
                <div class="bg-white rounded-xl shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Lista de Beneficios</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Beneficio
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Distribuidor
                                    </th>

                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($benefits as $benefit): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-pills text-green-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($benefit['beneficio']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $benefit['estado'] === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <?php echo $benefit['estado'] === 'activo' ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($benefit['distribuidor']); ?></div>
                                        <div class="text-sm text-gray-500">Proveedor</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900 transition duration-150" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-900 transition duration-150" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-orange-600 hover:text-orange-900 transition duration-150" title="Distribuir">
                                                <i class="fas fa-share"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-900 transition duration-150" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando <span class="font-medium">1</span> a <span class="font-medium">4</span> de <span class="font-medium"><?php echo count($benefits); ?></span> resultados
                            </div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Anterior
                                </button>
                                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                    1
                                </button>
                                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>