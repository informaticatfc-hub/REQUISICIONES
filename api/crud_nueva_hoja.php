<?php
include_once 'conexion.php';
include_once 'auth.php';
require_once __DIR__ . '/bitacora.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

//Conexion con axios, por parametro POST
$_POST = api_get_request_data();

//Recepcion de datos por Axios
$id_Req = (isset($_POST['idReq'])) ? $_POST['idReq'] : '';
$accion = (isset($_POST['accion'])) ? $_POST['accion'] : '';
$clv_Emisor = (isset($_POST['id_emisor'])) ? $_POST['id_emisor'] : '';
$clv_Prov = (isset($_POST['id_prov'])) ? $_POST['id_prov'] : '';
$totalPagar = (isset($_POST['Total'])) ? $_POST['Total'] : '';
$formaPago = (isset($_POST['formaPago'])) ? $_POST['formaPago'] : '';
$fechaSolicitud = (isset($_POST['fechaSolicitud'])) ? $_POST['fechaSolicitud'] : '';
$datos = json_decode((isset($_POST['items'])) ? $_POST['items'] : '');
$observaciones = (isset($_POST['observaciones'])) ? $_POST['observaciones'] : '';
$time = (isset($_POST['time'])) ? $_POST['time'] : '';
$conceptoUnico = (isset($_POST['conceptoUnico'])) ? $_POST['conceptoUnico'] : '';

// --- RBAC + CSRF (Fase 3) ---
// case 1 = INSERT hoja + items (write); resto son lecturas para construir la UI.
$accionInt = (int)$accion;
if ($accionInt === 1) {
    api_require_csrf($_POST);
    tf_require_permission($conexion, 'requisiciones.create');
} else {
    tf_require_permission($conexion, 'requisiciones.view');
}

$currentUser = api_get_current_user($conexion);

