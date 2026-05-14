<?php
// Topbar unificada para vistas legacy.
// Mantiene la misma estructura visual que Inicio y cambia solo las acciones por rol.

require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__legacyPdo = (new Conexion())->Conectar();
$__legacyUser = tf_current_user($__legacyPdo);
$__legacyRole = strtolower((string)($__legacyUser['role']['code'] ?? ''));
$__legacyDirAcc = (int)($__legacyUser['user_directionAcess'] ?? 0);
$__legacyIsDirector = in_array($__legacyRole, ['director', 'direccion', 'admin'], true) || $__legacyDirAcc === 1;
$__legacyPerms = $__legacyUser['permissions'] ?? [];
$__legacyName = (string)($__legacyUser['user_name'] ?? $__legacyUser['user_nameUser'] ?? 'Usuario');
$__legacyRoleName = (string)($__legacyUser['role']['name'] ?? 'Usuario');
$__legacyShowAdmin = in_array('admin.users.view', $__legacyPerms, true) || in_array($__legacyRole, ['admin', 'director'], true) || $__legacyDirAcc === 1;
$__legacyCurrent = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

if (!function_exists('tf_legacy_nav_active')) {
    function tf_legacy_nav_active($current, array $files) {
        return in_array($current, $files, true) ? 'is-active' : '';
    }
}

$legacy_links = $__legacyIsDirector
    ? [
        ['label' => 'Inicio', 'href' => './index.php', 'active' => ['index.php']],
        ['label' => 'Presiones', 'href' => './all_presiones.php', 'active' => ['all_presiones.php', 'presiones_detalles.php']],
        ['label' => 'Direccion', 'href' => './direccion.php', 'active' => ['direccion.php', 'reportes_kpi.php']],
      ]
    : [
        ['label' => 'Inicio', 'href' => './index.php', 'active' => ['index.php']],
        ['label' => 'Obras', 'href' => './obras.php', 'active' => ['obras.php']],
        ['label' => 'Requisiciones', 'href' => './requisiciones.php', 'active' => ['requisiciones.php', 'hojas_requisicion.php', 'items_requisicion.php', 'nueva_hoja.php', 'nueva_requisicion.php', 'enlazar_requisiciones.php']],
        ['label' => 'Presiones', 'href' => './presiones.php', 'active' => ['presiones.php', 'presiones_detalles.php']],
        ['label' => 'Catalogos', 'href' => './menu_catalago.php', 'active' => ['menu_catalago.php', 'catalago.php', 'bancos.php', 'agregar_proveedor.php', 'proveedores.php']],
      ];

if ($__legacyShowAdmin) {
    $legacy_links[] = ['label' => 'Personal', 'href' => './admin.php', 'active' => ['admin.php']];
}
?>
<nav class="navbar app-navbar">
    <div class="container-fluid legacy-navbar-shell legacy-navbar-shell-unified">
        <a href="./index.php" class="navbar-brand legacy-navbar-brand legacy-navbar-brand-unified">
            <span class="legacy-navbar-logo">TF</span>
            <span class="legacy-navbar-brand-copy">
                <strong>The Fuentes</strong>
                <small>Workspace</small>
            </span>
        </a>
        <div class="legacy-navbar-links" role="navigation" aria-label="Navegacion principal">
            <?php foreach ($legacy_links as $__link): ?>
            <a href="<?= htmlspecialchars($__link['href']) ?>"
               class="legacy-navbar-link <?= tf_legacy_nav_active($__legacyCurrent, $__link['active']) ?>">
                <?= htmlspecialchars($__link['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="legacy-navbar-actions-placeholder">
            <a href="./closeSesion.php" class="legacy-navbar-logout" title="Cerrar sesion">Salir</a>
            <span class="legacy-navbar-userchip">
                <span class="legacy-navbar-avatar"><?= htmlspecialchars(strtoupper(substr($__legacyName, 0, 1))) ?></span>
                <span class="legacy-navbar-usertext">
                    <strong><?= htmlspecialchars($__legacyName) ?></strong>
                    <small><?= htmlspecialchars($__legacyRoleName) ?></small>
                </span>
            </span>
        </div>
    </div>
</nav>
