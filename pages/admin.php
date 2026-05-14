<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

// Solo admin (o cualquiera con admin.users.view) entra a esta pantalla
if (!tf_has_permission('admin.users.view', $__user)) {
    tf_abort(403, 'Acceso restringido al administrador del sistema');
}

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

$tf_subbar_extra = $canCreate
    ? '<div class="tf-subbar-actions">
           <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" onclick="document.getElementById(\'tfAdmin\').__vue__.openCreate()">
               <i class="bi bi-person-plus"></i> Nuevo usuario
           </button>
       </div>'
    : '';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="tfAdmin" class="tf-page-inner">

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
            <button type="button" class="tf-btn tf-btn-primary" @click="openCreate">
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
            <div class="tf-kpi-value" v-cloak>{{ users.length }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
                <span class="tf-kpi-label">Activos</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ countActivos }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-shield-lock-fill"></i>
                </span>
                <span class="tf-kpi-label">Roles definidos</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ roles.length }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger">
                    <i class="bi bi-person-x-fill"></i>
                </span>
                <span class="tf-kpi-label">Inactivos</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ countInactivos }}</div>
        </article>
    </section>

    <!-- Tabla de usuarios -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-people-fill"></i> Usuarios del sistema
                </h2>
                <p class="tf-card-sub" v-cloak>{{ users.length }} usuarios registrados</p>
            </div>
            <input v-model="filterText"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar usuario o rol..."
                   style="max-width:260px">
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto">
            <table class="tf-admin-table" v-cloak>
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
                    <tr v-for="u in filteredUsers" :key="u.user_id">
                        <td>
                            <strong>{{ u.user_nameUser }}</strong>
                            <small class="d-block text-muted">ID #{{ u.user_id }}</small>
                        </td>
                        <td>
                            {{ u.user_name || '—' }}
                            <small class="d-block text-muted">{{ u.user_email || '' }}</small>
                        </td>
                        <td>
                            <?php if ($canRole): ?>
                            <select class="form-select form-select-sm"
                                    :value="u.user_role_id"
                                    @change="changeRole(u, $event.target.value)"
                                    :disabled="u.user_id == <?= (int)$__user['user_id'] ?>"
                                    style="min-width:160px">
                                <option v-for="r in roles" :key="r.role_id" :value="r.role_id">
                                    {{ r.role_nombre }}
                                </option>
                            </select>
                            <?php else: ?>
                            <span class="tf-role" :class="'tf-role-' + (u.role_codigo || 'lector')">
                                {{ u.role_nombre || '—' }}
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="tf-status"
                                  :class="u.user_estatus === 'ACTIVO' ? 'tf-status-active' : 'tf-status-inactive'">
                                {{ u.user_estatus }}
                            </span>
                        </td>
                        <td>
                            <small>{{ u.user_lastLogin || 'Nunca' }}</small>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <?php if ($canEdit): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    @click="openEdit(u)" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="tf-btn tf-btn-sm"
                                    :class="u.user_estatus === 'ACTIVO' ? 'tf-btn-danger' : 'tf-btn-success'"
                                    @click="toggleStatus(u)"
                                    :disabled="u.user_id == <?= (int)$__user['user_id'] ?>"
                                    :title="u.user_estatus === 'ACTIVO' ? 'Desactivar' : 'Activar'">
                                <i :class="u.user_estatus === 'ACTIVO' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr v-if="!filteredUsers.length">
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:1.5rem"></i>
                            <div>Sin resultados</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($canAudit): ?>
    <!-- Audit log -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-clock-history"></i> Bitacora de auditoria
                </h2>
                <p class="tf-card-sub">Ultimas <?= 50 ?> acciones registradas</p>
            </div>
            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm" @click="loadAudit">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto;max-height:420px;overflow-y:auto">
            <table class="tf-admin-table" v-cloak>
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
                    <tr v-for="a in audit" :key="a.audit_id">
                        <td><small>{{ a.audit_createdAt }}</small></td>
                        <td>{{ a.audit_userName || '—' }}</td>
                        <td><code style="font-size:.78rem">{{ a.audit_accion }}</code></td>
                        <td>{{ a.audit_modulo || '—' }}</td>
                        <td><small>{{ a.audit_detalle || '' }}</small></td>
                        <td><small>{{ a.audit_ip || '' }}</small></td>
                    </tr>
                    <tr v-if="!audit.length">
                        <td colspan="6" class="text-center text-muted py-3">Sin registros</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- Modal: crear/editar usuario -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:1px solid var(--tf-border)">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi" :class="editing ? 'bi-pencil-square' : 'bi-person-plus'"></i>
                        {{ editing ? 'Editar usuario' : 'Nuevo usuario' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" v-if="!editing">
                        <label class="form-label">Usuario (login)</label>
                        <input v-model="form.user_nameUser" type="text" class="form-control" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input v-model="form.user_name" type="text" class="form-control" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input v-model="form.user_email" type="email" class="form-control" autocomplete="email">
                    </div>
                    <div class="mb-3" v-if="!editing">
                        <label class="form-label">Rol</label>
                        <select v-model="form.user_role_id" class="form-select">
                            <option v-for="r in roles" :key="r.role_id" :value="r.role_id">
                                {{ r.role_nombre }} — nivel {{ r.role_nivel }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            {{ editing ? 'Nueva contrasena (opcional)' : 'Contrasena' }}
                        </label>
                        <input v-model="form.user_password" type="password" class="form-control"
                               :placeholder="editing ? 'Dejar vacio para no cambiar' : 'Minimo 8 caracteres'"
                               autocomplete="new-password">
                        <small class="text-muted">Las contrasenas se guardan con hash bcrypt.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tf-btn tf-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="tf-btn tf-btn-primary" @click="saveUser" :disabled="saving">
                        <i class="bi bi-check-lg"></i> {{ saving ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$tf_use_datatables = false;
$tf_inline_script = <<<JS
    var url = "../api/crud_admin.php";
    var __canAudit = JSON.parse('<?= $canAudit ? 'true' : 'false' ?>');

    var app = new Vue({
        el: "#tfAdmin",
        data: {
            users: [],
            roles: [],
            audit: [],
            filterText: "",
            editing: false,
            saving: false,
            form: {
                user_id: null,
                user_nameUser: "",
                user_name: "",
                user_email: "",
                user_role_id: null,
                user_password: ""
            },
            modal: null
        },
        computed: {
            countActivos:   function () { return this.users.filter(function(u){return u.user_estatus === 'ACTIVO';}).length; },
            countInactivos: function () { return this.users.filter(function(u){return u.user_estatus !== 'ACTIVO';}).length; },
            filteredUsers: function () {
                var q = (this.filterText || "").trim().toLowerCase();
                if (!q) return this.users;
                return this.users.filter(function(u){
                    return (u.user_nameUser || "").toLowerCase().indexOf(q) !== -1
                        || (u.user_name     || "").toLowerCase().indexOf(q) !== -1
                        || (u.role_nombre   || "").toLowerCase().indexOf(q) !== -1
                        || (u.user_email    || "").toLowerCase().indexOf(q) !== -1;
                });
            }
        },
        methods: {
            loadAll: function () {
                axios.post(url, { accion: 1 }).then(function(r){ this.users = r.data || []; }.bind(this));
                axios.post(url, { accion: 2 }).then(function(r){ this.roles = r.data || []; }.bind(this));
                if (__canAudit) this.loadAudit();
            },
            loadAudit: function () {
                axios.post(url, { accion: 7, limite: 50 }).then(function(r){
                    this.audit = r.data || [];
                }.bind(this));
            },
            openCreate: function () {
                this.editing = false;
                this.form = {
                    user_id: null, user_nameUser: "", user_name: "", user_email: "",
                    user_role_id: this.roles.length ? this.roles[this.roles.length - 1].role_id : null,
                    user_password: ""
                };
                this.modal.show();
            },
            openEdit: function (u) {
                this.editing = true;
                this.form = {
                    user_id: u.user_id,
                    user_nameUser: u.user_nameUser,
                    user_name: u.user_name || "",
                    user_email: u.user_email || "",
                    user_role_id: u.user_role_id,
                    user_password: ""
                };
                this.modal.show();
            },
            saveUser: function () {
                if (this.saving) return;
                this.saving = true;
                var f = this.form;
                var req = this.editing
                    ? { accion: 4, user_id: f.user_id, user_name: f.user_name, user_email: f.user_email, user_password: f.user_password }
                    : { accion: 3, user_nameUser: f.user_nameUser, user_name: f.user_name, user_email: f.user_email, user_role_id: f.user_role_id, user_password: f.user_password };
                axios.post(url, req)
                    .then(function () {
                        Swal.fire({icon:'success', title:'Guardado', timer:1500, showConfirmButton:false, toast:true, position:'top-end'});
                        this.modal.hide();
                        this.loadAll();
                    }.bind(this))
                    .catch(function (err) {
                        var msg = (err.response && err.response.data && err.response.data.message) || 'Error al guardar';
                        Swal.fire({icon:'error', title:'Error', text: msg});
                    })
                    .finally(function () { this.saving = false; }.bind(this));
            },
            changeRole: function (u, newRoleId) {
                axios.post(url, { accion: 5, user_id: u.user_id, user_role_id: parseInt(newRoleId, 10) })
                    .then(function () {
                        Swal.fire({icon:'success', title:'Rol actualizado', timer:1200, showConfirmButton:false, toast:true, position:'top-end'});
                        this.loadAll();
                    }.bind(this))
                    .catch(function (err) {
                        var msg = (err.response && err.response.data && err.response.data.message) || 'No se pudo cambiar el rol';
                        Swal.fire({icon:'error', title:'Error', text: msg});
                    });
            },
            toggleStatus: function (u) {
                var nuevo = u.user_estatus === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
                Swal.fire({
                    title: nuevo === 'INACTIVO' ? 'Desactivar usuario?' : 'Activar usuario?',
                    text: u.user_nameUser, icon: 'question',
                    showCancelButton: true, confirmButtonText: 'Si, ' + (nuevo === 'INACTIVO' ? 'desactivar' : 'activar')
                }).then(function (res) {
                    if (!res.isConfirmed) return;
                    axios.post(url, { accion: 6, user_id: u.user_id, user_estatus: nuevo })
                        .then(function () {
                            Swal.fire({icon:'success', title:'Listo', timer:1000, showConfirmButton:false, toast:true, position:'top-end'});
                            this.loadAll();
                        }.bind(this))
                        .catch(function (err) {
                            var msg = (err.response && err.response.data && err.response.data.message) || 'Error';
                            Swal.fire({icon:'error', title:'Error', text: msg});
                        });
                }.bind(this));
            }
        },
        mounted: function () {
            this.modal = new bootstrap.Modal(document.getElementById('userModal'));
            this.loadAll();
        }
    });
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
