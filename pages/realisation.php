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

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$periods = $budgetModel->getPeriods();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Réalisation - Gestion Budgétaire</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            <h1>Ajouter une Réalisation - <?= htmlspecialchars($currentDepartment['name']) ?></h1>
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
                    <select name="period_id" id="period_id" required>
                        <option value="">Sélectionnez une période</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>">
                                <?= htmlspecialchars($period['name']) ?> (<?= date('d/m/Y', strtotime($period['start_date'])) ?> - <?= date('d/m/Y', strtotime($period['end_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
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

                <h2>Réalisations Budgétaires</h2>
                <div id="forecasts-container">
                    <div class="forecast-item">
                        <div class="form-group">
                            <label for="category-0">Catégorie</label>
                            <select name="categories[0][category]" id="category-0" class="category-select" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Recette">Recette</option>
                                <option value="Dépense">Dépense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type-0">Type</label>
                            <select name="categories[0][type]" id="type-0" class="type-select" required>
                                <option value="">Sélectionnez un type</option>
                            </select>
                            <input type="hidden" name="categories[0][budget_id]" id="budget_id-0" class="budget_id-hidden">
                        </div>
                        <div class="form-group">
                            <label for="amount-0">Montant (€)</label>
                            <input type="number" step="0.01" name="categories[0][amount]" id="amount-0" required>
                        </div>
                        <div class="form-group">
                            <label for="description-0">Description (facultatif)</label>
                            <textarea name="categories[0][description]" id="description-0"></textarea>
                        </div>
                        <button type="button" class="btn btn-error remove-forecast">Supprimer</button>
                    </div>
                </div>

                <button type="button" id="add-forecast" class="btn">Ajouter une Réalisation</button>
                <button type="submit" name="add_realisation" class="btn btn-primary">Soumettre</button>
            </form>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé');
            const container = document.getElementById('forecasts-container');
            const addButton = document.getElementById('add-forecast');
            const periodSelect = document.getElementById('period_id');
            let counter = 1;

            // Fonction pour mettre à jour les options du select type
            function updateTypeOptions(categorySelect, typeSelect, budgetIdInput) {
                const category = categorySelect.value;
                const periodId = periodSelect.value;
                console.log('Mise à jour des types:', {
                    category,
                    periodId
                });

                typeSelect.innerHTML = '<option value="">Sélectionnez un type</option>';
                typeSelect.disabled = true;
                budgetIdInput.value = '';

                if (!category || !periodId) {
                    console.log('Catégorie ou période manquante, arrêt');
                    return;
                }

                console.log('Envoi requête AJAX à get_types.php');
                fetch(`../controllers/get_types.php?category=${encodeURIComponent(category)}&period_id=${encodeURIComponent(periodId)}`)
                    .then(response => {
                        console.log('Statut HTTP:', response.status, response.statusText);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Réponse de get_types.php:', JSON.stringify(data));
                        if (data.error) {
                            console.error('Erreur serveur:', data.error);
                            typeSelect.innerHTML = '<option value="">Erreur: ' + data.error + '</option>';
                            return;
                        }

                        if (!data.types || data.types.length === 0) {
                            console.log('Aucun type retourné');
                            typeSelect.innerHTML = '<option value="">Aucun type disponible</option>';
                            return;
                        }

                        data.types.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.type;
                            option.textContent = item.type;
                            option.dataset.budgetId = item.budget_id;
                            typeSelect.appendChild(option);
                        });
                        typeSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Erreur AJAX:', error.message);
                        typeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    });

                // Mettre à jour budget_id lors du changement de type
                typeSelect.addEventListener('change', () => {
                    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                    budgetIdInput.value = selectedOption ? selectedOption.dataset.budgetId || '' : '';
                    console.log('Type sélectionné:', typeSelect.value, 'Budget ID:', budgetIdInput.value);
                });
            }

            // Initialiser les écouteurs pour les selects existants
            function initializeCategoryListeners() {
                console.log('Initialisation des écouteurs pour category-select');
                document.querySelectorAll('.category-select').forEach(categorySelect => {
                    const index = categorySelect.id.split('-')[1];
                    const typeSelect = document.getElementById(`type-${index}`);
                    const budgetIdInput = document.getElementById(`budget_id-${index}`);

                    categorySelect.addEventListener('change', () => {
                        console.log('Catégorie changée:', categorySelect.value);
                        updateTypeOptions(categorySelect, typeSelect, budgetIdInput);
                    });

                    // Mettre à jour au chargement si catégorie et période sont sélectionnées
                    if (categorySelect.value && periodSelect.value) {
                        updateTypeOptions(categorySelect, typeSelect, budgetIdInput);
                    }
                });
            }

            // Appeler au chargement initial
            initializeCategoryListeners();

            // Mettre à jour les types lorsque la période change
            periodSelect.addEventListener('change', () => {
                console.log('Période changée:', periodSelect.value);
                document.querySelectorAll('.category-select').forEach(categorySelect => {
                    const index = categorySelect.id.split('-')[1];
                    const typeSelect = document.getElementById(`type-${index}`);
                    const budgetIdInput = document.getElementById(`budget_id-${index}`);
                    updateTypeOptions(categorySelect, typeSelect, budgetIdInput);
                });
            });

            // Ajouter une nouvelle réalisation
            addButton.addEventListener('click', function() {
                console.log('Ajout d\'une nouvelle réalisation');
                const newItem = document.createElement('div');
                newItem.className = 'forecast-item';
                newItem.innerHTML = `
                    <div class="form-group">
                        <label for="category-${counter}">Catégorie</label>
                        <select name="categories[${counter}][category]" id="category-${counter}" class="category-select" required>
                            <option value="">Sélectionnez une catégorie</option>
                            <option value="Recette">Recette</option>
                            <option value="Dépense">Dépense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type-${counter}">Type</label>
                        <select name="categories[${counter}][type]" id="type-${counter}" class="type-select" required>
                            <option value="">Sélectionnez un type</option>
                        </select>
                        <input type="hidden" name="categories[${counter}][budget_id]" id="budget_id-${counter}" class="budget_id-hidden">
                    </div>
                    <div class="form-group">
                        <label for="amount-${counter}">Montant (€)</label>
                        <input type="number" step="0.01" name="categories[${counter}][amount]" id="amount-${counter}" required>
                    </div>
                    <div class="form-group">
                        <label for="description-${counter}">Description (facultatif)</label>
                        <textarea name="categories[${counter}][description]" id="description-${counter}"></textarea>
                    </div>
                    <button type="button" class="btn btn-error remove-forecast">Supprimer</button>
                `;
                container.appendChild(newItem);

                // Initialiser l'écouteur pour le nouveau select
                const newCategorySelect = document.getElementById(`category-${counter}`);
                const newTypeSelect = document.getElementById(`type-${counter}`);
                const newBudgetIdInput = document.getElementById(`budget_id-${counter}`);
                newCategorySelect.addEventListener('change', () => {
                    console.log('Nouvelle catégorie changée:', newCategorySelect.value);
                    updateTypeOptions(newCategorySelect, newTypeSelect, newBudgetIdInput);
                });

                counter++;
            });

            // Supprimer une réalisation
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-forecast')) {
                    if (container.children.length > 1) {
                        e.target.parentElement.remove();
                    } else {
                        alert('Vous devez avoir au moins une réalisation.');
                    }
                }
            });
        });
    </script>
</body>

</html>