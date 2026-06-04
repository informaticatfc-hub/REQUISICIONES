<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

// Para crear una hoja/requisicion hace falta requisiciones.create
if (!tf_has_permission('requisiciones.create', $__user)) {
    tf_abort(403, 'No tienes permisos para crear requisiciones');
}

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? '';
$usuario_rolCode = $__user['role']['code'] ?? '';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title     = 'Nueva requisicion';
$tf_active_nav     = 'requisiciones';
$tf_breadcrumb     = [
    ['Inicio', './index.php'],
    ['Requisiciones', './requisiciones.php'],
    ['Presiones', './presiones.php'],
    ['Nueva requisicion', '#'],
];
$tf_user = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true);
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');
$tf_extra_head     = '<style>[x-cloak]{display:none!important;}</style>';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppReq" class="tf-page-inner" x-data="nuevaRequisicionApp()" x-init="init()" x-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Hoja de requisicion</span>
            <h1 class="tf-page-title">Nueva requisicion</h1>
            <p class="tf-page-lead">
                Completa el formulario para crear una nueva hoja de requisicion de compra.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <a href="./presiones.php" class="tf-btn tf-btn-ghost tf-btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a presiones
            </a>
        </div>
    </header>

    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2" x-show="draftRestored || draftLastSaved" x-cloak>
        <span class="small">
            <i class="bi bi-save2"></i>
            <span x-show="draftRestored">Se restauro un borrador local.</span>
            <span x-show="!draftRestored">Borrador guardado localmente.</span>
            <span x-show="draftLastSaved"> Ultimo guardado: <strong x-text="draftLastSaved"></strong></span>
        </span>
        <button type="button" class="btn btn-sm btn-outline-danger" x-on:click="clearDraft(true)">
            Descartar borrador
        </button>
    </div>

    <!-- Datos de la empresa emisora -->
    <section class="tf-card mb-4">
        <header class="tf-card-header">
            <h2 class="tf-card-title">
                <i class="bi bi-building"></i> Datos de la empresa
            </h2>
            <span class="tf-card-sub text-muted small">Informacion del emisor (solo lectura)</span>
        </header>
        <div class="tf-card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre de la empresa</label>
                    <input type="text" class="form-control" x-model="Emisor_Nombre" readonly>
                </div>
                <div class="col-md-5">
                    <label class="form-label">RFC</label>
                    <input type="text" class="form-control" x-model="Emisor_RFC" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">Direccion</label>
                    <input type="text" class="form-control" x-model="Emisor_Adress" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" x-model="Emisor_Phone" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fax</label>
                    <input type="text" class="form-control" x-model="Emisor_Fax" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Codigo postal</label>
                    <input type="text" class="form-control" x-model="Emisor_ZipCode" readonly>
                </div>
            </div>
        </div>
    </section>

    <!-- Datos del proveedor -->
    <section class="tf-card mb-4">
        <header class="tf-card-header">
            <h2 class="tf-card-title">
                <i class="bi bi-truck"></i> Datos del proveedor
            </h2>
            <span class="tf-card-sub text-muted small">Selecciona un proveedor activo y valida sus datos bancarios</span>
        </header>
        <div class="tf-card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre del proveedor</label>
                    <!-- Selector con búsqueda por nombre / RFC / CLABE -->
                    <div style="position:relative">
                        <input type="text"
                               class="form-control"
                               x-model="provBusqueda"
                               x-bind:placeholder="selected_Provedor ? provNombreSeleccionado : 'Buscar por nombre, RFC o CLABE...'"
                               x-on:focus="provDropdownOpen=true"
                               x-on:input="provDropdownOpen=true; selected_Provedor=''"
                               x-on:blur="setTimeout(function(){provDropdownOpen=false},200)"
                               autocomplete="off">
                        <ul x-show="provDropdownOpen && proveedoresFiltrados.length"
                            class="list-unstyled mb-0"
                            style="position:absolute;z-index:1050;background:var(--tf-surface);border:1px solid var(--tf-border);border-radius:8px;width:100%;max-height:220px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,.12);top:100%;left:0">
                            <template x-for="p in proveedoresFiltrados" :key="p.proveedor_id">
                            <li
                                class="px-3 py-2"
                                style="cursor:pointer;border-bottom:1px solid var(--tf-border)"
                                x-on:mousedown.prevent="seleccionarProveedor(p)">
                                <span class="fw-semibold" x-text="p.proveedor_nombre"></span>
                                <br>
                                <small class="text-muted">RFC: <span x-text="p.proveedor_rfc || '—'"></span> &nbsp;|&nbsp; CLABE: <span x-text="p.proveedor_clabe || '—'"></span></small>
                            </li>
                            </template>
                            <li x-show="!proveedoresFiltrados.length && provBusqueda"
                                class="px-3 py-2 text-muted small">
                                Sin resultados
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="tf-btn tf-btn-primary w-100" x-on:click="validarProv(selected_Provedor)" x-bind:disabled="!selected_Provedor">
                        <i class="bi bi-check2-circle"></i> Validar
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">RFC del proveedor</label>
                    <input type="text" class="form-control" x-model="Prov_RFC" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CLABE bancaria</label>
                    <input type="text" class="form-control" x-model="Prov_Clabe" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cuenta bancaria</label>
                    <input type="text" class="form-control" x-model="Prov_Cuenta" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referencia</label>
                    <input type="text" class="form-control" x-model="Prov_RefBank" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Banco</label>
                    <input type="text" class="form-control" x-model="Prov_Bank" readonly>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Correo electronico</label>
                    <input type="text" class="form-control" x-model="Prov_Email" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" x-model="Prov_Phone" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sucursal</label>
                    <input type="text" class="form-control" x-model="Prov_SucBank" readonly>
                </div>
            </div>
        </div>
    </section>

    <!-- Items de la requisicion -->
    <section class="tf-card mb-4">
        <header class="tf-card-header">
            <h2 class="tf-card-title">
                <i class="bi bi-list-check"></i> Items de la requisicion
            </h2>
            <div class="tf-card-actions">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="PagoTransfs" x-model="PagoTrans" x-bind:disabled="Items.length > 0" x-on:change="pagoTransaccionActivado">
                    <label class="form-check-label" for="PagoTransfs">
                        Pagar por transferencia
                    </label>
                </div>
            </div>
        </header>
        <div class="tf-card-body">

            <div class="tf-admin-table-wrapper">
                <table class="tf-admin-table">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Unidad</th>
                            <th>Producto</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Precio unit.</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Retenciones</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="Items.length === 0">
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> Aun no has agregado items
                            </td>
                        </tr>
                        <template x-for="(item, idx) in Items" :key="item.Lote + '-' + idx">
                        <tr>
                            <th scope="row" x-text="item.Lote"></th>
                            <td x-text="item.Unidad"></td>
                            <td class="text-break" x-text="item.Nombre"></td>
                            <td class="text-end" x-text="item.Cantidad"></td>
                            <td class="text-end">$<span x-text="formatMoney(item.UnitedPrice)"></span></td>
                            <td class="text-end">+ $<span x-text="formatMoney(item.IVA)"></span></td>
                            <td class="text-end">- $<span x-text="formatMoney(item.Retenciones)"></span></td>
                            <td class="text-end fw-semibold">$<span x-text="formatMoney(item.STotal)"></span></td>
                            <td class="text-end">
                                <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm text-danger" x-on:click="quitarItem(idx)" title="Eliminar item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Total:</th>
                            <th class="text-end">$<span x-text="Total_Pagar_Mostrar"></span></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="tf-btn tf-btn-primary" x-on:click="showModalAddItem">
                    <i class="bi bi-plus-circle"></i> Agregar item
                </button>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label" for="Observ">Observaciones adicionales</label>
                    <textarea class="form-control" id="Observ" rows="3" x-model="observaciones" placeholder="Notas, contexto u observaciones para validador y autorizador..."></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="./presiones.php" class="tf-btn tf-btn-ghost">
                    Cancelar
                </a>
                <button type="button" class="tf-btn tf-btn-success" x-on:click="agregarRequisicion" x-bind:disabled="!puedeGuardar">
                    <i class="bi bi-file-earmark-check"></i> Crear requisicion
                </button>
            </div>
        </div>
    </section>

