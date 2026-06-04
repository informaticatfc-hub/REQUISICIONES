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
$idPresion =   (isset($_POST['idPresion'])) ? $_POST['idPresion'] : '';
$idReq =   (isset($_POST['idReq'])) ? $_POST['idReq'] : '';
$idHoja =   (isset($_POST['idHoja'])) ? $_POST['idHoja'] : '';

// --- RBAC (Fase 3) ---
// Todas las acciones son lecturas usadas para armar el panel de enlace de requisiciones.
// Antes aceptaba id_user desde POST; ahora siempre se deriva de sesion (case 2).
tf_require_permission($conexion, 'requisiciones.view');
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        $consulta = "SELECT `hojaRequisicion_id`, `hojaRequisicion_numero`, `hojaRequisicion_total`,`requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre` FROM `hojasrequisicion` INNER JOIN requisiciones ON requisiciones.requisicion_id = hojasrequisicion.hojaRequisicion_idReq WHERE requisiciones.requisicion_Obra = ? AND (hojasrequisicion.hojaRequisicion_estatus = 'REVISION') ORDER BY `requisicion_Clave`, `requisicion_Numero`, `hojaRequisicion_numero`;";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$obra));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        // Antes leia $_POST['id_user'] (impersonacion). Ahora devuelve el usuario de sesion.
        $data = array($currentUser);
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
        $consulta = "SELECT p.presiones_nombre,
                           COALESCE(p.presiones_total, 0) AS presiones_total,
                           COALESCE(SUM(h.hojaRequisicion_total),0) AS totalYaLigado
                    FROM presiones p
                    LEFT JOIN hojasrequisicion h
                        ON h.hojaRequisicion_idPresion = p.presiones_id
                       AND h.hojaRequisicion_estatus NOT IN ('NUEVO','PENDIENTE','RECHAZADA')
                    WHERE p.presiones_id = ?
                    GROUP BY p.presiones_id, p.presiones_nombre, p.presiones_total";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array((int)$idPresion));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
