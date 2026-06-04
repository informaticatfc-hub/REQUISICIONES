<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);
tf_require_direction_access($__pdo, 'Acceso restringido a usuarios con acceso directivo.');

$usuario_sesion = $__user['user_nameUser'] ?? ($_SESSION['Usuario'] ?? '');
$usuario_nombre = $__user['user_name'] ?? $usuario_sesion;
$usuario_rol = $__user['role']['name'] ?? 'Residente';
$usuario_rolCode = $__user['role']['code'] ?? 'residente';
$usuario_dirAcc = tf_user_has_direction_access($__user) ? 1 : 0;
$usuario_perms = $__user['permissions'] ?? [];
$canPresionesWrite = tf_has_permission('presiones.authorize', $__user)
    || in_array($usuario_rolCode, ['admin', 'director', 'desarrollador'], true)
    || $usuario_dirAcc === 1;

$tf_page_title = 'Presiones de todas las obras';
$tf_active_nav = 'direccion';
$tf_breadcrumb = [
    ['Inicio', './index.php'],
    ['Menu Direccion', './direccion.php'],
    ['Presiones de todas las obras', '']
];
$tf_user = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = tf_user_has_direction_access($__user);
$tf_show_admin = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar = true;
$tf_user_id_js = (string)$usuario_sesion;

$tf_extra_head = <<<'HTML'
<style>
    [x-cloak] { display: none !important; }

    .formula-bar {
        background: var(--tf-surface-2);
        border: 1px solid var(--tf-border);
        border-radius: 10px;
        padding: 10px;
    }

    .formula-cell {
        min-width: 220px;
    }

    .formula-help {
        font-size: 0.75rem;
        color: var(--tf-text-soft);
    }

    .totals-mini-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .totals-mini-card {
        background: var(--tf-surface);
        border: 1px solid var(--tf-border);
        border-radius: 10px;
        padding: 10px;
    }

    .totals-mini-card .lbl {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--tf-text-soft);
    }

    .totals-mini-card .val {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--tf-text);
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
        color: var(--tf-text-soft);
        background: var(--tf-surface);
        border: 1px solid var(--tf-border-strong);
        border-radius: 8px;
        padding: 8px 10px;
        text-align: center;
    }

    .excel-formula-input {
        min-height: 36px;
        border-radius: 8px;
        border: 1px solid var(--tf-border-strong);
    }

    .excel-table-wrap {
        border: 1px solid var(--tf-border-strong);
        border-radius: 10px;
        background: var(--tf-surface);
        overflow: auto;
    }

    .excel-grid {
        border-collapse: collapse !important;
        min-width: 1300px;
        background: var(--tf-surface);
    }

    .excel-grid thead th {
        position: sticky;
        top: 0;
        background: var(--tf-surface-2) !important;
        color: var(--tf-text-soft) !important;
        border: 1px solid var(--tf-border-strong) !important;
        font-size: 0.75rem;
    }

    .excel-grid tbody td {
        border: 1px solid var(--tf-border) !important;
        background: var(--tf-surface) !important;
        padding: 6px 8px !important;
        vertical-align: middle;
        color: var(--tf-text) !important;
    }

    .excel-grid tbody tr:hover td {
        background: var(--tf-surface-2) !important;
    }

    .excel-cell-input {
        border: 1px solid transparent;
        border-radius: 6px;
        background: var(--tf-surface);
        min-height: 32px;
        width: 100%;
        padding: 4px 6px;
        font-size: 0.84rem;
    }

    .excel-cell-input:focus {
        outline: none;
        border-color: var(--tf-brand-600);
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
        background: rgba(217, 119, 6, 0.12) !important;
    }

    .excel-grid tbody tr.row-dirty:hover td {
        background: rgba(217, 119, 6, 0.18) !important;
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
        background: var(--tf-brand-600);
        color: #fff;
    }

    .director-export-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin: 14px 0 18px;
    }

    .director-export-bar .btn {
        min-height: 36px;
    }
</style>
HTML;

$tf_extra_scripts = <<<'HTML'
<script src="../assets/lib/exceljs/exceljs.min.js"></script>
<script src="../assets/lib/xlsx/xlsx.full.min.js"></script>
<script>window.TF_ALL_PRESIONES_PERMS = { canPresionesWrite: CAN_PRESIONES_WRITE };</script>
<script src="../assets/js/all_presiones.js?v=fase08r"></script>
HTML;
$tf_extra_scripts = str_replace('CAN_PRESIONES_WRITE', $canPresionesWrite ? 'true' : 'false', $tf_extra_scripts);

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
=======
<div id="AppIndex" class="container page-shell overflow-auto page-content" x-data="allPresionesApp()" x-init="init()" x-cloak>
    <div class="page-hdr">
        <div class="page-hdr-left">
            <h2 class="page-title">Presiones Pendientes - Todas las Obras</h2>
            <p class="page-lead">Gestion tipo Excel para autorizacion de pagos. Puedes importar/exportar por obra o de forma masiva; los cambios se guardan hasta confirmar.</p>
            <div class="obras-chip mt-2">
                <span class="obras-chip-dot"></span>
                Vista de Direccion