</div>

<?php
$tf_inline_script = <<<JS
(function(){
    var url = "../api/crud_nueva_hoja.php";

    // HTML del modal "Agregar item" - se mantiene en heredoc para conservar identico al legacy
    var ITEM_FORM_FULL = '<div class="col"><hr><div class="row g-3 my-2"><div class="col-12"><label class="form-label text-start" for="Producto">Producto</label><textarea class="form-control" placeholder="Ingresa los datos de tu producto" id="Producto" rows="3"></textarea></div></div><div class="row g-3 my-2"><div class="col-md-4"><label class="form-label text-start" for="Unidad">Unidad</label><select class="form-select" id="Unidad"><option value="">Selecciona</option><option value="DISEÑO">DISEÑO</option><option value="PIEZAS">PIEZAS</option><option value="BULTOS">BULTOS</option><option value="PESOS">PESOS</option><option value="LITROS">LITROS</option><option value="SERVICIOS">SERVICIO</option><option value="MENSUALIDAD">MENSUALIDAD</option><option value="RENTA">RENTA</option><option value="CUBETAS">CUBETAS</option><option value="TONELADAS">TONELADAS</option><option value="METROS">METROS</option><option value="METROS CUADRADOS">METROS CUADRADOS</option><option value="METROS CUBICOS">METROS CUBICOS</option><option value="KILOGRAMOS">KILOGRAMOS</option></select></div><div class="col-md-4"><label class="form-label text-start" for="Cantidad">Cantidad</label><input type="number" min="0" step="any" placeholder="0" class="form-control" id="Cantidad"></div><div class="col-md-4"><label class="form-label text-start" for="UnitedPrice">Precio unitario</label><input type="number" min="0" step="any" placeholder="0" class="form-control" id="UnitedPrice"></div></div><hr><div class="row my-2"><div class="col"><h6 class="text-start fw-bold mb-2">Retenciones aplicables</h6></div></div><div class="row g-3"><div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetFlete"><label class="form-check-label" for="RetFlete">Flete (4%)</label></div></div><div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetPersonaFIsica"><label class="form-check-label" for="RetPersonaFIsica">Persona Fisica (10.67%)</label></div></div><div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetencionRESICO"><label class="form-check-label" for="RetencionRESICO">RESICO (1.25%)</label></div></div></div></div>';

    var ITEM_FORM_SIMPLE = '<div class="col"><hr><div class="row g-3 my-2"><div class="col-12"><label class="form-label text-start" for="Producto">Producto</label><textarea class="form-control" placeholder="Ingresa los datos de tu producto" id="Producto" rows="3"></textarea></div></div><div class="row g-3 my-2"><div class="col-md-4"><label class="form-label text-start" for="Unidad">Unidad</label><select class="form-select" id="Unidad"><option value="">Selecciona</option><option value="DISEÑO">DISEÑO</option><option value="PIEZAS">PIEZAS</option><option value="BULTOS">BULTOS</option><option value="PESOS">PESOS</option><option value="LITROS">LITROS</option><option value="SERVICIOS">SERVICIO</option><option value="MENSUALIDAD">MENSUALIDAD</option><option value="RENTA">RENTA</option><option value="CUBETAS">CUBETAS</option><option value="TONELADAS">TONELADAS</option><option value="METROS">METROS</option><option value="METROS CUADRADOS">METROS CUADRADOS</option><option value="METROS CUBICOS">METROS CUBICOS</option><option value="KILOGRAMOS">KILOGRAMOS</option></select></div><div class="col-md-4"><label class="form-label text-start" for="Cantidad">Cantidad</label><input type="number" min="0" step="any" placeholder="0" class="form-control" id="Cantidad"></div><div class="col-md-4"><label class="form-label text-start" for="UnitedPrice">Precio unitario</label><input type="number" min="0" step="any" placeholder="0" class="form-control" id="UnitedPrice"></div></div><hr></div>';

    function nuevaRequisicionApp() {
        return {
            // Catalogos
            emisores: [],
            proveedores: [],
            // Estado
            Items: [],
            selected_Provedor: "",
            // Datos emisor
            Emisor_Id: "",
            Emisor_Nombre: "",
            Emisor_RFC: "",
            Emisor_Adress: "",
            Emisor_Phone: "",
            Emisor_Fax: "",
            Emisor_ZipCode: "",
            // Datos proveedor
            Prov_Id: "",
            Prov_RFC: "",
            Prov_Clabe: "",
            Prov_Cuenta: "",
            Prov_Email: "",
            Prov_Phone: "",
            Prov_SucBank: "",
            Prov_RefBank: "",
            Prov_Bank: "",
            // Retenciones temporales (por item)
            RetFlete: false,
            RetFisica: false,
            RetResico: false,
            indexFlete: 0,
            indexFisica: 0,
            indexResico: 0,
            retenciones: 0,
            IVA: 0,
            // Datos generales
            PagoTrans: true,
            FormaPago: "Transferencia",
            SubTotal: 0,
            Total_Pagar: 0,
            Item_Lote: 0,
            observaciones: "",
            timeNow: "",
            saving: false,
            draftKeyBase: 'tf:draft:nueva_requisicion:v1',
            draftRestored: false,
            draftLastSaved: '',
            draftTimer: null,
            // Búsqueda combinada de proveedor
            provBusqueda: "",
            provDropdownOpen: false
        ,
            get draftKey() {
                var idPresion = localStorage.getItem('IdPresion') || 'sin_presion';
                return this.draftKeyBase + ':' + idPresion;
            },
            get Total_Pagar_Mostrar() {
                return Number.parseFloat(this.Total_Pagar || 0).toFixed(2);
            },
            get puedeGuardar() {
                return !this.saving
                    && this.Items.length > 0
                    && !!this.Emisor_Id
                    && !!this.Prov_Id;
            },
            get proveedoresFiltrados() {
                var q = (this.provBusqueda || '').toLowerCase().trim();
                if (!q) return this.proveedores;
                return this.proveedores.filter(function (p) {
                    return (p.proveedor_nombre || '').toLowerCase().includes(q)
                        || (p.proveedor_rfc   || '').toLowerCase().includes(q)
                        || (p.proveedor_clabe || '').toLowerCase().includes(q);
                });
            },
            get provNombreSeleccionado() {
                if (!this.selected_Provedor) return '';
                var found = this.proveedores.find(function (p) {
                    return String(p.proveedor_id) === String(this.selected_Provedor);
                }.bind(this));
                return found ? found.proveedor_nombre : '';
            }
        ,
            formatMoney: function (v) {
                return Number.parseFloat(v || 0).toFixed(2);
            },
            seleccionarProveedor: function (p) {
                this.selected_Provedor = p.proveedor_id;
                this.provBusqueda = p.proveedor_nombre;
                this.provDropdownOpen = false;
                this.persistDraftDebounced();
            },
            pagoTransaccionActivado: function () {
                // Reset retenciones cuando cambia el toggle (no hay items aun)
                this.FormaPago = this.PagoTrans ? "Transferencia" : "Efectivo";
                this.persistDraftDebounced();
            },
            validarProv: function (idProv) {
                if (!idProv) { return; }
                axios.post(url, { accion: 4, id_prov: idProv }).then(function (response) {
                    var arr = response.data || [];
                    if (!arr.length) {
                        Swal.fire({ icon: 'warning', title: 'Proveedor no encontrado' });
                        return;
                    }
                    var p = arr[0];
                    this.Prov_Id = p.proveedor_id;
                    this.Prov_RFC = p.proveedor_rfc || '';
                    this.Prov_Clabe = p.proveedor_clabe || '';
                    this.Prov_Cuenta = p.proveedor_numeroCuenta || '';
                    this.Prov_Email = p.proveedor_email || '';
                    this.Prov_Phone = p.proveedor_telefono || '';
                    this.Prov_SucBank = p.proveedor_sucursal || '';
                    this.Prov_RefBank = p.proveedor_refBanco || '';
                    this.Prov_Bank = p.proveedor_banco || '';
                    this.persistDraftDebounced();
                }.bind(this)).catch(function (err) {
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error al validar proveedor' });
                });
            },
            cargarEmisor: function () {
                return axios.post(url, { accion: 2 }).then(function (response) {
                    this.emisores = response.data || [];
                    if (this.emisores.length) {
                        var e = this.emisores[0];
                        this.Emisor_Id = e.emisor_id;
                        this.Emisor_Nombre = e.emisor_nombre || '';
                        this.Emisor_RFC = e.emisor_rfc || '';
                        this.Emisor_Adress = e.emisor_direccion || '';
                        this.Emisor_Phone = e.emisor_telefono || '';
                        this.Emisor_ZipCode = e.emisor_zipCode || '';
                        this.Emisor_Fax = e.emisor_fax || '';
                    }
                }.bind(this));
            },
            cargarProveedores: function () {
                return axios.post(url, { accion: 3 }).then(function (response) {
                    this.proveedores = response.data || [];
                }.bind(this));
            },
            showModalAddItem: function () {
                var self = this;
                var htmlForm = self.PagoTrans ? ITEM_FORM_FULL : ITEM_FORM_SIMPLE;
                Swal.fire({
                    title: 'Nuevo item',
                    html: htmlForm,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    preConfirm: function () {
                        var nombre = (document.getElementById('Producto') || {}).value || '';
                        var unidad = (document.getElementById('Unidad') || {}).value || '';
                        var cantidad = (document.getElementById('Cantidad') || {}).value || '';
                        var precio = (document.getElementById('UnitedPrice') || {}).value || '';
                        if (!nombre.trim() || !unidad || !cantidad || Number(cantidad) <= 0 || !precio || Number(precio) <= 0) {
                            Swal.showValidationMessage('Todos los campos son obligatorios y > 0');
                            return false;
                        }
                        var item = {
                            Nombre: nombre,
                            Unidad: unidad,
                            Cantidad: Number(cantidad),
                            UnitedPrice: Number(precio),
                            bandFlete: false,
                            bandFisico: false,
                            bandResico: false,
                            bandISR: false
                        };
                        if (self.PagoTrans) {
                            item.bandFlete  = !!(document.getElementById('RetFlete') && document.getElementById('RetFlete').checked);
                            item.bandFisico = !!(document.getElementById('RetPersonaFIsica') && document.getElementById('RetPersonaFIsica').checked);
                            item.bandResico = !!(document.getElementById('RetencionRESICO') && document.getElementById('RetencionRESICO').checked);
                        }
                        return item;
                    }
                }).then(function (result) {
                    if (result.isConfirmed && result.value) {
                        self.agregarItem(result.value);
                        var Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
                        Toast.fire({ icon: 'success', title: 'Item agregado' });
                    }
                });
            },
            agregarItem: function (item) {
                var base = (item.UnitedPrice || 0) * (item.Cantidad || 0);
                var iva = 0, retTotal = 0;
                if (this.PagoTrans) {
                    iva = base * 0.16;
                    if (item.bandFlete)  { retTotal += base * 0.04; }
                    if (item.bandFisico) { retTotal += base * 0.1067; }
                    if (item.bandResico) { retTotal += base * 0.0125; }
                }
                item.IVA = iva;
                item.Retenciones = retTotal;
                item.STotal = base - retTotal + iva;
                this.Item_Lote++;
                item.Lote = this.Item_Lote;
                this.Items.unshift(item);
                this.recalcularTotal();
                this.persistDraftDebounced();
            },
            quitarItem: function (idx) {
                this.Items.splice(idx, 1);
                this.recalcularTotal();
                this.persistDraftDebounced();
            },
            recalcularTotal: function () {
                var t = 0;
                for (var i = 0; i < this.Items.length; i++) {
                    t += Number(this.Items[i].STotal || 0);
                }
                this.SubTotal = t;
                this.Total_Pagar = t;
            },
            agregarRequisicion: function () {
                if (!this.puedeGuardar) { return; }
                var self = this;
                Swal.fire({
                    title: '¿Quieres guardar la requisicion?',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar y salir',
                    denyButtonText: 'Guardar y agregar otra'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        self.guardarRequisicion(function () {
                            Swal.fire({ icon: 'success', title: 'Requisicion guardada' }).then(function () {
                                window.location.href = './requisiciones.php';
                            });
                        });
                    } else if (result.isDenied) {
                        self.guardarRequisicion(function () {
                            Swal.fire({ icon: 'success', title: 'Requisicion guardada' }).then(function () {
                                window.location.reload();
                            });
                        });
                    }
                });
            },
            guardarRequisicion: function (onSuccess) {
                if (this.saving) { return; }
                this.saving = true;

                var idPresion = localStorage.getItem('IdPresion');
                if (!idPresion) {
                    Swal.fire({ icon: 'error', title: 'No hay presion seleccionada', text: 'Regresa a Presiones y selecciona una para crear la requisicion.' });
                    this.saving = false;
                    return;
                }

                var fecha = new Date();
                var year = fecha.getFullYear();
                var mes  = fecha.getMonth() + 1;
                var dia  = fecha.getDate();
                mes = mes < 10 ? '0' + mes : mes;
                dia = dia < 10 ? '0' + dia : dia;
                var FechaReq = year + '-' + mes + '-' + dia;

                var hh = fecha.getHours();
                var mm = fecha.getMinutes();
                var ss = fecha.getSeconds();
                var timeNow = String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');

                var payload = {
                    accion: 1,
                    time: timeNow,
                    idReq: idPresion,
                    id_emisor: this.Emisor_Id,
                    id_prov: this.Prov_Id,
                    Total: this.Total_Pagar,
                    formaPago: this.FormaPago,
                    fechaSolicitud: FechaReq,
                    items: JSON.stringify(this.Items),
                    observaciones: this.observaciones,
                    conceptoUnico: ''
                };

                axios.post(url, payload).then(function (response) {
                    this.saving = false;
                    var data = response.data;
                    if (data && data.error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.mensaje || 'No se pudo crear la requisicion.' });
                        return;
                    }
                    this.clearDraft(false);
                    if (typeof onSuccess === 'function') { onSuccess(); }
                }.bind(this)).catch(function (err) {
                    this.saving = false;
                    console.error(err);
                    var msg = (err && err.response && err.response.data && err.response.data.error) || 'No se pudo crear la requisicion.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }.bind(this));
            },
            buildDraftPayload: function () {
                return {
                    selected_Provedor: this.selected_Provedor || '',
                    provBusqueda: this.provBusqueda || '',
                    Prov_Id: this.Prov_Id || '',
                    Prov_RFC: this.Prov_RFC || '',
                    Prov_Clabe: this.Prov_Clabe || '',
                    Prov_Cuenta: this.Prov_Cuenta || '',
                    Prov_Email: this.Prov_Email || '',
                    Prov_Phone: this.Prov_Phone || '',
                    Prov_SucBank: this.Prov_SucBank || '',
                    Prov_RefBank: this.Prov_RefBank || '',
                    Prov_Bank: this.Prov_Bank || '',
                    PagoTrans: !!this.PagoTrans,
                    FormaPago: this.FormaPago || 'Transferencia',
                    observaciones: this.observaciones || '',
                    Items: Array.isArray(this.Items) ? this.Items : [],
                    savedAt: Date.now()
                };
            },
            persistDraft: function () {
                try {
                    localStorage.setItem(this.draftKey, JSON.stringify(this.buildDraftPayload()));
                    this.draftLastSaved = new Date().toLocaleTimeString('es-MX', { hour12: false });
                } catch (e) {
                    console.warn('No se pudo guardar borrador local', e);
                }
            },
            persistDraftDebounced: function () {
                if (this.draftTimer) {
                    clearTimeout(this.draftTimer);
                }
                this.draftTimer = setTimeout(function () {
                    this.persistDraft();
                }.bind(this), 350);
            },
            restoreDraft: function () {
                try {
                    var raw = localStorage.getItem(this.draftKey);
                    if (!raw) { return; }
                    var data = JSON.parse(raw);
                    this.selected_Provedor = data.selected_Provedor || '';
                    this.provBusqueda = data.provBusqueda || '';
                    this.Prov_Id = data.Prov_Id || '';
                    this.Prov_RFC = data.Prov_RFC || '';
                    this.Prov_Clabe = data.Prov_Clabe || '';
                    this.Prov_Cuenta = data.Prov_Cuenta || '';
                    this.Prov_Email = data.Prov_Email || '';
                    this.Prov_Phone = data.Prov_Phone || '';
                    this.Prov_SucBank = data.Prov_SucBank || '';
                    this.Prov_RefBank = data.Prov_RefBank || '';
                    this.Prov_Bank = data.Prov_Bank || '';
                    this.PagoTrans = typeof data.PagoTrans === 'boolean' ? data.PagoTrans : true;
                    this.FormaPago = data.FormaPago || (this.PagoTrans ? 'Transferencia' : 'Efectivo');
                    this.observaciones = data.observaciones || '';
                    this.Items = Array.isArray(data.Items) ? data.Items : [];
                    this.Item_Lote = this.Items.length;
                    this.recalcularTotal();
                    if (data.savedAt) {
                        this.draftLastSaved = new Date(data.savedAt).toLocaleTimeString('es-MX', { hour12: false });
                    }
                    this.draftRestored = true;
                } catch (e) {
                    console.warn('No se pudo restaurar borrador local', e);
                }
            },
            clearDraft: function (withToast) {
                localStorage.removeItem(this.draftKey);
                this.draftRestored = false;
                this.draftLastSaved = '';
                if (withToast) {
                    Swal.fire({ icon: 'success', title: 'Borrador eliminado', timer: 1500, showConfirmButton: false });
                }
            },
            init: function () {
                Promise.all([this.cargarEmisor(), this.cargarProveedores()]).finally(function () {
                    this.restoreDraft();
                }.bind(this));

                this.$watch('observaciones', function () {
                    this.persistDraftDebounced();
                }.bind(this));

                this.$watch('selected_Provedor', function () {
                    this.persistDraftDebounced();
                }.bind(this));

                this.$watch('PagoTrans', function () {
                    this.persistDraftDebounced();
                }.bind(this));

                window.addEventListener('beforeunload', function () {
                    this.persistDraft();
                }.bind(this));
            }
        };
    }
})();
JS;

$tf_use_vue = false;
$tf_use_axios = true;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';

include __DIR__ . '/../includes/layout_bottom.php';
?>
