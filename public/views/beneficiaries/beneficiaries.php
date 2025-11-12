<?php
session_start();
$currentPage = 'Beneficiarios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// Incluir dependencias - RUTAS CORRECTAS desde views/beneficiaries/
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiarios - Instituto Nacional de Nutrición</title>
    <link href="/innprojec/public/assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <style>
        .modal-backup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
        .modal-content-backup { background: white; border-radius: 12px; width: 100%; max-width: 800px; max-height: 90vh; overflow: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem; margin-left: 0.5rem; }
        .dataTables_wrapper .dataTables_length select { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border: 1px solid #d1d5db; padding: 0.5rem 1rem; margin-left: 0.25rem; border-radius: 0.375rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #16a34a; color: white !important; border-color: #16a34a; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #e5e7eb; border-color: #d1d5db; }
        table.dataTable { width: 100% !important; }
        .dataTables_wrapper { width: 100% !important; }
        table.dataTable thead th { border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        table.dataTable tbody td { white-space: nowrap; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include '../../components/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
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

            <main class="flex-1 overflow-x-auto p-6">
                <!-- Mostrar mensajes -->
                <?php if(isset($_SESSION['message'])): ?>
                <div class="bg-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Filtros</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                            <select id="filtro-municipio" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los municipios</option>
                                <option value="PALAVECINO">PALAVECINO</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Caso</label>
                            <select id="filtro-caso" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los casos</option>
                                <option value="1">Caso 1</option>
                                <option value="2">Caso 2</option>
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
                        <p class="text-sm text-gray-600">Total: <?php echo count($beneficiarios); ?> registros encontrados</p>
                    </div>
                    
                    <div class="p-6">
                        <table id="tabla-beneficiarios" class="w-full display" style="width: 100% !important;">
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
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($beneficiario['nombre_beneficiario'] . ' ' . $beneficiario['apellido_beneficiario']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($beneficiario['cedula_beneficiario']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($beneficiario['edad']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Antropometría -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-xs">
                                            <div class="flex justify-between"><span>Peso:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['peso']); ?></span></div>
                                            <div class="flex justify-between"><span>Talla:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['talla']); ?></span></div>
                                            <div class="flex justify-between"><span>CBI:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['cbi']); ?></span></div>
                                            <div class="flex justify-between"><span>IMC:</span> <span class="font-medium"><?php echo htmlspecialchars($beneficiario['imc']); ?></span></div>
                                        </div>
                                    </td>
                                    
                                    <!-- Caso -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $beneficiario['caso'] === '1' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                            Caso <?php echo htmlspecialchars($beneficiario['caso']); ?>
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($beneficiario['estado']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <!-- Acciones -->
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-1">
                                            <button onclick="editarBeneficiario('<?php echo $beneficiario['cedula_beneficiario']; ?>')" class="text-blue-600 hover:text-blue-900 transition duration-150 p-1" title="Editar">
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
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo Beneficiario -->
    <div id="nuevoBeneficiarioModal" class="modal-backup">
        <div class="modal-content-backup">
            <div class="bg-green-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-xl font-semibold">Nuevo Beneficiario</h3>
                <button onclick="cerrarModal()" class="text-white hover:text-gray-200 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formNuevoBeneficiario" method="POST" class="p-6">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Personal</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cedula_beneficiario" class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                            <input type="text" id="cedula_beneficiario" name="cedula_beneficiario" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: V-12345678">
                        </div>
                        <div>
                            <label for="genero" class="block text-sm font-medium text-gray-700 mb-2">Género *</label>
                            <select id="genero" name="genero" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar género</option>
                                <option value="MASCULINO">MASCULINO</option>
                                <option value="FEMENINO">FEMENINO</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="nombres" class="block text-sm font-medium text-gray-700 mb-2">Nombres *</label>
                            <input type="text" id="nombres" name="nombres" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: María José">
                        </div>
                        <div>
                            <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: González Pérez">
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
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Información Antropométrica</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="peso_kg" class="block text-sm font-medium text-gray-700 mb-2">Peso (kg) *</label>
                            <input type="number" id="peso_kg" name="peso_kg" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 28.5">
                        </div>
                        <div>
                            <label for="talla_cm" class="block text-sm font-medium text-gray-700 mb-2">Talla (cm) *</label>
                            <input type="number" id="talla_cm" name="talla_cm" step="0.1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 125.0">
                        </div>
                        <div>
                            <label for="imc" class="block text-sm font-medium text-gray-700 mb-2">IMC</label>
                            <input type="text" id="imc" name="imc" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100" placeholder="Se calculará automáticamente">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="cbi_mm" class="block text-sm font-medium text-gray-700 mb-2">Circunferencia Braquial (mm)</label>
                            <input type="number" id="cbi_mm" name="cbi_mm" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 142.0">
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
                        <i class="fas fa-save mr-2"></i>Guardar Beneficiario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
                    "infoFiltered": "(filtrado de _MAX_ registros Totales)",
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
                    }
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [5] }
                ],
                dom: '<"flex justify-between items-center mb-4"<"flex"l><"flex"f>>rt<"flex justify-between items-center mt-4"<"flex"i><"flex"p>>',
                autoWidth: false,
                scrollX: true
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

            function limpiarFiltros() {
                $('#filtro-municipio, #filtro-caso, #filtro-estado').val('');
                table.search('').columns().search('').draw();
            }
        });

        // Funciones para el modal
        function abrirModal() {
            document.getElementById('nuevoBeneficiarioModal').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
        }
        
        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        // Calcular IMC automáticamente
        document.getElementById('peso_kg').addEventListener('input', calcularIMC);
        document.getElementById('talla_cm').addEventListener('input', calcularIMC);
        
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso_kg').value);
            const talla = parseFloat(document.getElementById('talla_cm').value) / 100; // convertir a metros
            
            if (peso && talla) {
                const imc = peso / (talla * talla);
                document.getElementById('imc').value = imc.toFixed(2);
            } else {
                document.getElementById('imc').value = '';
            }
        }

        // Calcular edad automáticamente
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

        // Funciones para editar y eliminar
        function editarBeneficiario(cedula) {
            alert('Editando beneficiario con cédula: ' + cedula);
            // Aquí iría la lógica para cargar datos en el modal de edición
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

        // Asegurar que el modal esté oculto al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nuevoBeneficiarioModal').style.display = 'none';
        });
    </script>
</body>
</html>