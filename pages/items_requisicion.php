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

/* --- Barra superior consolidada: titulo + stats + acciones --- */
.detail-bar {
    display:flex; flex-wrap:wrap; align-items:center; gap:22px 28px;
    background:var(--tf-surface); border:1px solid var(--tf-border);
    border-radius:var(--tf-radius-lg); box-shadow:var(--tf-shadow-xs);
    padding:16px 24px; margin-bottom:26px;
}
.detail-bar-title { display:flex; flex-direction:column; gap:3px; margin-right:auto; }
.db-eyebrow { font-size:.7rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:var(--tf-text-muted); }
.db-id { font-size:1.05rem; font-weight:700; line-height:1.2; color:var(--tf-text); }
.detail-bar-stats { display:flex; align-items:flex-start; gap:30px; }
.db-stat { display:flex; flex-direction:column; gap:5px; }
.db-stat .k { font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-text-muted); }
.db-stat .v { font-size:.95rem; font-weight:700; color:var(--tf-text); line-height:1; }
.db-stat .v.total { color:var(--tf-brand-600); }
.db-stat .badge { align-self:flex-start; font-size:.72rem; }
.detail-bar-actions { display:flex; flex-wrap:wrap; gap:8px; }

/* --- Partes (Emisor / Proveedor): plano, dos columnas con divisor vertical --- */
.parties { display:grid; grid-template-columns:1fr 1fr; margin-bottom:10px; }
.party-col { padding:2px 0 22px; min-width:0; }
.party-col:first-child { padding-right:36px; }
.party-col.is-prov { border-left:1px solid var(--tf-border); padding-left:36px; }
.party-eyebrow { font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--tf-text-muted); margin:0 0 8px; }
.party-name { font-size:1.35rem; font-weight:700; line-height:1.25; color:var(--tf-text); margin:0; word-break:break-word; }
.party-rfc { font-size:.82rem; color:var(--tf-text-soft); margin:6px 0 14px; font-variant-numeric:tabular-nums; }
.party-body { margin:0; padding:0; }
.party-row {
    display:grid; grid-template-columns:120px 1fr; gap:16px; align-items:baseline;
    padding:11px 0; border-top:1px solid var(--tf-border);
}
.party-row > dt { margin:0; font-size:.72rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--tf-text-muted); }
.party-row > dd { margin:0; font-size:.92rem; font-weight:500; color:var(--tf-text); word-break:break-word; font-variant-numeric:tabular-nums; }
.section-title { font-size:.95rem; font-weight:700; margin:14px 0 8px; color:var(--tf-text); }

