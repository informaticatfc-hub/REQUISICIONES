<?php
session_start();

$requestDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '')), '/');
$loginPath = ($requestDir === '' || $requestDir === '.') ? 'pages/login.php' : 'login.php';

// Verifica si la clave "Usuario" está definida
if (!isset($_SESSION["Usuario"]) || $_SESSION["Usuario"] == "") {
    // Redirige a login.php si la sesión no está activa
    header("Location: " . $loginPath);
    exit(); // Usa exit() para asegurarte de que el script se detenga después de la redirección
}
?>