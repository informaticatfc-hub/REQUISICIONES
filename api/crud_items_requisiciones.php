<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$idReq = (isset($_POST['id_req'])) ? $_POST['id_req'] : '';
$idHoja = (isset($_POST['id_Hoja'])) ? $_POST['id_Hoja'] : '';
$unidad = (isset($_POST['unidad'])) ? $_POST['unidad'] : '';
$producto = (isset($_POST['producto'])) ? $_POST['producto'] : '';
$iva = (isset($_POST['iva'])) ? $_POST['iva'] : '';
$retenciones = (isset($_POST['retenciones'])) ? $_POST['retenciones'] : '';
$banderaFlete = (isset($_POST['banderaFlete'])) ? (int)$_POST['banderaFlete'] : '';
$banderaFisica = (isset($_POST['banderaFisica'])) ? (int)$_POST['banderaFisica'] : '';
$banderaResico = (isset($_POST['banderaResico'])) ? (int)$_POST['banderaResico'] : '';
$banderaISR = (isset($_POST['banderaISR'])) ?  (int)$_POST['banderaISR'] : '';
$precio = (isset($_POST['precio'])) ? $_POST['precio'] : '';
$cantidad = (isset($_POST['cantidad'])) ? $_POST['cantidad'] : '';
$total = (isset($_POST['total'])) ? $_POST['total'] : '';
$id = (isset($_POST['id'])) ? $_POST['id'] : '';
$obra = (isset($_POST['obra'])) ? $_POST['obra'] : '';
$idPresion = (isset($_POST['idPresion'])) ? $_POST['idPresion'] : '';
$comentarios = (isset($_POST['comentarios'])) ? $_POST['comentarios'] : '';
$nuevaFormaPago = (isset($_POST['formaPago'])) ? $_POST['formaPago'] : '';
$id_Prov = (isset($_POST['id_Prov'])) ? $_POST['id_Prov'] : '';

// --- RBAC + CSRF (Fase 3) ---
// Writes: 3 (edit item), 4 (delete item), 6 (insert/get requisicion), 7 (mover a REVISION),
//         11 (ligar requisicion -> direccion.authorize), 12 (regresar a PENDIENTE),
//         13 (cambiar forma pago + recalcular), 15 (cambiar proveedor).
// Lecturas: 1, 2, 5, 8, 9, 10, 14.
$accionInt = (int)$accion;
$writeActions = array(3, 4, 6, 7, 11, 12, 13, 15);
if (in_array($accionInt, $writeActions, true)) {
    api_require_csrf($_POST);
    if ($accionInt === 11) {
        // Ligado de requisicion / autorizacion direccion
        tf_require_permission($conexion, 'requisiciones.authorize');
    } elseif ($accionInt === 4) {
        // Delete item de hoja
        tf_require_permission($conexion, 'requisiciones.edit');
    } else {
        tf_require_permission($conexion, 'requisiciones.edit');
    }
} else {
    tf_require_permission($conexion, 'requisiciones.view');
}

$currentUser = api_get_current_user($conexion);

function item_requisicion_obtener_obra_id(PDO $conexion, $idHoja)
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

