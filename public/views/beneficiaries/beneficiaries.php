<?php
$currentPage = 'Beneficiarios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Datos de ejemplo para la tabla
$beneficiarios = [
    [
        'id' => 1,
        'municipio' => 'Municipio A',
        'parroquia' => 'Parroquia Central',
        'sector' => 'Sector Norte',
        'cedula_representante' => 'V-12345678',
        'nombre_representante' => 'María González',
        'cedula_beneficiario' => 'V-87654321',
        'nombre_beneficiario' => 'Ana González',
        'fecha_nacimiento' => '2015-03-15',
        'edad' => '8 años',
        'peso' => '28.5',
        'talla' => '1.25',
        'cbi' => '14.2',
        'csi' => '52.1',
        'imc' => '18.2',
        'caso' => 'A',
        'estado' => 'activo'
    ],
    [
        'id' => 2,
        'municipio' => 'Municipio B',
        'parroquia' => 'Parroquia Este',
        'sector' => 'Sector Sur',
        'cedula_representante' => 'V-23456789',
        'nombre_representante' => 'Carlos Rodríguez',
        'cedula_beneficiario' => 'V-98765432',
        'nombre_beneficiario' => 'Luis Rodríguez',
        'fecha_nacimiento' => '2017-08-20',
        'edad' => '6 años',
        'peso' => '22.0',
        'talla' => '1.15',
        'cbi' => '13.8',
        'csi' => '50.3',
        'imc' => '16.6',
        'caso' => 'B',
        'estado' => 'activo'
    ],
    [
        'id' => 3,
        'municipio' => 'Municipio A',
        'parroquia' => 'Parroquia Oeste',
        'sector' => 'Sector Centro',
        'cedula_representante' => 'V-34567890',
        'nombre_representante' => 'Elena Martínez',
        'cedula_beneficiario' => 'V-87654329',
        'nombre_beneficiario' => 'Sofía Martínez',
        'fecha_nacimiento' => '2013-11-10',
        'edad' => '10 años',
        'peso' => '32.0',
        'talla' => '1.35',
        'cbi' => '15.1',
        'csi' => '54.2',
        'imc' => '17.5',
        'caso' => 'A',
        'estado' => 'activo'
    ],
    [
        'id' => 4,
        'municipio' => 'Municipio C',
        'parroquia' => 'Parroquia Norte',
        'sector' => 'Sector Este',
        'cedula_representante' => 'V-45678901',
        'nombre_representante' => 'Roberto Sánchez',
        'cedula_beneficiario' => 'V-76543210',
        'nombre_beneficiario' => 'Pedro Sánchez',
        'fecha_nacimiento' => '2016-05-25',
        'edad' => '7 años',
        'peso' => '24.5',
        'talla' => '1.20',
        'cbi' => '14.0',
        'csi' => '51.0',
        'imc' => '17.0',
        'caso' => 'B',
        'estado' => 'inactivo'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiarios - Instituto Nacional de Nutrición</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <style>
        /* Estilos de respaldo para el modal */
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
            max-width: 800px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        
        /* Estilos para DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #6b7280;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #d1d5db;
            padding: 0.5rem 1rem;
            margin-left: 0.25rem;
            border-radius: 0.375rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #16a34a;
            color: white !important;
            border-color: #16a34a;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }
        
        /* Mejoras visuales para la tabla */
        table.dataTable tbody tr {
            background-color: white;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #f9fafb !important;
        }
        
        table.dataTable thead th {
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
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
                    <h2 class="text-xl font-semibold text-gray-800">Beneficiarios</h2>
                    <div class="flex items-center space-x-4">
                        <button onclick="abrirModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>Nuevo Beneficiario
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Filtros</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                            <select id="filtro-municipio" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los municipios</option>
                                <option value="Municipio A">Municipio A</option>
                                <option value="Municipio B">Municipio B</option>
                                <option value="Municipio C">Municipio C</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso</label>
                            <select id="filtro-caso" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los casos</option>
                                <option value="A">Caso A</option>
                                <option value="B">Caso B</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select id="filtro-estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="flex items-end space-x-2">
                            <button id="btn-filtrar" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                            <button id="btn-limpiar" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Beneficiarios -->
                <div class="bg-white rounded-xl shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Lista de Beneficiarios</h3>
                    </div>
                    
                    <div class="p-6">
                        <table id="tabla-beneficiarios" class="w-full display">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ubicación
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Representante
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Beneficiario
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Antropometría
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Caso
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($beneficiarios as $beneficiario): ?>
                                <tr>
                                    <!-- Ubicación -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['municipio']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['parroquia']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($beneficiario['sector']); ?></div>
                                    </td>
                                    
                                    <!-- Representante -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['nombre_representante']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['cedula_representante']); ?></div>
                                    </td>
                                    
                                    <!-- Beneficiario -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-child text-blue-600 text-xs"></i>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['nombre_beneficiario']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['cedula_beneficiario']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($beneficiario['edad']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Antropometría -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-xs">
                                            <div class="flex justify-between"><span>Peso:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['peso']); ?> kg</span></div>
                                            <div class="flex justify-between"><span>Talla:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['talla']); ?> m</span></div>
                                            <div class="flex justify-between"><span>CBI:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['cbi']); ?> cm</span></div>
                                            <div class="flex justify-between"><span>CSI:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['csi']); ?> cm</span></div>
                                            <div class="flex justify-between"><span>IMC:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['imc']); ?></span></div>
                                        </div>
                                    </td>
                                    
                                    <!-- Caso -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $beneficiario['caso'] === 'A' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                            Caso <?php echo $beneficiario['caso']; ?>
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $beneficiario['estado'] === 'activo' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $beneficiario['estado'] === 'activo' ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <!-- Acciones -->
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-1">
                                            <button onclick="editarBeneficiario(<?php echo $beneficiario['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 transition duration-150 p-1" 
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-orange-600 hover:text-orange-900 transition duration-150 p-1" title="Asignar beneficio">
                                                <i class="fas fa-gift"></i>
                                            </button>
                                            <button onclick="eliminarBeneficiario(<?php echo $beneficiario['id']; ?>, '<?php echo $beneficiario['nombre_beneficiario']; ?>')" 
                                                    class="text-red-600 hover:text-red-900 transition duration-150 p-1" 
                                                    title="Eliminar">
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
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo Beneficiario -->
    <div id="nuevoBeneficiarioModal" class="modal-backup">
        <div class="modal-content-backup">
            <!-- Header del Modal -->
            <div class="bg-green-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-xl font-semibold">Nuevo Beneficiario</h3>
                <button onclick="cerrarModal()" class="text-white hover:text-gray-200 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Formulario -->
            <form id="formNuevoBeneficiario" class="p-6">
                <!-- Información de Ubicación -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información de Ubicación</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="municipio" class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                            <select id="municipio" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar municipio</option>
                                <option value="Municipio A">Municipio A</option>
                                <option value="Municipio B">Municipio B</option>
                                <option value="Municipio C">Municipio C</option>
                            </select>
                        </div>
                        <div>
                            <label for="parroquia" class="block text-sm font-medium text-gray-700 mb-2">Parroquia *</label>
                            <select id="parroquia" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar parroquia</option>
                                <option value="Parroquia Central">Parroquia Central</option>
                                <option value="Parroquia Este">Parroquia Este</option>
                                <option value="Parroquia Oeste">Parroquia Oeste</option>
                                <option value="Parroquia Norte">Parroquia Norte</option>
                            </select>
                        </div>
                        <div>
                            <label for="sector" class="block text-sm font-medium text-gray-700 mb-2">Sector *</label>
                            <input type="text" id="sector" name="sector" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Sector Norte">
                        </div>
                    </div>
                </div>
                
                <!-- Información del Representante -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cedula_representante" class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                            <input type="text" id="cedula_representante" name="cedula_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-12345678">
                        </div>
                        <div>
                            <label for="nombre_representante" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" id="nombre_representante" name="nombre_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: María González">
                        </div>
                    </div>
                </div>
                
                <!-- Información del Beneficiario -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Beneficiario</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                            <input type="text" id="cedula_beneficiario" name="cedula_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-87654321">
                        </div>
                        <div>
                            <label for="nombre_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" id="nombre_beneficiario" name="nombre_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: Ana González">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento *</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="edad" class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <input type="text" id="edad" name="edad" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                </div>
                
                <!-- Información Antropométrica -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Antropométrica</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="peso" class="block text-sm font-medium text-gray-700 mb-2">Peso (kg) *</label>
                            <input type="number" id="peso" name="peso" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28.5">
                        </div>
                        <div>
                            <label for="talla" class="block text-sm font-medium text-gray-700 mb-2">Talla (m) *</label>
                            <input type="number" id="talla" name="talla" step="0.01" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 1.25">
                        </div>
                        <div>
                            <label for="imc" class="block text-sm font-medium text-gray-700 mb-2">IMC</label>
                            <input type="text" id="imc" name="imc" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="cbi" class="block text-sm font-medium text-gray-700 mb-2">Circunferencia Braquial (cm)</label>
                            <input type="number" id="cbi" name="cbi" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 14.2">
                        </div>
                        <div>
                            <label for="csi" class="block text-sm font-medium text-gray-700 mb-2">Circunferencia de la Cintura (cm)</label>
                            <input type="number" id="csi" name="csi" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 52.1">
                        </div>
                    </div>
                </div>
                
                <!-- Clasificación y Estado -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Clasificación y Estado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="caso" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso *</label>
                            <select id="caso" name="caso" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar caso</option>
                                <option value="A">Caso A</option>
                                <option value="B">Caso B</option>
                            </select>
                        </div>
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select id="estado" name="estado" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar estado</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i>Guardar Beneficiario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Beneficiario -->
    <div id="editarBeneficiarioModal" class="modal-backup">
        <div class="modal-content-backup">
            <!-- Header del Modal -->
            <div class="bg-blue-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-xl font-semibold">Editar Beneficiario</h3>
                <button onclick="cerrarModalEditar()" class="text-white hover:text-gray-200 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Formulario -->
            <form id="formEditarBeneficiario" class="p-6">
                <input type="hidden" id="editar_id" name="id">
                
                <!-- Información de Ubicación -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información de Ubicación</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="editar_municipio" class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                            <select id="editar_municipio" name="municipio" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar municipio</option>
                                <option value="Municipio A">Municipio A</option>
                                <option value="Municipio B">Municipio B</option>
                                <option value="Municipio C">Municipio C</option>
                            </select>
                        </div>
                        <div>
                            <label for="editar_parroquia" class="block text-sm font-medium text-gray-700 mb-2">Parroquia *</label>
                            <select id="editar_parroquia" name="parroquia" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar parroquia</option>
                                <option value="Parroquia Central">Parroquia Central</option>
                                <option value="Parroquia Este">Parroquia Este</option>
                                <option value="Parroquia Oeste">Parroquia Oeste</option>
                                <option value="Parroquia Norte">Parroquia Norte</option>
                            </select>
                        </div>
                        <div>
                            <label for="editar_sector" class="block text-sm font-medium text-gray-700 mb-2">Sector *</label>
                            <input type="text" id="editar_sector" name="sector" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Sector Norte">
                        </div>
                    </div>
                </div>
                
                <!-- Información del Representante -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Representante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="editar_cedula_representante" class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                            <input type="text" id="editar_cedula_representante" name="cedula_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: V-12345678">
                        </div>
                        <div>
                            <label for="editar_nombre_representante" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" id="editar_nombre_representante" name="nombre_representante" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: María González">
                        </div>
                    </div>
                </div>
                
                <!-- Información del Beneficiario -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información del Beneficiario</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="editar_cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                            <input type="text" id="editar_cedula_beneficiario" name="cedula_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: V-87654321">
                        </div>
                        <div>
                            <label for="editar_nombre_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" id="editar_nombre_beneficiario" name="nombre_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Ana González">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="editar_fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento *</label>
                            <input type="date" id="editar_fecha_nacimiento" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="editar_edad" class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                            <input type="text" id="editar_edad" name="edad" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                </div>
                
                <!-- Información Antropométrica -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Antropométrica</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="editar_peso" class="block text-sm font-medium text-gray-700 mb-2">Peso (kg) *</label>
                            <input type="number" id="editar_peso" name="peso" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 28.5">
                        </div>
                        <div>
                            <label for="editar_talla" class="block text-sm font-medium text-gray-700 mb-2">Talla (m) *</label>
                            <input type="number" id="editar_talla" name="talla" step="0.01" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 1.25">
                        </div>
                        <div>
                            <label for="editar_imc" class="block text-sm font-medium text-gray-700 mb-2">IMC</label>
                            <input type="text" id="editar_imc" name="imc" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="editar_cbi" class="block text-sm font-medium text-gray-700 mb-2">Circunferencia Braquial (cm)</label>
                            <input type="number" id="editar_cbi" name="cbi" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 14.2">
                        </div>
                        <div>
                            <label for="editar_csi" class="block text-sm font-medium text-gray-700 mb-2">Circunferencia de la Cintura (cm)</label>
                            <input type="number" id="editar_csi" name="csi" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 52.1">
                        </div>
                    </div>
                </div>
                
                <!-- Clasificación y Estado -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Clasificación y Estado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="editar_caso" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso *</label>
                            <select id="editar_caso" name="caso" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar caso</option>
                                <option value="A">Caso A</option>
                                <option value="B">Caso B</option>
                            </select>
                        </div>
                        <div>
                            <label for="editar_estado" class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select id="editar_estado" name="estado" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar estado</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
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

    <!-- jQuery (requerido por DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            var table = $('#tabla-beneficiarios').DataTable({
                responsive: true,
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "infoPostFix": "",
                    "thousands": ",",
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
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna ascendente",
                        "sortDescending": ": activar para ordenar la columna descendente"
                    }
                },
                dom: '<"flex justify-between items-center mb-4"<"flex"l><"flex"f>>rt<"flex justify-between items-center mt-4"<"flex"i><"flex"p>>',
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [5] } // Deshabilitar ordenamiento en columna de acciones
                ]
            });

            // Filtros personalizados
            $('#filtro-municipio, #filtro-caso, #filtro-estado').on('change', function() {
                aplicarFiltros();
            });

            $('#btn-filtrar').on('click', function() {
                aplicarFiltros();
            });

            $('#btn-limpiar').on('click', function() {
                limpiarFiltros();
            });

            function aplicarFiltros() {
                var municipio = $('#filtro-municipio').val();
                var caso = $('#filtro-caso').val();
                var estado = $('#filtro-estado').val();

                // Aplicar filtros a las columnas correspondientes
                table.column(0).search(municipio); // Columna de ubicación (municipio)
                table.column(4).search(caso); // Columna de caso
                
                // Para estado, buscar en la columna 4 que contiene el texto del estado
                if (estado) {
                    table.column(4).search('\\b' + estado + '\\b', true, false);
                } else {
                    table.column(4).search('');
                }

                table.draw();
            }

            // Función para limpiar filtros
            function limpiarFiltros() {
                $('#filtro-municipio, #filtro-caso, #filtro-estado').val('');
                table.search('').columns().search('').draw();
            }
        });

        // Funciones para el modal de nuevo beneficiario
        function abrirModal() {
            console.log('Abriendo modal...');
            document.getElementById('nuevoBeneficiarioModal').style.display = 'flex';
        }
        
        function cerrarModal() {
            console.log('Cerrando modal...');
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
        }
        
        // Funciones para el modal de edición
        function abrirModalEditar(beneficiario) {
            console.log('Abriendo modal de edición para:', beneficiario);
            
            // Llenar el formulario con los datos del beneficiario
            document.getElementById('editar_id').value = beneficiario.id;
            document.getElementById('editar_municipio').value = beneficiario.municipio;
            document.getElementById('editar_parroquia').value = beneficiario.parroquia;
            document.getElementById('editar_sector').value = beneficiario.sector;
            document.getElementById('editar_cedula_representante').value = beneficiario.cedula_representante;
            document.getElementById('editar_nombre_representante').value = beneficiario.nombre_representante;
            document.getElementById('editar_cedula_beneficiario').value = beneficiario.cedula_beneficiario;
            document.getElementById('editar_nombre_beneficiario').value = beneficiario.nombre_beneficiario;
            
            // Si existe fecha de nacimiento en los datos, usarla, de lo contrario calcularla
            if (beneficiario.fecha_nacimiento) {
                document.getElementById('editar_fecha_nacimiento').value = beneficiario.fecha_nacimiento;
            } else {
                // Calcular fecha aproximada basada en la edad
                const edad = parseInt(beneficiario.edad);
                const hoy = new Date();
                const añoNacimiento = hoy.getFullYear() - edad;
                const fechaAproximada = añoNacimiento + '-01-01';
                document.getElementById('editar_fecha_nacimiento').value = fechaAproximada;
            }
            
            document.getElementById('editar_edad').value = beneficiario.edad;
            document.getElementById('editar_peso').value = parseFloat(beneficiario.peso);
            document.getElementById('editar_talla').value = parseFloat(beneficiario.talla);
            document.getElementById('editar_cbi').value = parseFloat(beneficiario.cbi);
            document.getElementById('editar_csi').value = parseFloat(beneficiario.csi);
            document.getElementById('editar_imc').value = beneficiario.imc;
            document.getElementById('editar_caso').value = beneficiario.caso;
            document.getElementById('editar_estado').value = beneficiario.estado;
            
            // Mostrar el modal
            document.getElementById('editarBeneficiarioModal').style.display = 'flex';
        }
        
        function cerrarModalEditar() {
            console.log('Cerrando modal de edición...');
            document.getElementById('editarBeneficiarioModal').style.display = 'none';
            document.getElementById('formEditarBeneficiario').reset();
        }
        
        // Calcular IMC automáticamente en el modal de nuevo beneficiario
        document.getElementById('peso').addEventListener('input', calcularIMC);
        document.getElementById('talla').addEventListener('input', calcularIMC);
        
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso').value);
            const talla = parseFloat(document.getElementById('talla').value);
            
            if (peso && talla) {
                const imc = peso / (talla * talla);
                document.getElementById('imc').value = imc.toFixed(2);
            } else {
                document.getElementById('imc').value = '';
            }
        }
        
        // Calcular IMC automáticamente en el modal de edición
        document.getElementById('editar_peso').addEventListener('input', calcularIMCEditar);
        document.getElementById('editar_talla').addEventListener('input', calcularIMCEditar);
        
        function calcularIMCEditar() {
            const peso = parseFloat(document.getElementById('editar_peso').value);
            const talla = parseFloat(document.getElementById('editar_talla').value);
            
            if (peso && talla) {
                const imc = peso / (talla * talla);
                document.getElementById('editar_imc').value = imc.toFixed(2);
            } else {
                document.getElementById('editar_imc').value = '';
            }
        }
        
        // Calcular edad automáticamente en el modal de nuevo beneficiario
        document.getElementById('fecha_nacimiento').addEventListener('change', calcularEdad);
        
        function calcularEdad() {
            const fechaNacimiento = new Date(document.getElementById('fecha_nacimiento').value);
            const hoy = new Date();
            
            if (fechaNacimiento && fechaNacimiento <= hoy) {
                let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
                const mes = hoy.getMonth() - fechaNacimiento.getMonth();
                
                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
                    edad--;
                }
                
                document.getElementById('edad').value = `${edad} años`;
            } else {
                document.getElementById('edad').value = '';
            }
        }
        
        // Calcular edad automáticamente en el modal de edición
        document.getElementById('editar_fecha_nacimiento').addEventListener('change', calcularEdadEditar);
        
        function calcularEdadEditar() {
            const fechaNacimiento = new Date(document.getElementById('editar_fecha_nacimiento').value);
            const hoy = new Date();
            
            if (fechaNacimiento && fechaNacimiento <= hoy) {
                let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
                const mes = hoy.getMonth() - fechaNacimiento.getMonth();
                
                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
                    edad--;
                }
                
                document.getElementById('editar_edad').value = `${edad} años`;
            } else {
                document.getElementById('editar_edad').value = '';
            }
        }
        
        // Manejar envío del formulario de nuevo beneficiario
        document.getElementById('formNuevoBeneficiario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Aquí iría la lógica para guardar el beneficiario
            alert('Beneficiario guardado exitosamente');
            cerrarModal();
            this.reset();
        });

        // Manejar envío del formulario de edición
        document.getElementById('formEditarBeneficiario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const datos = Object.fromEntries(formData);
            
            console.log('Actualizando beneficiario:', datos);
            
            // Aquí iría la lógica para actualizar el beneficiario en la base de datos
            // Por ahora simulamos la actualización
            
            // Simular éxito
            alert('Beneficiario actualizado exitosamente');
            
            // Cerrar modal
            cerrarModalEditar();
            
            // Recargar la página para ver los cambios
            setTimeout(() => {
                location.reload();
            }, 1000);
        });

        // Función para editar beneficiario
        function editarBeneficiario(id) {
            console.log('Intentando editar beneficiario ID:', id);
            
            // Buscar el beneficiario en los datos PHP
            const beneficiarios = <?php echo json_encode($beneficiarios); ?>;
            
            // Buscar el beneficiario
            const beneficiario = beneficiarios.find(b => b.id == id);
            
            if (beneficiario) {
                console.log('Beneficiario encontrado:', beneficiario);
                abrirModalEditar(beneficiario);
            } else {
                console.error('Beneficiario no encontrado. ID buscado:', id);
                alert('Error: Beneficiario no encontrado. ID: ' + id);
            }
        }

        // Función para eliminar beneficiario
        function eliminarBeneficiario(id, nombre) {
            if (confirm(`¿Está seguro de que desea eliminar al beneficiario "${nombre}"? Esta acción no se puede deshacer.`)) {
                console.log('Eliminando beneficiario ID:', id);
                
                // Aquí iría la lógica para eliminar el beneficiario
                // Simular eliminación
                alert(`Beneficiario "${nombre}" eliminado exitosamente`);
                
                // Recargar la página
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        // Cerrar modales con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('nuevoBeneficiarioModal').style.display === 'flex') {
                    cerrarModal();
                }
                if (document.getElementById('editarBeneficiarioModal').style.display === 'flex') {
                    cerrarModalEditar();
                }
            }
        });

        // Asegurar que los modales estén ocultos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
            document.getElementById('editarBeneficiarioModal').style.display = 'none';
        });
    </script>
</body>
</html>