<?php
/**
 * crud_admin.php — Endpoints de administracion (Fase 2)
 * ------------------------------------------------------------
 * Acciones disponibles:
 *   1 = listar usuarios (con su rol)
 *   2 = listar roles
 *   3 = crear usuario              (CSRF + admin.users.create)
 *   4 = actualizar usuario         (CSRF + admin.users.edit)
 *   5 = cambiar rol de usuario     (CSRF + admin.roles.manage)
 *   6 = activar/desactivar usuario (CSRF + admin.users.edit)
 *   7 = listar audit_log reciente  (admin.audit.view)
 *   8 = listar obras activas       (admin.users.view)
 *   9 = asignar obra a usuario     (director/admin)
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

$data = [];

switch ($accion) {
    case 1:
        // Listar usuarios — Fase 5: usa la vista v_users_full (rol + permisos_count)
        tf_require_permission($pdo, 'admin.users.view');
        $stmt = $pdo->query(
            "SELECT v.user_id,
                    v.user_nameUser,
                    v.user_name,
                    v.email AS user_email,
                    v.user_directionAcess,
                    COALESCE(v.user_estatus,'ACTIVO') AS user_estatus,
                    v.user_lastLogin,
                    v.role_codigo,
                    v.role_nombre,
                    v.role_nivel,
                    v.permisos_count,
                    u.user_role_id,
                    u.user_obra_id,
                    o.obras_nombre AS user_obra_nombre
               FROM `v_users_full` v
               LEFT JOIN `users` u ON u.user_id = v.user_id
                LEFT JOIN `obras` o ON o.obras_id = u.user_obra_id
               ORDER BY v.user_id DESC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2:
        // Listar roles
        tf_require_permission($pdo, 'admin.users.view');
        $stmt = $pdo->query(
            "SELECT role_id, role_codigo, role_nombre, role_nivel, role_descripcion
               FROM `roles`
               WHERE role_estatus = 'ACTIVO'
               ORDER BY role_nivel DESC"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 3:
        // Crear usuario
        tf_csrf_validate($payload);
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
        tf_csrf_validate($payload);
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

        tf_audit_log($pdo, 'user.update', 'admin', $id, ['fields' => array_map(function($f){
            return preg_replace('/ = \?$/', '', $f);
        }, $fields)]);
        $data = ['ok' => true];
        break;

    case 5:
        // Cambiar rol
        tf_csrf_validate($payload);
        tf_require_permission($pdo, 'admin.roles.manage');

        $id     = api_require_positive_int($payload['user_id']      ?? 0, 'ID invalido');
        $roleId = api_require_positive_int($payload['user_role_id'] ?? 0, 'Rol invalido');

        // No permitir auto-degradacion del unico admin
        if ((int)$me['user_id'] === $id) {
            api_json_error('No puedes cambiar tu propio rol', 403);
        }

        $upd = $pdo->prepare("UPDATE `users` SET user_role_id = ? WHERE user_id = ?");
        $upd->execute([$roleId, $id]);

        tf_audit_log($pdo, 'user.role.change', 'admin', $id, ['new_role_id' => $roleId]);
        $data = ['ok' => true];
        break;

    case 6:
        // Activar / desactivar
        tf_csrf_validate($payload);
        tf_require_permission($pdo, 'admin.users.edit');

        $id      = api_require_positive_int($payload['user_id'] ?? 0, 'ID invalido');
        $estatus = ($payload['user_estatus'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';

        if ((int)$me['user_id'] === $id && $estatus === 'INACTIVO') {
            api_json_error('No puedes desactivarte a ti mismo', 403);
        }

        $upd = $pdo->prepare("UPDATE `users` SET user_estatus = ? WHERE user_id = ?");
        $upd->execute([$estatus, $id]);

        tf_audit_log($pdo, 'user.status.change', 'admin', $id, ['estatus' => $estatus]);
        $data = ['ok' => true];
        break;

    case 7:
        // Audit log reciente
        tf_require_permission($pdo, 'admin.audit.view');
        $limit = max(1, min((int)($payload['limite'] ?? 100), 500));
        $stmt = $pdo->query(
            "SELECT audit_id, audit_userId, audit_userName, audit_accion,
                    audit_modulo, audit_entidadId, audit_detalle, audit_ip,
                    audit_createdAt
               FROM `audit_log`
               ORDER BY audit_id DESC
               LIMIT $limit"
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        tf_csrf_validate($payload);
        if (!tf_admin_can_assign_obra($me)) {
            api_json_error('No tienes permisos para asignar obras', 403);
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

    default:
        api_json_error('Accion invalida', 400);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
$pdo = null;
