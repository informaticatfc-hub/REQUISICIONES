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
    <title>PRESION DE LA SEMANA</title>
</head>

<body class="app-layout">
    <div id="AppPresionDetail">
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
                    <li class="breadcrumb-item"><a href="./obras.php"><span>Menu de Obra</span></a></li>
                    <li class="breadcrumb-item"><a href="./presiones.php"><span>Presiones de Obra</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Presion de la Semana {{semana}} y del dia {{dia}} de la obra {{obraActiva.length ? obraActiva[0].obras_nombre : ''}}</span></li>
                </ol>
            </nav>
            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Presion &mdash; Semana {{this.semana}} / Dia {{this.dia}}</h2>
                        <p class="page-lead">Detalle de la presion de pago de la obra {{obraActiva.length ? obraActiva[0].obras_nombre : ''}}.</p>
                        <div class="obras-chip mt-2">
                            <span class="obras-chip-dot"></span>
                            Detalle de presion
                        </div>
                    </div>
                    <div class="page-hdr-right">
                        <button type="button" class="btn btn-success" @click="exportarExcel">Exportar Excel</button>
                    </div>
                </div>
                <div class="ops-hero-grid">
                    <div class="quick-tile">
                        <span class="quick-tile-label">Semana / Dia</span>
                        <span class="quick-tile-value small">Sem {{this.semana}} &mdash; Dia {{this.dia}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Items en Presion</span>
                        <span class="quick-tile-value">{{presiones.filter(function(p){return p.HojaEstatus == 'LIGADA' || p.HojaEstatus == 'AUTORIZADA' || p.HojaEstatus == 'PAGADA'}).length}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Obra</span>
                        <span class="quick-tile-value small">{{obraActiva.length ? obraActiva[0].obras_nombre : ''}}</span>
                    </div>
                </div>
                <div class="table-wrapper">
                <div class="overflow-auto">
                        <table id="example" class="table table-prof align-middle table-hover w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" class="text-center align-middle">CLAVE</th>
                                    <th scope="col" class="text-center align-middle">N° DE REQUISICION</th>
                                    <th scope="col" class="text-center align-middle">PROVEEDOR</th>
                                    <th scope="col" class="text-center align-middle">CONCEPTO</th>
                                    <th scope="col" class="text-center align-middle">ADEUDO</th>
                                    <th scope="col" class="text-center align-middle">NETO A PAGAR</th>
                                    <th scope="col" class="text-center align-middle">FORMA DE PAGO</th>
                                    <th scope="col" class="text-center align-middle">FECHA DE PAGO</th>
                                    <th scope="col" class="text-center align-middle">BANCO DE PAGO</th>
                                    <th scope="col" class="text-center align-middle">APLICAR ACCIONES</th>
                                    <th class="text-center align-middle"></th>
                                </tr>
                            </thead>
                            <tbody class="table-light" id="Tabla_Items">
                                <tr class="my-3" v-for="(presion,indice) of presiones" v-if="presion.HojaEstatus == 'LIGADA' || presion.HojaEstatus == 'AUTORIZADA' || presion.HojaEstatus == 'PAGADA'">
                                    <td scope="row" class="text-center align-middle inline-block fs-6">{{presion.clave}}</td>
                                    <td :class="presion.atrClass" :style="presion.strStyle">{{presion.NumReq}}</td>
                                    <td :class="presion.atrClass" :style="presion.strStyle">{{presion.proveedor}}</td>
                                    <td :class="presion.atrClass" :style="presion.strStyle">{{presion.concepto}}</td>
                                    <td class="text-center align-middle fs-6">{{formatearMoneda(presion.total, true)}}</td>
                                    <td class="text-center align-middle fs-6">{{formatearMoneda(presion.adeudo, true)}}</td>
                                    <td class="text-center align-middle fs-6">{{presion.formaPago}}</td>
                                    <td class="text-center align-middle fs-6">
                                        <input type="date" class="form-control" id="FechaPago" v-model="presion.Fecha">
                                    </td>
                                    <td class="text-center align-middle fs-6">
                                        <input type="text" class="form-control" id="BancoPago" v-model="presion.Banco" placeholder="Ingresa Banco">
                                    </td>
                                    <td class="text-center align-middle inline-block fs-6">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Basic mixed styles example">
                                            <button type="button" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" data-toggle="tooltip" :title="'Descargar ' + presion.NumReq" @click="imprimirReq(presion.NumRequi,presion.clave,presion.id_hoja)">
                                                <img class="" src="../images/icons/download.svg" alt="user-icon" height="24" width="24">
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <img class="me-2" v-if="presion.showDetail == false" src="../images/icons/arrow_down.svg" alt="user-icon" height="24" width="24" style="cursor: pointer;" @click="cambiarBooleano(presion.showDetail,indice)">
                                        <img class="me-2" v-if="presion.showDetail == true" src="../images/icons/arrow_up.svg" alt="user-icon" height="24" width="24" style="cursor: pointer;" @click="cambiarBooleano(presion.showDetail,indice)">
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr class="my-3">
                                    <td colspan="4" class="text-end">Total: </td>
                                    <td class="text-center align-middle fs-6">{{formatearMoneda(sumatoria(presiones,"total"),true)}}</td>
                                    <td class="text-center align-middle fs-6">{{formatearMoneda(sumatoria(presiones,"adeudo"),true)}}</td>
                                    <td colspan="7"></td>
                                </tr>
                            </tfoot>
                        </table>
                </div><!-- /overflow-auto -->
                </div><!-- /table-wrapper -->
                <div class="d-flex justify-content-center mt-4 mb-3" v-if="this.estatus == 'PENDIENTE'">
                    <button class="btn btn-primary px-4" @click="cerrarPresion" title="Cerrar Presion">CERRAR Y GUARDAR PRESION</button>
                </div>
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

    <!--CDN de la bibloteca JsPDF-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>

    <script src="../assets/js/pdfGenerate.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/presiones_detalles.js"></script>
    <script src="../assets/js/layout_sidebar.js?v=fase07b"></script>
</body>

</html>


