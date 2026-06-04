<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['requisiciones.view', 'requisiciones.edit', 'direccion.view', 'presiones.authorize'], 'No tienes permiso para ver las hojas de requisición');

$tf_page_title = 'Hojas de la Requisicion';
$tf_active_nav = 'obras';
$tf_breadcrumb = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Requisiciones', './requisiciones.php'], ['Hojas de la Requisicion', '#']];
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
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.css">
<style>[x-cloak]{display:none!important;}</style>
CSS;

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
    <!--Esta es la llamada CSS de data table-->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <!--llamar a mi documento de CSS-->
    <link rel="stylesheet" href="../assets/css/main.css">
    <title>HOJAS DE LA REQUISICION</title>
</head>

<body class="app-layout">
    <div id="AppHojas">
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
=======
<div id="AppHojas" class="tf-page-inner" x-data="hojasReqApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <div class="page-hdr">
            <div class="page-hdr-left">
                <h2 class="page-title">Hojas - Requisicion <span x-text="(requisiciones[0] && requisiciones[0].requisicion_Numero) || ''"></span></h2>
                <p class="page-lead">Consulta y administra las hojas que componen esta requisicion.</p>
                <div class="obras-chip mt-2">
                    <span class="obras-chip-dot"></span>
                    Requisicion activa
>>>>>>> Stashed changes
                </div>
            </div>
            <div class="page-hdr-right" x-show="canReqEdit">
                <button type="button" class="btn btn-success" x-on:click="addHoja">Agregar Hoja <span x-text="Number((requisiciones[0] && requisiciones[0].requisicion_Hojas) || 0) + 1"></span></button>
            </div>
        </div>

        <div class="ops-hero-grid">
            <div class="quick-tile">
                <span class="quick-tile-label">Numero de Requisicion</span>
                <span class="quick-tile-value small" x-text="(requisiciones[0] && requisiciones[0].requisicion_Numero) || ''"></span>
            </div>
            <div class="quick-tile">
                <span class="quick-tile-label">Total de Hojas</span>
                <span class="quick-tile-value" x-text="hojas.length"></span>
            </div>
            <div class="quick-tile">
                <span class="quick-tile-label">Monto Total Requisición</span>
                <span class="quick-tile-value small" x-text="formatearMoneda(montoTotalRequisicion,true)"></span>
            </div>
            <div class="quick-tile">
                <span class="quick-tile-label">Obra</span>
                <span class="quick-tile-value small" x-text="obras.length ? obras[0].obras_nombre : ''"></span>
            </div>
        </div>

        <div class="table-wrapper">
            <div class="overflow-auto">
                <table id="example" class="table table-prof table-hover w-100">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="text-center align-middle">Numero de Hoja</th>
                            <th scope="col" class="text-center align-middle">Forma de Pago</th>
                            <th scope="col" class="text-center align-middle">Total de Pagar</th>
                            <th scope="col" class="text-center align-middle">Estatus</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="table-light" id="Tabla_Items">
                        <template x-for="(hoja,indice) in hojas" :key="hoja.hojaRequisicion_id + '-' + indice">
                        <tr class="my-3">
                            <td scope="row" class="text-center align-middle">Hoja N° <span x-text="hoja.hojaRequisicion_numero"></span></td>
                            <td class="text-center align-middle" x-text="hoja.hojaRequisicion_formaPago"></td>
                            <td class="text-center align-middle" x-text="formatearMoneda(hoja.hojaRequisicion_total,true)"></td>
                            <td class="text-center align-middle">
                                <!-- R-M2: Badge color-coded único + botón historial -->
                                <span class="badge"
                                    x-bind:class="{
                                        'bg-secondary':  hoja.hojaRequisicion_estatus === 'NUEVO',
                                        'bg-warning text-dark': hoja.hojaRequisicion_estatus === 'PENDIENTE' || hoja.hojaRequisicion_estatus === 'REVISION',
                                        'bg-info text-dark':    hoja.hojaRequisicion_estatus === 'LIGADA',
                                        'bg-danger':     hoja.hojaRequisicion_estatus === 'RECHAZADA',
                                        'bg-primary':    hoja.hojaRequisicion_estatus === 'AUTORIZADA',
                                        'bg-success':    hoja.hojaRequisicion_estatus === 'PAGADA'
                                    }"
                                    x-text="hoja.hojaRequisicion_estatus === 'PENDIENTE' ? 'NO APROBADO' : hoja.hojaRequisicion_estatus">
                                </span>
                                <button class="btn btn-link btn-sm p-0 ms-1 text-muted"
                                        title="Ver historial de cambios"
                                        x-on:click="verHistorialHoja(hoja.hojaRequisicion_id, hoja.hojaRequisicion_numero)">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group" role="group" aria-label="acciones hoja">
                                    <button type="button" class="btn btn-success" x-on:click="ConsultarItemHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Consultar Hoja">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-white" viewBox="0 0 16 16">
                                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                        </svg>
                                    </button>
                                    <!-- C-M3: Duplicar hoja -->
                                    <button type="button" class="btn btn-outline-secondary" x-show="canReqEdit" x-on:click="duplicarHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Duplicar Hoja">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" x-on:click="eliminarHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Eliminar Hoja" x-show="canReqEdit && (hoja.hojaRequisicion_estatus == 'NUEVO' || hoja.hojaRequisicion_estatus == 'PENDIENTE' || hoja.hojaRequisicion_estatus == 'RECHAZADA')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                            <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="2" class="text-end">Total requisición:</td>
                            <td class="text-center" x-text="formatearMoneda(montoTotalRequisicion,true)"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- R-M2 / R-M3: Modal historial de estatus / Timeline -->
<div class="modal fade" id="modalHistorialHoja" tabindex="-1" aria-labelledby="modalHistorialHojaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialHojaLabel">
                    <i class="bi bi-clock-history me-2"></i>Historial de estatus — <span id="histModalHojaNum"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Timeline flujo de aprobación -->
                <div class="d-flex flex-wrap gap-1 mb-4 align-items-center small text-muted">
                    <span class="badge bg-secondary">NUEVO</span><i class="bi bi-arrow-right"></i>
                    <span class="badge bg-warning text-dark">PENDIENTE</span><i class="bi bi-arrow-right"></i>
                    <span class="badge bg-warning text-dark">REVISIÓN</span><i class="bi bi-arrow-right"></i>
                    <span class="badge bg-info text-dark">LIGADA</span><i class="bi bi-arrow-right"></i>
                    <span class="badge bg-primary">AUTORIZADA</span><i class="bi bi-arrow-right"></i>
                    <span class="badge bg-success">PAGADA</span>
                    <span class="ms-2 text-muted">(o <span class="badge bg-danger">RECHAZADA</span>)</span>
                </div>
                <div id="histModalLoading" class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2"></div> Cargando historial…
                </div>
                <div id="histModalContent" class="d-none">
                    <div class="position-relative">
                        <div id="histModalTimeline"></div>
                    </div>
                    <p id="histModalEmpty" class="text-muted text-center py-3 d-none">Sin registros de historial para esta hoja.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_use_jquery = true;
$tf_use_datatables = true;
$tf_extra_scripts =
    '<script>window.TF_LEGACY_PERMS = {canReqEdit:' . ($__canReqEdit ? 'true' : 'false') . ', canDireccion:' . ($__canDireccion ? 'true' : 'false') . '};</script>' .
    '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>' .
    '<script src="../assets/js/hojas_requisicion.js?v=fase08o"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
