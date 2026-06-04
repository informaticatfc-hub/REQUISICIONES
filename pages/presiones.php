<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['presiones.view'], 'No tienes permiso para ver presiones');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Usuario';
$usuario_rolCode = $__user['role']['code'] ?? 'usuario';
$usuario_perms   = $__user['permissions']  ?? [];

$canCreatePresion = tf_has_permission('presiones.create', $__user);

$tf_extra_head = '<style>[x-cloak]{display:none!important;}</style>';
$tf_page_title     = 'Presiones';
$tf_active_nav     = 'presiones';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Presiones', '#']];
$tf_user           = [
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
    <title>PRESIONES DE LA OBRA</title>
</head>

<body class="app-layout">
    <div id="AppPresion">
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
                                <li v-for="obra in this.obrasLista">
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
<div id="AppPresion" class="tf-page-inner" x-data="presionesApp()" x-init="init()" x-cloak>
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Seguimiento semanal</span>
            <h1 class="tf-page-title">Presiones <span x-show="obras.length">- <span x-text="obras.length ? obras[0].obras_nombre : ''"></span></span></h1>
            <p class="tf-page-lead">Gestiona las presiones de pago de la obra activa.</p>
>>>>>>> Stashed changes
        </div>
        <?php if ($canCreatePresion): ?>
        <div class="tf-page-header-actions">
            <button type="button" class="tf-btn tf-btn-primary" x-on:click="NewPression">
                <i class="bi bi-plus-lg"></i> Agregar Presion
            </button>
        </div>
        <?php endif; ?>
    </header>

    <section class="tf-kpi-grid">
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Total presiones</span></div>
            <div class="tf-kpi-value" x-text="totalPresiones"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Pendientes</span></div>
            <div class="tf-kpi-value" x-text="pendientesCount"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Autorizadas</span></div>
            <div class="tf-kpi-value" x-text="autorizadasCount"></div>
        </article>
    </section>

    <!-- Tabs: Todas / Pendientes de pago -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='todas'}" x-on:click="setActiveTab('todas')">
                <i class="bi bi-list-ul"></i> Todas
                <span class="badge bg-secondary ms-1" x-text="totalPresiones"></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='pendientes'}" x-on:click="setActiveTab('pendientes')">
                <i class="bi bi-hourglass-split"></i> Pendientes de pago
                <span class="badge bg-warning text-dark ms-1" x-show="autorizadasCount" x-text="autorizadasCount"></span>
            </button>
        </li>
    </ul>

    <section class="tf-card" x-show="activeTab==='todas'">
        <div class="p-3 border-bottom">
            <input x-model="searchText" x-on:input="onSearchInput"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar por nombre, alias, semana o día..."
                   style="max-width:320px">
        </div>
        <div class="tf-card-body p-0" style="overflow-x:auto;">
            <table id="example" class="tf-admin-table w-100">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Alias</th>
                        <th>Semana</th>
                        <th>Dia</th>
                        <th>Estatus</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(presion,indice) in presiones" :key="presion.presiones_id + '-' + indice">
                    <tr>
                        <td x-text="presion.presiones_nombre"></td>
                        <td x-text="presion.presiones_alias"></td>
                        <td x-text="presion.presiones_semana"></td>
                        <td x-text="presion.presiones_dia"></td>
                        <td>
                            <span class="tf-status tf-status-inactive" x-show="presion.presiones_estatus == 'PENDIENTE'">PENDIENTE DE PAGO</span>
                            <span class="tf-status tf-status-active" x-show="presion.presiones_estatus != 'PENDIENTE'">CERRADA</span>
                        </td>
                        <td style="text-align:right">
                            <div class="btn-group dropdown" role="group">
                                <button type="button" class="tf-btn tf-btn-sm tf-btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <?php if ($canCreatePresion): ?>
                                        <a class="dropdown-item" href="#" x-show="presion.presiones_estatus == 'PENDIENTE'" x-on:click.prevent="ConsultarPresion(presion.presiones_id,1,0,0)">
                                            Enlazar Requisiciones
                                        </a>
                                        <a class="dropdown-item disabled" href="#" x-show="presion.presiones_estatus != 'PENDIENTE'">
                                            Enlazar Requisiciones
                                        </a>
                                        <?php endif; ?>
                                    </li>
                                    <li><a class="dropdown-item" href="#" x-on:click.prevent="ConsultarPresion(presion.presiones_id,2,presion.presiones_semana,presion.presiones_dia)">Detalles de la Presion</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Tab: Pendientes de pago (AUTORIZADO) -->
    <section class="tf-card" x-show="activeTab==='pendientes'">
        <div class="p-3 border-bottom">
            <input x-model="searchText" x-on:input="onSearchInput"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar pendientes por nombre, alias, semana o día..."
                   style="max-width:320px">
        </div>
        <div class="tf-card-body p-0" style="overflow-x:auto;">
            <div x-show="!pendientesDePago.length" class="p-4 text-center text-muted">
                <i class="bi bi-check-circle" style="font-size:2rem"></i>
                <p class="mt-2 mb-0">Sin presiones pendientes de pago</p>
            </div>
            <table class="tf-admin-table w-100" x-show="pendientesDePago.length">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Alias</th>
                        <th>Semana</th>
                        <th>Dia</th>
                        <th style="text-align:right">Ir a detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(p, index) in pendientesDePago" :key="p.presiones_id + '-pend-' + index">
                    <tr>
                        <td x-text="p.presiones_nombre"></td>
                        <td x-text="p.presiones_alias"></td>
                        <td x-text="p.presiones_semana"></td>
                        <td x-text="p.presiones_dia"></td>
                        <td style="text-align:right">
                            <button class="tf-btn tf-btn-sm tf-btn-primary"
                                    x-on:click.prevent="ConsultarPresion(p.presiones_id,2,p.presiones_semana,p.presiones_dia)">
                                <i class="bi bi-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top">
        <small class="text-muted" x-text="'Página ' + currentPage + ' de ' + totalPages + ' · ' + totalRows + ' registros'"></small>
        <div class="btn-group">
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(1)" x-bind:disabled="currentPage <= 1">Primera</button>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage - 1)" x-bind:disabled="currentPage <= 1">Anterior</button>
            <template x-for="p in pageRange" :key="'pr-page-'+p">
            <button
                    class="tf-btn tf-btn-sm"
                    x-bind:class="p === currentPage ? 'tf-btn-primary' : 'tf-btn-ghost'"
                    x-on:click="goToPage(p)" x-text="p"></button>
            </template>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage + 1)" x-bind:disabled="currentPage >= totalPages">Siguiente</button>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(totalPages)" x-bind:disabled="currentPage >= totalPages">Última</button>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/js/presiones.js"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
