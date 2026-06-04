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

function tf_login_has_column(PDO $conexion, $columnName) {
    static $cache = [];

    if (array_key_exists($columnName, $cache)) {
        return $cache[$columnName];
    }

    try {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*)
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'users'
                AND COLUMN_NAME = ?"
        );
        $stmt->execute([$columnName]);
        $cache[$columnName] = ((int)$stmt->fetchColumn() > 0);
    } catch (Exception $e) {
        $cache[$columnName] = false;
    }

    return $cache[$columnName];
}

/**
 * Registra un intento fallido de login para la IP dada (si rate limiting está activo).
 */
function tf_login_record_fail(PDO $conexion, string $ip, bool $active): void {
    if (!$active) return;
    try {
        $conexion->prepare("INSERT INTO `login_attempts` (`attempt_ip`) VALUES (?)")
                 ->execute([$ip]);
    } catch (Exception $e) {
        // No bloquear el flujo si falla el INSERT
    }
}

/**
 * Limpia los intentos fallidos de login para la IP dada al loguearse con éxito.
 */
function tf_login_clear_fails(PDO $conexion, string $ip, bool $active): void {
    if (!$active) return;
    try {
        $conexion->prepare("DELETE FROM `login_attempts` WHERE `attempt_ip` = ?")
                 ->execute([$ip]);
    } catch (Exception $e) {
        // No bloquear el flujo
    }
}

tf_session_start();
tf_security_headers();
header('Content-Type: application/json; charset=utf-8');

$objeto   = new Conexion();
$conexion = $objeto->Conectar();

$hasUserRoleId      = tf_login_has_column($conexion, 'user_role_id');
$hasUserEstatus     = tf_login_has_column($conexion, 'user_estatus');
$hasEstadoUsuarioId = tf_login_has_column($conexion, 'id_estado_usuario');

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

// ── Rate limiting: máx. 5 intentos fallidos por IP en 15 minutos ─────────────
$clientIp = (string)($_SERVER['HTTP_X_FORWARDED_FOR']
    ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
$clientIp = trim($clientIp);

$rateLimitActive = false;
try {
    $chkTable = $conexion->query("SHOW TABLES LIKE 'login_attempts'")->fetchAll();
    if (!empty($chkTable)) {
        $rateLimitActive = true;
        $window    = 15 * 60; // 15 minutos en segundos
        $maxFails  = 5;
        $stmtCount = $conexion->prepare(
            "SELECT COUNT(*) FROM `login_attempts`
              WHERE `attempt_ip` = ? AND `attempt_at` > NOW() - INTERVAL ? SECOND"
        );
        $stmtCount->execute([$clientIp, $window]);
        $failCount = (int)$stmtCount->fetchColumn();

        if ($failCount >= $maxFails) {
            http_response_code(429);
            echo json_encode([
                'bandera'  => 'false',
                'user_id'  => 0,
                'bloqueado' => true,
                'mensaje'  => 'Demasiados intentos fallidos. Espera 15 minutos.',
            ], JSON_UNESCAPED_UNICODE);
            $conexion = null;
            exit;
        }
    }
} catch (Exception $e) {
    // Si la tabla no existe o hay error, continuamos sin rate limiting
    $rateLimitActive = false;
}
// ─────────────────────────────────────────────────────────────────────────────

$roleSelect = $hasUserRoleId ? '`user_role_id`' : 'NULL AS `user_role_id`';
$estatusSelect = $hasUserEstatus
    ? '`user_estatus`'
    : ($hasEstadoUsuarioId
        ? "CASE WHEN `id_estado_usuario` IN (2, 4) THEN 'INACTIVO' ELSE 'ACTIVO' END AS `user_estatus`"
        : "'ACTIVO' AS `user_estatus`");

$consulta = "SELECT `user_id`, `user_nameUser`, `user_password`, `user_directionAcess`,
                    $roleSelect,
                    $estatusSelect
             FROM `users`
             WHERE `user_nameUser` = ?
             LIMIT 1";
$stmt = $conexion->prepare($consulta);
$stmt->execute([$Usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Defensa contra timing attacks: si no existe el usuario, hacer un hash dummy
if (!$user) {
    password_verify($Contrasena, '$2y$10$2b2HqQ4mJm0S0d6Q8rM3Le4F4D0Q0Q4mKx0u4r8rN1M8F3kQY6e5W'); // pseudo-trabajo
    tf_audit_log($conexion, 'login.fail', 'auth', null, "user_not_found:$Usuario");
    tf_login_record_fail($conexion, $clientIp, $rateLimitActive);
    echo json_encode($dato, JSON_UNESCAPED_UNICODE);
    $conexion = null;
    exit;
}

// Usuario inactivo
if (isset($user['user_estatus']) && $user['user_estatus'] === 'INACTIVO') {
    tf_audit_log($conexion, 'login.fail', 'auth', (int)$user['user_id'], 'user_inactive');
    tf_login_record_fail($conexion, $clientIp, $rateLimitActive);
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
    tf_login_record_fail($conexion, $clientIp, $rateLimitActive);
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

// Auditar exito + limpiar intentos fallidos de esta IP
tf_audit_log($conexion, 'login.success', 'auth', (int)$user['user_id'], "role=$roleCode");
tf_login_clear_fails($conexion, $clientIp, $rateLimitActive);

$dato = [
    'bandera'  => 'true',
    'user_id'  => (int)$user['user_id'],
    'rol'      => $roleCode,
    'rolName'  => $roleName,
    'csrf'     => tf_csrf_token(),
];

echo json_encode($dato, JSON_UNESCAPED_UNICODE);
$conexion = null;
