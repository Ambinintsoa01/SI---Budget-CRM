<?php
include("../utils/db_connection.php");
$db = Database::getInstance();
$departements = $db->query("SELECT * FROM departments")->fetchAll();
if (isset($_SESSION['department_id'])) {
    header('Location: home.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Budgétaire</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f7f8fa;
            margin: 0;
        }

        .login-container {
            max-width: 400px;
            /* Réduit pour un look plus compact */
            width: 100%;
            margin: 20px;
        }

        .login-container form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .login-container button {
            align-self: flex-end;
            /* Bouton aligné à droite */
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Connexion</h1>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">Identifiants incorrects !</div>
        <?php endif; ?>
        <form action="../controllers/authController.php" method="post">
            <div class="form-group">
                <label for="department">Département</label>
                <select name="department" id="department" required>
                    <option value="">Sélectionnez votre département</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn">Se connecter</button>
        </form>
    </div>
</body>

</html>