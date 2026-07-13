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
// case 6 = INSERT requisicion (create); case 8 = DELETE hoja (delete); case 9 = duplicar hoja (create).
// El resto son lecturas.
$accionInt = (int)$accion;
if ($accionInt === 6) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'requisiciones.create');
} elseif ($accionInt === 8) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'requisiciones.delete');
} elseif ($accionInt === 9) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'requisiciones.create');
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
    case 6:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $consulta = "SELECT `obras_nombre`,`ciudadesObras_codigo` FROM `obras` JOIN estadosobra ON estadosobra.ciudadesObras_id = obras.obras_cuidad WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        if (count($data) === 0) {
            $data = array();
            break;
        }
        $numero_requesicion = $data[0]['ciudadesObras_codigo'] . "-" . $data[0]['obras_nombre'];
        $consulta = "SELECT * FROM `requisiciones` WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($clave, (int)$obra));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        if (count($data) == 0) {
            $numero_requesicion = $numero_requesicion . "-" . $clave . "-000";
            // Consulta para insertar datos en la tabla requisiciones
            $consulta = "INSERT INTO `requisiciones` (`requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`, `requisicion_Obra`, `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_total`, `requisicion_estatus`) 
            VALUES (NULL, :requisicion_clave, :requisicion_Numero, :requisicion_nombre, :requisicion_Obra , :requisicion_fechaSolicitud, '0', '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            // Vincular las variables a la consulta
            $resultado->bindParam(':requisicion_clave', $clave);
            $resultado->bindParam(':requisicion_nombre', $nombreReq);
            $resultado->bindParam(':requisicion_fechaSolicitud', $fechaReq);
            $resultado->bindParam(':requisicion_Obra', $obra);
            $resultado->bindParam(':requisicion_Numero', $numero_requesicion);
            // Ejecutar la consulta
            $resultado->execute();
        } else {
            $consulta = "SELECT `requisicion_Folio` FROM `requisiciones` WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ? ORDER BY `requisicion_Folio` ASC";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($clave, (int)$obra));
            $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
            $folio = $data[count($data) - 1]['requisicion_Folio'] + 1;
            $numero_requesicion = $numero_requesicion . "-" . $clave . "-" . convertFolio($folio);

            // Consulta para insertar datos en la tabla requisiciones
            $consulta = "INSERT INTO `requisiciones` (`requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`, `requisicion_Obra`, `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_total`, `requisicion_estatus`) 
             VALUES (NULL, :requisicion_clave, :requisicion_Numero, :requisicion_nombre, :requisicion_Obra , :requisicion_fechaSolicitud, :requisicion_Folio, '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            // Vincular las variables a la consulta
            $resultado->bindParam(':requisicion_clave', $clave);
            $resultado->bindParam(':requisicion_nombre', $nombreReq);
            $resultado->bindParam(':requisicion_fechaSolicitud', $fechaReq);
            $resultado->bindParam(':requisicion_Obra', $obra);
            $resultado->bindParam(':requisicion_Numero', $numero_requesicion);
            $resultado->bindParam(':requisicion_Folio', $folio);
            // Ejecutar la consulta
            $resultado->execute();
        }
        break;
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

    // C-M3: Duplicar hoja (copia cabecera + items dentro de la misma requisición)
    case 9:
    $obraHoja = hojas_requisicion_obtener_obra_por_hoja($conexion, $idHoja);
    if ($obraHoja === null) {
        tf_abort(404, 'Hoja no encontrada');
    }
    tf_require_obra_access($conexion, $obraHoja, $currentUser);
    $conexion->beginTransaction();
    try {
        // Obtener hoja origen
        $stHoja = $conexion->prepare("SELECT * FROM `hojasrequisicion` WHERE `hojaRequisicion_id` = :id");
        $stHoja->bindValue(':id', (int)$idHoja, PDO::PARAM_INT);
        $stHoja->execute();
        $hojaOrigen = $stHoja->fetch(PDO::FETCH_ASSOC);
        if (!$hojaOrigen) {
            $conexion->rollBack();
            tf_abort(404, 'Hoja no encontrada');
        }

        // Calcular el siguiente número de hoja en la misma requisición
        $stNum = $conexion->prepare(
            "SELECT COALESCE(MAX(hojaRequisicion_numero), 0) + 1 AS siguiente
             FROM `hojasrequisicion`
             WHERE `hojaRequisicion_idReq` = :idReq"
        );
        $stNum->bindValue(':idReq', (int)$hojaOrigen['hojaRequisicion_idReq'], PDO::PARAM_INT);
        $stNum->execute();
        $siguienteNum = (int)$stNum->fetchColumn();

        // Insertar hoja copia con estatus NUEVO
        $stInsHoja = $conexion->prepare(
            "INSERT INTO `hojasrequisicion` (
                `hojaRequisicion_idReq`, `hojaRequisicion_numero`, `hojaRequisicion_formaPago`,
                `hojaRequisicion_idEmisor`, `hojaRequisicion_proveedor`,
                `hojaRequisicion_total`, `hojaRequisicion_fechaPago`,
                `hojaRequisicion_transferencia`, `hojaRequisicion_observaciones`,
                `hojarequisicion_conceptoUnico`, `hojaRequisicion_estatus`
            ) VALUES (?,?,?,?,?,?,?,?,?,?,'NUEVO')"
        );
        $stInsHoja->execute([
            (int)$hojaOrigen['hojaRequisicion_idReq'],
            $siguienteNum,
            $hojaOrigen['hojaRequisicion_formaPago'],
            $hojaOrigen['hojaRequisicion_idEmisor'],
            $hojaOrigen['hojaRequisicion_proveedor'],
            $hojaOrigen['hojaRequisicion_total'],
            $hojaOrigen['hojaRequisicion_fechaPago'],
            $hojaOrigen['hojaRequisicion_transferencia'],
            $hojaOrigen['hojaRequisicion_observaciones'],
            $hojaOrigen['hojarequisicion_conceptoUnico'],
        ]);
        $newHojaId = (int)$conexion->lastInsertId();

        // Copiar items
        $stItems = $conexion->prepare(
            "SELECT `itemRequisicion_lote`, `itemRequisicion_unidad`, `itemRequisicion_producto`,
                    `itemRequisicion_cantidad`, `itemRequisicion_precio`, `itemRequisicion_iva`,
                    `itemRequisicion_total`, `itemRequisicion_retenciones`, `itemRequisicion_stotal`
             FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = :id"
        );
        $stItems->bindValue(':id', (int)$idHoja, PDO::PARAM_INT);
        $stItems->execute();
        $items = $stItems->fetchAll(PDO::FETCH_ASSOC);

        $stInsItem = $conexion->prepare(
            "INSERT INTO `itemrequisicion` (
                `itemRequisicion_idHoja`, `itemRequisicion_lote`, `itemRequisicion_unidad`,
                `itemRequisicion_producto`, `itemRequisicion_cantidad`, `itemRequisicion_precio`,
                `itemRequisicion_iva`, `itemRequisicion_total`, `itemRequisicion_retenciones`,
                `itemRequisicion_stotal`
            ) VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        foreach ($items as $item) {
            $stInsItem->execute([
                $newHojaId,
                $item['itemRequisicion_lote'],
                $item['itemRequisicion_unidad'],
                $item['itemRequisicion_producto'],
                $item['itemRequisicion_cantidad'],
                $item['itemRequisicion_precio'],
                $item['itemRequisicion_iva'],
                $item['itemRequisicion_total'],
                $item['itemRequisicion_retenciones'],
                $item['itemRequisicion_stotal'],
            ]);
        }

        // Actualizar contador de hojas en la requisición
        $stUpd = $conexion->prepare(
            "UPDATE `requisiciones` SET `requisicion_Hojas` = `requisicion_Hojas` + 1
             WHERE `requisicion_id` = ?"
        );
        $stUpd->execute([(int)$hojaOrigen['hojaRequisicion_idReq']]);

        $conexion->commit();
        $data = ['status' => 'ok', 'newHojaId' => $newHojaId, 'numero' => $siguienteNum];
        tf_audit_log($conexion, 'requisiciones.hoja.duplicar', 'hojasrequisicion', $newHojaId, [
            'origen_id' => (int)$idHoja,
        ]);
    } catch (Exception $e) {
        $conexion->rollBack();
        $data = ['status' => 'error', 'message' => $e->getMessage()];
    }
    break;

    case 10:
        // R-M2 / R-M3: Historial de estatus de una hoja (para badge popover y timeline)
        $idHojaInt = (int)$idHoja;
        if ($idHojaInt <= 0) {
            api_json_error('ID de hoja inválido', 422);
        }
        // Verificar acceso a la obra de la hoja
        $obraHoja = hojas_requisicion_obtener_obra_por_hoja($conexion, $idHojaInt);
        if ($obraHoja !== null) {
            tf_require_obra_access($conexion, $obraHoja, $currentUser);
        }
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

}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;


function convertFolio($folioInt)
{
    if ($folioInt < 10) {
        return "0" . "0" . $folioInt;
    } else if ($folioInt < 100) {
        return "0" . $folioInt;
    } else {
        return $folioInt;
    }
}
