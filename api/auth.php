<?php
/**
 * api/auth.php — Helpers de API (Fase 2)
 * ------------------------------------------------------------
 * Mantiene la API publica original para no romper crud_*.php
 * existentes y delega en api/rbac.php para sesion / permisos.
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/rbac.php';

tf_session_start();

// ------------------------------------------------------------
// Helpers existentes (compatibilidad)
// ------------------------------------------------------------
function api_json_error($message, $statusCode = 400, $extra = array())
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array(
        'error'   => true,
        'message' => $message,
    ), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function api_get_request_data()
{
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody, true);
    return is_array($decoded) ? $decoded : array();
}

function api_require_positive_int($value, $message)
{
    $filtered = filter_var($value, FILTER_VALIDATE_INT, array(
        'options' => array('min_range' => 1),
    ));
    if ($filtered === false) {
        api_json_error($message, 422);
    }
    return (int)$filtered;
}

/**
 * Carga del usuario actual. Ahora incluye `role` y `permissions`.
 */
function api_get_current_user(PDO $conexion)
{
    return tf_current_user($conexion);
}

/**
 * Compat legacy: validar acceso a Direccion.
 */
function api_require_direction_access($user, ?PDO $conexion = null)
{
    $code = $user['role']['code'] ?? '';
    $hasFlag = (int)($user['user_directionAcess'] ?? 0) === 1;
    if ($code !== 'admin' && $code !== 'director' && !$hasFlag) {
        if ($conexion instanceof PDO) {
            tf_audit_log($conexion, 'access.denied', 'rbac', null, [
                'reason' => 'direction_access_required',
                'user_role' => (string)$code,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
        }
        api_json_error('No tienes permisos para esta accion', 403);
    }
}

/**
 * Nuevo helper: exige un permiso concreto (codigo modulo.accion).
 * Uso:  api_require_permission($conexion, 'requisiciones.create');
 */
function api_require_permission(PDO $conexion, $code)
{
    return tf_require_permission($conexion, $code);
}

/**
 * Nuevo helper: exige uno de los roles dados.
 * Uso:  api_require_role($conexion, ['admin','director']);
 */
function api_require_role(PDO $conexion, $codes)
{
    return tf_require_role($conexion, $codes);
}

/**
 * Validar CSRF para acciones que modifican datos.
 * Si el body trae _csrf o el header X-CSRF-Token, lo valida.
 * Para acciones GET-only no es necesario llamarlo.
 */
function api_require_csrf($payload = null, ?PDO $conexion = null)
{
    tf_csrf_validate($payload, $conexion);
}
