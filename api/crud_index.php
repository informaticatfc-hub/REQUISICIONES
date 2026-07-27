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
        $selectObras = "SELECT `obras`.*, `estadosobra`.`ciudadesObras_nombre` AS `ubicacion`
            FROM `obras`
            LEFT JOIN `estadosobra` ON `estadosobra`.`ciudadesObras_id` = `obras`.`obras_cuidad`
            WHERE `obras`.`obras_estatus` = 'ACTIVO'" . $scope['sql'];
        if ($modo === 'recientes') {
            $consulta = $selectObras . " ORDER BY `obras`.`obras_id` DESC LIMIT " . $limite;
        } else {
            $consulta = $selectObras . " ORDER BY `obras`.`obras_nombre`";
        }
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($scope['params']);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;