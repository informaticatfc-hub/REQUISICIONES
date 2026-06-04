<?php
require_once __DIR__ . '/bitacora.php';
include_once 'conexion.php';
include_once 'auth.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$obras = json_decode((isset($_POST['obras'])) ? $_POST['obras'] : '', true);
$autorizado = (isset($_POST['autorizado'])) ? $_POST['autorizado'] : '';
$idHoja = (isset($_POST['idHoja'])) ? $_POST['idHoja'] : '';
$adeudo = (isset($_POST['parcial'])) ? $_POST['parcial'] : '';
$coments = (isset($_POST['coments'])) ? $_POST['coments'] : '';
$presion = (isset($_POST['presion'])) ? $_POST['presion'] : [];
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limite = isset($_POST['limite']) ? (int)$_POST['limite'] : 20;
$search = isset($_POST['search']) ? trim((string)$_POST['search']) : '';

// --- RBAC + CSRF (Fase 3) ---
// case 1 (echo user) y 2-3 (listas) son lecturas; 4-7 son writes (autorizar / actualizar pagos).
// case 8 (resumen via vista v_presiones_summary) tambien es lectura (Fase 5).
$writeActions = array(4, 5, 6, 7);
$accionInt = (int)$accion;
$currentUser = api_get_current_user($conexion);
if (in_array($accionInt, $writeActions, true)) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'presiones.authorize');
} elseif ($accionInt === 11) {
    // F-M2: marcar hoja como PAGADA requiere permiso finanzas.pagar
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'finanzas.pagar');
} else {
    if (!tf_has_permission('presiones.view', $currentUser) && !tf_has_permission('direccion.view', $currentUser)) {
        tf_audit_log($conexion, 'access.denied', 'presiones', null, [
            'reason' => 'presiones_view_forbidden',
            'accion' => $accionInt,
            'uri'    => $_SERVER['REQUEST_URI'] ?? null,
        ]);
        api_json_error('No tienes permiso para ver presiones', 403);
    }
}

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
        $data = array();
        $primeraInt = 0;
        $colapseAtr = "";
        $colapseband = "true";
        $colapseShow = "show";
        foreach ($obras as $obra) {
            $consulta = "SELECT 
                hojaRequisicion_id, 
                requisicion_Clave, 
                requisicion_Numero, 
                hojaRequisicion_observaciones, 
                hojaRequisicion_numero, 
                requisicion_Nombre, 
                proveedor_nombre, 
                hojaRequisicion_total, 
                hojaRequisicion_formaPago, 
                hojaRequisicion_estatus, 
                presiones_estatus, 
                hojaRequisicion_fechaPago, 
                hojasRequisicion_bancoPago,
                hojarequisicion_adeudo,
                presiones_id,
                hojarequisicion_conceptoUnico
            FROM 
                requisicionesligadas 
            JOIN 
                presiones ON presiones.presiones_id =  requisicionesLigada_presionID
            JOIN 
                requisiciones ON requisiciones.requisicion_id = requisicionesLigadas_requisicionID 
            JOIN 
                hojasrequisicion ON hojasrequisicion.hojaRequisicion_id = requisicionesLigadas_hojaID 
            JOIN 
                provedores ON hojasrequisicion.hojaRequisicion_proveedor = provedores.proveedor_id 
            WHERE 
                presiones.presiones_nombre LIKE :nombre 
            AND
                presiones.presiones_estatus = 'PENDIENTE'
            ORDER BY 
                requisicion_Clave DESC";
            // Prepara la consulta
            $resultado = $conexion->prepare($consulta);
            // Define el valor del parámetro
            $nombre = '%' . $obra['obras_nombre'] . '%';
            // Asigna el valor al marcador de posición
            $resultado->bindParam(':nombre', $nombre);
            // Ejecuta la consulta
            $resultado->execute();
            // Obtiene los resultados
            $dataBD = $resultado->fetchAll(PDO::FETCH_ASSOC);
            if (count($dataBD) > 0) {
                $dataPresion = array();
                foreach ($dataBD as $hoja) {
                    $consulta = "SELECT `itemRequisicion_producto` FROM `itemrequisicion` WHERE `itemRequisicion_idHoja` = :id_hoja";
                    $resultado = $conexion->prepare($consulta);
                    $resultado->bindValue(':id_hoja', (int)$hoja['hojaRequisicion_id'], PDO::PARAM_INT);
                    $resultado->execute();
                    $dataitms = $resultado->fetchAll(PDO::FETCH_ASSOC);
                    array_push($dataPresion, array(
                        'id_hoja' => $hoja['hojaRequisicion_id'],
                        'id_presion' => $hoja['presiones_id'],
                        'formaPago' => obtenerAbreviatura($hoja['hojaRequisicion_formaPago']),
                        'NumReq' => obtenerNumeracionFinal($hoja['requisicion_Numero']) . " Hoja Numero: " . $hoja['hojaRequisicion_numero'],
                        'clave' => $hoja['requisicion_Clave'],
                        'concepto' => empty($hoja['hojarequisicion_conceptoUnico']) ? convertToString($dataitms) : $hoja['hojarequisicion_conceptoUnico'],
                        'proveedor' => $hoja['proveedor_nombre'],
                        'total' => $hoja['hojaRequisicion_total'],
                        'adeudo' => $hoja['hojarequisicion_adeudo'],
                        'Observaciones' => $hoja['hojaRequisicion_observaciones'],
                        "Banco" => $hoja['hojasRequisicion_bancoPago'],
                        "Fecha" => $hoja['hojaRequisicion_fechaPago'],
                        "HojaEstatus" => $hoja['hojaRequisicion_estatus'],
                        "PresionEstatus" => $hoja['presiones_estatus']
                    ));
                };
                if ($primeraInt > 0) {
                    $colapseAtr = "collapsed";
                    $colapseband = "false";
                    $colapseShow = "";
                }
                array_push($data, array(
                    'Nombre_Obra' => $obra['obras_nombre'],
                    'Presion_Obra' => $dataPresion,
                    'colapse_Atr' => $colapseAtr,
                    'colapse_band' => $colapseband,
                    'colapse_show' => $colapseShow,
                    'total_Glabal' => sumaTotal($conexion, $dataBD[0]['presiones_id'], "Propuesto"),
                    'total_Global_Aut' => sumaTotal($conexion, $dataBD[0]['presiones_id'], "Auturizado"),
                    'total_Efectivo' => sumaTotalEfectivo($conexion, $dataBD[0]['presiones_id'], "Propuesto"),
                    'total_Efectivo_Aut' => sumaTotalEfectivo($conexion, $dataBD[0]['presiones_id'], "Auturizado"),
                    'total_Transferencia' => sumaTotalTrans($conexion, $dataBD[0]['presiones_id'], "Propuesto"),
                    'total_Transferencia_Aut' => sumaTotalTrans($conexion, $dataBD[0]['presiones_id'], "Auturizado"),
                    'total_Global_Rechazado' => sumaTotal($conexion, $dataBD[0]['presiones_id'], "Rechazado"),
                    'total_Efectivo_Rechazado' => sumaTotalEfectivo($conexion, $dataBD[0]['presiones_id'], "Rechazado"),
                    'total_Transferencia_Rechazado' => sumaTotalTrans($conexion, $dataBD[0]['presiones_id'], "Rechazado")
                ));
                $primeraInt++;
            }
        }
        break;
    case 4:
        if ($autorizado) {
            $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_estatus` = 'AUTORIZADA', `hojarequisicion_adeudo` = :adeudo, `hojaRequisicion_observaciones` = :observaciones WHERE `hojasrequisicion`.`hojaRequisicion_id` = :idHoja";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindValue(':adeudo', (float)$adeudo, PDO::PARAM_STR);
            $resultado->bindValue(':observaciones', $coments, PDO::PARAM_STR);
            $resultado->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $resultado->execute();
            $data = [
                "success" => true
            ];
            tf_audit_log($conexion, 'presiones.authorize.hoja', 'hojasrequisicion', (int)$idHoja, array(
                'adeudo' => (float)$adeudo,
                'observaciones' => $coments,
                'estatus' => 'AUTORIZADA',
            ));
            tf_hoja_estatus_log($conexion, (int)$idHoja, null, 'AUTORIZADA', $coments, $currentUser);
            tf_hoja_set_validador($conexion, (int)$idHoja, $currentUser);
        } else {
            $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_estatus` = 'LIGADA', `hojarequisicion_adeudo` = 0, `hojaRequisicion_observaciones` = :observaciones WHERE `hojasrequisicion`.`hojaRequisicion_id` = :idHoja";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindValue(':observaciones', $coments, PDO::PARAM_STR);
            $resultado->bindParam(':idHoja', $idHoja, PDO::PARAM_INT);
            $resultado->execute();
            $data = [
                "success" => true
            ];
            tf_audit_log($conexion, 'presiones.reject.hoja', 'hojasrequisicion', (int)$idHoja, array(
                'observaciones' => $coments,
                'estatus' => 'LIGADA',
            ));
            tf_hoja_estatus_log($conexion, (int)$idHoja, null, 'LIGADA', $coments, $currentUser);
            tf_hoja_set_validador($conexion, (int)$idHoja, $currentUser);
        }
        break;
    case 5:
        $consulta = "UPDATE `hojasrequisicion` SET `hojaRequisicion_estatus` = 'LIGADA' WHERE `hojasrequisicion`.`hojaRequisicion_id` = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindParam(':id_hoja', $idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $data = 0;
        tf_audit_log($conexion, 'presiones.hoja.toLigada', 'hojasrequisicion', (int)$idHoja, null);
        tf_hoja_estatus_log($conexion, (int)$idHoja, null, 'LIGADA', null, $currentUser);
        break;
    case 6:
        $consulta = "UPDATE `hojasrequisicion` SET `hojarequisicion_adeudo` = :adeudo , `hojaRequisicion_observaciones` = :observaciones WHERE `hojasrequisicion`.`hojaRequisicion_id` = :id_hoja";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':adeudo', (float)$adeudo, PDO::PARAM_STR);
        $resultado->bindValue(':observaciones', $coments, PDO::PARAM_STR);
        $resultado->bindParam(':id_hoja', $idHoja, PDO::PARAM_INT);
        $resultado->execute();
        $data = 0;
        tf_audit_log($conexion, 'presiones.hoja.updateAdeudo', 'hojasrequisicion', (int)$idHoja, array(
            'adeudo' => (float)$adeudo,
            'observaciones' => $coments,
        ));
        break;
    case 7:
        try {
            $registrosProcesados = 0;
            $registrosFallidos = [];

            foreach ($presion as $registro) {
                $status = ($registro['adeudo'] == 0) ? "RECHAZADA" : "AUTORIZADA";

                $consulta = "UPDATE hojasrequisicion 
                     SET hojarequisicion_adeudo = :adeudo, 
                         hojaRequisicion_observaciones = :observaciones,
                         hojaRequisicion_estatus = :estatus
                     WHERE hojaRequisicion_id = :id_hoja";

                $resultado = $conexion->prepare($consulta);
                $resultado->bindValue(':adeudo', (float)($registro['adeudo'] ?? 0), PDO::PARAM_STR);
                $resultado->bindValue(':observaciones', $registro['Observaciones'] ?? '', PDO::PARAM_STR);
                $resultado->bindValue(':id_hoja', $registro['id_hoja'], PDO::PARAM_INT);
                $resultado->bindValue(':estatus', $status, PDO::PARAM_STR);

                if ($resultado->execute()) {
                    $registrosProcesados++;
                } else {
                    $registrosFallidos[] = $registro['id_hoja'];
                }
            }

            if (count($registrosFallidos) > 0) {
                $data = [
                    'status' => 'error',
                    'mensaje' => 'Algunos registros no pudieron guardarse',
                    'fallos' => $registrosFallidos,
                    'procesados' => $registrosProcesados
                ];
            } else {
                $data = [
                    'status' => 'success',
                    'mensaje' => 'Todos los registros fueron guardados correctamente',
                    'procesados' => $registrosProcesados
                ];
            }
            tf_audit_log($conexion, 'presiones.batch.authorize', 'hojasrequisicion', null, array(
                'procesados' => $registrosProcesados,
                'fallos' => $registrosFallidos,
            ));
        } catch (Exception $e) {
            $data = [
                'status' => 'error',
                'mensaje' => 'Error inesperado al guardar los registros',
                'error' => $e->getMessage()
            ];
        }

        break;
    case 8:
        // Fase 5: Resumen agregado de presiones usando la vista v_presiones_summary
        // Calcula totales/adeudo desde las hojas reales (los campos
        // presiones_adeudo y presiones_gastosObra estan en 0 en el dump).
        $obraFiltro = isset($_POST['obra']) ? (int)$_POST['obra'] : 0;
        if ($obraFiltro > 0) {
            $consulta = "SELECT * FROM `v_presiones_summary`
                          WHERE presiones_obra = :obra
                          ORDER BY presiones_fechaCreacion DESC, presiones_id DESC";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindValue(':obra', $obraFiltro, PDO::PARAM_INT);
        } else {
            $consulta = "SELECT * FROM `v_presiones_summary`
                          ORDER BY presiones_fechaCreacion DESC, presiones_id DESC";
            $resultado = $conexion->prepare($consulta);
        }
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 9:
        // Fase 8: Detalle de hojas ligadas por presion para dashboard KPI.
        // Incluye hoja, requisicion, proveedor y estatus para mostrar trazabilidad completa.
        $obraFiltro  = isset($_POST['obra'])  ? (int)$_POST['obra'] : 0;
        $desdeFiltro = isset($_POST['desde']) ? trim((string)$_POST['desde']) : '';
        $hastaFiltro = isset($_POST['hasta']) ? trim((string)$_POST['hasta']) : '';

        $where = array();
        $params = array();

        if ($obraFiltro > 0) {
            $where[] = 'p.presiones_obra = :obra';
            $params[':obra'] = $obraFiltro;
        }
        if ($desdeFiltro !== '') {
            $where[] = 'DATE(p.presiones_fechaCreacion) >= :desde';
            $params[':desde'] = $desdeFiltro;
        }
        if ($hastaFiltro !== '') {
            $where[] = 'DATE(p.presiones_fechaCreacion) <= :hasta';
            $params[':hasta'] = $hastaFiltro;
        }

        $sqlWhere = '';
        if (!empty($where)) {
            $sqlWhere = ' WHERE ' . implode(' AND ', $where);
        }

        $consulta = "SELECT
                p.presiones_id,
                p.presiones_nombre,
                p.presiones_semana,
                p.presiones_dia,
                p.presiones_fechaCreacion,
                p.presiones_obra,
                o.obras_nombre,
                h.hojaRequisicion_id,
                h.hojaRequisicion_numero,
                h.hojaRequisicion_estatus,
                h.hojaRequisicion_total,
                h.hojarequisicion_adeudo,
                r.requisicion_id,
                r.requisicion_Numero,
                r.requisicion_Clave,
                r.requisicion_Nombre,
                pr.proveedor_nombre
            FROM `requisicionesligadas` rl
            INNER JOIN `presiones` p
                ON p.presiones_id = rl.requisicionesLigada_presionID
            LEFT JOIN `obras` o
                ON o.obras_id = p.presiones_obra
            INNER JOIN `hojasrequisicion` h
                ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
            LEFT JOIN `requisiciones` r
                ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
            LEFT JOIN `provedores` pr
                ON pr.proveedor_id = h.hojaRequisicion_proveedor"
            . $sqlWhere .
            " ORDER BY p.presiones_fechaCreacion DESC, p.presiones_id DESC, h.hojaRequisicion_numero ASC";

        $resultado = $conexion->prepare($consulta);
        foreach ($params as $k => $v) {
            $resultado->bindValue($k, $v);
        }
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 10:
        // UX-11: Paginación server-side para tab de pendientes de autorización.
        // Devuelve filas planas para la tabla y metadatos de paginación.
        $page = max(1, $page);
        $limite = max(5, min(100, $limite));
        $offset = ($page - 1) * $limite;

        $where = " WHERE p.presiones_estatus = 'PENDIENTE'";
        $params = array();
        if ($search !== '') {
            $where .= " AND (
                o.obras_nombre LIKE :q
                OR r.requisicion_Clave LIKE :q
                OR r.requisicion_Numero LIKE :q
                OR pr.proveedor_nombre LIKE :q
                OR COALESCE(h.hojarequisicion_conceptoUnico, '') LIKE :q
            )";
            $params[':q'] = '%' . $search . '%';
        }

        $sqlTotal = "SELECT COUNT(*)
            FROM `requisicionesligadas` rl
            INNER JOIN `presiones` p ON p.presiones_id = rl.requisicionesLigada_presionID
            INNER JOIN `hojasrequisicion` h ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
            LEFT JOIN `requisiciones` r ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
            LEFT JOIN `obras` o ON o.obras_id = p.presiones_obra
            LEFT JOIN `provedores` pr ON pr.proveedor_id = h.hojaRequisicion_proveedor"
            . $where;
        $stTotal = $conexion->prepare($sqlTotal);
        foreach ($params as $k => $v) {
            $stTotal->bindValue($k, $v);
        }
        $stTotal->execute();
        $total = (int)$stTotal->fetchColumn();

        $consulta = "SELECT
                o.obras_nombre AS Nombre_Obra,
                r.requisicion_Clave AS clave,
                CONCAT(COALESCE(r.requisicion_Numero,''), ' Hoja Numero: ', COALESCE(h.hojaRequisicion_numero,'')) AS NumReq,
                pr.proveedor_nombre AS proveedor,
                COALESCE(NULLIF(h.hojarequisicion_conceptoUnico,''), '(Sin concepto)') AS concepto,
                h.hojaRequisicion_total AS total,
                h.hojaRequisicion_fechaPago AS fechaPago
            FROM `requisicionesligadas` rl
            INNER JOIN `presiones` p ON p.presiones_id = rl.requisicionesLigada_presionID
            INNER JOIN `hojasrequisicion` h ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
            LEFT JOIN `requisiciones` r ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
            LEFT JOIN `obras` o ON o.obras_id = p.presiones_obra
            LEFT JOIN `provedores` pr ON pr.proveedor_id = h.hojaRequisicion_proveedor"
            . $where .
            " ORDER BY h.hojaRequisicion_id ASC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset;

        $resultado = $conexion->prepare($consulta);
        foreach ($params as $k => $v) {
            $resultado->bindValue($k, $v);
        }
        $resultado->execute();
        $rows = $resultado->fetchAll(PDO::FETCH_ASSOC);

        $data = array(
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int)ceil($total / $limite)),
            'limite' => $limite,
        );
        break;

    case 11:
        // F-M2: Marcar hoja como PAGADA con folio, banco y fecha de pago.
        $folioPago  = isset($_POST['folio_pago'])  ? trim((string)$_POST['folio_pago'])  : '';
        $bancoPago  = isset($_POST['banco_id'])    ? (int)$_POST['banco_id']             : 0;
        $fechaPago  = isset($_POST['fecha_pago'])  ? trim((string)$_POST['fecha_pago'])  : '';

        if ((int)$idHoja <= 0) {
            api_json_error('ID de hoja inválido', 422);
        }
        if ($folioPago === '') {
            api_json_error('El folio de pago es obligatorio', 422);
        }
        if ($fechaPago === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
            api_json_error('La fecha de pago es inválida', 422);
        }

        try {
            $conexion->beginTransaction();

            // Verificar que la hoja existe y está AUTORIZADA
            $stCheck = $conexion->prepare(
                "SELECT `hojaRequisicion_estatus`, `hojaRequisicion_id` FROM `hojasrequisicion` WHERE `hojaRequisicion_id` = ? FOR UPDATE"
            );
            $stCheck->execute(array((int)$idHoja));
            $hojaRow = $stCheck->fetch(PDO::FETCH_ASSOC);

            if (!$hojaRow) {
                $conexion->rollBack();
                api_json_error('La hoja no existe', 404);
            }
            if ($hojaRow['hojaRequisicion_estatus'] !== 'AUTORIZADA') {
                $conexion->rollBack();
                api_json_error('Solo se pueden marcar como PAGADAS las hojas en estado AUTORIZADA', 409);
            }

            $sqlUpd = "UPDATE `hojasrequisicion` SET
                `hojaRequisicion_estatus`   = 'PAGADA',
                `hojaRequisicion_folioPago` = ?,
                `hojaRequisicion_bancoPago` = ?,
                `hojaRequisicion_fechaPago` = ?
                WHERE `hojaRequisicion_id` = ?";
            $stUpd = $conexion->prepare($sqlUpd);
            $stUpd->execute(array(
                $folioPago,
                $bancoPago > 0 ? $bancoPago : null,
                $fechaPago,
                (int)$idHoja
            ));

            $conexion->commit();

            tf_audit_log($conexion, 'finanzas.pago.hoja', 'hojasrequisicion', (int)$idHoja, array(
                'folio_pago' => $folioPago,
                'banco_id'   => $bancoPago,
                'fecha_pago' => $fechaPago,
                'estatus'    => 'PAGADA',
            ));
            tf_hoja_estatus_log($conexion, (int)$idHoja, null, 'PAGADA', 'Folio: ' . $folioPago, $currentUser);

            $data = array('status' => 'ok', 'message' => 'Hoja marcada como PAGADA');
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            api_json_error('No se pudo registrar el pago', 500, array('details' => $e->getMessage()));
        }
        break;

    case 12:
        // F-M2: Lista paginada de hojas AUTORIZADAS pendientes de pago (para rol finanzas).
        $page   = max(1, $page);
        $limite = max(5, min(100, $limite));
        $offset = ($page - 1) * $limite;

        $where12  = " WHERE h.hojaRequisicion_estatus = 'AUTORIZADA'";
        $params12 = array();

        if ($search !== '') {
            $where12 .= " AND (
                o.obras_nombre       LIKE :q
                OR r.requisicion_Clave  LIKE :q
                OR pr.proveedor_nombre  LIKE :q
                OR COALESCE(h.hojarequisicion_conceptoUnico, '') LIKE :q
            )";
            $params12[':q'] = '%' . $search . '%';
        }

        $sqlT12 = "SELECT COUNT(*)
            FROM `hojasrequisicion` h
            LEFT JOIN `requisicionesligadas` rl ON rl.requisicionesLigadas_hojaID = h.hojaRequisicion_id
            LEFT JOIN `presiones` p  ON p.presiones_id = rl.requisicionesLigada_presionID
            LEFT JOIN `requisiciones` r ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
            LEFT JOIN `obras` o  ON o.obras_id = p.presiones_obra
            LEFT JOIN `provedores` pr ON pr.proveedor_id = h.hojaRequisicion_proveedor"
            . $where12;
        $stT12 = $conexion->prepare($sqlT12);
        foreach ($params12 as $k => $v) { $stT12->bindValue($k, $v); }
        $stT12->execute();
        $total12 = (int)$stT12->fetchColumn();

        $sql12 = "SELECT
                h.hojaRequisicion_id,
                h.hojaRequisicion_numero,
                h.hojaRequisicion_estatus,
                h.hojaRequisicion_total,
                h.hojarequisicion_adeudo,
                h.hojaRequisicion_observaciones,
                COALESCE(NULLIF(h.hojarequisicion_conceptoUnico,''), '(Sin concepto)') AS concepto,
                o.obras_nombre         AS obra_nombre,
                r.requisicion_Clave    AS clave,
                r.requisicion_Numero   AS numero_req,
                pr.proveedor_nombre    AS proveedor,
                pr.proveedor_clabe     AS proveedor_clabe,
                pr.proveedor_rfc       AS proveedor_rfc
            FROM `hojasrequisicion` h
            LEFT JOIN `requisicionesligadas` rl ON rl.requisicionesLigadas_hojaID = h.hojaRequisicion_id
            LEFT JOIN `presiones` p  ON p.presiones_id = rl.requisicionesLigada_presionID
            LEFT JOIN `requisiciones` r ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
            LEFT JOIN `obras` o  ON o.obras_id = p.presiones_obra
            LEFT JOIN `provedores` pr ON pr.proveedor_id = h.hojaRequisicion_proveedor"
            . $where12 .
            " ORDER BY h.hojaRequisicion_id ASC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset;

        $st12 = $conexion->prepare($sql12);
        foreach ($params12 as $k => $v) { $st12->bindValue($k, $v); }
        $st12->execute();
        $rows12 = $st12->fetchAll(PDO::FETCH_ASSOC);

        $data = array(
            'rows'   => $rows12,
            'total'  => $total12,
            'page'   => $page,
            'pages'  => max(1, (int)ceil($total12 / $limite)),
            'limite' => $limite,
        );
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
    };
    return $result;
}

