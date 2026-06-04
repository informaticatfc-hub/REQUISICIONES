<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo      = (new Conexion())->Conectar();
$__user     = tf_current_user($__pdo);
tf_require_direction_access($__pdo, 'Acceso restringido a usuarios con acceso directivo.');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? '';
$usuario_rolCode = $__user['role']['code'] ?? '';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title     = 'Reportes KPI';
$tf_active_nav     = 'direccion';
$tf_breadcrumb     = [['Direccion', './direccion.php'], ['Reportes KPI', '#']];
$tf_user = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = true;
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

$tf_extra_head = <<<'CSS'
<style>
[x-cloak] { display: none; }

/* ── KPI grid (6 cols) ── */
.kpi-6col { grid-template-columns: repeat(6, 1fr); }
@media (max-width: 1200px) { .kpi-6col { grid-template-columns: repeat(3, 1fr); } }
@media (max-width:  640px) { .kpi-6col { grid-template-columns: repeat(2, 1fr); } }
.kpi-val-sm { font-size: clamp(1.05rem, 1.8vw, 1.4rem) !important; letter-spacing: -.02em; }

/* ── mini progress bar inside KPI card ── */
.kpi-mini-bar  { height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-top: 8px; }
.kpi-mini-fill { height: 100%; border-radius: 99px; transition: width .5s; }
.kpi-mini-fill.fill-green  { background: var(--tf-success-600, #16a34a); }
.kpi-mini-fill.fill-yellow { background: var(--tf-warning-600, #d97706); }
.kpi-mini-fill.fill-red    { background: var(--tf-danger-600,  #dc2626); }

/* ── Active obra badge ── */
.kpi-obra-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe; border-radius: 99px;
    padding: 5px 14px; font-size: .8rem; font-weight: 500;
    cursor: pointer; transition: background .15s;
}
.kpi-obra-badge:hover { background: #dbeafe; }

/* ── Card header section count ── */
.kpi-section-count { font-size: .78rem; color: var(--tf-text-muted, #94a3b8); }

/* ── Bar chart ── */
.kpi-bar-legend { display: flex; align-items: center; font-size: .75rem; color: var(--tf-text-soft, #64748b); }
.kpi-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
.kpi-bar-list  { display: flex; flex-direction: column; gap: 10px; }
.kpi-bar-row   { display: grid; grid-template-columns: 200px 1fr 190px; align-items: center; gap: 14px; }
@media (max-width: 768px) {
    .kpi-bar-row { grid-template-columns: 1fr; }
    .kpi-bar-right { text-align: left !important; }
}
.kpi-bar-label     { min-width: 0; }
.kpi-bar-obra-name {
    display: block; font-size: .83rem; font-weight: 600;
    color: var(--tf-text, #0f172a); white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; cursor: pointer;
}
.kpi-bar-obra-name:hover { color: var(--tf-brand-600, #1d6fe5); text-decoration: underline; }
.kpi-bar-obra-meta { font-size: .71rem; color: var(--tf-text-muted, #94a3b8); }
.kpi-bar-track  { height: 26px; background: #f1f5f9; border-radius: 8px; overflow: hidden; display: flex; }
.kpi-bar-pagado { background: #22c55e; height: 100%; transition: width .5s ease; }
.kpi-bar-adeudo { background: #f97316; height: 100%; transition: width .5s ease; }
.kpi-bar-right      { text-align: right; }
.kpi-bar-total      { display: block; font-size: .87rem; font-weight: 700; color: var(--tf-text, #0f172a); }
.kpi-bar-adeudo-lbl { display: block; font-size: .73rem; color: #ef4444; }
.kpi-bar-ok-lbl     { display: block; font-size: .73rem; color: #16a34a; }

/* ── Detail table ── */
.kpi-table-wrap { overflow-x: auto; }
.kpi-table { min-width: 860px; margin: 0; }
.kpi-table thead th {
    position: sticky; top: 0; z-index: 1;
    background: var(--tf-surface-2, #f8fafc) !important;
    color: var(--tf-text-soft, #475569) !important;
    font-size: .71rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .05em; border-bottom: 1px solid #e2e8f0 !important;
    white-space: nowrap;
}
.kpi-table td       { font-size: .83rem; vertical-align: middle; }
.kpi-total-row td   { background: var(--tf-surface-2, #f8fafc); border-top: 2px solid #e2e8f0; }
.kpi-obra-link      { cursor: pointer; color: inherit; }
.kpi-obra-link:hover { color: var(--tf-brand-600, #1d6fe5); text-decoration: underline; }
.kpi-pct-cell   { display: flex; align-items: center; gap: 6px; }
.kpi-pct-track  { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.kpi-pct-fill   { height: 100%; border-radius: 99px; }
.kpi-pct-fill.good { background: #22c55e; }
.kpi-pct-fill.warn { background: #eab308; }
.kpi-pct-fill.bad  { background: #ef4444; }
.kpi-pct-label  { font-size: .72rem; color: var(--tf-text-muted, #94a3b8); white-space: nowrap; min-width: 32px; text-align: right; }

/* ── Hojas detail table ── */
.kpi-hojas-table td { font-size: .81rem; }
.kpi-presion-row { cursor: pointer; }
.kpi-presion-row.active td {
    background: #eff6ff !important;
    border-top: 1px solid #bfdbfe;
    border-bottom: 1px solid #bfdbfe;
}
[data-bs-theme="dark"] .kpi-presion-row.active td {
    background: rgba(59,130,246,.2) !important;
    border-top-color: rgba(96,165,250,.5);
    border-bottom-color: rgba(96,165,250,.5);
}
.kpi-presion-tools { display: flex; gap: 8px; flex-wrap: wrap; }
.kpi-hojas-id {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--tf-surface-2, #f8fafc);
    border: 1px solid var(--tf-border, #e2e8f0);
    font-weight: 600;
    font-size: .74rem;
}
.kpi-estado-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: .72rem;
    font-weight: 600;
}
.kpi-estado-badge.ok { background: #dcfce7; color: #166534; }
.kpi-estado-badge.mid { background: #fef3c7; color: #92400e; }
.kpi-estado-badge.off { background: #fee2e2; color: #991b1b; }

/* ── Loading / Empty states ── */
.kpi-loading-state { text-align: center; padding: 64px 20px; }
.kpi-obra-selector {
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    background: var(--tf-surface, #ffffff);
    color: var(--tf-text, #0f172a);
    box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
}
.kpi-obra-selector-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}
.kpi-obra-selector-scroll {
    max-height: 220px;
    overflow: auto;
    padding-right: 4px;
}
.kpi-obra-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.kpi-obra-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: var(--tf-brand-50, #eaf2ff);
    color: var(--tf-brand-700, #1d4ed8);
    font-size: .78rem;
    font-weight: 700;
}
.kpi-obra-chip button {
    border: 0;
    background: transparent;
    color: inherit;
    padding: 0;
    line-height: 1;
}
.kpi-obra-hit {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: background .2s ease;
}
.kpi-obra-hit:hover { background: var(--tf-surface-2, #eff6ff); }
.kpi-obra-hit input { margin-top: 0; }
.kpi-obra-hit small { color: var(--tf-text-muted, #64748b); }
.kpi-obra-single-card {
    border: 1px solid var(--tf-border, #dbe4f0);
    border-radius: 12px;
    background: var(--tf-surface-2, #f8fafc);
    padding: 12px;
}
.kpi-obra-single-card .badge { background: var(--tf-brand-600, #2563eb) !important; }
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>
<div id="AppKpiDireccion" class="tf-page-inner" x-data="reportesKpiApp()" x-init="init()" x-cloak>
<div class="tf-page-content">

<<<<<<< Updated upstream
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
=======
    <!-- ══ PAGE HEADER ═══════════════════════════════════════════ -->
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel de Direccion</span>
            <h1 class="tf-page-title">Reportes KPI</h1>
            <p class="tf-page-lead">Desglose profesional de gastos por fecha, proveedor y obra.</p>
        </div>
    </header>
>>>>>>> Stashed changes

    <!-- ══ FILTER PANEL ══════════════════════════════════════════ -->
    <div class="tf-card mb-4">
        <div class="tf-card-header">
            <h2 class="tf-card-title"><i class="bi bi-bar-chart-line"></i> Dashboard KPI — Dirección</h2>
            <div class="tf-card-actions">
                <button x-show="filtros.obraIds.length" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="verTodas">
                    <i class="bi bi-grid me-1"></i>Ver todas
                </button>
            </div>
        </div>
        <div class="tf-card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Obras</label>
                    <div class="kpi-obra-selector p-2 p-lg-3">
                        <template x-if="obraUnicaSeleccionada">
                            <div class="kpi-obra-single-card d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="flex-grow-1">
                                    <div class="badge text-bg-primary mb-2">Modo obra unica</div>
                                    <div class="fw-semibold" x-text="obraSeleccionUnicaDetalle ? obraSeleccionUnicaDetalle.obras_nombre : 'Obra seleccionada'"></div>
                                    <small class="text-muted d-block">Drilldown activo: el selector queda bloqueado a una sola obra para ver sus presiones y hojas.</small>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" x-on:click="verTodas">Cambiar</button>
                            </div>
                        </template>
                        <template x-if="!obraUnicaSeleccionada">
                            <div class="kpi-obra-selector-head mb-2">
                                <small class="text-muted">Busca y marca una o varias obras</small>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" x-on:click="seleccionarTodasObras" x-bind:disabled="!obras.length">Todas</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" x-on:click="limpiarObras" x-bind:disabled="!filtros.obraIds.length">Ninguna</button>
                                </div>
                            </div>
                            <input class="form-control form-control-sm mb-2" type="search" x-model.trim="filtroObraTexto" placeholder="Filtrar obra por nombre">
                            <div x-show="obrasSeleccionadasResumen.length" class="kpi-obra-chip-list mb-2">
                                <template x-for="obra in obrasSeleccionadasResumen" x-bind:key="obra.id">
                                    <span class="kpi-obra-chip">
                                        <span x-text="obra.nombre"></span>
                                        <button type="button" x-on:click="toggleObraSeleccion(obra.id)" aria-label="Quitar obra">×</button>
                                    </span>
                                </template>
                            </div>
                            <div class="kpi-obra-selector-scroll">
                                <div x-show="!obrasFiltradasSelector.length" class="text-muted small py-2">No hay obras que coincidan con el filtro.</div>
                                <template x-for="obra in obrasFiltradasSelector" x-bind:key="obra.obras_id">
                                    <label class="kpi-obra-hit border-bottom">
                                        <input class="form-check-input flex-shrink-0" type="checkbox" x-bind:value="String(obra.obras_id)" x-model="filtros.obraIds">
                                        <span class="flex-grow-1">
                                            <span class="d-block fw-semibold" x-text="obra.obras_nombre"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input class="form-control form-control-sm" type="date" x-model="filtros.desde">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input class="form-control form-control-sm" type="date" x-model="filtros.hasta">
                </div>
                <!-- Accesos rápidos de período -->
                <div class="col-md-auto d-flex flex-wrap gap-1 align-items-end pb-1">
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="setPeriodo('semana')" title="Últimos 7 días">7d</button>
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="setPeriodo('mes')" title="Mes actual">Mes</button>
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="setPeriodo('trimestre')" title="Último trimestre">Trim</button>
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="setPeriodo('anio')" title="Año actual">Año</button>
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="setPeriodo('todo')" title="Sin filtro de fecha">Todo</button>
                </div>
                <div class="col-md-2">
                    <button class="tf-btn tf-btn-primary w-100" x-on:click="aplicarFiltros" x-bind:disabled="cargando">
                        <span x-show="cargando" class="spinner-border spinner-border-sm me-1"></span>
                        Aplicar
                    </button>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="tf-btn tf-btn-ghost tf-btn-sm flex-fill" x-on:click="exportarCsv" x-bind:disabled="!consultaAplicada" title="Exportar CSV">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button class="tf-btn tf-btn-ghost tf-btn-sm flex-fill" x-on:click="exportarPdf" x-bind:disabled="!consultaAplicada" title="Exportar PDF">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ LOADING STATE ══════════════════════════════════════════ -->
    <div x-show="cargando && !consultaAplicada" class="kpi-loading-state">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2 text-muted">Cargando datos del sistema…</p>
    </div>

    <!-- ══ DASHBOARD ══════════════════════════════════════════════ -->
    <template x-if="consultaAplicada">

        <div>

        <!-- Obra activa badge -->
        <div x-show="filtros.obraIds.length" class="mb-3">
            <span class="kpi-obra-badge" x-on:click="verTodas" title="Clic para ver todas las obras">
                <i class="bi bi-building"></i>
                <span x-text="obraActualNombre"></span>
                <i class="bi bi-x-circle ms-1"></i>
            </span>
        </div>

        <!-- ── 6 KPI tiles ── -->
        <div class="tf-kpi-grid kpi-6col mb-4">

            <div class="tf-kpi">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">Presiones</span>
                    <div class="tf-kpi-icon"><i class="bi bi-receipt-cutoff"></i></div>
                </div>
                <div class="tf-kpi-value" x-text="kpi.presiones"></div>
                <p class="tf-kpi-sub">registros en filtro</p>
            </div>

            <div class="tf-kpi">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">Total Gastos</span>
                    <div class="tf-kpi-icon"><i class="bi bi-cash-stack"></i></div>
                </div>
                <div class="tf-kpi-value kpi-val-sm" x-text="money(kpi.total)"></div>
                <p class="tf-kpi-sub">monto total presionado</p>
            </div>

            <div class="tf-kpi success">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">Pagado</span>
                    <div class="tf-kpi-icon"><i class="bi bi-check-circle"></i></div>
                </div>
                <div class="tf-kpi-value kpi-val-sm" style="color:var(--tf-success-600)" x-text="money(kpi.pagado)"></div>
                <p class="tf-kpi-sub">monto liquidado</p>
            </div>

            <div class="tf-kpi danger">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">Adeudo</span>
                    <div class="tf-kpi-icon"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="tf-kpi-value kpi-val-sm" style="color:var(--tf-danger-600)" x-text="money(kpi.adeudo)"></div>
                <p class="tf-kpi-sub">pendiente de pago</p>
            </div>

            <div class="tf-kpi" x-bind:class="kpi.pct >= 80 ? 'success' : kpi.pct >= 40 ? 'warning' : 'danger'">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">% Pagado</span>
                    <div class="tf-kpi-icon"><i class="bi bi-percent"></i></div>
                </div>
                <div class="tf-kpi-value"><span x-text="kpi.pct.toFixed(1)"></span>%</div>
                <div class="kpi-mini-bar">
                    <div class="kpi-mini-fill"
                         x-bind:class="kpi.pct >= 80 ? 'fill-green' : kpi.pct >= 40 ? 'fill-yellow' : 'fill-red'"
                         x-bind:style="{width: kpi.pct + '%'}"></div>
                </div>
            </div>

            <div class="tf-kpi warning">
                <div class="tf-kpi-head">
                    <span class="tf-kpi-label">Hojas ligadas</span>
                    <div class="tf-kpi-icon"><i class="bi bi-file-earmark-text"></i></div>
                </div>
                <div class="tf-kpi-value" x-text="kpi.hojas"></div>
                <p class="tf-kpi-sub">Ticket prom: <span x-text="money(kpi.ticket)"></span></p>
            </div>

        </div>

        <!-- ── Bar chart por obra ── -->
        <div class="tf-card mb-4">
            <div class="tf-card-header">
                <h3 class="tf-card-title"><i class="bi bi-bar-chart-horizontal"></i> Presupuesto por Obra</h3>
                <span class="kpi-section-count"><span x-text="obraStats.length"></span> obra(s) con movimientos</span>
            </div>
            <div class="tf-card-body">
                <div x-show="!obraStats.length" class="text-center text-muted py-3">
                    Sin registros para los filtros seleccionados.
                </div>
                <div x-show="obraStats.length">
                    <div class="kpi-bar-legend mb-3">
                        <span><span class="kpi-dot" style="background:#22c55e"></span>Pagado</span>
                        <span class="ms-3"><span class="kpi-dot" style="background:#f97316"></span>Adeudo</span>
                    </div>
                    <div class="kpi-bar-list">
                        <template x-for="os in obraStats" x-bind:key="os.obraId">
                            <div class="kpi-bar-row">
                                <div class="kpi-bar-label">
                                    <span class="kpi-bar-obra-name" x-on:click="drillDown(os.obraId)" x-bind:title="os.obra" x-text="os.obra"></span>
                                    <span class="kpi-bar-obra-meta"><span x-text="os.presiones"></span> presiones · <span x-text="os.hojas"></span> hojas</span>
                                </div>
                                <div class="kpi-bar-track">
                                    <div class="kpi-bar-pagado" x-bind:style="{width: calcPct(os.pagado, os.total) + '%'}"></div>
                                    <div class="kpi-bar-adeudo" x-bind:style="{width: calcPct(os.adeudo, os.total) + '%'}"></div>
                                </div>
                                <div class="kpi-bar-right">
                                    <span class="kpi-bar-total" x-text="money(os.total)"></span>
                                    <span x-show="os.adeudo > 0" class="kpi-bar-adeudo-lbl">Adeudo: <span x-text="money(os.adeudo)"></span></span>
                                    <span x-show="!(os.adeudo > 0)" class="kpi-bar-ok-lbl"><i class="bi bi-check2-circle"></i> Al corriente</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Detail table (collapsible) ── -->
        <div class="tf-card mb-4" x-show="reporteAgrupado.length">
            <div class="tf-card-header">
                <h3 class="tf-card-title"><i class="bi bi-table"></i> Detalle por Fecha</h3>
                <div class="tf-card-actions">
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="mostrarTabla = !mostrarTabla">
                        <i class="bi" x-bind:class="mostrarTabla ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        <span x-text="mostrarTabla ? 'Ocultar' : 'Ver detalle'"></span>
                    </button>
                </div>
            </div>
            <div x-show="mostrarTabla" class="kpi-table-wrap">
                <table class="table table-hover align-middle kpi-table mb-0">
                    <thead>
                        <tr>
                            <th>OBRA</th>
                            <th>FECHA</th>
                            <th class="text-center">PRES.</th>
                            <th class="text-center">HOJAS</th>
                            <th class="text-end">TOTAL</th>
                            <th class="text-end">ADEUDO</th>
                            <th style="min-width:130px">% PAGADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in reporteAgrupado" x-bind:key="r.key">
                            <tr>
                                <td>
                                    <span class="kpi-obra-link" x-on:click="drillDown(r.obraId)" x-text="r.obra"></span>
                                </td>
                                <td x-text="r.fecha"></td>
                                <td class="text-center" x-text="r.presiones"></td>
                                <td class="text-center" x-text="r.hojas"></td>
                                <td class="text-end fw-semibold" x-text="money(r.total)"></td>
                                <td class="text-end" x-bind:class="r.adeudo > 0 ? 'text-danger' : 'text-success'" x-text="r.adeudo > 0 ? money(r.adeudo) : '—'"></td>
                                <td>
                                    <div class="kpi-pct-cell">
                                        <div class="kpi-pct-track">
                                            <div class="kpi-pct-fill" x-bind:class="pctClass(r.pct)" x-bind:style="{width: r.pct + '%'}"></div>
                                        </div>
                                        <span class="kpi-pct-label"><span x-text="r.pct.toFixed(0)"></span>%</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr class="kpi-total-row">
                            <td colspan="4" class="text-end fw-bold text-muted">TOTALES</td>
                            <td class="text-end fw-bold" x-text="money(kpi.total)"></td>
                            <td class="text-end fw-bold" x-bind:class="kpi.adeudo > 0 ? 'text-danger' : 'text-success'" x-text="money(kpi.adeudo)"></td>
                            <td>
                                <div class="kpi-pct-cell">
                                    <div class="kpi-pct-track">
                                        <div class="kpi-pct-fill" x-bind:class="pctClass(kpi.pct)" x-bind:style="{width: kpi.pct + '%'}"></div>
                                    </div>
                                    <span class="kpi-pct-label"><span x-text="kpi.pct.toFixed(0)"></span>%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Escalera: presiones (obra unica) ── -->
        <div class="tf-card mb-4" x-show="obraUnicaSeleccionada">
            <div class="tf-card-header">
                <h3 class="tf-card-title"><i class="bi bi-diagram-3"></i> Presiones de la obra seleccionada</h3>
                <div class="tf-card-actions d-flex align-items-center gap-2">
                    <span class="kpi-section-count"><span x-text="presionesDetalle.length"></span> presion(es)</span>
                </div>
            </div>
            <div class="kpi-table-wrap">
                <table class="table table-hover align-middle kpi-table kpi-hojas-table mb-0">
                    <thead>
                        <tr>
                            <th>PRESION</th>
                            <th>OBRA</th>
                            <th>FECHA</th>
                            <th class="text-center">HOJAS</th>
                            <th class="text-end">TOTAL</th>
                            <th class="text-end">ADEUDO</th>
                            <th style="min-width:130px">% PAGADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="!presionesDetalle.length">
                            <td colspan="8" class="text-center text-muted py-3">Sin presiones para la obra/filtro seleccionado.</td>
                        </tr>
                        <template x-for="p in presionesDetalle" x-bind:key="'p-' + p.presiones_id">
                            <tr class="kpi-presion-row"
                                x-bind:class="{ active: Number(selectedPresionId) === Number(p.presiones_id) }"
                                x-on:click="seleccionarPresion(p.presiones_id)">
                                <td>
                                    <strong x-text="p.presiones_nombre || ('Presion #' + p.presiones_id)"></strong>
                                    <div class="text-muted" style="font-size:.7rem">ID <span x-text="p.presiones_id"></span></div>
                                </td>
                                <td x-text="p.obra || 'SIN OBRA'"></td>
                                <td x-text="isoDate(p.presiones_fechaCreacion) || 'SIN_FECHA'"></td>
                                <td class="text-center" x-text="p.hojas"></td>
                                <td class="text-end fw-semibold" x-text="money(p.total)"></td>
                                <td class="text-end" x-bind:class="p.adeudo > 0 ? 'text-danger' : 'text-success'" x-text="p.adeudo > 0 ? money(p.adeudo) : '—'"></td>
                                <td>
                                    <div class="kpi-pct-cell">
                                        <div class="kpi-pct-track">
                                            <div class="kpi-pct-fill" x-bind:class="pctClass(p.pct)" x-bind:style="{width: p.pct + '%'}"></div>
                                        </div>
                                        <span class="kpi-pct-label"><span x-text="p.pct.toFixed(0)"></span>%</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Escalera: hojas de la presion seleccionada ── -->
        <div class="tf-card mb-4" x-show="obraUnicaSeleccionada && selectedPresionId">
            <div class="tf-card-header">
                <h3 class="tf-card-title"><i class="bi bi-list-check"></i> Hojas ligadas de la presion seleccionada</h3>
                <div class="tf-card-actions d-flex align-items-center gap-2">
                    <span class="kpi-section-count"><span x-text="hojasPresionSeleccionada.length"></span> hoja(s)</span>
                    <div class="kpi-presion-tools">
                        <button class="btn btn-sm btn-outline-success" x-on:click="exportarExcelPresion" x-bind:disabled="!hojasPresionSeleccionada.length">
                            <i class="bi bi-file-earmark-excel"></i> Excel Presion
                        </button>
                        <button class="btn btn-sm btn-outline-primary" x-on:click="exportarZipPresion" x-bind:disabled="!hojasPresionSeleccionada.length || exportandoZip">
                            <span x-show="exportandoZip" class="spinner-border spinner-border-sm me-1"></span>
                            <i x-show="!exportandoZip" class="bi bi-file-zip"></i>
                            ZIP (Excel + PDFs)
                        </button>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" x-on:click="mostrarHojas = !mostrarHojas">
                        <i class="bi" x-bind:class="mostrarHojas ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        <span x-text="mostrarHojas ? 'Ocultar' : 'Ver detalle'"></span>
                    </button>
                </div>
            </div>
            <div x-show="mostrarHojas" class="kpi-table-wrap">
                <table class="table table-hover align-middle kpi-table kpi-hojas-table mb-0">
                    <thead>
                        <tr>
                            <th>PRESION</th>
                            <th>OBRA</th>
                            <th>HOJA</th>
                            <th>REQUISICION</th>
                            <th>PROVEEDOR</th>
                            <th>ESTATUS</th>
                            <th class="text-end">TOTAL</th>
                            <th class="text-end">ADEUDO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="h in hojasPresionSeleccionada" x-bind:key="'h-' + h.hojaRequisicion_id + '-' + h.presiones_id">
                            <tr>
                                <td>
                                    <strong x-text="h.presiones_nombre || ('Presion #' + h.presiones_id)"></strong>
                                    <div class="text-muted" style="font-size:.7rem" x-text="isoDate(h.presiones_fechaCreacion) || 'SIN_FECHA'"></div>
                                </td>
                                <td x-text="h.obras_nombre || 'SIN OBRA'"></td>
                                <td><span class="kpi-hojas-id">#<span x-text="h.hojaRequisicion_numero || h.hojaRequisicion_id"></span></span></td>
                                <td>
                                    <div x-text="h.requisicion_Numero || 'SIN NUMERO'"></div>
                                    <div class="text-muted" style="font-size:.7rem" x-text="h.requisicion_Nombre || ''"></div>
                                </td>
                                <td x-text="h.proveedor_nombre || 'SIN PROVEEDOR'"></td>
                                <td>
                                    <span class="kpi-estado-badge" x-bind:class="estadoBadgeClass(h.hojaRequisicion_estatus)" x-text="h.hojaRequisicion_estatus || 'SIN_ESTATUS'"></span>
                                </td>
                                <td class="text-end fw-semibold" x-text="money(h.hojaRequisicion_total)"></td>
                                <td class="text-end" x-bind:class="toNum(h.hojarequisicion_adeudo) > 0 ? 'text-danger' : 'text-success'" x-text="toNum(h.hojarequisicion_adeudo) > 0 ? money(h.hojarequisicion_adeudo) : '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tf-card mb-4" x-show="!obraUnicaSeleccionada">
            <div class="tf-card-body text-muted">
                Selecciona una sola obra para habilitar la vista escalonada de Presion → Hojas y la descarga ZIP por presion.
            </div>
        </div>

        </div>
    </template>

    <!-- Empty (before load) -->
    <template x-if="!consultaAplicada && !cargando">
        <div class="tf-card">
            <div class="tf-card-body text-center text-muted py-5">
                Presiona <strong>Aplicar</strong> para cargar el dashboard.
            </div>
        </div>
    </template>

</div>
</div>

<?php
$tf_use_vue       = false;
$tf_use_axios     = true;
$tf_extra_scripts =
    '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>' .
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>' .
    '<script src="../assets/lib/exceljs/exceljs.min.js"></script>' .
    '<script src="../assets/lib/jspdf/jspdf.umd.min.js"></script>' .
    '<script src="../assets/lib/jspdf/jspdf.plugin.autotable.min.js"></script>' .
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>' .
    '<script src="../assets/js/pdfGenerate.js?v=fase08u"></script>' .
    '<script src="../assets/js/reportes_kpi.js?v=fase08u"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
