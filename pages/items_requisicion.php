<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['requisiciones.view', 'requisiciones.edit', 'direccion.view', 'presiones.authorize'], 'No tienes permiso para ver los items de la requisición');

// Permisos para la UI (botones de edicion/validacion). Sin esto el JS los recibe como false.
$__canReqEdit   = tf_has_permission('requisiciones.create', $__user) || tf_has_permission('requisiciones.edit', $__user);
$__canDireccion = tf_has_permission('direccion.view', $__user) || tf_user_has_direction_access($__user);

$tf_page_title = 'Items de la Requisicion';
$tf_active_nav = 'obras';
$tf_breadcrumb = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Requisiciones', './requisiciones.php'], ['Hojas de la Requisicion', './hojas_requisicion.php'], ['Items de la Requisicion', '#']];
$tf_user = [
    'name'        => $__user['user_name'] ?? '',
    'role'        => $__user['role']['name'] ?? '',
    'roleCode'    => $__user['role']['code'] ?? '',
    'initials'    => '',
    'permissions' => $__user['permissions'] ?? [],
];
$tf_show_direccion = in_array(($__user['role']['code'] ?? ''), ['director', 'admin', 'desarrollador'], true)
    || tf_user_has_direction_access($__user);
$tf_show_admin = in_array(($__user['role']['code'] ?? ''), ['admin', 'desarrollador'], true);
$tf_user_id_js = (string)($__user['user_id'] ?? '');

