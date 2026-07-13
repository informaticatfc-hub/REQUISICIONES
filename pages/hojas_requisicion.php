<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['requisiciones.view', 'requisiciones.edit', 'direccion.view', 'presiones.authorize'], 'No tienes permiso para ver las hojas de requisición');

$__canReqEdit   = tf_has_permission('requisiciones.create', $__user) || tf_has_permission('requisiciones.edit', $__user);
$__canDireccion = tf_has_permission('direccion.view', $__user) || tf_user_has_direction_access($__user);

$tf_page_title = 'Hojas de la Requisicion';
$tf_active_nav = 'obras';
$tf_breadcrumb = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Requisiciones', './requisiciones.php'], ['Hojas de la Requisicion', '#']];
$tf_user = [
    'name'        => $__user['user_name'] ?? '',
    'role'        => $__user['role']['name'] ?? '',
    'roleCode'    => $__user['role']['code'] ?? '',
    'initials'    => '',
    'permissions' => $__user['permissions'] ?? [],
];
$tf_show_direccion = in_array(($__user['role']['code'] ?? ''), ['director', 'admin', 'desarrollador'], true)
    || tf_user_has_direction_access($__user);
$tf_show_admin = in_array(($__user['role']['code'] ?? ''), ['admin', 'desarrollador'], true);
$tf_user_id_js = (string)($__user['user_id'] ?? '');

$tf_extra_head = <<<'CSS'
<style>
[x-cloak]{display:none!important;}
.hoja-meta{display:flex;flex-wrap:wrap;gap:12px;margin:4px 0 20px;}
.hoja-meta .meta-item{flex:1 1 180px;background:var(--tf-surface);border:1px solid var(--tf-border);border-radius:var(--tf-radius);padding:12px 16px;box-shadow:var(--tf-shadow-sm);}
.hoja-meta .meta-label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--tf-text-muted);margin-bottom:4px;}
.hoja-meta .meta-value{font-size:1.05rem;font-weight:700;color:var(--tf-text);word-break:break-word;}
.cot-upload-zone{background:var(--tf-surface-2)!important;border:1px solid var(--tf-border)!important;}
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppHojas" class="tf-page-inner" x-data="hojasReqApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <header class="tf-page-header">
            <div>
                <span class="tf-eyebrow">Requisicion activa</span>
                <h1 class="tf-page-title">Hojas - Requisicion <span x-text="(requisiciones[0] && requisiciones[0].requisicion_Numero) || ''"></span></h1>
                <p class="tf-page-lead">Consulta y administra las hojas que componen esta requisicion.</p>
            </div>
            <div class="tf-page-header-actions" x-show="canReqEdit">
                <button type="button" class="tf-btn tf-btn-primary" x-on:click="addHoja">
                    <i class="bi bi-file-earmark-plus"></i> Agregar Hoja <span x-text="Number((requisiciones[0] && requisiciones[0].requisicion_Hojas) || 0) + 1"></span>
                </button>
            </div>
        </header>

        <div class="hoja-meta">
            <div class="meta-item">
                <span class="meta-label">Numero de Requisicion</span>
                <span class="meta-value" x-text="(requisiciones[0] && requisiciones[0].requisicion_Numero) || '—'"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total de Hojas</span>
                <span class="meta-value" x-text="hojas.length"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Monto Total Requisición</span>
                <span class="meta-value" x-text="formatearMoneda(montoTotalRequisicion,true)"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Obra</span>
                <span class="meta-value" x-text="obras.length ? obras[0].obras_nombre : '—'"></span>
            </div>
        </div>

        <section class="tf-card">
            <div class="tf-card-body p-0" style="overflow-x:auto">
                <table class="tf-admin-table w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Numero de Hoja</th>
                            <th class="text-center">Forma de Pago</th>
                            <th class="text-center">Total de Pagar</th>
                            <th class="text-center">Estatus</th>
                            <th style="text-align:right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="Tabla_Items">
                        <template x-for="(hoja,indice) in hojas" :key="hoja.hojaRequisicion_id + '-' + indice">
                        <tr class="my-3">
                            <td scope="row" class="text-center align-middle">Hoja N° <span x-text="hoja.hojaRequisicion_numero"></span></td>
                            <td class="text-center align-middle" x-text="hoja.hojaRequisicion_formaPago"></td>
                            <td class="text-center align-middle" x-text="formatearMoneda(hoja.hojaRequisicion_total,true)"></td>
                            <td class="text-center align-middle">
                                <span class="badge"
                                    x-bind:class="{
                                        'bg-secondary':  hoja.hojaRequisicion_estatus === 'NUEVO',
                                        'bg-warning text-dark': hoja.hojaRequisicion_estatus === 'PENDIENTE' || hoja.hojaRequisicion_estatus === 'REVISION',
                                        'bg-info text-dark':    hoja.hojaRequisicion_estatus === 'LIGADA',
                                        'bg-danger':     hoja.hojaRequisicion_estatus === 'RECHAZADA',
                                        'bg-primary':    hoja.hojaRequisicion_estatus === 'AUTORIZADA',
                                        'bg-success':    hoja.hojaRequisicion_estatus === 'PAGADA'
                                    }"
                                    x-text="hoja.hojaRequisicion_estatus === 'PENDIENTE' ? 'NO APROBADO' : hoja.hojaRequisicion_estatus">
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group" role="group" aria-label="acciones hoja">
                                    <button type="button" class="btn btn-success" x-on:click="ConsultarItemHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Consultar Hoja">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-white" viewBox="0 0 16 16">
                                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                        </svg>
                                    </button>
                                    <!-- ITF-3: Cotizaciones adjuntas -->
                                    <button type="button" class="btn btn-outline-primary position-relative" x-on:click="abrirCotizaciones(hoja.hojaRequisicion_id, hoja.hojaRequisicion_numero)" data-toggle="tooltip" title="Cotizaciones adjuntas">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" x-on:click="eliminarHoja(hoja.hojaRequisicion_id)" data-toggle="tooltip" title="Eliminar Hoja" x-show="canReqEdit && (hoja.hojaRequisicion_estatus == 'NUEVO' || hoja.hojaRequisicion_estatus == 'PENDIENTE' || hoja.hojaRequisicion_estatus == 'RECHAZADA')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                            <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Total requisición:</td>
                            <td class="text-center" x-text="formatearMoneda(montoTotalRequisicion,true)"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</div>

