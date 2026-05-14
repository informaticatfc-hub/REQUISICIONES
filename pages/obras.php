<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

// RBAC: requiere permiso de ver obras
if (!tf_has_permission('obras.view', $__user)) {
    tf_abort(403, 'No tienes permiso para acceder a esta seccion');
}

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Residente';
$usuario_rolCode = $__user['role']['code'] ?? 'residente';
$usuario_perms   = $__user['permissions']  ?? [];
$usuario_dirAcc  = (int)($__user['user_directionAcess'] ?? 0);

// Obra activa: por GET, por sessionStorage (JS) o fallback al menu
$obraIdParam = isset($_GET['obra']) ? (int)$_GET['obra'] : 0;

$tf_page_title     = 'Menu de obra';
$tf_active_nav     = 'obras';
$tf_breadcrumb     = [
    ['Inicio', './index.php'],
    ['Obras',  './index.php'],
    ['Menu',   '#'],
];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true) || $usuario_dirAcc === 1;
$tf_show_admin     = $usuario_rolCode === 'admin';
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

<div id="AppObras" class="tf-page-inner">

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Obra activa</span>
            <h1 class="tf-page-title" v-cloak>
                {{ obras.length ? obras[0].obras_nombre : 'Cargando obra...' }}
            </h1>
            <p class="tf-page-lead">
                Selecciona el modulo con el que deseas trabajar en esta obra.
            </p>
            <div class="tf-chip tf-chip-success mt-2" v-cloak>
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
            <div class="tf-kpi-value" style="font-size:1.25rem" v-cloak>
                {{ obras.length ? obras[0].obras_nombre : '—' }}
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
            <div class="tf-kpi-value" v-cloak>{{ obrasLista.length }}</div>
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
                        @click="enterRequisiciones"
                        :disabled="!can('requisiciones.view')">
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
                        @click="enterPresiones"
                        :disabled="!can('presiones.view')">
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

    // Recuperar obra activa: prioridad ?obra= -> sessionStorage -> 0
    var __obraActiva = 0;
    try {
        var fromUrl = new URLSearchParams(window.location.search).get('obra');
        if (fromUrl) __obraActiva = parseInt(fromUrl, 10) || 0;
        if (!__obraActiva) {
            var fromSS = sessionStorage.getItem("obraActiva");
            if (fromSS) __obraActiva = parseInt(fromSS, 10) || 0;
        }
    } catch (e) {}

    if (__obraActiva) {
        try { sessionStorage.setItem("obraActiva", String(__obraActiva)); } catch(e){}
    } else {
        // Sin obra activa -> volver al menu de obras
        window.location.replace(url2 + "/index.php");
    }

    new Vue({
        el: "#AppObras",
        data: {
            users: [],
            obras: [],
            obrasLista: [],
            NameUser: (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name) || ""
        },
        methods: {
            can: function(code) { return window.TF && window.TF.can ? window.TF.can(code) : true; },
            consultarUsuario: function () {
                axios.post(url, { accion: 1 }).then(function(r){
                    this.users = r.data || [];
                    if (this.users[0] && this.users[0].user_name) this.NameUser = this.users[0].user_name;
                }.bind(this)).catch(function(){ console.warn("No se pudo cargar usuario"); });
            },
            infoObraActiva: function (id) {
                axios.post(url, { accion: 3, obra: id }).then(function(r){
                    this.obras = r.data || [];
                }.bind(this)).catch(function(){ console.warn("No se pudo cargar la obra"); });
            },
            listarObras: function () {
                axios.post(url, { accion: 2, modo: "recientes", limite: 12 }).then(function(r){
                    this.obrasLista = r.data || [];
                }.bind(this));
            },
            enterRequisiciones: function () { window.location.href = url2 + "/requisiciones.php"; },
            enterPresiones:     function () { window.location.href = url2 + "/presiones.php"; },
            irDireecion:        function () { window.location.href = url2 + "/direccion.php"; },
            irMenuCatalago:     function () { window.location.href = url2 + "/menu_catalago.php"; }
        },
        created: function () {
            this.listarObras();
            this.infoObraActiva(__obraActiva);
            this.consultarUsuario();
        }
    });
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