function item_requisicion_obtener_obra_por_requisicion(PDO $conexion, $idReq)
{
    $consulta = "SELECT `requisicion_Obra` FROM `requisiciones` WHERE `requisicion_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idReq));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

function item_requisicion_obtener_obra_por_presion(PDO $conexion, $idPresion)
{
    $consulta = "SELECT `presiones_obra` FROM `presiones` WHERE `presiones_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idPresion));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

function item_requisicion_validar_hoja(PDO $conexion, $idHoja, $currentUser)
{
    $obraId = item_requisicion_obtener_obra_id($conexion, $idHoja);
    if ($obraId === null) {
        tf_abort(404, 'Hoja no encontrada');
    }

    return tf_require_obra_access($conexion, $obraId, $currentUser);
}

function item_requisicion_validar_requisicion(PDO $conexion, $idReq, $currentUser)
{
    $obraId = item_requisicion_obtener_obra_por_requisicion($conexion, $idReq);
    if ($obraId === null) {
        tf_abort(404, 'Requisicion no encontrada');
    }

    return tf_require_obra_access($conexion, $obraId, $currentUser);
}

function item_requisicion_validar_presion(PDO $conexion, $idPresion, $currentUser)
{
    $obraId = item_requisicion_obtener_obra_por_presion($conexion, $idPresion);
    if ($obraId === null) {
        tf_abort(404, 'Presion no encontrada');
    }

    return tf_require_obra_access($conexion, $obraId, $currentUser);
}

switch ($accion) {
    case 1:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "SELECT `itemRequisicion_id`, `itemRequisicion_unidad`, `itemRequisicion_producto`, `itemRequisicion_iva`, `itemRequisicion_retenciones`, `itemRequisicion_banderaFlete`, `itemRequisicion_banderaFisica`, `itemRequisicion_banderaResico`, `itemRequisicion_banderaISR`, `itemRequisicion_precio`, `itemRequisicion_cantidad`, `itemRequisicion_estatus`, `hojaRequisicion_total` FROM itemrequisicion INNER JOIN hojasrequisicion ON itemRequisicion_idHoja = hojasrequisicion.hojaRequisicion_id WHERE itemRequisicion_idHoja = :id_hoja ORDER BY itemRequisicion_id ASC";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        $data = array($currentUser);
        break;
    case 3:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "UPDATE `itemrequisicion` SET `itemRequisicion_unidad`=:unidad, `itemRequisicion_producto`=:producto, `itemRequisicion_iva`=:iva, `itemRequisicion_retenciones`=:retenciones, `itemRequisicion_banderaFlete`=:banderaFlete, `itemRequisicion_banderaFisica`=:banderaFisica, `itemRequisicion_banderaResico`=:banderaResico, `itemRequisicion_banderaISR`=:banderaISR, `itemRequisicion_precio`=:precio, `itemRequisicion_cantidad`=:cantidad WHERE `itemRequisicion_id` = :id";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':unidad', $unidad, PDO::PARAM_STR);
        $resultado->bindValue(':producto', $producto, PDO::PARAM_STR);
        $resultado->bindValue(':iva', $iva, PDO::PARAM_STR);
        $resultado->bindValue(':retenciones', $retenciones, PDO::PARAM_STR);
        $resultado->bindValue(':banderaFlete', $banderaFlete, PDO::PARAM_INT);
        $resultado->bindValue(':banderaFisica', $banderaFisica, PDO::PARAM_INT);
        $resultado->bindValue(':banderaResico', $banderaResico, PDO::PARAM_INT);
        $resultado->bindValue(':banderaISR', $banderaISR, PDO::PARAM_INT);
        $resultado->bindValue(':precio', $precio, PDO::PARAM_STR);
        $resultado->bindValue(':cantidad', $cantidad, PDO::PARAM_STR);
        $resultado->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $resultado->execute();
        $data = actualizarTotalHoja($conexion, (int)$idHoja);
        tf_audit_log($conexion, 'requisiciones.item.edit', 'itemrequisicion', (int)$id, array(
            'id_hoja' => (int)$idHoja,
            'producto' => $producto,
            'precio' => $precio,
            'cantidad' => $cantidad,
        ));
        break;
    case 4:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "DELETE FROM `itemrequisicion` WHERE `itemRequisicion_id` = :id";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $resultado->execute();
        $data = actualizarTotalHoja($conexion, (int)$idHoja);
        tf_audit_log($conexion, 'requisiciones.item.delete', 'itemrequisicion', (int)$id, array(
            'id_hoja' => (int)$idHoja,
        ));
        break;
    case 5:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "SELECT * FROM `hojasrequisicion` INNER JOIN emisores ON hojasrequisicion.hojaRequisicion_empresa = emisores.emisor_id INNER JOIN provedores ON hojasrequisicion.hojaRequisicion_proveedor = provedores.proveedor_id WHERE hojaRequisicion_id = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 6:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "INSERT INTO `itemrequisicion` (`itemRequisicion_id`, `itemRequisicion_idHoja`, `itemRequisicion_unidad`, `itemRequisicion_producto`, `itemRequisicion_iva`, `itemRequisicion_retenciones`, `itemRequisicion_banderaFlete`, `itemRequisicion_banderaFisica`, `itemRequisicion_banderaResico`, `itemRequisicion_banderaISR`, `itemRequisicion_precio`, `itemRequisicion_cantidad`, `itemRequisicion_estatus`) VALUES (NULL, :id_hoja, :unidad, :producto, :iva, :retenciones, :banderaFlete, :banderaFisica, :banderaResico, :banderaISR, :precio, :cantidad, 'N')";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->bindValue(':unidad', $unidad, PDO::PARAM_STR);
        $resultado->bindValue(':producto', $producto, PDO::PARAM_STR);
        $resultado->bindValue(':iva', $iva, PDO::PARAM_STR);
        $resultado->bindValue(':retenciones', $retenciones, PDO::PARAM_STR);
        $resultado->bindValue(':banderaFlete', $banderaFlete, PDO::PARAM_INT);
        $resultado->bindValue(':banderaFisica', $banderaFisica, PDO::PARAM_INT);
        $resultado->bindValue(':banderaResico', $banderaResico, PDO::PARAM_INT);
        $resultado->bindValue(':banderaISR', $banderaISR, PDO::PARAM_INT);
        $resultado->bindValue(':precio', $precio, PDO::PARAM_STR);
        $resultado->bindValue(':cantidad', $cantidad, PDO::PARAM_STR);
        $resultado->execute();
        $newItemId = (int)$conexion->lastInsertId();
        $data = actualizarTotalHoja($conexion, (int)$idHoja);
        tf_audit_log($conexion, 'requisiciones.item.create', 'itemrequisicion', $newItemId, array(
            'id_hoja' => (int)$idHoja,
            'producto' => $producto,
            'precio' => $precio,
            'cantidad' => $cantidad,
        ));
        break;
    case 7:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta =  "
            UPDATE hojasrequisicion
            SET hojaRequisicion_estatus       = :estatus,
                hojaRequisicion_observaciones = :obs
            WHERE hojaRequisicion_id            = :id
        ";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':estatus', 'REVISION', PDO::PARAM_STR);
        $resultado->bindValue(':obs', $comentarios, PDO::PARAM_STR);
        $resultado->bindValue(':id', $idReq, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        tf_audit_log($conexion, 'requisiciones.hoja.toRevision', 'hojasrequisicion', (int)$idReq, array(
            'comentarios' => $comentarios,
        ));
        break;
    case 8:
        $consulta = "SELECT `obras_nombre`,`ciudadesObras_nombre` FROM `obras` JOIN estadosobra ON estadosobra.ciudadesObras_id = obras.obras_cuidad WHERE `obras_id` = :obra";
        $resultado = $conexion->prepare($consulta);
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $resultado->bindValue(':obra', $obraId, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 9:
        item_requisicion_validar_requisicion($conexion, $idReq, $currentUser);
        $consulta = "SELECT `requisicion_Clave`, `requisicion_Numero` FROM `requisiciones` WHERE `requisicion_id` = :id_req";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_req', (int)$idReq, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 10:
        if (tf_user_can_view_all_obras($currentUser)) {
            $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute();
        } else {
            $obraAsignada = tf_user_assigned_obra_id($currentUser);
            if ($obraAsignada === null) {
                $data = array();
                break;
            }
            $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' AND `obras_id` = ? ORDER BY `obras_nombre`";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($obraAsignada));
        }
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 11:
        item_requisicion_validar_presion($conexion, $idPresion, $currentUser);
        item_requisicion_validar_requisicion($conexion, $idReq, $currentUser);
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        try {
            // Inicia transacción
            $conexion->beginTransaction();

            // Validar si ya existe una relación igual
            $consultaExiste = "SELECT COUNT(*) AS total FROM requisicionesligadas 
        WHERE requisicionesLigada_presionID = :idPresion 
        AND requisicionesLigadas_requisicionID = :idReq 
        AND requisicionesLigadas_hojaID = :idHoja";

            $stmtExiste = $conexion->prepare($consultaExiste);
            $stmtExiste->bindParam(':idPresion', $idPresion, PDO::PARAM_INT);
            $stmtExiste->bindParam(':idReq', $idReq, PDO::PARAM_INT);
            $stmtExiste->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmtExiste->execute();

            $resultado = $stmtExiste->fetch(PDO::FETCH_ASSOC);
            $registroExiste = $resultado['total'] > 0;

            // Si no existe, insertamos
            if (!$registroExiste) {
                $consultaInsert = "INSERT INTO requisicionesligadas (
            requisicionesLigada_presionID, 
            requisicionesLigadas_requisicionID, 
            requisicionesLigadas_hojaID
        ) VALUES (:idPresion, :idReq, :idHoja)";

                $stmtInsert = $conexion->prepare($consultaInsert);
                $stmtInsert->bindParam(':idPresion', $idPresion, PDO::PARAM_INT);
                $stmtInsert->bindParam(':idReq', $idReq, PDO::PARAM_INT);
                $stmtInsert->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
                $stmtInsert->execute();
            }

            // En ambos casos, actualizamos hojaRequisicion
            $consultaUpdate = "UPDATE hojasrequisicion SET 
        hojaRequisicion_estatus = 'LIGADA',
        hojarequisicion_comentariosValidacion = :comentario,
        hojarequisicion_adeudo = :adeudo 
        WHERE hojaRequisicion_id = :idHoja";

            $stmtUpdate = $conexion->prepare($consultaUpdate);
            $comentario = 'OK';
            $stmtUpdate->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':adeudo', $total, PDO::PARAM_STR); // asegúrate que $total es numérico
            $stmtUpdate->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmtUpdate->execute();

            // Confirmar si todo salió bien
            $conexion->commit();

            $data = [
                'status' => 'success',
                'mensaje' => $registroExiste
                    ? 'Registro ya existía, se actualizó hojaRequisicion'
                    : 'Registro insertado y hojaRequisicion actualizada'
            ];
            tf_audit_log($conexion, 'requisiciones.ligar', 'requisicionesligadas', (int)$idHoja, array(
                'id_presion' => (int)$idPresion,
                'id_req' => (int)$idReq,
                'id_hoja' => (int)$idHoja,
                'total' => $total,
                'ya_existia' => $registroExiste,
            ));
        } catch (Exception $e) {
            // Revertir cambios si algo falla
            $conexion->rollBack();

            $data = [
                'status' => 'error',
                'mensaje' => 'Ocurrió un error durante la operación',
                'error' => $e->getMessage()
            ];
        }
        break;
    case 12:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_estatus` = 'PENDIENTE', `hojarequisicion_comentariosValidacion` = :comentarios WHERE `hojasrequisicion`.`hojaRequisicion_id` = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':comentarios', $comentarios, PDO::PARAM_STR);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        tf_audit_log($conexion, 'requisiciones.hoja.toPendiente', 'hojasrequisicion', (int)$idHoja, array(
            'comentarios' => $comentarios,
        ));
        break;
    case 13:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        try {
            // Iniciar transacción
            $conexion->beginTransaction();

            // Debug opcional
            // echo "$nuevaFormaPago $idHoja $iva";

            // 1️⃣ Actualizar forma de pago
            $consulta1 = "UPDATE hojasrequisicion 
                  SET hojaRequisicion_formaPago = :nuevaFormaPago 
                  WHERE hojaRequisicion_id = :idHoja";
            $stmt1 = $conexion->prepare($consulta1);
            $stmt1->bindParam(':nuevaFormaPago', $nuevaFormaPago, PDO::PARAM_STR);
            $stmt1->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmt1->execute();

            // 2️⃣ Actualizar IVA y banderas en items
            $consulta2 = "UPDATE itemrequisicion 
                  SET itemRequisicion_iva = ((itemRequisicion_cantidad * itemRequisicion_precio) * :iva),
                      itemRequisicion_retenciones = 0,
                      itemRequisicion_banderaFlete = 0,
                      itemRequisicion_banderaFisica = 0,
                      itemRequisicion_banderaResico = 0,
                      itemRequisicion_banderaISR = 0
                  WHERE itemRequisicion_idHoja = :idHoja";
            $stmt2 = $conexion->prepare($consulta2);
            $stmt2->bindValue(':iva', (float)$iva, PDO::PARAM_STR);
            $stmt2->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmt2->execute();

            // 3️⃣ Calcular total actualizado
            $consulta3 = "SELECT SUM(
                        (itemRequisicion_cantidad * itemRequisicion_precio) 
                        + itemRequisicion_iva 
                        - itemRequisicion_retenciones
                    ) AS TotalItem 
                  FROM itemrequisicion 
                  WHERE itemRequisicion_idHoja = :idHoja";
            $stmt3 = $conexion->prepare($consulta3);
            $stmt3->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmt3->execute();
            $resultado = $stmt3->fetch(PDO::FETCH_ASSOC);
            $totalCambio = $resultado['TotalItem'] ?? 0;

            // 4️⃣ Actualizar total en hojaRequisicion
            $consulta4 = "UPDATE hojasrequisicion 
                  SET hojaRequisicion_total = :total 
                  WHERE hojaRequisicion_id = :idHoja";
            $stmt4 = $conexion->prepare($consulta4);
            $stmt4->bindValue(':total', $totalCambio, PDO::PARAM_STR);
            $stmt4->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $stmt4->execute();

            // Confirmar si todo fue exitoso
            $conexion->commit();

            $data = [
                'status' => 'success',
                'mensaje' => 'Forma de pago y totales actualizados correctamente',
                'total_actualizado' => $totalCambio
            ];
            tf_audit_log($conexion, 'requisiciones.hoja.changeFormaPago', 'hojasrequisicion', (int)$idHoja, array(
                'nueva_forma_pago' => $nuevaFormaPago,
                'iva' => $iva,
                'total_actualizado' => $totalCambio,
            ));
        } catch (Exception $e) {
            // Revertir cambios si ocurre un error
            $conexion->rollBack();

            $data = [
                'status' => 'error',
                'mensaje' => 'Error al actualizar los datos',
                'error' => $e->getMessage()
            ];
        }
        break;
    case 14:
        $consulta = "SELECT * FROM `provedores` WHERE `proveedor_estatus` = 'ACTIVO' ORDER BY `proveedor_nombre` LIMIT 1000";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 15:
        item_requisicion_validar_hoja($conexion, $idHoja, $currentUser);
        $consulta = "UPDATE `hojasrequisicion` 
                        SET `hojaRequisicion_proveedor` = :id_prov 
                        WHERE `hojaRequisicion_id` = :id_hoja";

        $resultado = $conexion->prepare($consulta);

        $resultado->bindParam(':id_prov', $id_Prov, PDO::PARAM_INT);
        $resultado->bindParam(':id_hoja', $idHoja, PDO::PARAM_INT);

        $resultado->execute();
        $data = 1;
        tf_audit_log($conexion, 'requisiciones.hoja.changeProveedor', 'hojasrequisicion', (int)$idHoja, array(
            'id_proveedor' => (int)$id_Prov,
        ));
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;

function actualizarTotalHoja(PDO $conexion, $idHoja)
{
    $consulta = "SELECT COALESCE(SUM((`itemRequisicion_cantidad` * `itemRequisicion_precio`) + `itemRequisicion_iva` - `itemRequisicion_retenciones`), 0) AS `TotalItem` FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = :id_hoja";
    $resultado = $conexion->prepare($consulta);
    $resultado->bindValue(':id_hoja', $idHoja, PDO::PARAM_INT);
    $resultado->execute();
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    $totalHoja = $data[0]['TotalItem'];

    $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_total` = :total WHERE `hojaRequisicion_id` = :id_hoja";
    $resultado = $conexion->prepare($consulta);
    $resultado->bindValue(':total', $totalHoja, PDO::PARAM_STR);
    $resultado->bindValue(':id_hoja', $idHoja, PDO::PARAM_INT);
    $resultado->execute();

    return $data;
}
