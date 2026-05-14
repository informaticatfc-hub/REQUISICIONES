<?php
// Navbar unificada para vistas legacy (app-navbar + acciones por rol)
// Variables opcionales del caller:
// - $legacy_actions: array [['label'=>'Inicio','href'=>'./index.php'], ...]
// - $legacy_show_actions: bool (default true)

require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__legacyPdo = (new Conexion())->Conectar();
$__legacyUser = tf_current_user($__legacyPdo);
$__legacyRole = strtolower((string)($__legacyUser['role']['code'] ?? ''));
$__legacyDirAcc = (int)($__legacyUser['user_directionAcess'] ?? 0);
$__legacyIsDirector = $__legacyRole === 'director' || $__legacyRole === 'admin' || $__legacyDirAcc === 1;

$legacy_show_actions = isset($legacy_show_actions) ? (bool)$legacy_show_actions : true;

if (!isset($legacy_actions) || !is_array($legacy_actions) || !count($legacy_actions)) {
    if ($__legacyIsDirector) {
        $legacy_actions = [
            ['label' => 'Menu Direccion', 'href' => './direccion.php', 'variant' => 'secondary'],
            ['label' => 'Autorizacion Presiones', 'href' => './all_presiones.php', 'variant' => 'secondary'],
            ['label' => 'Reportes KPI', 'href' => './reportes_kpi.php', 'variant' => 'secondary'],
            ['label' => 'Inicio', 'href' => './index.php', 'variant' => 'secondary'],
            ['label' => 'Cerrar Sesion', 'href' => './closeSesion.php', 'variant' => 'danger ms-auto'],
        ];
    } else {
        $legacy_actions = [
            ['label' => 'Inicio', 'href' => './index.php', 'variant' => 'secondary'],
            ['label' => 'Obras', 'href' => './obras.php', 'variant' => 'secondary'],
            ['label' => 'Requisiciones', 'href' => './requisiciones.php', 'variant' => 'secondary'],
            ['label' => 'Presiones', 'href' => './presiones.php', 'variant' => 'secondary'],
            ['label' => 'Catalogos', 'href' => './menu_catalago.php', 'variant' => 'secondary'],
            ['label' => 'Cerrar Sesion', 'href' => './closeSesion.php', 'variant' => 'danger ms-auto'],
        ];
    }
}
?>
<nav class="navbar app-navbar">
    <div class="container-fluid">
        <span class="navbar-brand text-light text-center w-100 fw-bolder">The Fuentes Corporation Workspace</span>
    </div>
</nav>
<?php if ($legacy_show_actions): ?>
<div class="director-shortcuts">
    <?php foreach ($legacy_actions as $__action):
        $__label = htmlspecialchars((string)($__action['label'] ?? 'Accion'));
        $__href = htmlspecialchars((string)($__action['href'] ?? '#'));
        $__variant = trim((string)($__action['variant'] ?? 'secondary'));
        $__class = 'btn btn-' . $__variant;
    ?>
    <a class="<?= $__class ?>" href="<?= $__href ?>"><?= $__label ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
