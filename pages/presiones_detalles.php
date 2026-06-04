<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['presiones.view', 'direccion.view', 'presiones.authorize'], 'No tienes permiso para ver el detalle de presiones');

$tf_page_title     = 'Detalle de Presion';
$tf_active_nav     = 'obras';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Detalle de Presion', '#']];
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
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.css">
<style>
[x-cloak]{display:none!important;}
#AppPresionDetail .table-prof thead th { white-space: nowrap; }
#AppPresionDetail .table-prof td { vertical-align: middle; }
#AppPresionDetail .table-prof thead th,
#AppPresionDetail .table-prof tbody td { padding: .62rem .68rem; }
#AppPresionDetail .tf-kpi-value { line-height: 1.15; }
@media (max-width: 991.98px) {
    #AppPresionDetail .tf-kpi-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
}
@media (max-width: 767.98px) {
    #AppPresionDetail .tf-page-header { margin-bottom: 14px; padding-bottom: 12px; }
    #AppPresionDetail .tf-page-actions { width: 100%; }
    #AppPresionDetail .tf-page-actions .tf-btn { width: 100%; }
    #AppPresionDetail .tf-kpi-grid { grid-template-columns: 1fr; }
    #AppPresionDetail .table-prof { font-size: .82rem; }
    #AppPresionDetail .table-prof thead th,
    #AppPresionDetail .table-prof tbody td { padding: .48rem .52rem; }
    #AppPresionDetail .table-prof .form-control { min-height: 34px; padding: .32rem .5rem; font-size: .8rem; }
}
</style>
CSS;

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
    <!--Esta es la llamada CSS de data table-->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <!--llamar a mi documento de CSS-->
    <link rel="stylesheet" href="../assets/css/main.css">
    <title>PRESION DE LA SEMANA</title>
</head>
=======
<div id="AppPresionDetail" class="tf-page-inner" x-data="presionDetailApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <header class="tf-page-header">
            <div>
                <span class="tf-eyebrow">Presion de Pago</span>
                <h1 class="tf-page-title">Presion — Semana <span x-text="semana"></span> / Dia <span x-text="dia"></span></h1>
                <p class="tf-page-lead">Detalle de la presion de pago de la obra <span x-text="(obraActiva[0] && obraActiva[0].obras_nombre) || ''"></span>.</p>
            </div>
            <div class="tf-page-actions">
                <button type="button" class="tf-btn tf-btn-secondary" x-on:click="exportarExcel">Exportar Excel</button>
            </div>
        </header>
