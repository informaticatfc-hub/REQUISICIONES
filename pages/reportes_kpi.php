<?php
include_once '../validarSesion.php';
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
        .executive-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .executive-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-xs);
            padding: 14px;
        }
        .executive-card .label {
            font-size: 0.74rem;
            text-transform: uppercase;
            color: #475569;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .executive-card .value {
            font-size: 1.35rem;
            color: #0f172a;
            font-weight: 800;
            margin-top: 4px;
        }
        .executive-card .sub {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
        }
        .kpi-block-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }
        .semaforo-dot {
            display: inline-flex;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .semaforo-ok { background: #16a34a; }
        .semaforo-warn { background: #d97706; }
        .semaforo-risk { background: #dc2626; }
        .var-positive { color: #16a34a; font-weight: 700; }
        .var-negative { color: #dc2626; font-weight: 700; }
        .var-neutral { color: #64748b; font-weight: 700; }
        .executive-alert-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-xs);
            padding: 12px;
            margin-bottom: 14px;
        }
        .executive-alert-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            background: #f8fafc;
            font-size: 0.84rem;
        }
        .threshold-chip {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            font-size: 0.74rem;
            font-weight: 700;
            margin-right: 6px;
            color: #334155;
            background: #fff;
        }
        @media (max-width: 992px) {
            .executive-grid { grid-template-columns: 1fr; }
        }
    </style>
    <title>Reportes KPI - Direccion</title>
</head>

<body class="app-layout director-top-layout">
    <div id="AppKpiDireccion">
        <div class="d-flex flex-column flex-shrink-0 h-100 position-fixed top-0 end-0 app-main">
            <nav class="navbar app-navbar">
                <div class="container-fluid">
                    <span class="navbar-brand text-light text-center w-100 fw-bolder">The Fuentes Corporation Workspace</span>
                </div>
            </nav>
            <nav class="nav shadow-sm d-flex align-items-center" id="navtab" aria-label="breadcrumb" aria-current="page">
                <ol class="breadcrumb py-2 px-3 my-0">
                    <li class="breadcrumb-item"><a href="./index.php"><img src="../images/icons/home.svg" alt="home" height="24" width="24"><span>Inicio</span></a></li>
                    <li class="breadcrumb-item"><a href="./direccion.php"><span>Menu Direccion</span></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><span>Reportes KPI</span></li>
                </ol>
            </nav>

            <div class="director-shortcuts">
                <button type="button" class="btn btn-secondary" @click="goDireccion">Menu Direccion</button>
                <button type="button" class="btn btn-secondary" @click="goAutorizacion">Autorizacion Presiones</button>
                <button type="button" class="btn btn-secondary" @click="goIndex">Inicio</button>
                <button type="button" class="btn btn-danger ms-auto" @click="goLogout">Cerrar Sesion</button>
            </div>

            <div class="container page-shell overflow-auto page-content">
                <div class="page-hdr">
                    <div class="page-hdr-left">
                        <h2 class="page-title">Reportes KPI - Direccion</h2>
                        <p class="page-lead">Analitica de gastos por obra con filtros por semana, mes o acumulado total.</p>
                    </div>
                </div>

                <div class="kpi-filter-box">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Obra</label>
                            <select class="form-select" v-model="filtros.obraId">
                                <option value="0">Todas las obras</option>
                                <option v-for="obra in obras" :value="String(obra.obras_id)">{{obra.obras_nombre}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Periodo</label>
                            <select class="form-select" v-model="filtros.periodo">
                                <option value="total">Total</option>
                                <option value="semana">Semana</option>
                                <option value="mes">Mes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input class="form-control" type="date" v-model="filtros.desde">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input class="form-control" type="date" v-model="filtros.hasta">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Umbral medio %</label>
                            <input class="form-control" type="number" min="0" step="0.1" v-model.number="umbral.medio">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Umbral alto %</label>
                            <input class="form-control" type="number" min="0" step="0.1" v-model.number="umbral.alto">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" type="button" @click="aplicarFiltros">Aplicar</button>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                            <button class="btn btn-secondary" type="button" @click="exportarCsv">CSV</button>
                            <button class="btn btn-secondary" type="button" @click="exportarPdf">PDF</button>
                            <span class="threshold-chip">Medio > {{umbral.medio}}%</span>
                            <span class="threshold-chip">Alto > {{umbral.alto}}%</span>
                        </div>
                    </div>
                </div>

                <div class="ops-hero-grid">
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

                <div class="executive-grid">
                    <div class="executive-card">
                        <div class="label">Semana actual</div>
                        <div class="value">{{money(ejecutivo.totalSemanaActual)}}</div>
                        <div class="sub">{{ejecutivo.semanaActualLabel}}</div>
                    </div>
                    <div class="executive-card">
                        <div class="label">Semana anterior</div>
                        <div class="value">{{money(ejecutivo.totalSemanaAnterior)}}</div>
                        <div class="sub">{{ejecutivo.semanaAnteriorLabel}}</div>
                    </div>
                    <div class="executive-card">
                        <div class="label">Variacion semanal</div>
                        <div class="value" :class="variacionClass(ejecutivo.variacionSemana)">{{ejecutivo.variacionTexto}}</div>
                        <div class="sub">Comparativo de gasto total semanal</div>
                    </div>
                    <div class="executive-card">
                        <div class="label">Obras riesgo alto</div>
                        <div class="value">{{ejecutivo.riesgoAltoCount}}</div>
                        <div class="sub">Desviacion mayor al umbral alto</div>
                    </div>
                </div>

                <div class="executive-alert-box" v-if="alertasEjecutivas.length">
                    <div class="kpi-block-title">Alertas Ejecutivas Prioritarias</div>
                    <div class="executive-alert-item" v-for="a in alertasEjecutivas" :key="a.obra">
                        <strong>{{a.obra}}</strong>
                        <span class="ms-2">Estado: {{a.estado}}</span>
                        <span class="ms-2">Desviacion: {{a.desviacionPct.toFixed(2)}}%</span>
                        <span class="ms-2">Monto: {{money(a.montoDesviado)}}</span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="kpi-block-title">Top 10 Obras por Gasto</div>
                        <div class="kpi-table-wrap">
                            <table class="table table-hover align-middle kpi-table">
                                <thead>
                                    <tr>
                                        <th>OBRA</th>
                                        <th>PRESIONES</th>
                                        <th>TOTAL GASTOS</th>
                                        <th>TOTAL ADEUDO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in topObras" :key="'top_'+r.obra">
                                        <td>{{r.obra}}</td>
                                        <td>{{r.presiones}}</td>
                                        <td>{{money(r.total)}}</td>
                                        <td>{{money(r.adeudo)}}</td>
                                    </tr>
                                    <tr v-if="!topObras.length">
                                        <td colspan="4" class="text-center text-muted py-3">Sin datos para top de obras.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="kpi-block-title">Semaforo de Desviacion (Adeudo vs Gasto)</div>
                        <div class="kpi-table-wrap">
                            <table class="table table-hover align-middle kpi-table">
                                <thead>
                                    <tr>
                                        <th>ESTADO</th>
                                        <th>OBRA</th>
                                        <th>DESVIACION</th>
                                        <th>MONTO DESVIADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in semaforoObras" :key="'sem_'+r.obra">
                                        <td>
                                            <span class="semaforo-dot" :class="semaforoDotClass(r.estado)"></span>
                                            {{r.estado}}
                                        </td>
                                        <td>{{r.obra}}</td>
                                        <td>{{r.desviacionPct.toFixed(2)}}%</td>
                                        <td>{{money(r.montoDesviado)}}</td>
                                    </tr>
                                    <tr v-if="!semaforoObras.length">
                                        <td colspan="4" class="text-center text-muted py-3">Sin datos para semaforo.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="kpi-table-wrap">
                    <table class="table table-hover align-middle kpi-table">
                        <thead>
                            <tr>
                                <th>OBRA</th>
                                <th>PERIODO</th>
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
                                <td>{{r.periodo}}</td>
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
    <script src="../assets/js/reportes_kpi.js"></script>
</body>

</html>
