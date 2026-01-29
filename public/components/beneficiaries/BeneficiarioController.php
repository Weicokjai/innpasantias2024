<?php
class BeneficiarioController {
    private $model;
    
    public function __construct($db) {
        $this->model = new BeneficiarioModel($db);
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            error_log("=== CONTROLADOR: Acción detectada: $action ===");
            
            switch ($action) {
                case 'create':
                    $this->createBeneficiario();
                    break;
                    
                case 'create_nuevo_ingreso':
                    $this->createNuevoIngreso();
                    break;
                    
                case 'update':
                    error_log("=== CONTROLADOR: Iniciando update ===");
                    $this->updateBeneficiario();
                    break;
                    
                case 'delete':
                    $this->deleteBeneficiario();
                    break;
                    
                case 'delete_nuevo_ingreso':
                    $this->deleteNuevoIngreso();
                    break;
                    
                case 'incorporar':
                    $this->incorporarNuevoIngreso();
                    break;
                    
                case 'incorporar_todos':
                    $this->incorporarTodos();
                    break;
            }
        }
    }
    
    private function createBeneficiario() {
        try {
            $required_fields = ['nombres', 'apellidos', 'cedula_beneficiario', 
                              'representante_nombre', 'representante_cedula', 
                              'municipio', 'parroquia', 'sector', 'situacion_dx'];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            if ($this->model->cedulaExists($_POST['cedula_beneficiario'], 'online')) {
                $_SESSION['message'] = "La cédula del beneficiario ya está registrada en beneficiarios activos";
                $_SESSION['message_type'] = 'error';
                return;
            }
            
            if (empty($_POST['edad']) && !empty($_POST['fecha_nacimiento'])) {
                $nacimiento = new DateTime($_POST['fecha_nacimiento']);
                $hoy = new DateTime();
                $edad = $hoy->diff($nacimiento)->y;
                $_POST['edad'] = $edad;
            }
            
            if (empty($_POST['imc']) && !empty($_POST['peso_kg']) && !empty($_POST['talla_cm'])) {
                $peso = floatval($_POST['peso_kg']);
                $talla = floatval($_POST['talla_cm']) / 100;
                if ($talla > 0) {
                    $_POST['imc'] = $peso / ($talla * $talla);
                }
            }
            
            // Validar campos para lactante/gestante
            if (isset($_POST['genero']) && $_POST['genero'] === 'F') {
                $condicion_femenina = $_POST['condicion_femenina'] ?? 'nada';
                
                if ($condicion_femenina === 'lactante' && empty($_POST['fecha_nacimiento_bebe'])) {
                    throw new Exception("Para beneficiaria lactante, debe ingresar la fecha de nacimiento del bebé");
                }
                
                if ($condicion_femenina === 'gestante') {
                    if (empty($_POST['semanas_gestacion'])) {
                        throw new Exception("Para beneficiaria gestante, debe ingresar las semanas de gestación");
                    }
                    
                    $semanas = intval($_POST['semanas_gestacion']);
                    if ($semanas < 1 || $semanas > 42) {
                        throw new Exception("Las semanas de gestación deben estar entre 1 y 42");
                    }
                }
            } else {
                $_POST['condicion_femenina'] = 'nada';
                $_POST['fecha_nacimiento_bebe'] = null;
                $_POST['semanas_gestacion'] = null;
            }
            
            $success = $this->model->createBeneficiario($_POST);
            
            if ($success) {
                $_SESSION['message'] = "✅ Beneficiario registrado exitosamente";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Error al registrar beneficiario");
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function updateBeneficiario() {
        try {
            error_log("=== CONTROLADOR: Inicio de updateBeneficiario ===");
            error_log("Datos POST recibidos: " . print_r($_POST, true));
            
            // Validar que existe el ID del beneficiario
            if (empty($_POST['beneficiario_id'])) {
                error_log("ERROR: beneficiario_id no encontrado en POST");
                throw new Exception("Error: ID del beneficiario no proporcionado. Datos: " . print_r($_POST, true));
            }
            
            $beneficiario_id = $_POST['beneficiario_id'];
            error_log("ID del beneficiario a actualizar: $beneficiario_id");
            
            // Lista de campos requeridos con nombres EXACTOS del formulario
            $required_fields = [
                'nombres', 
                'apellidos',
                'cedula_beneficiario',
                'fecha_nacimiento',
                'genero',
                'municipio',
                'parroquia',
                'sector',
                'representante_nombre',
                'representante_apellido',
                'representante_cedula',
                'representante_telefono',
                'cbi_mm',
                'peso_kg',
                'talla_cm',
                'situacion_dx'
            ];
            
            // Validar campos requeridos
            $missing_fields = [];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field]) && $_POST[$field] !== '0') {
                    $missing_fields[] = $field;
                    error_log("Campo faltante: $field = '" . ($_POST[$field] ?? 'VACÍO') . "'");
                }
            }
            
            if (!empty($missing_fields)) {
                throw new Exception("Error: Campos requeridos faltantes: " . implode(', ', $missing_fields));
            }
            
            // Verificar que el beneficiario existe
            if (!$this->model->beneficiarioExists($beneficiario_id)) {
                throw new Exception("El beneficiario con ID $beneficiario_id no existe o ha sido eliminado");
            }
            
            // Calcular edad si no se proporciona
            $edad = 0;
            if (empty($_POST['edad']) && !empty($_POST['fecha_nacimiento'])) {
                try {
                    $nacimiento = new DateTime($_POST['fecha_nacimiento']);
                    $hoy = new DateTime();
                    $edad = $hoy->diff($nacimiento)->y;
                    error_log("Edad calculada: $edad años");
                } catch (Exception $e) {
                    error_log("Error calculando edad: " . $e->getMessage());
                }
            } else {
                $edad = intval($_POST['edad'] ?? 0);
            }
            
            // Calcular IMC si no se proporciona
            $imc = 0;
            if (empty($_POST['imc']) && !empty($_POST['peso_kg']) && !empty($_POST['talla_cm'])) {
                $peso = floatval($_POST['peso_kg']);
                $talla = floatval($_POST['talla_cm']) / 100;
                if ($talla > 0) {
                    $imc = $peso / ($talla * $talla);
                    error_log("IMC calculado: $imc");
                }
            } else {
                $imc = floatval($_POST['imc'] ?? 0);
            }
            
            // Validar campos para lactante/gestante
            $condicion_femenina = 'nada';
            $fecha_nacimiento_bebe = null;
            $semanas_gestacion = null;
            
            if (isset($_POST['genero']) && $_POST['genero'] === 'F') {
                $condicion_femenina = $_POST['condicion_femenina'] ?? 'nada';
                error_log("Condición femenina: $condicion_femenina");
                
                if ($condicion_femenina === 'lactante') {
                    if (empty($_POST['fecha_nacimiento_bebe'])) {
                        throw new Exception("Para beneficiaria lactante, debe ingresar la fecha de nacimiento del bebé");
                    }
                    $fecha_nacimiento_bebe = $_POST['fecha_nacimiento_bebe'];
                    error_log("Fecha nacimiento bebé: $fecha_nacimiento_bebe");
                } elseif ($condicion_femenina === 'gestante') {
                    if (empty($_POST['semanas_gestacion'])) {
                        throw new Exception("Para beneficiaria gestante, debe ingresar las semanas de gestación");
                    }
                    
                    $semanas = intval($_POST['semanas_gestacion']);
                    if ($semanas < 1 || $semanas > 42) {
                        throw new Exception("Las semanas de gestación deben estar entre 1 y 42");
                    }
                    $semanas_gestacion = $semanas;
                    error_log("Semanas gestación: $semanas_gestacion");
                }
            } else {
                $condicion_femenina = 'nada';
            }
            
            // Preparar datos para el modelo - NOMBRES EXACTOS como espera el modelo
            $data = [
                'beneficiario_id' => $beneficiario_id, // ¡IMPORTANTE! El modelo espera este nombre
                'id_beneficiario' => $beneficiario_id, // Por si acaso
                'nombres' => $_POST['nombres'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'cedula_beneficiario' => $_POST['cedula_beneficiario'] ?? '',
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
                'edad' => $edad,
                'genero' => $_POST['genero'] ?? '',
                'condicion_femenina' => $condicion_femenina,
                'fecha_nacimiento_bebe' => $fecha_nacimiento_bebe,
                'semanas_gestacion' => $semanas_gestacion,
                'representante_nombre' => $_POST['representante_nombre'] ?? '',
                'representante_apellido' => $_POST['representante_apellido'] ?? '',
                'representante_cedula' => $_POST['representante_cedula'] ?? '',
                'representante_telefono' => $_POST['representante_telefono'] ?? '',
                'municipio' => $_POST['municipio'] ?? '',
                'parroquia' => $_POST['parroquia'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'nombre_clap' => $_POST['nombre_clap'] ?? '',
                'nombre_comuna' => $_POST['nombre_comuna'] ?? '',
                'cbi_mm' => floatval($_POST['cbi_mm'] ?? 0),
                'peso_kg' => floatval($_POST['peso_kg'] ?? 0),
                'talla_cm' => floatval($_POST['talla_cm'] ?? 0),
                'imc' => $imc,
                'situacion_dx' => $_POST['situacion_dx'] ?? ''
            ];
            
            error_log("Datos preparados para el modelo: " . print_r($data, true));
            
            // Llamar al modelo para actualizar
            $success = $this->model->updateBeneficiario($data);
            
            if ($success) {
                $_SESSION['message'] = "✅ Beneficiario actualizado exitosamente";
                $_SESSION['message_type'] = 'success';
                error_log("=== CONTROLADOR: Actualización exitosa ===");
            } else {
                error_log("=== CONTROLADOR: El modelo devolvió false en updateBeneficiario ===");
                throw new Exception("Error al actualizar beneficiario en la base de datos");
            }
            
        } catch (Exception $e) {
            error_log("=== CONTROLADOR: ERROR en updateBeneficiario: " . $e->getMessage() . " ===");
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function createNuevoIngreso() {
        try {
            $required_fields = ['nombres', 'apellidos', 'cedula_beneficiario', 
                              'representante_nombre', 'representante_cedula', 
                              'municipio', 'parroquia', 'sector', 'situacion_dx'];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            if ($this->model->cedulaExists($_POST['cedula_beneficiario'], 'offline')) {
                $_SESSION['message'] = "✅ Esta cédula ya está registrada en nuevos ingresos (se actualizó el registro)";
                $_SESSION['message_type'] = 'info';
            }
            
            if (!empty($_POST['fecha_nacimiento'])) {
                $nacimiento = new DateTime($_POST['fecha_nacimiento']);
                $hoy = new DateTime();
                $edad = $hoy->diff($nacimiento)->y;
                $_POST['edad'] = $edad;
            }
            
            if (!empty($_POST['peso_kg']) && !empty($_POST['talla_cm'])) {
                $peso = floatval($_POST['peso_kg']);
                $talla = floatval($_POST['talla_cm']) / 100;
                if ($talla > 0) {
                    $_POST['imc'] = $peso / ($talla * $talla);
                }
            }
            
            // Validar campos para lactante/gestante
            if (isset($_POST['genero']) && $_POST['genero'] === 'F') {
                $condicion_femenina = $_POST['condicion_femenina'] ?? 'nada';
                
                if ($condicion_femenina === 'lactante' && empty($_POST['fecha_nacimiento_bebe'])) {
                    throw new Exception("Para beneficiaria lactante, debe ingresar la fecha de nacimiento del bebé");
                }
                
                if ($condicion_femenina === 'gestante') {
                    if (empty($_POST['semanas_gestacion'])) {
                        throw new Exception("Para beneficiaria gestante, debe ingresar las semanas de gestación");
                    }
                    
                    $semanas = intval($_POST['semanas_gestacion']);
                    if ($semanas < 1 || $semanas > 42) {
                        throw new Exception("Las semanas de gestación deben estar entre 1 y 42");
                    }
                }
            } else {
                $_POST['condicion_femenina'] = 'nada';
                $_POST['fecha_nacimiento_bebe'] = null;
                $_POST['semanas_gestacion'] = null;
            }
            
            $success = $this->model->createNuevoIngreso($_POST);
            
            if ($success) {
                $_SESSION['message'] = "✅ Nuevo ingreso registrado exitosamente. Se encuentra en la pestaña 'Nuevos Ingresos'";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Error al registrar nuevo ingreso");
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function deleteBeneficiario() {
        try {
            $cedula = $_POST['cedula_beneficiario'] ?? '';
            
            if (empty($cedula)) {
                throw new Exception("Cédula no proporcionada");
            }
            
            $success = $this->model->deleteBeneficiario($cedula);
            
            if ($success) {
                $_SESSION['message'] = "✅ Beneficiario marcado como inactivo";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Error al eliminar beneficiario");
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function deleteNuevoIngreso() {
        try {
            $cedula = $_POST['cedula_beneficiario'] ?? '';
            
            if (empty($cedula)) {
                throw new Exception("Cédula no proporcionada");
            }
            
            $success = $this->model->deleteNuevoIngreso($cedula);
            
            if ($success) {
                $_SESSION['message'] = "✅ Registro eliminado exitosamente";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Error al eliminar registro");
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function incorporarNuevoIngreso() {
        try {
            $cedula = $_POST['cedula_beneficiario'] ?? '';
            
            if (empty($cedula)) {
                throw new Exception("Cédula no proporcionada");
            }
            
            $success = $this->model->incorporarNuevoIngreso($cedula);
            
            if ($success) {
                $_SESSION['message'] = "✅ Beneficiario incorporado a la base de datos principal";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Error al incorporar beneficiario");
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    private function incorporarTodos() {
        try {
            $contador = $this->model->incorporarTodos();
            
            if ($contador > 0) {
                $_SESSION['message'] = "✅ $contador beneficiarios incorporados exitosamente";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "ℹ️ No hay nuevos ingresos para incorporar";
                $_SESSION['message_type'] = 'info';
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = "❌ " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    public function getAllBeneficiariosFormatted() {
        $beneficiarios = $this->model->getAllBeneficiarios();
        return $beneficiarios;
    }
    
    public function getNuevosIngresosFormatted() {
        $nuevos_ingresos = $this->model->getAllNuevosIngresos();
        
        $formatted = [];
        foreach ($nuevos_ingresos as $ingreso) {
            // Determinar condición femenina
            $condicion = '';
            if ($ingreso['genero'] === 'F') {
                if ($ingreso['lactante'] == 1) {
                    $condicion = 'Lactante';
                    if (!empty($ingreso['fecha_nac_lactante'])) {
                        $fecha_bebe = new DateTime($ingreso['fecha_nac_lactante']);
                        $condicion .= ' (Bebé nacido: ' . $fecha_bebe->format('d/m/Y') . ')';
                    }
                } elseif ($ingreso['gestante'] == 1) {
                    $condicion = 'Gestante (' . ($ingreso['semanas_gestacion'] ?? '0') . ' semanas)';
                } else {
                    $condicion = 'Ninguna condición especial';
                }
            }
            
            $formatted[] = [
                'cedula_beneficiario' => $ingreso['cedula_beneficiario'],
                'nombre_beneficiario' => $ingreso['nombre_beneficiario'],
                'apellido_beneficiario' => $ingreso['apellido_beneficiario'],
                'fecha_nacimiento' => $ingreso['fecha_nacimiento'],
                'edad' => $ingreso['edad'] ?? $this->calcularEdad($ingreso['fecha_nacimiento']),
                'genero' => $ingreso['genero'],
                'peso' => $ingreso['peso_kg'] ?? '',
                'talla' => $ingreso['talla_cm'] ?? '',
                'cbi' => $ingreso['cbi_mm'] ?? '',
                'imc' => $ingreso['imc'] ?? '',
                'situacion_dx' => $ingreso['situacion_dx'],
                'condicion_femenina' => $condicion,
                'municipio' => $ingreso['municipio'] ?? '',
                'parroquia' => $ingreso['parroquia'] ?? '',
                'sector' => $ingreso['sector'] ?? '',
                'fecha_registro' => $ingreso['fecha_registro']
            ];
        }
        
        return $formatted;
    }
    
    public function getFiltrosData() {
        return $this->model->getFiltrosData();
    }
    
    private function calcularEdad($fecha_nacimiento) {
        if (empty($fecha_nacimiento)) return '';
        
        try {
            $nacimiento = new DateTime($fecha_nacimiento);
            $hoy = new DateTime();
            return $hoy->diff($nacimiento)->y;
        } catch (Exception $e) {
            return '';
        }
    }
    
    // Método auxiliar para obtener nombre legible de los campos
    private function getFieldName($field) {
        $names = [
            'beneficiario_id' => 'ID del Beneficiario',
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'cedula_beneficiario' => 'Cédula del Beneficiario',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'genero' => 'Género',
            'municipio' => 'Municipio',
            'parroquia' => 'Parroquia',
            'sector' => 'Sector',
            'representante_nombre' => 'Nombre del Representante',
            'representante_apellido' => 'Apellido del Representante',
            'representante_cedula' => 'Cédula del Representante',
            'representante_telefono' => 'Teléfono del Representante',
            'cbi_mm' => 'CBI (mm)',
            'peso_kg' => 'Peso (kg)',
            'talla_cm' => 'Talla (cm)',
            'situacion_dx' => 'Situación/DX'
        ];
        
        return $names[$field] ?? $field;
    }
}
?>