<?php
require_once '../models/Auth.php';
require_once '../models/Budget.php';
require_once '../models/Department.php';

session_start();

$auth = new Auth();
$budget = new Budget();
$department = new Department();

if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit();
}

$currentDepartment = $auth->getCurrentDepartment();

// Créer un nouveau budget
if (isset($_POST['create_budget'])) {
    $initialBalance = $_POST['initial_balance'];
    $periodId = $_POST['period_id'];
    $year = date('Y');

    // Valider les données POST
    if (!isset($_POST['categories']) || !is_array($_POST['categories']) || empty($_POST['categories'])) {
        $_SESSION['error'] = "Aucune prévision fournie.";
        header('Location: ../pages/budget.php');
        exit();
    }

    foreach ($_POST['categories'] as $index => $category) {
        if (
            !isset($category['category'], $category['type'], $category['amount']) ||
            empty($category['category']) || empty($category['type']) || !is_numeric($category['amount'])
        ) {
            $_SESSION['error'] = "Données de prévision invalides à l'index $index.";
            header('Location: ../pages/budget.php');
            exit();
        }
    }

    $budgetId = $budget->createBudget($currentDepartment['id'], $initialBalance, $year, $periodId);

    if ($budgetId !== false) {
        $success = true;
        foreach ($_POST['categories'] as $category) {
            if (!$budget->addForecast(
                $budgetId,
                $category['category'],
                $category['type'],
                $category['amount'],
                $category['description'] ?? null,
                $periodId
            )) {
                $success = false;
                break;
            }
        }

        if ($success) {
            $_SESSION['success'] = "Budget créé avec succès ! En attente de validation.";
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout des prévisions.";
        }
    } else {
        $_SESSION['error'] = "Erreur lors de la création du budget.";
    }
    header('Location: ../pages/budget.php');
    exit();
}

// Ajouter une réalisation
if (isset($_POST['add_realisation'])) {
    $periodId = $_POST['period_id'];
    $date = $_POST['date'];
    $departmentId = $currentDepartment['id'];

    // Valider les données POST
    if (!isset($_POST['categories']) || !is_array($_POST['categories']) || empty($_POST['categories'])) {
        $_SESSION['error'] = "Aucune réalisation fournie.";
        header('Location: ../pages/realisation.php');
        exit();
    }

    // Valider que period_id existe
    $periodExists = $budget->checkPeriodExists($periodId);
    if (!$periodExists) {
        $_SESSION['error'] = "Période sélectionnée invalide.";
        header('Location: ../pages/realisation.php');
        exit();
    }

    $success = true;
    foreach ($_POST['categories'] as $category) {
        // Vérifier que le budget_id existe et correspond à la catégorie
        $budgetData = $budget->getBudgetByPeriodDepartmentCategory($periodId, $departmentId, $category['category']);
        if (!$budgetData || $budgetData['id'] != $category['budget_id']) {
            $_SESSION['error'] = "Budget invalide pour la catégorie {$category['category']}.";
            $success = false;
            break;
        }

        // Vérifier si une réalisation existe déjà pour ce budget_id, category, type et period_id
        if ($budget->checkRealizationExists($category['budget_id'], $category['category'], $category['type'], $periodId)) {
            $_SESSION['error'] = "Une réalisation existe déjà pour la catégorie {$category['category']}, type {$category['type']} et période sélectionnée.";
            $success = false;
            break;
        }

        // Ajouter la réalisation si aucune ne existe
        if (!$budget->addRealization(
            $category['budget_id'],
            $category['category'],
            $category['type'],
            $category['amount'],
            $date,
            $periodId,
            $category['description'] ?? null
        )) {
            $success = false;
            break;
        }
    }

    if ($success) {
        $_SESSION['success'] = "Réalisation ajoutée avec succès !";
    } else {
        $_SESSION['error'] = $_SESSION['error'] ?? "Erreur lors de l'ajout des réalisations.";
    }

    header('Location: ../pages/realisation.php');
    exit();
}

// Valider/Rejeter un budget
if (isset($_POST['update_status']) && $auth->isFinance()) {
    $budgetId = $_POST['budget_id'];
    $status = $_POST['status'];

    if ($budget->updateBudgetStatus($budgetId, $status)) {
        $_SESSION['success'] = "Statut du budget mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du statut.";
    }
    header('Location: ../pages/validation.php');
    exit();
}

// Gestion des départements (DG seulement)
if ($auth->isDG()) {
    if (isset($_POST['add_department'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];

        if ($department->createDepartment($name, $description)) {
            $_SESSION['success'] = "Département créé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la création du département.";
        }
    }

    if (isset($_POST['edit_department'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];

        if ($department->updateDepartment($id, $name, $description)) {
            $_SESSION['success'] = "Département mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du département.";
        }
    }

    if (isset($_GET['delete_department'])) {
        $id = $_GET['id'];

        if ($department->deleteDepartment($id)) {
            $_SESSION['success'] = "Département supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du département.";
        }
    }
}

// header('Location: ../pages/home.php');
exit();
