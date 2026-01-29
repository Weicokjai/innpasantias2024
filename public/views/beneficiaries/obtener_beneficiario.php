<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rutas absolutas
$base_dir = dirname(__FILE__, 2);
require_once $base_dir . '/config/database.php';
require_once $base_dir . '/components/beneficiaries/BeneficiarioModel.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $model = new BeneficiarioModel($db);
    
    // Verificar que sea una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    $cedula = $_POST['cedula_beneficiario'] ?? '';
    
    if (empty($action) || empty($cedula)) {
        echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes']);
        exit;
    }
    
    if ($action !== 'get_beneficiario') {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit;
    }
    
    // Obtener datos del beneficiario
    $beneficiario = $model->getBeneficiarioByCedula($cedula);
    
    if (!$beneficiario) {
        echo json_encode(['success' => false, 'message' => 'Beneficiario no encontrado']);
        exit;
    }
    
    // Procesar datos para el formulario
    $datos_formateados = [
        'id_beneficiario' => $beneficiario['id_beneficiario'] ?? '',
        'cedula_beneficiario' => $beneficiario['cedula_beneficiario'] ?? '',
        'nombres' => $beneficiario['nombres'] ?? $beneficiario['nombre_beneficiario'] ?? '',
        'apellidos' => $beneficiario['apellidos'] ?? $beneficiario['apellido_beneficiario'] ?? '',
        'genero' => $beneficiario['genero'] ?? '',
        'fecha_nacimiento' => $beneficiario['fecha_nacimiento'] ?? '',
        'edad' => $beneficiario['edad'] ?? 0,
        
        // Antropometría
        'cbi_mm' => $beneficiario['cbi_mm'] ?? 0,
        'peso_kg' => $beneficiario['peso_kg'] ?? 0,
        'talla_cm' => $beneficiario['talla_cm'] ?? 0,
        'imc' => $beneficiario['imc'] ?? 0,
        'situacion_dx' => $beneficiario['situacion_dx'] ?? '',
        
        // Representante
        'representante_cedula' => $beneficiario['cedula_representante'] ?? '',
        'representante_nombre' => $beneficiario['nombre_representante'] ?? $beneficiario['nombres_representante'] ?? '',
        'representante_apellido' => $beneficiario['apellido_representante'] ?? $beneficiario['apellidos_representante'] ?? '',
        'representante_telefono' => $beneficiario['numero_contacto'] ?? $beneficiario['telefono_representante'] ?? '',
        
        // Ubicación
        'municipio' => $beneficiario['municipio'] ?? '',
        'parroquia' => $beneficiario['parroquia'] ?? '',
        'sector' => $beneficiario['sector'] ?? '',
        'nombre_clap' => $beneficiario['nombre_clap'] ?? '',
        'nombre_comuna' => $beneficiario['nombre_comuna'] ?? '',
        
        // Condición femenina
        'lactante' => $beneficiario['lactante'] ?? 0,
        'fecha_nac_lactante' => $beneficiario['fecha_nac_lactante'] ?? '',
        'gestante' => $beneficiario['gestante'] ?? 0,
        'semanas_gestacion' => $beneficiario['semanas_gestacion'] ?? 0
    ];
    
    // Determinar condición femenina
    $genero = strtoupper($datos_formateados['genero']);
    if ($genero === 'FEMENINO' || $genero === 'F') {
        if ($datos_formateados['lactante'] == 1) {
            $datos_formateados['condicion_femenina'] = 'lactante';
        } elseif ($datos_formateados['gestante'] == 1) {
            $datos_formateados['condicion_femenina'] = 'gestante';
        } else {
            $datos_formateados['condicion_femenina'] = 'nada';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $datos_formateados
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>