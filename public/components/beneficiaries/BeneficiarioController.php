<?php
class BeneficiarioController {
    private $beneficiarioModel;

    public function __construct($db) {
        $this->beneficiarioModel = new BeneficiarioModel($db);
    }

    public function handleRequest() {
        if ($_POST) {
            $action = $_POST['action'] ?? '';
            
            switch($action) {
                case 'create':
                    $this->createBeneficiario();
                    break;
                case 'update':
                    $this->updateBeneficiario();
                    break;
                case 'delete':
                    $this->deleteBeneficiario();
                    break;
            }
        }
    }

    private function createBeneficiario() {
        $data = [
            'cedula_beneficiario' => $_POST['cedula_beneficiario'],
            'nombres' => $_POST['nombres'],
            'apellidos' => $_POST['apellidos'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'edad' => $_POST['edad'],
            'peso_kg' => $_POST['peso_kg'],
            'talla_cm' => $_POST['talla_cm'],
            'cbi_mm' => $_POST['cbi_mm'],
            'imc' => $_POST['imc'],
            'situacion_dx' => $_POST['situacion_dx'],
            'genero' => $_POST['genero']
        ];
        
        if ($this->beneficiarioModel->createBeneficiario($data)) {
            $_SESSION['message'] = 'Beneficiario creado exitosamente';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error al crear beneficiario';
            $_SESSION['message_type'] = 'error';
        }
    }

    private function updateBeneficiario() {
        $data = [
            'cedula_beneficiario' => $_POST['cedula_beneficiario'],
            'nombres' => $_POST['nombres'],
            'apellidos' => $_POST['apellidos'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'edad' => $_POST['edad'],
            'peso_kg' => $_POST['peso_kg'],
            'talla_cm' => $_POST['talla_cm'],
            'cbi_mm' => $_POST['cbi_mm'],
            'imc' => $_POST['imc'],
            'situacion_dx' => $_POST['situacion_dx'],
            'genero' => $_POST['genero']
        ];
        
        if ($this->beneficiarioModel->updateBeneficiario($data)) {
            $_SESSION['message'] = 'Beneficiario actualizado exitosamente';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error al actualizar beneficiario';
            $_SESSION['message_type'] = 'error';
        }
    }

    private function deleteBeneficiario() {
        $cedula = $_POST['cedula_beneficiario'];
        if ($this->beneficiarioModel->deleteBeneficiario($cedula)) {
            $_SESSION['message'] = 'Beneficiario eliminado exitosamente';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error al eliminar beneficiario';
            $_SESSION['message_type'] = 'error';
        }
    }

    public function getAllBeneficiariosFormatted() {
        $beneficiarios_data = $this->beneficiarioModel->getAllBeneficiarios();
        $beneficiarios = [];

        foreach($beneficiarios_data as $benef) {
            // Formatear edad
            if (empty($benef['edad']) && !empty($benef['fecha_nacimiento'])) {
                $fechaNacimiento = new DateTime($benef['fecha_nacimiento']);
                $hoy = new DateTime();
                $edad = $hoy->diff($fechaNacimiento)->y;
                $benef['edad'] = $edad . ' años';
            } elseif (!empty($benef['edad'])) {
                $benef['edad'] = $benef['edad'] . ' años';
            } else {
                $benef['edad'] = 'No especificada';
            }
            
            // Formatear medidas antopometricas
            $benef['peso'] = !empty($benef['peso']) ? number_format($benef['peso'], 1) . ' kg' : 'No registrado';
            $benef['talla'] = !empty($benef['talla']) ? number_format($benef['talla'] / 100, 2) . ' m' : 'No registrado';
            $benef['cbi'] = !empty($benef['cbi']) ? number_format($benef['cbi'], 1) . ' cm' : 'No registrado';
            $benef['imc'] = !empty($benef['imc']) ? number_format($benef['imc'], 1) : 'No calculado';
            
            // Valores por defecto
            $benef['municipio'] = empty($benef['municipio']) ? 'PALAVECINO' : $benef['municipio'];
            $benef['parroquia'] = empty($benef['parroquia']) ? 'AGUA VIVA' : $benef['parroquia'];
            $benef['sector'] = empty($benef['sector']) ? 'SIN ESPECIFICAR' : $benef['sector'];
            $benef['nombre_representante'] = empty($benef['nombre_representante']) ? 'NO REGISTRADO' : $benef['nombre_representante'];
            $benef['cedula_representante'] = empty($benef['cedula_representante']) ? 'NO REGISTRADO' : $benef['cedula_representante'];
            
            // ID único
            $benef['id'] = crc32($benef['cedula_beneficiario']);
            
            $beneficiarios[] = $benef;
        }

        return $beneficiarios;
    }
}
?>