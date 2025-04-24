<?php
require_once '../models/Auth.php';

session_start();

$auth = new Auth();

if (isset($_POST['login'])) {
    $departmentName = $_POST['department'];
    $departmentPassword = $_POST['password'];
    if ($auth->login($departmentName, $departmentPassword)) {
        header('Location: ../pages/home.php');
        exit();
    } else {
        $_SESSION['error'] = "Département invalide";
        header('Location: ../pages/login.php?error=1');
        exit();
    }
}

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: ../pages/login.php');
    exit();
}
?>