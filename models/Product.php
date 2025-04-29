<?php
require_once '../utils/db_connection.php';

class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllProduct()
    {
        try {
            $sql = "SELECT * FROM products";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function getAllCatrgories()
    {
        try {
            $sql = "SELECT * FROM product_category";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function getProductsWithDetails()
    {
        try {
            $sql = "
            SELECT 
                p.id AS product_id,
                p.name AS product_name,
                pc.name AS category_name,
                GROUP_CONCAT(DISTINCT CONCAT(c.name, ': ', ca.description) SEPARATOR '\n') AS action_descriptions,
                v.nb_vente AS total_ventes,
                s.nb_produit AS total_stock
            FROM products p
            JOIN product_category pc ON p.category_id = pc.id
            LEFT JOIN customer_actions ca ON ca.product_id = p.id
            LEFT JOIN customers c ON ca.customer_id = c.id
            LEFT JOIN vente v ON v.product_id = p.id
            LEFT JOIN stock s ON s.product_id = p.id
            GROUP BY p.id, p.name, pc.name
            ORDER BY p.id
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des produits : " . $e->getMessage());
            return false;
        }
    }
}
