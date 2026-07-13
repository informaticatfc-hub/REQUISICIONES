<?php
/**
 * crud_notifications.php — Conteos de acciones pendientes por rol.
 * Usado por el badge de notificaciones del topbar (polling 60 s).
 *
 * GET /api/crud_notifications.php
 * Responde siempre JSON { pendientes_autorizacion, pendientes_pago, total }
 */
header('Content-Type: application/json; charset=utf-8');

include_once 'conexion.php';
include_once 'rbac.php';

$obj = new Conexion();
$pdo = $obj->Conectar();

$user = tf_current_user($pdo);
if (!$user) {
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$roleCode   = $user['roleCode']    ?? '';
$userId     = (int)($user['user_id'] ?? 0);
$perms      = $user['permissions'] ?? [];

// Presiones pendientes de autorización (solo director/admin/desarrollador)
$pendAuth = 0;
if (
    in_array('presiones.authorize', $perms, true) ||
    in_array('*', $perms, true)
) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM `presiones` WHERE `presiones_estatus` = 'PENDIENTE'");
    $st->execute();
    $pendAuth = (int)$st->fetchColumn();
}

// Presiones autorizadas pendientes de pago (finanzas/admin)
$pendPago = 0;
if (
    in_array('finanzas.pagar', $perms, true) ||
    in_array('*', $perms, true)
) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM `presiones` WHERE `presiones_estatus` = 'AUTORIZADO'");
    $st->execute();
    $pendPago = (int)$st->fetchColumn();
}

echo json_encode([
    'pendientes_autorizacion' => $pendAuth,
    'pendientes_pago'         => $pendPago,
    'total'                   => $pendAuth + $pendPago,
], JSON_UNESCAPED_UNICODE);

$pdo = null;
