<?php
class BeneficiarioModel {
    private $conn;
    private $table_beneficiario = "beneficiario";
    private $table_representante = "representante";
    private $table_ubicacion = "ubicacion";

    public function __construct($db) {
        $this->conn = $db;
        $this->crearTablaConCamposAdicionales();
    }

    private function crearTablaConCamposAdicionales() {
        $this->createNuevosIngresosTable();
    }

    private function createNuevosIngresosTable() {
        $query = "CREATE TABLE IF NOT EXISTS nuevos_ingresos (
            id_nuevo_ingreso INT PRIMARY KEY AUTO_INCREMENT,
            cedula_beneficiario VARCHAR(20) NOT NULL,
            nombre_beneficiario VARCHAR(100) NOT NULL,
            apellido_beneficiario VARCHAR(100) NOT NULL,
            fecha_nacimiento DATE NOT NULL,
            genero ENUM('M', 'F') NOT NULL,
            edad INT,
            imc DECIMAL(5,2),
            cbi_mm DECIMAL(5,1),
            peso_kg DECIMAL(5,1),
            talla_cm DECIMAL(5,1),
            situacion_dx ENUM('1', '2') NOT NULL,
            
            cedula_representante VARCHAR(20),
            nombre_representante VARCHAR(100),
            apellido_representante VARCHAR(100),
            telefono_representante VARCHAR(20),
            
            municipio VARCHAR(100),
            parroquia VARCHAR(100),
            sector VARCHAR(100),
            nombre_clap VARCHAR(150),
            nombre_comuna VARCHAR(150),
            
            lactante BOOLEAN DEFAULT FALSE,
            fecha_nac_lactante DATE NULL,
            gestante BOOLEAN DEFAULT FALSE,
            semanas_gestacion INT NULL,
            
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            incorporado BOOLEAN DEFAULT FALSE,
            
            UNIQUE KEY idx_cedula_beneficiario (cedula_beneficiario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creando tabla nuevos_ingresos: " . $e->getMessage());
            return false;
        }
    }

