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
    <title>Agregar Proveedor</title>
</head>

<body class="app-layout">
    <div id="AppNewProv">
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
                <li class="breadcrumb-item"><a href="./menu_catalago.php"><span>Menu Catalago</span></a></li>
                <li class="breadcrumb-item"><a href="./proveedores.php"><span>Catalago de Proveedores</span></a></li>
                <li class="breadcrumb-item active" aria-current="page"><span>Agregar Proveedor</span></li>
            </ol>
        </nav>
        <div class="container page-shell overflow-auto page-content">
            <div class="page-hdr">
                <div class="page-hdr-left">
                    <h2 class="page-title">Agregar Proveedor</h2>
                    <p class="page-lead">Registra un nuevo proveedor con sus datos fiscales y bancarios.</p>
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="nameProv" class="form-label">Nombre del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="nameProv" placeholder="Ingresa informacion..." v-model="nombre_prov" require>
                    <div class="invalid-feedback">
                        Por favor ingresa el nombre del proveedor.
                    </div>
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="rfcProv" class="form-label">RFC del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="rfcProv" placeholder="Ingresa informacion..." v-model="rfc_prov" require>
                </div>
                <div class="col">
                    <label for="clabeProv" class="form-label">Clave Bancaria del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="clabeProv" placeholder="Ingresa informacion..." v-model="clabe_prov" require>
                </div>
                <div class="col">
                    <label for="cuentaProv" class="form-label">Cuenta Bancaria del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="cuentaProv" placeholder="Ingresa informacion..." v-model="cuenta_prov" require>
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="rfcProv" class="form-label">Numero de Tarjeta</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="rfcProv" placeholder="Ingresa informacion..." v-model="tarjeta_prov">
                </div>
                <div class="col">
                    <label for="clabeProv" class="form-label">Numero de Referencia del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="clabeProv" placeholder="Ingresa informacion..." v-model="referencia_prov">
                </div>
                <div class="col">
                    <label for="cuentaProv" class="form-label">Tipo de Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="cuentaProv" placeholder="Ingresa informacion..." v-model="tipo_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="bankProv" class="form-label">Banco del Proveedor</label>
                    <select class="form-select border border-primary-subtle border-2" aria-label="Default select example" v-model="selected_Banco">
                        <option value="">Selecciona Banco</option>
                        <option v-for="(banco,indice) of bancos" :value="banco.banco_nombreComercial">{{banco.banco_id}}- {{banco.banco_nombreComercial}}</option>
                    </select>
                </div>
                <div class="col">
                    <label for="sucursalProv" class="form-label">Sucursal Bancaria del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="sucursalProv" placeholder="Ingresa informacion..." v-model="suc_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="adressProv" class="form-label">Direccion del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="adressProv" placeholder="Ingresa informacion..." v-model="direccion_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="rfcProv" class="form-label">Telefono del Proveedor</label>
                    <input type="text" class="form-control border border-primary-subtle border-2" id="rfcProv" placeholder="Ingresa informacion..." v-model="tel_prov">
                </div>
                <div class="col">
                    <label for="clabeProv" class="form-label">Correo Electronico del Proveedor</label>
                    <input type="email" class="form-control border border-primary-subtle border-2" id="clabeProv" placeholder="Ingresa la Clabe Bancaria del Proveedor" v-model="email_prov">
                </div>
            </div>
            <div class="row w-100 mt-5 mb-5 mx-auto">
                <div class="col px-0 d-grid gap-2">
                    <button class="btn btn-success" @click="agregarProveedor" title="Agregar Proveedor">
                        <span class="text-center">Agregar Proveedor</span>
                    </button>
                </div>
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

    <!-- scripts constume-->
    <script src="../assets/js/agregar_proveedor.js"></script>
    <script src="../assets/js/layout_sidebar.js"></script>
</body>

</html>


