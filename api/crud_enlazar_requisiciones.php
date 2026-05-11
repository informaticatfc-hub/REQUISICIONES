<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = json_decode(file_get_contents("php://input"), true);

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$id_user = (isset($_POST['id_user'])) ? $_POST['id_user'] : '';
$obra = (isset($_POST['obra'])) ? $_POST['obra'] : '';
$nombreReq  = (isset($_POST['nombreReq'])) ? $_POST['nombreReq'] : '';
$fechaReq =   (isset($_POST['fechaReq'])) ? $_POST['fechaReq'] : '';
$idPresion =   (isset($_POST['idPresion'])) ? $_POST['idPresion'] : '';
$idReq =   (isset($_POST['idReq'])) ? $_POST['idReq'] : '';
$idHoja =   (isset($_POST['idHoja'])) ? $_POST['idHoja'] : '';
$data = array();

switch ($accion) {
    case 1:
        $consulta = "SELECT `hojaRequisicion_id`, `hojaRequisicion_numero`, `hojaRequisicion_total`,`requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre` FROM `hojasrequisicion` INNER JOIN requisiciones ON requisiciones.requisicion_id = hojasrequisicion.hojaRequisicion_idReq WHERE requisiciones.requisicion_Obra = ? AND (hojasrequisicion.hojaRequisicion_estatus = 'REVISION') ORDER BY `requisicion_Clave`, `requisicion_Numero`, `hojaRequisicion_numero`;";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$obra));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        $consulta = "SELECT * FROM `users` WHERE `user_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_user));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 3:
        $consulta = "SELECT `obras_nombre` FROM `obras` WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$obra));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 5:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case  6:
        $consulta = "SELECT `presiones_nombre` FROM `presiones` WHERE `presiones_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$idPresion));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
