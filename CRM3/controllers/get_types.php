<?php
require_once '../models/Budget.php';
require_once '../models/Auth.php';

header('Content-Type: application/json');

try {
    // Initialiser la session
    session_start();

    $auth = new Auth();
    $budget = new Budget();

    // Vérifier l'authentification
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        error_log("get_types.php: Échec de l'authentification, session: " . json_encode($_SESSION));
        echo json_encode(['error' => 'Non autorisé']);
        exit();
    }

    // Vérifier le département actuel
    $currentDepartment = $auth->getCurrentDepartment();
    if (!$currentDepartment || !isset($currentDepartment['id'])) {
        http_response_code(400);
        error_log("get_types.php: Département non trouvé, currentDepartment: " . json_encode($currentDepartment));
        echo json_encode(['error' => 'Département non trouvé']);
        exit();
    }

    // Vérifier la catégorie
    if (!isset($_GET['category']) || empty($_GET['category']) || !in_array($_GET['category'], ['Recette', 'Dépense'])) {
        http_response_code(400);
        error_log("get_types.php: Catégorie invalide, category: " . ($_GET['category'] ?? 'non défini'));
        echo json_encode(['error' => 'Catégorie non spécifiée ou invalide']);
        exit();
    }

    // Vérifier period_id
    if (!isset($_GET['period_id']) || !is_numeric($_GET['period_id'])) {
        http_response_code(400);
        error_log("get_types.php: period_id invalide, period_id: " . ($_GET['period_id'] ?? 'non défini'));
        echo json_encode(['error' => 'Période non spécifiée ou invalide']);
        exit();
    }

    $category = $_GET['category'];
    $periodId = $_GET['period_id'];
    $departmentId = $currentDepartment['id'];

    error_log("get_types.php: Paramètres reçus - category=$category, period_id=$periodId, department_id=$departmentId");

    // Récupérer les types avec budget_id depuis budget_forecasts
    $types = $budget->getTypesByCategory($departmentId, $category, $periodId);
    $typeList = array_map(function($type) {
        return [
            'type' => $type['type'],
            'budget_id' => $type['budget_id']
        ];
    }, $types);

    error_log("get_types.php: Types récupérés: " . json_encode($typeList));

    echo json_encode(['types' => $typeList]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("get_types.php: Erreur serveur: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
    exit();
}
?>