    public function getMunicipios() {
        $query = "SELECT DISTINCT municipio FROM ubicacion WHERE municipio IS NOT NULL AND municipio != '' ORDER BY municipio";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getParroquiasPorMunicipio() {
        $query = "SELECT municipio, GROUP_CONCAT(DISTINCT parroquia ORDER BY parroquia) as parroquias 
                  FROM ubicacion 
                  WHERE municipio IS NOT NULL AND parroquia IS NOT NULL 
                  AND municipio != '' AND parroquia != ''
                  GROUP BY municipio 
                  ORDER BY municipio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['parroquias'])) {
                $result[$row['municipio']] = explode(',', $row['parroquias']);
            }
        }
        return $result;
    }

    public function getSectoresPorParroquia() {
        $query = "SELECT parroquia, GROUP_CONCAT(DISTINCT sector ORDER BY sector) as sectores 
                  FROM ubicacion 
                  WHERE parroquia IS NOT NULL AND sector IS NOT NULL 
                  AND parroquia != '' AND sector != ''
                  GROUP BY parroquia 
                  ORDER BY parroquia";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['sectores'])) {
                $result[$row['parroquia']] = explode(',', $row['sectores']);
            }
        }
        return $result;
    }

    public function getAllBeneficiarios() {
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
                    b.peso_kg,
                    b.talla_cm,
                    b.situacion_dx,
                    b.status,
                    b.fecha_registro,
                    b.lactando,
                    b.fecha_nac_lactante,
                    b.gestante,
                    b.semanas_gestacion,
                    b.lactante,
                    
                    r.cedula_representante,
                    r.nombres as nombre_representante,
                    r.apellidos as apellido_representante,
                    r.numero_contacto,
                    
                    u.municipio,
                    u.parroquia,
                    u.sector
                  
                  FROM {$this->table_beneficiario} b
                  LEFT JOIN {$this->table_representante} r ON b.id_representante = r.id_representante
                  LEFT JOIN {$this->table_ubicacion} u ON b.id_ubicacion = u.id_ubicacion
                  WHERE b.status = 'Activo' OR b.status IS NULL
                  ORDER BY b.fecha_registro DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAllBeneficiarios: " . $e->getMessage());
            return [];
        }
    }

    public function getBeneficiarioById($id) {
        $query = "SELECT 
                    b.*,
                    r.*,
                    u.*
                  
                  FROM {$this->table_beneficiario} b
                  LEFT JOIN {$this->table_representante} r ON b.id_representante = r.id_representante
                  LEFT JOIN {$this->table_ubicacion} u ON b.id_ubicacion = u.id_ubicacion
                  WHERE b.id_beneficiario = :id 
                  AND (b.status = 'Activo' OR b.status IS NULL)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function beneficiarioExists($id) {
        $query = "SELECT COUNT(*) FROM {$this->table_beneficiario} 
                 WHERE id_beneficiario = :id 
                 AND (status = 'Activo' OR status IS NULL)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetchColumn() > 0;
    }

    public function createBeneficiario($data) {
        try {
            $this->conn->beginTransaction();
            
            $lactando = 'NO';
            $gestante = 'NO';
            $lactante_tinyint = 0;
            $fecha_nac_lactante = null;
            $semanas_gestacion = null;
            
            $condicion_femenina = $data['condicion_femenina'] ?? 'nada';
            $genero_input = $data['genero'] ?? '';
            
            if ($genero_input == 'F' || $genero_input == 'FEMENINO') {
                if ($condicion_femenina == 'lactante') {
                    $lactando = 'SI';
                    $lactante_tinyint = 1;
                    $fecha_nac_lactante = !empty($data['fecha_nacimiento_bebe']) ? 
                                          $data['fecha_nacimiento_bebe'] : null;
                } elseif ($condicion_femenina == 'gestante') {
                    $gestante = 'SI';
                    $semanas_gestacion = !empty($data['semanas_gestacion']) ? 
                                        intval($data['semanas_gestacion']) : null;
                }
            }
            
            $genero_db = 'OTRO';
            if ($genero_input == 'M' || $genero_input == 'MASCULINO') {
                $genero_db = 'MASCULINO';
            } elseif ($genero_input == 'F' || $genero_input == 'FEMENINO') {
                $genero_db = 'FEMENINO';
            }
            
            $query_representante = "INSERT INTO {$this->table_representante} 
                (cedula_representante, nombres, apellidos, numero_contacto, fecha_registro)
                VALUES (:cedula_representante, :nombre_representante, :apellido_representante, 
                        :telefono_representante, NOW())
                ON DUPLICATE KEY UPDATE 
                nombres = VALUES(nombres),
                apellidos = VALUES(apellidos),
                numero_contacto = VALUES(numero_contacto)";
            
            $stmt_rep = $this->conn->prepare($query_representante);
            $stmt_rep->execute([
                ':cedula_representante' => $data['representante_cedula'] ?? '',
                ':nombre_representante' => $data['representante_nombre'] ?? '',
                ':apellido_representante' => $data['representante_apellido'] ?? '',
                ':telefono_representante' => $data['representante_telefono'] ?? ''
            ]);
            
            $query_get_rep_id = "SELECT id_representante FROM {$this->table_representante} 
                                WHERE cedula_representante = :cedula";
            $stmt_get_rep = $this->conn->prepare($query_get_rep_id);
            $stmt_get_rep->execute([':cedula' => $data['representante_cedula']]);
            $representante = $stmt_get_rep->fetch(PDO::FETCH_ASSOC);
            $id_representante = $representante['id_representante'] ?? null;
            
            $query_ubicacion = "INSERT INTO {$this->table_ubicacion} 
                (municipio, parroquia, sector, fecha_registro)
                VALUES (:municipio, :parroquia, :sector, NOW())
                ON DUPLICATE KEY UPDATE 
                municipio = VALUES(municipio),
                parroquia = VALUES(parroquia),
                sector = VALUES(sector)";
            
            $stmt_ubic = $this->conn->prepare($query_ubicacion);
            $stmt_ubic->execute([
                ':municipio' => $data['municipio'] ?? '',
                ':parroquia' => $data['parroquia'] ?? '',
                ':sector' => $data['sector'] ?? ''
            ]);
            
            $query_get_ubic_id = "SELECT id_ubicacion FROM {$this->table_ubicacion} 
                                 WHERE municipio = :municipio 
                                 AND parroquia = :parroquia 
                                 AND sector = :sector";
            $stmt_get_ubic = $this->conn->prepare($query_get_ubic_id);
            $stmt_get_ubic->execute([
                ':municipio' => $data['municipio'],
                ':parroquia' => $data['parroquia'],
                ':sector' => $data['sector']
            ]);
            $ubicacion = $stmt_get_ubic->fetch(PDO::FETCH_ASSOC);
            $id_ubicacion = $ubicacion['id_ubicacion'] ?? null;
            
            $query_beneficiario = "INSERT INTO {$this->table_beneficiario} 
                (cedula_beneficiario, nombres, apellidos, genero, fecha_nacimiento, edad,
                 imc, cbi_mm, peso_kg, talla_cm, situacion_dx,
                 lactando, fecha_nac_lactante, gestante, semanas_gestacion, lactante,
                 id_representante, id_ubicacion, status, fecha_registro)
                VALUES (:cedula_beneficiario, :nombres, :apellidos, :genero, :fecha_nacimiento, :edad,
                        :imc, :cbi_mm, :peso_kg, :talla_cm, :situacion_dx,
                        :lactando, :fecha_nac_lactante, :gestante, :semanas_gestacion, :lactante,
                        :id_representante, :id_ubicacion, 'Activo', NOW())";
            
            $stmt_ben = $this->conn->prepare($query_beneficiario);
            $success = $stmt_ben->execute([
                ':cedula_beneficiario' => $data['cedula_beneficiario'],
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':genero' => $genero_db,
                ':fecha_nacimiento' => $data['fecha_nacimiento'],
                ':edad' => $data['edad'] ?? 0,
                ':imc' => $data['imc'] ?? 0,
                ':cbi_mm' => $data['cbi_mm'] ?? 0,
                ':peso_kg' => $data['peso_kg'] ?? 0,
                ':talla_cm' => $data['talla_cm'] ?? 0,
                ':situacion_dx' => $data['situacion_dx'],
                ':lactando' => $lactando,
                ':fecha_nac_lactante' => $fecha_nac_lactante,
                ':gestante' => $gestante,
                ':semanas_gestacion' => $semanas_gestacion,
                ':lactante' => $lactante_tinyint,
                ':id_representante' => $id_representante,
                ':id_ubicacion' => $id_ubicacion
            ]);
            
            $this->conn->commit();
            return $success;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en createBeneficiario: " . $e->getMessage());
            throw new Exception("Error al crear beneficiario: " . $e->getMessage());
        }
    }

    public function updateBeneficiario($data) {
        try {
            error_log("=== MODELO updateBeneficiario INICIADO ===");
            error_log("Datos recibidos: " . print_r($data, true));
            
            $this->conn->beginTransaction();
            
            $beneficiario_id = $data['beneficiario_id'] ?? $data['id_beneficiario'] ?? null;
            
            if (empty($beneficiario_id)) {
                throw new Exception("ID de beneficiario no proporcionado");
            }
            
            error_log("ID del beneficiario a actualizar: $beneficiario_id");
            
            $genero_db = 'OTRO';
            $genero_input = $data['genero'] ?? '';
            
            if ($genero_input == 'M' || $genero_input == 'MASCULINO') {
                $genero_db = 'MASCULINO';
            } elseif ($genero_input == 'F' || $genero_input == 'FEMENINO') {
                $genero_db = 'FEMENINO';
            }
            
            error_log("Género convertido: $genero_input -> $genero_db");
            
            $lactando = 'NO';
            $gestante = 'NO';
            $lactante_tinyint = 0;
            $fecha_nac_lactante = null;
            $semanas_gestacion = null;
            
            $condicion_femenina = $data['condicion_femenina'] ?? 'nada';
            
            if ($genero_db == 'FEMENINO') {
                if ($condicion_femenina == 'lactante') {
                    $lactando = 'SI';
                    $lactante_tinyint = 1;
                    $fecha_nac_lactante = !empty($data['fecha_nacimiento_bebe']) ? 
                                          $data['fecha_nacimiento_bebe'] : null;
                    error_log("Lactante activado - fecha bebé: $fecha_nac_lactante");
                } elseif ($condicion_femenina == 'gestante') {
                    $gestante = 'SI';
                    $semanas_gestacion = !empty($data['semanas_gestacion']) ? 
                                        intval($data['semanas_gestacion']) : null;
                    error_log("Gestante activado - semanas: $semanas_gestacion");
                }
            }
            
            error_log("Estado final - Lactando: $lactando, Gestante: $gestante, Lactante_tinyint: $lactante_tinyint");
            
            if (!$this->beneficiarioExists($beneficiario_id)) {
                throw new Exception("El beneficiario con ID $beneficiario_id no existe");
            }
            
            // Obtener datos actuales
            $query_get_current = "SELECT 
                b.id_representante,
                b.id_ubicacion,
                r.cedula_representante as cedula_rep_actual
                FROM {$this->table_beneficiario} b
                LEFT JOIN {$this->table_representante} r ON b.id_representante = r.id_representante
                WHERE b.id_beneficiario = :id";
            
            $stmt_current = $this->conn->prepare($query_get_current);
            $stmt_current->execute([':id' => $beneficiario_id]);
            $current_data = $stmt_current->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_data) {
                throw new Exception("No se pudieron obtener los datos actuales");
            }
            
            $current_id_representante = $current_data['id_representante'] ?? null;
            $current_cedula_rep_actual = $current_data['cedula_rep_actual'] ?? '';
            $current_id_ubicacion = $current_data['id_ubicacion'] ?? null;
            
            error_log("ID Representante actual: $current_id_representante");
            error_log("Cédula Representante actual: $current_cedula_rep_actual");
            error_log("ID Ubicación actual: $current_id_ubicacion");
            
            // CORRECCIÓN: SIEMPRE ACTUALIZAR EL REPRESENTANTE, INCLUSO SI LA CÉDULA NO CAMBIA
            $cedula_nueva = $data['representante_cedula'] ?? '';
            
            if ($current_id_representante) {
                // ACTUALIZAR REPRESENTANTE EXISTENTE
                $query_update_rep = "UPDATE {$this->table_representante} SET 
                    cedula_representante = :cedula_representante,
                    nombres = :nombre_representante,
                    apellidos = :apellido_representante,
                    numero_contacto = :telefono_representante
                    WHERE id_representante = :id_representante";
                
                $stmt_rep = $this->conn->prepare($query_update_rep);
                $stmt_rep->execute([
                    ':id_representante' => $current_id_representante,
                    ':cedula_representante' => $cedula_nueva,
                    ':nombre_representante' => $data['representante_nombre'] ?? '',
                    ':apellido_representante' => $data['representante_apellido'] ?? '',
                    ':telefono_representante' => $data['representante_telefono'] ?? ''
                ]);
                
                error_log("Representante actualizado - ID: $current_id_representante, Nueva cédula: $cedula_nueva");
                $id_representante = $current_id_representante;
            } else {
                // CREAR NUEVO REPRESENTANTE (si no existe)
                error_log("El beneficiario no tiene representante. Creando nuevo...");
                
                $query_insert_rep = "INSERT INTO {$this->table_representante} 
                    (cedula_representante, nombres, apellidos, numero_contacto, fecha_registro)
                    VALUES (:cedula_representante, :nombre_representante, :apellido_representante, 
                            :telefono_representante, NOW())";
                
                $stmt_rep = $this->conn->prepare($query_insert_rep);
                $stmt_rep->execute([
                    ':cedula_representante' => $cedula_nueva,
                    ':nombre_representante' => $data['representante_nombre'] ?? '',
                    ':apellido_representante' => $data['representante_apellido'] ?? '',
                    ':telefono_representante' => $data['representante_telefono'] ?? ''
                ]);
                
                $id_representante = $this->conn->lastInsertId();
                error_log("Nuevo representante creado - ID: $id_representante, Cédula: $cedula_nueva");
            }
            
            // Actualizar ubicación
            if ($current_id_ubicacion) {
                $query_update_ubic = "UPDATE {$this->table_ubicacion} SET 
                    municipio = :municipio,
                    parroquia = :parroquia,
                    sector = :sector
                    WHERE id_ubicacion = :id_ubicacion";
                
                $stmt_ubic = $this->conn->prepare($query_update_ubic);
                $stmt_ubic->execute([
                    ':id_ubicacion' => $current_id_ubicacion,
                    ':municipio' => $data['municipio'] ?? '',
                    ':parroquia' => $data['parroquia'] ?? '',
                    ':sector' => $data['sector'] ?? ''
                ]);
                
                error_log("Ubicación actualizada - ID: $current_id_ubicacion");
                $id_ubicacion = $current_id_ubicacion;
            } else {
                // Crear nueva ubicación si no existe
                error_log("El beneficiario no tiene ubicación. Creando nueva...");
                
                $query_insert_ubic = "INSERT INTO {$this->table_ubicacion} 
                    (municipio, parroquia, sector, fecha_registro)
                    VALUES (:municipio, :parroquia, :sector, NOW())";
                
                $stmt_ubic = $this->conn->prepare($query_insert_ubic);
                $stmt_ubic->execute([
                    ':municipio' => $data['municipio'] ?? '',
                    ':parroquia' => $data['parroquia'] ?? '',
                    ':sector' => $data['sector'] ?? ''
                ]);
                
                $id_ubicacion = $this->conn->lastInsertId();
                error_log("Nueva ubicación creada - ID: $id_ubicacion");
            }
            
            // ACTUALIZAR BENEFICIARIO
            $query_beneficiario = "UPDATE {$this->table_beneficiario} SET 
                cedula_beneficiario = :cedula_beneficiario,
                nombres = :nombres,
                apellidos = :apellidos,
                genero = :genero,
                fecha_nacimiento = :fecha_nacimiento,
                edad = :edad,
                imc = :imc,
                cbi_mm = :cbi_mm,
                peso_kg = :peso_kg,
                talla_cm = :talla_cm,
                situacion_dx = :situacion_dx,
                lactando = :lactando,
                fecha_nac_lactante = :fecha_nac_lactante,
                gestante = :gestante,
                semanas_gestacion = :semanas_gestacion,
                lactante = :lactante,
                id_representante = :id_representante,
                fecha_actualizacion = NOW()
                WHERE id_beneficiario = :id_beneficiario";
            
            $params = [
                ':id_beneficiario' => $beneficiario_id,
                ':cedula_beneficiario' => $data['cedula_beneficiario'] ?? '',
                ':nombres' => $data['nombres'] ?? '',
                ':apellidos' => $data['apellidos'] ?? '',
                ':genero' => $genero_db,
                ':fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                ':edad' => $data['edad'] ?? 0,
                ':imc' => $data['imc'] ?? 0,
                ':cbi_mm' => $data['cbi_mm'] ?? 0,
                ':peso_kg' => $data['peso_kg'] ?? 0,
                ':talla_cm' => $data['talla_cm'] ?? 0,
                ':situacion_dx' => $data['situacion_dx'] ?? '',
                ':lactando' => $lactando,
                ':fecha_nac_lactante' => $fecha_nac_lactante,
                ':gestante' => $gestante,
                ':semanas_gestacion' => $semanas_gestacion,
                ':lactante' => $lactante_tinyint,
                ':id_representante' => $id_representante
            ];
            
            error_log("Ejecutando UPDATE del beneficiario...");
            error_log("Parámetros: " . print_r($params, true));
            
            $stmt_ben = $this->conn->prepare($query_beneficiario);
            $success = $stmt_ben->execute($params);
            
            if ($success) {
                $rowCount = $stmt_ben->rowCount();
                error_log("UPDATE ejecutado. Filas afectadas: $rowCount");
                
                if ($rowCount > 0) {
                    $this->conn->commit();
                    error_log("=== MODELO updateBeneficiario EXITOSO ===");
                    return true;
                } else {
                    error_log("ADVERTENCIA: No se afectaron filas en beneficiario. Verificar datos.");
                    // Verificar si realmente hubo cambios
                    $this->conn->commit(); // Aún así commit si el representante/ubicación se actualizó
                    return true;
                }
            } else {
                $errorInfo = $stmt_ben->errorInfo();
                error_log("ERROR en execute: " . print_r($errorInfo, true));
                $this->conn->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERROR PDO en updateBeneficiario: " . $e->getMessage());
            error_log("Código: " . $e->getCode());
            throw new Exception("Error en base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERROR General en updateBeneficiario: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteBeneficiario($cedula) {
        $query = "UPDATE {$this->table_beneficiario} SET status = 'Inactivo' 
                  WHERE cedula_beneficiario = :cedula";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cedula", $cedula);
        
        return $stmt->execute();
    }

    public function getAllNuevosIngresos() {
        $this->createNuevosIngresosTable();
        
        $query = "SELECT * FROM nuevos_ingresos 
                 WHERE incorporado = FALSE 
                 ORDER BY fecha_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createNuevoIngreso($data) {
        $this->createNuevosIngresosTable();
        
        $lactante = isset($data['condicion_femenina']) && $data['condicion_femenina'] == 'lactante' ? 1 : 0;
        $fecha_nac_lactante = $lactante && !empty($data['fecha_nacimiento_bebe']) ? 
                              $data['fecha_nacimiento_bebe'] : null;
        
        $gestante = isset($data['condicion_femenina']) && $data['condicion_femenina'] == 'gestante' ? 1 : 0;
        $semanas_gestacion = $gestante && !empty($data['semanas_gestacion']) ? 
                            intval($data['semanas_gestacion']) : null;
        
        $query = "INSERT INTO nuevos_ingresos 
            (cedula_beneficiario, nombre_beneficiario, apellido_beneficiario,
             fecha_nacimiento, genero, edad, imc, cbi_mm, peso_kg, talla_cm, situacion_dx,
             cedula_representante, nombre_representante, apellido_representante, telefono_representante,
             municipio, parroquia, sector, nombre_clap, nombre_comuna,
             lactante, fecha_nac_lactante, gestante, semanas_gestacion)
            VALUES (:cedula_beneficiario, :nombre_beneficiario, :apellido_beneficiario,
                    :fecha_nacimiento, :genero, :edad, :imc, :cbi_mm, :peso_kg, :talla_cm, :situacion_dx,
                    :cedula_representante, :nombre_representante, :apellido_representante, :telefono_representante,
                    :municipio, :parroquia, :sector, :nombre_clap, :nombre_comuna,
                    :lactante, :fecha_nac_lactante, :gestante, :semanas_gestacion)
            ON DUPLICATE KEY UPDATE
            nombre_beneficiario = VALUES(nombre_beneficiario),
            apellido_beneficiario = VALUES(apellido_beneficiario),
            fecha_nacimiento = VALUES(fecha_nacimiento),
            genero = VALUES(genero),
            edad = VALUES(edad),
            imc = VALUES(imc),
            cbi_mm = VALUES(cbi_mm),
            peso_kg = VALUES(peso_kg),
            talla_cm = VALUES(talla_cm),
            situacion_dx = VALUES(situacion_dx),
            lactante = VALUES(lactante),
            fecha_nac_lactante = VALUES(fecha_nac_lactante),
            gestante = VALUES(gestante),
            semanas_gestacion = VALUES(semanas_gestacion),
            incorporado = FALSE";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            ':cedula_beneficiario' => $data['cedula_beneficiario'],
            ':nombre_beneficiario' => $data['nombres'],
            ':apellido_beneficiario' => $data['apellidos'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':genero' => $data['genero'],
            ':edad' => $data['edad'] ?? 0,
            ':imc' => $data['imc'] ?? 0,
            ':cbi_mm' => $data['cbi_mm'] ?? 0,
            ':peso_kg' => $data['peso_kg'] ?? 0,
            ':talla_cm' => $data['talla_cm'] ?? 0,
            ':situacion_dx' => $data['situacion_dx'],
            ':cedula_representante' => $data['representante_cedula'] ?? '',
            ':nombre_representante' => $data['representante_nombre'] ?? '',
            ':apellido_representante' => $data['representante_apellido'] ?? '',
            ':telefono_representante' => $data['representante_telefono'] ?? '',
            ':municipio' => $data['municipio'] ?? '',
            ':parroquia' => $data['parroquia'] ?? '',
            ':sector' => $data['sector'] ?? '',
            ':nombre_clap' => $data['nombre_clap'] ?? '',
            ':nombre_comuna' => $data['nombre_comuna'] ?? '',
            ':lactante' => $lactante,
            ':fecha_nac_lactante' => $fecha_nac_lactante,
            ':gestante' => $gestante,
            ':semanas_gestacion' => $semanas_gestacion
        ]);
    }

    public function incorporarNuevoIngreso($cedula) {
        try {
            $this->conn->beginTransaction();
            
            $query_select = "SELECT * FROM nuevos_ingresos 
                           WHERE cedula_beneficiario = :cedula 
                           AND incorporado = FALSE";
            
            $stmt_select = $this->conn->prepare($query_select);
            $stmt_select->execute([':cedula' => $cedula]);
            $nuevo_ingreso = $stmt_select->fetch(PDO::FETCH_ASSOC);
            
            if (!$nuevo_ingreso) {
                throw new Exception("Nuevo ingreso no encontrado o ya incorporado");
            }
            
            $genero_db = 'OTRO';
            if ($nuevo_ingreso['genero'] == 'M') {
                $genero_db = 'MASCULINO';
            } elseif ($nuevo_ingreso['genero'] == 'F') {
                $genero_db = 'FEMENINO';
            }
            
            $lactando = 'NO';
            $gestante = 'NO';
            $lactante_tinyint = 0;
            $fecha_nac_lactante = null;
            $semanas_gestacion = null;
            
            if ($genero_db == 'FEMENINO') {
                if ($nuevo_ingreso['lactante']) {
                    $lactando = 'SI';
                    $lactante_tinyint = 1;
                    $fecha_nac_lactante = $nuevo_ingreso['fecha_nac_lactante'];
                }
                if ($nuevo_ingreso['gestante']) {
                    $gestante = 'SI';
                    $semanas_gestacion = $nuevo_ingreso['semanas_gestacion'];
                }
            }
            
            $query_representante = "INSERT INTO {$this->table_representante} 
                (cedula_representante, nombres, apellidos, numero_contacto, fecha_registro)
                VALUES (:cedula_representante, :nombre_representante, :apellido_representante, 
                        :telefono_representante, NOW())
                ON DUPLICATE KEY UPDATE 
                nombres = VALUES(nombres),
                apellidos = VALUES(apellidos),
                numero_contacto = VALUES(numero_contacto)";
            
            $stmt_rep = $this->conn->prepare($query_representante);
            $stmt_rep->execute([
                ':cedula_representante' => $nuevo_ingreso['cedula_representante'],
                ':nombre_representante' => $nuevo_ingreso['nombre_representante'],
                ':apellido_representante' => $nuevo_ingreso['apellido_representante'],
                ':telefono_representante' => $nuevo_ingreso['telefono_representante']
            ]);
            
            $query_get_rep_id = "SELECT id_representante FROM {$this->table_representante} 
                                WHERE cedula_representante = :cedula";
            $stmt_get_rep = $this->conn->prepare($query_get_rep_id);
            $stmt_get_rep->execute([':cedula' => $nuevo_ingreso['cedula_representante']]);
            $representante = $stmt_get_rep->fetch(PDO::FETCH_ASSOC);
            $id_representante = $representante['id_representante'];
            
            $query_ubicacion = "INSERT INTO {$this->table_ubicacion} 
                (municipio, parroquia, sector, fecha_registro)
                VALUES (:municipio, :parroquia, :sector, NOW())
                ON DUPLICATE KEY UPDATE 
                municipio = VALUES(municipio),
                parroquia = VALUES(parroquia),
                sector = VALUES(sector)";
            
            $stmt_ubic = $this->conn->prepare($query_ubicacion);
            $stmt_ubic->execute([
                ':municipio' => $nuevo_ingreso['municipio'],
                ':parroquia' => $nuevo_ingreso['parroquia'],
                ':sector' => $nuevo_ingreso['sector']
            ]);
            
            $query_get_ubic_id = "SELECT id_ubicacion FROM {$this->table_ubicacion} 
                                 WHERE municipio = :municipio 
                                 AND parroquia = :parroquia 
                                 AND sector = :sector";
            $stmt_get_ubic = $this->conn->prepare($query_get_ubic_id);
            $stmt_get_ubic->execute([
                ':municipio' => $nuevo_ingreso['municipio'],
                ':parroquia' => $nuevo_ingreso['parroquia'],
                ':sector' => $nuevo_ingreso['sector']
            ]);
            $ubicacion = $stmt_get_ubic->fetch(PDO::FETCH_ASSOC);
            $id_ubicacion = $ubicacion['id_ubicacion'];
            
            $query_beneficiario = "INSERT INTO {$this->table_beneficiario} 
                (cedula_beneficiario, nombres, apellidos, genero, fecha_nacimiento, edad,
                 imc, cbi_mm, peso_kg, talla_cm, situacion_dx,
                 lactando, fecha_nac_lactante, gestante, semanas_gestacion, lactante,
                 id_representante, id_ubicacion, status, fecha_registro)
                VALUES (:cedula_beneficiario, :nombre_beneficiario, :apellido_beneficiario, 
                        :genero, :fecha_nacimiento, :edad, :imc, :cbi_mm, :peso_kg, :talla_cm, :situacion_dx,
                        :lactando, :fecha_nac_lactante, :gestante, :semanas_gestacion, :lactante,
                        :id_representante, :id_ubicacion, 'Activo', NOW())";
            
            $stmt_ben = $this->conn->prepare($query_beneficiario);
            $stmt_ben->execute([
                ':cedula_beneficiario' => $nuevo_ingreso['cedula_beneficiario'],
                ':nombre_beneficiario' => $nuevo_ingreso['nombre_beneficiario'],
                ':apellido_beneficiario' => $nuevo_ingreso['apellido_beneficiario'],
                ':genero' => $genero_db,
                ':fecha_nacimiento' => $nuevo_ingreso['fecha_nacimiento'],
                ':edad' => $nuevo_ingreso['edad'] ?? 0,
                ':imc' => $nuevo_ingreso['imc'] ?? 0,
                ':cbi_mm' => $nuevo_ingreso['cbi_mm'] ?? 0,
                ':peso_kg' => $nuevo_ingreso['peso_kg'] ?? 0,
                ':talla_cm' => $nuevo_ingreso['talla_cm'] ?? 0,
                ':situacion_dx' => $nuevo_ingreso['situacion_dx'],
                ':lactando' => $lactando,
                ':fecha_nac_lactante' => $fecha_nac_lactante,
                ':gestante' => $gestante,
                ':semanas_gestacion' => $semanas_gestacion,
                ':lactante' => $lactante_tinyint,
                ':id_representante' => $id_representante,
                ':id_ubicacion' => $id_ubicacion
            ]);
            
            $query_update = "UPDATE nuevos_ingresos SET incorporado = TRUE 
                            WHERE cedula_beneficiario = :cedula";
            
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->execute([':cedula' => $cedula]);
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en incorporarNuevoIngreso: " . $e->getMessage());
            throw new Exception("Error al incorporar beneficiario: " . $e->getMessage());
        }
    }

    public function incorporarTodos() {
        try {
            $this->conn->beginTransaction();
            
            $query_select = "SELECT cedula_beneficiario FROM nuevos_ingresos 
                           WHERE incorporado = FALSE";
            
            $stmt_select = $this->conn->prepare($query_select);
            $stmt_select->execute();
            
            $nuevos_ingresos = $stmt_select->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $contador = 0;
            foreach ($nuevos_ingresos as $cedula) {
                try {
                    $this->incorporarNuevoIngreso($cedula);
                    $contador++;
                } catch (Exception $e) {
                    error_log("Error incorporando cédula $cedula: " . $e->getMessage());
                    continue;
                }
            }
            
            $this->conn->commit();
            return $contador;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function deleteNuevoIngreso($cedula) {
        $query = "DELETE FROM nuevos_ingresos 
                 WHERE cedula_beneficiario = :cedula 
                 AND incorporado = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cedula", $cedula);
        
        return $stmt->execute();
    }

    public function cedulaExists($cedula, $tipo = 'online') {
        if ($tipo == 'online') {
            $query = "SELECT COUNT(*) FROM {$this->table_beneficiario} 
                     WHERE cedula_beneficiario = :cedula 
                     AND (status = 'Activo' OR status IS NULL)";
        } else {
            $query = "SELECT COUNT(*) FROM nuevos_ingresos 
                     WHERE cedula_beneficiario = :cedula 
                     AND incorporado = FALSE";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cedula' => $cedula]);
        
        return $stmt->fetchColumn() > 0;
    }

    public function getFiltrosData() {
        return [
            'municipios' => $this->getMunicipios(),
            'parroquias_por_municipio' => $this->getParroquiasPorMunicipio(),
            'sectores_por_parroquia' => $this->getSectoresPorParroquia()
        ];
    }
}
?>