>>>>>>> Stashed changes
            </div>
        </div>
    </div>

    <!-- ── Tabs de vista ─────────────────────────────────────────── -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='excel'}" x-on:click="setActiveTab('excel')" type="button">
                <i class="bi bi-table"></i> Vista Excel por Obra
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='pendientes'}" x-on:click="setActiveTab('pendientes')" type="button">
                <i class="bi bi-clock-history"></i> Pendientes de Autorización
                <span class="badge bg-warning text-dark ms-1" x-show="pendientesTotal" x-text="pendientesTotal"></span>
            </button>
        </li>
        <!-- F-M2: Tab pendientes de pago (rol finanzas) -->
        <li class="nav-item" x-show="canFinanzasPagar">
            <button class="nav-link" x-bind:class="{active: activeTab==='pendientes_pago'}" x-on:click="setActiveTab('pendientes_pago')" type="button">
                <i class="bi bi-cash-coin"></i> Pendientes de Pago
                <span class="badge bg-danger ms-1" x-show="pendientesPagoTotal" x-text="pendientesPagoTotal"></span>
            </button>
        </li>
    </ul>

    <!-- ── Tab: Vista Excel ──────────────────────────────────────── -->
    <div x-show="activeTab==='excel'">
    <div class="director-export-bar">
        <button class="btn btn-primary" type="button" x-on:click="exportarExcelMasivo()">Exportar todo XLSX</button>
        <button class="btn btn-outline-secondary" type="button" x-on:click="triggerImportExcelGlobal()" x-show="canPresionesWrite">Importar todo XLSX/CSV</button>
        <button class="btn btn-success" type="button" x-on:click="guardarCambiosMasivos()" x-show="canPresionesWrite">Guardar todo</button>
        <span class="small text-muted">Editables en importacion: solo PAGO AUTORIZADO y OBSERVACIONES.</span>
        <input type="file" id="excelImportAllGlobal" accept=".xlsx,.csv" class="d-none" x-on:change="importarExcelMasivo($event)" x-show="canPresionesWrite">
    </div>

    <div class="row mb-5">
        <div class="accordion" id="accordionExample">
            <template x-for="(obra, index) in presiones" :key="index">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button x-bind:class="'accordion-button fw-bold ' + obra.colapse_Atr" type="button" data-bs-toggle="collapse" x-bind:data-bs-target="'#' + 'collapse' + quitarEspacios(obra.Nombre_Obra)" x-bind:aria-expanded="obra.colapse_band" x-bind:aria-controls="'collapse' + quitarEspacios(obra.Nombre_Obra)">
                        Obra: <span x-text="obra.Nombre_Obra"></span>
                    </button>
                </h2>
                <div x-bind:id="'collapse' + quitarEspacios(obra.Nombre_Obra)" x-bind:class="'accordion-collapse collapse ' + obra.colapse_show" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-lg-12 d-flex gap-2 justify-content-lg-end">
                                <span class="chip-count" title="Filas editadas" x-text="(obra.Presion_Obra || []).filter(function(row){ return !!row._dirty; }).length"></span>
                            </div>
                            <div class="col-12">
                                <div class="totals-mini-grid">
                                    <div class="totals-mini-card">
                                        <div class="lbl">Total Propuesto</div>
                                        <div class="val" x-text="formatearMoneda(totalPropuestoObra(obra))"></div>
                                    </div>
<<<<<<< Updated upstream
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
                                                <input type="file" :id="'excelImport'+index" accept=".xlsx,.csv" class="d-none" @change="importarExcelObra($event,index)">
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
=======
                                    <div class="totals-mini-card">
                                        <div class="lbl">Total Autorizado</div>
                                        <div class="val" x-text="formatearMoneda(totalAutorizadoObra(obra))"></div>
