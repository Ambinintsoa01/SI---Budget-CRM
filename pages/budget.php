<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Budget.php';
require_once '../models/Product.php';
require_once '../models/crm.php';

$crm = new crm();
$auth = new Auth();
$product = new Product();
$budgetModel = new Budget();

// $categories = $product->getAllCatrgories();
// $products = $product->getAllProduct();
$actionsProducts = $crm->getActionsForProducts();
$actionsProductsCat = $crm->getActionsForProductsCategory();
$currentDepartment = $auth->getCurrentDepartment();

$periods = $budgetModel->getPeriods();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            <?php if ($auth->isFinance()): ?>
                <a href="validation.php" class="btn">
                    <i class="fas fa-user-secret"></i> Finance
                </a>
            <?php endif; ?>
            <?php if ($auth->isMarkNComm()): ?>
                <a href="graph.php" class="btn">
                    <i class="fas fa-chart-bar"></i> Graphe&Stat
                </a>
                <a href="products.php" class="btn">
                    <i class="fas fa-pizza-slice"></i> Products
                </a>
            <?php endif; ?>
            <a href="../controllers/authController.php?logout=1" class="btn btn-error">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header>
            <h1>Ajouter une Prevision - <?= htmlspecialchars($currentDepartment['name']) ?></h1>
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

        <main>
            <form action="../controllers/budgetController.php" method="post">
                <div class="form-group">
                    <label for="period_id">Période</label>
                    <select name="period_id" id="period_id" required data-department-id="<?= $currentDepartment['id'] ?>">
                        <option value="">Sélectionnez une période</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>" data-start-date="<?= $period['start_date'] ?>">
                                <?= htmlspecialchars($period['name']) ?> (<?= date('d/m/Y', strtotime($period['start_date'])) ?> - <?= date('d/m/Y', strtotime($period['end_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="initial_balance">Solde Initial (€)</label>
                    <input type="number" step="0.01" id="initial_balance" name="initial_balance" value="0" readonly>
                </div>

                <?php if ($auth->isMarkNComm()) { ?>
                    <div class="form-group">
                        <label for="action_id">Action</label>
                        <select name="action_id" required>
                            <option value="">Selectionner l'action a repondre</option>
                            <optgroup label="Produits">
                                <?php foreach ($actionsProducts as $act) { ?>
                                    <option value="<?= $act['id'] ?>"><?= $act['name'] ?>: <?= $act['description'] ?></option>
                                <?php } ?>
                            </optgroup>
                            <optgroup label="Categories">
                                <?php foreach ($actionsProductsCat as $act) { ?>
                                    <option value="<?= $act['id'] ?>"><?= $act['name'] ?>: <?= $act['description'] ?></option>
                                <?php } ?>
                            </optgroup>
                        </select>
                    </div>
                <?php } ?>

                <h2>Prévisions Budgétaires</h2>
                <div id="forecasts-container">
                    <div class="forecast-item">
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select name="categories[0][category]" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Recette">Recette</option>
                                <option value="Dépense">Dépense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <input type="text" name="categories[0][type]" required>
                        </div>
                        <div class="form-group">
                            <label>Montant (€)</label>
                            <input type="number" step="0.01" name="categories[0][amount]" required>
                        </div>
                        <div class="form-group">
                            <label>Description (facultatif)</label>
                            <textarea name="categories[0][description]"></textarea>
                        </div>
                        <button type="button" class="btn btn-error remove-forecast">Supprimer</button>
                    </div>
                </div>

                <button type="button" id="add-forecast" class="btn">Ajouter une Prévision</button>
                <button type="submit" name="create_budget" class="btn btn-primary">Soumettre le Budget</button>
            </form>
        </main>
    </div>

    <script>
        // Gestion dynamique des prévisions
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('forecasts-container');
            const addButton = document.getElementById('add-forecast');
            const periodSelect = document.getElementById('period_id');
            const initialBalanceInput = document.getElementById('initial_balance');
            let counter = 1;

            // Gestion de l'ajout de prévisions
            addButton.addEventListener('click', function() {
                const newItem = document.createElement('div');
                newItem.className = 'forecast-item';
                newItem.innerHTML = `
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="categories[${counter}][category]" required>
                            <option value="">Sélectionnez une catégorie</option>
                            <option value="Recette">Recette</option>
                            <option value="Dépense">Dépense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <input type="text" name="categories[${counter}][type]" required>
                    </div>
                    <div class="form-group">
                        <label>Montant (€)</label>
                        <input type="number" step="0.01" name="categories[${counter}][amount]" required>
                    </div>
                    <div class="form-group">
                        <label>Description (facultatif)</label>
                        <textarea name="categories[${counter}][description]"></textarea>
                    </div>
                    <button type="button" class="btn btn-error remove-forecast">Supprimer</button>
                `;
                container.appendChild(newItem);
                counter++;
            });

            // Gestion de la suppression de prévisions
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-forecast')) {
                    if (container.children.length > 1) {
                        e.target.parentElement.remove();
                    } else {
                        alert('Vous devez avoir au moins une prévision.');
                    }
                }
            });

            // Gestion du solde initial en fonction de la période sélectionnée
            periodSelect.addEventListener('change', function() {
                const periodId = this.value;
                const departmentId = this.dataset.departmentId;
                const selectedOption = this.options[this.selectedIndex];
                const startDate = selectedOption ? selectedOption.dataset.startDate : null;

                if (!periodId || !startDate) {
                    initialBalanceInput.value = '0';
                    return;
                }

                // Envoyer une requête AJAX pour obtenir le solde final de la période précédente
                fetch('../controllers/getPreviousPeriodBalance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            department_id: departmentId,
                            current_period_start_date: startDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            initialBalanceInput.value = parseFloat(data.balance).toFixed(2);
                        } else {
                            initialBalanceInput.value = '0';
                            console.error(data.error);
                        }
                    })
                    .catch(error => {
                        initialBalanceInput.value = '0';
                        console.error('Erreur AJAX:', error);
                    });
            });
        });
    </script>
</body>

</html>