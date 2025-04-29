<?php

require_once '../utils/db_connection.php';

class CRM
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllActions()
    {
        try {
            $sql = "SELECT ca.id, c.name, ca.description
                    FROM customer_actions ca
                    JOIN customers c
                    ON c.id = ca.customer_id
                    ORDER BY ca.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function getActionsForProducts()
    {
        try {
            $sql = "SELECT ca.id, c.name, ca.description
                    FROM customer_actions ca
                    JOIN customers c
                    ON c.id = ca.customer_id
                    WHERE ca.p_category_id IS NULL
                    ORDER BY ca.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function getActionsForProductsCategory()
    {
        try {
            $sql = "SELECT ca.id, c.name, ca.description
                    FROM customer_actions ca
                    JOIN customers c
                    ON c.id = ca.customer_id
                    WHERE ca.product_id IS NULL
                    ORDER BY ca.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function getActionsById($id)
    {
        try {
            $sql = "SELECT *
                    FROM customer_actions 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false; // Retourne un tableau associatif ou false si aucune ligne
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'action: " . $e->getMessage());
            return false;
        }
    }

    public function createReaction($p_category_id, $product_id, $departement_id, $description, $date_reaction)
    {
        try {
            $this->db->beginTransaction();

            // Normalisation de la date
            $date_reaction = date('Y-m-d', strtotime($date_reaction));

            $stmt = $this->db->prepare("
                INSERT INTO comm_reactions 
                (p_category_id, product_id, department_id, phase, description, date_reaction) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $p_category_id,
                $product_id,
                $departement_id,
                2,
                $description,
                $date_reaction
            ]);

            $React_Id = $this->db->lastInsertId();
            $this->db->commit();

            return $React_Id;
        } catch (PDOException $e) {
            $this->db->rollBack(); // Important
            error_log("Erreur ajout reaction: " . $e->getMessage());
            return false;
        }
    }

    public function createCRM($c_action_id, $c_reaction_id)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO CRM 
                (c_action_id, c_reaction_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$c_action_id, $c_reaction_id]);
        } catch (PDOException $e) {
            error_log("Erreur ajout reaction: " . $e->getMessage());
            return false;
        }
    }

    public function getCRM($id)
    {
        try {
            $sql = "
            SELECT cr.p_category_id, cr.product_id
            FROM CRM c
            INNER JOIN comm_reactions cr ON c.c_reaction_id = cr.id
            WHERE c.c_reaction_id = ?
        ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourne un tableau de résultats
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du CRM: " . $e->getMessage());
            return false;
        }
    }

    public function getAllReactionIds()
    {
        try {
            $sql = "SELECT id FROM comm_reactions";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des IDs de réactions: " . $e->getMessage());
            return [];
        }
    }
}
