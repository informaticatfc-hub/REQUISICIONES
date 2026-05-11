<?php
include_once 'conexion.php';
include_once 'auth.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = api_get_request_data();

$accion = isset($_POST['accion']) ? (int)$_POST['accion'] : 0;
$obra = $_POST['obra'] ?? 0;
$nombreReq = isset($_POST['nombreReq']) ? trim((string)$_POST['nombreReq']) : '';
$fechaReq = isset($_POST['fechaReq']) ? trim((string)$_POST['fechaReq']) : '';
$clave = isset($_POST['clave']) ? trim((string)$_POST['clave']) : '';
$folioReq = $_POST['folio'] ?? 0;
$Hoja = $_POST['hoja'] ?? 0;
$idReq = $_POST['idReq'] ?? 0;
$numReq = $_POST['numeroReq'] ?? '';
$id_presion = $_POST['id_presion'] ?? 0;

$currentUser = api_get_current_user($conexion);
$data = array();

switch ($accion) {
    case 1:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        $consulta = "SELECT `requisicion_id`, `requisicion_Numero`, `requisicion_Clave`, `requisicion_Nombre`, `requisicion_estatus`
            FROM `requisiciones`
            WHERE `requisicion_Obra` = ?
            AND COALESCE(`requisicion_estatus`, '') NOT IN ('CERRADA', 'CANCELADA')
            ORDER BY `requisicion_fechaSolicitud` DESC, `requisicion_Numero` DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $dataArray = $resultado->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dataArray as $row) {
            $data[] = array(
                'requisicion_id' => $row['requisicion_id'],
                'requisicion_Numero' => $row['requisicion_Numero'],
                'requisicion_Clave' => $row['requisicion_Clave'],
                'requisicion_Nombre' => $row['requisicion_Nombre'],
                'requisicion_estatus' => $row['requisicion_estatus'],
                'requisicion_EditShow' => false,
            );
        }
        break;

    case 2:
        $data = array($currentUser);
        break;

    case 3:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        $consulta = "SELECT `obras_nombre`, `obra_automatico` FROM `obras` WHERE `obras_id` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($obraId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 4:
        $presionId = api_require_positive_int($id_presion, 'Presion invalida');
        $consulta = "SELECT SUM(`requisicion_total`) AS `totalPresion` FROM `requisiciones` WHERE `requisicion_idPresion` = ?";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute(array($presionId));
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 5:
        $resultado = $conexion->prepare("SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`");
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 6:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        if ($nombreReq === '' || $fechaReq === '' || $clave === '') {
            api_json_error('Faltan datos para crear la requisicion', 422);
        }

        try {
            $conexion->beginTransaction();
            $obraInfo = obtenerPrefijoObra($conexion, $obraId);
            $ultimoFolio = obtenerUltimoFolio($conexion, $obraId, $clave);
            $folio = $ultimoFolio + 1;
            $numeroRequisicion = construirNumeroRequisicion($obraInfo, $clave, $folio);

            $consulta = "INSERT INTO `requisiciones` (
                `requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`,
                `requisicion_Obra`, `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_Hojas`,
                `requisicion_total`, `requisicion_estatus`
            ) VALUES (NULL, ?, ?, ?, ?, ?, ?, '0', '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($clave, $numeroRequisicion, $nombreReq, $obraId, $fechaReq, $folio));

            $data = array(
                'success' => true,
                'requisicion_id' => (int)$conexion->lastInsertId(),
                'numero_nuevo' => $numeroRequisicion,
            );
            $conexion->commit();
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo crear la requisicion', 500, array('details' => $e->getMessage()));
        }
        break;

    case 7:
        $obraId = api_require_positive_int($obra, 'Obra invalida');
        $folioManual = filter_var($folioReq, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        $hojasIniciales = filter_var($Hoja, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));

        if ($folioManual === false || $hojasIniciales === false || $nombreReq === '' || $fechaReq === '' || $clave === '') {
            api_json_error('Faltan datos validos para la requisicion manual', 422);
        }

        try {
            $conexion->beginTransaction();
            $obraInfo = obtenerPrefijoObra($conexion, $obraId);

            $consulta = "SELECT `requisicion_id`
                FROM `requisiciones`
                WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ? AND `requisicion_Folio` = ?
                LIMIT 1";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($clave, $obraId, $folioManual));
            if ($resultado->fetch(PDO::FETCH_ASSOC)) {
                $conexion->rollBack();
                $data = array(
                    'success' => false,
                    'message' => 'Ya existe una requisicion con ese folio para la misma obra y clave.',
                );
                break;
            }

            $numeroRequisicion = construirNumeroRequisicion($obraInfo, $clave, $folioManual);
            $consulta = "INSERT INTO `requisiciones` (
                `requisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `requisicion_Nombre`,
                `requisicion_Obra`, `requisicion_fechaSolicitud`, `requisicion_Folio`, `requisicion_Hojas`,
                `requisicion_total`, `requisicion_estatus`
            ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, '0', 'ABIERTO')";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($clave, $numeroRequisicion, $nombreReq, $obraId, $fechaReq, $folioManual, $hojasIniciales));

            $data = array(
                'success' => true,
                'requisicion_id' => (int)$conexion->lastInsertId(),
                'numero_nuevo' => $numeroRequisicion,
            );
            $conexion->commit();
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo crear la requisicion manual', 500, array('details' => $e->getMessage()));
        }
        break;

    case 8:
        api_require_direction_access($currentUser);
        $requisicionId = api_require_positive_int($idReq, 'Requisicion invalida');
        $numeroEditado = filter_var($numReq, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        if ($numeroEditado === false || $nombreReq === '') {
            api_json_error('Datos invalidos para editar la requisicion', 422);
        }

        $sqlGet = "SELECT `requisicion_Numero` FROM `requisiciones` WHERE `requisicion_id` = :idReq";
        $stmtGet = $conexion->prepare($sqlGet);
        $stmtGet->bindValue(':idReq', $requisicionId, PDO::PARAM_INT);
        $stmtGet->execute();
        $numeroActual = $stmtGet->fetchColumn();

        if ($numeroActual === false) {
            api_json_error('Requisicion no encontrada', 404);
        }

        $nuevoNumero = reemplazarUltimosDigitos($numeroActual, convertFolio((int)$numeroEditado));
        $sqlUpd = "UPDATE `requisiciones`
            SET `requisicion_Numero` = :newNumero, `requisicion_Nombre` = :newNombre
            WHERE `requisicion_id` = :idReq";
        $stmtUpd = $conexion->prepare($sqlUpd);
        $stmtUpd->bindValue(':newNumero', $nuevoNumero, PDO::PARAM_STR);
        $stmtUpd->bindValue(':newNombre', $nombreReq, PDO::PARAM_STR);
        $stmtUpd->bindValue(':idReq', $requisicionId, PDO::PARAM_INT);
        $stmtUpd->execute();

        $data = array(
            'success' => true,
            'idReq' => $requisicionId,
            'numero_anterior' => $numeroActual,
            'numero_nuevo' => $nuevoNumero,
            'nombre_nuevo' => $nombreReq,
            'rows_affected' => $stmtUpd->rowCount(),
        );
        break;

    case 9:
        api_require_direction_access($currentUser);
        $requisicionId = api_require_positive_int($idReq, 'Requisicion invalida');

        try {
            $conexion->beginTransaction();

            $consulta = "SELECT `hojaRequisicion_id` FROM `hojasrequisicion` WHERE `hojaRequisicion_idReq` = ?";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute(array($requisicionId));
            $idsHojas = $resultado->fetchAll(PDO::FETCH_ASSOC);

            $deleteItems = $conexion->prepare("DELETE FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = ?");
            foreach ($idsHojas as $hoja) {
                $deleteItems->execute(array((int)$hoja['hojaRequisicion_id']));
            }

            $deleteHojas = $conexion->prepare("DELETE FROM `hojasrequisicion` WHERE `hojaRequisicion_idReq` = ?");
            $deleteHojas->execute(array($requisicionId));

            $deleteReq = $conexion->prepare("DELETE FROM `requisiciones` WHERE `requisicion_id` = ?");
            $deleteReq->execute(array($requisicionId));

            $conexion->commit();
            $data = array('status' => 'ok');
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo eliminar la requisicion', 500, array('details' => $e->getMessage()));
        }
        break;

    default:
        api_json_error('Accion invalida', 400);
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;

function convertFolio($folioInt)
{
    if ($folioInt < 10) {
        return '00' . $folioInt;
    }

    if ($folioInt < 100) {
        return '0' . $folioInt;
    }

    return (string)$folioInt;
}

function reemplazarUltimosDigitos($cadenaOriginal, $nuevoNumero)
{
    return preg_replace('/\d+$/', (string)$nuevoNumero, $cadenaOriginal);
}

function obtenerPrefijoObra(PDO $conexion, $obraId)
{
    $consulta = "SELECT `obras_nombre`, `ciudadesObras_codigo`
        FROM `obras`
        JOIN `estadosobra` ON `estadosobra`.`ciudadesObras_id` = `obras`.`obras_cuidad`
        WHERE `obras_id` = ?
        FOR UPDATE";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($obraId));
    $obraInfo = $resultado->fetch(PDO::FETCH_ASSOC);

    if (!$obraInfo) {
        throw new RuntimeException('La obra seleccionada no existe');
    }

    return $obraInfo;
}

function obtenerUltimoFolio(PDO $conexion, $obraId, $clave)
{
    $consulta = "SELECT `requisicion_Folio`
        FROM `requisiciones`
        WHERE `requisicion_Clave` = ? AND `requisicion_Obra` = ?
        ORDER BY `requisicion_Folio` DESC
        LIMIT 1";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute(array($clave, $obraId));
    $ultimoFolio = $resultado->fetchColumn();

    return $ultimoFolio === false ? -1 : (int)$ultimoFolio;
}

function construirNumeroRequisicion($obraInfo, $clave, $folio)
{
    return $obraInfo['ciudadesObras_codigo'] . '-' . $obraInfo['obras_nombre'] . '-' . $clave . '-' . convertFolio((int)$folio);
}