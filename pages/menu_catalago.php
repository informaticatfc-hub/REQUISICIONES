<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['catalogos.view'], 'No tienes permiso para acceder a los catalogos');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Residente';
$usuario_rolCode = $__user['role']['code'] ?? 'residente';
$usuario_perms   = $__user['permissions']  ?? [];
$usuario_dirAcc  = tf_user_has_direction_access($__user) ? 1 : 0;

$tf_page_title     = 'Catalogos';
$tf_active_nav     = 'catalogos';
$tf_breadcrumb     = [
    ['Inicio',    './index.php'],
    ['Catalogos', '#'],
];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = tf_user_has_direction_access($__user);
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

$tf_subbar_extra = '
    <div class="tf-subbar-actions">
        <a href="./index.php" class="tf-btn tf-btn-ghost tf-btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al inicio
        </a>
    </div>
';

$canManageProv  = tf_has_permission('proveedores.manage', $__user);
$canManageBanco = tf_has_permission('bancos.manage', $__user);

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppCatalogos" class="tf-page-inner">

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Configuracion del sistema</span>
            <h1 class="tf-page-title">Catalogos</h1>
            <p class="tf-page-lead">
                Administra la informacion base del sistema: proveedores, bancos y otros catalogos.
            </p>
        </div>
    </header>

    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-collection-fill"></i> Catalogos disponibles
                </h2>
                <p class="tf-card-sub">Selecciona un catalogo para gestionarlo</p>
            </div>
        </header>
        <div class="tf-card-body">
            <div class="tf-module-grid">

                <button type="button"
                        class="tf-module-card"
                        onclick="window.location.href='./proveedores.php'">
                    <span class="tf-module-icon tf-module-icon-primary">
                        <i class="bi bi-truck"></i>
                    </span>
                    <span class="tf-module-label">Proveedores</span>
                    <span class="tf-module-sub">
                        Consulta y gestiona el catalogo de proveedores registrados
                    </span>
                    <span class="tf-module-cta">
                        <?= $canManageProv ? 'Gestionar' : 'Consultar' ?>
                        <i class="bi bi-arrow-right"></i>
                    </span>
                </button>

                <button type="button"
                        class="tf-module-card"
                        onclick="window.location.href='./bancos.php'">
                    <span class="tf-module-icon tf-module-icon-success">
                        <i class="bi bi-bank"></i>
                    </span>
                    <span class="tf-module-label">Bancos</span>
                    <span class="tf-module-sub">
                        Administra los bancos registrados en el sistema
                    </span>
                    <span class="tf-module-cta">
                        <?= $canManageBanco ? 'Gestionar' : 'Consultar' ?>
                        <i class="bi bi-arrow-right"></i>
                    </span>
                </button>

            </div>
        </div>
    </section>

    <!-- Info de permisos contextual -->
    <section class="tf-card" x-show="false">
        <!-- placeholder para futuros catalogos -->
    </section>

</div>

<?php
$tf_use_vue = false;
include __DIR__ . '/../includes/layout_bottom.php';
?>
