<?php
header('Content-Type: application/json; charset=UTF-8');
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();


//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$obra = (isset($_POST['obra'])) ? $_POST['obra'] : '';
$dia = (isset($_POST['dia'])) ? $_POST['dia'] : '';
$semana = (isset($_POST['semana'])) ? $_POST['semana'] : '';
$idHoja = (isset($_POST['idHoja'])) ? $_POST['idHoja'] : '';
$idPresion = (isset($_POST['idPresion'])) ? $_POST['idPresion'] : '';
$fechaPago = (isset($_POST['fechaPago'])) ? $_POST['fechaPago'] : '';
$bancoPago = (isset($_POST['bancoPago'])) ? $_POST['bancoPago'] : '';
$estatus = (isset($_POST['status'])) ? $_POST['status'] : '';
$time = (isset($_POST['time'])) ? $_POST['time'] : '';
$autorizado = (isset($_POST['autorizado'])) ? $_POST['autorizado'] : '';
$DatosExport = json_decode((isset($_POST['datos'])) ? $_POST['datos'] : '', true);
$NombrePress = (isset($_POST['namePres'])) ? $_POST['namePres'] : '';
$total = (isset($_POST['total'])) ? $_POST['total'] : '';
$adeudo = (isset($_POST['adeudo'])) ? $_POST['adeudo'] : '';
$Presiones = json_decode((isset($_POST['Presiones'])) ? $_POST['Presiones'] : '', true);
$output = "";
$textExpecial = "";

// --- RBAC + CSRF (Fase 3) ---
// case 5 = marcar PAGADA, case 7 = cerrar presion (AUTORIZADO).
// El resto son lecturas para construir la vista de detalle.
$accionInt = (int)$accion;
$writeActions = array(5, 7);
if (in_array($accionInt, $writeActions, true)) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'presiones.authorize');
} else {
    tf_require_permission($conexion, 'presiones.view');
}
$currentUser = api_get_current_user($conexion);

