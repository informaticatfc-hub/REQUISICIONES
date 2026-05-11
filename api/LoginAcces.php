<?php
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = json_decode(file_get_contents("php://input"), true);

//Recepcion de datos por Axios
$Usuario = isset($_POST['user']) ? trim((string)$_POST['user']) : '';
$Contrasena = isset($_POST['password']) ? (string)$_POST['password'] : '';
$dato = array(
    'bandera' => 'false',
    'user_id' => 0
);

if ($Usuario !== '' && $Contrasena !== '') {
    $consulta = "SELECT `user_id`, `user_password` FROM `users` WHERE `user_nameUser` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute([$Usuario]);
    $user = $resultado->fetch(PDO::FETCH_ASSOC);

    if ($user && isset($user["user_password"])) {
        $storedPassword = (string)$user["user_password"];
        $isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
        $isValidPassword = $isHashedPassword
            ? password_verify($Contrasena, $storedPassword)
            : hash_equals($storedPassword, $Contrasena);

        if ($isValidPassword && !$isHashedPassword) {
            $newHash = password_hash($Contrasena, PASSWORD_DEFAULT);
            $updatePassword = $conexion->prepare("UPDATE `users` SET `user_password` = ? WHERE `user_id` = ?");
            $updatePassword->execute([$newHash, (int)$user['user_id']]);
        }

        if (!$isValidPassword) {
            print json_encode($dato, JSON_UNESCAPED_UNICODE);
            $conexion = NULL;
            exit;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION["Usuario"] = $Usuario;
        $_SESSION["UsuarioId"] = (int)$user["user_id"];
        $dato['bandera'] = 'true';
        $dato['user_id'] = (int)$user["user_id"];
    }
}

print json_encode($dato, JSON_UNESCAPED_UNICODE);
$conexion = NULL;