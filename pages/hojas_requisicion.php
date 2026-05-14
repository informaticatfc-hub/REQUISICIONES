<?php
include_once '../validarSesion.php';
?>
<!DOCTYPE html>
<html>

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
        </div>
        <div class="d-flex flex-column flex-shrink-0 h-100 position-fixed top-0 end-0 app-main">
            <!--Navbar-->
            <nav class="navbar app-navbar">
                <div class="container-fluid">
                    <span class="navbar-brand text-light text-center w-100 fw-bolder">The Fuentes Corporation Workspace</span>
                </div>
            </nav>
            <nav class="nav shadow-sm d-flex align-items-center" id="navtab" aria-label="breadcrumb" aria-current="page">
                <ol class="breadcrumb py-2 px-3 my-0">
                    <li class="breadcrumb-item">
                        <a href="./index.php">
                            <img class="" src="../images/icons/home.svg" alt="user-icon" height="24" width="24">
                            <span>Inicio</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><a href="./obras.php"><span>Menu de Obra: {{obras.length ? obras[0].obras_nombre : ''}}</span></a></li>
                    <li class="breadcrumb-item"><a href="./requisiciones.php"><span>Requisiciones de Obra</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Hojas de la Requisicion</span></li>
                </ol>
            </nav>
            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Hojas &mdash; Requisicion {{this.requisiciones[0].requisicion_Numero}}</h2>
                        <p class="page-lead">Consulta y administra las hojas que componen esta requisicion.</p>
                        <div class="obras-chip mt-2">
                            <span class="obras-chip-dot"></span>
                            Requisicion activa
                        </div>
                    </div>
                    <div class="page-hdr-right" v-if="users.length && users[0].user_editReq == 1">
                        <button type="button" class="btn btn-success" @click="addHoja">Agregar Hoja {{Number(this.requisiciones[0].requisicion_Hojas) + 1}}</button>
                    </div>
                </div>
                <div class="ops-hero-grid">
                    <div class="quick-tile">
                        <span class="quick-tile-label">Numero de Requisicion</span>
                        <span class="quick-tile-value small">{{this.requisiciones[0].requisicion_Numero}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total de Hojas</span>
                        <span class="quick-tile-value">{{hojas.length}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Obra</span>
                        <span class="quick-tile-value small">{{obras.length ? obras[0].obras_nombre : ''}}</span>
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
                                <tr class="my-3" v-for="(hoja,indice) of hojas">
                                    <td scope="row" class="text-center align-middle">Hoja N° {{hoja.hojaRequisicion_numero}}</td>
                                    <td class="text-center align-middle">{{hoja.hojaRequisicion_formaPago}}</td>
                                    <td class="text-center align-middle">{{formatearMoneda(hoja.hojaRequisicion_total,true)}}</td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-primary" v-if="hoja.hojaRequisicion_estatus == 'NUEVO'">NUEVO</span>
                                        <span class="badge bg-danger" v-if="hoja.hojaRequisicion_estatus == 'PENDIENTE'">NO APROVADO</span>
                                        <span class="badge bg-warning" v-if="hoja.hojaRequisicion_estatus == 'REVISION'">REVISION</span>
                                        <span class="badge bg-primary" v-if="hoja.hojaRequisicion_estatus == 'LIGADA'">LIGADA A PRESION</span>
                                        <span class="badge bg-danger" v-if="hoja.hojaRequisicion_estatus == 'RECHAZADA'">RECHAZADA</span>
                                        <span class="badge bg-primary" v-if="hoja.hojaRequisicion_estatus == 'AUTORIZADA'">AUTORIZADA</span>
                                        <span class="badge bg-success" v-if="hoja.hojaRequisicion_estatus == 'PAGADA'">PAGADA</span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                                            <button type="button" class="btn btn-success" @click="ConsultarItemHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Consultar Hoja">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-white" viewBox="0 0 16 16">
                                                    <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                                    <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-danger" @click="eleminarHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Eliminar Hoja" v-if="hoja.hojaRequisicion_estatus == 'NUEVO' || hoja.hojaRequisicion_estatus == 'PENDIENTE' || hoja.hojaRequisicion_estatus == 'RECHAZADA'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-dark">
                            </tfoot>
                        </table>
                </div><!-- /overflow-auto -->
                </div><!-- /table-wrapper -->
            </div>
        </div>
    </div>
    <!--scripts de bootstrap, poppers y jquery-->
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- scripts de vue.js-->
    <script src="../assets/lib/vue/vue.min.js"></script>

    <!--Script de axios-->
    <script src="../assets/lib/axios/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!--esta es la llamada cdn de datatable-->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/hojas_requisicion.js"></script>
    <script src="../assets/js/layout_sidebar.js?v=fase07b"></script>
</body>

</html>


