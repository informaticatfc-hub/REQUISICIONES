<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['requisiciones.edit'], 'No tienes permiso para crear hojas de requisición');

$tf_page_title = 'Nueva Hoja';
$tf_active_nav = 'obras';
$tf_breadcrumb = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Requisiciones', './requisiciones.php'], ['Hojas de la Requisicion', './hojas_requisicion.php'], ['Nueva Hoja', '#']];
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
$tf_extra_head = '<style>[x-cloak]{display:none!important;}</style>';

include __DIR__ . '/../includes/layout_top.php';
?>

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
    <!--llamar a mi documento de CSS-->
    <link rel="stylesheet" href="../assets/css/main.css">
    <title>NUEVA REQUISICION</title>
</head>

<body class="app-layout">
    <div id="AppReq">
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
=======
<div id="AppReq" class="tf-page-inner" x-data="nuevaHojaApp()" x-init="init()" x-cloak>
    <div class="tf-page-content page-form-shell">
        <div class="page-hdr">
            <div class="page-hdr-left">
                <h2 class="page-title">Nueva Hoja de Requisicion</h2>
                <p class="page-lead">Completa el formulario para agregar una nueva hoja a la requisicion.</p>
>>>>>>> Stashed changes
            </div>
        </div>

        <div class="row"><div class="col-6"><h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Datos Empresa</h3></div></div>
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-7"><label for="Nombre">Nombre de la Empresa</label><input type="text" class="form-control" id="Nombre" name="Nombre" x-model="Emisor_Nombre" readonly></div>
            <div class="col-5"><label for="RFC">RFC</label><input type="text" class="form-control" id="RFC" name="RFC" x-model="Emisor_RFC" readonly></div>
        </div>
        <div class="row form-group mx-0 mt-0 mb-3"><div class="col-12"><label for="Direccion">Direccion de la Empresa</label><input type="text" class="form-control" id="Direccion" name="Direccion" x-model="Emisor_Adress" readonly></div></div>
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-4"><label for="Telefono">Telefono</label><input type="text" class="form-control" id="Telefono" name="Telefono" x-model="Emisor_Phone" readonly></div>
            <div class="col-4"><label for="Fax">Fax</label><input type="text" class="form-control" id="Fax" name="Fax" x-model="Emisor_Fax" readonly></div>
            <div class="col-4"><label for="ZipCode">Codigo Postal</label><input type="text" class="form-control" id="ZipCode" name="ZipCode" x-model="Emisor_ZipCode" readonly></div>
        </div>

        <div class="row"><div class="col-6"><h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Datos del Proveedor</h3></div></div>
        <!-- C-M1: Búsqueda combinada por nombre / RFC / CLABE -->
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-12">
                <label for="ProvSearchInput">Nombre del Proveedor (busca por nombre, RFC o CLABE)</label>
                <div style="position:relative" x-data>
                    <input type="text"
                           id="ProvSearchInput"
                           class="form-control"
                           placeholder="Escribe nombre, RFC o CLABE del proveedor..."
                           x-model="provSearchText"
                           x-on:input="provDropOpen = provSearchText.length > 0"
                           x-on:keydown.escape="provDropOpen = false"
                           autocomplete="off">
                    <!-- Dropdown de resultados -->
                    <div x-show="provDropOpen && provSearchResults.length > 0"
                         x-on:click.outside="provDropOpen = false"
                         style="position:absolute;top:100%;left:0;right:0;z-index:1050;
                                background:#fff;border:1px solid #ccc;border-top:none;
                                max-height:260px;overflow-y:auto;border-radius:0 0 .375rem .375rem;
                                box-shadow:0 4px 12px rgba(0,0,0,.1)">
                        <template x-for="p in provSearchResults" x-bind:key="p.proveedor_id">
                            <button type="button"
                                    class="dropdown-item py-2 px-3 text-start"
                                    style="border-bottom:1px solid #f0f0f0"
                                    x-on:click="seleccionarProveedor(p)">
                                <div class="fw-semibold" x-text="p.proveedor_nombre"></div>
                                <small class="text-muted">
                                    RFC: <span x-text="p.proveedor_rfc || '—'"></span>
                                    &nbsp;·&nbsp;
                                    CLABE: <span x-text="p.proveedor_clabe || '—'"></span>
                                </small>
                            </button>
                        </template>
                    </div>
                    <div x-show="provDropOpen && provSearchText.length > 0 && provSearchResults.length === 0"
                         style="position:absolute;top:100%;left:0;right:0;z-index:1050;
                                background:#fff;border:1px solid #ccc;padding:.5rem 1rem;
                                border-radius:0 0 .375rem .375rem;color:#999;font-size:.9rem">
                        Sin coincidencias
                    </div>
                </div>
                <div x-show="Prov_Id" class="mt-1 small text-success">
                    <i class="bi bi-check-circle-fill"></i> Proveedor seleccionado: <strong x-text="Prov_Nombre_Display"></strong>
                </div>
            </div>
        </div>
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-6"><label for="RFCProv">RFC del Proveedor</label><input type="text" class="form-control" id="RFCProv" name="RFCProv" x-model="Prov_RFC" readonly></div>
            <div class="col-6"><label for="CLABE">CLABE Bancaria</label><input type="text" class="form-control" id="CLABE" name="CLABE" x-model="Prov_Clabe" readonly></div>
        </div>
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-3"><label for="CuentaBank">Cuenta Bancaria</label><input type="text" class="form-control" id="CuentaBank" name="CuentaBank" x-model="Prov_Cuenta" readonly></div>
            <div class="col-3"><label for="ReferenciaBank">Referencia Bancaria</label><input type="text" class="form-control" id="ReferenciaBank" name="ReferenciaBank" x-model="Prov_RefBank" readonly></div>
            <div class="col-3"><label for="CardBank">Tarjeta Bancaria</label><input type="text" class="form-control" id="CardBank" name="CardBank" x-model="Prov_BankCard" readonly></div>
            <div class="col-3"><label for="Bank">Banco</label><input type="text" class="form-control" id="Bank" name="Bank" x-model="Prov_Bank" readonly></div>
        </div>
        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-6"><label for="Email">Correo Electronico</label><input type="text" class="form-control" id="Email" name="Email" x-model="Prov_Email" readonly></div>
            <div class="col-3"><label for="Telefono">Telefono</label><input type="text" class="form-control" id="Telefono" name="Telefono" x-model="Prov_Phone" readonly></div>
            <div class="col-3"><label for="SuncBank">Sucursal del Banco</label><input type="text" class="form-control" id="SuncBank" name="SuncBank" x-model="Prov_SucBank" readonly></div>
        </div>

        <div class="row"><div class="col-6"><h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Items de la Requisicion</h3></div></div>
        <div class="row form-group mx-0 mt-0 mb-1"><div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="PagoTransfs" x-on:click="pagoTransaccionActivado" checked><label class="form-check-label" for="PagoTransfs">Pagar por Transferencia</label></div></div></div>

        <div class="row form-group mx-0 mt-0 mb-3">
            <div class="col-12">
                <table class="table table-prof table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Lote</th><th scope="col">Unidad</th><th scope="col">Producto</th><th scope="col">Cantidad</th><th scope="col">Precio Unitario</th><th scope="col">IVA</th><th scope="col">Retenciones</th><th scope="col">Total</th><th></th>
                        </tr>
                    </thead>
                    <tbody class="table-light" id="Tabla_Items">
                        <template x-for="(item,indice) in Items" :key="item.Lote + '-' + indice">
                        <tr class="my-3">
                            <th scope="row" class="py-3 celda_Item text-center align-middle" x-text="item.Lote"></th>
                            <td class="py-3 celda_Item text-center align-middle" x-text="item.Unidad"></td>
                            <td class="py-3 celda_Item text-break" style="max-width: 200px" x-text="item.Nombre"></td>
                            <td class="py-3 celda_Item text-center align-middle" x-text="item.Cantidad"></td>
                            <td class="py-3 celda_Item text-center align-middle" x-text="formatearMoneda(item.UnitedPrice)"></td>
                            <td class="py-3 celda_Item text-center align-middle">+ <span x-text="formatearMoneda(item.IVA)"></span></td>
                            <td class="py-3 celda_Item text-center align-middle">- <span x-text="formatearMoneda(item.Retenciones)"></span></td>
                            <td class="py-3 celda_Item text-center align-middle" x-text="formatearMoneda(item.STotal)"></td>
                            <td class="py-3 celda_Item text-center align-middle">
                                <button type="button" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Eliminar item" x-on:click="eliminarItem(indice)">
                                    <img src="../images/icons/delete.svg" alt="eliminar" height="24" width="24">
                                </button>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                    <tfoot class="table-dark"><tr><th colspan="7" class="table-active text-end">Total: </th><td colspan="2" x-text="formatearMoneda(Total_Pagar_Mostrar)"></td></tr></tfoot>
                </table>
            </div>

            <div class="row w-100 mt-0 mb-3 mx-auto"><div class="col px-0 d-flex justify-content-center"><button type="submit" class="btn btn-primary" x-on:click="showModalAddItem" title="Agregar el Item"><span class="text-center">Agregar Item</span></button></div></div>

            <div class="row my-3" x-show="conceptoUnico"><div class="col"><label for="conceptResumen" class="form-label fw-bold mb-1">Concepto Unico</label><div id="conceptResumenHelp" class="form-text mb-1">El concepto resumido remplazara a los items de esta hoja por este unico concepto. Este "concepto unico" sera visible unicamente en la presion</div><textarea class="form-control border-primary" id="conceptResumen" rows="3" x-model="conceptoUnicoText" placeholder="Ingresa tu concepto unico"></textarea></div></div>

            <div class="row form-group mx-0 mt-0 mb-3"><div class="col-12 px-0"><label for="Observ">Observaciones Adicionales</label><textarea class="form-control" id="Observ" rows="3" x-model="observaciones"></textarea></div></div>
            <div class="row w-100 mt-0 mb-3 mx-auto"><div class="col px-0 d-flex justify-content-center"><button class="btn btn-success" x-on:click="agregarRequisicion()" title="Agregar Requisicion"><span class="text-center">Crear Hoja de la Requisicion</span></button></div></div>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_use_jquery = true;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/js/nueva_hoja.js?v=fase08n"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