function formatearMoneda($cantidad)
{
    // Asegurarse de que la cantidad sea un número
    if (!is_numeric($cantidad)) {
        return "Entrada no válida";
    }

    // Formatear la cantidad como moneda
    return "$" . number_format($cantidad, 2, '.', '');
}

function sumaTotal($conexion, $idPresion, $tipoDeCuenta)
{
    switch ($tipoDeCuenta) {
        case "Propuesto":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Auturizado":
            $consulta = "SELECT SUM(hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Rechazado":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total - hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_estatus != 'LIGADA'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'] ?? 0;
            break;
        default:
            return "Dato no válido";
            break;
    }
}

function sumaTotalEfectivo($conexion, $idPresion, $tipoDeCuenta)
{
    switch ($tipoDeCuenta) {
        case "Propuesto":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Efectivo'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Auturizado":
            $consulta = "SELECT SUM(hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Efectivo'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Rechazado":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total - hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_estatus != 'LIGADA'
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Efectivo'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'] ?? 0;
            break;
        default:
            return "Dato no válido";
            break;
    }
}

function sumaTotalTrans($conexion, $idPresion, $tipoDeCuenta)
{
    switch ($tipoDeCuenta) {
        case "Propuesto":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Transferencia'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Auturizado":
            $consulta = "SELECT SUM(hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Transferencia'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'];
            break;
        case "Rechazado":
            $consulta = "SELECT SUM(hojasrequisicion.hojaRequisicion_total - hojasrequisicion.hojarequisicion_adeudo) AS total_sumatoria
            FROM requisicionesligadas
            INNER JOIN hojasrequisicion
            ON requisicionesligadas.requisicionesLigadas_hojaID = hojasrequisicion.hojaRequisicion_id
            WHERE requisicionesligadas.requisicionesLigada_presionID = :idPresion
            AND hojasrequisicion.hojaRequisicion_estatus != 'LIGADA'
            AND hojasrequisicion.hojaRequisicion_formaPago = 'Transferencia'";

            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':idPresion', $idPresion, PDO::PARAM_INT); // Vincula la variable $idPresion al parámetro :idPresion
            $resultado->execute();
            $respuestaBD = $resultado->fetch(PDO::FETCH_ASSOC);
            return $respuestaBD['total_sumatoria'] ?? 0;
            break;
        default:
            return "Dato no válido";
            break;
    }
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

function obtenerAbreviatura($metodoPago) {
    $mapa = [
        "Efectivo" => "Efec",
        "Transferencia" => "Trans"
    ];
    
    return $mapa[$metodoPago] ?? "";
}
