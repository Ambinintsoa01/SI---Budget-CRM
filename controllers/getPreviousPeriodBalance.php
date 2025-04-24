<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Budget.php';

header('Content-Type: application/json');

$auth = new Auth();
$budgetModel = new Budget();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer les données POST
$input = json_decode(file_get_contents('php://input'), true);
$departmentId = $input['department_id'] ?? null;
$currentPeriodStartDate = $input['current_period_start_date'] ?? null;

if (!$departmentId || !$currentPeriodStartDate) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

// Trouver la période précédente
$previousPeriod = $budgetModel->getPreviousPeriod($currentPeriodStartDate, $departmentId);

if (!$previousPeriod) {
    // Aucune période précédente, solde initial = 0
    echo json_encode(['success' => true, 'balance' => 0]);
    exit;
}

// Calculer le solde final de la période précédente
$summaryData = $budgetModel->prepareSummaryData($departmentId);
$initialBalance = $summaryData['data'][$departmentId][$previousPeriod['id']][array_key_first($summaryData['categories'])]['initial_balance'] ?? 0;
$cumulativeBalance = $initialBalance;

foreach ($summaryData['periods'] as $period) {
    if ($period['id'] > $previousPeriod['id']) {
        break; // Ne traiter que les périodes jusqu'à la période précédente
    }
    $totalRecette = 0;
    $totalDepense = 0;

    foreach ($summaryData['departments'] as $dept) {
        if ($dept['id'] != $departmentId) {
            continue; // Ne traiter que le département concerné
        }
        foreach ($summaryData['categories'] as $category) {
            if (isset($summaryData['data'][$dept['id']][$period['id']][$category])) {
                foreach ($summaryData['data'][$dept['id']][$period['id']][$category] as $type => $data) {
                    $amount = $data['realization']['amount'] ?? 0;
                    if (strpos($category, 'Recette') !== false || strpos($category, 'Revenue') !== false) {
                        $totalRecette += $amount;
                    } elseif (strpos($category, 'Dépense') !== false || strpos($category, 'Charge') !== false) {
                        $totalDepense += $amount;
                    }
                }
            }
        }
    }

    $periodBalance = ($cumulativeBalance + $totalRecette) - $totalDepense;
    $cumulativeBalance = $periodBalance;
}

echo json_encode(['success' => true, 'balance' => $cumulativeBalance]);
exit;