<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);
$__canEnlazar = tf_has_permission('presiones.create', $__user)
    || tf_has_permission('presiones.authorize', $__user)
    || in_array((string)($__user['role']['code'] ?? ''), ['admin', 'director', 'desarrollador'], true);

$tf_page_title     = 'Enlazar Requisiciones';
$tf_active_nav     = 'obras';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Obras', './obras.php'], ['Enlazar Requisiciones', '#']];
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
[x-cloak] { display: none !important; }
#AppPresion .table-prof thead th { white-space: nowrap; }
#AppPresion .table-prof td { vertical-align: middle; }
#AppPresion .table-prof thead th,
#AppPresion .table-prof tbody td { padding: .62rem .72rem; }
#AppPresion .tf-kpi-value { line-height: 1.15; }
@media (max-width: 991.98px) {
    #AppPresion .tf-kpi-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
}
@media (max-width: 767.98px) {
    #AppPresion .tf-page-header { margin-bottom: 14px; padding-bottom: 12px; }
    #AppPresion .tf-kpi-grid { grid-template-columns: 1fr; }
    #AppPresion .table-prof { font-size: .83rem; }
    #AppPresion .table-prof thead th,
    #AppPresion .table-prof tbody td { padding: .5rem .55rem; }
    #AppPresion .table-prof .btn { padding: .3rem .46rem; }
}
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppPresion" class="tf-page-inner" x-data="enlazarReqApp()" x-init="init()" x-cloak>
    <div class="tf-page-content">
        <header class="tf-page-header">
            <div>
                <span class="tf-eyebrow">Vinculacion</span>
                <h1 class="tf-page-title">Enlazar Requisiciones</h1>
                <p class="tf-page-lead">
                    Selecciona requisiciones disponibles para agregar a la presion
                    <strong x-text="(presiones[0] && presiones[0].presiones_nombre) || ''"></strong>.
                </p>
            </div>
        </header>

        <div class="tf-kpi-grid mb-4">
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Presion Activa</span></div>
                <div class="tf-kpi-value" style="font-size:1.25rem;" x-text="(presiones[0] && presiones[0].presiones_nombre) || '-'"></div>
            </article>
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Requisiciones Disponibles</span></div>
                <div class="tf-kpi-value" x-text="requisiciones.length"></div>
            </article>
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Total ya ligado</span></div>
                <div class="tf-kpi-value" style="font-size:1.1rem" x-bind:class="totalYaLigado > 0 ? 'text-warning' : ''" x-text="formatearMoneda(totalYaLigado, true)">
                </div>
            </article>
            <article class="tf-kpi">
                <div class="tf-kpi-head"><span class="tf-kpi-label">Obra</span></div>
                <div class="tf-kpi-value" style="font-size:1.25rem;" x-text="(obras[0] && obras[0].obras_nombre) || '-'"></div>
            </article>
        </div>

        <section class="tf-card">
            <div class="tf-card-body p-0">
                <div class="table-responsive">
                    <table id="example" class="table table-prof table-hover align-middle w-100 mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center align-middle">Numero de Requisicion</th>
                                <th class="text-center align-middle">Numero de Hoja</th>
                                <th class="text-center align-middle">Nombre de la Requisicion</th>
                                <th class="text-center align-middle">Clave</th>
                                <th class="text-center align-middle">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="table-light" id="Tabla_Items">
                            <template x-for="(req, indice) in requisiciones" :key="req.hojaRequisicion_id + '-' + indice">
                            <tr>
                                <td class="text-center align-middle" x-text="req.requisicion_Numero"></td>
                                <td class="text-center align-middle">Hoja N° <span x-text="req.hojaRequisicion_numero"></span></td>
                                <td class="text-start align-middle" x-text="req.requisicion_Nombre"></td>
                                <td class="text-center align-middle" x-text="req.requisicion_Clave"></td>
                                <td class="text-center align-middle" x-text="formatearMoneda(req.hojaRequisicion_total,true)"></td>
                                <td class="text-center align-middle">
                                    <?php if ($__canEnlazar): ?>
                                    <button type="button" class="btn btn-primary"
                                            x-on:click="enlazarConPresion(req.hojaRequisicion_id, req.requisicion_id, req.hojaRequisicion_total)"
                                            data-toggle="tooltip"
                                            x-bind:title="'Revisar Hoja N°' + req.hojaRequisicion_numero + ' de la Requisicion ' + req.requisicion_Numero">
                                        <img src="../images/icons/pay.svg" alt="accion" height="24" width="24">
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_use_jquery = true;
$tf_use_datatables = true;
$tf_extra_scripts = '<script src="../assets/js/enlazar_requisiciones.js?v=fase08n2"></script><script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
