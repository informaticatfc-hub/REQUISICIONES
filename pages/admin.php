<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_permission($__pdo, 'admin.users.view');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Administrador';
$usuario_rolCode = $__user['role']['code'] ?? 'admin';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title     = 'Administracion';
$tf_active_nav     = 'admin';
$tf_breadcrumb     = [
    ['Inicio', './index.php'],
    ['Administracion', '#'],
];
$tf_user = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true);
$tf_show_admin     = true;
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

$canCreate = tf_has_permission('admin.users.create', $__user);
$canEdit   = tf_has_permission('admin.users.edit',   $__user);
$canRole   = tf_has_permission('admin.roles.manage', $__user);
$canAudit  = tf_has_permission('admin.audit.view',   $__user);
$canAssignObra = tf_user_has_direction_access($__user);

$tf_subbar_extra = $canCreate
    ? '<div class="tf-subbar-actions">
           <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" onclick="window.tfAdminOpenCreate && window.tfAdminOpenCreate()">
               <i class="bi bi-person-plus"></i> Nuevo usuario
           </button>
       </div>'
    : '';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="tfAdmin" class="tf-page-inner" x-data="adminApp()" x-init="init()" x-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Panel de administracion</span>
            <h1 class="tf-page-title">Usuarios y roles</h1>
            <p class="tf-page-lead">
                Gestiona los accesos al workspace: crea usuarios, asigna roles y revisa la bitacora.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <?php if ($canCreate): ?>
            <button type="button" class="tf-btn tf-btn-primary" x-on:click="openCreate">
                <i class="bi bi-person-plus"></i> Nuevo usuario
            </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- KPIs -->
    <section class="tf-kpi-grid" aria-label="Resumen">
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-people-fill"></i>
                </span>
                <span class="tf-kpi-label">Usuarios totales</span>
            </div>
            <div class="tf-kpi-value" x-text="users.length"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
                <span class="tf-kpi-label">Activos</span>
            </div>
            <div class="tf-kpi-value" x-text="countActivos"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-shield-lock-fill"></i>
                </span>
                <span class="tf-kpi-label">Roles definidos</span>
            </div>
            <div class="tf-kpi-value" x-text="roles.length"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger">
                    <i class="bi bi-person-x-fill"></i>
                </span>
                <span class="tf-kpi-label">Inactivos</span>
            </div>
            <div class="tf-kpi-value" x-text="countInactivos"></div>
        </article>
    </section>

    <!-- Navegacion por pestanas -->
    <nav class="tf-admin-tabs" role="tablist" x-cloak>
        <button class="tf-tab-btn" x-bind:class="{active: activeTab==='usuarios'}" x-on:click="activeTab='usuarios'" role="tab">
            <i class="bi bi-people"></i> Usuarios
        </button>
        <?php if ($canRole): ?>
        <button class="tf-tab-btn" x-bind:class="{active: activeTab==='roles'}" x-on:click="setTab('roles')" role="tab">
            <i class="bi bi-shield-check"></i> Roles y permisos
        </button>
        <?php endif; ?>
        <?php if ($canAudit): ?>
        <button class="tf-tab-btn" x-bind:class="{active: activeTab==='bitacora'}" x-on:click="activeTab='bitacora'" role="tab">
            <i class="bi bi-clock-history"></i> Bitacora
        </button>
        <?php endif; ?>
    </nav>

    <!-- Pestana: Usuarios -->
    <div x-show="activeTab==='usuarios'">

    <!-- Tabla de usuarios -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-people-fill"></i> Usuarios del sistema
                </h2>
                <p class="tf-card-sub"><span x-text="users.length"></span> usuarios registrados</p>
            </div>
            <input x-model="filterText"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar usuario o rol..."
                   style="max-width:260px">
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto">
            <table class="tf-admin-table" x-cloak>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre / Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Ultimo acceso</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="u in filteredUsers" :key="u.user_id">
                    <tr>
                        <td>
                            <strong x-text="u.user_nameUser"></strong>
                            <small class="d-block text-muted">ID #<span x-text="u.user_id"></span></small>
                        </td>
                        <td>
                            <span x-text="u.user_name || '-'"></span>
                            <small class="d-block text-muted" x-text="u.user_email || ''"></small>
                        </td>
                        <td>
                            <?php if ($canRole): ?>
                            <select class="form-select form-select-sm"
                                    x-bind:value="u.user_role_id"
                                    x-on:change="changeRole(u, $event.target.value)"
                                    x-bind:disabled="u.user_id == <?= (int)$__user['user_id'] ?>"
                                    style="min-width:160px">
                                <template x-for="r in assignableRoles" :key="r.role_id">
                                    <option :value="r.role_id" x-text="r.role_nombre"></option>
                                </template>
                            </select>
                            <?php else: ?>
                            <span class="tf-role" x-bind:class="'tf-role-' + (u.role_codigo || 'lector')">
                                <span x-text="u.role_nombre || '-'"></span>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span x-show="u.user_obra_nombre" class="badge bg-primary-subtle text-primary-emphasis">
                                <i class="bi bi-building me-1"></i><span x-text="u.user_obra_nombre"></span>
                            </span>
                            <small x-show="!u.user_obra_nombre" class="text-muted">Sin obra</small>
                        </td>
                        <td>
                            <span class="tf-status"
                                  x-bind:class="u.user_estatus === 'ACTIVO' ? 'tf-status-active' : 'tf-status-inactive'">
                                <span x-text="u.user_estatus"></span>
                            </span>
                        </td>
                        <td>
                            <small x-text="u.user_lastLogin || 'Nunca'"></small>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <?php if ($canEdit): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    x-on:click="openEdit(u)" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($canAssignObra): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    x-on:click="openAssignObra(u)" title="Asignar obra">
                                <i class="bi bi-building-add"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="tf-btn tf-btn-sm"
                                    x-bind:class="u.user_estatus === 'ACTIVO' ? 'tf-btn-danger' : 'tf-btn-success'"
                                    x-on:click="toggleStatus(u)"
                                    x-bind:disabled="u.user_id == <?= (int)$__user['user_id'] ?>"
                                    x-bind:title="u.user_estatus === 'ACTIVO' ? 'Desactivar' : 'Activar'">
                                <i x-bind:class="u.user_estatus === 'ACTIVO' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    </template>
                    <tr x-show="!filteredUsers.length">
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:1.5rem"></i>
                            <div>Sin resultados</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    </div><!-- /tab usuarios -->

    <!-- Pestana: Bitacora -->
    <?php if ($canAudit): ?>
    <div x-show="activeTab==='bitacora'">
    <!-- Audit log -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-clock-history"></i> Bitacora de auditoria
                </h2>
                <p class="tf-card-sub"><span x-text="auditTotal"></span> registros</p>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
                <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="loadAudit(1)">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
                <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="exportAuditCSV">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
        </header>
        <!-- Filtros -->
        <div class="tf-card-body" style="border-bottom:1px solid var(--tf-border)">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label tf-label mb-1">Usuario</label>
                    <input type="text" class="form-control form-control-sm"
                           x-model="auditFilter.user"
                           placeholder="Nombre o ID"
                           x-on:keyup.enter="loadAudit(1)">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label tf-label mb-1">Accion</label>
                    <input type="text" class="form-control form-control-sm"
                           x-model="auditFilter.accion"
                           placeholder="ej: login.fail"
                           x-on:keyup.enter="loadAudit(1)">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label tf-label mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm"
                           x-model="auditFilter.desde"
                           x-on:change="loadAudit(1)">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label tf-label mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm"
                           x-model="auditFilter.hasta"
                           x-on:change="loadAudit(1)">
                </div>
                <div class="col-12 col-sm-auto">
                    <button type="button" class="tf-btn tf-btn-primary tf-btn-sm w-100"
                            x-on:click="loadAudit(1)">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
                <div class="col-12 col-sm-auto">
                    <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm w-100"
                            x-on:click="resetAuditFilter">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
        <div class="tf-card-body p-0" style="overflow-x:auto;max-height:420px;overflow-y:auto">
            <table class="tf-admin-table" x-cloak>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Modulo</th>
                        <th>Detalle</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="a in audit" :key="a.audit_id">
                    <tr>
                        <td><small x-text="a.audit_createdAt"></small></td>
                        <td x-text="a.audit_userName || '-'"></td>
                        <td><code style="font-size:.78rem" x-text="a.audit_accion"></code></td>
                        <td x-text="a.audit_modulo || '-'"></td>
                        <td><small x-text="a.audit_detalle || ''"></small></td>
                        <td><small x-text="a.audit_ip || ''"></small></td>
                    </tr>
                    </template>
                    <tr x-show="!audit.length">
                        <td colspan="6" class="text-center text-muted py-3">Sin registros</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Paginacion de auditoria -->
        <div class="tf-card-body" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap" x-cloak x-show="auditPages>1">
            <small class="text-muted">
            Pagina <span x-text="auditPage"></span> de <span x-text="auditPages"></span> &middot; <span x-text="auditTotal"></span> registros
            </small>
            <div style="display:flex;gap:.25rem">
                <button class="tf-btn tf-btn-ghost tf-btn-sm"
                        x-bind:disabled="auditPage<=1"
                        x-on:click="loadAudit(auditPage-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
            <template x-for="p in auditPageRange" :key="p">
                <button class="tf-btn tf-btn-ghost tf-btn-sm"
                    x-bind:class="p===auditPage?'tf-btn-primary':''"
                    x-on:click="loadAudit(p)" x-text="p"></button>
            </template>
                <button class="tf-btn tf-btn-ghost tf-btn-sm"
                        x-bind:disabled="auditPage>=auditPages"
                        x-on:click="loadAudit(auditPage+1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    </div><!-- /tab bitacora -->
    <?php endif; ?>

    <!-- Pestana: Roles y permisos -->
    <?php if ($canRole): ?>
    <div x-show="activeTab==='roles'" x-cloak>

        <div class="tf-roles-layout">

            <!-- Panel izquierdo: lista de roles -->
            <section class="tf-card">
                <header class="tf-card-header">
                    <div>
                        <h2 class="tf-card-title"><i class="bi bi-shield-check"></i> Roles</h2>
                        <p class="tf-card-sub"><span x-text="roles.length"></span> roles en el sistema</p>
                    </div>
                    <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" x-on:click="openCreateRole">
                        <i class="bi bi-plus"></i> Nuevo
                    </button>
                </header>
                <div class="tf-card-body p-0">
                    <ul class="list-unstyled m-0">
                        <template x-for="r in roles" :key="r.role_id">
                        <li
                            class="tf-role-item"
                            x-bind:class="{active: selectedRole && selectedRole.role_id === r.role_id}"
                            x-on:click="selectRole(r)">
                            <div style="flex:1;min-width:0">
                                <strong class="d-block" x-text="r.role_nombre"></strong>
                                <small class="text-muted"><span x-text="r.role_codigo"></span> &middot; nivel <span x-text="r.role_nivel"></span></small>
                            </div>
                            <span class="tf-status"
                                  x-bind:class="r.role_estatus === 'ACTIVO' ? 'tf-status-active' : 'tf-status-inactive'">
                                <span x-text="r.role_estatus"></span>
                            </span>
                        </li>
                        </template>
                        <li x-show="!roles.length" class="px-4 py-3 text-muted text-center">Sin roles</li>
                    </ul>
                </div>
            </section>

            <!-- Panel derecho: permisos del rol -->
            <section class="tf-card" x-show="selectedRole">
                <header class="tf-card-header">
                    <div>
                        <h2 class="tf-card-title">
                            <i class="bi bi-key"></i>
                            <span x-text="selectedRole ? selectedRole.role_nombre : ''"></span>
                        </h2>
                        <p class="tf-card-sub">Activa o desactiva acciones para este rol</p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                x-on:click="openEditRole(selectedRole)">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button type="button" class="tf-btn tf-btn-sm"
                                x-bind:class="selectedRole && selectedRole.role_estatus === 'ACTIVO' ? 'tf-btn-danger' : 'tf-btn-success'"
                                x-on:click="toggleRoleStatus(selectedRole)"
                                x-bind:disabled="!selectedRole || selectedRole.role_codigo === 'admin'">
                            <span x-text="selectedRole && selectedRole.role_estatus === 'ACTIVO' ? 'Desactivar' : 'Activar'"></span>
                        </button>
                    </div>
                </header>
                <div class="tf-card-body">
                    <div x-show="loadingPerms" class="text-center py-4 text-muted">
                        <i class="bi bi-hourglass-split"></i> Cargando permisos...
                    </div>
                    <div x-show="!loadingPerms">
                        <p x-show="selectedRole && selectedRole.role_codigo === 'admin'" class="tf-perm-admin-note">
                            <i class="bi bi-shield-fill-check"></i>
                            El rol <strong>Administrador</strong> tiene acceso total y sus permisos no pueden modificarse.
                        </p>
                        <template x-for="entry in Object.entries(permsByModule)" :key="entry[0]">
                        <div class="tf-perm-group">
                            <h6 class="tf-perm-module" x-text="entry[0]"></h6>
                            <div class="tf-perm-grid">
                                <template x-for="p in entry[1]" :key="p.permission_id">
                                <label class="tf-perm-item" x-bind:class="{granted: hasPermission(p.permission_id)}">
                                    <input type="checkbox"
                                           x-bind:checked="hasPermission(p.permission_id)"
                                           x-on:change="togglePerm(p, $event.target.checked)"
                                           x-bind:disabled="!selectedRole || selectedRole.role_codigo === 'admin'">
                                    <span>
                                        <strong x-text="p.permission_codigo"></strong>
                                        <small class="d-block text-muted" x-text="p.permission_descripcion || ''"></small>
                                    </span>
                                </label>
                                </template>
                            </div>
                        </div>
                        </template>
                        <p x-show="!permissions.length" class="text-muted text-center py-3">Sin permisos definidos.</p>
                    </div>
                </div>
            </section>

            <!-- Placeholder cuando no hay rol seleccionado -->
            <section class="tf-card tf-roles-placeholder" x-show="!selectedRole">
                <i class="bi bi-arrow-left-circle" style="font-size:2rem;color:var(--tf-text-muted)"></i>
                <p class="text-muted mt-2">Selecciona un rol para gestionar sus permisos</p>
            </section>

        </div>

    </div><!-- /tab roles -->
    <?php endif; ?>

    <!-- Modal: crear/editar rol -->
    <?php if ($canRole): ?>
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:1px solid var(--tf-border)">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" x-bind:class="editingRole ? 'bi-pencil-square' : 'bi-shield-plus'"></i>
                        <span x-text="editingRole ? 'Editar rol' : 'Nuevo rol'"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" x-show="!editingRole">
                        <label class="form-label">Codigo <small class="text-muted">(letras minusculas, numeros, _)</small></label>
                        <input x-model="roleForm.role_codigo" type="text" class="form-control"
                               placeholder="ej: supervisor" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input x-model="roleForm.role_nombre" type="text" class="form-control"
                               placeholder="ej: Supervisor de obra">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripcion <small class="text-muted">(opcional)</small></label>
                        <input x-model="roleForm.role_descripcion" type="text" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nivel jerarquico <small class="text-muted">(1-99; admin=100)</small></label>
                        <input x-model.number="roleForm.role_nivel" type="number" class="form-control"
                               min="1" max="99">
                        <small class="text-muted">A mayor numero, mayor autoridad en el sistema.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tf-btn tf-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="tf-btn tf-btn-primary" x-on:click="saveRole" x-bind:disabled="savingRole">
                        <i class="bi bi-check-lg"></i> <span x-text="savingRole ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal: crear/editar usuario -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:1px solid var(--tf-border)">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" x-bind:class="editing ? 'bi-pencil-square' : 'bi-person-plus'"></i>
                        <span x-text="editing ? 'Editar usuario' : 'Nuevo usuario'"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" x-show="!editing">
                        <label class="form-label">Usuario (login)</label>
                        <input x-model="form.user_nameUser" type="text" class="form-control" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input x-model="form.user_name" type="text" class="form-control" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input x-model="form.user_email" type="email" class="form-control" autocomplete="email">
                    </div>
                    <div class="mb-3" x-show="!editing">
                        <label class="form-label">Rol</label>
                        <select x-model="form.user_role_id" class="form-select">
                            <template x-for="r in roles" :key="r.role_id">
                                <option :value="r.role_id" x-text="r.role_nombre + ' - nivel ' + r.role_nivel"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <span x-text="editing ? 'Nueva contrasena (opcional)' : 'Contrasena'"></span>
                        </label>
                        <input x-model="form.user_password" type="password" class="form-control"
                               x-bind:placeholder="editing ? 'Dejar vacio para no cambiar' : 'Minimo 8 caracteres'"
                               autocomplete="new-password">
                        <small class="text-muted">Las contrasenas se guardan con hash bcrypt.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tf-btn tf-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="tf-btn tf-btn-primary" x-on:click="saveUser" x-bind:disabled="saving">
                        <i class="bi bi-check-lg"></i> <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="obraModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:14px;border:1px solid var(--tf-border)">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-building-add"></i>
                        Obras asignadas &mdash; <span x-text="obraTarget.user_nameUser"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Activa o desactiva las obras a las que tiene acceso este colaborador.
                        Los cambios se aplican inmediatamente.
                    </p>
                    <input x-model="obraFilter" type="search" class="form-control form-control-sm mb-3"
                           placeholder="Buscar obra...">
                    <div x-show="loadingObras" class="text-center py-4 text-muted">
                        <i class="bi bi-hourglass-split"></i> Cargando...
                    </div>
                    <div x-show="!loadingObras" style="max-height:360px;overflow-y:auto;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <template x-for="obra in filteredObrasModal" :key="obra.obras_id">
                        <label
                               class="d-flex align-items-center gap-2 p-2 rounded"
                               style="cursor:pointer;border:1px solid var(--tf-border);background:var(--tf-surface)"
                               x-bind:style="isObraAssigned(obra.obras_id) ? 'border-color:var(--tf-primary);background:var(--tf-primary-soft,#eff6ff)' : ''">
                            <input type="checkbox"
                                   class="form-check-input m-0 flex-shrink-0"
                                   x-bind:checked="isObraAssigned(obra.obras_id)"
                                   x-on:change="toggleObraAssign(obra.obras_id, $event.target.checked)">
                            <span class="small fw-semibold" x-text="obra.obras_nombre"></span>
                        </label>
                        </template>
                        <p x-show="!filteredObrasModal.length" class="text-muted small">Sin resultados</p>
                    </div>
                    <div class="mt-3 pt-2" style="border-top:1px solid var(--tf-border)">
                        <small class="text-muted">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span x-text="obraAssignedIds.length"></span> obra<span x-text="obraAssignedIds.length !== 1 ? 's' : ''"></span> asignada<span x-text="obraAssignedIds.length !== 1 ? 's' : ''"></span>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tf-btn tf-btn-primary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$tf_use_datatables  = false;
$tf_inline_script = 'window.TF_ADMIN_CONFIG = ' . json_encode([
    'canAudit' => (bool)$canAudit,
    'canAssignObra' => (bool)$canAssignObra,
    'canRole' => (bool)$canRole,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';

$tf_use_vue = false;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/js/admin.js?v=fase08u"></script>';

include __DIR__ . '/../includes/layout_bottom.php';
?>

