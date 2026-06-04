<?php
include_once '../validarSesion.php';

// ----------------------------------------------------------------
// RBAC + datos del usuario en sesion
// ----------------------------------------------------------------
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

// Gate de acceso: solo quien pueda ver el catalogo
tf_require_permission($__pdo, 'catalogos.view');

$usuario_sesion = $__user['user_nameUser'] ?? ($_SESSION['Usuario'] ?? '');
$usuario_nombre = $__user['user_name']     ?? $usuario_sesion;
$usuario_rol    = $__user['role']['name']  ?? 'Residente';
$usuario_rolCode= $__user['role']['code']  ?? 'residente';
$usuario_dirAcc = tf_user_has_direction_access($__user) ? 1 : 0;
$usuario_perms  = $__user['permissions']   ?? [];

// Capacidades para gates visuales
$canManage = in_array('*', $usuario_perms, true) || in_array('proveedores.manage', $usuario_perms, true);

// ----------------------------------------------------------------
// Variables del layout v4
// ----------------------------------------------------------------
$tf_page_title     = 'Proveedores';
$tf_active_nav     = 'catalogos';
$tf_breadcrumb     = [
    ['Catalogos',  './menu_catalago.php'],
    ['Proveedores', '#'],
];
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

$tf_subbar_extra = '
    <div class="tf-subbar-actions">
        <a href="./menu_catalago.php" class="tf-btn tf-btn-ghost tf-btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a catalogos
        </a>
    </div>
';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppProveedores" class="tf-page-inner" x-data="proveedoresApp()" x-init="init()" x-cloak>

    <!-- ============================================================
         Page header
         ============================================================ -->
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Catalogo</span>
            <h1 class="tf-page-title">Proveedores</h1>
            <p class="tf-page-lead">
                Gestiona los proveedores registrados: consulta, edita y administra su estatus.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <?php if ($canManage): ?>
            <a href="./agregar_proveedor.php" class="tf-btn tf-btn-primary">
                <i class="bi bi-plus-circle"></i> Agregar proveedor
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- ============================================================
         KPI grid
         ============================================================ -->
    <section class="tf-kpi-grid" aria-label="Resumen rapido" x-cloak>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary">
                    <i class="bi bi-truck"></i>
                </span>
                <span class="tf-kpi-label">Total proveedores</span>
            </div>
            <div class="tf-kpi-value" x-text="proveedores.length"></div>
            <div class="tf-kpi-foot">
                <span>Registrados en catalogo</span>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success">
                    <i class="bi bi-check-circle"></i>
                </span>
                <span class="tf-kpi-label">Activos</span>
            </div>
            <div class="tf-kpi-value" x-text="countActivos"></div>
            <div class="tf-kpi-foot">
                <span>Disponibles para uso</span>
            </div>
        </article>

        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning">
                    <i class="bi bi-funnel"></i>
                </span>
                <span class="tf-kpi-label">Filtrados</span>
            </div>
            <div class="tf-kpi-value" x-text="proveedoresFiltrados.length"></div>
            <div class="tf-kpi-foot">
                <span>Coinciden con la busqueda</span>
            </div>
        </article>
    </section>

    <!-- ============================================================
         Tabla con filtro + paginacion cliente
         ============================================================ -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-list-ul"></i> Listado
                </h2>
                <p class="tf-card-sub" x-cloak>
                    Pagina <span x-text="paginaActual"></span> de <span x-text="totalPaginas"></span>
                    &middot; <span x-text="proveedoresFiltrados.length"></span> resultado(s)
                </p>
            </div>
            <div class="d-flex align-items-center gap-2" style="flex-wrap:wrap">
                <input type="search"
                       x-model="filtro"
                       x-on:input="paginaActual = 1"
                       class="form-control form-control-sm"
                       placeholder="Buscar por nombre o RFC..."
                       style="min-width:240px">
                <select x-model="filtroEstatus"
                        x-on:change="paginaActual = 1"
                        class="form-select form-select-sm"
                        style="min-width:140px">
                    <option value="">Todos</option>
                    <option value="ACTIVO">Activos</option>
                    <option value="INACTIVO">Inactivos</option>
                </select>
            </div>
        </header>

        <div class="tf-card-body p-0">

            <!-- Loader -->
            <div x-show="cargando" class="tf-empty">
                <i class="bi bi-arrow-clockwise"></i>
                <p>Cargando proveedores...</p>
            </div>

            <!-- Vacio -->
            <div x-show="!cargando && !proveedoresFiltrados.length" class="tf-empty" x-cloak>
                <i class="bi bi-inbox"></i>
                <p>No se encontraron proveedores con esos criterios.</p>
            </div>

            <!-- Tabla -->
            <div x-show="!cargando && proveedoresFiltrados.length" class="overflow-auto" x-cloak>
                <table class="tf-admin-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RFC</th>
                            <th>Banco</th>
                            <th>Estatus</th>
                            <th style="text-align:right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="prov in proveedoresPagina" :key="prov.proveedor_id">
                        <tr>
                            <td>
                                <strong x-text="prov.proveedor_nombre"></strong>
                                <div x-show="prov.proveedor_email" class="small text-muted">
                                    <i class="bi bi-envelope"></i> <span x-text="prov.proveedor_email"></span>
                                </div>
                            </td>
                            <td>
                                <code style="font-size:.82rem" x-text="prov.proveedor_rfc || '-'"></code>
                            </td>
                            <td>
                                <span x-text="prov.proveedor_banco || '-'"></span>
                            </td>
                            <td>
                                <span class="tf-status"
                                      :class="prov.proveedor_estatus === 'ACTIVO' ? 'tf-status-active' : 'tf-status-inactive'"
                                      x-text="prov.proveedor_estatus">
                                </span>
                            </td>
                            <td style="text-align:right; white-space:nowrap">
                                <button type="button"
                                        class="tf-btn tf-btn-ghost tf-btn-sm"
                                        title="Consultar"
                                        x-on:click="viewProveedor(prov.proveedor_id)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($canManage): ?>
                                <button type="button"
                                        class="tf-btn tf-btn-ghost tf-btn-sm"
                                        title="Editar"
                                        x-on:click="editProveedor(prov)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button"
                                        class="tf-btn tf-btn-ghost tf-btn-sm text-danger"
                                        title="Desactivar"
                                        x-on:click="desactivarProveedor(prov)">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pager -->
        <footer x-show="!cargando && totalPaginas > 1"
                class="tf-card-footer d-flex align-items-center justify-content-between"
                style="padding:12px 16px;border-top:1px solid var(--tf-border);flex-wrap:wrap;gap:8px"
                x-cloak>
            <div class="small text-muted">
                Mostrando <span x-text="rangoInicio"></span> - <span x-text="rangoFin"></span> de <span x-text="proveedoresFiltrados.length"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="tf-btn tf-btn-ghost tf-btn-sm"
                        :disabled="paginaActual === 1"
                        x-on:click="paginaActual = 1">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
                <button type="button"
                        class="tf-btn tf-btn-ghost tf-btn-sm"
                        :disabled="paginaActual === 1"
                        x-on:click="paginaActual--">
                    <i class="bi bi-chevron-left"></i> Anterior
                </button>
                <span class="small">Pag. <span x-text="paginaActual"></span> / <span x-text="totalPaginas"></span></span>
                <button type="button"
                        class="tf-btn tf-btn-ghost tf-btn-sm"
                        :disabled="paginaActual === totalPaginas"
                        x-on:click="paginaActual++">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button"
                        class="tf-btn tf-btn-ghost tf-btn-sm"
                        :disabled="paginaActual === totalPaginas"
                        x-on:click="paginaActual = totalPaginas">
                    <i class="bi bi-chevron-double-right"></i>
                </button>
            </div>
        </footer>
    </section>

