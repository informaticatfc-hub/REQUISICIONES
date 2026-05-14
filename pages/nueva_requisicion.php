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
$tf_show_admin     = in_array($usuario_rolCode, ['admin'], true);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppReq" class="tf-page-inner" v-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Hoja de requisicion</span>
            <h1 class="tf-page-title">Nueva requisicion</h1>
            <p class="tf-page-lead">
                Completa el formulario para crear una nueva hoja de requisicion de compra.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <a href="./requisiciones.php" class="tf-btn tf-btn-ghost tf-btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a requisiciones
            </a>
        </div>
    </header>

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
                    <input type="text" class="form-control" v-model="Emisor_Nombre" readonly>
                </div>
                <div class="col-md-5">
                    <label class="form-label">RFC</label>
                    <input type="text" class="form-control" v-model="Emisor_RFC" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">Direccion</label>
                    <input type="text" class="form-control" v-model="Emisor_Adress" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" v-model="Emisor_Phone" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fax</label>
                    <input type="text" class="form-control" v-model="Emisor_Fax" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Codigo postal</label>
                    <input type="text" class="form-control" v-model="Emisor_ZipCode" readonly>
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
                    <select class="form-select" v-model="selected_Provedor">
                        <option value="">-- Selecciona un proveedor --</option>
                        <option v-for="proveedor in proveedores" :key="proveedor.proveedor_id" :value="proveedor.proveedor_id">
                            {{ proveedor.proveedor_id }} - {{ proveedor.proveedor_nombre }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="tf-btn tf-btn-primary w-100" @click="validarProv(selected_Provedor)" :disabled="!selected_Provedor">
                        <i class="bi bi-check2-circle"></i> Validar
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">RFC del proveedor</label>
                    <input type="text" class="form-control" v-model="Prov_RFC" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CLABE bancaria</label>
                    <input type="text" class="form-control" v-model="Prov_Clabe" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cuenta bancaria</label>
                    <input type="text" class="form-control" v-model="Prov_Cuenta" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referencia</label>
                    <input type="text" class="form-control" v-model="Prov_RefBank" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Banco</label>
                    <input type="text" class="form-control" v-model="Prov_Bank" readonly>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Correo electronico</label>
                    <input type="text" class="form-control" v-model="Prov_Email" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" v-model="Prov_Phone" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sucursal</label>
                    <input type="text" class="form-control" v-model="Prov_SucBank" readonly>
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
                    <input class="form-check-input" type="checkbox" role="switch" id="PagoTransfs" v-model="PagoTrans" :disabled="Items.length > 0" @change="pagoTransaccionActivado">
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
                        <tr v-if="Items.length === 0">
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> Aun no has agregado items
                            </td>
                        </tr>
                        <tr v-for="(item, idx) in Items" :key="item.Lote">
                            <th scope="row">{{ item.Lote }}</th>
                            <td>{{ item.Unidad }}</td>
                            <td class="text-break">{{ item.Nombre }}</td>
                            <td class="text-end">{{ item.Cantidad }}</td>
                            <td class="text-end">\${{ formatMoney(item.UnitedPrice) }}</td>
                            <td class="text-end">+ \${{ formatMoney(item.IVA) }}</td>
                            <td class="text-end">- \${{ formatMoney(item.Retenciones) }}</td>
                            <td class="text-end fw-semibold">\${{ formatMoney(item.STotal) }}</td>
                            <td class="text-end">
                                <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm text-danger" @click="quitarItem(idx)" title="Eliminar item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Total:</th>
                            <th class="text-end">\${{ Total_Pagar_Mostrar }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="tf-btn tf-btn-primary" @click="showModalAddItem">
                    <i class="bi bi-plus-circle"></i> Agregar item
                </button>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label" for="Observ">Observaciones adicionales</label>
                    <textarea class="form-control" id="Observ" rows="3" v-model="observaciones" placeholder="Notas, contexto u observaciones para validador y autorizador..."></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="./requisiciones.php" class="tf-btn tf-btn-ghost">
                    Cancelar
                </a>
                <button type="button" class="tf-btn tf-btn-success" @click="agregarRequisicion" :disabled="!puedeGuardar">
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

    new Vue({
        el: "#AppReq",
        data: {
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
            saving: false
        },
        computed: {
            Total_Pagar_Mostrar: function () {
                return Number.parseFloat(this.Total_Pagar || 0).toFixed(2);
            },
            puedeGuardar: function () {
                return !this.saving
                    && this.Items.length > 0
                    && !!this.Emisor_Id
                    && !!this.Prov_Id;
            }
        },
        methods: {
            formatMoney: function (v) {
                return Number.parseFloat(v || 0).toFixed(2);
            },
            pagoTransaccionActivado: function () {
                // Reset retenciones cuando cambia el toggle (no hay items aun)
                this.FormaPago = this.PagoTrans ? "Transferencia" : "Efectivo";
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
                }.bind(this)).catch(function (err) {
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error al validar proveedor' });
                });
            },
            cargarEmisor: function () {
                axios.post(url, { accion: 2 }).then(function (response) {
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
                axios.post(url, { accion: 3 }).then(function (response) {
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
            },
            quitarItem: function (idx) {
                this.Items.splice(idx, 1);
                this.recalcularTotal();
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
                    if (typeof onSuccess === 'function') { onSuccess(); }
                }.bind(this)).catch(function (err) {
                    this.saving = false;
                    console.error(err);
                    var msg = (err && err.response && err.response.data && err.response.data.error) || 'No se pudo crear la requisicion.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }.bind(this));
            }
        },
        mounted: function () {
            this.cargarEmisor();
            this.cargarProveedores();
        }
    });
})();
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
