<?php
/**
 * crud_admin.php â€” Endpoints de administracion (Fase 2)
 * ------------------------------------------------------------
 * Acciones disponibles:
 *   1 = listar usuarios (con su rol)
 *   2 = listar roles
 *   3 = crear usuario              (CSRF + admin.users.create)
 *   4 = actualizar usuario         (CSRF + admin.users.edit)
 *   5 = cambiar rol de usuario     (CSRF + admin.roles.manage)
 *   6 = activar/desactivar usuario (CSRF + admin.users.edit)
 *   7 = listar audit_log reciente  (admin.audit.view)
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

tf_session_start();
header('Content-Type: application/json; charset=utf-8');

$objeto = new Conexion();
$pdo = $objeto->Conectar();

$payload = api_get_request_data();
$accion  = isset($payload['accion']) ? (int)$payload['accion'] : 0;

// Carga del usuario actual (aborta 401 si no hay sesion)
$me = tf_current_user($pdo);

function tf_admin_can_assign_obra(array $user) {
    $roleCode = strtolower((string)($user['role']['code'] ?? ''));
    $dirAcc = (int)($user['user_directionAcess'] ?? 0);
    return in_array($roleCode, ['admin', 'director'], true) || $dirAcc === 1 || tf_has_permission('admin.users.edit', $user);
}

function tf_admin_forbidden(PDO $pdo, array $me, $reason, $message, array $extra = array()) {
    tf_audit_log($pdo, 'access.denied', 'admin', null, array_merge(array(
        'reason' => $reason,
        'accion' => (int)($_POST['accion'] ?? 0),
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'user_id' => (int)($me['user_id'] ?? 0),
        'role' => (string)($me['role']['code'] ?? ''),
    ), $extra));
    api_json_error($message, 403);
}
$data = [];

try {
switch ($accion) {
    case 1:
        // Listar usuarios
        // Construye la consulta directamente (sin depender de v_users_full)
        // para ser resiliente ante migraciones parcialmente aplicadas.
        tf_require_permission($pdo, 'admin.users.view');

        // Detectar columnas disponibles en users
        $_uCols = [];
        try {
            $__r = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'");
            $_uCols = array_flip($__r->fetchAll(PDO::FETCH_COLUMN, 0));
        } catch (Exception $__e) {}

        $_emailExpr   = (isset($_uCols['user_email']) && isset($_uCols['Email']))
                            ? "COALESCE(u.user_email, u.`Email`)"
                            : (isset($_uCols['user_email']) ? "u.user_email" : (isset($_uCols['Email']) ? "u.`Email`" : "NULL"));
        $_estatusExpr = isset($_uCols['user_estatus'])  ? "COALESCE(u.user_estatus,'ACTIVO')" : "'ACTIVO'";
        $_loginExpr   = isset($_uCols['user_lastLogin']) ? "u.user_lastLogin"  : "NULL";
        $_roleIdExpr  = isset($_uCols['user_role_id'])  ? "u.user_role_id"    : "NULL";
        $_obraIdExpr  = isset($_uCols['user_obra_id'])  ? "u.user_obra_id"    : "NULL";

        $_hasRbac = isset($_uCols['user_role_id']) && tf_rbac_tables_exist($pdo);
        $_roleJoin   = $_hasRbac ? "LEFT JOIN `roles` r ON r.role_id = u.user_role_id" : "";
        $_obraJoin   = isset($_uCols['user_obra_id']) ? "LEFT JOIN `obras` o ON o.obras_id = u.user_obra_id" : "";
        $_roleFields = $_hasRbac
            ? "r.role_codigo, r.role_nombre, r.role_nivel,
               (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.role_id) AS permisos_count,"
            : "NULL AS role_codigo, NULL AS role_nombre, NULL AS role_nivel, 0 AS permisos_count,";
        $_obraName = isset($_uCols['user_obra_id']) ? "o.obras_nombre" : "NULL";

        $stmt = $pdo->query(
            "SELECT u.user_id,
                    u.user_nameUser,
                    u.user_name,
                    {$_emailExpr}   AS user_email,
                    u.user_directionAcess,
                    {$_estatusExpr} AS user_estatus,
                    {$_loginExpr}   AS user_lastLogin,
                    {$_roleFields}
                    {$_roleIdExpr}  AS user_role_id,
                    {$_obraIdExpr}  AS user_obra_id,
                    {$_obraName}    AS user_obra_nombre
               FROM `users` u
               {$_roleJoin}
               {$_obraJoin}
               ORDER BY u.user_id DESC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2:
        // Listar roles
        tf_require_permission($pdo, 'admin.users.view');
        $stmt = $pdo->query(
            "SELECT role_id, role_codigo, role_nombre, role_nivel, role_descripcion, role_estatus,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = roles.role_id) AS permisos_count
               FROM `roles`
               ORDER BY role_nivel DESC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 3:
        // Crear usuario
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.users.create');

        $userName     = trim((string)($payload['user_nameUser'] ?? ''));
        $displayName  = trim((string)($payload['user_name']     ?? $userName));
        $email        = trim((string)($payload['user_email']    ?? ''));
        $password     =       (string)($payload['user_password'] ?? '');
        $roleId       = (int)($payload['user_role_id'] ?? 0);

        if ($userName === '' || $password === '' || $roleId <= 0) {
            api_json_error('Usuario, contrasena y rol son obligatorios', 422);
        }
        if (strlen($password) < 8) {
            api_json_error('La contrasena debe tener al menos 8 caracteres', 422);
        }

        // Validar unicidad
        $chk = $pdo->prepare("SELECT user_id FROM `users` WHERE user_nameUser = ? LIMIT 1");
        $chk->execute([$userName]);
        if ($chk->fetch()) {
            api_json_error('Ya existe un usuario con ese identificador', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare(
            "INSERT INTO `users`
                (user_nameUser, user_name, user_password, user_email,
                 user_role_id, user_estatus, user_directionAcess)
             VALUES (?, ?, ?, ?, ?, 'ACTIVO', 0)"
        );
        $ins->execute([$userName, $displayName, $hash, $email !== '' ? $email : null, $roleId]);
        $newId = (int)$pdo->lastInsertId();

        tf_audit_log($pdo, 'user.create', 'admin', $newId, [
            'user_nameUser' => $userName,
            'role_id'       => $roleId,
        ]);
        $data = ['ok' => true, 'user_id' => $newId];
        break;

    case 4:
        // Actualizar datos del usuario (nombre, email)
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.users.edit');

        $id          = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        $displayName = trim((string)($payload['user_name']  ?? ''));
        $email       = trim((string)($payload['user_email'] ?? ''));
        $newPassword =       (string)($payload['user_password'] ?? '');

        $fields = [];
        $args   = [];
        if ($displayName !== '') { $fields[] = 'user_name = ?';  $args[] = $displayName; }
        if ($email !== '')       { $fields[] = 'user_email = ?'; $args[] = $email; }
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) api_json_error('La contrasena debe tener al menos 8 caracteres', 422);
            $fields[] = 'user_password = ?'; $args[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        if (empty($fields)) api_json_error('Sin cambios a aplicar', 422);

        $args[] = $id;
        $upd = $pdo->prepare("UPDATE `users` SET " . implode(', ', $fields) . " WHERE user_id = ?");
        $upd->execute($args);

        // Log de campos modificados (sin exponer el hash)
        $loggedFields = array_map(function($f) {
            return preg_replace('/ = \?$/', '', $f);
        }, $fields);
        tf_audit_log($pdo, 'user.update', 'admin', $id, ['fields' => $loggedFields]);

        // Log dedicado si se cambiÃ³ la contraseÃ±a
        if ($newPassword !== '') {
            tf_audit_log($pdo, 'user.password_reset', 'admin', $id, [
                'reset_by' => $me['user_id'] ?? null,
            ]);
        }
        $data = ['ok' => true];
        break;

    case 5:
        // Cambiar rol
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.roles.manage');

        $id     = api_require_positive_int($payload['user_id']      ?? 0, 'ID invalido');
        $roleId = api_require_positive_int($payload['user_role_id'] ?? 0, 'Rol invalido');

        // No permitir auto-degradacion del unico admin
        if ((int)$me['user_id'] === $id) {
            tf_admin_forbidden($pdo, $me, 'self_role_change', 'No puedes cambiar tu propio rol', array('target_user_id' => $id));
        }

        $upd = $pdo->prepare("UPDATE `users` SET user_role_id = ? WHERE user_id = ?");
        $upd->execute([$roleId, $id]);

        tf_audit_log($pdo, 'user.role.change', 'admin', $id, ['new_role_id' => $roleId]);
        $data = ['ok' => true];
        break;

    case 6:
        // Activar / desactivar
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.users.edit');

        $id      = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        $estatus = ($payload['user_estatus'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';

        if ((int)$me['user_id'] === $id && $estatus === 'INACTIVO') {
            tf_admin_forbidden($pdo, $me, 'self_deactivate', 'No puedes desactivarte a ti mismo', array('target_user_id' => $id));
        }

        $upd = $pdo->prepare("UPDATE `users` SET user_estatus = ? WHERE user_id = ?");
        $upd->execute([$estatus, $id]);

        tf_audit_log($pdo, 'user.status.change', 'admin', $id, ['estatus' => $estatus]);
        $data = ['ok' => true];
        break;

    case 7:
        // Audit log con filtros opcionales: user_id, accion, fecha_desde, fecha_hasta, page
        tf_require_permission($pdo, 'admin.audit.view');
        $limit  = max(1, min((int)($payload['limite'] ?? 50), 200));
        $page   = max(1, (int)($payload['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        $filterUser  = trim((string)($payload['filter_user']  ?? ''));
        $filterAccion = trim((string)($payload['filter_accion'] ?? ''));
        $filterDesde = trim((string)($payload['filter_desde'] ?? ''));
        $filterHasta = trim((string)($payload['filter_hasta'] ?? ''));

        if ($filterUser !== '') {
            $where[]  = "(audit_userName LIKE ? OR audit_userId = ?)";
            $params[] = '%' . $filterUser . '%';
            $params[] = (int)$filterUser;
        }
        if ($filterAccion !== '') {
            $where[]  = "audit_accion LIKE ?";
            $params[] = '%' . $filterAccion . '%';
        }
        if ($filterDesde !== '') {
            $where[]  = "audit_createdAt >= ?";
            $params[] = $filterDesde . ' 00:00:00';
        }
        if ($filterHasta !== '') {
            $where[]  = "audit_createdAt <= ?";
            $params[] = $filterHasta . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total para paginaciÃ³n
        $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM `audit_log` $whereSql");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT audit_id, audit_userId, audit_userName, audit_accion,
                    audit_modulo, audit_entidadId, audit_detalle, audit_ip,
                    audit_createdAt
               FROM `audit_log`
               $whereSql
               ORDER BY audit_id DESC
               LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        $data = [
            'rows'    => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'   => $total,
            'page'    => $page,
            'pages'   => max(1, (int)ceil($total / $limit)),
            'limite'  => $limit,
        ];
        break;

    case 8:
        // Listar obras activas para asignacion
        tf_require_permission($pdo, 'admin.users.view');
        $stmt = $pdo->query(
            "SELECT obras_id, obras_nombre
               FROM `obras`
              WHERE COALESCE(obras_estatus, 'ACTIVO') = 'ACTIVO'
              ORDER BY obras_nombre ASC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 9:
        // Asignar obra unica a usuario (direccion/admin)
        tf_csrf_validate($payload, $pdo);
        if (!tf_admin_can_assign_obra($me)) {
            tf_admin_forbidden($pdo, $me, 'assign_obra_forbidden', 'No tienes permisos para asignar obras');
        }

        $id = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        $obraIdRaw = $payload['user_obra_id'] ?? null;
        $obraId = ($obraIdRaw === null || $obraIdRaw === '' || (int)$obraIdRaw <= 0) ? null : (int)$obraIdRaw;

        if ((int)$me['user_id'] === $id && $obraId === null) {
            api_json_error('No puedes dejarte sin obra desde esta accion', 422);
        }

        if ($obraId !== null) {
            $chk = $pdo->prepare("SELECT obras_id FROM `obras` WHERE obras_id = ? LIMIT 1");
            $chk->execute([$obraId]);
            if (!$chk->fetch()) {
                api_json_error('La obra seleccionada no existe', 404);
            }
        }

        $upd = $pdo->prepare("UPDATE `users` SET user_obra_id = ? WHERE user_id = ?");
        if ($obraId === null) {
            $upd->bindValue(1, null, PDO::PARAM_NULL);
        } else {
            $upd->bindValue(1, $obraId, PDO::PARAM_INT);
        }
        $upd->bindValue(2, $id, PDO::PARAM_INT);
        $upd->execute();

        tf_audit_log($pdo, 'user.obra.assign', 'admin', $id, ['user_obra_id' => $obraId]);
        $data = ['ok' => true, 'user_obra_id' => $obraId];
        break;

    // ----------------------------------------------------------------
    // GESTIÃ“N DE ROLES Y PERMISOS (acciones 10-15)
    // Requiere permiso admin.roles.manage
    // ----------------------------------------------------------------

    case 10:
        // Listar todos los permisos definidos
        tf_require_permission($pdo, 'admin.roles.manage');
        $stmt = $pdo->query(
            "SELECT permission_id, permission_codigo, permission_modulo, permission_descripcion
               FROM `permissions`
               ORDER BY permission_modulo ASC, permission_codigo ASC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 11:
        // IDs de permisos asignados a un rol especÃ­fico
        tf_require_permission($pdo, 'admin.roles.manage');
        $roleId = api_require_positive_int($payload['role_id'] ?? 0, 'Rol invalido');
        $stmt = $pdo->prepare(
            "SELECT permission_id FROM `role_permissions` WHERE role_id = ?"
        );
        $stmt->execute([$roleId]);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        break;

    case 12:
        // Conceder o revocar un permiso a un rol
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.roles.manage');

        $roleId = api_require_positive_int($payload['role_id']      ?? 0, 'Rol invalido');
        $permId = api_require_positive_int($payload['permission_id'] ?? 0, 'Permiso invalido');
        $grant  = !empty($payload['grant']);

        // Proteger rol admin: siempre tiene todos los permisos
        $chkR = $pdo->prepare("SELECT role_codigo FROM `roles` WHERE role_id = ? LIMIT 1");
        $chkR->execute([$roleId]);
        $rRow = $chkR->fetch(PDO::FETCH_ASSOC);
        if ($rRow && $rRow['role_codigo'] === 'admin') {
            tf_admin_forbidden($pdo, $me, 'admin_role_protected', 'El rol Administrador no puede modificarse', array('role_id' => $roleId));
        }

        if ($grant) {
            $ins = $pdo->prepare(
                "INSERT IGNORE INTO `role_permissions` (role_id, permission_id) VALUES (?, ?)"
            );
            $ins->execute([$roleId, $permId]);
            $accion = 'role.perm.grant';
        } else {
            $del = $pdo->prepare(
                "DELETE FROM `role_permissions` WHERE role_id = ? AND permission_id = ?"
            );
            $del->execute([$roleId, $permId]);
            $accion = 'role.perm.revoke';
        }

        tf_audit_log($pdo, $accion, 'admin', $roleId, [
            'role_id'       => $roleId,
            'permission_id' => $permId,
            'grant'         => $grant,
        ]);
        $data = ['ok' => true];
        break;

    case 13:
        // Crear nuevo rol
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.roles.manage');

        $codigo      = strtolower(trim((string)($payload['role_codigo']      ?? '')));
        $nombre      = trim((string)($payload['role_nombre']      ?? ''));
        $descripcion = trim((string)($payload['role_descripcion'] ?? ''));
        $nivel       = max(1, min(99, (int)($payload['role_nivel'] ?? 10)));

        if ($codigo === '' || $nombre === '') {
            api_json_error('Codigo y nombre son obligatorios', 422);
        }
        if (!preg_match('/^[a-z0-9_]+$/', $codigo)) {
            api_json_error('Codigo: solo letras minusculas, numeros y guion bajo', 422);
        }

        $chkC = $pdo->prepare("SELECT role_id FROM `roles` WHERE role_codigo = ? LIMIT 1");
        $chkC->execute([$codigo]);
        if ($chkC->fetch()) {
            api_json_error('Ya existe un rol con ese codigo', 409);
        }

        $ins = $pdo->prepare(
            "INSERT INTO `roles` (role_codigo, role_nombre, role_descripcion, role_nivel)
             VALUES (?, ?, ?, ?)"
        );
        $ins->execute([$codigo, $nombre, $descripcion ?: null, $nivel]);
        $newId = (int)$pdo->lastInsertId();

        tf_audit_log($pdo, 'role.create', 'admin', $newId, [
            'role_codigo' => $codigo,
            'role_nombre' => $nombre,
            'role_nivel'  => $nivel,
        ]);
        $data = ['ok' => true, 'role_id' => $newId];
        break;

    case 14:
        // Actualizar nombre/descripcion/nivel de un rol
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.roles.manage');

        $id          = api_require_positive_int($payload['role_id'] ?? 0, 'ID invalido');
        $nombre      = trim((string)($payload['role_nombre']      ?? ''));
        $descripcion = trim((string)($payload['role_descripcion'] ?? ''));
        $nivel       = max(1, min(99, (int)($payload['role_nivel'] ?? 0)));

        if ($nombre === '') api_json_error('El nombre es obligatorio', 422);

        $chkA = $pdo->prepare("SELECT role_codigo FROM `roles` WHERE role_id = ? LIMIT 1");
        $chkA->execute([$id]);
        $rowA = $chkA->fetch(PDO::FETCH_ASSOC);
        if ($rowA && $rowA['role_codigo'] === 'admin') {
            tf_admin_forbidden($pdo, $me, 'admin_role_protected', 'No se puede modificar el rol Administrador', array('role_id' => $id));
        }

        $upd = $pdo->prepare(
            "UPDATE `roles` SET role_nombre = ?, role_descripcion = ?, role_nivel = ?
             WHERE role_id = ?"
        );
        $upd->execute([$nombre, $descripcion ?: null, $nivel, $id]);

        tf_audit_log($pdo, 'role.update', 'admin', $id, [
            'role_nombre' => $nombre,
            'role_nivel'  => $nivel,
        ]);
        $data = ['ok' => true];
        break;

    case 15:
        // Activar / desactivar rol
        tf_csrf_validate($payload, $pdo);
        tf_require_permission($pdo, 'admin.roles.manage');

        $id      = api_require_positive_int($payload['role_id'] ?? 0, 'ID invalido');
        $estatus = ($payload['role_estatus'] ?? 'INACTIVO') === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

        $chkS = $pdo->prepare("SELECT role_codigo FROM `roles` WHERE role_id = ? LIMIT 1");
        $chkS->execute([$id]);
        $rowS = $chkS->fetch(PDO::FETCH_ASSOC);
        if ($rowS && $rowS['role_codigo'] === 'admin') {
            tf_admin_forbidden($pdo, $me, 'admin_role_protected', 'No se puede desactivar el rol Administrador', array('role_id' => $id));
        }

        $upd = $pdo->prepare("UPDATE `roles` SET role_estatus = ? WHERE role_id = ?");
        $upd->execute([$estatus, $id]);

        tf_audit_log($pdo, 'role.status.change', 'admin', $id, ['estatus' => $estatus]);
        $data = ['ok' => true];
        break;

    case 16:
        // Listar obras asignadas a un usuario (desde user_obras pivot)
        if (!tf_admin_can_assign_obra($me)) {
            tf_admin_forbidden($pdo, $me, 'assign_multi_obra_forbidden', 'Sin permiso');
        }
        $targetId = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        // Verificar que la tabla existe
        $pivotChk = $pdo->query("SHOW TABLES LIKE 'user_obras'")->fetchAll();
        if (!empty($pivotChk)) {
            $stmt = $pdo->prepare(
                "SELECT uo.obras_id, o.obras_nombre
                   FROM `user_obras` uo
                   LEFT JOIN `obras` o ON o.obras_id = uo.obras_id
                  WHERE uo.user_id = ?
                  ORDER BY o.obras_nombre ASC"
            );
            $stmt->execute([$targetId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // fallback: columna legacy
            $stmt = $pdo->prepare(
                "SELECT u.user_obra_id AS obras_id, o.obras_nombre
                   FROM `users` u LEFT JOIN `obras` o ON o.obras_id = u.user_obra_id
                  WHERE u.user_id = ? AND u.user_obra_id IS NOT NULL"
            );
            $stmt->execute([$targetId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        break;

    case 17:
        // Toggle asignaciÃ³n de obra a usuario (grant o revoke)
        tf_csrf_validate($payload, $pdo);
        if (!tf_admin_can_assign_obra($me)) {
            tf_admin_forbidden($pdo, $me, 'assign_multi_obra_forbidden', 'Sin permiso');
        }
        $targetId = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        $obraId   = api_require_positive_int($payload['obras_id'] ?? 0, 'Obra invalida');
        $grant    = !empty($payload['grant']);

        // Verificar que la obra existe
        $chkO = $pdo->prepare("SELECT obras_id FROM `obras` WHERE obras_id = ? LIMIT 1");
        $chkO->execute([$obraId]);
        if (!$chkO->fetch()) {
            api_json_error('La obra no existe', 404);
        }

        // Verificar que la tabla user_obras existe
        $pivotChk2 = $pdo->query("SHOW TABLES LIKE 'user_obras'")->fetchAll();
        if (empty($pivotChk2)) {
            api_json_error('Tabla user_obras no existe. Aplica la migraciÃ³n 008.', 500);
        }

        if ($grant) {
            $ins = $pdo->prepare(
                "INSERT IGNORE INTO `user_obras` (`user_id`, `obras_id`, `asignado_por`)
                 VALUES (?, ?, ?)"
            );
            $ins->execute([$targetId, $obraId, (int)$me['user_id']]);
            // Actualizar user_obra_id si el usuario no tiene uno aÃºn
            $pdo->prepare(
                "UPDATE `users` SET user_obra_id = ? WHERE user_id = ? AND (user_obra_id IS NULL OR user_obra_id = 0)"
            )->execute([$obraId, $targetId]);
        } else {
            $del = $pdo->prepare(
                "DELETE FROM `user_obras` WHERE user_id = ? AND obras_id = ?"
            );
            $del->execute([$targetId, $obraId]);
            // Si era la obra activa, limpiar user_obra_id
            $pdo->prepare(
                "UPDATE `users` SET user_obra_id = NULL WHERE user_id = ? AND user_obra_id = ?"
            )->execute([$targetId, $obraId]);
        }

        tf_audit_log($pdo, $grant ? 'user.obra.grant' : 'user.obra.revoke', 'admin', $targetId, [
            'obras_id' => $obraId,
        ]);
        $data = ['ok' => true, 'grant' => $grant, 'obras_id' => $obraId];
        break;

    default:
        api_json_error('Accion invalida', 400);
}
} catch (PDOException $e) {
    http_response_code(500);
    error_log('crud_admin PDOException: ' . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Error de base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    error_log('crud_admin error: ' . $e->getMessage());
    echo json_encode(['error' => true, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
$pdo = null;

