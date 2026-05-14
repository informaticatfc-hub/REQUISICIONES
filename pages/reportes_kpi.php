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
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .director-top-layout .app-sidebar { display: none !important; }
        .director-top-layout .app-main { left: 0 !important; }
        .director-shortcuts {
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
        .kpi-filter-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: var(--shadow-xs);
        }
        .kpi-table-wrap {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: auto;
            background: #fff;
            box-shadow: var(--shadow-xs);
        }
        .kpi-table {
            min-width: 980px;
            margin: 0;
        }
        .kpi-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #eef2ff !important;
            color: #1e293b !important;
            font-size: 0.74rem;
            border-bottom: 1px solid #cbd5e1 !important;
        }
        .kpi-table td {
            font-size: 0.84rem;
            vertical-align: middle;
        }
    </style>
    <title>Reportes KPI - Direccion</title>
</head>

<body class="app-layout director-top-layout">
    <div id="AppKpiDireccion">
        <div class="d-flex flex-column flex-shrink-0 h-100 position-fixed top-0 end-0 app-main">
            <?php include __DIR__ . '/../includes/legacy_navbar.php'; ?>
            <nav class="nav shadow-sm d-flex align-items-center" id="navtab" aria-label="breadcrumb" aria-current="page">
                <ol class="breadcrumb py-2 px-3 my-0">
                    <li class="breadcrumb-item"><a href="./index.php"><img src="../images/icons/home.svg" alt="home" height="24" width="24"><span>Inicio</span></a></li>
                    <li class="breadcrumb-item"><a href="./direccion.php"><span>Menu Direccion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Reportes KPI</span></li>
                </ol>
            </nav>

            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Reportes KPI - Direccion</h2>
                        <p class="page-lead">Desglose profesional de gastos por fecha. Selecciona obra y rango para consultar.</p>
                    </div>
                </div>

                <div class="kpi-filter-box">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Obra</label>
                            <select class="form-select" v-model="filtros.obraId">
                                <option value="">Selecciona una obra</option>
                                <option v-for="obra in obras" :value="String(obra.obras_id)">{{obra.obras_nombre}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input class="form-control" type="date" v-model="filtros.desde">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input class="form-control" type="date" v-model="filtros.hasta">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" type="button" @click="aplicarFiltros">Aplicar</button>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                            <button class="btn btn-secondary" type="button" @click="exportarCsv">CSV</button>
                            <button class="btn btn-secondary" type="button" @click="exportarPdf">PDF</button>
                        </div>
                    </div>
                </div>

                <div v-if="consultaAplicada" class="ops-hero-grid">
                    <div class="quick-tile">
                        <span class="quick-tile-label">Presiones</span>
                        <span class="quick-tile-value">{{kpi.presiones}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total Gastos</span>
                        <span class="quick-tile-value">{{money(kpi.total)}}</span>
                    </div>
                    <div class="quick-tile">
                        <span class="quick-tile-label">Total Adeudo</span>
                        <span class="quick-tile-value">{{money(kpi.adeudo)}}</span>
                    </div>
                </div>
                <div v-if="consultaAplicada" class="kpi-table-wrap">
                    <table class="table table-hover align-middle kpi-table">
                        <thead>
                            <tr>
                                <th>OBRA</th>
                                <th>FECHA</th>
                                <th>PRESIONES</th>
                                <th>HOJAS LIGADAS</th>
                                <th>TOTAL GASTOS</th>
                                <th>TOTAL ADEUDO</th>
                                <th>TICKET PROM.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in reporteAgrupado" :key="r.key">
                                <td>{{r.obra}}</td>
                                <td>{{r.fecha}}</td>
                                <td>{{r.presiones}}</td>
                                <td>{{r.hojas}}</td>
                                <td>{{money(r.total)}}</td>
                                <td>{{money(r.adeudo)}}</td>
                                <td>{{money(r.ticket)}}</td>
                            </tr>
                            <tr v-if="!reporteAgrupado.length">
                                <td colspan="7" class="text-center text-muted py-4">Sin registros para los filtros seleccionados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="kpi-filter-box text-center text-muted">
                    Selecciona una obra, define fechas si aplica y presiona <strong>Aplicar</strong> para mostrar el desglose.
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/lib/vue/vue.min.js"></script>
    <script src="../assets/lib/axios/axios.min.js"></script>
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/reportes_kpi.js?v=fase07e"></script>
</body>

</html>
