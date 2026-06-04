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
    </style>
    <title>Menu de Obras</title>
</head>
=======
<div id="AppDireccion" class="tf-page-inner" x-data="direccionApp()" x-init="init()" x-cloak>
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel directivo</span>
            <h1 class="tf-page-title">Direccion</h1>
            <p class="tf-page-lead">Panel directivo para autorizaciones y reportes globales.</p>
        </div>
    </header>
>>>>>>> Stashed changes

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
