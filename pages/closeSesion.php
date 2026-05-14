<?php
/**
 * closeSesion.php — Fase 2
 * Cierra la sesion de forma segura: limpia $_SESSION,
 * invalida la cookie y registra el evento en audit_log.
 */
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

tf_session_start();

// Audit log de logout (si hay usuario en sesion)
if (!empty($_SESSION['Usuario'])) {
    try {
        $objeto = new Conexion();
        $pdo = $objeto->Conectar();
        tf_audit_log($pdo, 'logout', 'auth', $_SESSION['UsuarioId'] ?? null, $_SESSION['Usuario']);
        $pdo = null;
    } catch (Exception $e) {
        // silencioso: no impedir el logout por fallar la auditoria
    }
}

// Limpiar variables de sesion
$_SESSION = [];

// Borrar cookie de sesion
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
