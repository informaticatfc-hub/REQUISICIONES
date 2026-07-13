<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['presiones.view'], 'No tienes permiso para ver presiones');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Usuario';
$usuario_rolCode = $__user['role']['code'] ?? 'usuario';
$usuario_perms   = $__user['permissions']  ?? [];

$canCreatePresion = tf_has_permission('presiones.create', $__user);

$tf_extra_head = '<style>[x-cloak]{display:none!important;}</style>';
$tf_page_title     = 'Presiones';
$tf_active_nav     = 'presiones';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Presiones', '#']];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true);
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppPresion" class="tf-page-inner" x-data="presionesApp()" x-init="init()" x-cloak>
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Seguimiento semanal</span>
            <h1 class="tf-page-title">Presiones <span x-show="obras.length">- <span x-text="obras.length ? obras[0].obras_nombre : ''"></span></span></h1>
            <p class="tf-page-lead">Gestiona las presiones de pago de la obra activa.</p>
        </div>
        <?php if ($canCreatePresion): ?>
        <div class="tf-page-header-actions">
            <button type="button" class="tf-btn tf-btn-primary" x-on:click="NewPression">
                <i class="bi bi-plus-lg"></i> Agregar Presion
            </button>
        </div>
        <?php endif; ?>
    </header>

    <section class="tf-kpi-grid">
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Total presiones</span></div>
            <div class="tf-kpi-value" x-text="totalPresiones"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Pendientes</span></div>
            <div class="tf-kpi-value" x-text="pendientesCount"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head"><span class="tf-kpi-label">Autorizadas</span></div>
            <div class="tf-kpi-value" x-text="autorizadasCount"></div>
        </article>
    </section>

    <!-- Tabs: Todas / Pendientes de pago -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='todas'}" x-on:click="setActiveTab('todas')">
                <i class="bi bi-list-ul"></i> Todas
                <span class="badge bg-secondary ms-1" x-text="totalPresiones"></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" x-bind:class="{active: activeTab==='pendientes'}" x-on:click="setActiveTab('pendientes')">
                <i class="bi bi-hourglass-split"></i> Pendientes de pago
                <span class="badge bg-warning text-dark ms-1" x-show="autorizadasCount" x-text="autorizadasCount"></span>
            </button>
        </li>
    </ul>

    <section class="tf-card" x-show="activeTab==='todas'">
        <div class="p-3 border-bottom">
            <input x-model="searchText" x-on:input="onSearchInput"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar por nombre, alias, semana o día..."
                   style="max-width:320px">
        </div>
        <div class="tf-card-body p-0" style="overflow-x:auto;">
            <table id="example" class="tf-admin-table w-100">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Alias</th>
                        <th>Semana</th>
                        <th>Dia</th>
                        <th>Estatus</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(presion,indice) in presiones" :key="presion.presiones_id + '-' + indice">
                    <tr>
                        <td x-text="presion.presiones_nombre"></td>
                        <td x-text="presion.presiones_alias"></td>
                        <td x-text="presion.presiones_semana"></td>
                        <td x-text="presion.presiones_dia"></td>
                        <td>
                            <span class="tf-status tf-status-inactive" x-show="presion.presiones_estatus === 'PENDIENTE'">En revisión</span>
                            <span class="tf-status tf-status-active" x-show="presion.presiones_estatus === 'AUTORIZADO'">Autorizada</span>
                            <span class="tf-status" style="background:#e2e8f0;color:#475569" x-show="presion.presiones_estatus === 'CERRADA'">Cerrada</span>
                        </td>
                        <td style="text-align:right">
                            <div class="btn-group dropdown" role="group">
                                <button type="button" class="tf-btn tf-btn-sm tf-btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <?php if ($canCreatePresion): ?>
                                        <a class="dropdown-item" href="#" x-show="presion.presiones_estatus == 'PENDIENTE'" x-on:click.prevent="ConsultarPresion(presion.presiones_id,1,0,0)">
                                            Enlazar Requisiciones
                                        </a>
                                        <a class="dropdown-item disabled" href="#" x-show="presion.presiones_estatus != 'PENDIENTE'">
                                            Enlazar Requisiciones
                                        </a>
                                        <?php endif; ?>
                                    </li>
                                    <li><a class="dropdown-item" href="#" x-on:click.prevent="ConsultarPresion(presion.presiones_id,2,presion.presiones_semana,presion.presiones_dia)">Detalles de la Presion</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Tab: Pendientes de pago (AUTORIZADO) -->
    <section class="tf-card" x-show="activeTab==='pendientes'">
        <div class="p-3 border-bottom">
            <input x-model="searchText" x-on:input="onSearchInput"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar pendientes por nombre, alias, semana o día..."
                   style="max-width:320px">
        </div>
        <div class="tf-card-body p-0" style="overflow-x:auto;">
            <div x-show="!pendientesDePago.length" class="p-4 text-center text-muted">
                <i class="bi bi-check-circle" style="font-size:2rem"></i>
                <p class="mt-2 mb-0">Sin presiones pendientes de pago</p>
            </div>
            <table class="tf-admin-table w-100" x-show="pendientesDePago.length">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Alias</th>
                        <th>Semana</th>
                        <th>Dia</th>
                        <th style="text-align:right">Ir a detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(p, index) in pendientesDePago" :key="p.presiones_id + '-pend-' + index">
                    <tr>
                        <td x-text="p.presiones_nombre"></td>
                        <td x-text="p.presiones_alias"></td>
                        <td x-text="p.presiones_semana"></td>
                        <td x-text="p.presiones_dia"></td>
                        <td style="text-align:right">
                            <button class="tf-btn tf-btn-sm tf-btn-primary"
                                    x-on:click.prevent="ConsultarPresion(p.presiones_id,2,p.presiones_semana,p.presiones_dia)">
                                <i class="bi bi-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top">
        <small class="text-muted" x-text="'Página ' + currentPage + ' de ' + totalPages + ' · ' + totalRows + ' registros'"></small>
        <div class="btn-group">
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(1)" x-bind:disabled="currentPage <= 1">Primera</button>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage - 1)" x-bind:disabled="currentPage <= 1">Anterior</button>
            <template x-for="p in pageRange" :key="'pr-page-'+p">
            <button
                    class="tf-btn tf-btn-sm"
                    x-bind:class="p === currentPage ? 'tf-btn-primary' : 'tf-btn-ghost'"
                    x-on:click="goToPage(p)" x-text="p"></button>
            </template>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage + 1)" x-bind:disabled="currentPage >= totalPages">Siguiente</button>
            <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(totalPages)" x-bind:disabled="currentPage >= totalPages">Última</button>
        </div>
    </div>
</div>

<?php
$tf_use_vue = false;
$tf_use_axios = true;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/js/presiones.js"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
