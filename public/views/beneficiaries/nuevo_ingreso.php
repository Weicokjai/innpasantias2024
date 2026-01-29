<?php
session_start();
$currentPage = 'Beneficiarios';
$currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];

// ===================================================
// 1. INCLUIR DEPENDENCIAS
// ===================================================
require_once $_SERVER['DOCUMENT_ROOT'] . '/innpasantias2024/public/config/database.php';
require_once __DIR__ . '/BeneficiarioController.php';

// ===================================================
// 2. INICIALIZAR
// ===================================================
$database = new Database();
$db = $database->getConnection();
$controller = new BeneficiarioController($db);

// ===================================================
// 3. PROCESAR FORMULARIO
// ===================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_offline') {
        $result = $controller->createOfflineBeneficiario($_POST);
        
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = 'error';
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ===================================================
// 4. OBTENER DATOS PARA FILTROS
// ===================================================
$beneficiarios = $controller->getAllBeneficiariosFormatted();

// Función para escape seguro
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Procesar filtros
function procesarFiltrosEficiente($beneficiarios) {
    $municipios = [];
    $parroquiasPorMunicipio = [];
    $sectoresPorParroquia = [];
    
    foreach($beneficiarios as $beneficiario) {
        $municipio = $beneficiario['municipio'] ?? '';
        $parroquia = $beneficiario['parroquia'] ?? '';
        $sector = $beneficiario['sector'] ?? '';
        
        if ($municipio && !isset($municipios[$municipio])) {
            $municipios[$municipio] = true;
        }
        
        if ($municipio && $parroquia) {
            if (!isset($parroquiasPorMunicipio[$municipio])) {
                $parroquiasPorMunicipio[$municipio] = [];
            }
            if (!isset($parroquiasPorMunicipio[$municipio][$parroquia])) {
                $parroquiasPorMunicipio[$municipio][$parroquia] = true;
            }
        }
        
        if ($parroquia && $sector) {
            if (!isset($sectoresPorParroquia[$parroquia])) {
                $sectoresPorParroquia[$parroquia] = [];
            }
            if (!isset($sectoresPorParroquia[$parroquia][$sector])) {
                $sectoresPorParroquia[$parroquia][$sector] = true;
            }
        }
    }
    
    // Convertir y ordenar
    $municipiosArray = array_keys($municipios);
    sort($municipiosArray);
    
    foreach ($parroquiasPorMunicipio as $municipio => $parroquias) {
        $parroquiasArray = array_keys($parroquias);
        sort($parroquiasArray);
        $parroquiasPorMunicipio[$municipio] = $parroquiasArray;
    }
    
    foreach ($sectoresPorParroquia as $parroquia => $sectores) {
        $sectoresArray = array_keys($sectores);
        sort($sectoresArray);
        $sectoresPorParroquia[$parroquia] = $sectoresArray;
    }
    
    return [
        'municipios' => $municipiosArray,
        'parroquias_por_municipio' => $parroquiasPorMunicipio,
        'sectores_por_parroquia' => $sectoresPorParroquia
    ];
}

$filtros = procesarFiltrosEficiente($beneficiarios);
$municipios = $filtros['municipios'];
$parroquiasPorMunicipio = $filtros['parroquias_por_municipio'];
$sectoresPorParroquia = $filtros['sectores_por_parroquia'];

