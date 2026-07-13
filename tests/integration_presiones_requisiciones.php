<?php
/**
 * Prueba de integración — Eslabón 1: Presiones y Requisiciones
 * ------------------------------------------------------------
 * Ejercita la API real por HTTP (sesión + CSRF + RBAC + alcance de obra)
 * contra una base de datos de PRUEBA. NUNCA apuntar a producción.
 *
 * Requisitos:
 *   - BD de prueba cargada (p. ej. tfc_test con el dump de DataBase/)
 *   - Servidor PHP sirviendo la raíz del repo, con las mismas variables DB_*
 *
 * Uso (Git Bash / CMD):
 *   DB_HOST=127.0.0.1 DB_NAME=tfc_test DB_USER=root DB_PASS= \
 *     php -S 127.0.0.1:8099 &                       # servidor bajo prueba
 *   DB_HOST=127.0.0.1 DB_NAME=tfc_test DB_USER=root DB_PASS= \
 *     php tests/integration_presiones_requisiciones.php
 *
 * Cubre:
 *   P1. Login correcto devuelve sesión + token CSRF
 *   P2. Crear presión -> success, estatus PENDIENTE, fecha del servidor, creador
 *   P3. La presión nueva aparece PRIMERO en el listado (orden, OBS-1)
 *   P4. Presión duplicada (obra+semana+día) -> success:false con mensaje
 *   P5. Mutación sin token CSRF -> 403
 *   P6. Crear requisición automática -> folio consecutivo, ABIERTO, creador
 *   P7. Requisición manual duplicada (clave+obra+folio) -> success:false
 *   P8. Residente sin permiso presiones.create -> 403
 *   P9. Residente crea requisición en SU obra -> success (alcance positivo)
 *   P10. Residente en obra NO asignada -> 403 (alcance de obra, P1-4)
 *   P11. Petición sin sesión -> 401
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$BASE = getenv('TF_BASE_URL') ?: 'http://127.0.0.1:8099';
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'tfc_test';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

if (stripos($DB_NAME, 'test') === false) {
    fwrite(STDERR, "ABORTADO: DB_NAME='$DB_NAME' no parece una BD de prueba (debe contener 'test').\n");
    exit(2);
}

$pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8",
    $DB_USER,
    $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ---------------------------------------------------------------------------
// Utilidades de aserción
// ---------------------------------------------------------------------------
$PASS = 0;
$FAIL = 0;

function check($cond, $label, $detail = '')
{
    global $PASS, $FAIL;
    if ($cond) {
        $PASS++;
        echo "  [OK]   $label\n";
    } else {
        $FAIL++;
        echo "  [FALLA] $label" . ($detail !== '' ? " -- $detail" : '') . "\n";
    }
}

// ---------------------------------------------------------------------------
// Cliente HTTP con cookies + CSRF (curl)
// ---------------------------------------------------------------------------
class ApiClient
{
    private $base;
    private $cookieFile;
    public $csrf = '';

    public function __construct($base)
    {
        $this->base = rtrim($base, '/');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'tfcook');
    }

    public function post($path, array $payload, $withCsrf = true)
    {
        $ch = curl_init($this->base . $path);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($withCsrf && $this->csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return [0, ['curl_error' => $err]];
        }
        $decoded = json_decode($body, true);
        return [$status, is_array($decoded) ? $decoded : ['raw' => $body]];
    }

    public function login($user, $password)
    {
        list($status, $data) = $this->post('/api/LoginAcces.php', [
            'user' => $user,
            'password' => $password,
        ], false);
        if (($data['bandera'] ?? '') === 'true') {
            $this->csrf = (string)($data['csrf'] ?? '');
        }
        return [$status, $data];
    }
}

// ---------------------------------------------------------------------------
// Semilla: usuarios y datos de prueba (idempotente)
// ---------------------------------------------------------------------------
const TEST_PWD = 'TfTest#2026!';
const SEM_TEST = '99';
const DIA_TEST = 'TESTDIA';
const CLAVE_TEST = 'TST';

function limpiar(PDO $pdo)
{
    $pdo->exec("DELETE FROM presiones WHERE presiones_semana = '" . SEM_TEST . "' AND presiones_dia LIKE '" . DIA_TEST . "%'");
    $pdo->exec("DELETE FROM requisiciones WHERE requisicion_Clave = '" . CLAVE_TEST . "'");
    $ids = $pdo->query("SELECT user_id FROM users WHERE user_nameUser LIKE 'tf_test_%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare("DELETE FROM user_obras WHERE user_id = ?")->execute([(int)$id]);
    }
    $pdo->exec("DELETE FROM users WHERE user_nameUser LIKE 'tf_test_%'");
    $pdo->exec("DELETE FROM login_attempts");
}

function crearUsuario(PDO $pdo, $username, $nombre, $roleId)
{
    $st = $pdo->prepare(
        "INSERT INTO users (user_nameUser, user_password, user_name, user_role_id, user_estatus)
         VALUES (?, ?, ?, ?, 'ACTIVO')"
    );
    $st->execute([$username, password_hash(TEST_PWD, PASSWORD_DEFAULT), $nombre, $roleId]);
    return (int)$pdo->lastInsertId();
}

limpiar($pdo);

// Dos obras activas con estado (requisito de construirNumeroRequisicion)
$obras = $pdo->query(
    "SELECT o.obras_id FROM obras o
     JOIN estadosobra e ON e.ciudadesObras_id = o.obras_cuidad
     WHERE o.obras_estatus = 'ACTIVO'
     ORDER BY o.obras_id LIMIT 2"
)->fetchAll(PDO::FETCH_COLUMN);

if (count($obras) < 2) {
    fwrite(STDERR, "ABORTADO: se requieren al menos 2 obras activas con estado en la BD de prueba.\n");
    exit(2);
}
$obraA = (int)$obras[0]; // asignada al residente
$obraB = (int)$obras[1]; // NO asignada al residente

$adminId = crearUsuario($pdo, 'tf_test_admin', 'Admin Prueba', 1);
$resId = crearUsuario($pdo, 'tf_test_res', 'Residente Prueba', 5);
$pdo->prepare("INSERT INTO user_obras (user_id, obras_id) VALUES (?, ?)")->execute([$resId, $obraA]);

echo "BD: $DB_NAME @ $DB_HOST | API: $BASE | obraA=$obraA obraB=$obraB\n\n";

// ---------------------------------------------------------------------------
// P1 — Login admin
// ---------------------------------------------------------------------------
echo "P1. Login y sesión\n";
$admin = new ApiClient($BASE);
list($st, $r) = $admin->login('tf_test_admin', TEST_PWD);
check($st === 200 && ($r['bandera'] ?? '') === 'true', 'login admin exitoso', json_encode($r));
check($admin->csrf !== '', 'login devuelve token CSRF');

// ---------------------------------------------------------------------------
// P2 — Crear presión
// ---------------------------------------------------------------------------
echo "\nP2. Crear presión (estatus inicial, fecha de servidor, creador)\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraA, 'semana' => SEM_TEST, 'dia' => DIA_TEST, 'alias' => 'Indirectos',
]);
$presionId = (int)($r['presion_id'] ?? 0);
check($st === 200 && ($r['success'] ?? false) === true && $presionId > 0, 'creación responde success', json_encode($r));

$row = $pdo->prepare("SELECT presiones_estatus, presiones_fechaCreacion, presiones_userCreado FROM presiones WHERE presiones_id = ?");
$row->execute([$presionId]);
$p = $row->fetch() ?: [];
check(($p['presiones_estatus'] ?? '') === 'PENDIENTE', "estatus inicial es PENDIENTE (badge 'En revisión')", json_encode($p));
check(($p['presiones_fechaCreacion'] ?? '') === date('Y-m-d'), 'fechaCreacion la pone el SERVIDOR (CURDATE)', (string)($p['presiones_fechaCreacion'] ?? 'null'));
check((string)($p['presiones_userCreado'] ?? '') === (string)$adminId, 'creador registrado (trazabilidad OBS-5)', json_encode($p));

// ---------------------------------------------------------------------------
// P3 — Orden del listado
// ---------------------------------------------------------------------------
echo "\nP3. La presión nueva aparece primero en el listado (OBS-1 orden)\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 1, 'obra' => $obraA, 'page' => 1, 'limite' => 20, 'serverSide' => 1,
]);
$primera = $r['rows'][0]['presiones_id'] ?? null;
check($st === 200 && (int)$primera === $presionId, 'rows[0] es la presión recién creada', "primera=$primera esperada=$presionId");

// ---------------------------------------------------------------------------
// P4 — Duplicado como advertencia
// ---------------------------------------------------------------------------
echo "\nP4. Presión duplicada (misma obra+semana+día)\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraA, 'semana' => SEM_TEST, 'dia' => DIA_TEST, 'alias' => 'Indirectos',
]);
check(($r['success'] ?? null) === false, 'duplicada responde success:false', json_encode($r));
check(stripos((string)($r['message'] ?? ''), 'ya existe') !== false, 'mensaje explica el duplicado');
$cnt = $pdo->query("SELECT COUNT(*) FROM presiones WHERE presiones_semana='" . SEM_TEST . "' AND presiones_dia='" . DIA_TEST . "' AND presiones_obra=$obraA")->fetchColumn();
check((int)$cnt === 1, 'no se insertó registro duplicado en BD');

// ---------------------------------------------------------------------------
// P5 — CSRF obligatorio
// ---------------------------------------------------------------------------
echo "\nP5. Mutación sin token CSRF\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraA, 'semana' => SEM_TEST, 'dia' => DIA_TEST . '2', 'alias' => 'Acarreo',
], false);
check($st === 403, 'rechazada con 403', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
// P6 — Requisición automática
// ---------------------------------------------------------------------------
echo "\nP6. Crear requisición automática (folio consecutivo, creador)\n";
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraA, 'nombreReq' => 'Prueba integración', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_TEST,
]);
$reqId = (int)($r['requisicion_id'] ?? 0);
check($st === 200 && ($r['success'] ?? false) === true && $reqId > 0, 'creación responde success', json_encode($r));
check(strpos((string)($r['numero_nuevo'] ?? ''), '-' . CLAVE_TEST . '-') !== false, 'número incluye la clave', (string)($r['numero_nuevo'] ?? ''));

$row = $pdo->prepare("SELECT requisicion_estatus, requisicion_userCreado, requisicion_userCreadoNombre, requisicion_Folio FROM requisiciones WHERE requisicion_id = ?");
$row->execute([$reqId]);
$q = $row->fetch() ?: [];
check(($q['requisicion_estatus'] ?? '') === 'ABIERTO', 'estatus inicial ABIERTO', json_encode($q));
check((int)($q['requisicion_userCreado'] ?? 0) === $adminId, 'creador (id) registrado');
check(($q['requisicion_userCreadoNombre'] ?? '') !== '', 'creador (nombre) registrado');

// Consecutivo: segunda requisición misma clave -> folio + 1
list($st, $r2) = $admin->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraA, 'nombreReq' => 'Prueba integración 2', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_TEST,
]);
$row->execute([(int)($r2['requisicion_id'] ?? 0)]);
$q2 = $row->fetch() ?: [];
check((int)($q2['requisicion_Folio'] ?? -99) === (int)($q['requisicion_Folio'] ?? 0) + 1, 'folio consecutivo automático', json_encode([$q['requisicion_Folio'] ?? null, $q2['requisicion_Folio'] ?? null]));

// ---------------------------------------------------------------------------
// P7 — Requisición manual duplicada
// ---------------------------------------------------------------------------
echo "\nP7. Requisición manual duplicada (clave+obra+folio)\n";
$manual = ['accion' => 7, 'obra' => $obraA, 'nombreReq' => 'Manual', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_TEST, 'folio' => 900, 'hoja' => 0];
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', $manual);
check(($r['success'] ?? false) === true, 'primera manual se crea', json_encode($r));
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', $manual);
check(($r['success'] ?? null) === false, 'segunda con mismo folio responde success:false', json_encode($r));

// ---------------------------------------------------------------------------
// P8–P10 — RBAC y alcance de obra (residente)
// ---------------------------------------------------------------------------
echo "\nP8. Residente sin permiso presiones.create\n";
$res = new ApiClient($BASE);
list($st, $r) = $res->login('tf_test_res', TEST_PWD);
check($st === 200 && ($r['bandera'] ?? '') === 'true', 'login residente exitoso', json_encode($r));
list($st, $r) = $res->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraA, 'semana' => SEM_TEST, 'dia' => DIA_TEST . '3', 'alias' => 'Acarreo',
]);
check($st === 403, 'crear presión -> 403 (sin permiso)', "status=$st " . json_encode($r));

echo "\nP9. Residente crea requisición en SU obra asignada\n";
list($st, $r) = $res->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraA, 'nombreReq' => 'Req residente', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_TEST,
]);
check($st === 200 && ($r['success'] ?? false) === true, 'creación permitida en obra asignada', "status=$st " . json_encode($r));

echo "\nP10. Residente en obra NO asignada (alcance de obra, P1-4)\n";
list($st, $r) = $res->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraB, 'nombreReq' => 'Req fuera de alcance', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_TEST,
]);
check($st === 403, 'creación bloqueada con 403', "status=$st " . json_encode($r));
$audit = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE audit_accion='access.denied' AND audit_userId=$resId")->fetchColumn();
check((int)$audit >= 1, 'intento registrado en audit_log (access.denied)');

// ---------------------------------------------------------------------------
// P11 — Sin sesión
// ---------------------------------------------------------------------------
echo "\nP11. Petición sin sesión\n";
$anon = new ApiClient($BASE);
list($st, $r) = $anon->post('/api/crud_Presiones.php', ['accion' => 1, 'obra' => $obraA], false);
check($st === 401, 'listar sin sesión -> 401', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
// Resumen y limpieza
// ---------------------------------------------------------------------------
limpiar($pdo);
echo "\n==============================\n";
echo "Resultado: $PASS OK, $FAIL fallas\n";
exit($FAIL === 0 ? 0 : 1);
