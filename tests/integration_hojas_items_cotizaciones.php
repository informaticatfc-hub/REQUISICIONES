<?php
/**
 * Prueba de integración — Eslabón 2: Hojas, Ítems y Cotizaciones
 * ------------------------------------------------------------
 * Requiere BD de prueba (ver tests/integration_presiones_requisiciones.php)
 * con la migración 015 aplicada, y servidor PHP sirviendo la raíz del repo.
 *
 * Uso:
 *   DB_HOST=127.0.0.1 DB_NAME=tfc_test DB_USER=root DB_PASS= \
 *     php -S 127.0.0.1:8099 &
 *   DB_HOST=127.0.0.1 DB_NAME=tfc_test DB_USER=root DB_PASS= \
 *     php tests/integration_hojas_items_cotizaciones.php
 *
 * Cubre:
 *   H1.  Crear requisiciones base (obra A y obra B)
 *   H2.  Crear hoja con ítems -> NUEVO, creador, total calculado en SERVIDOR
 *   H3.  Hoja sin ítems -> 422
 *   H4.  Listar ítems de la hoja
 *   H5.  Agregar ítem -> total recalculado
 *   H6.  Anti-IDOR: editar ítem indicando una hoja a la que NO pertenece -> 404
 *   H7.  Eliminar ítem -> total recalculado
 *   H8.  Enviar hoja a REVISIÓN (contrato real del frontend) + hoja_estatus_log
 *   H9.  Cotizaciones: subir PDF y PNG, rechazar TXT, listar, servir con
 *        Content-Type correcto, eliminar (BD + archivo físico)
 *   H10. Ligar hoja a presión (permiso hojas.ligar) -> LIGADA + adeudo
 *   H11. Ligar hoja a presión de OTRA obra -> 422
 *   H12. Endpoints retirados (duplicar hoja / crear req legacy) -> 400
 *   H13. Residente: cotizaciones de obra ajena -> 403; de su obra -> 200
 *   H14. Borrar hoja exige hojas.delete (residente 403 / admin ok)
 *   H15. Eliminar requisición con permiso migrado (migración 015)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/lib/ApiClient.php';

$BASE = getenv('TF_BASE_URL') ?: 'http://127.0.0.1:8099';
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'tfc_test';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

if (stripos($DB_NAME, 'test') === false) {
    fwrite(STDERR, "ABORTADO: DB_NAME='$DB_NAME' no parece una BD de prueba.\n");
    exit(2);
}

$pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8",
    $DB_USER,
    $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$PASS = 0;
$FAIL = 0;
function check($cond, $label, $detail = '')
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [OK]   $label\n"; }
    else { $FAIL++; echo "  [FALLA] $label" . ($detail !== '' ? " -- $detail" : '') . "\n"; }
}

const TEST_PWD = 'TfTest#2026!';
const CLAVE_T2 = 'TS2';
const SEM_T2 = '98';
$UPLOADS = realpath(__DIR__ . '/..') . '/uploads/cotizaciones/';

function limpiar(PDO $pdo)
{
    global $UPLOADS;
    $reqIds = $pdo->query("SELECT requisicion_id FROM requisiciones WHERE requisicion_Clave='" . CLAVE_T2 . "'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($reqIds as $rid) {
        $hojas = $pdo->query("SELECT hojaRequisicion_id FROM hojasrequisicion WHERE hojaRequisicion_idReq=" . (int)$rid)->fetchAll(PDO::FETCH_COLUMN);
        foreach ($hojas as $hid) {
            $hid = (int)$hid;
            $pdo->exec("DELETE FROM itemrequisicion WHERE itemRequisicion_idHoja=$hid");
            $pdo->exec("DELETE FROM requisicionesligadas WHERE requisicionesLigadas_hojaID=$hid");
            $pdo->exec("DELETE FROM hojas_cotizaciones WHERE cotizacion_hoja_id=$hid");
            $pdo->exec("DELETE FROM hoja_estatus_log WHERE log_hojaId=$hid");
            $dir = $UPLOADS . $hid;
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') as $f) { @unlink($f); }
                @rmdir($dir);
            }
        }
        $pdo->exec("DELETE FROM hojasrequisicion WHERE hojaRequisicion_idReq=" . (int)$rid);
    }
    $pdo->exec("DELETE FROM requisiciones WHERE requisicion_Clave='" . CLAVE_T2 . "'");
    $pdo->exec("DELETE FROM presiones WHERE presiones_semana='" . SEM_T2 . "'");
    $ids = $pdo->query("SELECT user_id FROM users WHERE user_nameUser LIKE 'tf_test2_%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare("DELETE FROM user_obras WHERE user_id = ?")->execute([(int)$id]);
    }
    $pdo->exec("DELETE FROM users WHERE user_nameUser LIKE 'tf_test2_%'");
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

function hojaRow(PDO $pdo, $id)
{
    $st = $pdo->prepare("SELECT * FROM hojasrequisicion WHERE hojaRequisicion_id = ?");
    $st->execute([(int)$id]);
    return $st->fetch() ?: [];
}

limpiar($pdo);

$obras = $pdo->query(
    "SELECT o.obras_id FROM obras o
     JOIN estadosobra e ON e.ciudadesObras_id = o.obras_cuidad
     WHERE o.obras_estatus = 'ACTIVO' ORDER BY o.obras_id LIMIT 2"
)->fetchAll(PDO::FETCH_COLUMN);
$obraA = (int)$obras[0];
$obraB = (int)$obras[1];

$provId = (int)$pdo->query("SELECT proveedor_id FROM provedores WHERE proveedor_estatus='ACTIVO' ORDER BY proveedor_id LIMIT 1")->fetchColumn();
$emisorId = (int)$pdo->query("SELECT emisor_id FROM emisores ORDER BY emisor_id LIMIT 1")->fetchColumn();

$adminId = crearUsuario($pdo, 'tf_test2_admin', 'Admin Prueba 2', 1);
$resId = crearUsuario($pdo, 'tf_test2_res', 'Residente Prueba 2', 5);
$pdo->prepare("INSERT INTO user_obras (user_id, obras_id) VALUES (?, ?)")->execute([$resId, $obraA]);

// Archivos de prueba
$tmpDir = sys_get_temp_dir();
$pdfPath = $tmpDir . '/tf_cot_test.pdf';
file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
$pngPath = $tmpDir . '/tf_cot_test.png';
file_put_contents($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$txtPath = $tmpDir . '/tf_cot_test.txt';
file_put_contents($txtPath, "esto no es una cotizacion valida\n");

echo "BD: $DB_NAME @ $DB_HOST | API: $BASE | obraA=$obraA obraB=$obraB prov=$provId emisor=$emisorId\n\n";

$admin = new ApiClient($BASE);
list($st, $r) = $admin->login('tf_test2_admin', TEST_PWD);

// ---------------------------------------------------------------------------
echo "H1. Requisiciones base\n";
check($st === 200 && ($r['bandera'] ?? '') === 'true', 'login admin', json_encode($r));
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraA, 'nombreReq' => 'Eslabon2 A', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_T2,
]);
$reqA = (int)($r['requisicion_id'] ?? 0);
check($reqA > 0, 'requisicion en obra A creada', json_encode($r));
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', [
    'accion' => 6, 'obra' => $obraB, 'nombreReq' => 'Eslabon2 B', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_T2,
]);
$reqB = (int)($r['requisicion_id'] ?? 0);
check($reqB > 0, 'requisicion en obra B creada', json_encode($r));

// ---------------------------------------------------------------------------
echo "\nH2. Crear hoja con items (total de SERVIDOR, no del cliente)\n";
$items = [
    ['Unidad' => 'PZA', 'Nombre' => 'Cemento gris', 'UnitedPrice' => 100, 'IVA' => 32, 'Retenciones' => 0, 'Cantidad' => 2,
     'bandFlete' => 0, 'bandFisico' => 0, 'bandResico' => 0, 'bandISR' => 0],
    ['Unidad' => 'PZA', 'Nombre' => 'Varilla 3/8', 'UnitedPrice' => 50, 'IVA' => 8, 'Retenciones' => 0, 'Cantidad' => 1,
     'bandFlete' => 0, 'bandFisico' => 0, 'bandResico' => 0, 'bandISR' => 0],
];
$payloadHoja = [
    'accion' => 1, 'idReq' => $reqA, 'id_emisor' => $emisorId, 'id_prov' => $provId,
    'Total' => 999999, // total FALSO del cliente: el servidor debe recalcular
    'formaPago' => 'TRANSFERENCIA', 'fechaSolicitud' => date('Y-m-d'),
    'items' => json_encode($items), 'observaciones' => 'test eslabon 2', 'conceptoUnico' => '',
];
list($st, $r) = $admin->post('/api/crud_nueva_hoja.php', $payloadHoja);
$hojaA = is_numeric($r['raw'] ?? null) ? (int)$r['raw'] : (is_int($r) ? $r : (int)($r[0] ?? 0));
// La API responde el id como entero JSON plano
if ($hojaA === 0 && isset($r['raw'])) { $hojaA = (int)$r['raw']; }
check($st === 200 && $hojaA > 0, 'hoja creada (responde id)', json_encode($r));
$h = hojaRow($pdo, $hojaA);
check(($h['hojaRequisicion_estatus'] ?? '') === 'NUEVO', 'estatus inicial NUEVO');
check((int)($h['hojaRequisicion_numero'] ?? 0) === 1, 'numero de hoja consecutivo (1)');
check((int)($h['hojaRequisicion_userCreado'] ?? 0) === $adminId, 'creador registrado (OBS-5)', json_encode($h));
check((float)($h['hojaRequisicion_total'] ?? -1) === 290.0, 'total calculado en SERVIDOR (290.00, ignora 999999)', 'total=' . ($h['hojaRequisicion_total'] ?? 'null'));
check((float)($h['hojarequisicion_adeudo'] ?? -1) === 290.0, 'adeudo igual al total');

// ---------------------------------------------------------------------------
echo "\nH3. Hoja sin items -> 422\n";
$sinItems = $payloadHoja;
$sinItems['items'] = json_encode([]);
list($st, $r) = $admin->post('/api/crud_nueva_hoja.php', $sinItems);
check($st === 422, 'rechazada con 422', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
echo "\nH4. Listar items de la hoja\n";
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', ['accion' => 1, 'id_Hoja' => $hojaA]);
check($st === 200 && is_array($r) && count($r) === 2, 'devuelve los 2 items', 'count=' . (is_array($r) ? count($r) : 'n/a'));

// ---------------------------------------------------------------------------
echo "\nH5. Agregar item -> total recalculado\n";
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 6, 'id_Hoja' => $hojaA, 'unidad' => 'PZA', 'producto' => 'Clavos',
    'iva' => 1.60, 'retenciones' => 0, 'banderaFlete' => 0, 'banderaFisica' => 0,
    'banderaResico' => 0, 'banderaISR' => 0, 'precio' => 10, 'cantidad' => 1,
]);
$h = hojaRow($pdo, $hojaA);
check((float)($h['hojaRequisicion_total'] ?? -1) === 301.60, 'total = 301.60 tras agregar', 'total=' . ($h['hojaRequisicion_total'] ?? 'null'));
$itemNuevo = (int)$pdo->query("SELECT itemRequisicion_id FROM itemrequisicion WHERE itemRequisicion_idHoja=$hojaA ORDER BY itemRequisicion_id DESC LIMIT 1")->fetchColumn();

// ---------------------------------------------------------------------------
echo "\nH6. Anti-IDOR: editar item declarando una hoja ajena\n";
// Segunda hoja en la misma requisicion
$payloadHojaB = $payloadHoja;
$payloadHojaB['items'] = json_encode([$items[1]]);
list($st, $r) = $admin->post('/api/crud_nueva_hoja.php', $payloadHojaB);
$hojaB = (int)($r['raw'] ?? 0);
check($hojaB > 0, 'segunda hoja creada', json_encode($r));
// Intentar editar un item de hojaA declarando id_Hoja = hojaB
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 3, 'id_Hoja' => $hojaB, 'id' => $itemNuevo, 'unidad' => 'PZA', 'producto' => 'HACKED',
    'iva' => 0, 'retenciones' => 0, 'banderaFlete' => 0, 'banderaFisica' => 0,
    'banderaResico' => 0, 'banderaISR' => 0, 'precio' => 1, 'cantidad' => 1,
]);
check($st === 404, 'rechazado con 404 (item no pertenece a la hoja)', "status=$st " . json_encode($r));
$prod = $pdo->query("SELECT itemRequisicion_producto FROM itemrequisicion WHERE itemRequisicion_id=$itemNuevo")->fetchColumn();
check($prod === 'Clavos', 'el item NO fue modificado', "producto=$prod");

// ---------------------------------------------------------------------------
echo "\nH7. Eliminar item -> total recalculado\n";
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 4, 'id_Hoja' => $hojaA, 'id' => $itemNuevo,
]);
$h = hojaRow($pdo, $hojaA);
check((float)($h['hojaRequisicion_total'] ?? -1) === 290.0, 'total regresa a 290.00', 'total=' . ($h['hojaRequisicion_total'] ?? 'null'));

// ---------------------------------------------------------------------------
echo "\nH8. Enviar hoja a REVISION (contrato real del frontend: id_req = id de hoja)\n";
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 7, 'id_req' => $hojaA, 'comentarios' => 'lista para revision',
]);
check($st === 200 && ($r['status'] ?? '') === 'ok', 'responde ok', "status=$st " . json_encode($r));
$h = hojaRow($pdo, $hojaA);
check(($h['hojaRequisicion_estatus'] ?? '') === 'REVISION', 'estatus REVISION en BD');
$logCnt = (int)$pdo->query("SELECT COUNT(*) FROM hoja_estatus_log WHERE log_hojaId=$hojaA AND log_estatusNuevo='REVISION'")->fetchColumn();
check($logCnt === 1, 'transicion registrada en hoja_estatus_log');

// ---------------------------------------------------------------------------
echo "\nH9. Cotizaciones (PDF + imagen + rechazo + servir + eliminar)\n";
list($st, $r) = $admin->postMultipart('/api/crud_cotizaciones.php', ['accion' => 2, 'hoja_id' => $hojaA, 'nombre' => 'Cotizacion PDF'], ['cotizacion' => $pdfPath]);
$cotPdf = (int)($r['cotizacion_id'] ?? 0);
check($st === 200 && ($r['ok'] ?? false) === true && $cotPdf > 0, 'sube PDF', json_encode($r));
list($st, $r) = $admin->postMultipart('/api/crud_cotizaciones.php', ['accion' => 2, 'hoja_id' => $hojaA, 'nombre' => 'Cotizacion foto'], ['cotizacion' => $pngPath]);
$cotPng = (int)($r['cotizacion_id'] ?? 0);
check($st === 200 && ($r['ok'] ?? false) === true && $cotPng > 0, 'sube PNG (OBS-3: imagenes)', json_encode($r));
list($st, $r) = $admin->postMultipart('/api/crud_cotizaciones.php', ['accion' => 2, 'hoja_id' => $hojaA, 'nombre' => 'Archivo invalido'], ['cotizacion' => $txtPath]);
check($st === 422, 'rechaza TXT con 422', "status=$st " . json_encode($r));
list($st, $r) = $admin->post('/api/crud_cotizaciones.php', ['accion' => 1, 'hoja_id' => $hojaA]);
check(is_array($r) && count($r) === 2, 'lista 2 cotizaciones', 'count=' . (is_array($r) ? count($r) : 'n/a'));
list($st, $body, $ctype) = $admin->getRaw('/api/crud_cotizaciones.php?accion=4&cotizacion_id=' . $cotPdf);
check($st === 200 && strpos($ctype, 'application/pdf') !== false && strpos((string)$body, '%PDF') === 0, 'sirve PDF con Content-Type correcto', "ct=$ctype");
list($st, $body, $ctype) = $admin->getRaw('/api/crud_cotizaciones.php?accion=4&cotizacion_id=' . $cotPng);
check($st === 200 && strpos($ctype, 'image/png') !== false, 'sirve PNG con Content-Type correcto', "ct=$ctype");
$archivoPdf = $pdo->query("SELECT cotizacion_archivo FROM hojas_cotizaciones WHERE cotizacion_id=$cotPdf")->fetchColumn();
list($st, $r) = $admin->post('/api/crud_cotizaciones.php', ['accion' => 3, 'cotizacion_id' => $cotPdf]);
check(($r['ok'] ?? false) === true, 'elimina cotizacion PDF', json_encode($r));
check(!file_exists(realpath(__DIR__ . '/..') . '/uploads/' . $archivoPdf), 'archivo fisico eliminado');
list($st, $r) = $admin->post('/api/crud_cotizaciones.php', ['accion' => 1, 'hoja_id' => $hojaA]);
check(is_array($r) && count($r) === 1, 'queda 1 cotizacion');

// ---------------------------------------------------------------------------
echo "\nH10. Ligar hoja a presion (permiso real hojas.ligar)\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraA, 'semana' => SEM_T2, 'dia' => 'TESTDIA2', 'alias' => 'Indirectos',
]);
$presionA = (int)($r['presion_id'] ?? 0);
check($presionA > 0, 'presion en obra A creada', json_encode($r));
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 11, 'idPresion' => $presionA, 'id_req' => $reqA, 'id_Hoja' => $hojaA,
    'comentarios' => 'OK', 'total' => 290.0,
]);
check($st === 200 && ($r['status'] ?? '') === 'success', 'liga aceptada (antes 403 por permiso fantasma)', "status=$st " . json_encode($r));
$h = hojaRow($pdo, $hojaA);
check(($h['hojaRequisicion_estatus'] ?? '') === 'LIGADA', 'hoja LIGADA');
check((float)($h['hojarequisicion_adeudo'] ?? -1) === 290.0, 'adeudo registrado');
$liga = (int)$pdo->query("SELECT COUNT(*) FROM requisicionesligadas WHERE requisicionesLigadas_hojaID=$hojaA AND requisicionesLigada_presionID=$presionA")->fetchColumn();
check($liga === 1, 'registro en requisicionesligadas');

// ---------------------------------------------------------------------------
echo "\nH11. Ligar a presion de OTRA obra -> 422 (previene BD-4)\n";
list($st, $r) = $admin->post('/api/crud_Presiones.php', [
    'accion' => 3, 'obra' => $obraB, 'semana' => SEM_T2, 'dia' => 'TESTDIA2', 'alias' => 'Indirectos',
]);
$presionB = (int)($r['presion_id'] ?? 0);
list($st, $r) = $admin->post('/api/crud_items_requisiciones.php', [
    'accion' => 11, 'idPresion' => $presionB, 'id_req' => $reqA, 'id_Hoja' => $hojaB,
    'comentarios' => 'cruce', 'total' => 58.0,
]);
check($st === 422, 'rechazado con 422 (obras distintas)', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
echo "\nH12. Endpoints retirados responden 400\n";
list($st, $r) = $admin->post('/api/crud_hojas_requisicion.php', ['accion' => 9, 'idHoja' => $hojaA]);
check($st === 400, 'duplicar hoja (accion 9) -> 400', "status=$st " . json_encode($r));
list($st, $r) = $admin->post('/api/crud_hojas_requisicion.php', [
    'accion' => 6, 'obra' => $obraA, 'nombreReq' => 'legacy', 'fechaReq' => date('Y-m-d'), 'clave' => CLAVE_T2,
]);
check($st === 400, 'crear requisicion legacy (accion 6) -> 400', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
echo "\nH13. Residente y alcance de obra en cotizaciones\n";
$res = new ApiClient($BASE);
list($st, $r) = $res->login('tf_test2_res', TEST_PWD);
check($st === 200 && ($r['bandera'] ?? '') === 'true', 'login residente', json_encode($r));
// Hoja en obra B (creada por admin)
$payloadHojaC = $payloadHoja;
$payloadHojaC['idReq'] = $reqB;
$payloadHojaC['items'] = json_encode([$items[0]]);
list($st, $r) = $admin->post('/api/crud_nueva_hoja.php', $payloadHojaC);
$hojaC = (int)($r['raw'] ?? 0);
check($hojaC > 0, 'hoja en obra B creada (admin)', json_encode($r));
list($st, $r) = $res->post('/api/crud_cotizaciones.php', ['accion' => 1, 'hoja_id' => $hojaC]);
check($st === 403, 'residente NO lista cotizaciones de obra ajena (403)', "status=$st " . json_encode($r));
list($st, $r) = $res->postMultipart('/api/crud_cotizaciones.php', ['accion' => 2, 'hoja_id' => $hojaC, 'nombre' => 'intrusa'], ['cotizacion' => $pdfPath]);
check($st === 403, 'residente NO sube cotizacion a obra ajena (403)', "status=$st " . json_encode($r));
list($st, $r) = $res->post('/api/crud_cotizaciones.php', ['accion' => 1, 'hoja_id' => $hojaA]);
check($st === 200 && is_array($r), 'residente SI lista cotizaciones de su obra', "status=$st");

// ---------------------------------------------------------------------------
echo "\nH14. Borrar hoja exige hojas.delete\n";
list($st, $r) = $res->post('/api/crud_hojas_requisicion.php', ['accion' => 8, 'idHoja' => $hojaB]);
check($st === 403, 'residente -> 403', "status=$st " . json_encode($r));
list($st, $r) = $admin->post('/api/crud_hojas_requisicion.php', ['accion' => 8, 'idHoja' => $hojaB]);
check($st === 200 && ($r['status'] ?? '') === 'ok', 'admin -> ok', "status=$st " . json_encode($r));

// ---------------------------------------------------------------------------
echo "\nH15. Eliminar requisicion (permiso de migracion 015)\n";
list($st, $r) = $admin->post('/api/crud_Requisiciones.php', ['accion' => 9, 'idReq' => $reqB]);
check($st === 200 && ($r['status'] ?? '') === 'ok', 'admin elimina requisicion B', "status=$st " . json_encode($r));
$quedan = (int)$pdo->query("SELECT COUNT(*) FROM requisiciones WHERE requisicion_id=$reqB")->fetchColumn();
check($quedan === 0, 'requisicion eliminada en BD');

// ---------------------------------------------------------------------------
limpiar($pdo);
@unlink($pdfPath); @unlink($pngPath); @unlink($txtPath);
echo "\n==============================\n";
echo "Resultado: $PASS OK, $FAIL fallas\n";
exit($FAIL === 0 ? 0 : 1);
