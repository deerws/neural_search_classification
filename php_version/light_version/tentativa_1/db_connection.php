<?php
// db_connection.php
class Database {
    private $host = 'localhost';
    private $db_name = 'sc2c';
    private $username = 'root'; // substitua pelo seu usuário MySQL
    private $password = 'sua_senha';   // substitua pela sua senha MySQL
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }
}

// Função auxiliar para salvar classificações
function saveClassification($db, $data) {
    try {
        $conn = $db->connect();
        
        // Prepara a chamada da stored procedure
        $stmt = $conn->prepare("CALL add_classification(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Executa com os parâmetros
        $stmt->execute([
            $data['username'],
            $data['program_name'],
            $data['area_name'],
            $data['line_name'],
            $data['system_name'],
            $data['domain_code'],
            $data['subdomain_code'],
            $data['score'],
            $data['is_approved'],
            $data['notes']
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error saving classification: " . $e->getMessage());
        return false;
    }
}

// Função para exportar dados para CSV
function exportClassifications($db) {
    try {
        $conn = $db->connect();
        $stmt = $conn->prepare("CALL export_classifications_to_csv()");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error exporting classifications: " . $e->getMessage());
        return [];
    }
}
?>