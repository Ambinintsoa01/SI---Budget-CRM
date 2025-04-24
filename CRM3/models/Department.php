<?php
require_once '../utils/db_connection.php';

class Department {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllDepartments() {
        $stmt = $this->db->query("SELECT * FROM departments");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createDepartment($name, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO departments (name, description) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$name, $description]);
    }
    
    public function updateDepartment($id, $name, $description) {
        $stmt = $this->db->prepare("
            UPDATE departments SET name = ?, description = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $id]);
    }
    
    public function deleteDepartment($id) {
        $stmt = $this->db->prepare("DELETE FROM departments WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>