switch ($accion) {
    case 1:
         try {
        $conexion->beginTransaction();

        $consulta = "SELECT `hojaRequisicion_id`
                FROM `hojasrequisicion`
                ORDER BY `hojaRequisicion_id` DESC
                LIMIT 1
                FOR UPDATE";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $ultimoIdHoja = $resultado->fetchColumn();
        $id_hoja = $ultimoIdHoja === false ? 1 : ((int)$ultimoIdHoja + 1);

        // 1) Lee y bloquea la fila para calcular $hoja sin condiciones de carrera
        $consulta = "SELECT `requisicion_Hojas` 
                    FROM `requisiciones` 
                    WHERE `requisicion_id` = :id_req
                    FOR UPDATE";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_req', $id_Req, PDO::PARAM_INT);
        $resultado->execute();

        $row = $resultado->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('No existe la requisición con id ' . $id_Req);
        }

        $hoja = (int)$row['requisicion_Hojas'];
        $hoja++; // siguiente número de hoja

        // 2) Inserta la hoja
        $consulta = "INSERT INTO `hojasrequisicion` (
            `hojaRequisicion_id`,
            `hojaRequisicion_idReq`,
            `hojaRequisicion_numero`,
            `hojaRequisicion_FechaSolicitud`,
            `hojaRequisicion_empresa`,
            `hojaRequisicion_proveedor`,
            `hojaRequisicion_observaciones`,
            `hojarequisicion_comentariosValidacion`,
            `hojarequisicion_comentariosAutorizacion`,
            `hojarequisicion_conceptoUnico`,
            `hojaRequisicion_formaPago`,
            `hojaRequisicion_fechaPago`,
            `hojasRequisicion_bancoPago`,
            `hojaRequisicion_total`,
            `hojarequisicion_adeudo`,
            `hojaRequisicion_estatus`
        ) VALUES (
            :id_hoja,
            :id_req,
            :numero,
            :fecha_solicitud,
            :empresa,
            :proveedor,
            :observaciones,
            :coment_val,
            :coment_aut,
            :concepto_unico,
            :forma_pago,
            :fecha_pago,
            :banco_pago,
            :total,
            :adeudo,
            :estatus
        )";

        $resultado = $conexion->prepare($consulta);

        $resultado->bindValue(':id_hoja',        $id_hoja, PDO::PARAM_INT);
        $resultado->bindValue(':id_req',         $id_Req,  PDO::PARAM_INT);
        $resultado->bindValue(':numero',         $hoja,    PDO::PARAM_INT);

        // fecha: si viene vacía, manda NULL real
        if (!isset($fechaSolicitud) || $fechaSolicitud === '' || $fechaSolicitud === null) {
            $resultado->bindValue(':fecha_solicitud', null, PDO::PARAM_NULL);
        } else {
            $resultado->bindValue(':fecha_solicitud', $fechaSolicitud, PDO::PARAM_STR);
        }

        $resultado->bindValue(':empresa',        $clv_Emisor, PDO::PARAM_INT);
        $resultado->bindValue(':proveedor',      $clv_Prov,   PDO::PARAM_INT);
        $resultado->bindValue(':observaciones',  $observaciones ?? '', PDO::PARAM_STR);

        // estos dos los estás usando como NULL explícito
        $resultado->bindValue(':coment_val',     null, PDO::PARAM_NULL);
        $resultado->bindValue(':coment_aut',     null, PDO::PARAM_NULL);

        $resultado->bindValue(':concepto_unico', $conceptoUnico ?? '', PDO::PARAM_STR);
        $resultado->bindValue(':forma_pago',     $formaPago, PDO::PARAM_STR);

        // también NULL reales
        $resultado->bindValue(':fecha_pago',     null, PDO::PARAM_NULL);
        $resultado->bindValue(':banco_pago',     null, PDO::PARAM_NULL);

        // DECIMAL como string para evitar líos de locale/comas
        $resultado->bindValue(':total',          $totalPagar, PDO::PARAM_STR);
        $resultado->bindValue(':adeudo',         $totalPagar, PDO::PARAM_STR);

        $resultado->bindValue(':estatus',        'NUEVO', PDO::PARAM_STR);

        $resultado->execute();
        tf_hoja_set_creator($conexion, $id_hoja, $currentUser);
        tf_hoja_creada_log($conexion, $id_hoja, $currentUser);

        // 3) Actualiza el contador de hojas con parámetros (sin concatenar)
        $consulta = "UPDATE `requisiciones` 
                    SET `requisicion_Hojas` = :hoja 
                    WHERE `requisiciones`.`requisicion_id` = :id_req";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':hoja',   $hoja,   PDO::PARAM_INT);
        $resultado->bindValue(':id_req', $id_Req, PDO::PARAM_INT);
        $resultado->execute();

        // 4) Inserta los ítems. Reusa el statement para rendimiento.
        $consulta = "INSERT INTO `itemrequisicion` (
            `itemRequisicion_id`,
            `itemRequisicion_idHoja`,
            `itemRequisicion_unidad`,
            `itemRequisicion_producto`,
            `itemRequisicion_iva`,
            `itemRequisicion_retenciones`,
            `itemRequisicion_banderaFlete`,
            `itemRequisicion_banderaFisica`,
            `itemRequisicion_banderaResico`,
            `itemRequisicion_banderaISR`,
            `itemRequisicion_precio`,
            `itemRequisicion_cantidad`,
            `itemRequisicion_parcialidad`,
            `itemRequisicion_estatus`
        ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'N')";
        $stmtItem = $conexion->prepare($consulta);

        foreach ($datos as $item) {
            $Unidad        = $item->Unidad;
            $Producto      = $item->Nombre;
            $Precio        = (float)$item->UnitedPrice;
            $IVA           = $item->IVA;
            $Ret           = $item->Retenciones;
            $cantidad      = $item->Cantidad;
            $banderaFlete  = (int)$item->bandFlete;
            $banderaFisica = (int)$item->bandFisico;
            $banderaResico = (int)$item->bandResico;
            $banderaISR    = (int)$item->bandISR;

            $stmtItem->execute([
                $id_hoja, $Unidad, $Producto, $IVA, $Ret,
                $banderaFlete, $banderaFisica, $banderaResico, $banderaISR,
                $Precio, $cantidad
            ]);
        }

        $conexion->commit();

        $data = $id_hoja;
        tf_audit_log($conexion, 'requisiciones.hoja.create', 'hojasrequisicion', (int)$id_hoja, array(
            'id_req' => (int)$id_Req,
            'numero' => $hoja,
            'total' => $totalPagar,
            'forma_pago' => $formaPago,
            'proveedor' => (int)$clv_Prov,
            'empresa' => (int)$clv_Emisor,
            'items_count' => is_array($datos) ? count($datos) : 0,
        ));
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log('[crud_nueva_hoja] Error: ' . $e->getMessage());
            $data = ['error' => true, 'mensaje' => 'Error al crear la hoja.'];
        }
        break;
    case 2:
        $consulta = "SELECT `emisor_id`,`emisor_nombre`,`emisor_rfc`,`emisor_direccion`,`emisor_telefono`,`emisor_fax`,`emisor_zipCode` FROM `emisores`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 3:
        // CORREGIDO: Solo proveedores ACTIVOS, ordenados por nombre
        $consulta = "SELECT `proveedor_id`,`proveedor_nombre` 
                     FROM `provedores` 
                     WHERE `proveedor_estatus` = 'ACTIVO' 
                     ORDER BY `proveedor_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 4:
        // CORREGIDO: Parametrizado con bindValue (antes concatenaba $clv_Prov)
        $consulta = "SELECT `proveedor_id`, `proveedor_rfc`, `proveedor_clabe`,
                     `proveedor_numeroCuenta`, `proveedor_sucursal`, `proveedor_refBanco`,
                     `proveedor_banco`, `proveedor_email`, `proveedor_telefono`, 
                     `presiones_tarjetaBanco` 
                     FROM `provedores` 
                     WHERE `proveedor_id` = :id_prov";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindValue(':id_prov', $clv_Prov, PDO::PARAM_INT);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 5:
        $data = array($currentUser);
        break;
    case 6:
        $consulta = "SELECT * FROM `obras` WHERE `obras_estatus` = 'ACTIVO' ORDER BY `obras_nombre`";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;

function convertFolio($folioInt)
{
    if ($folioInt < 10) {
        return "0" . "0" . $folioInt;
    } else if ($folioInt < 100) {
        return "0" . $folioInt;
    } else {
        return $folioInt;
    }
}