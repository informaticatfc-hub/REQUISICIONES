<?php
include_once 'conexion.php';
include_once 'auth.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = api_get_request_data();

$accion = isset($_POST['accion']) ? (int)$_POST['accion'] : 0;
$semana = isset($_POST['semana']) ? trim((string)$_POST['semana']) : '';
$dia = isset($_POST['dia']) ? trim((string)$_POST['dia']) : '';
$fecha = isset($_POST['fecha']) ? trim((string)$_POST['fecha']) : '';
$obra = $_POST['obra'] ?? 0;
$alias = isset($_POST['alias']) ? trim((string)$_POST['alias']) : '';

// --- RBAC + CSRF (Fase 3) ---
if ($accion === 3) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'presiones.create');
} else {
    tf_require_permission($conexion, 'presiones.view');
}

$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        $consulta = "SELECT `presiones_id`, `presiones_nombre`, `presiones_alias`, `presiones_estatus`, `presiones_semana`, `presiones_dia`
            FROM `presiones`
            WHERE `presiones_obra` = ?
            ORDER BY `presiones_fechaCreacion` DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2:
        $data = array($currentUser);
        break;

    case 3:
        $obraId = api_require_positive_int($obra, 'Obra invalida');

        if ($semana === '' || $dia === '' || $fecha === '') {
            api_json_error('Faltan datos para crear la presion', 422);
        }

        try {
            $conexion->beginTransaction();

            $consulta = "SELECT `obras_nombre`
                FROM `obras`
                WHERE `obras_id` = ?
                FOR UPDATE";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($obraId));
            $obraData = $resultado->fetch(PDO::FETCH_ASSOC);

            if (!$obraData) {
                throw new RuntimeException('La obra seleccionada no existe');
            }

            $consulta = "SELECT `presiones_id`
                FROM `presiones`
                WHERE `presiones_obra` = ? AND `presiones_semana` = ? AND `presiones_dia` = ?
                LIMIT 1";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($obraId, $semana, $dia));
            $existingPresion = $resultado->fetch(PDO::FETCH_ASSOC);

            if ($existingPresion) {
                $conexion->rollBack();
                $data = array(
                    'success' => false,
                    'message' => 'Ya existe una presion para la obra, semana y dia indicados.',
                    'presion_id' => (int)$existingPresion['presiones_id'],
                );
                break;
            }

            $nombrePresion = $obraData['obras_nombre'] . '-' . $semana . '-' . $dia;
            $consulta = "INSERT INTO `presiones` (
                `presiones_id`, `presiones_nombre`, `presiones_alias`, `presiones_semana`, `presiones_dia`,
                `presiones_adeudo`, `presiones_fechaCreacion`, `presiones_gastosObra`, `presiones_obra`,
                `presiones_userCreado`, `presiones_userValidado`, `presiones_estatus`
            ) VALUES (
                NULL, ?, ?, ?, ?, '0', ?, '0', ?, ?, NULL, 'PENDIENTE'
            )";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array(
                $nombrePresion,
                $alias,
                $semana,
                $dia,
                $fecha,
                $obraId,
                (int)$currentUser['user_id'],
            ));

            $presionId = (int)$conexion->lastInsertId();
            $conexion->commit();

            $data = array(
                'success' => true,
                'presion_id' => $presionId,
                'presion_nombre' => $nombrePresion,
            );
            tf_audit_log($conexion, 'presiones.create', 'presiones', $presionId, array(
                'obra' => $obraId,
                'semana' => $semana,
                'dia' => $dia,
                'alias' => $alias,
                'nombre' => $nombrePresion,
            ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo crear la presion', 500, array('details' => $e->getMessage()));
        }
        break;

    case 4:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        $consulta = "SELECT `obras_nombre` FROM `obras` WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 5:
        $resultado = $conexion->prepare("SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`");
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    default:
        api_json_error('Accion invalida', 400);
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;