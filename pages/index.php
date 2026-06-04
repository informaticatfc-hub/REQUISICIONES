<?php
include_once '../validarSesion.php';

// ----------------------------------------------------------------
// Datos del usuario en sesion + RBAC
// ----------------------------------------------------------------
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

$usuario_sesion = $__user['user_nameUser'] ?? ($_SESSION['Usuario'] ?? '');
$usuario_nombre = $__user['user_name']     ?? $usuario_sesion;
$usuario_rol    = $__user['role']['name']  ?? 'Residente';
$usuario_rolCode= $__user['role']['code']  ?? 'residente';
$usuario_dirAcc = tf_user_has_direction_access($__user) ? 1 : 0;
$usuario_perms  = $__user['permissions']   ?? [];

// ----------------------------------------------------------------
// Variables del layout
// ----------------------------------------------------------------
$tf_page_title     = 'Inicio';
$tf_active_nav     = 'inicio';
$tf_breadcrumb     = [['Inicio', './index.php']];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin', 'director'], true) || $usuario_dirAcc === 1;
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)$usuario_sesion;
$tf_es_director    = ($usuario_rolCode === 'director') || ($usuario_dirAcc === 1);
$tf_is_lector      = ($usuario_rolCode === 'lector');
$canObras          = tf_has_permission('obras.view', $__user);
$canRequisiciones  = tf_has_permission('requisiciones.view', $__user);
$canRequisicionesCreate = tf_has_permission('requisiciones.create', $__user);
$canCatalogos      = tf_has_permission('catalogos.view', $__user);
$canPresiones      = tf_has_permission('presiones.view', $__user);
$canDireccion      = tf_has_permission('direccion.view', $__user) || tf_has_permission('presiones.authorize', $__user) || $tf_show_direccion;

