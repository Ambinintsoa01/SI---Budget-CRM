<?php
require_once '../utils/db_connection.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($departmentName, $departmentPassword) {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE name = ? AND password = ?");
        $stmt->execute([$departmentName, $departmentPassword]);

        if ($stmt->rowCount() > 0) {
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['department_id'] = $department['id'];
            $_SESSION['department_name'] = $department['name'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['department_id']);
    }
    
    public function isFinance() {
        return isset($_SESSION['department_name']) && $_SESSION['department_name'] === 'Compta & Finance';
    }

    public function isMarkNComm() {
        return isset($_SESSION['department_name']) && $_SESSION['department_name'] === 'Marketing & Communication';
    }
    
    public function isDG() {
        return isset($_SESSION['department_name']) && $_SESSION['department_name'] === 'DG';
    }
    
    public function getCurrentDepartment() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['department_id'],
                'name' => $_SESSION['department_name']
            ];
        }
        return null;
    }
}
?>