<?php
// ProcesarNuevoIngreso.php

session_start();
include_once '../../config/database.php';

class ProcesadorNuevoIngreso {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function procesar() {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // 1. VALIDAR DATOS REQUERIDOS
            $required_fields = [
                'municipio', 'parroquia', 'sector',
                'nombre_representante', 'apellido_representante', 'cedula_representante', 'telefono_representante',
                'nombre_beneficiario', 'apellido_beneficiario', 'cedula_beneficiario',
                'fecha_nacimiento', 'genero', 'cbi_mm', 'peso_kg', 'talla_cm', 'caso'
            ];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo <strong>$field</strong> es requerido");
                }
            }
            
            $cedulaBeneficiario = trim($_POST['cedula_beneficiario']);
            $cedulaRepresentante = trim($_POST['cedula_representante']);
            
            // 2. VALIDAR QUE NO EXISTA EN TABLA BENEFICIARIO
            $query = "SELECT id_beneficiario FROM beneficiario WHERE cedula_beneficiario = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$cedulaBeneficiario]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("El beneficiario con cédula <strong>$cedulaBeneficiario</strong> ya existe en la base de datos principal");
            }
            
            // 3. CALCULAR IMC
            $peso = floatval($_POST['peso_kg']);
            $talla = floatval($_POST['talla_cm']);
            
            if ($talla <= 0) {
                throw new Exception("La talla debe ser mayor a cero");
            }
            
            $talla_m = $talla / 100;
            $imc = round($peso / ($talla_m * $talla_m), 2);
            
            // 4. CALCULAR EDAD
            $fecha_nacimiento = new DateTime($_POST['fecha_nacimiento']);
            $hoy = new DateTime();
            
            if ($fecha_nacimiento > $hoy) {
                throw new Exception("La fecha de nacimiento no puede ser mayor a la fecha actual");
            }
            
            $edad = $fecha_nacimiento->diff($hoy)->y;
            
            // 5. DETERMINAR DIAGNÓSTICO (situacion_dx)
            $situacion_dx = $this->determinarDiagnosticoIMC($imc, $edad);
            
            // 6. STATUS - Por defecto será PENDIENTE según el ENUM
            $status = 'PENDIENTE'; // Esto coincide con tu ENUM
            
            // 7. INSERTAR UBICACIÓN
            $ubicacion_id = $this->insertarUbicacion([
                'municipio' => trim($_POST['municipio']),
                'parroquia' => trim($_POST['parroquia']),
                'sector' => trim($_POST['sector'])
            ]);
            
            // 8. INSERTAR REPRESENTANTE
            $representante_id = $this->insertarRepresentante([
                'cedula' => $cedulaRepresentante,
                'nombres' => trim($_POST['nombre_representante']),
                'apellidos' => trim($_POST['apellido_representante']),
                'telefono' => trim($_POST['telefono_representante'])
            ]);
            
            // 9. INSERTAR EN TABLA BENEFICIARIO
            $this->insertarBeneficiario([
                'cedula_beneficiario' => $cedulaBeneficiario,
                'nombres' => trim($_POST['nombre_beneficiario']),
                'apellidos' => trim($_POST['apellido_beneficiario']),
                'genero' => $_POST['genero'],
                'fecha_nacimiento' => $_POST['fecha_nacimiento'],
                'edad' => $edad,
                'imc' => $imc,
                'cbi_mm' => floatval($_POST['cbi_mm']),
                'peso_kg' => $peso,
                'talla_cm' => $talla,
                'situacion_dx' => $situacion_dx,
                'lactando' => ($_POST['lactante'] ?? 0) == 1 ? 'SI' : 'NO',
                'gestante' => ($_POST['gestante'] ?? 0) == 1 ? 'SI' : 'NO',
                'id_representante' => $representante_id,
                'id_ubicacion' => $ubicacion_id,
                'status' => $status
            ]);
            
            // 10. CONFIRMAR TRANSACCIÓN
            $this->db->commit();
            
            $_SESSION['message'] = "✅ Nuevo ingreso registrado exitosamente. Status: PENDIENTE";
            $_SESSION['message_type'] = 'success';
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $_SESSION['message'] = "❌ Error: " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
        
        // Redirigir a la página de beneficiarios
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    private function determinarDiagnosticoIMC($imc, $edad) {
        // Clasificación según OMS para adultos
        if ($edad >= 18) {
            if ($imc < 16) return 'DELGADEZ SEVERA';
            if ($imc < 17) return 'DELGADEZ MODERADA';
            if ($imc < 18.5) return 'DELGADEZ LEVE';
            if ($imc < 25) return 'NORMAL';
            if ($imc < 30) return 'SOBREPESO';
            if ($imc < 35) return 'OBESIDAD TIPO I';
            if ($imc < 40) return 'OBESIDAD TIPO II';
            return 'OBESIDAD TIPO III';
        }
        
        // Para niños/adolescentes (0-17 años)
        if ($edad < 5) {
            if ($imc < 14) return 'DESNUTRICIÓN SEVERA';
            if ($imc < 15) return 'DESNUTRICIÓN MODERADA';
            if ($imc < 16) return 'DESNUTRICIÓN LEVE';
            if ($imc < 18) return 'NORMAL';
            if ($imc < 20) return 'SOBREPESO';
            return 'OBESIDAD';
        } else {
            if ($imc < 16) return 'DELGADEZ SEVERA';
            if ($imc < 17) return 'DELGADEZ MODERADA';
            if ($imc < 18.5) return 'DELGADEZ LEVE';
            if ($imc < 25) return 'NORMAL';
            if ($imc < 30) return 'SOBREPESO';
            return 'OBESIDAD';
        }
    }
    
    private function insertarUbicacion($datos) {
        // Verificar si ya existe la ubicación
        $query = "SELECT id_ubicacion FROM ubicacion 
                 WHERE municipio = ? AND parroquia = ? AND sector = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $datos['municipio'],
            $datos['parroquia'],
            $datos['sector']
        ]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['id_ubicacion'];
        }
        
        // Insertar nueva ubicación
        $query = "INSERT INTO ubicacion (estado, municipio, parroquia, sector, fecha_registro) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        
        // Asumimos que siempre es el mismo estado (ajustar según tu configuración)
        $estado = "Bolívar";
        
        $stmt->execute([
            $estado,
            $datos['municipio'],
            $datos['parroquia'],
            $datos['sector']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function insertarRepresentante($datos) {
        // Verificar si ya existe el representante
        $query = "SELECT id_representante FROM representante WHERE cedula_representante = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$datos['cedula']]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['id_representante'];
        }
        
        // Insertar nuevo representante
        $query = "INSERT INTO representante (cedula_representante, nombres, apellidos, numero_contacto, fecha_registro) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $datos['cedula'],
            $datos['nombres'],
            $datos['apellidos'],
            $datos['telefono']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function insertarBeneficiario($datos) {
        $query = "INSERT INTO beneficiario (
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
                    semanas_gestacion,
                    id_representante,
                    id_ubicacion,
                    status,
                    fecha_registro
                 ) VALUES (
                    :cedula_beneficiario,
                    :nombres,
                    :apellidos,
                    :genero,
                    :fecha_nacimiento,
                    :edad,
                    :imc,
                    :cbi_mm,
                    :cci_cintura,
                    :peso_kg,
                    :talla_cm,
                    :situacion_dx,
                    :lactando,
                    :fecha_nac_lactante,
                    :gestante,
                    :semanas_gestacion,
                    :id_representante,
                    :id_ubicacion,
                    :status,
                    NOW()
                 )";
        
        $stmt = $this->db->prepare($query);
        
        // Preparar parámetros
        $params = [
            ':cedula_beneficiario' => $datos['cedula_beneficiario'],
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':genero' => $datos['genero'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':edad' => $datos['edad'],
            ':imc' => $datos['imc'],
            ':cbi_mm' => $datos['cbi_mm'],
            ':cci_cintura' => !empty($_POST['cci_cintura']) ? floatval($_POST['cci_cintura']) : null,
            ':peso_kg' => $datos['peso_kg'],
            ':talla_cm' => $datos['talla_cm'],
            ':situacion_dx' => $datos['situacion_dx'],
            ':lactando' => $datos['lactando'],
            ':fecha_nac_lactante' => (!empty($_POST['fecha_nacimiento_bebe']) && $datos['lactando'] == 'SI') ? $_POST['fecha_nacimiento_bebe'] : null,
            ':gestante' => $datos['gestante'],
            ':semanas_gestacion' => (!empty($_POST['semanas_gestacion']) && $datos['gestante'] == 'SI') ? intval($_POST['semanas_gestacion']) : null,
            ':id_representante' => $datos['id_representante'],
            ':id_ubicacion' => $datos['id_ubicacion'],
            ':status' => $datos['status']
        ];
        
        return $stmt->execute($params);
    }
    
    // Función para obtener los nuevos ingresos (status = PENDIENTE)
    public function obtenerNuevosIngresos() {
        $query = "SELECT 
                    b.id_beneficiario,
                    b.cedula_beneficiario,
                    b.nombres as nombre_beneficiario,
                    b.apellidos as apellido_beneficiario,
                    b.genero,
                    b.fecha_nacimiento,
                    b.edad,
                    b.imc,
                    b.cbi_mm,
                    b.cci_cintura,
                    b.peso_kg,
                    b.talla_cm,
                    b.situacion_dx,
                    b.lactando,
                    b.gestante,
                    b.semanas_gestacion,
                    b.status,
                    b.fecha_registro,
                    
                    r.cedula_representante,
                    r.nombres as nombre_representante,
                    r.apellidos as apellido_representante,
                    r.numero_contacto,
                    
                    u.municipio,
                    u.parroquia,
                    u.sector
                  
                  FROM beneficiario b
                  INNER JOIN representante r ON b.id_representante = r.id_representante
                  INNER JOIN ubicacion u ON b.id_ubicacion = u.id_ubicacion
                  WHERE b.status = 'PENDIENTE'
                  ORDER BY b.fecha_registro DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Función para cambiar status de PENDIENTE a ACTIVO
    public function activarBeneficiario($cedula) {
        try {
            $query = "UPDATE beneficiario 
                     SET status = 'ACTIVO', fecha_actualizacion = NOW()
                     WHERE cedula_beneficiario = ? AND status = 'PENDIENTE'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$cedula]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            throw new Exception("Error al activar beneficiario: " . $e->getMessage());
        }
    }
    
    // Función para marcar como INACTIVO
    public function desactivarBeneficiario($cedula) {
        try {
            $query = "UPDATE beneficiario 
                     SET status = 'INACTIVO', fecha_actualizacion = NOW()
                     WHERE cedula_beneficiario = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$cedula]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            throw new Exception("Error al desactivar beneficiario: " . $e->getMessage());
        }
    }
}