@media (max-width: 767.98px){
    .detail-bar-title { margin-right:0; width:100%; }
    .detail-bar-stats { gap:22px; }
    .parties { grid-template-columns:1fr; }
    .party-col:first-child { padding-right:0; padding-bottom:18px; border-bottom:1px solid var(--tf-border); }
    .party-col.is-prov { border-left:0; padding-left:0; padding-top:20px; }
}
@media (max-width: 479.98px){
    .party-row { grid-template-columns:1fr; gap:2px; }
}
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppItems" class="tf-page-inner" x-data="itemRequisicionApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <div class="detail-bar">
            <div class="detail-bar-title">
                <span class="db-eyebrow">Detalle de Requisición</span>
                <span class="db-id"><span x-text="Numero_Req"></span> · Hoja <span x-text="hojaActiva.hojaRequisicion_numero || ''"></span></span>
            </div>
            <div class="detail-bar-stats">
                <div class="db-stat">
                    <span class="k">Estatus</span>
                    <span class="badge"
                        x-bind:class="{
                            'bg-secondary':  hojaActiva.hojaRequisicion_estatus === 'NUEVO',
                            'bg-warning text-dark': hojaActiva.hojaRequisicion_estatus === 'PENDIENTE' || hojaActiva.hojaRequisicion_estatus === 'REVISION',
                            'bg-info text-dark':    hojaActiva.hojaRequisicion_estatus === 'LIGADA',
                            'bg-danger':     hojaActiva.hojaRequisicion_estatus === 'RECHAZADA',
                            'bg-primary':    hojaActiva.hojaRequisicion_estatus === 'AUTORIZADA',
                            'bg-success':    hojaActiva.hojaRequisicion_estatus === 'PAGADA'
                        }"
                        x-text="hojaActiva.hojaRequisicion_estatus || ''"></span>
                </div>
                <div class="db-stat">
                    <span class="k">Forma de Pago</span>
                    <span class="v" x-text="hojaActiva.hojaRequisicion_formaPago || ''"></span>
                </div>
                <div class="db-stat">
                    <span class="k">Total</span>
                    <span class="v total" x-text="formatearMoneda(hojaActiva.hojaRequisicion_total || 0, true)"></span>
                </div>
            </div>
            <div class="detail-bar-actions">
                <button type="button" class="btn btn-success btn-sm" x-on:click="cambiarProveedor()" x-show="isEditableSheet">Cambiar Proveedor</button>
                <button type="button" class="btn btn-secondary btn-sm" x-on:click="cambiarFormaPago(hojaActiva.hojaRequisicion_formaPago)" x-show="isEditableSheet">Forma de Pago</button>
                <button type="button" class="btn btn-danger btn-sm" x-on:click="imprimirReq">Imprimir</button>
            </div>
        </div>

        <div class="parties">
            <div class="party-col">
                <p class="party-eyebrow">Empresa Emisora</p>
                <p class="party-name" x-text="hojaActiva.emisor_nombre || '—'"></p>
                <p class="party-rfc">RFC · <span x-text="hojaActiva.emisor_rfc || '—'"></span></p>
                <dl class="party-body">
                    <div class="party-row"><dt>Dirección</dt><dd x-text="hojaActiva.emisor_direccion || '—'"></dd></div>
                    <div class="party-row"><dt>Teléfono</dt><dd x-text="hojaActiva.emisor_telefono || '—'"></dd></div>
                    <div class="party-row"><dt>C.P.</dt><dd x-text="hojaActiva.emisor_zipCode || '—'"></dd></div>
                </dl>
            </div>
            <div class="party-col is-prov">
                <p class="party-eyebrow">Proveedor</p>
                <p class="party-name" x-text="hojaActiva.proveedor_nombre || '—'"></p>
                <p class="party-rfc">RFC · <span x-text="hojaActiva.proveedor_rfc || '—'"></span></p>
                <dl class="party-body">
                    <div class="party-row"><dt>Banco</dt><dd x-text="hojaActiva.proveedor_banco || '—'"></dd></div>
                    <div class="party-row"><dt>Cuenta</dt><dd x-text="hojaActiva.proveedor_numeroCuenta || '—'"></dd></div>
                    <div class="party-row"><dt>CLABE</dt><dd x-text="hojaActiva.proveedor_clabe || '—'"></dd></div>
                    <div class="party-row"><dt>Sucursal</dt><dd x-text="hojaActiva.proveedor_sucursal || '—'"></dd></div>
                    <div class="party-row"><dt>Tarjeta</dt><dd x-text="hojaActiva.presiones_tarjetaBanco || '—'"></dd></div>
                    <div class="party-row"><dt>Email</dt><dd x-text="hojaActiva.proveedor_email || '—'"></dd></div>
                    <div class="party-row"><dt>Teléfono</dt><dd x-text="hojaActiva.proveedor_telefono || '—'"></dd></div>
                </dl>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h3 class="section-title mb-0">Items de la Requisición</h3>
            <button type="button" class="btn btn-primary" x-on:click="agregarItem" id="btnAddItem" x-show="isEditableSheet">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg me-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                <span class="fw-semibold text-white">Agregar item</span>
            </button>
        </div>

        <div class="table-wrapper"><div class="overflow-auto">
            <table id="example" class="tf-admin-table w-100">
                <thead><tr><th scope="col" class="text-center">Unidad</th><th scope="col" class="text-center">Producto</th><th scope="col" class="text-center">Cantidad</th><th scope="col" class="text-center">Precio Unitario</th><th scope="col" class="text-center">IVA</th><th scope="col" class="text-center">Retenciones</th><th scope="col" class="text-center">Subtotal</th><th scope="col"></th></tr></thead>
                <tbody id="Tabla_Items">
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
                                <button type="button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Modificar item" x-on:click="editItem(item.itemRequisicion_producto,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_banderaFlete,item.itemRequisicion_banderaFisica,item.itemRequisicion_banderaResico,item.itemRequisicion_banderaISR,item.itemRequisicion_id)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill text-white" viewBox="0 0 16 16"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V11.5h-.5a.5.5 0 0 1-.5-.5V9.5h-.5a.5.5 0 0 1-.5-.5V7.5H1.5a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1 .5-.5h1V4.5a.5.5 0 0 1 .5-.5h.5V3.5a.5.5 0 0 1 .5-.5h.5V2.5a.5.5 0 0 1 .5-.5H6a.5.5 0 0 1 .5.5v1.5h.5a.5.5 0 0 1 0 1h-.5v.5h.5a.5.5 0 0 1 0 1h-.5v.5h.5a.5.5 0 0 1 0 1h-.5v.5h.5a.5.5 0 0 1 0 1h-.5v1a.5.5 0 0 0 .5.5h.5v.5a.5.5 0 0 0 .5.5h1.5a.5.5 0 0 0 .5-.5v-.5h.5a.5.5 0 0 0 .5-.5v-1.5a.5.5 0 0 0-1 0v1h-.5v-.5a.5.5 0 0 0-1 0v.5h-.5v-.5a.5.5 0 0 0-1 0v.5H9v-.5a.5.5 0 0 0-1 0v.5H7v-1.5a.5.5 0 0 0-1 0v1.5H5v-.5a.5.5 0 0 0-1 0v.5H3v-.5a.5.5 0 0 0-1 0v.5H1v-2a.5.5 0 0 0-1 0v2a1.5 1.5 0 0 0 1.5 1.5h13a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/></svg></button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Eliminar item" x-on:click="eliminarItem(item.itemRequisicion_id,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_retenciones)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" /></svg></button>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
                <tfoot><tr class="fw-bold"><td colspan="6" class="text-end">Total:</td><td class="text-center" x-text="formatearMoneda(Number(hojaActiva.hojaRequisicion_total || 0).toFixed(2), true)"></td><td></td></tr></tfoot>
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
