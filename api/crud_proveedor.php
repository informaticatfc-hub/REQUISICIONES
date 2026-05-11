<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = json_decode(file_get_contents("php://input"), true);

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$id_user = (isset($_POST['id_user'])) ? $_POST['id_user'] : '';
$id_prov = (isset($_POST['id_prov'])) ? $_POST['id_prov'] : ''; 
$formValues = isset($_POST['formValues']) ? $_POST['formValues'] : null;
$data = array();

switch ($accion) {
    case 1:
        $consulta = "SELECT * FROM `users` WHERE `user_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_user));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 3:
        $consulta = "SELECT * FROM `provedores` WHERE `proveedor_estatus` = 'ACTIVO'";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        $consulta = "UPDATE `provedores` SET `proveedor_estatus` = 'INACTIVO' WHERE `proveedor_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_prov));
        $data = 1;
        break;
    case 5:
        $consulta = "SELECT * FROM `provedores` WHERE  `proveedor_id` = ? LIMIT 1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_prov));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 6:
        $consulta = "UPDATE `provedores` 
        SET 
            `proveedor_nombre` = :nombre,
            `presiones_type` = :tipo,
            `proveedor_rfc` = :rfc,
            `proveedor_clabe` = :clabe,
            `proveedor_numeroCuenta` = :cuenta,
            `proveedor_sucursal` = :sucursal,
            `proveedor_refBanco` = :referencia,
            `presiones_tarjetaBanco` = :tarjeta,
            `proveedor_banco` = :banco,
            `proveedor_email` = :email,
            `proveedor_telefono` = :telefono
        WHERE `proveedor_id` = :id";
    
        $resultado = $conexion->prepare($consulta);
        
        $resultado->bindParam(':nombre', $formValues['nombreProv']);
        $resultado->bindParam(':tipo', $formValues['typeProv']);
        $resultado->bindParam(':rfc', $formValues['RFCProv']);
        $resultado->bindParam(':clabe', $formValues['claveProv']);
        $resultado->bindParam(':cuenta', $formValues['cuentaBancaria']);
        $resultado->bindParam(':sucursal', $formValues['sucursalProv']);
        $resultado->bindParam(':referencia', $formValues['referenciaProv']);
        $resultado->bindParam(':tarjeta', $formValues['tarjetaProv']);
        $resultado->bindParam(':banco', $formValues['bancoProv']);
        $resultado->bindParam(':email', $formValues['correoProv']);
        $resultado->bindParam(':telefono', $formValues['telefonoProv']);        
        $resultado->bindParam(':id', $id_prov, PDO::PARAM_INT);
        
        $resultado->execute();
    
        $data = 1;
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;