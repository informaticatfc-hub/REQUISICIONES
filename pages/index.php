<?php
include_once '../validarSesion.php';

// ----------------------------------------------------------------
// Datos del usuario en sesion (preparado para RBAC en Fase 2)
// ----------------------------------------------------------------
$usuario_sesion = $_SESSION['Usuario']           ?? '';
$usuario_nombre = $_SESSION['UsuarioNombre']     ?? $usuario_sesion;
$usuario_rol    = $_SESSION['UsuarioRol']        ?? 'Residente';
$usuario_dirAcc = (int)($_SESSION['UsuarioDirAccess'] ?? 0);

// ----------------------------------------------------------------
// Variables del layout
// ----------------------------------------------------------------
$tf_page_title     = 'Inicio';
$tf_active_nav     = 'inicio';
$tf_breadcrumb     = [['Inicio', './index.php']];
$tf_user           = [
    'name'     => $usuario_nombre,
    'role'     => $usuario_rol,
    'initials' => '',
];
$tf_show_direccion = $usuario_dirAcc === 1;
$tf_show_admin     = ($usuario_rol === 'Admin' || $usuario_rol === 'admin');
$tf_show_subbar    = true;
$tf_user_id_js     = (string)$usuario_sesion;

// Acciones rapidas en la sub-bar
$tf_subbar_extra = '
    <div class="tf-subbar-actions">
        <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" onclick="window.TfLayout && window.TfLayout.openCmd()">
            <i class="bi bi-search"></i> Buscar
        </button>
        <a href="./menu_catalago.php" class="tf-btn tf-btn-secondary tf-btn-sm">
            <i class="bi bi-collection"></i> Catalogos
        </a>
    </div>
