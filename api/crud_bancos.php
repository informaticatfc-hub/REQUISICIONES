<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = json_decode(file_get_contents("php://input"), true);

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$id_user = (isset($_POST['id_user'])) ? $_POST['id_user'] : '';
$id_banco = (isset($_POST['id_banco'])) ? $_POST['id_banco'] : ''; 
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
        $consulta = "SELECT * FROM `bancos` WHERE `banco_activo` = 1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        $consulta = "UPDATE `bancos` SET `banco_activo` = 0 WHERE `banco_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_banco));
        $data = 1;
        break;
    case 5:
        $consulta = "SELECT * FROM `bancos` WHERE  `banco_id` = ? LIMIT 1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$id_banco));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 6:
        $consulta = "UPDATE `bancos` 
        SET 
            `banco_razonSocial` = :RazonSocial,
            `banco_nombreComercial` = :NombreComercial
        WHERE `banco_id` = :id";
    
        $resultado = $conexion->prepare($consulta);
        
        $resultado->bindParam(':RazonSocial', $formValues['razonSocialBanco']);
        $resultado->bindParam(':NombreComercial', $formValues['comercialBanco']);     
        $resultado->bindParam(':id', $id_banco, PDO::PARAM_INT);
        
        $resultado->execute();
    
        $data = 1;
        break;
    case 7:
        $consulta = "INSERT INTO `bancos`
        (`banco_id`, `banco_razonSocial`, `banco_nombreComercial`, `banco_activo`)
         VALUES 
         (NULL, :RazonSocial, :NombreComercial, 1)";
    
        $resultado = $conexion->prepare($consulta);
        
        $resultado->bindParam(':RazonSocial', $formValues['razonSocialBanco']);
        $resultado->bindParam(':NombreComercial', $formValues['comercialBanco']);     
        
        $resultado->execute();
    
        $data = 1;
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;