<?php
/**
 * validarSesion.php — Fase 2
 * ------------------------------------------------------------
 * Sesion segura (cookie httpOnly, SameSite=Lax, regeneracion
 * periodica). Cabeceras de seguridad activas. Si la sesion no
 * existe, redirige a login.
 *
 * Compatible con el comportamiento legacy: sigue verificando
 * $_SESSION["Usuario"].
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/api/rbac.php';

tf_session_start();
tf_security_headers();

$requestDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '')), '/');
$loginPath = ($requestDir === '' || $requestDir === '.') ? 'pages/login.php' : 'login.php';

if (!isset($_SESSION["Usuario"]) || $_SESSION["Usuario"] === "") {
    header("Location: " . $loginPath);
    exit();
}

// Generar CSRF token para que el bottom layout pueda exponerlo al JS
tf_csrf_token();