';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppIndex" class="tf-page-inner">

    <!-- ============================================================
         Page header
         ============================================================ -->
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel principal</span>
            <h1 class="tf-page-title">Bienvenido, <span v-cloak>{{ NameUser || '<?= htmlspecialchars($usuario_nombre) ?>' }}</span></h1>
            <p class="tf-page-lead">
                Resumen del espacio de trabajo. Selecciona una obra o modulo para comenzar.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <a href="./nueva_requisicion.php" class="tf-btn tf-btn-primary">
                <i class="bi bi-file-earmark-plus"></i> Nueva requisicion
            </a>
        </div>
    </header>

    <!-- ============================================================
         KPI grid
         ============================================================ -->
    <section class="tf-kpi-grid" aria-label="Resumen rapido">
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-building"></i>
                </span>
                <span class="tf-kpi-label">Obras activas recientes</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ obras.length }}</div>
            <div class="tf-kpi-foot">
                <span>Mostrando las 12 mas recientes</span>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success">
                    <i class="bi bi-collection"></i>
                </span>
                <span class="tf-kpi-label">Catalogos</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Proveedores y Bancos</div>
            <div class="tf-kpi-foot">
                <a href="./menu_catalago.php" @click.prevent="irMenuCatalago" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Abrir <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>

        <article class="tf-kpi" v-if="users.length && users[0].user_directionAcess == 1" v-cloak>
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                <span class="tf-kpi-label">Direccion</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Presiones pendientes</div>
            <div class="tf-kpi-foot">
                <a href="./direccion.php" @click.prevent="irDireecion" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Autorizar <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger">
                    <i class="bi bi-receipt"></i>
                </span>
                <span class="tf-kpi-label">Requisiciones</span>
            </div>
            <div class="tf-kpi-value" style="font-size:1.35rem">Modulo</div>
            <div class="tf-kpi-foot">
                <a href="./requisiciones.php" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Ver todas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>
    </section>

    <!-- ============================================================
         Lista de obras + acciones rapidas
         ============================================================ -->
    <div class="tf-grid-2">
        <!-- Obras -->
        <section class="tf-card">
            <header class="tf-card-header">
                <div>
                    <h2 class="tf-card-title">
                        <i class="bi bi-buildings"></i> Obras activas recientes
                    </h2>
                    <p class="tf-card-sub" v-cloak>{{ obras.length }} obras encontradas</p>
                </div>
                <a href="#" class="tf-btn tf-btn-ghost tf-btn-sm">
                    Ver todas <i class="bi bi-arrow-right"></i>
                </a>
            </header>

            <div class="tf-card-body p-0">
                <div v-if="!obras.length" class="tf-empty" v-cloak>
                    <i class="bi bi-inbox"></i>
                    <p>No hay obras registradas para mostrar.</p>
                </div>

                <ul class="tf-list" v-cloak>
                    <li v-for="obra in obras" :key="obra.obras_id" class="tf-obra-row">
                        <div class="tf-obra-row-main">
                            <span class="tf-obra-row-icon">
                                <i class="bi bi-building"></i>
                            </span>
                            <div class="tf-obra-row-text">
                                <strong>{{ obra.obras_nombre }}</strong>
                                <small>Obra activa</small>
                            </div>
                        </div>
                        <button type="button"
                                class="tf-btn tf-btn-primary tf-btn-sm"
                                @click="irObra(obra.obras_id)">
                            <i class="bi bi-box-arrow-up-right"></i> Abrir
                        </button>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Panel lateral: acciones rapidas + actividad -->
        <aside class="tf-side-col">
            <section class="tf-card">
                <header class="tf-card-header">
                    <h2 class="tf-card-title">
                        <i class="bi bi-lightning-charge-fill"></i> Acciones rapidas
                    </h2>
                </header>
                <div class="tf-card-body">
                    <div class="tf-quick-grid">
                        <a href="./nueva_requisicion.php" class="tf-quick-btn">
                            <i class="bi bi-file-earmark-plus"></i>
                            <span>Nueva requisicion</span>
                        </a>
                        <a href="./menu_catalago.php" class="tf-quick-btn" @click.prevent="irMenuCatalago">
                            <i class="bi bi-collection"></i>
                            <span>Catalogos</span>
                        </a>
                        <a href="./presiones.php" class="tf-quick-btn">
                            <i class="bi bi-cash-coin"></i>
                            <span>Presiones</span>
                        </a>
                        <?php if ($tf_show_direccion): ?>
                        <a href="./direccion.php" class="tf-quick-btn" @click.prevent="irDireecion">
                            <i class="bi bi-briefcase-fill"></i>
                            <span>Direccion</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="tf-card">
                <header class="tf-card-header">
                    <h2 class="tf-card-title">
                        <i class="bi bi-clock-history"></i> Actividad reciente
                    </h2>
                </header>
                <div class="tf-card-body">
                    <ul class="tf-timeline">
                        <li>
                            <span class="tf-timeline-dot tf-timeline-dot-primary"></span>
                            <div>
                                <strong>Bienvenido al nuevo workspace</strong>
                                <small>Diseno renovado v4.1 con soporte mobile</small>
                            </div>
                        </li>
                        <li>
                            <span class="tf-timeline-dot tf-timeline-dot-success"></span>
                            <div>
                                <strong>Sistema actualizado</strong>
                                <small>Bootstrap 5.3.3 + Bootstrap Icons</small>
                            </div>
                        </li>
                        <li>
                            <span class="tf-timeline-dot tf-timeline-dot-warning"></span>
                            <div>
                                <strong>Roles y permisos</strong>
                                <small>Proximamente: control granular por rol</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </aside>
    </div>

</div>

<?php
// JS inline: carga datos via Vue 2, expone al layout
$tf_inline_script = <<<JS
    // ID del usuario: prioriza session (TF_CONTEXT) sobre localStorage legacy
    var __tf_user_id = (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.id)
        || localStorage.getItem("NameUser");

    var url  = "../api/crud_index.php";
    var url2 = ".";

    new Vue({
        el: "#AppIndex",
        data: {
            users: [],
            obras: [],
            NameUser: (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name) || ""
        },
        methods: {
            consultarUsuario: function (user_id) {
                if (!user_id) return;
                axios.post(url, { accion: 1, id_user: user_id }).then(function (response) {
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
                try { localStorage.setItem("obraActiva", idObra); } catch (e) {}
                window.location.href = url2 + "/obras.php";
            },
            irDireecion: function () {
                window.location.href = url2 + "/direccion.php";
            },
            irMenuCatalago: function () {
                window.location.href = url2 + "/menu_catalago.php";
            }
        },
        created: function () {
            this.listarObras();
            this.consultarUsuario(__tf_user_id);
        }
    });
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