</div>

<?php
$tf_inline_script = 'window.TF_PROV_CONFIG = ' . json_encode([
    'canManage' => (bool)$canManage,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>'
    . '<script src="../assets/js/proveedores.js?v=fase08u"></script>';

/* proveedores.js (external, see assets/js/proveedores.js) — heredoc below is dead but kept for git history */
if (false) {
$__unused = <<<JS
    var url = "../api/crud_proveedor.php";

    function proveedoresApp() {
        return {
            proveedores: [],
            cargando: true,
            filtro: "",
            filtroEstatus: "",
            paginaActual: 1,
            porPagina: 50,
            canManage: {$canManageJs}
        ,
            get countActivos() {
                var c = 0;
                for (var i = 0; i < this.proveedores.length; i++) {
                    if (this.proveedores[i].proveedor_estatus === 'ACTIVO') c++;
                }
                return c;
            },
            get proveedoresFiltrados() {
                var q = (this.filtro || "").trim().toLowerCase();
                var est = this.filtroEstatus;
                var out = this.proveedores;
                if (est) {
                    out = out.filter(function (p) { return p.proveedor_estatus === est; });
                }
                if (q) {
                    out = out.filter(function (p) {
                        var nom = (p.proveedor_nombre || "").toLowerCase();
                        var rfc = (p.proveedor_rfc || "").toLowerCase();
                        return nom.indexOf(q) !== -1 || rfc.indexOf(q) !== -1;
                    });
                }
                return out;
            },
            get totalPaginas() {
                var t = Math.ceil(this.proveedoresFiltrados.length / this.porPagina);
                return t < 1 ? 1 : t;
            },
            get proveedoresPagina() {
                var ini = (this.paginaActual - 1) * this.porPagina;
                return this.proveedoresFiltrados.slice(ini, ini + this.porPagina);
            },
            get rangoInicio() {
                if (!this.proveedoresFiltrados.length) return 0;
                return (this.paginaActual - 1) * this.porPagina + 1;
            },
            get rangoFin() {
                var fin = this.paginaActual * this.porPagina;
                return fin > this.proveedoresFiltrados.length ? this.proveedoresFiltrados.length : fin;
            }
        },
        ajustarPagina: function () {
            if (this.paginaActual > this.totalPaginas) this.paginaActual = this.totalPaginas;
            if (this.paginaActual < 1) this.paginaActual = 1;
        },
            obtenerProveedores: function () {
                var self = this;
                self.cargando = true;
                axios.post(url, { accion: 3 }).then(function (response) {
                    self.proveedores = response.data || [];
                    self.ajustarPagina();
                }).catch(function (err) {
                    console.error("Error al obtener proveedores:", err);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar el catalogo de proveedores."
                    });
                }).finally(function () {
                    self.cargando = false;
                });
            },
            esc: function (s) {
                if (s === null || s === undefined) return "";
                return String(s)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#39;");
            },
            viewProveedor: function (idProveedor) {
                var self = this;
                axios.post(url, { accion: 5, id_prov: idProveedor }).then(function (response) {
                    if (!response.data || !response.data[0]) {
                        Swal.fire({ icon: "warning", title: "Sin datos" });
                        return;
                    }
                    var p = response.data[0];
                    Swal.fire({
                        title: "Detalle del proveedor",
                        width: '50%',
                        html: ''
                            + '<div style="text-align:left">'
                            +   '<div class="row mb-3"><div class="col-12">'
                            +     '<label class="form-label fw-bold">Nombre</label>'
                            +     '<p>' + self.esc(p.proveedor_nombre) + '</p>'
                            +   '</div></div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">RFC</label><p>' + self.esc(p.proveedor_rfc) + '</p></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">CLABE</label><p>' + self.esc(p.proveedor_clabe) + '</p></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Cuenta</label><p>' + self.esc(p.proveedor_numeroCuenta) + '</p></div>'
                            +   '</div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Tarjeta</label><p>' + self.esc(p.presiones_tarjetaBanco) + '</p></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Referencia</label><p>' + self.esc(p.proveedor_refBanco) + '</p></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Tipo</label><p>' + self.esc(p.presiones_type) + '</p></div>'
                            +   '</div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Banco</label><p>' + self.esc(p.proveedor_banco) + '</p></div>'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Sucursal</label><p>' + self.esc(p.proveedor_sucursal) + '</p></div>'
                            +   '</div>'
                            +   '<div class="row mb-1">'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Telefono</label><p>' + self.esc(p.proveedor_telefono) + '</p></div>'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Email</label><p>' + self.esc(p.proveedor_email) + '</p></div>'
                            +   '</div>'
                            + '</div>',
                        focusConfirm: false
                    });
                }).catch(function (err) {
                    console.error(err);
                    Swal.fire({ icon: "error", title: "Error", text: "No se pudo consultar el proveedor." });
                });
            },
            editProveedor: function (prov) {
                if (!this.canManage) return;
                var self = this;
                var idProveedor = prov.proveedor_id;
                axios.post(url, { accion: 5, id_prov: idProveedor }).then(function (response) {
                    if (!response.data || !response.data[0]) return;
                    var p = response.data[0];
                    Swal.fire({
                        title: "Editar proveedor",
                        width: '50%',
                        html: ''
                            + '<div style="text-align:left">'
                            +   '<div class="row mb-3"><div class="col-12">'
                            +     '<label class="form-label fw-bold">Nombre</label>'
                            +     '<input type="text" class="form-control" id="pNombre" value="' + self.esc(p.proveedor_nombre) + '">'
                            +   '</div></div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">RFC</label><input type="text" class="form-control" id="pRfc" value="' + self.esc(p.proveedor_rfc) + '"></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">CLABE</label><input type="text" class="form-control" id="pClabe" value="' + self.esc(p.proveedor_clabe) + '"></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Cuenta</label><input type="text" class="form-control" id="pCuenta" value="' + self.esc(p.proveedor_numeroCuenta) + '"></div>'
                            +   '</div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Tarjeta</label><input type="text" class="form-control" id="pTarjeta" value="' + self.esc(p.presiones_tarjetaBanco) + '"></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Referencia</label><input type="text" class="form-control" id="pReferencia" value="' + self.esc(p.proveedor_refBanco) + '"></div>'
                            +     '<div class="col-md-4"><label class="form-label fw-bold">Tipo</label><input type="text" class="form-control" id="pTipo" value="' + self.esc(p.presiones_type) + '"></div>'
                            +   '</div>'
                            +   '<div class="row mb-3">'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Banco</label><input type="text" class="form-control" id="pBanco" value="' + self.esc(p.proveedor_banco) + '"></div>'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Sucursal</label><input type="text" class="form-control" id="pSucursal" value="' + self.esc(p.proveedor_sucursal) + '"></div>'
                            +   '</div>'
                            +   '<div class="row mb-1">'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Telefono</label><input type="text" class="form-control" id="pTelefono" value="' + self.esc(p.proveedor_telefono) + '"></div>'
                            +     '<div class="col-md-6"><label class="form-label fw-bold">Email</label><input type="email" class="form-control" id="pEmail" value="' + self.esc(p.proveedor_email) + '"></div>'
                            +   '</div>'
                            + '</div>',
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar cambios',
                        cancelButtonText: 'Cancelar',
                        preConfirm: function () {
                            var get = function (id) {
                                var el = document.getElementById(id);
                                return el ? el.value : "";
                            };
                            return {
                                nombreProv: get('pNombre'),
                                RFCProv: get('pRfc'),
                                claveProv: get('pClabe'),
                                cuentaBancaria: get('pCuenta'),
                                tarjetaProv: get('pTarjeta'),
                                referenciaProv: get('pReferencia'),
                                typeProv: get('pTipo'),
                                bancoProv: get('pBanco'),
                                sucursalProv: get('pSucursal'),
                                telefonoProv: get('pTelefono'),
                                correoProv: get('pEmail')
                            };
                        }
                    }).then(function (result) {
                        if (result.isConfirmed && result.value) {
                            self.guardarProveedor(prov, idProveedor, result.value);
                        }
                    });
                });
            },
            guardarProveedor: function (prov, idProveedor, formValues) {
                var self = this;
                axios.post(url, { accion: 6, id_prov: idProveedor, formValues: formValues }).then(function () {
                    // Actualizamos in-place el objeto en el array
                    prov.proveedor_nombre = formValues.nombreProv;
                    prov.proveedor_rfc = formValues.RFCProv;
                    prov.proveedor_clabe = formValues.claveProv;
                    prov.proveedor_numeroCuenta = formValues.cuentaBancaria;
                    prov.presiones_tarjetaBanco = formValues.tarjetaProv;
                    prov.proveedor_refBanco = formValues.referenciaProv;
                    prov.presiones_type = formValues.typeProv;
                    prov.proveedor_banco = formValues.bancoProv;
                    prov.proveedor_sucursal = formValues.sucursalProv;
                    prov.proveedor_telefono = formValues.telefonoProv;
                    prov.proveedor_email = formValues.correoProv;
                    self.ajustarPagina();
                    Swal.fire({
                        toast: true,
                        position: "bottom-start",
                        icon: "success",
                        title: "Proveedor actualizado",
                        showConfirmButton: false,
                        timer: 2500
                    });
                }).catch(function (err) {
                    console.error("Error al editar:", err);
                    Swal.fire({ icon: "error", title: "Error", text: "No se pudo guardar el proveedor." });
                });
            },
            desactivarProveedor: function (prov) {
                if (!this.canManage) return;
                var self = this;
                Swal.fire({
                    icon: "warning",
                    title: "Desactivar proveedor",
                    text: "El proveedor \"" + prov.proveedor_nombre + "\" se marcara como INACTIVO. Podras reactivarlo despues.",
                    showCancelButton: true,
                    confirmButtonText: "Si, desactivar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#dc3545"
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    axios.post(url, { accion: 4, id_prov: prov.proveedor_id }).then(function () {
                        // El endpoint solo devuelve los ACTIVO -> sacamos el item de la lista
                        var idx = self.proveedores.indexOf(prov);
                        if (idx > -1) self.proveedores.splice(idx, 1);
                        self.ajustarPagina();
                        Swal.fire({
                            toast: true,
                            position: "bottom-start",
                            icon: "success",
                            title: "Proveedor desactivado",
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }).catch(function (err) {
                        console.error(err);
                        Swal.fire({ icon: "error", title: "Error", text: "No se pudo desactivar." });
                    });
                });
            },
            init: function () {
                this.obtenerProveedores();
            }
        }
    }
JS;
} // end if(false)

$tf_use_vue = false;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';

include __DIR__ . '/../includes/layout_bottom.php';
?>
