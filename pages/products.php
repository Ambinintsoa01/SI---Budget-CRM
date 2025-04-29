<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Product.php';

$auth = new Auth();
$product = new Product();

$currentDepartment = $auth->getCurrentDepartment();

$products = $product->getProductsWithDetails();

// Regrouper les produits par catégorie
$grouped = [];
foreach ($products as $product) {
    $grouped[$product['category_name']][] = $product;
}


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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
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
            <h1>Liste des produits</h1>
        </header><br>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php foreach ($grouped as $categoryName => $productList): ?>
            <div class="category-section">
                <h2><?= htmlspecialchars($categoryName) ?></h2>
                <div class="scroll-container">
                    <?php foreach ($productList as $product): ?>
                        <div class="product-card">
                            <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                            <div class="ventes">Ventes totales : <?= (int)$product['total_ventes'] ?></div>
                            <div class="ventes">Stock restant : <?= (int)$product['total_stock'] ?></div>
                            <span class="dropdown-toggle">▼</span>
                            <div class="description">
                                <?= nl2br(htmlspecialchars($product['action_descriptions'] ?? "Aucune remarque client.")) ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <script>
        // Toggle description on dropdown click
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const desc = toggle.nextElementSibling;
                desc.style.display = desc.style.display === 'block' ? 'none' : 'block';
            });
        });
    </script>
</body>

</html>