<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? (int)$_POST['accion'] : 0;
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

// --- RBAC + CSRF (Fase 3) ---
// case 4 = INSERT proveedor -> proveedores.manage; resto son lecturas.
if ($accion === 4) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'proveedores.manage');
} else {
    tf_require_permission($conexion, 'catalogos.view');
}
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        $consulta = "SELECT * FROM `bancos` WHERE `banco_activo` = 1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        // Antes leia $_POST['id_user'] (impersonacion). Ahora devuelve el usuario de sesion.
        $data = array($currentUser);
        break;
    case 3:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        // Validar duplicados por CLABE o número de cuenta antes de insertar
        $stmtDup = $conexion->prepare(
            "SELECT `proveedor_id`, `proveedor_nombre`
             FROM `provedores`
             WHERE (`proveedor_clabe` = ? AND `proveedor_clabe` != '')
                OR (`proveedor_numeroCuenta` = ? AND `proveedor_numeroCuenta` != '')
             LIMIT 1"
        );
        $stmtDup->execute([$clabe, $cuenta]);
        $dup = $stmtDup->fetch(PDO::FETCH_ASSOC);
        if ($dup) {
            $data = array(
                'ok' => false,
                'duplicate' => true,
                'proveedor_nombre' => $dup['proveedor_nombre'],
                'proveedor_id' => (int)$dup['proveedor_id'],
            );
            break;
        }

        $consulta = "INSERT INTO `provedores` (
            `proveedor_id`, `proveedor_nombre`, `presiones_type`, `proveedor_rfc`,
            `proveedor_clabe`, `proveedor_numeroCuenta`, `proveedor_sucursal`,
            `proveedor_refBanco`, `presiones_tarjetaBanco`, `proveedor_banco`,
            `proveedor_email`, `proveedor_telefono`, `proveedor_estatus`
        ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute([$nombre, $tipoProv, $rfc, $clabe, $cuenta, $sucursal, $referencia, $tarjeta, $banco, $correo, $telefono]);
        $newId = (int)$conexion->lastInsertId();
        $data = array('ok' => true, 'proveedor_id' => $newId);
        tf_audit_log($conexion, 'proveedores.create', 'provedores', $newId, array(
            'nombre' => $nombre,
            'rfc' => $rfc,
            'tipo' => $tipoProv,
        ));
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
