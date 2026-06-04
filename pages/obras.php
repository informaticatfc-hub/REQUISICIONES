<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['obras.view'], 'No tienes permiso para acceder a esta seccion');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Residente';
$usuario_rolCode = $__user['role']['code'] ?? 'residente';
$usuario_perms   = $__user['permissions']  ?? [];
$usuario_dirAcc  = tf_user_has_direction_access($__user) ? 1 : 0;

// Obra activa: por GET, por sessionStorage (JS) o fallback al menu
$obraIdParam = isset($_GET['obra']) ? (int)$_GET['obra'] : 0;

$tf_page_title     = 'Menu de obra';
$tf_active_nav     = 'obras';
$tf_breadcrumb     = [
    ['Inicio', './index.php'],
    ['Obras',  '#'],
];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true) || $usuario_dirAcc === 1;
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

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppObras" class="tf-page-inner" x-data="obrasApp()" x-init="init()" x-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Obra activa</span>
            <h1 class="tf-page-title" x-cloak x-text="obras.length ? obras[0].obras_nombre : 'Cargando obra...'"></h1>
            <p class="tf-page-lead">
                Selecciona el modulo con el que deseas trabajar en esta obra.
            </p>
            <div class="tf-chip tf-chip-success mt-2" x-cloak>
                <span class="tf-chip-dot"></span> Obra activa
            </div>
        </div>
        <div class="tf-page-header-actions">
            <button type="button" class="tf-btn tf-btn-secondary"
                    onclick="window.location.href='./index.php'">
                <i class="bi bi-arrow-repeat"></i> Cambiar de obra
            </button>
        </div>
    </header>

    <!-- KPI rapido de la obra -->
    <section class="tf-kpi-grid" aria-label="Resumen de la obra">
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-building"></i>
                </span>
                <span class="tf-kpi-label">Nombre de la obra</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.25rem" x-cloak>
                <span x-text="obras.length ? obras[0].obras_nombre : '—'"></span>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success">
                    <i class="bi bi-grid"></i>
                </span>
                <span class="tf-kpi-label">Modulos disponibles</span>
            </div>
            <div class="tf-kpi-value">2</div>
            <div class="tf-kpi-foot">
                <span>Presiones y Requisiciones</span>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-clock-history"></i>
                </span>
                <span class="tf-kpi-label">Obras recientes</span>
            </div>
            <div class="tf-kpi-value" x-cloak x-text="obrasLista.length"></div>
            <div class="tf-kpi-foot">
                <span>en el panel principal</span>
            </div>
        </article>
    </section>

    <!-- Modulos de la obra -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-collection-fill"></i> Modulos de la obra
                </h2>
                <p class="tf-card-sub">Elige el modulo donde deseas trabajar</p>
            </div>
        </header>
        <div class="tf-card-body">
            <div class="tf-module-grid">

                <!-- Requisiciones -->
                <button type="button"
                        class="tf-module-card"
                    x-on:click="enterRequisiciones"
                    x-bind:disabled="!can('requisiciones.view')">
                    <span class="tf-module-icon tf-module-icon-primary">
                        <i class="bi bi-receipt"></i>
                    </span>
                    <span class="tf-module-label">Requisiciones</span>
                    <span class="tf-module-sub">Crea y consulta las requisiciones de compra</span>
                    <span class="tf-module-cta">
                        Abrir modulo <i class="bi bi-arrow-right"></i>
                    </span>
                </button>

                <!-- Presiones -->
                <button type="button"
                        class="tf-module-card"
                    x-on:click="enterPresiones"
                    x-bind:disabled="!can('presiones.view')">
                    <span class="tf-module-icon tf-module-icon-success">
                        <i class="bi bi-cash-coin"></i>
                    </span>
                    <span class="tf-module-label">Presiones</span>
                    <span class="tf-module-sub">Gestiona las presiones de pago de la obra</span>
                    <span class="tf-module-cta">
                        Abrir modulo <i class="bi bi-arrow-right"></i>
                    </span>
                </button>

            </div>
        </div>
    </section>

</div>

<?php
$tf_inline_script = <<<JS
    var url  = "../api/crud_obras.php";
    var url2 = ".";

    // Recuperar obra activa: prioridad ?obra= -> sessionStorage -> localStorage -> 0
    var __obraActiva = 0;
    try {
        var fromUrl = new URLSearchParams(window.location.search).get('obra');
        if (fromUrl) __obraActiva = parseInt(fromUrl, 10) || 0;
        if (!__obraActiva) {
            var fromSS = sessionStorage.getItem("obraActiva");
            if (fromSS) __obraActiva = parseInt(fromSS, 10) || 0;
        }
        if (!__obraActiva) {
            var fromLS = localStorage.getItem("obraActiva");
            if (fromLS) __obraActiva = parseInt(fromLS, 10) || 0;
        }
    } catch (e) {}

    if (__obraActiva) {
        try { sessionStorage.setItem("obraActiva", String(__obraActiva)); } catch(e){}
        try { localStorage.setItem("obraActiva", String(__obraActiva)); } catch(e){}
    } else {
        // Sin obra activa -> volver al menu de obras
        window.location.replace(url2 + "/index.php");
    }

    function obrasApp() {
        return {
            users: [],
            obras: [],
            obrasLista: [],
            NameUser: (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name) || "",
            can: function(code) { return window.TF && window.TF.can ? window.TF.can(code) : true; },
            consultarUsuario: async function () {
                try {
                    var r = await axios.post(url, { accion: 1 });
                    this.users = r.data || [];
                    if (this.users[0] && this.users[0].user_name) this.NameUser = this.users[0].user_name;
                } catch (e) {
                    console.warn("No se pudo cargar usuario");
                }
            },
            infoObraActiva: async function (id) {
                try {
                    var r = await axios.post(url, { accion: 3, obra: id });
                    this.obras = r.data || [];
                } catch (e) {
                    console.warn("No se pudo cargar la obra");
                }
            },
            listarObras: async function () {
                var r = await axios.post(url, { accion: 2, modo: "recientes", limite: 12 });
                this.obrasLista = r.data || [];
            },
            enterRequisiciones: function () { window.location.href = url2 + "/requisiciones.php"; },
            enterPresiones:     function () { window.location.href = url2 + "/presiones.php"; },
            irDireecion:        function () { window.location.href = url2 + "/direccion.php"; },
            irMenuCatalago:     function () { window.location.href = url2 + "/menu_catalago.php"; },
            init: function () {
                this.listarObras();
                this.infoObraActiva(__obraActiva);
                this.consultarUsuario();
            }
        };
    }
JS;

$tf_use_vue = false;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';

include __DIR__ . '/../includes/layout_bottom.php';
?>
