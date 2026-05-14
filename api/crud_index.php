<?php
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$modo = (isset($_POST['modo'])) ? trim((string)$_POST['modo']) : 'activas';
$limite = isset($_POST['limite']) ? (int)$_POST['limite'] : 12;
$limite = max(1, min($limite, 100));
$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        $data = array($currentUser);
        break;
    case 2:
        $scope = tf_scope_obras_query($conexion, $currentUser);
        if ($modo === 'recientes') {
            $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO'" . $scope['sql'] . " ORDER BY `obras_id` DESC LIMIT " . $limite;
        } else {
            $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO'" . $scope['sql'] . " ORDER BY `obras_nombre`";
        }
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($scope['params']);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;