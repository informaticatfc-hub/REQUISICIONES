<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = json_decode(file_get_contents("php://input"), true);

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
$nombre = isset($_POST['nombre']) ? trim((string)$_POST['nombre']) : '';
$direccion = isset($_POST['direccion']) ? trim((string)$_POST['direccion']) : '';
$rfc = isset($_POST['rfc']) ? trim((string)$_POST['rfc']) : '';
$clabe = isset($_POST['clabe']) ? trim((string)$_POST['clabe']) : '';
$cuenta = isset($_POST['cuenta']) ? trim((string)$_POST['cuenta']) : '';
$tarjeta = isset($_POST['tarjeta']) ? trim((string)$_POST['tarjeta']) : '';
$referencia = isset($_POST['referencia']) ? trim((string)$_POST['referencia']) : '';
$banco = isset($_POST['banco']) ? trim((string)$_POST['banco']) : '';
$tipoProv = isset($_POST['tipoProv']) ? trim((string)$_POST['tipoProv']) : '';
$sucursal = isset($_POST['sucursal']) ? trim((string)$_POST['sucursal']) : '';
$telefono = isset($_POST['telefono']) ? trim((string)$_POST['telefono']) : '';
$correo = isset($_POST['correo']) ? trim((string)$_POST['correo']) : '';
$data = array();

switch ($accion) {
    case 1:
        $consulta = "SELECT * FROM `bancos` WHERE `banco_activo` = 1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        $consulta = "SELECT * FROM `users` WHERE `user_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute([$id_user]);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 3:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        $consulta = "INSERT INTO `provedores` (`proveedor_id`, `proveedor_nombre`, `presiones_type`, `proveedor_rfc`, `proveedor_clabe`, `proveedor_numeroCuenta`, `proveedor_sucursal`, `proveedor_refBanco`, `presiones_tarjetaBanco`, `proveedor_banco`, `proveedor_email`, `proveedor_telefono`, `proveedor_estatus`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute([$nombre, $tipoProv, $rfc, $clabe, $cuenta, $sucursal, $referencia, $tarjeta, $banco, $correo, $telefono]);
        $data = array('ok' => true);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
