<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Budget.php';

$auth = new Auth();
$budget = new Budget();

$currentDepartment = $auth->getCurrentDepartment();

// Gérer les actions de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->isFinance()) {
    if (isset($_POST['budget_id']) && isset($_POST['action'])) {
        $budgetId = (int)$_POST['budget_id'];
        $action = $_POST['action'];
        $status = $action === 'validate' ? 1 : 2; // 1: Validé, 2: Rejeté

        if ($budget->updateBudgetStatus($budgetId, $status)) {
            $_SESSION['success'] = "Budget " . ($action === 'validate' ? 'validé' : 'rejeté') . " avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut du budget.";
        }
        header("Location: home.php");
        exit;
    }
}

// Récupérer les budgets
$budgets = $budget->getBudgetsWithDetails();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Gestion Budgétaire</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <aside class="sidebar">
        <h2 style="color: white;">Menu</h2>
        <div class="sidebar-actions">
            <a href="home.php" class="btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="budget.php" class="btn">
                <i class="fas fa-file-invoice-dollar"></i> Créer un Budget
            </a>
            <a href="realisation.php" class="btn">
                <i class="fas fa-money-bill-wave"></i> Ajouter une Réalisation
            </a>
            <a href="validation.php" class="btn">
                <i class="fas fa-user-secret"></i> Finance
            </a>
            <a href="../controllers/authController.php?logout=1" class="btn btn-error">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header>
            <h1>Gestion Budgétaire - <?= htmlspecialchars($currentDepartment['name']) ?></h1>
            <a href="home.php" class="logout">Retour</a>
        </header><br>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section>
            <h2>Liste des Budgets</h2>
            <table class="budget-table">
                <thead>
                    <tr>
                        <th>Département</th>
                        <th>Catégorie</th>
                        <th>Solde Initial</th>
                        <th>Montant Prévu</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($budgets)): ?>
                        <tr>
                            <td colspan="6">Aucun budget disponible.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($budgets as $budget): ?>
                            <tr>
                                <td><?= htmlspecialchars($budget['department_name']) ?></td>
                                <td><?= htmlspecialchars($budget['category'] ?? 'N/A') ?></td>
                                <td><?= number_format($budget['initial_balance'], 2, ',', ' ') ?> €</td>
                                <td><?= $budget['forecast_amount'] ? number_format($budget['forecast_amount'], 2, ',', ' ') . ' €' : 'N/A' ?></td>
                                <td class="status-<?= $budget['status'] ?>">
                                    <?php
                                    switch ($budget['status']) {
                                        case 0:
                                            echo 'En attente';
                                            break;
                                        case 1:
                                            echo 'Validé';
                                            break;
                                        case 2:
                                            echo 'Rejeté';
                                            break;
                                        default:
                                            echo 'Inconnu';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($auth->isFinance() && $budget['status'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="budget_id" value="<?= $budget['budget_id'] ?>">
                                            <input type="hidden" name="action" value="validate">
                                            <button type="submit" class="validate-btn">Valider</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="budget_id" value="<?= $budget['budget_id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="reject-btn">Rejeter</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>

</html>