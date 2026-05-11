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
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
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
        $resultado->bindValue(':obra', (int)$obra, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        $consulta = "SELECT SUM(`requisicion_total`) AS `totalPresion` FROM `requisiciones` WHERE `requisicion_idPresion` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_presion));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 5:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 6:
        $consulta = "SELECT `obras_nombre`,`ciudadesObras_codigo` FROM `obras` JOIN estadosobra ON estadosobra.ciudadesObras_id = obras.obras_cuidad WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$obra));
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
        $consulta = "SELECT `requisicion_Numero`,`requisicion_Hojas` FROM `requisiciones` WHERE `requisicion_id` = :id_req";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_req', (int)$IdReq, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
   case 8:
    api_require_direction_access($currentUser);
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
    } catch (Exception $e) {
        $conexion->rollBack();
        $data = ['status' => 'error', 'message' => $e->getMessage()];
    }
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
