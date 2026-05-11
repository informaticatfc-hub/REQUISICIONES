<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function api_json_error($message, $statusCode = 400, $extra = array())
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge(array(
        'error' => true,
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

function api_get_current_user(PDO $conexion)
{
    static $cachedUser = null;

    if ($cachedUser !== null) {
        return $cachedUser;
    }

    if (empty($_SESSION['Usuario'])) {
        api_json_error('Sesion no valida', 401);
    }

    $consulta = 'SELECT * FROM `users` WHERE `user_nameUser` = ? LIMIT 1';
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($_SESSION['Usuario']));
    $user = $resultado->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        api_json_error('Usuario de sesion no encontrado', 401);
    }

    if (!isset($user['user_name']) && isset($user['user_nameUser'])) {
        $user['user_name'] = $user['user_nameUser'];
    }

    $cachedUser = $user;
    return $cachedUser;
}

function api_require_direction_access($user)
{
    if ((int)($user['user_directionAcess'] ?? 0) !== 1) {
        api_json_error('No tienes permisos para esta accion', 403);
    }
}