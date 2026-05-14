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
                    <li class="breadcrumb-item"><a href="./presiones.php"><span>Presion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Nueva Requisicion</span></li>
                </ol>
            </nav>
            <div class="container page-shell page-form-shell">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Nueva Requisicion</h2>
                        <p class="page-lead">Completa el formulario para crear una nueva requisicion de compra.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Datos Empresa</h3>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-7">
                        <label for="Nombre">Nombre de la Empresa</label>
                        <input type="text" class="form-control" id="Nombre" name="Nombre" v-model="Emisor_Nombre" readonly>
                    </div>
                    <div class="col-5">
                        <label for="RFC">RFC</label>
                        <input type="text" class="form-control" id="RFC" name="RFC" v-model="Emisor_RFC" readonly>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-12">
                        <label for="Direccion">Direccion de la Empresa</label>
                        <input type="text" class="form-control" id="Direccion" name="Direccion" v-model="Emisor_Adress" readonly>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-4">
                        <label for="Telefono">Telefono</label>
                        <input type="text" class="form-control" id="Telefono" name="Telefono" v-model="Emisor_Phone" readonly>
                    </div>
                    <div class="col-4">
                        <label for="Fax">Fax</label>
                        <input type="text" class="form-control" id="Fax" name="Fax" v-model="Emisor_Fax" readonly>
                    </div>
                    <div class="col-4">
                        <label for="ZipCode">Codigo Postal</label>
                        <input type="text" class="form-control" id="ZipCode" name="ZipCode" v-model="Emisor_ZipCode" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Datos del Proveedor</h3>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-7">
                        <label for="NombreProv">Nombre del Proveedor</label>
                        <select class="form-select" aria-label="Default select example" v-model="selected_Provedor">
                            <option v-for="(proveedor,indice) of proveedores" :value="proveedor.proveedor_id">{{proveedor.proveedor_id}}- {{proveedor.proveedor_nombre}}</option>
                        </select>
                    </div>
                    <div class="col-1 d-flex align-items-end justify-content-center">
                        <button class="btn btn-danger" title="Validar Proveedor" @click="validarProv(selected_Provedor)">
                            <span>Validar</span>
                        </button>
                    </div>
                    <div class="col-4">
                        <label for="RFCProv">RFC del Proveedor</label>
                        <input type="text" class="form-control" id="RFCProv" name="RFCProv" v-model="Prov_RFC" readonly>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-3">
                        <label for="CLABE">CLABE Bancaria</label>
                        <input type="text" class="form-control" id="CLABE" name="CLABE" v-model="Prov_Clabe" readonly>
                    </div>
                    <div class="col-3">
                        <label for="CuentaBank">Cuenta Bancaria</label>
                        <input type="text" class="form-control" id="CuentaBank" name="CuentaBank" v-model="Prov_Cuenta" readonly>
                    </div>
                    <div class="col-3">
                        <label for="ReferenciaBank">Referencia Bancaria</label>
                        <input type="text" class="form-control" id="ReferenciaBank" name="ReferenciaBank" v-model="Prov_RefBank" readonly>
                    </div>
                    <div class="col-3">
                        <label for="Bank">Banco</label>
                        <input type="text" class="form-control" id="Bank" name="Bank" v-model="Prov_Bank" readonly>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-6">
                        <label for="Email">Correo Electronico</label>
                        <input type="text" class="form-control" id="Email" name="Email" v-model="Prov_Email" readonly>
                    </div>
                    <div class="col-3">
                        <label for="Telefono">Telefono</label>
                        <input type="text" class="form-control" id="Telefono" name="Telefono" v-model="Prov_Phone" readonly>
                    </div>
                    <div class="col-3">
                        <label for="SuncBank">Sucursal del Banco</label>
                        <input type="text" class="form-control" id="SuncBank" name="SuncBank" v-model="Prov_SucBank" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <h3 class="m-2 mt-5 mb-3 Subtitulo section-title">Items de la Requisicion</h3>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-1">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="PagoTransfs" @click="pagoTransaccionActivado" checked>
                            <label class="form-check-label" for="PagoTransfs">Pagar por Transferencia</label>
                        </div>
                    </div>
                </div>
                <div class="row form-group mx-0 mt-0 mb-3">
                    <div class="col-12">
                        <table class="table table-prof table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Lote</th>
                                    <th scope="col">Unidad</th>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Cantidad</th>
                                    <th scope="col">Precio Unitario</th>
                                    <th scope="col">IVA</th>
                                    <th scope="col">Retenciones</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody class="table-light" id="Tabla_Items">
                                <!-- Formato HTML de la tabla
                                <tr class="my-3">
                                    <th scope="row">1</th>
                                    <td>Diseño</td>
                                    <td>Servicio de Control de Calidad</td>
                                    <td>1</td>
                                    <td>$80,000.00</td>
                                    <td>$1.16</td>
                                    <td>$62,800.00</td>
                                </tr>-->
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="7" class="table-active text-end">Total: </th>
                                    <td>${{Total_Pagar_Mostrar}}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="row w-100 mt-0 mb-3 mx-auto">
                        <div class="col px-0 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary" @click="showModalAddItem" title="Agregar el Item">
                                <span class="text-center">Agregar Item</span>
                            </button>
                        </div>
                    </div>
                    <div class="row form-group mx-0 mt-0 mb-3">
                        <div class="col-12 px-0">
                            <label for="Observ">Observaciones Adicionales</label>
                            <textarea class="form-control" id="Observ" rows="3" v-model="observaciones"></textarea>
                        </div>
                    </div>
                    <div class="row w-100 mt-0 mb-3 mx-auto">
                        <div class="col px-0 d-flex justify-content-center">
                            <button class="btn btn-success" @click="agregarRequisicion(Date_Req,selected_ClveReq)" title="Agregar Requisicion">
                                <span class="text-center">Crear Requiscion</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--scripts de bootstrap, poppers y jquery-->
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.min.js"></script>

    <!-- scripts de vue.js-->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <!--Script de axios-->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/nueva_requisicion.js"> </script>
    <script src="../assets/js/layout_sidebar.js"></script>
</body>

</html>


