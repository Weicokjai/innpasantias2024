<?php
// Iniciar sesión para mensajes de feedback
session_start();

// Verificar si hay conexión en la sesión
if (!isset($_SESSION['db_conn'])) {
    // Si no hay conexión, intentar crear una
    include_once '../../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    $_SESSION['db_conn'] = $conn;
} else {
    $conn = $_SESSION['db_conn'];
}

// Variables para el formulario
$cedula_beneficiario = $nombres = $apellidos = $genero = $fecha_nacimiento = "";
$imc = $cbi_mm = $cci_cintura = $peso_kg = $talla_cm = $situacion_dx = "";
$lactando = "NO";
$fecha_nac_lactante = "";
$gestante = "NO";
$semanas_gestacion = 0;
$edad = 0;

// Mensajes de error/success
$mensaje = "";
$mensaje_tipo = ""; // success, error, warning

// Procesar formulario si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'register') {
    
    // Sanitizar y validar inputs
    $cedula_beneficiario = trim($_POST['cedula_beneficiario'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    
    // Datos antropométricos (pueden estar vacíos)
    $imc = !empty($_POST['imc']) ? floatval(str_replace(',', '.', $_POST['imc'])) : NULL;
    $cbi_mm = !empty($_POST['cbi_mm']) ? floatval(str_replace(',', '.', $_POST['cbi_mm'])) : NULL;
    $cci_cintura = !empty($_POST['cci_cintura']) ? floatval(str_replace(',', '.', $_POST['cci_cintura'])) : NULL;
    $peso_kg = !empty($_POST['peso_kg']) ? floatval(str_replace(',', '.', $_POST['peso_kg'])) : NULL;
    $talla_cm = !empty($_POST['talla_cm']) ? floatval(str_replace(',', '.', $_POST['talla_cm'])) : NULL;
    $situacion_dx = trim($_POST['situacion_dx'] ?? '');
    
    // Datos específicos para mujeres
    $lactando = isset($_POST['lactando']) && $_POST['lactando'] == 'SI' ? 'SI' : 'NO';
    $fecha_nac_lactante = !empty($_POST['fecha_nac_lactante']) ? trim($_POST['fecha_nac_lactante']) : NULL;
    $gestante = isset($_POST['gestante']) && $_POST['gestante'] == 'SI' ? 'SI' : 'NO';
    $semanas_gestacion = isset($_POST['semanas_gestacion']) ? intval($_POST['semanas_gestacion']) : 0;
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($cedula_beneficiario)) {
        $errores[] = "La cédula del beneficiario es obligatoria";
    }
    
    if (empty($nombres)) {
        $errores[] = "Los nombres son obligatorios";
    }
    
    if (empty($apellidos)) {
        $errores[] = "Los apellidos son obligatorios";
    }
    
    if (empty($genero)) {
        $errores[] = "El género es obligatorio";
    }
    
    if (empty($fecha_nacimiento)) {
        $errores[] = "La fecha de nacimiento es obligatoria";
    } else {
        // Calcular edad
        $fecha_nac = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;
        
        // Validar que la fecha no sea futura
        if ($fecha_nac > $hoy) {
            $errores[] = "La fecha de nacimiento no puede ser futura";
        }
    }
    
    // Si no hay errores, proceder con la inserción
    if (empty($errores)) {
        try {
            // Verificar si el beneficiario ya existe
            $sql_check = "SELECT cedula_beneficiario FROM beneficiario WHERE cedula_beneficiario = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $cedula_beneficiario);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $mensaje = "❌ El beneficiario con cédula $cedula_beneficiario ya está registrado en el sistema.";
                $mensaje_tipo = "error";
            } else {
                // Preparar la consulta de inserción
                $sql = "INSERT INTO beneficiario (
                    cedula_beneficiario, 
                    nombres, 
                    apellidos, 
                    genero, 
                    fecha_nacimiento, 
                    edad, 
                    imc, 
                    cbi_mm, 
                    cci_cintura, 
                    peso_kg, 
                    talla_cm, 
                    situacion_dx, 
                    lactando, 
                    fecha_nac_lactante, 
                    gestante, 
                    semanas_gestacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                
                // Bind parameters
                $stmt->bind_param(
                    "sssssidddddsissi",
                    $cedula_beneficiario,
                    $nombres,
                    $apellidos,
                    $genero,
                    $fecha_nacimiento,
                    $edad,
                    $imc,
                    $cbi_mm,
                    $cci_cintura,
                    $peso_kg,
                    $talla_cm,
                    $situacion_dx,
                    $lactando,
                    $fecha_nac_lactante,
                    $gestante,
                    $semanas_gestacion
                );
                
                // Ejecutar la inserción
                if ($stmt->execute()) {
                    $mensaje = "✅ Beneficiario registrado exitosamente en la base de datos.";
                    $mensaje_tipo = "success";
                    
                    // Limpiar el formulario
                    $cedula_beneficiario = $nombres = $apellidos = $genero = $fecha_nacimiento = "";
                    $imc = $cbi_mm = $cci_cintura = $peso_kg = $talla_cm = $situacion_dx = "";
                    $lactando = "NO";
                    $fecha_nac_lactante = "";
                    $gestante = "NO";
                    $semanas_gestacion = 0;
                    $edad = 0;
                    
                } else {
                    $mensaje = "❌ Error al registrar el beneficiario: " . $stmt->error;
                    $mensaje_tipo = "error";
                }
                
                $stmt->close();
            }
            
            $stmt_check->close();
            
        } catch (Exception $e) {
            $mensaje = "❌ Error en el sistema: " . $e->getMessage();
            $mensaje_tipo = "error";
        }
    } else {
        // Mostrar errores de validación
        $mensaje = "❌ " . implode("<br>❌ ", $errores);
        $mensaje_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Beneficiario - INN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #16a34a 0%, #0d8b3a 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }
        
        .message.success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border-left: 5px solid #16a34a;
        }
        
        .message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 5px solid #dc2626;
        }
        
        .message.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-left: 5px solid #f59e0b;
        }
        
        .form-section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f9fafb;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            color: #1f2937;
        }
        
        .section-title i {
            font-size: 1.5rem;
            color: #16a34a;
        }
        
        .section-title h2 {
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .required::after {
            content: " *";
            color: #dc2626;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
            accent-color: #16a34a;
        }
        
        .conditional-section {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        
        .conditional-section.active {
            display: block;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #16a34a 0%, #0d8b3a 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(22, 163, 74, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .small-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 0.9rem;
            padding: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Registrar Nuevo Beneficiario</h1>
            <p>Complete el formulario para agregar un nuevo beneficiario al sistema</p>
        </div>
        
        <div class="form-container">
            <?php if ($mensaje): ?>
                <div class="message <?php echo $mensaje_tipo; ?>">
                    <i class="fas fa-<?php echo $mensaje_tipo == 'success' ? 'check-circle' : ($mensaje_tipo == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                    <div><?php echo $mensaje; ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="action" value="register">
                
                <!-- Sección 1: Información Personal -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-id-card"></i>
                        <h2>Información Personal</h2>
                    </div>
                    
                    <div class="grid">
                        <div class="form-group">
                            <label for="cedula_beneficiario" class="required">Cédula del Beneficiario</label>
                            <input type="text" 
                                   id="cedula_beneficiario" 
                                   name="cedula_beneficiario" 
                                   value="<?php echo htmlspecialchars($cedula_beneficiario); ?>"
                                   placeholder="Ej: V-12345678 o 12345678"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombres" class="required">Nombres</label>
                            <input type="text" 
                                   id="nombres" 
                                   name="nombres" 
                                   value="<?php echo htmlspecialchars($nombres); ?>"
                                   placeholder="Ej: María José"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellidos" class="required">Apellidos</label>
                            <input type="text" 
                                   id="apellidos" 
                                   name="apellidos" 
                                   value="<?php echo htmlspecialchars($apellidos); ?>"
                                   placeholder="Ej: González Pérez"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="genero" class="required">Género</label>
                            <select id="genero" name="genero" required onchange="toggleWomenSection()">
                                <option value="">Seleccione un género</option>
                                <option value="MASCULINO" <?php echo $genero == 'MASCULINO' ? 'selected' : ''; ?>>MASCULINO</option>
                                <option value="FEMENINO" <?php echo $genero == 'FEMENINO' ? 'selected' : ''; ?>>FEMENINO</option>
                                <option value="OTRO" <?php echo $genero == 'OTRO' ? 'selected' : ''; ?>>OTRO</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_nacimiento" class="required">Fecha de Nacimiento</label>
                            <input type="date" 
                                   id="fecha_nacimiento" 
                                   name="fecha_nacimiento" 
                                   value="<?php echo htmlspecialchars($fecha_nacimiento); ?>"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 2: Datos Antropométricos -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-weight-scale"></i>
                        <h2>Datos Antropométricos</h2>
                    </div>
                    
                    <div class="small-inputs">
                        <div class="form-group">
                            <label for="peso_kg">Peso (kg)</label>
                            <input type="number" 
                                   id="peso_kg" 
                                   name="peso_kg" 
                                   value="<?php echo htmlspecialchars($peso_kg ?: ''); ?>"
                                   placeholder="Ej: 65.5"
                                   step="0.1"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="talla_cm">Talla (cm)</label>
                            <input type="number" 
                                   id="talla_cm" 
                                   name="talla_cm" 
                                   value="<?php echo htmlspecialchars($talla_cm ?: ''); ?>"
                                   placeholder="Ej: 165.0"
                                   step="0.1"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="imc">IMC</label>
                            <input type="number" 
                                   id="imc" 
                                   name="imc" 
                                   value="<?php echo htmlspecialchars($imc ?: ''); ?>"
                                   placeholder="Calculado automáticamente"
                                   step="0.01"
                                   min="0"
                                   readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="cbi_mm">CBI (mm)</label>
                            <input type="number" 
                                   id="cbi_mm" 
                                   name="cbi_mm" 
                                   value="<?php echo htmlspecialchars($cbi_mm ?: ''); ?>"
                                   placeholder="Ej: 142.0"
                                   step="0.1"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="cci_cintura">CCI Cintura</label>
                            <input type="number" 
                                   id="cci_cintura" 
                                   name="cci_cintura" 
                                   value="<?php echo htmlspecialchars($cci_cintura ?: ''); ?>"
                                   placeholder="Ej: 85.0"
                                   step="0.1"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="situacion_dx">Situación DX</label>
                            <select id="situacion_dx" name="situacion_dx">
                                <option value="">Seleccione una situación</option>
                                <option value="1" <?php echo $situacion_dx == '1' ? 'selected' : ''; ?>>Caso 1</option>
                                <option value="2" <?php echo $situacion_dx == '2' ? 'selected' : ''; ?>>Caso 2</option>
                                <option value="3" <?php echo $situacion_dx == '3' ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 3: Información Específica para Mujeres -->
                <div class="form-section" id="women-section" style="display: <?php echo $genero == 'FEMENINO' ? 'block' : 'none'; ?>;">
                    <div class="section-title">
                        <i class="fas fa-female"></i>
                        <h2>Información Específica para Mujeres</h2>
                    </div>
                    
                    <div class="grid">
                        <div class="form-group">
                            <label>Lactando</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" 
                                           name="lactando" 
                                           value="SI" 
                                           <?php echo $lactando == 'SI' ? 'checked' : ''; ?>
                                           onchange="toggleLactatingSection()">
                                    Sí
                                </label>
                                <label class="radio-option">
                                    <input type="radio" 
                                           name="lactando" 
                                           value="NO" 
                                           <?php echo $lactando == 'NO' ? 'checked' : ''; ?>
                                           onchange="toggleLactatingSection()">
                                    No
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Gestante</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" 
                                           name="gestante" 
                                           value="SI" 
                                           <?php echo $gestante == 'SI' ? 'checked' : ''; ?>
                                           onchange="togglePregnantSection()">
                                    Sí
                                </label>
                                <label class="radio-option">
                                    <input type="radio" 
                                           name="gestante" 
                                           value="NO" 
                                           <?php echo $gestante == 'NO' ? 'checked' : ''; ?>
                                           onchange="togglePregnantSection()">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección condicional para lactantes -->
                    <div id="lactating-section" class="conditional-section <?php echo $lactando == 'SI' ? 'active' : ''; ?>">
                        <h3 style="margin-bottom: 15px; color: #4b5563;"><i class="fas fa-baby"></i> Información del Lactante</h3>
                        <div class="grid">
                            <div class="form-group">
                                <label for="fecha_nac_lactante">Fecha de Nacimiento del Lactante</label>
                                <input type="date" 
                                       id="fecha_nac_lactante" 
                                       name="fecha_nac_lactante" 
                                       value="<?php echo htmlspecialchars($fecha_nac_lactante ?: ''); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección condicional para gestantes -->
                    <div id="pregnant-section" class="conditional-section <?php echo $gestante == 'SI' ? 'active' : ''; ?>">
                        <h3 style="margin-bottom: 15px; color: #4b5563;"><i class="fas fa-heart"></i> Información de la Gestación</h3>
                        <div class="grid">
                            <div class="form-group">
                                <label for="semanas_gestacion">Semanas de Gestación</label>
                                <input type="number" 
                                       id="semanas_gestacion" 
                                       name="semanas_gestacion" 
                                       value="<?php echo htmlspecialchars($semanas_gestacion); ?>"
                                       placeholder="Ej: 28"
                                       min="1"
                                       max="42">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Beneficiario
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-eraser"></i> Limpiar Formulario
                    </button>
                </div>
                
                <div class="form-footer">
                    <p><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Función para mostrar/ocultar sección de mujeres
        function toggleWomenSection() {
            const genero = document.getElementById('genero').value;
            const womenSection = document.getElementById('women-section');
            
            if (genero === 'FEMENINO') {
                womenSection.style.display = 'block';
            } else {
                womenSection.style.display = 'none';
                // Resetear valores específicos de mujeres
                document.querySelectorAll('input[name="lactando"]').forEach(radio => {
                    if (radio.value === 'NO') radio.checked = true;
                });
                document.querySelectorAll('input[name="gestante"]').forEach(radio => {
                    if (radio.value === 'NO') radio.checked = true;
                });
                toggleLactatingSection();
                togglePregnantSection();
            }
        }
        
        // Función para mostrar/ocultar sección de lactantes
        function toggleLactatingSection() {
            const lactando = document.querySelector('input[name="lactando"]:checked');
            const lactatingSection = document.getElementById('lactating-section');
            
            if (lactando && lactando.value === 'SI') {
                lactatingSection.classList.add('active');
            } else {
                lactatingSection.classList.remove('active');
            }
        }
        
        // Función para mostrar/ocultar sección de gestantes
        function togglePregnantSection() {
            const gestante = document.querySelector('input[name="gestante"]:checked');
            const pregnantSection = document.getElementById('pregnant-section');
            
            if (gestante && gestante.value === 'SI') {
                pregnantSection.classList.add('active');
            } else {
                pregnantSection.classList.remove('active');
            }
        }
        
        // Calcular IMC automáticamente
        document.getElementById('peso_kg').addEventListener('input', calculateIMC);
        document.getElementById('talla_cm').addEventListener('input', calculateIMC);
        
        function calculateIMC() {
            const peso = parseFloat(document.getElementById('peso_kg').value);
            const talla = parseFloat(document.getElementById('talla_cm').value);
            
            if (peso && talla && talla > 0) {
                const imc = peso / ((talla / 100) ** 2);
                document.getElementById('imc').value = imc.toFixed(2);
            } else {
                document.getElementById('imc').value = '';
            }
        }
        
        // Función para resetear el formulario completamente
        function resetForm() {
            if (confirm('¿Está seguro de que desea limpiar todo el formulario?')) {
                // Resetear todos los campos
                document.querySelector('form').reset();
                // Ocultar secciones condicionales
                document.getElementById('women-section').style.display = 'none';
                document.getElementById('lactating-section').classList.remove('active');
                document.getElementById('pregnant-section').classList.remove('active');
                // Limpiar IMC
                document.getElementById('imc').value = '';
            }
        }
        
        // Validar formulario antes de enviar
        document.querySelector('form').addEventListener('submit', function(event) {
            const cedula = document.getElementById('cedula_beneficiario').value;
            const nombres = document.getElementById('nombres').value;
            const apellidos = document.getElementById('apellidos').value;
            const genero = document.getElementById('genero').value;
            const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
            
            if (!cedula || !nombres || !apellidos || !genero || !fechaNacimiento) {
                alert('Por favor complete todos los campos obligatorios (*)');
                event.preventDefault();
            }
        });
        
        // Inicializar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            calculateIMC(); // Calcular IMC si hay valores
            toggleWomenSection(); // Configurar sección de mujeres
        });
    </script>
</body>
</html>