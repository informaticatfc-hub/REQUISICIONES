<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);
$__roleCode = $__user['role']['code'] ?? '';
$__dirAcc = (int)($__user['user_directionAcess'] ?? 0);

if (!in_array($__roleCode, ['director', 'admin'], true) && $__dirAcc !== 1) {
    header('Location: ./index.php');
    exit;
}
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
    <style>
        .director-top-layout .app-sidebar { display: none !important; }
        .director-top-layout .app-main { left: 0 !important; }
        .director-top-layout .director-shortcuts {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: calc(var(--topbar-h) + 42px);
            z-index: 89;
        }
        .director-top-layout .director-shortcuts .btn {
            min-height: 34px;
            font-size: 0.8rem;
            padding: 0.35rem 0.8rem;
        }
        .formula-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
        }
        .formula-cell { min-width: 220px; }
        .formula-help { font-size: 0.75rem; color: #475569; }
        .totals-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .totals-mini-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
        }
        .totals-mini-card .lbl {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
        }
        .totals-mini-card .val {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }
        .excel-toolbar {
            display: grid;
            grid-template-columns: 170px 1fr auto;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .excel-name-box {
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .excel-formula-input {
            min-height: 36px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
        .excel-table-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            overflow: auto;
        }
        .excel-grid {
            border-collapse: collapse !important;
            min-width: 1300px;
            background: #fff;
        }
        .excel-grid thead th {
            position: sticky;
            top: 0;
            background: #eef2ff !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 0.75rem;
        }
        .excel-grid tbody td {
            border: 1px solid #e2e8f0 !important;
            background: #fff !important;
            padding: 6px 8px !important;
            vertical-align: middle;
        }
        .excel-grid tbody tr:hover td {
            background: #f8fafc !important;
        }
        .excel-cell-input {
            border: 1px solid transparent;
            border-radius: 6px;
            background: #fff;
            min-height: 32px;
            width: 100%;
            padding: 4px 6px;
            font-size: 0.84rem;
        }
        .excel-cell-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.16);
        }
        .excel-cell-textarea {
            min-height: 56px;
            resize: vertical;
        }
        .excel-utility-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .excel-utility-bar .form-control {
            max-width: 260px;
            min-height: 34px;
        }
        .excel-utility-bar .form-check {
            margin-left: 6px;
            margin-right: 4px;
        }
        .excel-grid tbody tr.row-dirty td {
            background: #fff7ed !important;
        }
        .excel-grid tbody tr.row-dirty:hover td {
            background: #ffedd5 !important;
        }
        .chip-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            border-radius: 999px;
            padding: 0 8px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #1d4ed8;
            color: #fff;
        }
    </style>
    <title>Presiones de Todas las Obras</title>
</head>