<!-- ITF-3: Modal cotizaciones adjuntas -->
<div class="modal fade" id="modalCotizaciones" tabindex="-1" aria-labelledby="modalCotizacionesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCotizacionesLabel">
                    <i class="bi bi-paperclip me-2"></i>Cotizaciones — Hoja N° <span id="cotModalHojaNum"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Upload -->
                <div id="cotUploadZone" class="cot-upload-zone rounded p-3 mb-3 d-none">
                    <p class="mb-2 fw-semibold small">Adjuntar cotización (PDF, máx. 8 MB)</p>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Nombre descriptivo</label>
                            <input type="text" id="cotNombreInput" class="form-control form-control-sm" placeholder="Ej: Cotización Proveedor A">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Archivo PDF</label>
                            <input type="file" id="cotFileInput" class="form-control form-control-sm" accept=".pdf,application/pdf">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary btn-sm" id="cotSubirBtn" onclick="window._cotApp && window._cotApp.subirCotizacion()">
                                <i class="bi bi-upload me-1"></i>Subir
                            </button>
                        </div>
                    </div>
                    <div id="cotUploadMsg" class="mt-2 small d-none"></div>
                </div>

                <!-- Lista -->
                <div id="cotLoadingSpinner" class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm me-2"></div> Cargando…
                </div>
                <div id="cotListaContainer" class="d-none">
                    <p id="cotEmptyMsg" class="text-muted text-center py-3 d-none">No hay cotizaciones adjuntas para esta hoja.</p>
                    <ul id="cotLista" class="list-group list-group-flush"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
// jQuery/DataTables ya no se usan: la tabla la renderiza Alpine (x-for).
$tf_use_jquery = false;
$tf_use_datatables = false;
$tf_extra_scripts =
    '<script>window.TF_LEGACY_PERMS = {canReqEdit:' . ($__canReqEdit ? 'true' : 'false') . ', canDireccion:' . ($__canDireccion ? 'true' : 'false') . '};</script>' .
    '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>' .
    '<script src="../assets/js/hojas_requisicion.js?v=fase09a"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
