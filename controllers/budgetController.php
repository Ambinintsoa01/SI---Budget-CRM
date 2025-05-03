<?php
require_once '../models/Auth.php';
require_once '../models/crm.php';
require_once '../models/Budget.php';
require_once '../models/Department.php';

session_start();

$crm = new crm();
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
                $_SESSION['error'] = "Erreur lors de l'ajout d'une prévision budgétaire.";
                $success = false;
                break;
            }
        }

        // CRM : Insertion des réactions marketing/communication
        if ($success && $auth->isMarkNComm()) {
            $action = $crm->getActionsById($_POST['action_id'] ?? null);

            if ($action) {
                foreach ($_POST['categories'] as $category) {
                    $react_id = $crm->createReaction(
                        $action['p_category_id'] ?? NULL,
                        $action['product_id'] ?? NULL,
                        $currentDepartment['id'],
                        $category['type'],
                        $action['date_action']
                    );
                }

                if ($react_id !== false) {
                    if (!$crm->createCRM($_POST['action_id'], $react_id)) {
                        $_SESSION['error'] = "Erreur lors de la création de l'association CRM.";
                        $success = false;
                    }
                } else {
                    $_SESSION['error'] = "Erreur lors de la création de la réaction.";
                    $success = false;
                }
            } else {
                $_SESSION['error'] = "Action introuvable pour création de réaction.";
                $success = false;
            }
        }

        if ($success) {
            $_SESSION['success'] = "Budget créé avec succès ! En attente de validation.";
        } else {
            if (!isset($_SESSION['error'])) {
                $_SESSION['error'] = "Erreur inattendue lors de la création du budget.";
            }
        }
    } else {
        $_SESSION['error'] = "Erreur lors de la création du budget principal.";
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
        // Valider que budget_id est un entier
        if (!isset($category['budget_id']) || !is_numeric($category['budget_id'])) {
            $_SESSION['error'] = "Identifiant de budget invalide pour la catégorie {$category['category']}.";
            $success = false;
            break;
        }

        // Vérifier que le budget_id existe et correspond à la catégorie
        $budgetData = $budget->getBudgetByPeriodDepartmentCategory($periodId, $departmentId, $category['category'], $category['budget_id']);
        if (!$budgetData) {
            $_SESSION['error'] = "Budget invalide pour la catégorie {$category['category']} ou identifiant de budget incorrect.";
            $success = false;
            break;
        }

        // Vérifier si une réalisation existe déjà pour ce budget_id, category, type et period_id
        if ($budget->checkRealizationExists($category['budget_id'], $category['category'], $category['type'], $periodId)) {
            $_SESSION['error'] = "Une réalisation existe déjà pour la catégorie {$category['category']}, type {$category['type']} et période sélectionnée.";
            $success = false;
            break;
        }

        // Vérification spécifique pour la catégorie "Dépense"
        if ($category['category'] === 'Dépense') {
            // Récupérer la prévision pour la catégorie, type, budget et période
            $forecast = $budget->getForecastByBudgetCategoryType($category['budget_id'], $category['category'], $category['type'], $periodId);
            if ($forecast && $category['amount'] > $forecast['amount']) {
                // Le montant de la réalisation dépasse la prévision
                // Calculer le reste total des dépenses pour la période
                $totalForecast = $budget->getTotalForecastByCategory($periodId, $departmentId, 'Dépense');
                $totalRealization = $budget->getTotalRealizationByCategory($periodId, $departmentId, 'Dépense');
                $remainingBudget = $totalForecast - $totalRealization;

                if ($remainingBudget >= $category['amount']) {
                    // Le reste total est suffisant, on peut insérer la réalisation
                } else {
                    // Le reste total est insuffisant, créer un nouveau budget et une nouvelle prévision
                    $initialBalance = 0; // À ajuster selon votre logique
                    $year = date('Y', strtotime($date));
                    $newBudgetId = $budget->createBudget($departmentId, $initialBalance, $year, $periodId);

                    if ($newBudgetId !== false) {
                        // Ajouter la nouvelle prévision avec le montant de la réalisation
                        if (!$budget->addForecast(
                            $newBudgetId,
                            $category['category'],
                            $category['type'],
                            $category['amount'],
                            $category['description'] ?? null,
                            $periodId
                        )) {
                            $_SESSION['error'] = "Erreur lors de l'ajout de la nouvelle prévision.";
                            $success = false;
                            break;
                        }

                        // Supprimer l'ancienne prévision pour ce budget_id, catégorie et type
                        if (!$budget->deleteForecast($category['budget_id'], $category['category'], $category['type'], $periodId)) {
                            $_SESSION['error'] = "Erreur lors de la suppression de l'ancienne prévision.";
                            $success = false;
                            break;
                        }

                        // Mettre à jour le budget_id pour la réalisation
                        $category['budget_id'] = $newBudgetId;
                    } else {
                        $_SESSION['error'] = "Erreur lors de la création du nouveau budget.";
                        $success = false;
                        break;
                    }
                }
            }
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

    if ($success && $auth->isMarkNComm()) {
        if ($success && !$crm->updatePhase($_POST['action_id'])) {
            $_SESSION['error'] = "Erreur lors de la mise à jour de la phase de l'action.";
            $success = false;
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