>>>>>>> Stashed changes
                                    </div>
                                    <div class="totals-mini-card">
                                        <div class="lbl">Diferencia</div>
                                        <div class="val" x-text="formatearMoneda(totalAutorizadoObra(obra) - totalPropuestoObra(obra))"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col table-responsive">
                                <div class="excel-utility-bar">
                                    <input type="text" class="form-control" placeholder="Filtrar por clave, proveedor, concepto..." x-model="obra.filtro">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" x-bind:id="'soloEditados'+index" x-model="obra.soloEditados">
                                        <label class="form-check-label" x-bind:for="'soloEditados'+index">Solo editados</label>
                                    </div>
                                    <button class="btn btn-success" type="button" x-on:click="autorizarTodoObra(index)" x-show="canPresionesWrite">Autorizar 100%</button>
                                    <button class="btn btn-warning" type="button" x-on:click="aplicarDescuentoObra(index)" x-show="canPresionesWrite">-10% masivo</button>
                                    <button class="btn btn-danger" type="button" x-on:click="rechazarTodoObra(index)" x-show="canPresionesWrite">Rechazar todo</button>
                                    <button class="btn btn-secondary" type="button" x-on:click="restaurarCambiosObra(index)" x-show="canPresionesWrite">Restaurar</button>
                                    <button class="btn btn-secondary" type="button" x-on:click="exportarCsvObra(index)">Exportar CSV</button>
                                    <button class="btn btn-secondary" type="button" x-on:click="exportarExcelObra(index)">Exportar XLSX</button>
                                    <button class="btn btn-secondary" type="button" x-on:click="triggerImportExcel(index)" x-show="canPresionesWrite">Importar XLSX/CSV</button>
                                    <input type="file" x-bind:id="'excelImport'+index" accept=".xlsx,.csv" class="d-none" x-on:change="importarExcelObra($event,index)" x-show="canPresionesWrite">
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
                                                <th scope="col" class="fs-6">OBSERVACIONES</th>
                                                <th scope="col" class="fs-6">FORMA DE PAGO</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-light" id="Tabla_Items">
                                            <template x-for="(presionObra,indice) in obra.Presion_Obra" :key="presionObra.id_hoja + '-' + indice">
                                            <tr class="my-3" x-show="filaVisible(obra,presionObra)" x-bind:class="{'row-dirty': presionObra._dirty}">
                                                <td x-text="presionObra.clave"></td>
                                                <td class="fs-6" x-text="presionObra.NumReq"></td>
                                                <td class="fs-6" x-text="presionObra.proveedor"></td>
                                                <td class="fs-6" x-text="presionObra.concepto"></td>
                                                <td class="fs-6" x-text="formatearMoneda(presionObra.total)"></td>
                                                <td class="fs-6" style="width: 200px;">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="dollar-sing">$</span>
                                                        <input type="text" class="form-control excel-cell-input" aria-describedby="adeudo" x-model="presionObra.adeudo" x-on:focus="selectCell(index,indice,'AUT')" x-on:input="onRowInput(index,indice,presionObra)" x-on:blur="normalizarAdeudo(index,indice)" x-on:paste="pegarRangoAdeudo($event,index,indice)" x-on:keydown="onCellKeydown($event,index,indice)" x-bind:readonly="!canPresionesWrite">
                                                        <button class="btn btn-secondary" type="button" x-on:click="openWinPorcentaje(index,indice)" id="button-addon1" x-show="canPresionesWrite">%</button>
                                                    </div>
                                                </td>
                                                <td x-bind:class="presionObra.atrClass" x-bind:style="presionObra.strStyle">
                                                    <div>
                                                        <textarea x-model="presionObra.Observaciones" class="form-control excel-cell-input excel-cell-textarea" placeholder="Escribe tu Comentario aquí" x-on:input="onRowInput(index,indice,presionObra)" x-bind:readonly="!canPresionesWrite"></textarea>
                                                    </div>
                                                </td>
                                                <td x-text="presionObra.formaPago"></td>
                                            </tr>
                                            </template>
                                        </tbody>
                                        <tfoot class="table-dark"></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col d-flex justify-content-center mb-3">
                                <button type="button" class="btn btn-primary fw-bold text-white" x-on:click="guardarCambios(obra.Presion_Obra)" x-show="canPresionesWrite">
                                    Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </div>
    </div><!-- /tab excel -->

    <!-- ── Tab: Pendientes de autorización ──────────────────────── -->
    <div x-show="activeTab==='pendientes'" x-cloak>
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-hourglass-split me-1 text-warning"></i> Presiones pendientes de autorización</span>
                <small class="text-muted">Ordenadas de más antigua a más reciente · <span x-text="pendientesTotal"></span> registros</small>
            </div>
            <div class="p-3 border-bottom">
                <input type="search"
                       class="form-control form-control-sm"
                       style="max-width: 360px;"
                       placeholder="Buscar por obra, clave, requisición, proveedor o concepto..."
                       x-model="pendientesSearch"
                       x-on:input="onPendientesSearchInput">
            </div>
            <div class="card-body p-0" style="overflow-x:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Obra</th>
                            <th>Clave</th>
                            <th>N° Req</th>
                            <th>Proveedor</th>
                            <th>Concepto</th>
                            <th>Adeudo</th>
                            <th>Fecha Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in pendientesRows" :key="idx">
                        <tr>
                            <td><span class="badge bg-secondary" x-text="row.Nombre_Obra"></span></td>
                            <td><code class="small" x-text="row.clave"></code></td>
                            <td x-text="row.NumReq"></td>
                            <td x-text="row.proveedor"></td>
                            <td class="small" x-text="row.concepto"></td>
                            <td class="text-end fw-semibold" x-text="formatearMoneda(row.total)"></td>
                            <td class="small" x-text="row.fechaPago || '—'"></td>
                        </tr>
                        </template>
                        <tr x-show="!pendientesRows.length && !pendientesLoading">
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                Sin presiones pendientes de autorización
                            </td>
                        </tr>
                        <tr x-show="pendientesLoading">
                            <td colspan="7" class="text-center text-muted py-4">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">Página <span x-text="pendientesPage"></span> de <span x-text="pendientesPages"></span></small>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPage(1)" x-bind:disabled="pendientesPage <= 1">Primera</button>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPage(pendientesPage-1)" x-bind:disabled="pendientesPage <= 1">Anterior</button>
                    <template x-for="p in pendientesPageRange" :key="'pend-page-'+p">
                    <button
                            class="btn btn-sm"
                            x-bind:class="p === pendientesPage ? 'btn-primary' : 'btn-outline-secondary'"
                            x-on:click="goPendientesPage(p)" x-text="p"></button>
                    </template>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPage(pendientesPage+1)" x-bind:disabled="pendientesPage >= pendientesPages">Siguiente</button>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPage(pendientesPages)" x-bind:disabled="pendientesPage >= pendientesPages">Última</button>
                </div>
            </div>
        </div>
    </div><!-- /tab pendientes -->