// La sub-bar queda limpia; las acciones ya se concentran en el navbar principal.
$tf_subbar_extra = '';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppIndex" class="tf-page-inner" x-data="indexApp()" x-init="init()" x-cloak>

    <!-- ============================================================
         Page header
         ============================================================ -->
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel principal</span>
            <h1 class="tf-page-title">Bienvenido, <span x-text="NameUser || '<?= htmlspecialchars($usuario_nombre) ?>'"></span></h1>
            <?php if ($tf_is_lector): ?>
            <p class="tf-page-lead">
                Dashboard de lectura: solo accesos a módulos permitidos para consulta y exportación.
            </p>
            <?php elseif ($tf_es_director): ?>
            <p class="tf-page-lead">
                Centro directivo para autorizacion, cierre de presiones y reportes KPI.
            </p>
            <?php else: ?>
            <p class="tf-page-lead">
                Resumen del espacio de trabajo. Selecciona una obra o modulo para comenzar.
            </p>
            <?php endif; ?>
        </div>
        
    </header>

    <?php if ($tf_is_lector): ?>
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title"><i class="bi bi-compass"></i> Accesos permitidos</h2>
                <p class="tf-card-sub">Vista simplificada para rol lector</p>
            </div>
        </header>
        <div class="tf-card-body">
            <div class="tf-module-grid">
                <?php if ($canObras || $canRequisiciones || $canPresiones): ?>
                <a href="./obras.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-primary">
                        <i class="bi bi-buildings"></i>
                    </span>
                    <span class="tf-module-label">Obras</span>
                    <span class="tf-module-sub">Consulta requisiciones y presiones de tus obras asignadas.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>

                <?php if ($canCatalogos): ?>
                <a href="./menu_catalago.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-success">
                        <i class="bi bi-collection"></i>
                    </span>
                    <span class="tf-module-label">Catálogos</span>
                    <span class="tf-module-sub">Consulta proveedores y bancos en modo lectura.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>

                <?php if ($canDireccion): ?>
                <a href="./reportes_kpi.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-warning">
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
                    <span class="tf-module-label">Reportes KPI</span>
                    <span class="tf-module-sub">Consulta indicadores globales y exporta reportes.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- ============================================================
         KPI grid
         ============================================================ -->
    <section class="tf-kpi-grid" aria-label="Resumen rapido">
        <?php if ($canDireccion): ?>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                <span class="tf-kpi-label">Autorizacion</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Todas las presiones</div>
            <div class="tf-kpi-foot">
                <a href="./all_presiones.php" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Abrir <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-graph-up-arrow"></i>
                </span>
                <span class="tf-kpi-label">Reportes KPI</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Gastos por fecha</div>
            <div class="tf-kpi-foot">
                <a href="./reportes_kpi.php" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Abrir <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($canObras): ?>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-building"></i>
                </span>
                <span class="tf-kpi-label">Obras activas recientes</span>
            </div>
            <div class="tf-kpi-value" x-text="obras.length"></div>
            <div class="tf-kpi-foot">
                <span>Mostrando las 12 mas recientes</span>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($canDireccion): ?>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                <span class="tf-kpi-label">Direccion</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Presiones pendientes</div>
            <div class="tf-kpi-foot">
                <a href="./direccion.php" x-on:click.prevent="irDireecion" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Autorizar <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>
        <?php endif; ?>

    </section>

    <!-- ============================================================
         Lista de obras + acciones rapidas
         ============================================================ -->
    <div class="tf-grid-2">
        <!-- Obras -->
        <?php if ($canObras): ?>
        <section class="tf-card">
            <header class="tf-card-header">
                <div>
                    <h2 class="tf-card-title">
                        <i class="bi bi-buildings"></i> Obras activas recientes
                    </h2>
                    <p class="tf-card-sub"><span x-text="obras.length"></span> obras encontradas</p>
                </div>
                <a href="#" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Ver todas <i class="bi bi-arrow-right"></i>
                </a>
            </header>

            <div class="tf-card-body p-0">
                <div x-show="!obras.length" class="tf-empty" x-cloak>
                    <i class="bi bi-inbox"></i>
                    <p>No hay obras registradas para mostrar.</p>
                </div>

                <ul class="tf-list" x-cloak>
                    <template x-for="obra in obras" :key="obra.obras_id">
                    <li class="tf-obra-row">
                        <div class="tf-obra-row-main">
                            <span class="tf-obra-row-icon">
                                <i class="bi bi-building"></i>
                            </span>
                            <div class="tf-obra-row-text">
                                <strong x-text="obra.obras_nombre"></strong>
                                <small>Obra activa</small>
                            </div>
                        </div>
                        <button type="button"
                                class="tf-btn tf-btn-primary tf-btn-sm"
                                x-on:click="irObra(obra.obras_id)">
                            <i class="bi bi-box-arrow-up-right"></i> Abrir
                        </button>
                    </li>
                    </template>
                </ul>
            </div>
        </section>
        <?php endif; ?>

        <!-- Panel lateral: acciones rapidas -->
        <aside class="tf-side-col">
            <section class="tf-card">
                <header class="tf-card-header">
                    <h2 class="tf-card-title">
                        <i class="bi bi-lightning-charge-fill"></i> Acciones rapidas
                    </h2>
                </header>
                <div class="tf-card-body">
                    <div class="tf-quick-grid">
                        <?php if ($canDireccion): ?>
                        <a href="./all_presiones.php" class="tf-quick-btn">
                            <i class="bi bi-briefcase-fill"></i>
                            <span>Autorizacion</span>
                        </a>
                        <a href="./reportes_kpi.php" class="tf-quick-btn">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Reportes KPI</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        </aside>
    </div>
    <?php endif; ?>

</div>

<?php
// JS inline: carga datos via Vue 2, expone al layout
$tf_inline_script = <<<JS
    // El usuario ya viene de la sesion PHP (TF_CONTEXT); ya no dependemos de localStorage
    var url  = "../api/crud_index.php";
    var url2 = ".";

    function indexApp() {
        return {
            users: [],
            obras: [],
            NameUser: (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name) || "",
            consultarUsuario: function () {
                axios.post(url, { accion: 1 }).then(function (response) {
                    this.users = response.data || [];
                    if (this.users[0] && this.users[0].user_name) {
                        this.NameUser = this.users[0].user_name;
                    }
                }.bind(this)).catch(function () {
                    console.warn("No se pudo consultar el usuario");
                });
            },
            listarObras: function () {
                axios.post(url, { accion: 2, modo: "recientes", limite: 12 }).then(function (response) {
                    this.obras = response.data || [];
                }.bind(this)).catch(function () {
                    console.warn("No se pudo listar obras");
                });
            },
            irObra: function (idObra) {
                try { sessionStorage.setItem("obraActiva", String(idObra)); } catch (e) {}
                try { localStorage.setItem("obraActiva", String(idObra)); } catch (e) {}
                window.location.href = url2 + "/obras.php?obra=" + encodeURIComponent(idObra);
            },
            irDireecion: function () {
                window.location.href = url2 + "/direccion.php";
            },
            irMenuCatalago: function () {
                window.location.href = url2 + "/menu_catalago.php";
            },
            init: function () {
                this.listarObras();
                this.consultarUsuario();
            }
        }
    }
JS;

$tf_use_vue = false;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';

include __DIR__ . '/../includes/layout_bottom.php';
?>
