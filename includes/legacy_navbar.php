<?php
// Topbar unificada para vistas legacy.
// Mantiene la misma estructura visual que Inicio y cambia solo las acciones por rol.

require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__legacyPdo = (new Conexion())->Conectar();
$__legacyUser = tf_current_user($__legacyPdo);
$__legacyRole = strtolower((string)($__legacyUser['role']['code'] ?? ''));
$__legacyDirAcc = tf_user_has_direction_access($__legacyUser) ? 1 : 0;
$__legacyIsDirector = in_array($__legacyRole, ['director', 'direccion', 'admin'], true) || $__legacyDirAcc === 1;

<<<<<<< Updated upstream
$legacy_show_actions = isset($legacy_show_actions) ? (bool)$legacy_show_actions : true;

if (!isset($legacy_actions) || !is_array($legacy_actions) || !count($legacy_actions)) {
    if ($__legacyIsDirector) {
        $legacy_actions = [
            ['label' => 'Inicio', 'href' => './index.php', 'class' => 'tf-nav-link'],
            ['label' => 'Menu Direccion', 'href' => './direccion.php', 'class' => 'tf-nav-link active'],
            ['label' => 'Autorizacion Presiones', 'href' => './all_presiones.php', 'class' => 'tf-nav-link'],
            ['label' => 'Reportes KPI', 'href' => './reportes_kpi.php', 'class' => 'tf-nav-link'],
            ['label' => 'Cerrar Sesion', 'href' => './closeSesion.php', 'class' => 'tf-nav-link tf-nav-danger'],
        ];
    } else {
        $legacy_actions = [
            ['label' => 'Inicio', 'href' => './index.php', 'class' => 'tf-nav-link active'],
            ['label' => 'Obras', 'href' => './obras.php', 'class' => 'tf-nav-link'],
            ['label' => 'Requisiciones', 'href' => './requisiciones.php', 'class' => 'tf-nav-link'],
            ['label' => 'Presiones', 'href' => './presiones.php', 'class' => 'tf-nav-link'],
            ['label' => 'Catalogos', 'href' => './menu_catalago.php', 'class' => 'tf-nav-link'],
            ['label' => 'Cerrar Sesion', 'href' => './closeSesion.php', 'class' => 'tf-nav-link tf-nav-danger'],
        ];
    }
}
=======
$__legacyHasAll = in_array('*', $__legacyPerms, true);
$__legacyCanObras = $__legacyHasAll || in_array('obras.view', $__legacyPerms, true);
$__legacyCanRequisiciones = $__legacyHasAll || in_array('requisiciones.view', $__legacyPerms, true);
$__legacyCanPresiones = $__legacyHasAll || in_array('presiones.view', $__legacyPerms, true);
$__legacyCanCatalogos = $__legacyHasAll || in_array('catalogos.view', $__legacyPerms, true);
$__legacyCanDireccion = $__legacyHasAll
    || in_array('direccion.view', $__legacyPerms, true)
    || in_array('presiones.authorize', $__legacyPerms, true)
    || $__legacyIsDirector;

if (!function_exists('tf_legacy_nav_active')) {
    function tf_legacy_nav_active($current, array $files) {
        return in_array($current, $files, true) ? 'is-active' : '';
    }
}

$legacy_links = [
    ['label' => 'Inicio', 'href' => './index.php', 'active' => ['index.php']],
];

if ($__legacyCanObras) {
    $legacy_links[] = ['label' => 'Obras', 'href' => './obras.php', 'active' => ['obras.php']];
}

// Solicitud funcional: ocultar modulos Requisiciones y Presiones en navbar legacy.

if ($__legacyCanCatalogos) {
    $legacy_links[] = ['label' => 'Catalogos', 'href' => './menu_catalago.php', 'active' => ['menu_catalago.php', 'catalago.php', 'bancos.php', 'agregar_proveedor.php', 'proveedores.php']];
}

if ($__legacyCanDireccion) {
    $legacy_links[] = ['label' => 'Direccion', 'href' => './direccion.php', 'active' => ['direccion.php', 'reportes_kpi.php']];
}

if ($__legacyShowAdmin) {
    $legacy_links[] = ['label' => 'Personal', 'href' => './admin.php', 'active' => ['admin.php']];
}
>>>>>>> Stashed changes
?>
<nav class="navbar app-navbar">
    <div class="container-fluid legacy-navbar-shell">
        <div class="dropdown">
            <button class="sidebar-toggle-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="sidebar-toggle-icon" aria-hidden="true">
                    <span class="sidebar-toggle-line"></span>
                    <span class="sidebar-toggle-line"></span>
                    <span class="sidebar-toggle-line"></span>
                </span>
                ACCIONES
            </button>
            <div class="dropdown-menu legacy-actions-menu shadow">
                <?php foreach ($legacy_actions as $__action):
                    $__label = htmlspecialchars((string)($__action['label'] ?? 'Accion'));
                    $__href = htmlspecialchars((string)($__action['href'] ?? '#'));
                    $__class = (string)($__action['class'] ?? 'tf-nav-link');
                    $__danger = strpos($__class, 'tf-nav-danger') !== false;
                ?>
                <a href="<?= $__href ?>" class="dropdown-item legacy-action-item<?= $__danger ? ' text-danger' : '' ?>"><?= $__label ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="./index.php" class="navbar-brand legacy-navbar-brand">
            <span class="legacy-navbar-logo">TF</span>
            <span class="legacy-navbar-title">The Fuentes Corporation Workspace</span>
        </a>
        <div class="legacy-navbar-actions-placeholder" aria-hidden="true">
            <span class="legacy-navbar-role"><?= htmlspecialchars((string)($__legacyUser['role']['name'] ?? 'Usuario')) ?></span>
        </div>
    </div>
</nav>
