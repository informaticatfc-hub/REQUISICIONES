<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['catalogos.view'], 'Acceso restringido');

$canManageBancos = tf_has_permission('catalogos.manage', $__user);

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? '';
$usuario_rolCode = $__user['role']['code'] ?? '';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title    = 'Catalogo de Bancos';
$tf_active_nav    = 'catalogos';
$tf_breadcrumb    = [['Catalogos', './menu_catalago.php'], ['Bancos', '#']];
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
include __DIR__ . '/../includes/layout_top.php';
?>
<div id="AppBancos" class="tf-page-inner" x-data="bancosApp()" x-init="init()" x-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Catalogo financiero</span>
            <h1 class="tf-page-title">Catalogo de Bancos</h1>
            <p class="tf-page-lead">Consulta y administra los bancos registrados en el sistema.</p>
        </div>
        <div class="tf-page-header-actions">
            <?php if ($canManageBancos): ?>
            <button type="button" class="tf-btn tf-btn-primary" x-on:click="addBanco">
                <i class="bi bi-plus-lg"></i> Agregar Banco
            </button>
            <?php endif; ?>
        </div>
    </header>

    <section class="tf-kpi-grid">
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon"><i class="bi bi-bank"></i></span>
                <span class="tf-kpi-label">Total Bancos</span>
            </div>
            <div class="tf-kpi-value" x-text="bancos.length"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success"><i class="bi bi-check-circle"></i></span>
                <span class="tf-kpi-label">Activos</span>
            </div>
            <div class="tf-kpi-value" x-text="activosCount"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger"><i class="bi bi-x-circle"></i></span>
                <span class="tf-kpi-label">Inactivos</span>
            </div>
            <div class="tf-kpi-value" x-text="inactivosCount"></div>
        </article>
    </section>

    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title"><i class="bi bi-bank2"></i> Bancos registrados</h2>
                <p class="tf-card-sub"><span x-text="bancos.length"></span> bancos en total</p>
            </div>
            <input x-model.debounce.300ms="filterText" type="search" class="form-control form-control-sm"
                   placeholder="Buscar banco..." style="max-width:240px">
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto">
            <table class="tf-admin-table">
                <thead>
                    <tr>
                        <th>Razon Social</th>
                        <th>Nombre Comercial</th>
                        <th>Estatus</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(banco, indice) in filteredBancos" :key="banco.banco_id">
                    <tr>
                        <td><strong x-text="banco.banco_razonSocial"></strong></td>
                        <td x-text="banco.banco_nombreComercial"></td>
                        <td>
                            <span class="tf-status"
                                  :class="banco.banco_activo == 1 ? 'tf-status-active' : 'tf-status-inactive'"
                                  x-text="banco.banco_activo == 1 ? 'ACTIVO' : 'INACTIVO'">
                            </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                                <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    title="Consultar" x-on:click="viewBanco(banco.banco_id)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if ($canManageBancos): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    title="Editar" x-on:click="editProveedor(indice, banco.banco_id)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="tf-btn tf-btn-danger tf-btn-sm"
                                    title="Eliminar" x-on:click="desactivarBanco(indice, banco.banco_id)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    </template>
                    <tr x-show="!filteredBancos.length">
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:1.5rem"></i>
                            <div>Sin resultados</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php
