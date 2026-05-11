<?php
    class Conexion{
        public static function Conectar(){
            if (!defined('servidor')) {
                define('servidor', getenv('DB_HOST') ?: 'localhost');
            }
            if (!defined('nombre_bd')) {
                define('nombre_bd', getenv('DB_NAME') ?: 'u701868959_TFC');
            }
            if (!defined('usuario')) {
                define('usuario', getenv('DB_USER') ?: 'u701868959_informatica');
            }
            if (!defined('password')) {
                define('password', getenv('DB_PASS') ?: '2025TFC@ti@sistem');
            }
            $opciones = array(  
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            );
            try{
                // connect_timeout en el DSN es la unica forma de cortar el timeout de conexion MySQL
                $dsn = "mysql:host=".servidor.";dbname=".nombre_bd.";charset=utf8;connect_timeout=5";
                $conexion = new PDO($dsn, usuario, password, $opciones);
                return $conexion;
            }catch(Exception $e){
                error_log("DB connection error: " . $e->getMessage());
                http_response_code(500);
                die("Error interno de conexion");
            }
        }
    }
?>