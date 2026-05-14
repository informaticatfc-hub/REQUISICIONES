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
    <title>ITEMS DE LA REQUISICION</title>
</head>

<body class="app-layout">
    <div id="AppItems">
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
            <?php include __DIR__ . '/../includes/legacy_navbar.php'; ?>
            <nav class="nav shadow-sm d-flex align-items-center" id="navtab" aria-label="breadcrumb" aria-current="page">
                <ol class="breadcrumb py-2 px-3 my-0" v-if="this.validate == 'false'">
                    <li class="breadcrumb-item">
                        <a href="./index.php">
                            <img class="" src="../images/icons/home.svg" alt="user-icon" height="24" width="24">
                            <span>Inicio</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><a href="./obras.php"><span>Menu Obras</span></a></li>
                    <li class="breadcrumb-item"><a href="./requisiciones.php"><span>Requisiciones de la Obra</span></a></li>
                    <li class="breadcrumb-item"><a href="./hojas_requisicion.php"><span>Hojas de la Requisicion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>{{this.Numero_Req}} HOJA N° {{this.hojas[0].hojaRequisicion_numero}} </span></li>
                </ol>
                <ol class="breadcrumb py-2 px-3 my-0" v-if="this.validate == 'true'">
                    <li class="breadcrumb-item">
                        <a href="./index.php">
                            <img class="" src="../images/icons/home.svg" alt="user-icon" height="24" width="24">
                            <span>Inicio</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><a href="./obras.php"><span>Menu de Obra: {{obras.length ? obras[0].obras_nombre : ''}}</span></a></li>
                    <li class="breadcrumb-item"><a href="./presiones.php"><span>Presiones de {{obras.length ? obras[0].obras_nombre : ''}}</span></a></li>
                    <li class="breadcrumb-item"><a href="./enlazar_requisiciones.php"><span>Enlazar Requisiciones a la Presion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>{{this.Numero_Req}} HOJA N° {{this.hojas[0].hojaRequisicion_numero}} </span></li>
                </ol>
            </nav>
            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Requisicion {{this.Numero_Req}} &mdash; Hoja {{this.hojas[0].hojaRequisicion_numero}}</h2>
                        <p class="page-lead">Detalle completo de los items, encabezado y observaciones de la requisicion.</p>
                    </div>
                    <div class="page-hdr-right">
                        <button type="button" class="btn btn-success" @click="cambiarProveedor()" v-if="(hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA') && users.length && users[0].user_editReq == 1">Cambiar Proveedor</button>
                        <button type="button" class="btn btn-secondary" @click="cambiarFormaPago(hojas[0].hojaRequisicion_formaPago)" v-if="(hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA') && users.length && users[0].user_editReq == 1">Forma de Pago</button>
                        <button type="button" class="btn btn-danger" @click="imprimirReq">Imprimir</button>
                    </div>
                </div>
                <div class="ops-hero-grid">
                    <div class="quick-tile">
                        <span class="quick-tile-label">Requisicion / Hoja</span>
                        <span class="quick-tile-value small">{{this.Numero_Req}} &mdash; Hoja {{hojas[0].hojaRequisicion_numero}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Estatus</span>
                        <span class="quick-tile-value small">{{hojas[0].hojaRequisicion_estatus}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total</span>
                        <span class="quick-tile-value small">{{formatearMoneda(hojas[0].hojaRequisicion_total, true)}}</span>
                    </div>
                </div>
                <div class="table-wrapper mb-3">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:.875rem;font-weight:600;color:var(--text-primary)">Encabezado &mdash; {{this.Numero_Req}} Hoja {{hojas[0].hojaRequisicion_numero}}</span>
                        <span class="badge bg-primary">{{hojas[0].hojaRequisicion_estatus}}</span>
                    </div>
                    <div style="padding:18px 18px;display:grid;grid-template-columns:1fr 1fr;gap:18px">
                        <div>
                            <p class="section-title">Datos de la Requisicion</p>
                            <div class="detail-row"><span>Clave:</span> <strong>{{this.clve}}</strong></div>
                            <div class="detail-row"><span>Fecha Solicitud:</span> <strong>{{hojas[0].hojaRequisicion_FechaSolicitud}}</strong></div>
                            <p class="section-title">Datos del Emisor</p>
                            <div class="detail-row"><span>Empresa:</span> <strong>{{hojas[0].emisor_nombre}}</strong></div>
                            <div class="detail-row"><span>RFC:</span> <strong>{{hojas[0].emisor_rfc}}</strong></div>
                            <div class="detail-row"><span>Direccion:</span> <strong>{{hojas[0].emisor_direccion}}</strong></div>
                            <div class="detail-row"><span>Telefono:</span> <strong>{{hojas[0].emisor_telefono}}</strong></div>
                            <div class="detail-row"><span>C.P.:</span> <strong>{{hojas[0].emisor_zipCode}}</strong></div>
                        </div>
                        <div>
                            <p class="section-title">Datos del Proveedor</p>
                            <div class="detail-row"><span>Empresa:</span> <strong>{{hojas[0].proveedor_nombre}}</strong></div>
                            <div class="detail-row"><span>RFC:</span> <strong>{{hojas[0].proveedor_rfc}}</strong></div>
                            <div class="detail-row"><span>Banco:</span> <strong>{{hojas[0].proveedor_banco}}</strong></div>
                            <div class="detail-row"><span>Cuenta:</span> <strong>{{hojas[0].proveedor_numeroCuenta}}</strong></div>
                            <div class="detail-row"><span>CLABE:</span> <strong>{{hojas[0].proveedor_clabe}}</strong></div>
                            <div class="detail-row"><span>Sucursal:</span> <strong>{{hojas[0].proveedor_sucursal}}</strong></div>
                            <div class="detail-row"><span>Referencia:</span> <strong>{{hojas[0].presiones_tarjetaBanco}}</strong></div>
                            <div class="detail-row"><span>Email:</span> <strong>{{hojas[0].proveedor_email}}</strong></div>
                            <div class="detail-row"><span>Telefono:</span> <strong>{{hojas[0].proveedor_telefono}}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="row" v-if="(hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA') && users.length && users[0].user_editReq == 1">
                    <div class="col d-flex align-items-end mb-3">
                        <button type="button" class="btn btn-primary ms-auto" @click="agregarItem" id="btnAddItem">
                            <span class="fw-bold text-white">Agregar item a esta requisicion</span>
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                <div class="overflow-auto">
                        <table id="example" class="table table-prof table-hover w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" class="text-center">Unidad</th>
                                    <th scope="col" class="text-center">Producto</th>
                                    <th scope="col" class="text-center">Cantidad</th>
                                    <th scope="col" class="text-center">Precio Unitario</th>
                                    <th scope="col" class="text-center">IVA</th>
                                    <th scope="col" class="text-center">Retenciones</th>
                                    <th scope="col" class="text-center">Subtotal</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody class="table-light" id="Tabla_Items">
                                <tr class="my-3" v-for="(item,indice) of itemsHoja">
                                    <td class="text-center align-middle">{{item.itemRequisicion_unidad}}</td>
                                    <td style="max-width: 150px;;">{{item.itemRequisicion_producto}}</td>
                                    <td class="text-center align-middle">{{formatearMoneda(item.itemRequisicion_cantidad,false)}}</td>
                                    <td class="text-center align-middle">{{formatearMoneda(item.itemRequisicion_precio,true)}}</td>
                                    <td class="text-center align-middle">{{formatearMoneda(item.itemRequisicion_iva,true)}}</td>
                                    <td class="text-center align-middle">{{formatearMoneda(item.itemRequisicion_retenciones,true)}}</td>
                                    <td class="text-center align-middle">{{ formatearMoneda((
                                        Number(item.itemRequisicion_cantidad ?? 0) * Number(item.itemRequisicion_precio ?? 0)
                                        + Number(item.itemRequisicion_iva ?? 0)
                                        - Number(item.itemRequisicion_retenciones ?? 0)
                                        ).toFixed(2), true) }}
                                    </td>
                                    <!--<td><span class="badge bg-danger">Pendiente</span></td>-->
                                    <td class="align-middle">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Basic mixed styles example" v-if="hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA'|| hojas[0].hojaRequisicion_estatus == 'RECHAZADA'">
                                            <button type="button" class="btn btn-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Editar item" @click="editItem(item.itemRequisicion_producto,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_banderaFlete,item.itemRequisicion_banderaFisica,item.itemRequisicion_banderaResico,item.itemRequisicion_banderaISR,item.itemRequisicion_id)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-white" viewBox="0 0 16 16">
                                                    <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                                    <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Eliminar Item" @click="eliminarItem(item.itemRequisicion_id,item.itemRequisicion_cantidad,item.itemRequisicion_precio,item.itemRequisicion_iva,item.itemRequisicion_retenciones)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold text-center">{{ formatearMoneda(Number(hojas[0]?.hojaRequisicion_total ?? 0).toFixed(2), true) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                </div><!-- /overflow-auto -->
                </div><!-- /table-wrapper -->
                <div class="table-wrapper mt-4 mb-3">
                    <div class="card-header">
                        <h5 class="card-title">Comentarios de la Requisicion {{this.Numero_Req}} Hoja Numero {{hojas[0].hojaRequisicion_numero}}</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="hojas[0].hojaRequisicion_estatus == 'LIGADA' || hojas[0].hojaRequisicion_estatus == 'AUTORIZADA' || hojas[0].hojaRequisicion_estatus == 'REVISION' || hojas[0].hojaRequisicion_estatus == 'PAGADA' || (users.length && users[0].user_editReq == 0) ">
                            <div class="row mt-3">
                                <div class="col">
                                    <h6 class="card-subtitle mb-2 text-muted">Comentarios de la Operacion</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <p class="card-subtitle mb-2 text-muted">{{hojas[0].hojaRequisicion_observaciones}}</span></p>
                                </div>
                            </div>
                        </div>
                        <div v-if="(hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA') && users.length && users[0].user_editReq == 1">
                            <div class="row mt-3">
                                <div class="col">
                                    <h6 class="card-subtitle mb-2 text-muted">Comentarios de la Operacion</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <textarea class="form-control" id="comentsValidacion" v-model="hojas[0].hojaRequisicion_observaciones" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div v-if="hojas[0].hojaRequisicion_estatus == 'REVISION' && this.validate == 'true'">
                            <hr class="my-2">
                            <div class="row mt-3">
                                <div class="col">
                                    <h6 class="card-subtitle mb-2 text-muted">Comentarios de Validacion</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <textarea class="form-control" id="comentsValidacion" v-model="hojas[0].hojarequisicion_comentariosValidacion" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div v-if="hojas[0].hojaRequisicion_estatus == 'PENDIENTE'">
                            <hr class="my-2">
                            <div class="row mt-3">
                                <div class="col">
                                    <h6 class="card-subtitle mb-2 text-muted">Comentarios de Validacion</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <p class="card-subtitle mb-2 text-muted">{{hojas[0].hojarequisicion_comentariosValidacion }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div v-if="hojas[0].hojaRequisicion_estatus == 'PAGADA' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA'">
                            <hr class="my-2">
                            <div class="row mt-3">
                                <div class="col">
                                    <h6 class="card-subtitle mb-2 text-muted">Comentarios de Autorizacion</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <p class="card-subtitle mb-2 text-muted">{{hojas[0].hojarequisicion_comentariosAutorizacion}}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /table-wrapper -->
                <div class="d-flex gap-2 justify-content-center mt-4 mb-5">
                    <button class="btn btn-primary" @click="validarRequisicion(hojas[0].hojaRequisicion_observaciones)" v-if="(hojas[0].hojaRequisicion_estatus == 'NUEVO' || hojas[0].hojaRequisicion_estatus == 'PENDIENTE' || hojas[0].hojaRequisicion_estatus == 'RECHAZADA') && users.length && users[0].user_editReq == 1">Solicitar Revision</button>
                    <button class="btn btn-success" @click="asignarAPresion(hojas[0].hojarequisicion_comentariosValidacion, hojas[0].hojaRequisicion_total)" v-if="hojas[0].hojaRequisicion_estatus == 'REVISION' && this.validate == 'true'">Validar Requisicion</button>
                </div>
            </div>
        </div>
    </div>
    <!--scripts de bootstrap, poppers y jquery-->
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!--esta es la llamada cdn de datatable-->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

    <!-- scripts de vue.js-->
    <script src="../assets/lib/vue/vue.min.js"></script>

    <!--Script de axios-->
    <script src="../assets/lib/axios/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!--CDN de la bibloteca JsPDF-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>

    <script src="../assets/js/pdfGenerate.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/item_requisicion.js"></script>
</body>

</html>


