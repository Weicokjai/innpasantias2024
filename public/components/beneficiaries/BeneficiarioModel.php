<?php
class BeneficiarioModel {
    private $conn;
    private $table_name = "beneficiario";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllBeneficiarios() {
        $query = "
            SELECT 
                b.cedula_beneficiario,
                b.nombres as nombre_beneficiario,
                b.apellidos as apellido_beneficiario,
                b.fecha_nacimiento,
                b.edad,
                b.peso_kg as peso,
                b.talla_cm as talla,
                b.cbi_mm as cbi,
                b.imc,
                b.situacion_dx as caso,
                'activo' as estado,
                r.cedula_representante,
                CONCAT(r.nombres, ' ', r.apellidos) as nombre_representante,
                u.municipio,
                u.parroquia,
                u.sector
            FROM beneficiario b
            LEFT JOIN procura_entrega pe ON b.cedula_beneficiario = pe.fk_cedula_beneficiario
            LEFT JOIN representante r ON pe.fk_cedula_representante = r.cedula_representante
            LEFT JOIN ubicacion u ON pe.fk_id_ubicacion = u.id_ubicacion
            WHERE b.cedula_beneficiario IS NOT NULL 
            AND b.cedula_beneficiario != ''
            GROUP BY b.cedula_beneficiario
            ORDER BY b.nombres ASC
            LIMIT 100
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBeneficiario($data) {
        $query = "INSERT INTO beneficiario SET 
            cedula_beneficiario=:cedula_beneficiario,
            nombres=:nombres,
            apellidos=:apellidos,
            fecha_nacimiento=:fecha_nacimiento,
            edad=:edad,
            peso_kg=:peso_kg,
            talla_cm=:talla_cm,
            cbi_mm=:cbi_mm,
            imc=:imc,
            situacion_dx=:situacion_dx,
            genero=:genero";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $cedula_beneficiario = htmlspecialchars(strip_tags($data['cedula_beneficiario']));
        $nombres = htmlspecialchars(strip_tags($data['nombres']));
        $apellidos = htmlspecialchars(strip_tags($data['apellidos']));
        $fecha_nacimiento = htmlspecialchars(strip_tags($data['fecha_nacimiento']));
        $edad = htmlspecialchars(strip_tags($data['edad']));
        $peso_kg = htmlspecialchars(strip_tags($data['peso_kg']));
        $talla_cm = htmlspecialchars(strip_tags($data['talla_cm']));
        $cbi_mm = htmlspecialchars(strip_tags($data['cbi_mm']));
        $imc = htmlspecialchars(strip_tags($data['imc']));
        $situacion_dx = htmlspecialchars(strip_tags($data['situacion_dx']));
        $genero = htmlspecialchars(strip_tags($data['genero']));

        // Bind parameters
        $stmt->bindParam(":cedula_beneficiario", $cedula_beneficiario);
        $stmt->bindParam(":nombres", $nombres);
        $stmt->bindParam(":apellidos", $apellidos);
        $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
        $stmt->bindParam(":edad", $edad);
        $stmt->bindParam(":peso_kg", $peso_kg);
        $stmt->bindParam(":talla_cm", $talla_cm);
        $stmt->bindParam(":cbi_mm", $cbi_mm);
        $stmt->bindParam(":imc", $imc);
        $stmt->bindParam(":situacion_dx", $situacion_dx);
        $stmt->bindParam(":genero", $genero);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateBeneficiario($data) {
        $query = "UPDATE beneficiario SET 
            nombres=:nombres,
            apellidos=:apellidos,
            fecha_nacimiento=:fecha_nacimiento,
            edad=:edad,
            peso_kg=:peso_kg,
            talla_cm=:talla_cm,
            cbi_mm=:cbi_mm,
            imc=:imc,
            situacion_dx=:situacion_dx,
            genero=:genero
            WHERE cedula_beneficiario=:cedula_beneficiario";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $cedula_beneficiario = htmlspecialchars(strip_tags($data['cedula_beneficiario']));
        $nombres = htmlspecialchars(strip_tags($data['nombres']));
        $apellidos = htmlspecialchars(strip_tags($data['apellidos']));
        $fecha_nacimiento = htmlspecialchars(strip_tags($data['fecha_nacimiento']));
        $edad = htmlspecialchars(strip_tags($data['edad']));
        $peso_kg = htmlspecialchars(strip_tags($data['peso_kg']));
        $talla_cm = htmlspecialchars(strip_tags($data['talla_cm']));
        $cbi_mm = htmlspecialchars(strip_tags($data['cbi_mm']));
        $imc = htmlspecialchars(strip_tags($data['imc']));
        $situacion_dx = htmlspecialchars(strip_tags($data['situacion_dx']));
        $genero = htmlspecialchars(strip_tags($data['genero']));

        // Bind parameters
        $stmt->bindParam(":cedula_beneficiario", $cedula_beneficiario);
        $stmt->bindParam(":nombres", $nombres);
        $stmt->bindParam(":apellidos", $apellidos);
        $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
        $stmt->bindParam(":edad", $edad);
        $stmt->bindParam(":peso_kg", $peso_kg);
        $stmt->bindParam(":talla_cm", $talla_cm);
        $stmt->bindParam(":cbi_mm", $cbi_mm);
        $stmt->bindParam(":imc", $imc);
        $stmt->bindParam(":situacion_dx", $situacion_dx);
        $stmt->bindParam(":genero", $genero);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function deleteBeneficiario($cedula) {
        $query = "DELETE FROM beneficiario WHERE cedula_beneficiario = :cedula";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cedula", $cedula);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getBeneficiarioByCedula($cedula) {
        $query = "SELECT * FROM beneficiario WHERE cedula_beneficiario = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cedula);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>