switch ($accion) {
    case 1:
        $data = array($currentUser);
        break;
    case 2:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 3:
        $consulta = "SELECT `hojaRequisicion_id`, `requisicion_Clave`, `requisicion_Numero`, `hojaRequisicion_observaciones`, `hojaRequisicion_numero`, `requisicion_Nombre`, `proveedor_nombre`, `hojaRequisicion_total`, `hojaRequisicion_formaPago`, `hojaRequisicion_estatus`, `presiones_estatus`, `hojaRequisicion_fechaPago`, `hojasRequisicion_bancoPago` , `hojarequisicion_adeudo`,  `hojarequisicion_conceptoUnico`
            FROM `requisicionesligadas`
            JOIN presiones ON presiones.presiones_id = requisicionesLigada_presionID
            JOIN requisiciones ON requisiciones.requisicion_id = requisicionesLigadas_requisicionID
            JOIN hojasrequisicion ON hojasrequisicion.hojaRequisicion_id = requisicionesLigadas_hojaID
            JOIN provedores ON hojasrequisicion.hojaRequisicion_proveedor = provedores.proveedor_id
            WHERE requisicionesLigada_presionID = :id_presion
            ORDER BY `requisicion_Clave` DESC";

        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_presion', (int)$idPresion, PDO::PARAM_INT);
        $resultado->execute();
        $dataBD = $resultado->fetchAll(PDO::FETCH_ASSOC);
        $data = array();

        foreach ($dataBD as $hoja) {
            $consulta = "SELECT `itemRequisicion_producto` FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = :id_hoja";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindValue(':id_hoja', (int)$hoja['hojaRequisicion_id'], PDO::PARAM_INT);
            $resultado->execute();
            $dataitms = $resultado->fetchAll(PDO::FETCH_ASSOC);
            array_push($data, array(
                'id_hoja' => $hoja['hojaRequisicion_id'],
                'formaPago' => obtenerAbreviatura($hoja['hojaRequisicion_formaPago']),
                'NumReq' => $hoja['requisicion_Numero'] . " Hoja Numero: " . $hoja['hojaRequisicion_numero'],
                'clave' => $hoja['requisicion_Clave'],
                'nombreReq' => $hoja['requisicion_Nombre'], // <--- AQUÍ AGREGAMOS EL NOMBRE
                'concepto' => empty($hoja['hojarequisicion_conceptoUnico']) ? convertToString($dataitms) : $hoja['hojarequisicion_conceptoUnico'],
                'proveedor' => $hoja['proveedor_nombre'],
                'total' => $hoja['hojaRequisicion_total'],
                'Observaciones' => $hoja['hojaRequisicion_observaciones'],
                "Banco" => $hoja['hojasRequisicion_bancoPago'],
                "Fecha" => empty($hoja['hojaRequisicion_fechaPago'])
                    ? date('Y-m-d')
                    : $hoja['hojaRequisicion_fechaPago'],
                "HojaEstatus" => $hoja['hojaRequisicion_estatus'],
                "PresionEstatus" => $hoja['presiones_estatus'],
                "adeudo" => $hoja['hojarequisicion_adeudo'],
                "NumRequi" => $hoja['requisicion_Numero'],
                "showDetail" => false,
                "atrClass" => "text-left align-middle inline-block text-truncate fs-6",
                "strStyle" => "max-width: 100px;"
            ));
        }
        ;
        break;
    case 4:
        $consulta = "SELECT `obras_nombre`,`ciudadesObras_nombre` FROM `obras` JOIN estadosobra ON estadosobra.ciudadesObras_id = obras.obras_cuidad WHERE `obras_id` = :obra";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':obra', (int)$obra, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 5:
        $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_estatus` = 'PAGADA', `hojaRequisicion_fechaPago` = :fecha_pago, `hojasRequisicion_bancoPago` = :banco_pago WHERE `hojasrequisicion`.`hojaRequisicion_id` = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':fecha_pago', $fechaPago, PDO::PARAM_STR);
        $resultado->bindValue(':banco_pago', $bancoPago, PDO::PARAM_STR);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        tf_audit_log($conexion, 'presiones.hoja.pagada', 'hojasrequisicion', (int)$idHoja, array(
            'fecha_pago' => $fechaPago,
            'banco_pago' => $bancoPago,
        ));
        break;
    case 6:
        $data = array(
            'status' => 'deprecated',
            'mensaje' => 'La exportacion Excel se genera en frontend con .xlsx (SheetJS).'
        );
        break;
    case 7:
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        try {
            $conexion->beginTransaction();

            // 1) Prepara el UPDATE una sola vez (mejor rendimiento que prepararlo en cada iteración)
            $sqlHoja = "
                UPDATE hojasrequisicion
                SET hojaRequisicion_fechaPago   = :fechaPago,
                    hojasRequisicion_bancoPago  = :bancoPago,
                    hojaRequisicion_estatus     = :estatus
                WHERE hojaRequisicion_id          = :id
            ";
            $stmtHoja = $conexion->prepare($sqlHoja);

            $autorizadas = 0;

            foreach ($Presiones as $presion) {
                // Valida estructura mínima
                if (
                    !isset($presion['PresionEstatus'], $presion['id_hoja']) ||
                    $presion['HojaEstatus'] !== 'AUTORIZADA'
                ) {
                    continue;
                }

                // Normaliza nulos / vacíos: si vienen "" o null, guarda NULL en DB
                $fecha = isset($presion['Fecha']) && $presion['Fecha'] !== '' ? $presion['Fecha'] : null;
                $banco = isset($presion['Banco']) && $presion['Banco'] !== '' ? $presion['Banco'] : null;
                $idHoja = (int) $presion['id_hoja'];
                

                // Ejecuta con tipos adecuados; PDO maneja NULL si pasas null y tipo PARAM_NULL
                if ($fecha === null) {
                    $stmtHoja->bindValue(':fechaPago', null, PDO::PARAM_NULL);
                } else {
                    $stmtHoja->bindValue(':fechaPago', $fecha, PDO::PARAM_STR);
                }

                if ($banco === null) {
                    $stmtHoja->bindValue(':bancoPago', null, PDO::PARAM_NULL);
                } else {
                    $stmtHoja->bindValue(':bancoPago', $banco, PDO::PARAM_STR);
                }

                $stmtHoja->bindValue(':estatus', 'PAGADA', PDO::PARAM_STR);
                $stmtHoja->bindValue(':id', $idHoja, PDO::PARAM_INT);

                $stmtHoja->execute();
                $autorizadas += $stmtHoja->rowCount(); // opcional: cuántas filas realmente cambió
            }

            // 2) Actualiza la tabla presiones de forma SEGURA (sin interpolar variables)
            //    Si $idPresion no está definido, lanza excepción clara.
            if (!isset($idPresion)) {
                throw new RuntimeException('Falta la variable $idPresion para actualizar presiones.');
            }

            $sqlPresion = "
                UPDATE presiones
                SET presiones_estatus = 'AUTORIZADO'
                WHERE presiones_id      = :idPresion
            ";
            $stmtPresion = $conexion->prepare($sqlPresion);
            $stmtPresion->bindValue(':idPresion', (int) $idPresion, PDO::PARAM_INT);
            $stmtPresion->execute();

            $conexion->commit();

            // Nota: usabas $date; por consistencia lo dejo en $data
            $data = [
                'status' => 'success',
                'mensaje' => 'Se actualizó y cerró la presión',
                'detalle' => [
                    'hojas_actualizadas' => $autorizadas,
                    'presion_id' => (int) $idPresion
                ]
            ];
            tf_audit_log($conexion, 'presiones.close', 'presiones', (int)$idPresion, array(
                'hojas_actualizadas' => $autorizadas,
            ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $data = [
                'status' => 'error',
                'mensaje' => 'Error al actualizar',
                'error' => $e->getMessage()
            ];
        }
        break;
    case 8:
        $data = array();
        $consulta = "SELECT * FROM `hojasrequisicion` INNER JOIN emisores ON hojasrequisicion.hojaRequisicion_empresa = emisores.emisor_id INNER JOIN provedores ON hojasrequisicion.hojaRequisicion_proveedor = provedores.proveedor_id WHERE hojaRequisicion_id = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $dataHoja = $resultado->fetchAll(PDO::FETCH_ASSOC);
        $consulta = "SELECT `itemRequisicion_id`, `itemRequisicion_unidad`, `itemRequisicion_producto`, `itemRequisicion_iva`, `itemRequisicion_retenciones`, `itemRequisicion_banderaFlete`, `itemRequisicion_banderaFisica`, `itemRequisicion_banderaResico`, `itemRequisicion_precio`, `itemRequisicion_cantidad`, `itemRequisicion_estatus`, `hojaRequisicion_total` FROM itemrequisicion INNER JOIN hojasrequisicion ON itemRequisicion_idHoja = hojasrequisicion.hojaRequisicion_id WHERE itemRequisicion_idHoja = :id_hoja ORDER BY itemRequisicion_id ASC";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_hoja', (int)$idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $dataItems = $resultado->fetchAll(PDO::FETCH_ASSOC);
        array_push($data, array(
            'infoHoja' => $dataHoja[0],
            'items' => $dataItems
        ));
        break;
    case 9:
        $consulta = "SELECT `presiones_estatus` FROM `presiones` WHERE `presiones_id` = :id_presion";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_presion', (int)$idPresion, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 10:
        $data = "";
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;


function convertToString($Arr)
{
    $result = "";
    $indes = 0;
    foreach ($Arr as $cadenaAux) {
        $result = $result . $cadenaAux['itemRequisicion_producto'];
        if ($indes < count($Arr) - 1) {
            $result = $result . " /// ";
        }
        $indes++;
    }
    ;
    return $result;
}

function formatearMoneda($cantidad)
{
    // Asegurarse de que la cantidad sea un número
    if (!is_numeric($cantidad)) {
        return "Entrada no válida";
    }

    // Formatear la cantidad como moneda con separadores de miles
    return "$ " . number_format($cantidad, 2, '.', ',');
}

function putNameSection($clave)
{
    switch ($clave) {
        case 'MAT':
            return 'MATERIAL';
        case 'EQH':
            return 'MAQUINARIA';
        case 'IND':
            return 'INDIRECTOS';
        case 'MO':
            return 'MANO DE OBRA';
    }
}

function obtenerAbreviatura($metodoPago)
{
    $mapa = [
        "Efectivo" => "Efec",
        "Transferencia" => "Trans"
    ];

    return $mapa[$metodoPago] ?? "";
}

function obtenerNumeracionFinal($cadena)
{
    // Explota la cadena por el separador "-"
    $partes = explode('-', $cadena);

    // Verifica que haya al menos una parte
    if (count($partes) > 0) {
        // Obtiene la última parte
        $numero = end($partes);

        // Verifica que sea un número
        if (is_numeric($numero)) {
            return $numero;
        }
    }

    // Retorna null si no se encontró un número válido
    return null;
}
?>