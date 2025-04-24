<?php
require_once '../utils/db_connection.php';

class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createBudget($departmentId, $initialBalance, $year, $periodId)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO budgets (department_id, initial_balance, year, period_id, status) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$departmentId, $initialBalance, $year, $periodId]);

            $budgetId = $this->db->lastInsertId();
            $this->db->commit();

            return $budgetId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur création budget: " . $e->getMessage());
            return false;
        }
    }

    public function addForecast($budgetId, $category, $type, $amount, $description = null, $periodId)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO budget_forecasts 
                (budget_id, category, type, amount, description, period_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$budgetId, $category, $type, $amount, $description, $periodId]);
        } catch (PDOException $e) {
            error_log("Erreur ajout prévision: " . $e->getMessage());
            return false;
        }
    }

    public function addRealization($budgetId, $category, $type, $amount, $date, $periodId, $description = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO budget_realizations (budget_id, category, type, amount, date, period_id, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$budgetId, $category, $type, $amount, $date, $periodId, $description]);
        } catch (PDOException $e) {
            error_log("Erreur ajout réalisation: " . $e->getMessage());
            return false;
        }
    }

    public function getBudgetByPeriodAndDepartment($periodId, $departmentId)
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM budgets
            WHERE period_id = ? AND department_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $departmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBudgetByPeriodDepartmentCategory($periodId, $departmentId, $category)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT b.id
            FROM budgets b
            JOIN budget_forecasts bf ON b.id = bf.budget_id
            WHERE b.period_id = ? AND b.department_id = ? AND bf.category = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $departmentId, $category]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBudgetDetails($budgetId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM budget_forecasts 
            WHERE budget_id = ?
        ");
        $stmt->execute([$budgetId]);
        $forecasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT * FROM budget_realizations 
            WHERE budget_id = ?
        ");
        $stmt->execute([$budgetId]);
        $realizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'forecasts' => $forecasts,
            'realizations' => $realizations
        ];
    }

    public function updateBudgetStatus($budgetId, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE budgets SET status = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $budgetId]);
    }

    public function getBudgetsWithDetails()
    {
        $sql = "
            SELECT 
                d.name as department_name,
                bf.category,
                bf.type,
                b.initial_balance,
                bf.amount as forecast_amount,
                b.status,
                b.id as budget_id
            FROM budgets b
            INNER JOIN departments d ON b.department_id = d.id
            LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id
            ORDER BY d.name, bf.category, bf.type
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkRealizationExists($budgetId, $category, $type, $periodId) {
        try {
            $sql = "
                SELECT COUNT(*) 
                FROM budget_realizations 
                WHERE budget_id = :budget_id 
                AND category = :category 
                AND type = :type 
                AND period_id = :period_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':budget_id' => $budgetId,
                ':category' => $category,
                ':type' => $type,
                ':period_id' => $periodId
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'existence de la réalisation : " . $e->getMessage());
            return false;
        }
    }

    function getBudgetsDetailsByPeriod($departmentId = null)
    {
        try {
            $sql = "
                SELECT 
                    p.id as period_id,
                    p.name as period_name,
                    d.id as department_id,
                    d.name as department_name,
                    b.id as budget_id,
                    b.initial_balance,
                    b.status,
                    bf.id as forecast_id,
                    bf.category,
                    bf.type,
                    bf.amount as forecast_amount,
                    bf.description as forecast_description,
                    br.id as realization_id,
                    br.amount as realization_amount,
                    br.date as realization_date,
                    br.description as realization_description
                FROM budgets b
                INNER JOIN departments d ON b.department_id = d.id
                INNER JOIN periods p ON b.period_id = p.id
                LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
                LEFT JOIN budget_realizations br ON b.id = br.budget_id 
                    AND br.period_id = p.id 
                    AND br.category = bf.category 
                    AND br.type = bf.type
                WHERE (bf.category IS NOT NULL OR br.category IS NOT NULL) 
                    AND b.status = 1
            ";

            $params = [];
            if ($departmentId) {
                $sql .= " AND d.id = ? ";
                $params = [$departmentId];
            }

            $sql .= " ORDER BY d.name, p.start_date, bf.category DESC, bf.type";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Récupérer tous les résultats dans un tableau
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Gérer l'erreur (vous pouvez personnaliser selon vos besoins)
            error_log("Erreur lors de l'exécution de la requête : " . $e->getMessage());
            return [];
        }
    }

    function prepareSummaryData($departmentId)
    {
        $rawData = $this->getBudgetsDetailsByPeriod($departmentId);
        $summaryData = [
            'departments' => [],
            'periods' => [],
            'categories' => [],
            'data' => []
        ];

        foreach ($rawData as $row) {
            $deptId = $row['department_id'];
            $deptName = $row['department_name'];
            $periodId = $row['period_id'];
            $periodName = $row['period_name'];
            $category = $row['category'] ?? 'Sans catégorie';
            $type = $row['type'] ?? 'Sans type';

            // Ajouter département
            if (!isset($summaryData['departments'][$deptId])) {
                $summaryData['departments'][$deptId] = ['id' => $deptId, 'name' => $deptName];
            }

            // Ajouter période
            if (!isset($summaryData['periods'][$periodId])) {
                $summaryData['periods'][$periodId] = ['id' => $periodId, 'name' => $periodName];
            }

            // Ajouter catégorie
            if (!in_array($category, $summaryData['categories'])) {
                $summaryData['categories'][] = $category;
            }

            // Organiser les données par département, période, catégorie et type
            if (!isset($summaryData['data'][$deptId])) {
                $summaryData['data'][$deptId] = [];
            }
            if (!isset($summaryData['data'][$deptId][$periodId])) {
                $summaryData['data'][$deptId][$periodId] = [];
            }
            if (!isset($summaryData['data'][$deptId][$periodId][$category])) {
                $summaryData['data'][$deptId][$periodId][$category] = [];
            }
            if (!isset($summaryData['data'][$deptId][$periodId][$category][$type])) {
                $summaryData['data'][$deptId][$periodId][$category][$type] = [
                    'forecast' => [
                        'amount' => 0,
                        'description' => $row['forecast_description'] ?? ''
                    ],
                    'realization' => [
                        'amount' => 0,
                        'description' => $row['realization_description'] ?? ''
                    ],
                    'initial_balance' => $row['initial_balance'] ?? 0
                ];
            }

            // Ajouter les montants
            $summaryData['data'][$deptId][$periodId][$category][$type]['forecast']['amount'] += $row['forecast_amount'] ?? 0;
            $summaryData['data'][$deptId][$periodId][$category][$type]['realization']['amount'] += $row['realization_amount'] ?? 0;
        }

        // Convertir les tableaux indexés en tableaux simples
        $summaryData['departments'] = array_values($summaryData['departments']);
        $summaryData['periods'] = array_values($summaryData['periods']);
        sort($summaryData['categories']);

        return $summaryData;
    }

    public function getPeriods()
    {
        $stmt = $this->db->query("SELECT * FROM periods ORDER BY start_date");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategories($departmentId)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT bf.type AS category 
            FROM budget_forecasts bf
            JOIN budgets b ON bf.budget_id = b.id
            WHERE b.department_id = ?
            UNION
            SELECT DISTINCT br.type AS category 
            FROM budget_realizations br
            JOIN budgets b ON br.budget_id = b.id
            WHERE b.department_id = ?
            ORDER BY category
        ");
        $stmt->execute([$departmentId, $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkPeriodExists($periodId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM periods WHERE id = ?
        ");
        $stmt->execute([$periodId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getTypesByCategory($departmentId, $category, $periodId)
    {
        $stmt = $this->db->prepare("
            SELECT bf.type, bf.budget_id
            FROM budget_forecasts bf
            JOIN budgets b ON bf.budget_id = b.id
            WHERE bf.category = ? AND bf.period_id = ? AND b.department_id = ?
        ");
        $stmt->execute([$category, $periodId, $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPreviousPeriod($currentPeriodStartDate, $departmentId) {
        try {
            // Trouver la période avec la date de fin la plus proche avant la date de début actuelle
            $sql = "
                SELECT p.*
                FROM periods p
                INNER JOIN budgets b ON p.id = b.period_id
                WHERE b.department_id = :department_id
                AND p.start_date < :current_start_date
                ORDER BY p.start_date DESC
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':current_start_date' => $currentPeriodStartDate
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la période précédente : " . $e->getMessage());
            return null;
        }
    }
}


    // public function getBudgetsDetailsByPeriod($departmentId = null)
    // {
    //     $sql = "
    //     SELECT 
    //         p.id as period_id,
    //         p.name as period_name,
    //         d.id as department_id,
    //         d.name as department_name,
    //         b.id as budget_id,
    //         b.initial_balance,
    //         b.status,
    //         bf.id as forecast_id,
    //         bf.category,
    //         bf.type,
    //         bf.amount as forecast_amount,
    //         bf.description as forecast_description,
    //         br.id as realization_id,
    //         br.amount as realization_amount,
    //         br.date as realization_date,
    //         br.description as realization_description
    //     FROM budgets b
    //     INNER JOIN departments d ON b.department_id = d.id
    //     INNER JOIN periods p ON b.period_id = p.id
    //     LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
    //     LEFT JOIN budget_realizations br ON b.id = br.budget_id 
    //         AND br.period_id = p.id 
    //         AND br.category = bf.category 
    //         AND br.type = bf.type
    //     WHERE (bf.category IS NOT NULL OR br.category IS NOT NULL) 
    //         AND b.status = 1
    // ";

    //     $params = [];
    //     if ($departmentId) {
    //         $sql .= " AND d.id = ? ";
    //         $params = [$departmentId];
    //     }

    //     $sql .= " ORDER BY d.name, p.start_date, bf.category DESC, bf.type ASC";

    //     $stmt = $this->db->prepare($sql);
    //     $stmt->execute($params);
    //     $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $result = [
    //         'periods' => [],
    //         'departments' => [],
    //         'categories' => [],
    //         'data' => []
    //     ];

    //     foreach ($rawData as $row) {
    //         $periodId = $row['period_id'];
    //         $departmentId = $row['department_id'];
    //         $category = $row['category'];

    //         if (!isset($result['periods'][$periodId])) {
    //             $result['periods'][$periodId] = [
    //                 'id' => $periodId,
    //                 'name' => $row['period_name']
    //             ];
    //         }

    //         if (!isset($result['departments'][$departmentId])) {
    //             $result['departments'][$departmentId] = [
    //                 'id' => $departmentId,
    //                 'name' => $row['department_name']
    //             ];
    //         }

    //         if ($category && !isset($result['categories'][$category])) {
    //             $result['categories'][$category] = $category;
    //         }

    //         if ($row['budget_id'] && $category) {
    //             $result['data'][$departmentId][$periodId][$category] = [
    //                 'forecast' => [
    //                     'id' => $row['forecast_id'],
    //                     'category' => $row['category'],
    //                     'type' => $row['type'],
    //                     'amount' => $row['forecast_amount'] ?? 0,
    //                     'description' => $row['forecast_description']
    //                 ],
    //                 'realization' => [
    //                     'id' => $row['realization_id'],
    //                     'category' => $row['category'],
    //                     'type' => $row['type'],
    //                     'amount' => $row['realization_amount'] ?? 0,
    //                     'date' => $row['realization_date'],
    //                     'description' => $row['realization_description']
    //                 ],
    //                 'initial_balance' => $row['initial_balance'],
    //                 'status' => $row['status']
    //             ];
    //         }
    //     }
    //     return $result;
    // }