// Convertir a JSON
$parroquias_json = json_encode($parroquiasPorMunicipio);
$sectores_json = json_encode($sectoresPorParroquia);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ingreso - Instituto Nacional de Nutrición</title>
    <link href="/innpasantias2024/public/assets/css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: 1.5rem;
        }
        .form-body {
            padding: 1.5rem;
        }
        .section-title {
            color: #1f2937;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #1f2937;
            transition: all 0.2s;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .required::after {
            content: " *";
            color: #ef4444;
        }
        .grid-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .grid-form-2 { grid-template-columns: repeat(2, 1fr); }
            .grid-form-3 { grid-template-columns: repeat(3, 1fr); }
            .grid-form-4 { grid-template-columns: repeat(4, 1fr); }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background-color: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover {
            background-color: #f9fafb;
        }
        .btn-primary {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-note {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include '../../components/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <a href="beneficiaries.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-2"></i>
                        </a>
                        Nuevo Ingreso
                    </h2>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Registro temporal para incorporación posterior
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-auto p-6">
                <?php if(isset($_SESSION['message'])): ?>
                <div class="bg-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $_SESSION['message_type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                    <?php echo e($_SESSION['message']); ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>

                <!-- Formulario -->
                <div class="form-container">
                    <div class="form-header">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-semibold">Registro de Nuevo Ingreso</h3>
                                <p class="text-green-100 mt-1">Complete todos los campos obligatorios (*)</p>
                            </div>
                            <a href="beneficiaries.php" class="text-white hover:text-green-200">
                                <i class="fas fa-times text-xl"></i>
                            </a>
                        </div>
                    </div>
                    
                    <form id="formNuevoIngreso" method="POST" action="" class="form-body">
                        <input type="hidden" name="action" value="create_offline">
                        <input type="hidden" id="parroquias_data_offline" value='<?php echo e($parroquias_json); ?>'>
                        <input type="hidden" id="sectores_data_offline" value='<?php echo e($sectores_json); ?>'>
                        
                        <div class="form-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Este registro se guardará temporalmente hasta que sea incorporado a la base de datos principal.</span>
                        </div>
                        
                        <!-- Representante DIVIDIDO EN NOMBRES Y APELLIDOS -->
                        <div class="mb-8">
                            <h4 class="section-title"><i class="fas fa-user-tie mr-2"></i>Información del Representante</h4>
                            <div class="grid-form grid-form-2">
                                <div>
                                    <label for="nombres_representante_offline" class="form-label required">Nombres del Representante</label>
                                    <input type="text" id="nombres_representante_offline" name="nombres_representante" required class="form-input" placeholder="Ej: María José">
                                </div>
                                <div>
                                    <label for="apellidos_representante_offline" class="form-label required">Apellidos del Representante</label>
                                    <input type="text" id="apellidos_representante_offline" name="apellidos_representante" required class="form-input" placeholder="Ej: Rodríguez Pérez">
                                </div>
                            </div>
                            <div class="grid-form grid-form-2 mt-4">
                                <div>
                                    <label for="cedula_representante_offline" class="form-label">Cédula del Representante</label>
                                    <input type="text" id="cedula_representante_offline" name="cedula_representante" class="form-input" placeholder="Ej: V-12345678">
                                </div>
                                <div>
                                    <label for="telefono_representante_offline" class="form-label">Teléfono de Contacto</label>
                                    <input type="text" id="telefono_representante_offline" name="telefono_representante" class="form-input" placeholder="Ej: 0412-1234567">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Beneficiario -->
                        <div class="mb-8">
                            <h4 class="section-title"><i class="fas fa-child mr-2"></i>Información del Beneficiario</h4>
                            <div class="grid-form grid-form-2">
                                <div>
                                    <label for="nombre_beneficiario_offline" class="form-label required">Nombre del Beneficiario</label>
                                    <input type="text" id="nombre_beneficiario_offline" name="nombre_beneficiario" required class="form-input" placeholder="Ej: Carlos">
                                </div>
                                <div>
                                    <label for="apellido_beneficiario_offline" class="form-label required">Apellido del Beneficiario</label>
                                    <input type="text" id="apellido_beneficiario_offline" name="apellido_beneficiario" required class="form-input" placeholder="Ej: Pérez">
                                </div>
                            </div>
                            <div class="grid-form grid-form-2 mt-4">
                                <div>
                                    <label for="cedula_beneficiario_offline" class="form-label">Cédula del Beneficiario</label>
                                    <input type="text" id="cedula_beneficiario_offline" name="cedula_beneficiario" class="form-input" placeholder="Ej: V-87654321">
                                </div>
                                <div>
                                    <label for="fecha_nacimiento_offline" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento_offline" name="fecha_nacimiento" class="form-input">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="genero_offline" class="form-label">Género</label>
                                <select id="genero_offline" name="genero" class="form-select">
                                    <option value="">Seleccionar género</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="mb-8">
                            <h4 class="section-title"><i class="fas fa-map-marker-alt mr-2"></i>Ubicación</h4>
                            <div class="grid-form grid-form-3">
                                <div>
                                    <label for="municipio_offline" class="form-label required">Municipio</label>
                                    <select id="municipio_offline" name="municipio" required class="form-select">
                                        <option value="">Seleccionar municipio</option>
                                        <?php foreach($municipios as $municipio): ?>
                                            <option value="<?php echo e($municipio); ?>"><?php echo e($municipio); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="parroquia_offline" class="form-label required">Parroquia</label>
                                    <select id="parroquia_offline" name="parroquia" required class="form-select" disabled>
                                        <option value="">Primero seleccione municipio</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="sector_offline" class="form-label required">Sector</label>
                                    <select id="sector_offline" name="sector" required class="form-select" disabled>
                                        <option value="">Primero seleccione parroquia</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Datos Antropométricos -->
                        <div class="mb-8">
                            <h4 class="section-title"><i class="fas fa-weight-scale mr-2"></i>Datos Antropométricos (Opcional)</h4>
                            <div class="grid-form grid-form-4">
                                <div>
                                    <label for="peso_offline" class="form-label">Peso (kg)</label>
                                    <input type="number" step="0.1" id="peso_offline" name="peso" class="form-input" placeholder="Ej: 25.5">
                                </div>
                                <div>
                                    <label for="talla_offline" class="form-label">Talla (cm)</label>
                                    <input type="number" step="0.1" id="talla_offline" name="talla" class="form-input" placeholder="Ej: 120.5">
                                </div>
                                <div>
                                    <label for="cbi_offline" class="form-label">CBI (mm)</label>
                                    <input type="number" step="0.1" id="cbi_offline" name="cbi" class="form-input" placeholder="Ej: 125.0">
                                </div>
                                <div>
                                    <label for="caso_offline" class="form-label">Tipo de Caso</label>
                                    <select id="caso_offline" name="caso" class="form-select">
                                        <option value="1">Caso A</option>
                                        <option value="2">Caso B</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observaciones -->
                        <div class="mb-8">
                            <h4 class="section-title"><i class="fas fa-notes-medical mr-2"></i>Observaciones</h4>
                            <div>
                                <label for="observaciones_offline" class="form-label">Observaciones adicionales</label>
                                <textarea id="observaciones_offline" name="observaciones" rows="3" class="form-textarea" placeholder="Observaciones adicionales..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="beneficiaries.php" class="btn btn-secondary">
                                <i class="fas fa-times btn-icon"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save btn-icon"></i>Guardar Nuevo Ingreso
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Datos de filtros
            let parroquiasData = JSON.parse($('#parroquias_data_offline').val() || '{}');
            let sectoresData = JSON.parse($('#sectores_data_offline').val() || '{}');
            
            // Filtro municipio → parroquia
            $('#municipio_offline').change(function() {
                let municipio = $(this).val();
                $('#parroquia_offline').html('<option value="">Seleccionar parroquia</option>');
                $('#sector_offline').html('<option value="">Seleccionar sector</option>');
                
                if (municipio && parroquiasData[municipio]) {
                    parroquiasData[municipio].sort().forEach(function(parroquia) {
                        $('#parroquia_offline').append('<option value="' + parroquia + '">' + parroquia + '</option>');
                    });
                    $('#parroquia_offline').prop('disabled', false);
                } else {
                    $('#parroquia_offline').prop('disabled', true);
                    $('#sector_offline').prop('disabled', true);
                }
            });
            
            // Filtro parroquia → sector
            $('#parroquia_offline').change(function() {
                let parroquia = $(this).val();
                $('#sector_offline').html('<option value="">Seleccionar sector</option>');
                
                if (parroquia && sectoresData[parroquia]) {
                    sectoresData[parroquia].sort().forEach(function(sector) {
                        $('#sector_offline').append('<option value="' + sector + '">' + sector + '</option>');
                    });
                    $('#sector_offline').prop('disabled', false);
                } else {
                    $('#sector_offline').prop('disabled', true);
                }
            });
            
            // Validación
            $('#formNuevoIngreso').submit(function(e) {
                let valid = true;
                $(this).find('[required]').each(function() {
                    if (!$(this).val().trim()) {
                        valid = false;
                        $(this).addClass('border-red-500');
                    } else {
                        $(this).removeClass('border-red-500');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Por favor complete todos los campos obligatorios (*)');
                }
            });
        });
    </script>
</body>
</html>