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
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limite = isset($_POST['limite']) ? (int)$_POST['limite'] : 20;
$search = isset($_POST['search']) ? trim((string)$_POST['search']) : '';
$estatus = isset($_POST['estatus']) ? trim((string)$_POST['estatus']) : '';
$serverSide = isset($_POST['serverSide']) ? (int)$_POST['serverSide'] : 0;

// --- RBAC + CSRF (Fase 3) ---
if ($accion === 3) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'presiones.create');
} else {
    tf_require_permission($conexion, 'presiones.view');
}

$currentUser = api_get_current_user($conexion);
$data = array();

function presiones_obtener_obra_id(PDO $conexion, $idPresion)
{
    $consulta = "SELECT `presiones_obra` FROM `presiones` WHERE `presiones_id` = ? LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array((int)$idPresion));
    $obraId = $resultado->fetchColumn();

    return $obraId === false ? null : (int)$obraId;
}

switch ($accion) {
    case 1:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $page = max(1, $page);
        $limite = max(5, min(100, $limite));
        $offset = ($page - 1) * $limite;

        $where = " WHERE `presiones_obra` = ?";
        $params = array($obraId);

        if ($estatus !== '') {
            $where .= " AND `presiones_estatus` = ?";
            $params[] = $estatus;
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= " AND (
                `presiones_nombre` LIKE ?
                OR `presiones_alias` LIKE ?
                OR `presiones_semana` LIKE ?
                OR `presiones_dia` LIKE ?
            )";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sqlTotal = "SELECT COUNT(*) FROM `presiones`" . $where;
        $stTotal = $conexion->prepare($sqlTotal);
        $stTotal->execute($params);
        $total = (int)$stTotal->fetchColumn();

        $consulta = "SELECT `presiones_id`, `presiones_nombre`, `presiones_alias`, `presiones_estatus`, `presiones_semana`, `presiones_dia`
            FROM `presiones`" . $where . "
            ORDER BY `presiones_fechaCreacion` DESC, `presiones_id` DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($params);
        $rows = $resultado->fetchAll(PDO::FETCH_ASSOC);

        if ($serverSide === 1) {
            $statsSql = "SELECT
                    COUNT(*) AS total_general,
                    SUM(CASE WHEN `presiones_estatus` = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN `presiones_estatus` = 'AUTORIZADO' THEN 1 ELSE 0 END) AS autorizadas
                FROM `presiones`
                WHERE `presiones_obra` = ?";
            $stStats = $conexion->prepare($statsSql);
            $stStats->execute(array($obraId));
            $stats = $stStats->fetch(PDO::FETCH_ASSOC) ?: array();

            $pages = (int)ceil($total / $limite);
            $data = array(
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'pages' => max(1, $pages),
                'limite' => $limite,
                'stats' => array(
                    'total' => (int)($stats['total_general'] ?? 0),
                    'pendientes' => (int)($stats['pendientes'] ?? 0),
                    'autorizadas' => (int)($stats['autorizadas'] ?? 0),
                ),
            );
        } else {
            // Compatibilidad legacy
            $data = $rows;
        }
        break;

    case 2:
        $data = array($currentUser);
        break;

    case 3:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        tf_require_obra_access($conexion, $obraId, $currentUser);

        if ($semana === '' || $dia === '') {
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
                NULL, ?, ?, ?, ?, '0', CURDATE(), '0', ?, ?, '', 'PENDIENTE'
            )";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array(
                $nombrePresion,
                $alias,
                $semana,
                $dia,
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
        tf_require_obra_access($conexion, $obraId, $currentUser);
        $consulta = "SELECT `obras_nombre` FROM `obras` WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 5:
        $scope = tf_scope_obras_query($conexion, $currentUser);
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO'" . $scope['sql'] . " ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute($scope['params']);
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    default:
        api_json_error('Accion invalida', 400);
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;