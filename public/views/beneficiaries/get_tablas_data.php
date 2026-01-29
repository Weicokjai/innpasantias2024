<?php
// get_tablas_data.php
session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Incluir dependencias
require_once '../../config/database.php';
require_once '../../components/beneficiaries/BeneficiarioModel.php';
require_once '../../components/beneficiaries/BeneficiarioController.php';

// Headers para JSON
header('Content-Type: application/json');

try {
    // Inicializar conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Crear controlador
    $controller = new BeneficiarioController($db);
    
    // Obtener el tipo de datos solicitado
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'beneficiarios';
    
    $response = [];
    
    switch ($tipo) {
        case 'beneficiarios':
            $data = $controller->getAllBeneficiariosFormatted();
            $response['data'] = $data;
            $response['total'] = count($data);
            break;
            
        case 'nuevos-ingresos':
            $data = $controller->getNuevosIngresosFormatted();
            $response['data'] = $data;
            $response['total'] = count($data);
            break;
            
        case 'filtros':
            $filtrosData = $controller->getFiltrosData();
            $response = $filtrosData;
            break;
            
        default:
            $response = ['error' => 'Tipo de datos no válido'];
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error en el servidor',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>