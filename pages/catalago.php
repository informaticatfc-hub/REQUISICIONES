<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['catalogos.view'], 'No tienes permiso para acceder a los catalogos');

// Ruta legacy preservada: redirige al menu v4 estandarizado.
header('Location: ./menu_catalago.php', true, 302);
exit;
