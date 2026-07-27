<?php
include_once 'conexion.php';
include_once 'auth.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = api_get_request_data();

$accion = isset($_POST['accion']) ? (int)$_POST['accion'] : 0;
$obra = $_POST['obra'] ?? 0;
$nombreReq = isset($_POST['nombreReq']) ? trim((string)$_POST['nombreReq']) : '';
$fechaReq = isset($_POST['fechaReq']) ? trim((string)$_POST['fechaReq']) : '';
$clave = isset($_POST['clave']) ? trim((string)$_POST['clave']) : '';
$folioReq = $_POST['folio'] ?? 0;
$Hoja = $_POST['hoja'] ?? 0;
$idReq = $_POST['idReq'] ?? 0;
$numReq = $_POST['numeroReq'] ?? '';
$id_presion = $_POST['id_presion'] ?? 0;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limite = isset($_POST['limite']) ? (int)$_POST['limite'] : 20;
$search = isset($_POST['search']) ? trim((string)$_POST['search']) : '';
$serverSide = isset($_POST['serverSide']) ? (int)$_POST['serverSide'] : 0;
$soloMias = isset($_POST['soloMias']) && (int)$_POST['soloMias'] === 1;

// --- RBAC + CSRF (Fase 3) --------------------------------------------------
// Lecturas exigen requisiciones.view; cada write exige su permiso especifico
// y valida CSRF antes de tocar la BD.
$writeActions = array(6, 7, 8, 9);
if (in_array($accion, $writeActions, true)) {
    api_require_csrf($_POST);
}
switch ($accion) {
    case 6: // create automatico
    case 7: // create manual
        tf_require_permission($conexion, 'requisiciones.create');
        break;
    case 8: // editar (antes: direction access)
        tf_require_permission($conexion, 'requisiciones.edit');
        break;
    case 9: // eliminar (antes: direction access)
        tf_require_permission($conexion, 'requisiciones.delete');
        break;
    default:
        tf_require_permission($conexion, 'requisiciones.view');
}

$currentUser = api_get_current_user($conexion);
$data = array();

