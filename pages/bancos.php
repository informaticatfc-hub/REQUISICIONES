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

<<<<<<< Updated upstream
<head>
    <meta charset="utf8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" type="image/jpg" href="../images/TheFuenteIcon.png" />
    <!--llamar a la extension de sweet alert-->
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css">
    <!-- fuente de Roboto flex-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <!--Fuentes de Iconos-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!--llamar a la extension de bootstrap-->
    <!-- esta es la llamada via CDN-
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">-->
    <!-- esta es la llamada local-->
    <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap.min.css">
    <!--Esta es la llamada CSS de data table-->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <!--llamar a mi documento de CSS-->
    <link rel="stylesheet" href="../assets/css/main.css">
    <title>Catalago de Bancos</title>
</head>

<body class="app-layout">
    <div id="AppIndex">

        <!--sidebar-->
        <div class="d-flex flex-column flex-shrink-0 p-3 text-white position-fixed top-0 start-0 h-100 app-sidebar" id="sidebar">
            <div class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <div class="d-flex flex-row">
                    <div class="d-flex align-items-center me-3">
                        <img src="../images/icons/user.svg" alt="user-icon" height="60" width="60">
                    </div>
                    <div class="d-flex flex-column my-3">
                        <span class="fs-5"> {{NameUser}}</span>
                    </div>
                </div>
            </div>
            <hr>
            <div id="sideBarItem" class="mb-auto overflow-auto page-content">
                <ul class="nav nav-pills flex-column f-5" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <li v-if="users.length && users[0].user_directionAcess == 1">
                        <a href="#" class="nav-link text-white" id="v-pills-reports-tab" data-bs-toggle="pill" data-bs-target="#v-pills-reports" type="button" role="tab" aria-controls="v-pills-reports" aria-selected="false" @click="irDireecion">
                            <img class="me-2" src="../images/icons/ceo.svg" alt="user-icon" height="24" width="24">
                            DIRECCION
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link text-white" aria-current="page" id="v-pills-obras-tab" data-bs-toggle="pill" data-bs-target="#v-pills-obras" type="button" role="tab" aria-controls="v-pills-obras" aria-selected="true">
                            <img class="me-2" src="../images/icons/obras.svg" alt="user-icon" height="24" width="24">
                            OBRAS
                        </a>
                        <div class="tab-content" id="v-pills-tabContent">
                            <ul class="tab-pane fade nav nav-pills flex-column mb-auto" id="v-pills-obras" role="tabpanel" aria-labelledby="v-pills-obras-tab">
                                <li v-for="obra in this.obras">
                                    <a style="cursor: pointer" class="nav-link text-white ms-4" aria-current="page" @click="irObra(obra.obras_id)">{{obra.obras_nombre}}</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="#" class="nav-link text-white" aria-current="page" id="v-pills-catalago-tab" data-bs-toggle="pill" data-bs-target="#v-pills-catalago" type="button" role="tab" aria-controls="v-pills-catalago" aria-selected="false" @click="irMenuCatalago">
                            <img class="me-2" src="../images/icons/catalagos.svg" alt="user-icon" height="24" width="24">
                            CATALAGOS
                        </a>
                    </li>
                </ul>
            </div>
            <hr>
            <div class="dropdown">
                <a href="./closeSesion.php" class="d-flex align-items-center text-white text-decoration-none f-5" aria-expanded="false">
                    <img class="me-2" src="../images/icons/logout.svg" alt="user-icon" height="24" width="24">
                    <span>CERRAR SESION</span>
                </a>
            </div>
=======
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Catalogo financiero</span>
            <h1 class="tf-page-title">Catalogo de Bancos</h1>
            <p class="tf-page-lead">Consulta y administra los bancos registrados en el sistema.</p>
>>>>>>> Stashed changes
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
