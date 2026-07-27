<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$obra = (isset($_POST['obra'])) ? $_POST['obra'] : '';
$nombreReq  = (isset($_POST['nombreReq'])) ? $_POST['nombreReq'] : '';
$fechaReq =   (isset($_POST['fechaReq'])) ? $_POST['fechaReq'] : '';
$clave =   (isset($_POST['clave'])) ? $_POST['clave'] : '';
$IdReq =   (isset($_POST['IdReq'])) ? $_POST['IdReq'] : '';
$idHoja =   (isset($_POST['idHoja'])) ? $_POST['idHoja'] : '';
$id_presion = (isset($_POST['id_presion'])) ? $_POST['id_presion'] : ((isset($_POST['idPresion'])) ? $_POST['idPresion'] : '');

// --- RBAC + CSRF (Fase 3) ---
// case 8 = DELETE hoja (delete). El resto son lecturas.
// (cases 6 y 9 eliminados: crear requisicion vive en crud_Requisiciones.php
//  y duplicar hoja se retiro por decision de negocio OBS-4b.)
$accionInt = (int)$accion;
if ($accionInt === 8) {
    api_require_csrf($_POST);
    // Permiso real del catalogo RBAC (antes 'requisiciones.delete', inexistente -> 403 para todos)
    tf_require_permission($conexion, 'hojas.delete');
} else {
    tf_require_permission($conexion, 'requisiciones.view');
}

$currentUser = api_get_current_user($conexion);
$data = array();

function hojas_requisicion_obtener_obra_por_requisicion(PDO $conexion, $idReq)
{
    $consulta = "SELECT `requisicion_Obra` FROM `requisiciones` WHERE `requisicion_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idReq));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

function hojas_requisicion_obtener_obra_por_hoja(PDO $conexion, $idHoja)
{
    $consulta = "SELECT r.`requisicion_Obra`
        FROM `hojasrequisicion` h
        INNER JOIN `requisiciones` r ON r.`requisicion_id` = h.`hojaRequisicion_idReq`
        WHERE h.`hojaRequisicion_id` = ?
        LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idHoja));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

function hojas_requisicion_obtener_obra_por_presion(PDO $conexion, $idPresion)
{
    $consulta = "SELECT `presiones_obra` FROM `presiones` WHERE `presiones_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idPresion));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

switch ($accion) {
    case 1:
        $obraRequisicion = hojas_requisicion_obtener_obra_por_requisicion($conexion, $IdReq);
        if ($obraRequisicion === null) {
            tf_abort(404, 'Requisicion no encontrada');
        }
        tf_require_obra_access($conexion, $obraRequisicion, $currentUser);
        $consulta = "SELECT * FROM `hojasrequisicion` WHERE `hojaRequisicion_idReq` = :id_req ORDER BY CASE hojaRequisicion_estatus WHEN 'RECHAZADA' THEN 1 WHEN 'PENDIENTE' THEN 2 WHEN 'NUEVO' THEN 3 WHEN 'REVISION' THEN 4 WHEN 'LIGADA' THEN 5 WHEN 'AUTORIZADA' THEN 6 ELSE 7 END";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_req', (int)$IdReq, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        $data = array($currentUser);
        break;
    case 3:
        $consulta = "SELECT `obras_nombre` FROM `obras` WHERE `obras_id` = :obra";
        $resultado = $conexion->prepare($consulta);
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $resultado->bindValue(':obra', $obraId, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        $presionObra = hojas_requisicion_obtener_obra_por_presion($conexion, $id_presion);
        if ($presionObra === null) {
            tf_abort(404, 'Presion no encontrada');
        }
        tf_require_obra_access($conexion, $presionObra, $currentUser);
        $consulta = "SELECT SUM(`requisicion_total`) AS `totalPresion` FROM `requisiciones` WHERE `requisicion_idPresion` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_presion));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 5:
        $scope = tf_scope_obras_query($conexion, $currentUser);
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO'" . $scope['sql'] . " ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($scope['params']);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    // case 6 (crear requisicion) ELIMINADO: era una ruta legacy sin registro de
    // creador ni auditoria y sin llamadores en el frontend. El alta de
    // requisiciones vive unicamente en crud_Requisiciones.php (acciones 6/7).
    case 7:
        $obraId = hojas_requisicion_obtener_obra_por_requisicion($conexion, $IdReq);
        if ($obraId === null) {
            tf_abort(404, 'Requisicion no encontrada');
        }
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $consulta = "SELECT `requisicion_Numero`,`requisicion_Hojas` FROM `requisiciones` WHERE `requisicion_id` = :id_req";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_req', (int)$IdReq, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
   case 8:
    $obraHoja = hojas_requisicion_obtener_obra_por_hoja($conexion, $idHoja);
    if ($obraHoja === null) {
        tf_abort(404, 'Hoja no encontrada');
    }
    tf_require_obra_access($conexion, $obraHoja, $currentUser);
    $conexion->beginTransaction();
    try {
        $stmt1 = $conexion->prepare("DELETE FROM itemrequisicion WHERE itemRequisicion_idHoja = :idHoja");
        $stmt1->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
        $stmt1->execute();

        $stmt2 = $conexion->prepare("DELETE FROM hojasrequisicion WHERE hojaRequisicion_id = :idHoja");
        $stmt2->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
        $stmt2->execute();

        $conexion->commit();
        $data = ['status' => 'ok'];
        tf_audit_log($conexion, 'requisiciones.hoja.delete', 'hojasrequisicion', (int)$idHoja, null);
    } catch (Exception $e) {
        $conexion->rollBack();
        $data = ['status' => 'error', 'message' => $e->getMessage()];
    }
    break;

    // case 9 (duplicar hoja) ELIMINADO por decision de negocio (OBS-4b,
    // 2026-06-29): cada hoja es unica. El boton se retiro de la UI y este
    // endpoint ademas referenciaba columnas inexistentes en la BD real.

    case 10:
        // R-M2 / R-M3: Historial de estatus de una hoja (para badge popover y timeline)
        $idHojaInt = (int)$idHoja;
        if ($idHojaInt <= 0) {
            api_json_error('ID de hoja inválido', 422);
        }
        // Verificar acceso a la obra de la hoja
        $obraHoja = hojas_requisicion_obtener_obra_por_hoja($conexion, $idHojaInt);
        if ($obraHoja === null) {
            tf_abort(404, 'Hoja no encontrada');
        }
        tf_require_obra_access($conexion, $obraHoja, $currentUser);
        $stmt = $conexion->prepare(
            'SELECT
                log_id           AS id,
                log_estatusAntes AS antes,
                log_estatusNuevo AS nuevo,
                log_comentario   AS comentario,
                log_userName     AS usuario,
                log_createdAt    AS fecha
             FROM `hoja_estatus_log`
             WHERE `log_hojaId` = ?
             ORDER BY `log_createdAt` ASC'
        );
        $stmt->execute([$idHojaInt]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    default:
        api_json_error('Accion invalida', 400);
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
