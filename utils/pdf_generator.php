<?php
require_once '../models/Auth.php';
require_once '../models/Budget.php';
require_once '../utils/database.php';

Auth::check();

require_once '../vendor/autoload.php';

$database = new Database();
$db = $database->getConnection();

$budget = new Budget($db);

// Récupérer les données
if(Auth::isFinance()) {
    $budgets = $budget->getAll(date('Y'), date('m'));
} else {
    $budgets = $budget->getByDepartment(Auth::getDepartmentId(), date('Y'), date('m'));
}

// Créer le PDF
$mpdf = new \Mpdf\Mpdf();

$html = '<h1>Rapport Budgétaire</h1>';
$html .= '<table border="1" cellpadding="10" cellspacing="0">';
$html .= '<tr><th>Département</th><th>Solde Initial</th><th>Mois/Année</th><th>Prévisions</th><th>Réalisations</th><th>Statut</th></tr>';

while($row = $budgets->fetch(PDO::FETCH_ASSOC)) {
    $forecasts = $db->query("SELECT SUM(amount) as total FROM budget_forecasts WHERE budget_id = " . $row['id'])->fetch();
    $realizations = $db->query("SELECT SUM(amount) as total FROM budget_realizations WHERE budget_id = " . $row['id'])->fetch();
    
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($row['department_name']) . '</td>';
    $html .= '<td>' . number_format($row['initial_balance'], 2, ',', ' ') . ' €</td>';
    $html .= '<td>' . $row['month'] . '/' . $row['year'] . '</td>';
    $html .= '<td>' . number_format($forecasts['total'], 2, ',', ' ') . ' €</td>';
    $html .= '<td>' . number_format($realizations['total'], 2, ',', ' ') . ' €</td>';
    $html .= '<td>' . ucfirst($row['status']) . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';

$mpdf->WriteHTML($html);
$mpdf->Output('budget_report.pdf', 'D');
?>