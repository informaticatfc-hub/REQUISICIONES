<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_direction_access($__pdo, 'No tienes permiso para acceder a Direccion');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Usuario';
$usuario_rolCode = $__user['role']['code'] ?? 'usuario';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title     = 'Direccion';
$tf_active_nav     = 'direccion';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Direccion', '#']];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = true;
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppDireccion" class="tf-page-inner" x-data="direccionApp()" x-init="init()" x-cloak>
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel directivo</span>
            <h1 class="tf-page-title">Direccion</h1>
            <p class="tf-page-lead">Panel directivo para autorizaciones y reportes globales.</p>
        </div>
    </header>

    <section class="tf-card">
        <div class="tf-card-body">
            <div class="tf-module-grid">
                <button type="button" class="tf-module-card" x-on:click="enterAllPresiones">
                    <span class="tf-module-icon tf-module-icon-warning"><i class="bi bi-briefcase-fill"></i></span>
                    <span class="tf-module-label">Autorizacion Presiones</span>
                    <span class="tf-module-sub">Aprueba, rechaza y ajusta pagos por obra.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </button>

                <button type="button" class="tf-module-card" x-on:click="enterReportesKpi">
                    <span class="tf-module-icon tf-module-icon-primary"><i class="bi bi-graph-up-arrow"></i></span>
                    <span class="tf-module-label">Reportes KPI</span>
                    <span class="tf-module-sub">Analiza gastos y comportamiento por periodos.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </section>
</div>

<?php
$tf_use_vue = false;
$tf_use_jquery = true;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script><script src="../assets/js/direccion.js?v=fase08b"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