function requisiciones_obtener_obra_por_requisicion(PDO $conexion, $idReq)
{
    $consulta = "SELECT `requisicion_Obra` FROM `requisiciones` WHERE `requisicion_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idReq));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

function requisiciones_obtener_obra_por_presion(PDO $conexion, $idPresion)
{
    $consulta = "SELECT `presiones_obra` FROM `presiones` WHERE `presiones_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idPresion));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

switch ($accion) {
    case 1:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $page = max(1, $page);
        $limite = max(5, min(100, $limite));
        $offset = ($page - 1) * $limite;

        $where = " WHERE `requisicion_Obra` = ?
                   AND COALESCE(`requisicion_estatus`, '') NOT IN ('CERRADA', 'CANCELADA')";
        $params = array($obraId);
        if ($soloMias && isset($currentUser['user_id'])) {
            $where .= " AND `requisicion_userCreado` = ?";
            $params[] = (int)$currentUser['user_id'];
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= " AND (
                `requisicion_Numero` LIKE ?
                OR `requisicion_Clave` LIKE ?
                OR `requisicion_Nombre` LIKE ?
            )";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sqlTotal = "SELECT COUNT(*) FROM `requisiciones`" . $where;
        $stTotal = $conexion->prepare($sqlTotal);
        $stTotal->execute($params);
        $total = (int)$stTotal->fetchColumn();

        $sqlStats = "SELECT
                SUM(CASE WHEN `requisicion_estatus` = 'ABIERTO' THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN `requisicion_estatus` IN ('CERRADO', 'CERRADA') THEN 1 ELSE 0 END) AS cerradas
            FROM `requisiciones`" . $where;
        $stStats = $conexion->prepare($sqlStats);
        $stStats->execute($params);
        $stats = $stStats->fetch(PDO::FETCH_ASSOC) ?: array('abiertas' => 0, 'cerradas' => 0);

        $consulta = "SELECT
                `requisicion_id`, `requisicion_Numero`, `requisicion_Clave`, `requisicion_Nombre`,
                `requisicion_estatus`,
                COALESCE((SELECT SUM(h.hojaRequisicion_total)
                          FROM hojasrequisicion h
                          WHERE h.hojaRequisicion_idReq = requisiciones.requisicion_id
                         ), 0) AS requisicion_montoTotal
            FROM `requisiciones`" . $where . "
            ORDER BY `requisicion_fechaSolicitud` DESC, `requisicion_Numero` DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($params);
        $dataArray = $resultado->fetchAll(PDO::FETCH_ASSOC);

        $rows = array();
        foreach ($dataArray as $row) {
            $rows[] = array(
                'requisicion_id' => $row['requisicion_id'],
                'requisicion_Numero' => $row['requisicion_Numero'],
                'requisicion_Clave' => $row['requisicion_Clave'],
                'requisicion_Nombre' => $row['requisicion_Nombre'],
                'requisicion_estatus' => $row['requisicion_estatus'],
                'requisicion_montoTotal' => (float)$row['requisicion_montoTotal'],
                'requisicion_EditShow' => false,
            );
        }

        if ($serverSide === 1) {
            $pages = (int)ceil($total / $limite);
            $data = array(
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'pages' => max(1, $pages),
                'limite' => $limite,
                'abiertas' => (int)($stats['abiertas'] ?? 0),
                'cerradas' => (int)($stats['cerradas'] ?? 0),
            );
        } else {
            // Compatibilidad legacy: devuelve arreglo plano cuando no viene serverSide
            $data = $rows;
        }
        break;

    case 2:
        $data = array($currentUser);
        break;

    case 3:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $consulta = "SELECT `obras_nombre`, `obra_automatico` FROM `obras` WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 4:
        $presionId = api_require_positive_int($id_presion, 'Presion invalida');
        $presionObraId = requisiciones_obtener_obra_por_presion($conexion, $presionId);
        if ($presionObraId === null) {
            tf_abort(404, 'Presion no encontrada');
        }
        tf_require_obra_access($conexion, $presionObraId, $currentUser);
        $consulta = "SELECT SUM(`requisicion_total`) AS `totalPresion` FROM `requisiciones` WHERE `requisicion_idPresion` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($presionId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 5:
        $scope = tf_scope_obras_query($conexion, $currentUser);
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO'" . $scope['sql'] . " ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($scope['params']);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 6:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        if ($nombreReq === '' || $fechaReq === '' || $clave === '') {
            api_json_error('Faltan datos para crear la requisicion', 422);
        }

        try {
            $conexion->beginTransaction();
            $obraInfo = obtenerPrefijoObra($conexion, $obraId);
            // Contador limpio por categoria (clave) + obra: toma el primer folio
            // libre empezando en 1, ignorando los folios basura del historico.
            $folio = obtenerSiguienteFolio($conexion, $obraId, $clave);
            $numeroRequisicion = construirNumeroRequisicion($obraInfo, $clave, $folio);
            // Guarda de unicidad del numero (por si un registro viejo ya ocupa
            // exactamente esa cadena): avanza el folio hasta encontrar uno libre.
            while (requisicionNumeroExiste($conexion, $numeroRequisicion)) {
                $folio++;
                $numeroRequisicion = construirNumeroRequisicion($obraInfo, $clave, $folio);
            }

            $consulta = "INSERT INTO `requisiciones` (
                `requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`,
                `requisicion_Obra`, `requisicion_userCreado`, `requisicion_userCreadoNombre`,
                `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_Hojas`,
                `requisicion_total`, `requisicion_estatus`
            ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, '0', '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array(
                $clave,
                $numeroRequisicion,
                $nombreReq,
                $obraId,
                (int)$currentUser['user_id'],
                $currentUser['user_name'] ?? $currentUser['user_nameUser'] ?? null,
                $fechaReq,
                $folio
            ));

            $newId = (int)$conexion->lastInsertId();
            $data = array(
                'success' => true,
                'requisicion_id' => $newId,
                'numero_nuevo' => $numeroRequisicion,
            );
            $conexion->commit();
            tf_audit_log($conexion, 'requisiciones.create.auto', 'requisiciones', $newId, array(
                'obra' => $obraId,
                'clave' => $clave,
                'numero' => $numeroRequisicion,
                'folio' => $folio,
            ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo crear la requisicion', 500, array('details' => $e->getMessage()));
        }
        break;

    case 7:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $folioManual = filter_var($folioReq, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        $hojasIniciales = filter_var($Hoja, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));

        if ($folioManual === false || $hojasIniciales === false || $nombreReq === '' || $fechaReq === '' || $clave === '') {
            api_json_error('Faltan datos validos para la requisicion manual', 422);
        }

        try {
            $conexion->beginTransaction();
            $obraInfo = obtenerPrefijoObra($conexion, $obraId);

            $consulta = "SELECT `requisicion_id`
                FROM `requisiciones`
                WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ? AND `requisicion_Folio` = ?
                LIMIT 1";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($clave, $obraId, $folioManual));
            if ($resultado->fetch(PDO::FETCH_ASSOC)) {
                $conexion->rollBack();
                $data = array(
                    'success' => false,
                    'message' => 'Ya existe una requisicion con ese folio para la misma obra y clave.',
                );
                break;
            }

            $numeroRequisicion = construirNumeroRequisicion($obraInfo, $clave, $folioManual);
            $consulta = "INSERT INTO `requisiciones` (
                `requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`,
                `requisicion_Obra`, `requisicion_userCreado`, `requisicion_userCreadoNombre`,
                `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_Hojas`,
                `requisicion_total`, `requisicion_estatus`
            ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array(
                $clave,
                $numeroRequisicion,
                $nombreReq,
                $obraId,
                (int)$currentUser['user_id'],
                $currentUser['user_name'] ?? $currentUser['user_nameUser'] ?? null,
                $fechaReq,
                $folioManual,
                $hojasIniciales
            ));

            $newId = (int)$conexion->lastInsertId();
            $data = array(
                'success' => true,
                'requisicion_id' => $newId,
                'numero_nuevo' => $numeroRequisicion,
            );
            $conexion->commit();
            tf_audit_log($conexion, 'requisiciones.create.manual', 'requisiciones', $newId, array(
                'obra' => $obraId,
                'clave' => $clave,
                'numero' => $numeroRequisicion,
                'folio' => $folioManual,
                'hojas_iniciales' => $hojasIniciales,
            ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo crear la requisicion manual', 500, array('details' => $e->getMessage()));
        }
        break;

    case 8:
        $requisicionId = api_require_positive_int($idReq, 'Requisicion invalida');
        $obraRequisicion = requisiciones_obtener_obra_por_requisicion($conexion, $requisicionId);
        if ($obraRequisicion === null) {
            tf_abort(404, 'Requisicion no encontrada');
        }
        tf_require_obra_access($conexion, $obraRequisicion, $currentUser);
        $numeroEditado = filter_var($numReq, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        if ($numeroEditado === false || $nombreReq === '') {
            api_json_error('Datos invalidos para editar la requisicion', 422);
        }

        $sqlGet = "SELECT `requisicion_Numero` FROM `requisiciones` WHERE `requisicion_id` = :idReq";
        $stmtGet = $conexion->prepare($sqlGet);
        $stmtGet->bindValue(':idReq', $requisicionId, PDO::PARAM_INT);
        $stmtGet->execute();
        $numeroActual = $stmtGet->fetchColumn();

        if ($numeroActual === false) {
            api_json_error('Requisicion no encontrada', 404);
        }

        $nuevoNumero = reemplazarUltimosDigitos($numeroActual, convertFolio((int)$numeroEditado));
        $sqlUpd = "UPDATE `requisiciones`
            SET `requisicion_Numero` = :newNumero, `requisicion_Nombre` = :newNombre
            WHERE `requisicion_id` = :idReq";
        $stmtUpd = $conexion->prepare($sqlUpd);
        $stmtUpd->bindValue(':newNumero', $nuevoNumero, PDO::PARAM_STR);
        $stmtUpd->bindValue(':newNombre', $nombreReq, PDO::PARAM_STR);
        $stmtUpd->bindValue(':idReq', $requisicionId, PDO::PARAM_INT);
        $stmtUpd->execute();

        $data = array(
            'success' => true,
            'idReq' => $requisicionId,
            'numero_anterior' => $numeroActual,
            'numero_nuevo' => $nuevoNumero,
            'nombre_nuevo' => $nombreReq,
            'rows_affected' => $stmtUpd->rowCount(),
        );
        tf_audit_log($conexion, 'requisiciones.edit', 'requisiciones', $requisicionId, array(
            'numero_anterior' => $numeroActual,
            'numero_nuevo' => $nuevoNumero,
            'nombre_nuevo' => $nombreReq,
        ));
        break;

    case 9:
        $requisicionId = api_require_positive_int($idReq, 'Requisicion invalida');
        $obraRequisicion = requisiciones_obtener_obra_por_requisicion($conexion, $requisicionId);
        if ($obraRequisicion === null) {
            tf_abort(404, 'Requisicion no encontrada');
        }
        tf_require_obra_access($conexion, $obraRequisicion, $currentUser);

        try {
            $conexion->beginTransaction();

            $consulta = "SELECT `hojaRequisicion_id` FROM `hojasrequisicion` WHERE `hojaRequisicion_idReq` = ?";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($requisicionId));
            $idsHojas = $resultado->fetchAll(PDO::FETCH_ASSOC);

            $deleteItems = $conexion->prepare("DELETE FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = ?");
            foreach ($idsHojas as $hoja) {
                $deleteItems->execute(array((int)$hoja['hojaRequisicion_id']));
            }

            $deleteHojas = $conexion->prepare("DELETE FROM `hojasrequisicion` WHERE `hojaRequisicion_idReq` = ?");
            $deleteHojas->execute(array($requisicionId));

            $deleteReq = $conexion->prepare("DELETE FROM `requisiciones` WHERE `requisicion_id` = ?");
            $deleteReq->execute(array($requisicionId));

            $conexion->commit();
            $data = array('status' => 'ok');
            tf_audit_log($conexion, 'requisiciones.delete', 'requisiciones', $requisicionId, array(
                'hojas_eliminadas' => count($idsHojas),
            ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo eliminar la requisicion', 500, array('details' => $e->getMessage()));
        }
        break;

    default:
        api_json_error('Accion invalida', 400);
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;

function convertFolio($folioInt)
{
    if ($folioInt < 10) {
        return '00' . $folioInt;
    }

    if ($folioInt < 100) {
        return '0' . $folioInt;
    }

    return (string)$folioInt;
}

function reemplazarUltimosDigitos($cadenaOriginal, $nuevoNumero)
{
    return preg_replace('/\d+$/', (string)$nuevoNumero, $cadenaOriginal);
}

function obtenerPrefijoObra(PDO $conexion, $obraId)
{
    $consulta = "SELECT `obras_nombre`, `ciudadesObras_codigo`
        FROM `obras`
        JOIN `estadosobra` ON `estadosobra`.`ciudadesObras_id` = `obras`.`obras_cuidad`
        WHERE `obras_id` = ?
        FOR UPDATE";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($obraId));
    $obraInfo = $resultado->fetch(PDO::FETCH_ASSOC);

    if (!$obraInfo) {
        throw new RuntimeException('La obra seleccionada no existe');
    }

    return $obraInfo;
}

function obtenerUltimoFolio(PDO $conexion, $obraId, $clave)
{
    $consulta = "SELECT `requisicion_Folio`
        FROM `requisiciones`
        WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ?
        ORDER BY `requisicion_Folio` DESC
        LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($clave, $obraId));
    $ultimoFolio = $resultado->fetchColumn();

    return $ultimoFolio === false ? -1 : (int)$ultimoFolio;
}

/**
 * Devuelve el primer folio libre (>= 1) para una categoria (clave) dentro de
 * una obra. Rellena huecos empezando en 1, por lo que ignora los folios basura
 * heredados (p.ej. 4363, 123654) y produce series limpias: 001, 002, 003...
 * Usa FOR UPDATE para bloquear las filas de esa clave+obra durante la
 * transaccion y evitar folios duplicados en creaciones concurrentes.
 */
function obtenerSiguienteFolio(PDO $conexion, $obraId, $clave)
{
    $consulta = "SELECT `requisicion_Folio`
        FROM `requisiciones`
        WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ?
        FOR UPDATE";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($clave, $obraId));

    $ocupados = array();
    foreach ($resultado->fetchAll(PDO::FETCH_COLUMN, 0) as $folio) {
        $ocupados[(int)$folio] = true;
    }

    $siguiente = 1;
    while (isset($ocupados[$siguiente])) {
        $siguiente++;
    }

    return $siguiente;
}

/**
 * Indica si ya existe una requisicion con ese numero exacto (unicidad de la
 * cadena visible, independiente del folio numerico).
 */
function requisicionNumeroExiste(PDO $conexion, $numero)
{
    $consulta = "SELECT 1 FROM `requisiciones` WHERE `requisicion_Numero` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($numero));

    return $resultado->fetchColumn() !== false;
}

function construirNumeroRequisicion($obraInfo, $clave, $folio)
{
    return $obraInfo['ciudadesObras_codigo'] . '-' . $obraInfo['obras_nombre'] . '-' . $clave . '-' . convertFolio((int)$folio);
}