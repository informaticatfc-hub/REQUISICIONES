<?php
/**
 * RBAC + Helpers de sesion segura
 * The Fuentes Workspace - Fase 2
 * ------------------------------------------------------------
 * Punto unico de entrada para:
 *   - tf_session_start()        : inicio de sesion seguro (cookie httpOnly, SameSite)
 *   - tf_csrf_token()           : obtener token CSRF de la sesion
 *   - tf_csrf_validate()        : validar token recibido (header o body)
 *   - tf_current_user($pdo)     : carga el usuario actual con su rol y permisos
 *   - tf_has_permission($code)  : verifica si el usuario tiene un permiso
 *   - tf_require_permission()   : aborta con 403 si no tiene el permiso
 *   - tf_require_role()         : aborta con 403 si no tiene el rol
 *   - tf_audit_log()            : registra accion en audit_log
 *
 * Defensivo: si las tablas RBAC todavia no existen (migracion no aplicada),
 * cae a modo legacy usando user_directionAcess.
 * ------------------------------------------------------------
 */

if (!defined('TF_RBAC_LOADED')) {
    define('TF_RBAC_LOADED', true);

    // --------------------------------------------------------
    // Configuracion de sesion segura
    // --------------------------------------------------------
    function tf_session_start() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Solo aplicar params antes de session_start
        $secure = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        );
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('TF_SESSID');
        session_start();

        // Rotar id periodicamente para mitigar fixation
        if (!isset($_SESSION['_tf_created'])) {
            $_SESSION['_tf_created'] = time();
            session_regenerate_id(true);
        } elseif (time() - $_SESSION['_tf_created'] > 1800) {
            // cada 30 min
            $_SESSION['_tf_created'] = time();
            session_regenerate_id(true);
        }
    }

    // --------------------------------------------------------
    // CSRF
    // --------------------------------------------------------
    function tf_csrf_token() {
        tf_session_start();
        if (empty($_SESSION['_tf_csrf'])) {
            $_SESSION['_tf_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_tf_csrf'];
    }

    /**
     * Valida un token CSRF.
     * Lo lee de (en orden):
     *   - Header X-CSRF-Token
     *   - $_POST['_csrf']  (body JSON o form)
     * Aborta con 403 si no coincide.
     */
    function tf_csrf_validate($payload = null) {
        tf_session_start();
        $expected = $_SESSION['_tf_csrf'] ?? '';
        if ($expected === '') {
            tf_abort(403, 'CSRF token ausente en sesion');
        }
        $given = '';
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $given = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (is_array($payload) && isset($payload['_csrf'])) {
            $given = (string)$payload['_csrf'];
        } elseif (isset($_POST['_csrf'])) {
            $given = (string)$_POST['_csrf'];
        }
        if (!hash_equals($expected, $given)) {
            tf_abort(403, 'CSRF token invalido');
        }
    }

    /**
     * Helper para imprimir el campo hidden en formularios.
     */
    function tf_csrf_field() {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(tf_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    // --------------------------------------------------------
    // Abort util
    // --------------------------------------------------------
    function tf_abort($code, $message) {
        http_response_code($code);
        // Si la peticion es JSON / API, responder JSON
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApi  = (
            strpos($accept, 'application/json') !== false
            || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
        );
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => true, 'message' => $message]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>Error ' . $code . '</title>';
            echo '<div style="font-family:Inter,system-ui;padding:40px;text-align:center">';
            echo '<h1 style="font-size:3rem;margin:0">Error ' . $code . '</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '<p><a href="/">Volver al inicio</a></p></div>';
        }
        exit;
    }

    // --------------------------------------------------------
    // Carga del usuario actual con su rol y permisos
    // --------------------------------------------------------
    /**
     * Retorna array:
     *   [
     *     'user_id' => int,
     *     'user_name' => string,
     *     'user_nameUser' => string,
     *     'user_directionAcess' => 0|1,
     *     'role' => ['code'=>'admin','name'=>'Administrador','level'=>100] | null,
     *     'permissions' => ['obras.view','requisiciones.create', ...]
     *   ]
     *
     * Si la sesion no es valida, aborta con 401.
     */
    function tf_current_user(PDO $pdo) {
        static $cached = null;
        if ($cached !== null) return $cached;

        tf_session_start();
        if (empty($_SESSION['Usuario'])) {
            tf_abort(401, 'Sesion no valida');
        }

        // 1) Cargar usuario base
        $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `user_nameUser` = ? LIMIT 1');
        $stmt->execute([$_SESSION['Usuario']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            tf_abort(401, 'Usuario no encontrado');
        }

        // Compatibilidad: algunas vistas usan user_name como display name
        if (!isset($user['user_name']) && isset($user['user_nameUser'])) {
            $user['user_name'] = $user['user_nameUser'];
        }

        // 2) Cargar rol (si la tabla existe)
        $user['role'] = null;
        $user['permissions'] = [];

        $hasRbac = tf_rbac_tables_exist($pdo);
        if ($hasRbac && !empty($user['user_role_id'])) {
            $stmt = $pdo->prepare('SELECT role_codigo, role_nombre, role_nivel FROM `roles` WHERE `role_id` = ? LIMIT 1');
            $stmt->execute([$user['user_role_id']]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $user['role'] = [
                    'code'  => $r['role_codigo'],
                    'name'  => $r['role_nombre'],
                    'level' => (int)$r['role_nivel'],
                ];
                // 3) Cargar permisos
                $stmt = $pdo->prepare(
                    'SELECT p.permission_codigo
                       FROM `permissions` p
                       INNER JOIN `role_permissions` rp ON rp.permission_id = p.permission_id
                       WHERE rp.role_id = ?'
                );
                $stmt->execute([$user['user_role_id']]);
                $user['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        }

        // 4) Fallback legacy: user_directionAcess=1 sin rol => director virtual
        if (!$user['role'] && (int)($user['user_directionAcess'] ?? 0) === 1) {
            $user['role'] = ['code' => 'director', 'name' => 'Direccion', 'level' => 80];
            $user['permissions'] = ['*']; // wildcard temporal
        } elseif (!$user['role']) {
            $user['role'] = ['code' => 'residente', 'name' => 'Residente', 'level' => 40];
            $user['permissions'] = ['obras.view', 'requisiciones.view', 'requisiciones.create',
                                    'requisiciones.edit', 'catalogos.view'];
        }

        $cached = $user;
        return $cached;
    }

    function tf_user_can_view_all_obras(array $user = null) {
        if ($user === null) {
            return false;
        }

        $roleCode = $user['role']['code'] ?? '';
        if (in_array($roleCode, ['admin', 'director'], true)) {
            return true;
        }

        return (int)($user['user_directionAcess'] ?? 0) === 1;
    }

    function tf_user_assigned_obra_id(array $user = null) {
        if ($user === null) {
            return null;
        }

        $obraId = $user['user_obra_id'] ?? $user['obra_id'] ?? $user['id_obra'] ?? null;
        $obraId = filter_var($obraId, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));

        return $obraId === false ? null : (int)$obraId;
    }

    function tf_require_obra_access(PDO $pdo, $obraId, $user = null) {
        $user = $user ?? tf_current_user($pdo);
        $obraId = filter_var($obraId, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));

        if ($obraId === false) {
            tf_abort(422, 'Obra invalida');
        }

        if (tf_user_can_view_all_obras($user)) {
            return (int)$obraId;
        }

        $assignedObraId = tf_user_assigned_obra_id($user);
        if ($assignedObraId === null) {
            tf_abort(403, 'No tienes una obra asignada');
        }

        if ((int)$obraId !== (int)$assignedObraId) {
            tf_abort(403, 'No tienes acceso a esta obra');
        }

        return (int)$obraId;
    }

    function tf_scope_obras_query(PDO $pdo, array $user = null) {
        $user = $user ?? tf_current_user($pdo);

        if (tf_user_can_view_all_obras($user)) {
            return array(
                'sql' => '',
                'params' => array(),
            );
        }

        $assignedObraId = tf_user_assigned_obra_id($user);
        if ($assignedObraId === null) {
            return array(
                'sql' => ' AND 1 = 0',
                'params' => array(),
            );
        }

        return array(
            'sql' => ' AND `obras_id` = ?',
            'params' => array($assignedObraId),
        );
    }

    function tf_rbac_tables_exist(PDO $pdo) {
        static $cache = null;
        if ($cache !== null) return $cache;
        try {
            $r = $pdo->query("SHOW TABLES LIKE 'roles'")->fetchAll();
            $cache = !empty($r);
        } catch (Exception $e) {
            $cache = false;
        }
        return $cache;
    }

    // --------------------------------------------------------
    // Permission checks
    // --------------------------------------------------------
    function tf_has_permission($code, $user = null) {
        if ($user === null) {
            // Necesitamos PDO; el caller debe pasar el user explicito si lo tiene
            return false;
        }
        if (in_array('*', $user['permissions'] ?? [], true)) return true;
        return in_array($code, $user['permissions'] ?? [], true);
    }

    function tf_require_permission(PDO $pdo, $code) {
        $user = tf_current_user($pdo);
        if (!tf_has_permission($code, $user)) {
            tf_abort(403, "No tienes permiso para: $code");
        }
        return $user;
    }

    function tf_require_role(PDO $pdo, $roleCodes) {
        if (!is_array($roleCodes)) $roleCodes = [$roleCodes];
        $user = tf_current_user($pdo);
        $code = $user['role']['code'] ?? '';
        if (!in_array($code, $roleCodes, true)) {
            tf_abort(403, 'Rol insuficiente para esta accion');
        }
        return $user;
    }

    function tf_require_min_level(PDO $pdo, $minLevel) {
        $user = tf_current_user($pdo);
        $level = (int)($user['role']['level'] ?? 0);
        if ($level < $minLevel) {
            tf_abort(403, 'Nivel de rol insuficiente');
        }
        return $user;
    }

    // --------------------------------------------------------
    // Audit log
    // --------------------------------------------------------
    function tf_audit_log(PDO $pdo, $accion, $modulo = null, $entidadId = null, $detalle = null) {
        try {
            if (!tf_rbac_tables_exist($pdo)) return; // tabla audit_log se crea junto con rbac
            $stmt = $pdo->prepare(
                'INSERT INTO `audit_log`
                    (audit_userId, audit_userName, audit_accion, audit_modulo, audit_entidadId, audit_detalle, audit_ip, audit_userAgent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $_SESSION['UsuarioId'] ?? null,
                $_SESSION['Usuario']   ?? null,
                $accion,
                $modulo,
                $entidadId,
                is_array($detalle) ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : $detalle,
                $_SERVER['REMOTE_ADDR']     ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (Exception $e) {
            error_log('audit_log fail: ' . $e->getMessage());
        }
    }

    // --------------------------------------------------------
    // Cabeceras de seguridad
    // --------------------------------------------------------
    function tf_security_headers() {
        if (headers_sent()) return;
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        // CSP: permite los CDN usados por el proyecto + source maps (.map)
        // Los .map se piden por fetch() y caen bajo connect-src, no script-src.
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.datatables.net https://cdnjs.cloudflare.com; "
             . "script-src-elem 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.datatables.net https://cdnjs.cloudflare.com; "
             . "style-src 'self' 'unsafe-inline' https://cdn.datatables.net https://fonts.googleapis.com; "
             . "style-src-elem 'self' 'unsafe-inline' https://cdn.datatables.net https://fonts.googleapis.com; "
             . "font-src 'self' https://fonts.gstatic.com https://cdn.datatables.net; "
             . "img-src 'self' data:; "
             . "connect-src 'self' https://cdn.datatables.net; "
             . "frame-ancestors 'self'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "object-src 'none'";
        header("Content-Security-Policy: $csp");
    }
}
