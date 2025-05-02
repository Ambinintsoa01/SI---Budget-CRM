<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Budget.php';
require_once '../models/Product.php';
require_once '../models/CRM.php';

$crm = new CRM();
$auth = new Auth();
$budget = new Budget();
$product = new Product();

$currentDepartment = $auth->getCurrentDepartment();
$summaryData = $budget->prepareSummaryData($auth->isFinance() ? null : $currentDepartment['id']);

// Récupérer tous les produits, les ventes et le stock
$products = $product->getAllProduct();
$sales = $product->getSalesByProduct();
$stock = $product->getStockByProduct();

// Récupérer tous les c_reaction_id et leurs données CRM
$c_reaction_ids = $crm->getAllReactionIds($currentDepartment['id']);
$crm_data = [];
foreach ($c_reaction_ids as $reaction) {
    $crm_data[] = $crm->getCRM($reaction['id']);
}

// Log pour débogage
error_log("Products: " . print_r($products, true));
error_log("Sales: " . print_r($sales, true));
error_log("Stock: " . print_r($stock, true));
error_log("CRM Data: " . print_r($crm_data, true));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Gestion Budgétaire</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            <a href="graph.php" class="btn">
                <i class="fas fa-chart-bar"></i> Graphe&Stat
            </a>
            <a href="products.php" class="btn">
                <i class="fas fa-pizza-slice"></i> Products
            </a>
            <a href="../controllers/authController.php?logout=1" class="btn btn-error">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header>
            <h1>Graphe budgétaire - <?= htmlspecialchars($currentDepartment['name']) ?></h1>
        </header><br>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <main>
            <div class="pagination">
                <button id="before-crm" class="btn">Avant CRM</button>
                <button id="after-crm" class="btn">Après CRM</button>
            </div>
            <canvas id="salesChart" width="400" height="200"></canvas>
        </main>
    </div>

    <script>
        // Données passées depuis PHP
        const products = <?= json_encode($products) ?>;
        const salesData = <?= json_encode($sales) ?>;
        const stockData = <?= json_encode($stock) ?>;
        const crmData = <?= json_encode($crm_data) ?>;

        // Log pour débogage
        // console.log('Products:', products);
        // console.log('Sales:', salesData);
        // console.log('Stock:', stockData);
        // console.log('CRM Data:', crmData);

        // Préparer les ventes de base (avant CRM)
        function prepareBaseSales() {
            return products.map(product => {
                const sale = salesData.find(s => parseInt(s.id) === parseInt(product.id)) || { total_sales: 0 };
                const stock = stockData.find(s => parseInt(s.id) === parseInt(product.id)) || { stock: 0 };
                return {
                    id: product.id,
                    name: product.name,
                    sales: sale.total_sales, // Limiter à stock et 100
                };
            });
        }

        // Préparer les ventes après CRM avec boost
        function prepareCrmSales(baseSales) {
            // Créer une copie profonde pour éviter de modifier baseSales
            const sales = baseSales.map(sale => ({ ...sale }));
            const boostedProducts = new Set();

            // Collecter les product_id boostés par le CRM
            crmData.forEach(reactions => {
                if (reactions && Array.isArray(reactions)) {
                    reactions.forEach(reaction => {
                        if (reaction.product_id) {
                            boostedProducts.add(parseInt(reaction.product_id));
                            // console.log('Boosted Product ID:', parseInt(reaction.product_id));
                        }
                    });
                }
            });

            // Appliquer un boost aux produits concernés
            sales.forEach(sale => {
                const stock = stockData.find(s => parseInt(s.id) === parseInt(sale.id)) || { stock: 0 };
                if (boostedProducts.has(parseInt(sale.id))) {
                    const boostedSales = sale.sales * 2; // Boost x10
                    sale.sales = boostedSales; // Limiter à stock et 100
                    // console.log(`Boosted ${sale.name}: ${sale.sales} (from ${boostedSales})`);
                }
            });

            // console.log('CRM Sales:', sales);
            return sales;
        }

        // Initialiser Chart.js
        const ctx = document.getElementById('salesChart').getContext('2d');
        let chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Nombre de ventes',
                    data: [],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 200, // Maximum 100
                        ticks: {
                            stepSize: 25, // Graduations à 0, 25, 50, 75, 100
                            callback: function(value) {
                                return value;
                            }
                        },
                        title: {
                            display: true,
                            text: 'Nombre de ventes'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Produits'
                        }
                    }
                }
            }
        });

        // Vérifier si les données sont vides
        if (!(products.length && salesData.length)) {
            document.getElementById('salesChart').style.display = 'none';
            document.querySelector('main').innerHTML += '<p>Aucune donnée de vente disponible.</p>';
        } else {
            // Données pour les deux graphiques
            // console.log('Generating baseSales...');
            const baseSales = prepareBaseSales();
            // console.log('Base Sales:', baseSales);
            // console.log('Generating crmSales...');
            const crmSales = prepareCrmSales(baseSales);
            // console.log('CRM Sales:', crmSales);

            // Vérifier si des produits sont boostés
            if (crmData.every(reactions => !reactions || reactions.length === 0)) {
                // console.warn('Aucun produit boosté par le CRM.');
            }

            // Fonction pour mettre à jour le graphique
            function updateChart(sales, title) {
                // console.log(`Updating chart with ${title}:`, sales);
                chart.data.labels = sales.map(sale => sale.name);
                chart.data.datasets[0].data = sales.map(sale => sale.sales);
                chart.data.datasets[0].label = title;
                chart.update();
            }

            // Gestion des boutons de pagination
            document.getElementById('before-crm').addEventListener('click', () => {
                updateChart(baseSales, 'Ventes avant CRM');
                document.getElementById('before-crm').classList.add('active');
                document.getElementById('after-crm').classList.remove('active');
            });

            document.getElementById('after-crm').addEventListener('click', () => {
                updateChart(crmSales, 'Ventes après CRM');
                document.getElementById('after-crm').classList.add('active');
                document.getElementById('before-crm').classList.remove('active');
            });

            // Afficher le graphique "Avant CRM" par défaut
            // console.log('Displaying default chart: Avant CRM');
            updateChart(baseSales, 'Ventes avant CRM');
            document.getElementById('before-crm').classList.add('active');
        }
    </script>

    <style>
        .pagination {
            margin-bottom: 20px;
        }
        .pagination .btn {
            margin-right: 10px;
            padding: 10px 20px;
            cursor: pointer;
        }
        .pagination .btn.active {
            background-color: #4CAF50;
            color: white;
        }
        #salesChart {
            max-width: 1800px;
            margin: 0 auto;
        }
    </style>
</body>
</html>