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
    <title>Inicio:: The Fuentes Corporation</title>
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
                    <li class="breadcrumb-item active d-flex align-items-center">
                        <img class="" src="../images/icons/home.svg" alt="user-icon" height="24" width="24">
                        <span>Inicio</span>
                    </li>
                </ol>
            </nav>
            <div class="container page-shell">

                <!-- Page header -->
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Bienvenido, {{NameUser}}</h2>
                        <p class="page-lead">Resumen del espacio de trabajo. Selecciona una obra o modulo para comenzar.</p>
                    </div>
                </div>

                <!-- Stat row -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <img src="../images/icons/obras.svg" alt="obras">
                        </div>
                        <p class="stat-label">Obras activas recientes</p>
                        <p class="stat-value">{{obras.length}}</p>
                        <p class="stat-sub">Mostrando las 12 mas recientes</p>
                    </div>
                    <a class="stat-card green" href="./menu_catalago.php" @click.prevent="irMenuCatalago">
                        <div class="stat-icon">
                            <img src="../images/icons/catalagos.svg" alt="catalogos">
                        </div>
                        <p class="stat-label">Catalogos</p>
                        <p class="stat-value" style="font-size:1.1rem;letter-spacing:-.02em">Proveedores y Bancos</p>
                        <p class="stat-sub">Administrar catalogos del sistema</p>
                    </a>
                    <a class="stat-card yellow" href="./direccion.php" @click.prevent="irDireecion" v-if="this.users.length && this.users[0].user_directionAcess == 1">
                        <div class="stat-icon">
                            <img src="../images/icons/ceo.svg" alt="direccion">
                        </div>
                        <p class="stat-label">Direccion</p>
                        <p class="stat-value" style="font-size:1.1rem;letter-spacing:-.02em">Presiones Pendientes</p>
                        <p class="stat-sub">Autorizar y gestionar presiones</p>
                    </a>
                </div>

                <!-- Obras list -->
                <div class="table-wrapper" v-if="obras.length">
                    <div style="padding:14px 18px 0;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:.875rem;font-weight:600;color:var(--text-primary)">Obras activas recientes</span>
                        <span style="font-size:.78rem;color:var(--text-secondary)">{{obras.length}} total</span>
                    </div>
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr>
                                <th style="background:var(--slate-50);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-secondary);padding:10px 18px;border-bottom:1px solid var(--border)">Nombre de la Obra</th>
                                <th style="background:var(--slate-50);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-secondary);padding:10px 18px;border-bottom:1px solid var(--border);text-align:right">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="obra in obras" style="border-bottom:1px solid rgba(226,232,240,.65)">
                                <td style="padding:12px 18px;font-size:.9rem;font-weight:500;color:var(--text-primary)">{{obra.obras_nombre}}</td>
                                <td style="padding:12px 18px;text-align:right">
                                    <button type="button" class="btn btn-primary" style="padding:.38rem .8rem;font-size:.82rem" @click="irObra(obra.obras_id)">
                                        Abrir obra
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
    <script src="../assets/js/index.js"></script>
    <script src="../assets/js/layout_sidebar.js"></script>
</body>

</html>


