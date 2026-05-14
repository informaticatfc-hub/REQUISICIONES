<?php
/**
 * LoginAcces.php — Fase 2
 * ------------------------------------------------------------
 * - Sesion segura (httpOnly, SameSite)
 * - Inicia sesion y carga rol + permisos
 * - Migracion progresiva de hash de contrasena
 * - Bitacora de auditoria del login (exito y fallo)
 * - Devuelve token CSRF para uso por el cliente
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/conexion.php';

tf_session_start();
tf_security_headers();
header('Content-Type: application/json; charset=utf-8');

$objeto   = new Conexion();
$conexion = $objeto->Conectar();

$payload    = json_decode(file_get_contents("php://input"), true) ?: [];
$Usuario    = isset($payload['user'])     ? trim((string)$payload['user'])     : '';
$Contrasena = isset($payload['password']) ?       (string)$payload['password']  : '';

$dato = [
    'bandera' => 'false',
    'user_id' => 0,
];

if ($Usuario === '' || $Contrasena === '') {
    echo json_encode($dato, JSON_UNESCAPED_UNICODE);
    $conexion = null;
    exit;
}

$consulta = "SELECT `user_id`, `user_nameUser`, `user_password`, `user_directionAcess`,
                    `user_role_id`, `user_estatus`
             FROM `users`
             WHERE `user_nameUser` = ?
             LIMIT 1";
$stmt = $conexion->prepare($consulta);
$stmt->execute([$Usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Defensa contra timing attacks: si no existe el usuario, hacer un hash dummy
if (!$user) {
    password_verify($Contrasena, '$2y$10$abcdefghijklmnopqrstuv'); // pseudo-trabajo
    tf_audit_log($conexion, 'login.fail', 'auth', null, "user_not_found:$Usuario");
    echo json_encode($dato, JSON_UNESCAPED_UNICODE);
    $conexion = null;
    exit;
}

// Usuario inactivo
if (isset($user['user_estatus']) && $user['user_estatus'] === 'INACTIVO') {
    tf_audit_log($conexion, 'login.fail', 'auth', (int)$user['user_id'], 'user_inactive');
    echo json_encode($dato, JSON_UNESCAPED_UNICODE);
    $conexion = null;
    exit;
}

$storedPassword = (string)$user['user_password'];
$isHashed       = password_get_info($storedPassword)['algo'] !== null;
$isValid        = $isHashed
    ? password_verify($Contrasena, $storedPassword)
    : hash_equals($storedPassword, $Contrasena);

if (!$isValid) {
    tf_audit_log($conexion, 'login.fail', 'auth', (int)$user['user_id'], 'bad_password');
    echo json_encode($dato, JSON_UNESCAPED_UNICODE);
    $conexion = null;
    exit;
}

// Migrar a hash si era texto plano o si el algoritmo es viejo
if (!$isHashed || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
    $newHash = password_hash($Contrasena, PASSWORD_DEFAULT);
    $upd = $conexion->prepare("UPDATE `users` SET `user_password` = ? WHERE `user_id` = ?");
    $upd->execute([$newHash, (int)$user['user_id']]);
}

// Cargar rol del usuario para guardar en sesion (si hay RBAC)
$roleCode = null;
$roleName = null;
if (tf_rbac_tables_exist($conexion) && !empty($user['user_role_id'])) {
    $r = $conexion->prepare("SELECT role_codigo, role_nombre FROM `roles` WHERE `role_id` = ?");
    $r->execute([$user['user_role_id']]);
    $rr = $r->fetch(PDO::FETCH_ASSOC);
    if ($rr) {
        $roleCode = $rr['role_codigo'];
        $roleName = $rr['role_nombre'];
    }
}
if ($roleCode === null) {
    // Fallback legacy
    $roleCode = ((int)($user['user_directionAcess'] ?? 0) === 1) ? 'director' : 'residente';
    $roleName = $roleCode === 'director' ? 'Direccion' : 'Residente';
}

// Sesion limpia: regenerar id para evitar fixation
session_regenerate_id(true);
$_SESSION['Usuario']           = $user['user_nameUser'];
$_SESSION['UsuarioId']         = (int)$user['user_id'];
$_SESSION['UsuarioNombre']     = $user['user_nameUser'];
$_SESSION['UsuarioRol']        = $roleName;
$_SESSION['UsuarioRolCode']    = $roleCode;
$_SESSION['UsuarioDirAccess']  = (int)($user['user_directionAcess'] ?? 0);
$_SESSION['_tf_created']       = time();

// Actualizar last login
try {
    $conexion->prepare("UPDATE `users` SET `user_lastLogin` = NOW() WHERE `user_id` = ?")
             ->execute([(int)$user['user_id']]);
} catch (Exception $e) {
    // Columna user_lastLogin podria no existir si la migracion aun no se aplico
}

// Auditar exito
tf_audit_log($conexion, 'login.success', 'auth', (int)$user['user_id'], "role=$roleCode");

$dato = [
    'bandera'  => 'true',
    'user_id'  => (int)$user['user_id'],
    'rol'      => $roleCode,
    'rolName'  => $roleName,
    'csrf'     => tf_csrf_token(),
];

echo json_encode($dato, JSON_UNESCAPED_UNICODE);
$conexion = null;
