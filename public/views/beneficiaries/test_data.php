<?php
// test_data.php - Para probar que los datos se obtienen correctamente
session_start();

// Incluir dependencias
require_once '../../config/database.php';
require_once '../../components/beneficiaries/BeneficiarioModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $model = new BeneficiarioModel($db);
    
    echo "<h2>Probando conexión y datos...</h2>";
    
    // 1. Probar conexión
    echo "<h3>1. Conexión a la base de datos:</h3>";
    echo $db ? "✅ Conexión establecida<br>" : "❌ Error en conexión<br>";
    
    // 2. Probar consulta directa
    echo "<h3>2. Consulta directa de beneficiarios:</h3>";
    $query = "SELECT COUNT(*) as total FROM beneficiarios WHERE status = 'ACTIVO'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total beneficiarios ACTIVOS: " . ($result['total'] ?? 0) . "<br>";
    
    // 3. Probar método del modelo
    echo "<h3>3. Método getAllBeneficiarios():</h3>";
    $beneficiarios = $model->getAllBeneficiarios();
    echo "Registros obtenidos: " . count($beneficiarios) . "<br>";
    
    if (!empty($beneficiarios)) {
        echo "<h4>Primer beneficiario:</h4>";
        echo "<pre>";
        print_r($beneficiarios[0]);
        echo "</pre>";
    }
    
    // 4. Probar método formateado
    echo "<h3>4. Método getAllBeneficiariosFormatted():</h3>";
    $formatted = $model->getAllBeneficiariosFormatted();
    echo "Registros formateados: " . count($formatted) . "<br>";
    
    if (!empty($formatted)) {
        echo "<h4>Primer beneficiario formateado:</h4>";
        echo "<pre>";
        print_r($formatted[0]);
        echo "</pre>";
    }
    
    // 5. Probar nuevos ingresos
    echo "<h3>5. Método getNuevosIngresos():</h3>";
    $nuevosIngresos = $model->getNuevosIngresos();
    echo "Nuevos ingresos PENDIENTES: " . count($nuevosIngresos) . "<br>";
    
    // 6. Probar municipios
    echo "<h3>6. Método getMunicipios():</h3>";
    $municipios = $model->getMunicipios();
    echo "Municipios encontrados: " . count($municipios) . "<br>";
    if (!empty($municipios)) {
        echo "Municipios: " . implode(', ', $municipios) . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>