$tf_extra_head = <<<'CSS'
<style>
[x-cloak]{display:none!important;}
.detail-row { display:grid; grid-template-columns: 1fr 2fr; gap:14px; border-bottom:1px solid var(--tf-border); padding:7px 0; }
.section-title { font-size:.95rem; font-weight:700; margin:14px 0 8px; color:var(--tf-text); }
@media (max-width: 991.98px){ .detail-row { grid-template-columns: 1fr; gap:4px; } }
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppItems" class="tf-page-inner" x-data="itemRequisicionApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <div class="page-hdr">
            <div class="page-hdr-left">
                <h2 class="page-title">Requisicion <span x-text="Numero_Req"></span> - Hoja <span x-text="hojaActiva.hojaRequisicion_numero || ''"></span></h2>
                <p class="page-lead">Detalle completo de los items, encabezado y observaciones de la requisicion.</p>
            </div>
            <div class="page-hdr-right">
                <button type="button" class="btn btn-success" x-on:click="cambiarProveedor()" x-show="isEditableSheet">Cambiar Proveedor</button>
                <button type="button" class="btn btn-secondary" x-on:click="cambiarFormaPago(hojaActiva.hojaRequisicion_formaPago)" x-show="isEditableSheet">Forma de Pago</button>
                <button type="button" class="btn btn-danger" x-on:click="imprimirReq">Imprimir</button>
            </div>
        </div>

        <div class="ops-hero-grid">
            <div class="quick-tile"><span class="quick-tile-label">Requisicion / Hoja</span><span class="quick-tile-value small"><span x-text="Numero_Req"></span> - Hoja <span x-text="hojaActiva.hojaRequisicion_numero || ''"></span></span></div>
            <div class="quick-tile"><span class="quick-tile-label">Estatus</span><span class="quick-tile-value small" x-text="hojaActiva.hojaRequisicion_estatus || ''"></span></div>
            <div class="quick-tile"><span class="quick-tile-label">Total</span><span class="quick-tile-value small" x-text="formatearMoneda(hojaActiva.hojaRequisicion_total || 0, true)"></span></div>
        </div>

        <div class="table-wrapper mb-3">
            <div style="padding:14px 18px;border-bottom:1px solid var(--tf-border);display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:.875rem;font-weight:600;color:var(--tf-text)">Encabezado - <span x-text="Numero_Req"></span> Hoja <span x-text="hojaActiva.hojaRequisicion_numero || ''"></span></span>
                <span class="badge bg-primary" x-text="hojaActiva.hojaRequisicion_estatus || ''"></span>
            </div>
            <div style="padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:18px">
                <div>
                    <p class="section-title">Datos de la Requisicion</p>
                    <div class="detail-row"><span>Clave:</span><strong x-text="clve"></strong></div>
                    <div class="detail-row"><span>Fecha Solicitud:</span><strong x-text="hojaActiva.hojaRequisicion_FechaSolicitud || ''"></strong></div>
                    <p class="section-title">Datos del Emisor</p>
                    <div class="detail-row"><span>Empresa:</span><strong x-text="hojaActiva.emisor_nombre || ''"></strong></div>
                    <div class="detail-row"><span>RFC:</span><strong x-text="hojaActiva.emisor_rfc || ''"></strong></div>
                    <div class="detail-row"><span>Direccion:</span><strong x-text="hojaActiva.emisor_direccion || ''"></strong></div>
                    <div class="detail-row"><span>Telefono:</span><strong x-text="hojaActiva.emisor_telefono || ''"></strong></div>
                    <div class="detail-row"><span>C.P.:</span><strong x-text="hojaActiva.emisor_zipCode || ''"></strong></div>
                </div>
                <div>
                    <p class="section-title">Datos del Proveedor</p>
                    <div class="detail-row"><span>Empresa:</span><strong x-text="hojaActiva.proveedor_nombre || ''"></strong></div>
                    <div class="detail-row"><span>RFC:</span><strong x-text="hojaActiva.proveedor_rfc || ''"></strong></div>
                    <div class="detail-row"><span>Banco:</span><strong x-text="hojaActiva.proveedor_banco || ''"></strong></div>
                    <div class="detail-row"><span>Cuenta:</span><strong x-text="hojaActiva.proveedor_numeroCuenta || ''"></strong></div>
                    <div class="detail-row"><span>CLABE:</span><strong x-text="hojaActiva.proveedor_clabe || ''"></strong></div>
                    <div class="detail-row"><span>Sucursal:</span><strong x-text="hojaActiva.proveedor_sucursal || ''"></strong></div>
                    <div class="detail-row"><span>Referencia:</span><strong x-text="hojaActiva.presiones_tarjetaBanco || ''"></strong></div>
                    <div class="detail-row"><span>Email:</span><strong x-text="hojaActiva.proveedor_email || ''"></strong></div>
                    <div class="detail-row"><span>Telefono:</span><strong x-text="hojaActiva.proveedor_telefono || ''"></strong></div>
                </div>
            </div>
        </div>

        <div class="row" x-show="isEditableSheet">
            <div class="col d-flex align-items-end mb-3"><button type="button" class="btn btn-primary ms-auto" x-on:click="agregarItem" id="btnAddItem"><span class="fw-bold text-white">Agregar item a esta requisicion</span></button></div>
        </div>

        <div class="table-wrapper"><div class="overflow-auto">
            <table id="example" class="table table-prof table-hover w-100">
                <thead class="table-dark"><tr><th scope="col" class="text-center">Unidad</th><th scope="col" class="text-center">Producto</th><th scope="col" class="text-center">Cantidad</th><th scope="col" class="text-center">Precio Unitario</th><th scope="col" class="text-center">IVA</th><th scope="col" class="text-center">Retenciones</th><th scope="col" class="text-center">Subtotal</th><th scope="col"></th></tr></thead>
                <tbody class="table-light" id="Tabla_Items">
                    <template x-for="(item,indice) in itemsHoja" :key="item.itemRequisicion_id + '-' + indice">
                    <tr class="my-3">
                        <td class="text-center align-middle" x-text="item.itemRequisicion_unidad"></td>
                        <td style="max-width: 150px;" x-text="item.itemRequisicion_producto"></td>
                        <td class="text-center align-middle" x-text="formatearMoneda(item.itemRequisicion_cantidad,false)"></td>
                        <td class="text-center align-middle" x-text="formatearMoneda(item.itemRequisicion_precio,true)"></td>
                        <td class="text-center align-middle" x-text="formatearMoneda(item.itemRequisicion_iva,true)"></td>
                        <td class="text-center align-middle" x-text="formatearMoneda(item.itemRequisicion_retenciones,true)"></td>
                        <td class="text-center align-middle" x-text="formatearMoneda((Number(item.itemRequisicion_cantidad || 0) * Number(item.itemRequisicion_precio || 0) + Number(item.itemRequisicion_iva || 0) - Number(item.itemRequisicion_retenciones || 0)).toFixed(2), true)"></td>
                        <td class="align-middle">
                            <div class="btn-group btn-group-sm" role="group" aria-label="acciones item" x-show="isEditableSheet">
                                <button type="button" class="btn btn-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Editar item" x-on:click="editItem(item.itemRequisicion_producto,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_banderaFlete,item.itemRequisicion_banderaFisica,item.itemRequisicion_banderaResico,item.itemRequisicion_banderaISR,item.itemRequisicion_id)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-white" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" /><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" /></svg></button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Eliminar Item" x-on:click="eliminarItem(item.itemRequisicion_id,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_retenciones)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" /></svg></button>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
                <tfoot class="table-dark"><tr><td colspan="6" class="text-end fw-bold">Total:</td><td class="fw-bold text-center" x-text="formatearMoneda(Number(hojaActiva.hojaRequisicion_total || 0).toFixed(2), true)"></td><td></td></tr></tfoot>
            </table>
        </div></div>

        <div class="table-wrapper mt-4 mb-3">
            <div class="card-header"><h5 class="card-title">Comentarios de la Requisicion <span x-text="Numero_Req"></span> Hoja Numero <span x-text="hojaActiva.hojaRequisicion_numero || ''"></span></h5></div>
            <div class="card-body">
                <div x-show="hojaActiva.hojaRequisicion_estatus == 'LIGADA' || hojaActiva.hojaRequisicion_estatus == 'AUTORIZADA' || hojaActiva.hojaRequisicion_estatus == 'REVISION' || hojaActiva.hojaRequisicion_estatus == 'PAGADA' || !canReqEdit">
                    <div class="row mt-3"><div class="col"><h6 class="card-subtitle mb-2 text-muted">Comentarios de la Operacion</h6></div></div>
                    <div class="row"><div class="col"><p class="card-subtitle mb-2 text-muted" x-text="hojaActiva.hojaRequisicion_observaciones || ''"></p></div></div>
                </div>
                <div x-show="isEditableSheet">
                    <div class="row mt-3"><div class="col"><h6 class="card-subtitle mb-2 text-muted">Comentarios de la Operacion</h6></div></div>
                    <div class="row"><div class="col"><textarea class="form-control" id="comentsValidacion" x-model="hojaActiva.hojaRequisicion_observaciones" rows="3"></textarea></div></div>
                </div>
                <div x-show="hojaActiva.hojaRequisicion_estatus == 'REVISION' && validate == 'true' && (canDireccion || canReqEdit)">
                    <hr class="my-2"><div class="row mt-3"><div class="col"><h6 class="card-subtitle mb-2 text-muted">Comentarios de Validacion</h6></div></div>
                    <div class="row"><div class="col"><textarea class="form-control" id="comentsValidacion" x-model="hojaActiva.hojarequisicion_comentariosValidacion" rows="3"></textarea></div></div>
                </div>
                <div x-show="hojaActiva.hojaRequisicion_estatus == 'PENDIENTE'"><hr class="my-2"><div class="row mt-3"><div class="col"><h6 class="card-subtitle mb-2 text-muted">Comentarios de Validacion</h6></div></div><div class="row"><div class="col"><p class="card-subtitle mb-2 text-muted" x-text="hojaActiva.hojarequisicion_comentariosValidacion || ''"></p></div></div></div>
                <div x-show="hojaActiva.hojaRequisicion_estatus == 'PAGADA' || hojaActiva.hojaRequisicion_estatus == 'RECHAZADA'"><hr class="my-2"><div class="row mt-3"><div class="col"><h6 class="card-subtitle mb-2 text-muted">Comentarios de Autorizacion</h6></div></div><div class="row"><div class="col"><p class="card-subtitle mb-2 text-muted" x-text="hojaActiva.hojarequisicion_comentariosAutorizacion || ''"></p></div></div></div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-4 mb-5">
            <button class="btn btn-primary" x-on:click="validarRequisicion(hojaActiva.hojaRequisicion_observaciones)" x-show="isEditableSheet">Solicitar Revision</button>
            <button class="btn btn-success" x-on:click="asignarAPresion(hojaActiva.hojarequisicion_comentariosValidacion, hojaActiva.hojaRequisicion_total)" x-show="hojaActiva.hojaRequisicion_estatus == 'REVISION' && validate == 'true' && (canDireccion || canReqEdit)">Validar Requisicion</button>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
// jQuery/DataTables ya no se usan: la tabla de items la renderiza Alpine (x-for).
$tf_use_jquery = false;
$tf_use_datatables = false;
$tf_extra_scripts =
    '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>' .
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>' .
    '<script src="../assets/js/pdfGenerate.js"></script>' .
    '<script>window.TF_LEGACY_PERMS = {canReqEdit:' . ($__canReqEdit ? 'true' : 'false') . ', canDireccion:' . ($__canDireccion ? 'true' : 'false') . '};</script>' .
    '<script src="../assets/js/item_requisicion.js?v=fase08n"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
