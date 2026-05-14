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
    <title>Presiones de Todas las Obras</title>
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
                    <li v-if="this.users[0].user_directionAcess == 1">
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
                    <li class="breadcrumb-item"><a href="./direccion.php"><span>Menu Direccion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Presiones de todas la Obras</span></li>
                </ol>
            </nav>
            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Presiones Pendientes &mdash; Todas las Obras</h2>
                        <p class="page-lead">Presiones pendientes de autorizacion y pago en todas las obras activas. Ingresa el monto autorizado y guarda los cambios.</p>
                        <div class="obras-chip mt-2">
                            <span class="obras-chip-dot"></span>
                            Vista de Direccion
                        </div>
                    </div>
                </div>
                <div class="ops-hero-grid">
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total Obras</span>
                        <span class="quick-tile-value">{{presiones.length}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total Presiones</span>
                        <span class="quick-tile-value">{{presiones.reduce(function(s,o){return s + o.Presion_Obra.length}, 0)}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Pendientes de Pago</span>
                        <span class="quick-tile-value">{{presiones.reduce(function(s,o){return s + o.Presion_Obra.length}, 0)}}</span>
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item" v-for="(obra, index) in presiones" :key="index">
                            <h2 class="accordion-header">
                                <button v-bind:class="'accordion-button fw-bold '+ obra.colapse_Atr" type="button" data-bs-toggle="collapse" v-bind:data-bs-target="'#' + 'collapse' + quitarEspacios(obra.Nombre_Obra)" v-bind:aria-expanded="obra.colapse_band" v-bind:aria-controls="'collapse' + quitarEspacios(obra.Nombre_Obra)">
                                    Obra: {{obra.Nombre_Obra}}
                                </button>
                            </h2>
                            <div v-bind:id="'collapse'+ quitarEspacios(obra.Nombre_Obra)" class="'accordion-collapse collapse ' + obra.colapse_show" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col d-flex align-items-end mb-3">
                                            <button type="button" class="btn btn-success ms-auto">
                                                <span class="fw-bold text-white" @click="consultarTotales(obra.total_Glabal, obra.total_Global_Aut, obra.total_Efectivo, obra.total_Efectivo_Aut, obra.total_Transferencia, obra.total_Transferencia_Aut, obra.total_Global_Rechazado, obra.total_Efectivo_Rechazado, obra.total_Transferencia_Rechazado, obra.Nombre_Obra)">Consultar Totales</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col table-responsive">
                                            <table id="example" class="table table-prof align-middle table-hover w-100">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th scope="col" class="fs-6">CLAVE</th>
                                                        <th scope="col" class="fs-6">N° DE REQUISICION</th>
                                                        <th scope="col" class="fs-6">PROVEEDOR</th>
                                                        <th scope="col" class="fs-6">CONCEPTO</th>
                                                        <th scope="col" class="fs-6">ADEUDO</th>
                                                        <th scope="col" class="fs-6">PAGO AUTORIZADO</th>
                                                        <th scope="col" class="fs-6">OBSERVACIONES</th>
                                                        <th scope="col" class="fs-6">FORMA DE PAGO</th>                                                    
                                                    </tr>
                                                </thead>
                                                <tbody class="table-light" id="Tabla_Items">
                                                    <tr class="my-3" v-for="(presionObra,indice) of obra.Presion_Obra">
                                                        <td>{{presionObra.clave}}</td>
                                                        <td class="fs-6">{{presionObra.NumReq}}</td>
                                                        <td class="fs-6">{{presionObra.proveedor}}</td>
                                                        <td class="fs-6">{{presionObra.concepto}}</td>
                                                        <td class="fs-6">{{formatearMoneda(presionObra.total)}}</td>
                                                        <td class="fs-6" style="width: 200px;">
                                                            <div class="input-group mb-3">                                                                
                                                                <span class="input-group-text" id="dollar-sing">$</span>
                                                                <input type="text" class="form-control" id="adeudoInput" aria-describedby="adeudo" v-model="presionObra.adeudo">
                                                                <button class="btn btn-secondary" type="button" @click="openWinPorcentaje(index,indice)" id="button-addon1">%</button>
                                                            </div>
                                                        </td>
                                                        <td :class="presionObra.atrClass" :style="presionObra.strStyle">
                                                            <div>
                                                                <textarea v-model="presionObra.Observaciones" class="form-control" id="adeudoInput" placeholder="Escribe tu Comentario aquí"></textarea>
                                                            </div>
                                                        </td>
                                                        <td>{{presionObra.formaPago}}</td>                
                                                    </tr>
                                                </tbody>
                                                <tfoot class="table-dark">
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col d-flex justify-content-center mb-3">
                                            <button type="button" class="btn btn-primary fw-bold text-white" @click="guardarCambios(obra.Presion_Obra)">
                                                Guardar Cambios
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--scripts de bootstrap, poppers y jquery-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.min.js"></script>

    <!-- scripts de vue.js-->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <!--Script de axios-->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!--esta es la llamada cdn de datatable-->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/all_presiones.js"></script>
    <script src="../assets/js/layout_sidebar.js"></script>
</body>

</html>