<body class="app-layout director-top-layout">
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
            <!--Navbar unificada-->
            <?php include __DIR__ . '/../includes/legacy_navbar.php'; ?>
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
                        <p class="page-lead">Gestion tipo Excel para autorizacion de pagos. Puedes editar celdas y tambien importar o exportar archivos Excel por obra.</p>
                        <div class="obras-chip mt-2">
                            <span class="obras-chip-dot"></span>
                            Vista de Direccion
                        </div>
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
                                    <div class="row g-2 align-items-end mb-3">
                                        <div class="col-lg-12 d-flex gap-2 justify-content-lg-end">
                                            <span class="chip-count" title="Filas editadas">{{(obra.Presion_Obra || []).filter(function(row){ return !!row._dirty; }).length}}</span>
                                            <button type="button" class="btn btn-secondary" @click="mostrarAyudaRapida()">Ayuda</button>
                                        </div>
                                        <div class="col-12">
                                            <div class="totals-mini-grid">
                                                <div class="totals-mini-card">
                                                    <div class="lbl">Total Propuesto</div>
                                                    <div class="val">{{formatearMoneda(totalPropuestoObra(obra))}}</div>
                                                </div>
                                                <div class="totals-mini-card">
                                                    <div class="lbl">Total Autorizado</div>
                                                    <div class="val">{{formatearMoneda(totalAutorizadoObra(obra))}}</div>
                                                </div>
                                                <div class="totals-mini-card">
                                                    <div class="lbl">Diferencia</div>
                                                    <div class="val">{{formatearMoneda(totalAutorizadoObra(obra) - totalPropuestoObra(obra))}}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col table-responsive">
                                            <div class="excel-utility-bar">
                                                <input type="text" class="form-control" placeholder="Filtrar por clave, proveedor, concepto..." v-model="obra.filtro">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :id="'soloEditados'+index" v-model="obra.soloEditados">
                                                    <label class="form-check-label" :for="'soloEditados'+index">Solo editados</label>
                                                </div>
                                                <button class="btn btn-success" type="button" @click="autorizarTodoObra(index)">Autorizar 100%</button>
                                                <button class="btn btn-warning" type="button" @click="aplicarDescuentoObra(index)">-10% masivo</button>
                                                <button class="btn btn-danger" type="button" @click="rechazarTodoObra(index)">Rechazar todo</button>
                                                <button class="btn btn-secondary" type="button" @click="restaurarCambiosObra(index)">Restaurar</button>
                                                <button class="btn btn-secondary" type="button" @click="exportarCsvObra(index)">Exportar CSV</button>
                                                <button class="btn btn-secondary" type="button" @click="exportarExcelObra(index)">Exportar Excel</button>
                                                <button class="btn btn-secondary" type="button" @click="triggerImportExcel(index)">Importar Excel</button>
                                                <input type="file" :id="'excelImport'+index" accept=".xlsx,.xls" class="d-none" @change="importarExcelObra($event,index)">
                                            </div>
                                            <div class="excel-toolbar">
                                                <div class="excel-name-box">{{selectedCellLabel || 'Sin celda seleccionada'}}</div>
                                                <input type="text" class="form-control excel-formula-input" placeholder="Barra de formula: =SUM(ADEUDO,100), =IF(AUT>ADEUDO,ADEUDO,AUT)" v-model="formulaBar" @keyup.enter="aplicarFormulaBarra(index)">
                                                <button class="btn btn-primary" type="button" @click="aplicarFormulaBarra(index)">Aplicar</button>
                                            </div>
                                            <div class="excel-table-wrap">
                                            <table class="table table-prof excel-grid align-middle w-100">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th scope="col" class="fs-6">CLAVE</th>
                                                        <th scope="col" class="fs-6">N° DE REQUISICION</th>
                                                        <th scope="col" class="fs-6">PROVEEDOR</th>
                                                        <th scope="col" class="fs-6">CONCEPTO</th>
                                                        <th scope="col" class="fs-6">ADEUDO</th>
                                                        <th scope="col" class="fs-6">PAGO AUTORIZADO</th>
                                                        <th scope="col" class="fs-6">FORMULA</th>
                                                        <th scope="col" class="fs-6">OBSERVACIONES</th>
                                                        <th scope="col" class="fs-6">FORMA DE PAGO</th>                                                    
                                                    </tr>
                                                </thead>
                                                <tbody class="table-light" id="Tabla_Items">
                                                    <tr class="my-3" v-for="(presionObra,indice) of obra.Presion_Obra" v-show="filaVisible(obra,presionObra)" :class="{'row-dirty': presionObra._dirty}">
                                                        <td>{{presionObra.clave}}</td>
                                                        <td class="fs-6">{{presionObra.NumReq}}</td>
                                                        <td class="fs-6">{{presionObra.proveedor}}</td>
                                                        <td class="fs-6">{{presionObra.concepto}}</td>
                                                        <td class="fs-6">{{formatearMoneda(presionObra.total)}}</td>
                                                        <td class="fs-6" style="width: 200px;">
                                                            <div class="input-group mb-3">
                                                                <span class="input-group-text" id="dollar-sing">$</span>
                                                                <input type="text" class="form-control excel-cell-input" aria-describedby="adeudo" v-model="presionObra.adeudo" @focus="selectCell(index,indice,'AUT')" @input="onRowInput(index,indice,presionObra)" @blur="normalizarAdeudo(index,indice)" @paste="pegarRangoAdeudo($event,index,indice)" @keydown="onCellKeydown($event,index,indice)">
                                                                <button class="btn btn-secondary" type="button" @click="openWinPorcentaje(index,indice)" id="button-addon1">%</button>
                                                            </div>
                                                        </td>
                                                        <td class="formula-cell">
                                                            <div class="input-group mb-2">
                                                                <span class="input-group-text">fx</span>
                                                                <input type="text" class="form-control excel-cell-input" placeholder="=MIN(ADEUDO,5000)" v-model="presionObra.formula" @focus="selectCell(index,indice,'FX')" @input="onRowInput(index,indice,presionObra)" @keyup.enter="aplicarFormulaFila(index,indice)">
                                                            </div>
                                                            <button class="btn btn-sm btn-primary" type="button" @click="aplicarFormulaFila(index,indice)">Aplicar</button>
                                                        </td>
                                                        <td :class="presionObra.atrClass" :style="presionObra.strStyle">
                                                            <div>
                                                                <textarea v-model="presionObra.Observaciones" class="form-control excel-cell-input excel-cell-textarea" placeholder="Escribe tu Comentario aquí" @input="onRowInput(index,indice,presionObra)"></textarea>
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
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- scripts de vue.js-->
    <script src="../assets/lib/vue/vue.min.js"></script>

    <!--Script de axios-->
    <script src="../assets/lib/axios/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/all_presiones.js?v=fase07e"></script>
    <script src="../assets/js/layout_sidebar.js?v=fase07b"></script>
</body>

</html>


