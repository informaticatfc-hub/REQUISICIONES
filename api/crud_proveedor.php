<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? (int)$_POST['accion'] : 0;
$id_prov = (isset($_POST['id_prov'])) ? $_POST['id_prov'] : '';
$formValues = isset($_POST['formValues']) ? $_POST['formValues'] : null;

// --- RBAC + CSRF (Fase 3) ---
// case 4 (disable) y case 6 (edit) son writes -> proveedores.manage; resto son lecturas.
$writeActions = array(4, 6);
if (in_array($accion, $writeActions, true)) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'proveedores.manage');
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
        tf_audit_log($conexion, 'proveedores.disable', 'provedores', (int)$id_prov, null);
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
        tf_audit_log($conexion, 'proveedores.edit', 'provedores', (int)$id_prov, array(
            'nombre' => $formValues['nombreProv'] ?? null,
            'rfc' => $formValues['RFCProv'] ?? null,
        ));
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;