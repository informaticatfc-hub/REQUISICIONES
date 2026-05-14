<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? (int)$_POST['accion'] : 0;
$id_banco = (isset($_POST['id_banco'])) ? $_POST['id_banco'] : '';
$formValues = isset($_POST['formValues']) ? $_POST['formValues'] : null;

// --- RBAC + CSRF (Fase 3) ---
// case 4 = disable, 6 = edit, 7 = insert -> bancos.manage; resto son lecturas.
$writeActions = array(4, 6, 7);
if (in_array($accion, $writeActions, true)) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'bancos.manage');
} else {
    tf_require_permission($conexion, 'catalogos.view');
}
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        // Antes leia $_POST['id_user'] (impersonacion). Ahora devuelve el usuario de sesion.
        $data = array($currentUser);
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
        tf_audit_log($conexion, 'bancos.disable', 'bancos', (int)$id_banco, null);
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
        tf_audit_log($conexion, 'bancos.edit', 'bancos', (int)$id_banco, array(
            'razon_social' => $formValues['razonSocialBanco'] ?? null,
            'nombre_comercial' => $formValues['comercialBanco'] ?? null,
        ));
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
        $newId = (int)$conexion->lastInsertId();
        $data = array('ok' => true, 'banco_id' => $newId);
        tf_audit_log($conexion, 'bancos.create', 'bancos', $newId, array(
            'razon_social' => $formValues['razonSocialBanco'] ?? null,
            'nombre_comercial' => $formValues['comercialBanco'] ?? null,
        ));
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;