$tf_use_vue        = false;
$tf_use_axios      = true;
$tf_use_datatables = false;
$tf_use_jquery     = false;
$tf_extra_head     = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts  = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';
$tf_inline_script  = <<<JS
    function bancosApp() {
        return {
            bancos: [],
            filterText: "",
            get filteredBancos() {
                var q = (this.filterText || "").trim().toLowerCase();
                if (!q) return this.bancos;
                return this.bancos.filter(function (b) {
                    return (b.banco_razonSocial || "").toLowerCase().indexOf(q) !== -1
                        || (b.banco_nombreComercial || "").toLowerCase().indexOf(q) !== -1;
                });
            },
            get activosCount() {
                return this.bancos.filter(function (b) { return Number(b.banco_activo) === 1; }).length;
            },
            get inactivosCount() {
                return this.bancos.filter(function (b) { return Number(b.banco_activo) === 0; }).length;
            },
            init: function () {
                this.loadBancos();
            },
            loadBancos: async function () {
                try {
                    var r = await axios.post("../api/crud_bancos.php", { accion: 3 });
                    this.bancos = Array.isArray(r.data) ? r.data : [];
                } catch (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || "Error al cargar bancos";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                }
            },
            addBanco: async function () {
                var res = await Swal.fire({
                    title: "Agregar banco",
                    html: '<div class="text-start">'
                        + '<label class="form-label">Razon social</label>'
                        + '<input id="tfBancoRazon" class="form-control mb-2">'
                        + '<label class="form-label">Nombre comercial</label>'
                        + '<input id="tfBancoNombre" class="form-control">'
                        + '</div>',
                    showCancelButton: true,
                    confirmButtonText: "Guardar",
                    preConfirm: function () {
                        var razon = (document.getElementById('tfBancoRazon') || {}).value || '';
                        var nombre = (document.getElementById('tfBancoNombre') || {}).value || '';
                        if (!razon.trim() || !nombre.trim()) {
                            Swal.showValidationMessage('Completa razon social y nombre comercial');
                            return false;
                        }
                        return { razonSocialBanco: razon.trim(), comercialBanco: nombre.trim() };
                    }
                });
                if (!res.isConfirmed || !res.value) return;
                try {
                    await axios.post("../api/crud_bancos.php", { accion: 7, formValues: res.value });
                    Swal.fire({ icon: "success", title: "Banco agregado", timer: 1200, showConfirmButton: false, toast: true, position: "top-end" });
                    await this.loadBancos();
                } catch (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || "Error al agregar";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                }
            },
            viewBanco: async function (id) {
                try {
                    var r = await axios.post("../api/crud_bancos.php", { accion: 5, id_banco: id });
                    var b = (r.data && r.data[0]) ? r.data[0] : null;
                    if (!b) return;
                    await Swal.fire({
                        title: "Detalle banco",
                        html: '<div class="text-start">'
                            + '<p><strong>Razon social:</strong> ' + (b.banco_razonSocial || '-') + '</p>'
                            + '<p><strong>Nombre comercial:</strong> ' + (b.banco_nombreComercial || '-') + '</p>'
                            + '</div>'
                    });
                } catch (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || "Error al consultar";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                }
            },
            editProveedor: async function (indice, id) {
                try {
                    var r = await axios.post("../api/crud_bancos.php", { accion: 5, id_banco: id });
                    var b = (r.data && r.data[0]) ? r.data[0] : null;
                    if (!b) return;
                    var res = await Swal.fire({
                        title: "Editar banco",
                        html: '<div class="text-start">'
                            + '<label class="form-label">Razon social</label>'
                            + '<input id="tfBancoRazonEdit" class="form-control mb-2" value="' + (b.banco_razonSocial || '') + '">'
                            + '<label class="form-label">Nombre comercial</label>'
                            + '<input id="tfBancoNombreEdit" class="form-control" value="' + (b.banco_nombreComercial || '') + '">'
                            + '</div>',
                        showCancelButton: true,
                        confirmButtonText: "Guardar",
                        preConfirm: function () {
                            var razon = (document.getElementById('tfBancoRazonEdit') || {}).value || '';
                            var nombre = (document.getElementById('tfBancoNombreEdit') || {}).value || '';
                            if (!razon.trim() || !nombre.trim()) {
                                Swal.showValidationMessage('Completa razon social y nombre comercial');
                                return false;
                            }
                            return { razonSocialBanco: razon.trim(), comercialBanco: nombre.trim() };
                        }
                    });
                    if (!res.isConfirmed || !res.value) return;
                    await axios.post("../api/crud_bancos.php", { accion: 6, id_banco: id, formValues: res.value });
                    Swal.fire({ icon: "success", title: "Banco actualizado", timer: 1200, showConfirmButton: false, toast: true, position: "top-end" });
                    await this.loadBancos();
                } catch (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || "Error al editar";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                }
            },
            desactivarBanco: async function (indice, id) {
                var res = await Swal.fire({
                    title: "Eliminar banco?",
                    text: "Esta accion no se puede deshacer.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Si, eliminar",
                    cancelButtonText: "Cancelar"
                });
                if (!res.isConfirmed) return;
                try {
                    await axios.post("../api/crud_bancos.php", { accion: 4, id_banco: id });
                    Swal.fire({ icon: "success", title: "Eliminado", timer: 1200, showConfirmButton: false, toast: true, position: "top-end" });
                    await this.loadBancos();
                } catch (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || "Error al eliminar";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                }
            }
        };
    }
JS;
include __DIR__ . '/../includes/layout_bottom.php';
?>
