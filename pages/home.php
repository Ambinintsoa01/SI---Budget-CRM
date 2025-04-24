<?php
session_start();
require_once '../models/Auth.php';
require_once '../models/Budget.php';

$auth = new Auth();
$budget = new Budget();

$currentDepartment = $auth->getCurrentDepartment();
$summaryData = $budget->prepareSummaryData($auth->isFinance() ? null : $currentDepartment['id']);

// Initialiser les clés si elles n'existent pas
if (!isset($summaryData['periods'])) {
    $summaryData['periods'] = [];
}
if (!isset($summaryData['departments'])) {
    $summaryData['departments'] = [];
}
if (!isset($summaryData['categories'])) {
    $summaryData['categories'] = [];
}
if (!isset($summaryData['data'])) {
    $summaryData['data'] = [];
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
            <?php if ($auth->isFinance()): ?>
                <a href="validation.php" class="btn">
                    <i class="fas fa-user-secret"></i> Finance
                </a>
            <?php endif; ?>
            <?php if ($auth->isMarkNComm()): ?>
                <a href="marketing.php" class="btn">
                    <i class="fas fa-bullhorn"></i> Marketing&Comm
                </a>
                <a href="graph.php" class="btn">
                    <i class="fas fa-chart-bar"></i> Graphe&Stat
                </a>
            <?php endif; ?>
            <button id="exportPdf" class="btn">
                <i class="fas fa-file-pdf"></i> Exporter en PDF
            </button>
            <a href="../controllers/authController.php?logout=1" class="btn btn-error">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header>
            <h1>Gestion Budgétaire - <?= htmlspecialchars($currentDepartment['name']) ?></h1>
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
            <?php if ($auth->isFinance()): ?>
                <!-- Affichage pour la finance - tous les départements -->
                <?php foreach ($summaryData['departments'] as $department): ?>
                    <div class="department-section">
                        <h2>Département: <?= htmlspecialchars($department['name']) ?></h2>
                        <table class="budget-details" id="budget-table-dept-<?= $department['id'] ?>">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <?php foreach ($summaryData['periods'] as $period): ?>
                                        <th colspan="3"><?= htmlspecialchars($period['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th></th>
                                    <?php foreach ($summaryData['periods'] as $period): ?>
                                        <th>Prévision</th>
                                        <th>Réalisation</th>
                                        <th>Écart</th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summaryData['categories'] as $category): ?>
                                    <?php
                                    // Vérifier si la catégorie a des données pour ce département
                                    $hasData = false;
                                    foreach ($summaryData['periods'] as $period) {
                                        if (isset($summaryData['data'][$department['id']][$period['id']][$category])) {
                                            $hasData = true;
                                            break;
                                        }
                                    }
                                    if (!$hasData) continue;

                                    // Récupérer les types distincts pour cette catégorie et ce département
                                    $types = [];
                                    foreach ($summaryData['periods'] as $period) {
                                        if (isset($summaryData['data'][$department['id']][$period['id']][$category])) {
                                            foreach ($summaryData['data'][$department['id']][$period['id']][$category] as $type => $data) {
                                                if (!in_array($type, $types)) {
                                                    $types[] = $type;
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                    <!-- Ligne pour la catégorie -->
                                    <tr class="category-row">
                                        <td colspan="<?= count($summaryData['periods']) * 3 + 2 ?>">
                                            <strong><?= htmlspecialchars($category) ?></strong>
                                        </td>
                                    </tr>
                                    <!-- Lignes pour chaque type -->
                                    <?php foreach ($types as $type): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($type) ?></td>
                                            <?php foreach ($summaryData['periods'] as $period): ?>
                                                <?php
                                                $periodData = $summaryData['data'][$department['id']][$period['id']][$category][$type] ?? [
                                                    'forecast' => ['amount' => 0, 'description' => ''],
                                                    'realization' => ['amount' => 0, 'description' => '']
                                                ];
                                                $forecast = $periodData['forecast']['amount'];
                                                $realization = $periodData['realization']['amount'];
                                                if (strpos($category, 'Dépense') !== false || strpos($category, 'Charge') !== false) {
                                                    $ecart = $forecast - $realization;
                                                } else {
                                                    $ecart = $realization - $forecast;
                                                }
                                                ?>
                                                <td>
                                                    <?= number_format($forecast, 2, ',', ' ') ?> €
                                                    <?php if (!empty($periodData['forecast']['description'])): ?>
                                                        <br><span class="description"><?= htmlspecialchars($periodData['forecast']['description']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= number_format($realization, 2, ',', ' ') ?> €
                                                    <?php if (!empty($periodData['realization']['description'])): ?>
                                                        <br><span class="description"><?= htmlspecialchars($periodData['realization']['description']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="<?= $ecart < 0 ? 'negative' : ($ecart > 0 ? 'positive' : '') ?>">
                                                    <?= number_format($ecart, 2, ',', ' ') ?> €
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <!-- Ligne pour le solde final -->
                                <tr class="total-row">
                                    <td colspan="1"><strong>Solde final</strong></td>
                                    <?php
                                    $initialBalance = $summaryData['data'][$department['id']][array_key_first($summaryData['periods'])][array_key_first($summaryData['categories'])]['initial_balance'] ?? 0;
                                    $cumulativeBalance = $initialBalance;

                                    foreach ($summaryData['periods'] as $period):
                                        $totalRecette = 0;
                                        $totalDepense = 0;

                                        foreach ($summaryData['categories'] as $category) {
                                            if (isset($summaryData['data'][$department['id']][$period['id']][$category])) {
                                                foreach ($summaryData['data'][$department['id']][$period['id']][$category] as $type => $data) {
                                                    $amount = $data['realization']['amount'] ?? 0;
                                                    if (strpos($category, 'Recette') !== false || strpos($category, 'Revenue') !== false) {
                                                        $totalRecette += $amount;
                                                    } elseif (strpos($category, 'Dépense') !== false || strpos($category, 'Charge') !== false) {
                                                        $totalDepense += $amount;
                                                    }
                                                }
                                            }
                                        }

                                        $periodBalance = ($cumulativeBalance + $totalRecette) - $totalDepense;
                                        $cumulativeBalance = $periodBalance;
                                    ?>
                                        <td colspan="3" class="text-right">
                                            <strong><?= number_format($periodBalance, 2, ',', ' ') ?> €</strong>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Cas non-finance -->
                <div class="department-section">
                    <table id="budgetsTable" class="budget-details">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <?php foreach ($summaryData['periods'] as $period): ?>
                                    <th colspan="3"><?= htmlspecialchars($period['name'] ?? '') ?></th>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <th></th>
                                <?php foreach ($summaryData['periods'] as $period): ?>
                                    <th>Prévision</th>
                                    <th>Réalisation</th>
                                    <th>Écart</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaryData['categories'] as $category): ?>
                                <?php
                                // Récupérer les types distincts pour cette catégorie (tous départements confondus)
                                $types = [];
                                foreach ($summaryData['departments'] as $dept) {
                                    foreach ($summaryData['periods'] as $period) {
                                        if (isset($summaryData['data'][$dept['id']][$period['id']][$category])) {
                                            foreach ($summaryData['data'][$dept['id']][$period['id']][$category] as $type => $data) {
                                                if (!in_array($type, $types)) {
                                                    $types[] = $type;
                                                }
                                            }
                                        }
                                    }
                                }
                                ?>
                                <!-- Ligne pour la catégorie -->
                                <tr class="category-row">
                                    <td colspan="<?= count($summaryData['periods']) * 3 + 2 ?>">
                                        <strong><?= htmlspecialchars($category) ?></strong>
                                    </td>
                                </tr>
                                <!-- Lignes pour chaque type -->
                                <?php foreach ($types as $type): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($type) ?></td>
                                        <?php foreach ($summaryData['periods'] as $period): ?>
                                            <?php
                                            $forecastTotal = 0;
                                            $realizationTotal = 0;
                                            foreach ($summaryData['departments'] as $dept) {
                                                if (isset($summaryData['data'][$dept['id']][$period['id']][$category][$type])) {
                                                    $forecastTotal += $summaryData['data'][$dept['id']][$period['id']][$category][$type]['forecast']['amount'] ?? 0;
                                                    $realizationTotal += $summaryData['data'][$dept['id']][$period['id']][$category][$type]['realization']['amount'] ?? 0;
                                                }
                                            }
                                            if (strpos($category, 'Dépense') !== false || strpos($category, 'Charge') !== false) {
                                                $ecart = $forecastTotal - $realizationTotal;
                                            } else {
                                                $ecart = $realizationTotal - $forecastTotal;
                                            }
                                            ?>
                                            <td>
                                                <?= number_format($forecastTotal, 2, ',', ' ') ?> €
                                            </td>
                                            <td>
                                                <?= number_format($realizationTotal, 2, ',', ' ') ?> €
                                            </td>
                                            <td class="<?= $ecart < 0 ? 'negative' : ($ecart > 0 ? 'positive' : '') ?>">
                                                <?= number_format($ecart, 2, ',', ' ') ?> €
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <!-- Ligne pour le solde final -->
                            <tr class="total-row">
                                <td colspan="1"><strong>Solde final</strong></td>
                                <?php
                                $initialBalance = $summaryData['data'][array_key_first($summaryData['data'])][array_key_first($summaryData['periods'])][array_key_first($summaryData['categories'])]['initial_balance'] ?? 0;
                                $cumulativeBalance = $initialBalance;

                                foreach ($summaryData['periods'] as $period):
                                    $totalRecette = 0;
                                    $totalDepense = 0;

                                    foreach ($summaryData['departments'] as $dept) {
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
                                ?>
                                    <td colspan="3" class="text-right">
                                        <strong><?= number_format($periodBalance, 2, ',', ' ') ?> €</strong>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.getElementById('exportPdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();
            let currentY = 10;

            // Titre du document
            doc.setFontSize(16);
            doc.text('Résumé des Budgets', 10, currentY);
            currentY += 10;

            <?php if ($auth->isFinance()): ?>
                // Exporter chaque tableau pour les utilisateurs finance
                const tables = document.querySelectorAll('.budget-details');
                tables.forEach((table, index) => {
                    const deptName = table.closest('.department-section').querySelector('h2').textContent;
                    doc.setFontSize(12);
                    doc.text(deptName, 10, currentY);
                    currentY += 10;

                    doc.autoTable({
                        html: table,
                        startY: currentY,
                        styles: {
                            fontSize: 8,
                            cellPadding: 2
                        },
                        columnStyles: {
                            0: {
                                cellWidth: 30
                            }
                        }, // Ajuster la largeur de la colonne "Catégorie"
                        didParseCell: (data) => {
                            // Inclure les descriptions dans les cellules
                            if (data.cell.section === 'body' && data.cell.raw.querySelector('.description')) {
                                const description = data.cell.raw.querySelector('.description')?.textContent || '';
                                data.cell.text = [data.cell.text[0], description];
                            }
                        }
                    });
                    currentY = doc.lastAutoTable.finalY + 15;
                });
            <?php else: ?>
                // Exporter le tableau unique pour les utilisateurs non-finance
                const table = document.getElementById('budgetsTable');
                if (table) {
                    doc.autoTable({
                        html: table,
                        startY: currentY,
                        styles: {
                            fontSize: 8,
                            cellPadding: 2
                        },
                        columnStyles: {
                            0: {
                                cellWidth: 30
                            }
                        },
                        didParseCell: (data) => {
                            if (data.cell.section === 'body' && data.cell.raw.querySelector('.description')) {
                                const description = data.cell.raw.querySelector('.description')?.textContent || '';
                                data.cell.text = [data.cell.text[0], description];
                            }
                        }
                    });
                }
            <?php endif; ?>

            doc.save('budgets_' + new Date().toISOString().slice(0, 10) + '.pdf');
        });
    </script>
</body>

</html>