// Uso del archivo - EJECUCIÓN PRINCIPAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $database = new Database();
    $db = $database->getConnection();
    
    $procesador = new ProcesadorNuevoIngreso($db);
    
    switch ($_POST['action']) {
        case 'create_nuevo_ingreso':
            $procesador->procesar();
            break;
            
        case 'activar_beneficiario':
            if (!empty($_POST['cedula'])) {
                try {
                    if ($procesador->activarBeneficiario($_POST['cedula'])) {
                        $_SESSION['message'] = "✅ Beneficiario activado exitosamente.";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "⚠️ No se pudo activar el beneficiario. Verifica que esté en estado PENDIENTE.";
                        $_SESSION['message_type'] = 'warning';
                    }
                } catch (Exception $e) {
                    $_SESSION['message'] = "❌ Error: " . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit();
            }
            break;
            
        case 'desactivar_beneficiario':
            if (!empty($_POST['cedula'])) {
                try {
                    if ($procesador->desactivarBeneficiario($_POST['cedula'])) {
                        $_SESSION['message'] = "✅ Beneficiario marcado como INACTIVO.";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "⚠️ No se pudo desactivar el beneficiario.";
                        $_SESSION['message_type'] = 'warning';
                    }
                } catch (Exception $e) {
                    $_SESSION['message'] = "❌ Error: " . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit();
            }
            break;
    }
} else {
    // Redirigir si se accede directamente sin POST
    header("Location: beneficiarios.php");
    exit();
}
?>