<<<<<<< Updated upstream
    <!--Script de axios-->
    <script src="../assets/lib/axios/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/all_presiones.js?v=fase07f"></script>
</body>

</html>
=======
    <!-- F-M2: Tab pendientes de pago -->
    <div x-show="activeTab==='pendientes_pago'" x-cloak>
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold"><i class="bi bi-cash-coin me-1 text-danger"></i> Hojas autorizadas — Pendientes de pago</span>
                <input type="search" class="form-control form-control-sm" style="max-width:360px;"
                       placeholder="Buscar por obra, clave, proveedor o concepto…"
                       x-model="pendientesPagoSearch"
                       x-on:input="onPendientesPagoSearchInput">
            </div>
            <div class="card-body p-0" style="overflow-x:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Obra</th>
                            <th>Clave</th>
                            <th>N° Req / Hoja</th>
                            <th>Proveedor</th>
                            <th>Concepto</th>
                            <th>Adeudo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in pendientesPagoRows" :key="'pp-'+row.hojaRequisicion_id">
                        <tr>
                            <td><span class="badge bg-secondary" x-text="row.obra_nombre"></span></td>
                            <td><code class="small" x-text="row.clave"></code></td>
                            <td x-text="(row.numero_req || '') + ' — Hoja ' + row.hojaRequisicion_numero"></td>
                            <td>
                                <div x-text="row.proveedor"></div>
                                <small class="text-muted" x-text="row.proveedor_clabe ? 'CLABE: ' + row.proveedor_clabe : ''"></small>
                            </td>
                            <td class="small" x-text="row.concepto"></td>
                            <td class="text-end fw-semibold text-success" x-text="'$' + Number(row.hojarequisicion_adeudo || 0).toLocaleString('es-MX', {minimumFractionDigits:2})"></td>
                            <td>
                                <button class="btn btn-sm btn-success" x-on:click="abrirModalPago(row)" title="Registrar pago">
                                    <i class="bi bi-cash-coin"></i> Pagar
                                </button>
                            </td>
                        </tr>
                        </template>
                        <tr x-show="!pendientesPagoRows.length && !pendientesPagoLoading">
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                Sin hojas pendientes de pago
                            </td>
                        </tr>
                        <tr x-show="pendientesPagoLoading">
                            <td colspan="7" class="text-center text-muted py-4">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">Página <span x-text="pendientesPagoPage"></span> de <span x-text="pendientesPagoPages"></span></small>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPagoPage(1)" x-bind:disabled="pendientesPagoPage <= 1">Primera</button>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPagoPage(pendientesPagoPage-1)" x-bind:disabled="pendientesPagoPage <= 1">Anterior</button>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPagoPage(pendientesPagoPage+1)" x-bind:disabled="pendientesPagoPage >= pendientesPagoPages">Siguiente</button>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="goPendientesPagoPage(pendientesPagoPages)" x-bind:disabled="pendientesPagoPage >= pendientesPagoPages">Última</button>
                </div>
            </div>
        </div>
    </div><!-- /tab pendientes_pago -->
>>>>>>> Stashed changes

</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/lib/exceljs/exceljs.min.js"></script>'
    . '<script src="../assets/lib/xlsx/xlsx.full.min.js"></script>'
    . '<script src="../assets/js/all_presiones.js?v=fase08r"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
