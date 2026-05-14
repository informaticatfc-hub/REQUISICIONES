<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? (int)$_POST['accion'] : 0;

// --- RBAC (Fase 3) ---
// Solo lecturas para construir el menu de catalogos.
tf_require_permission($conexion, 'catalogos.view');
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        // Antes: SELECT * FROM users WHERE user_id = '$id_user' (SQL INJECTION).
        // Ahora se devuelve el usuario derivado de la sesion.
        $data = array($currentUser);
        break;
    case 2:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