>>>>>>> Stashed changes

        <div class="tf-kpi-grid mb-4">
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Semana / Dia</span></div>
                <div class="tf-kpi-value" style="font-size:1.25rem;">Sem <span x-text="semana"></span> — Dia <span x-text="dia"></span></div>
            </article>
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Items en Presion</span></div>
                <div class="tf-kpi-value" x-text="presiones.filter(function(p){return p.HojaEstatus == 'LIGADA' || p.HojaEstatus == 'AUTORIZADA' || p.HojaEstatus == 'PAGADA'}).length"></div>
            </article>
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Obra</span></div>
                <div class="tf-kpi-value" style="font-size:1.25rem;" x-text="(obraActiva[0] && obraActiva[0].obras_nombre) || '-'"></div>
            </article>
        </div>

        <section class="tf-card">
            <div class="tf-card-body p-0">
                <div class="table-responsive">
                    <table id="example" class="table table-prof align-middle table-hover w-100 mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center align-middle">CLAVE</th>
                                <th class="text-center align-middle">N° DE REQUISICION</th>
                                <th class="text-center align-middle">PROVEEDOR</th>
                                <th class="text-center align-middle">CONCEPTO</th>
                                <th class="text-center align-middle">ADEUDO</th>
                                <th class="text-center align-middle">NETO A PAGAR</th>
                                <th class="text-center align-middle">FORMA DE PAGO</th>
                                <th class="text-center align-middle">FECHA DE PAGO</th>
                                <th class="text-center align-middle">BANCO DE PAGO</th>
                                <th class="text-center align-middle">APLICAR ACCIONES</th>
                                <th class="text-center align-middle"></th>
                            </tr>
                        </thead>
                        <tbody class="table-light" id="Tabla_Items">
                            <template x-for="(presion, indice) in presiones" :key="presion.id_hoja + '-' + indice">
                            <tr x-show="presion.HojaEstatus == 'LIGADA' || presion.HojaEstatus == 'AUTORIZADA' || presion.HojaEstatus == 'PAGADA'">
                                <td class="text-center align-middle" x-text="presion.clave"></td>
                                <td x-bind:class="presion.atrClass" x-bind:style="presion.strStyle" x-text="presion.NumReq"></td>
                                <td x-bind:class="presion.atrClass" x-bind:style="presion.strStyle" x-text="presion.proveedor"></td>
                                <td x-bind:class="presion.atrClass" x-bind:style="presion.strStyle" x-text="presion.concepto"></td>
                                <td class="text-center align-middle" x-text="formatearMoneda(presion.total, true)"></td>
                                <td class="text-center align-middle" x-text="formatearMoneda(presion.adeudo, true)"></td>
                                <td class="text-center align-middle" x-text="presion.formaPago"></td>
                                <td class="text-center align-middle">
                                    <input type="date" class="form-control" x-model="presion.Fecha" x-show="canClosePresion">
                                    <span x-show="!canClosePresion" x-text="presion.Fecha || '—'"></span>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="text" class="form-control" x-model="presion.Banco" placeholder="Ingresa Banco" x-show="canClosePresion">
                                    <span x-show="!canClosePresion" x-text="presion.Banco || '—'"></span>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-danger"
                                            data-toggle="tooltip"
                                            x-bind:title="'Descargar ' + presion.NumReq"
                                            x-on:click="imprimirReq(presion.NumRequi,presion.clave,presion.id_hoja)">
                                        <img src="../images/icons/download.svg" alt="descargar" height="24" width="24">
                                    </button>
                                </td>
                                <td class="text-center align-middle">
                                    <img x-show="presion.showDetail == false" src="../images/icons/arrow_down.svg" alt="expandir" height="24" width="24" style="cursor:pointer;" x-on:click="cambiarBooleano(presion.showDetail,indice)">
                                    <img x-show="presion.showDetail != false" src="../images/icons/arrow_up.svg" alt="colapsar" height="24" width="24" style="cursor:pointer;" x-on:click="cambiarBooleano(presion.showDetail,indice)">
                                </td>
                            </tr>
                            </template>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td colspan="4" class="text-end">Total:</td>
                                <td class="text-center align-middle" x-text="formatearMoneda(sumatoria(presiones,'total'),true)"></td>
                                <td class="text-center align-middle" x-text="formatearMoneda(sumatoria(presiones,'adeudo'),true)"></td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>

        <div class="d-flex justify-content-center mt-4 mb-3" x-show="estatus == 'PENDIENTE' && canClosePresion">
            <button class="tf-btn tf-btn-primary" x-on:click="cerrarPresion" title="Cerrar Presion">CERRAR Y GUARDAR PRESION</button>
        </div>

        <!-- ── Comentario del Director ──────────────────────────────── -->
        <section class="tf-card mt-4" x-show="canClosePresion || comentarioDirector">
            <header class="tf-card-header">
                <div>
                    <h2 class="tf-card-title"><i class="bi bi-chat-text"></i> Comentario del Director</h2>
                    <p class="tf-card-sub">Notas visibles para el equipo de compras sobre esta presión.</p>
                </div>
                <button x-show="canClosePresion" type="button" class="tf-btn tf-btn-primary tf-btn-sm"
                        x-on:click="guardarComentarioDirector" x-bind:disabled="savingComentario">
                    <i class="bi bi-floppy"></i> <span x-text="savingComentario ? 'Guardando...' : 'Guardar nota'"></span>
                </button>
            </header>
            <div class="tf-card-body">
                <textarea x-show="canClosePresion"
                          class="form-control" rows="4"
                          placeholder="Escribe aquí el motivo de aprobación, rechazo parcial o cualquier indicación para compras..."
                          x-model="comentarioDirector"
                          maxlength="2000"
                          style="resize:vertical"></textarea>
                <div x-show="!canClosePresion" class="p-3 rounded" style="background:var(--tf-surface-2);border:1px solid var(--tf-border);white-space:pre-wrap" x-text="comentarioDirector || '-'">
                </div>
                <div class="text-end mt-1" x-show="canClosePresion">
                    <small class="text-muted"><span x-text="(comentarioDirector || '').length"></span>/2000</small>
                </div>
            </div>
        </section>
    </div>
<<<<<<< Updated upstream
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

    <!--SheetJS para exportacion Excel real (.xlsx)-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!--CDN de la bibloteca JsPDF-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>

    <script src="../assets/js/pdfGenerate.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/presiones_detalles.js"></script>
</body>

</html>

=======
</div>
>>>>>>> Stashed changes

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_use_jquery = true;
$tf_use_datatables = true;
$tf_extra_scripts =
    '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>' .
    '<script src="../assets/lib/exceljs/exceljs.min.js"></script>' .
    '<script src="../assets/lib/xlsx/xlsx.full.min.js"></script>' .
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js" integrity="sha384-NaWTHo/8YCBYJ59830LTz/P4aQZK1sS0SneOgAvhsIl3zBu8r9RevNg5lHCHAuQ/" crossorigin="anonymous"></script>' .
    '<script src="../assets/js/pdfGenerate.js"></script>' .
    '<script src="../assets/js/presiones_detalles.js?v=fase08n"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
