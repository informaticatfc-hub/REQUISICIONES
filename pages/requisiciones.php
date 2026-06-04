<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['requisiciones.view'], 'No tienes permisos para ver requisiciones');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? '';
$usuario_rolCode = $__user['role']['code'] ?? '';
$usuario_perms   = $__user['permissions']  ?? [];

$canCreate = tf_has_permission('requisiciones.create', $__user);
$canEdit   = tf_has_permission('requisiciones.edit',   $__user);
$canDelete = tf_has_permission('requisiciones.delete', $__user);

$tf_page_title     = 'Requisiciones';
$tf_active_nav     = 'requisiciones';
$tf_breadcrumb     = [
    ['Inicio', './index.php'],
    ['Obras', './obras.php'],
    ['Requisiciones', '#'],
];
$tf_user = [
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
$tf_extra_head     = '<style>[x-cloak]{display:none!important;}</style>';

$tf_subbar_extra = $canCreate
    ? '<div class="tf-subbar-actions">
           <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" onclick="window.tfRequisicionesOpenCreate && window.tfRequisicionesOpenCreate()">
               <i class="bi bi-file-earmark-plus"></i> Nueva requisicion
           </button>
       </div>'
    : '';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppPresion" class="tf-page-inner" x-data="requisicionesApp()" x-init="init()" x-cloak>

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Obra <span x-text="obras[0] ? obras[0].obras_nombre : '—'"></span></span>
            <h1 class="tf-page-title">Requisiciones</h1>
            <p class="tf-page-lead">
                Consulta y gestiona las requisiciones de compra de la obra activa.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <?php if ($canCreate): ?>
            <button type="button" class="tf-btn tf-btn-primary" x-on:click="addRequisicion">
                <i class="bi bi-file-earmark-plus"></i> Nueva requisicion
            </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- KPIs -->
    <section class="tf-kpi-grid" aria-label="Resumen">
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-primary"><i class="bi bi-receipt"></i></span>
                <span class="tf-kpi-label">Total</span>
            </div>
            <div class="tf-kpi-value" x-text="totalRequisiciones"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning"><i class="bi bi-folder2-open"></i></span>
                <span class="tf-kpi-label">Abiertas</span>
            </div>
            <div class="tf-kpi-value" x-text="countAbiertas"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success"><i class="bi bi-check-circle-fill"></i></span>
                <span class="tf-kpi-label">Cerradas</span>
            </div>
            <div class="tf-kpi-value" x-text="countCerradas"></div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger"><i class="bi bi-x-octagon-fill"></i></span>
                <span class="tf-kpi-label">En página</span>
            </div>
            <div class="tf-kpi-value" x-text="requisiciones.length"></div>
        </article>
    </section>

    <!-- R-M4: Alerta de demasiadas requisiciones abiertas (>= 5 sin cerrar) -->
    <div class="alert alert-warning alert-dismissible d-flex align-items-center gap-2 mb-3"
         role="alert"
         x-show="abiertasCount >= 5"
         x-cloak>
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
        <div>
            <strong>Atención:</strong> Hay <strong x-text="abiertasCount"></strong> requisiciones abiertas en esta obra.
            Se recomienda revisar y cerrar las que ya no sean necesarias antes de crear nuevas.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>

    <!-- Tabla de requisiciones -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-list-check"></i> Listado
                </h2>
                <p class="tf-card-sub" x-text="totalRequisiciones + ' requisiciones registradas en esta obra'"></p>
            </div>
            <input x-model="filterText"
                   x-on:input="onSearchInput"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar por numero, nombre o clave..."
                   style="max-width:280px">
            <!-- C-M4: Filtro "Mis requisiciones" -->
            <button type="button"
                    class="btn btn-sm"
                    x-bind:class="soloMias ? 'btn-primary' : 'btn-outline-secondary'"
                    x-on:click="soloMias = !soloMias; currentPage = 1; listarRequisiciones(localStorage.getItem('obraActiva'))"
                    title="Mostrar solo mis requisiciones">
                <i class="bi bi-person-check"></i>
                <span x-text="soloMias ? 'Mis requisiciones' : 'Todas'"></span>
            </button>
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto">
            <table class="tf-admin-table">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Nombre</th>
                        <th style="width:100px">Clave</th>
                        <th style="width:120px">Estado</th>
                        <th style="width:130px;text-align:right">Monto Total</th>
                        <th style="width:180px;text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(req, indice) in filteredRequisiciones" :key="req.requisicion_id + '-' + indice">
                    <tr>
                        <td>
                            <strong x-show="!req.requisicion_EditShow" x-text="req.requisicion_Numero"></strong>
                            <input x-show="req.requisicion_EditShow"
                                   type="text"
                                   class="form-control form-control-sm"
                                   x-model="req.requisicion_Numero">
                        </td>
                        <td>
                            <span x-show="!req.requisicion_EditShow" x-text="req.requisicion_Nombre"></span>
                            <input x-show="req.requisicion_EditShow"
                                   type="text"
                                   class="form-control form-control-sm"
                                   x-model="req.requisicion_Nombre">
                        </td>
                        <td><code style="font-size:.8rem" x-text="req.requisicion_Clave"></code></td>
                        <td>
                            <span class="badge rounded-pill fs-75"
                                  x-bind:class="{
                                    'bg-warning text-dark': req.requisicion_estatus === 'ABIERTO',
                                    'bg-info text-dark':    req.requisicion_estatus === 'EN REVISION',
                                    'bg-success':           req.requisicion_estatus === 'CERRADO' || req.requisicion_estatus === 'CERRADA',
                                    'bg-danger':            req.requisicion_estatus === 'CANCELADA' || req.requisicion_estatus === 'RECHAZADA',
                                    'bg-secondary':         !['ABIERTO','EN REVISION','CERRADO','CERRADA','CANCELADA','RECHAZADA'].includes(req.requisicion_estatus)
                                  }"
                                  x-bind:title="req.requisicion_estatus === 'ABIERTO'     ? 'Requisición activa, pendiente de aprobación'
                                        : req.requisicion_estatus === 'EN REVISION' ? 'En revisión por el director'
                                        : req.requisicion_estatus === 'CERRADO' || req.requisicion_estatus === 'CERRADA' ? 'Requisición cerrada y aprobada'
                                        : req.requisicion_estatus === 'CANCELADA'   ? 'Requisición cancelada'
                                        : req.requisicion_estatus === 'RECHAZADA'   ? 'Requisición rechazada'
                                        : req.requisicion_estatus"
                                  data-bs-toggle="tooltip"
                                  style="letter-spacing:.5px;font-size:.72rem;padding:.35em .7em">
                                <span x-text="req.requisicion_estatus"></span>
                            </span>
                        </td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap">
                            <span x-show="Number(req.requisicion_montoTotal) > 0" class="text-success fw-semibold"
                                  x-text="'$' + formatMoney(req.requisicion_montoTotal)">
                            </span>
                            <span x-show="!(Number(req.requisicion_montoTotal) > 0)" class="text-muted">—</span>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    x-on:click="ConsultarItemRq(req.requisicion_id)"
                                    title="Consultar hojas de requisicion">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if ($canEdit): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    x-show="!req.requisicion_EditShow"
                                    x-on:click="editRequisicion(indice)"
                                    title="Editar requisicion">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="tf-btn tf-btn-primary tf-btn-sm"
                                    x-show="req.requisicion_EditShow"
                                    x-on:click="saveEditrequisicion(indice, req.requisicion_id, req.requisicion_Numero, req.requisicion_Nombre)"
                                    title="Guardar cambios">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button type="button" class="tf-btn tf-btn-danger tf-btn-sm"
                                    x-on:click="deleteRequisicionShow(indice, req.requisicion_id)"
                                    title="Eliminar requisicion">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    </template>
                    <tr x-show="!filteredRequisiciones.length">
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:1.5rem"></i>
                            <div>Sin requisiciones para esta obra</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top">
            <small class="text-muted" x-text="'Página ' + currentPage + ' de ' + totalPages + ' · ' + totalRequisiciones + ' registros'"></small>
            <div class="btn-group">
                <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(1)" x-bind:disabled="currentPage <= 1">Primera</button>
                <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage - 1)" x-bind:disabled="currentPage <= 1">Anterior</button>
                <template x-for="p in pageRange" :key="'rq-page-'+p">
                <button
                        class="tf-btn tf-btn-sm"
                        x-bind:class="p === currentPage ? 'tf-btn-primary' : 'tf-btn-ghost'"
                        x-on:click="goToPage(p)" x-text="p"></button>
                </template>
                <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(currentPage + 1)" x-bind:disabled="currentPage >= totalPages">Siguiente</button>
                <button class="tf-btn tf-btn-ghost tf-btn-sm" x-on:click="goToPage(totalPages)" x-bind:disabled="currentPage >= totalPages">Última</button>
            </div>
        </div>
    </section>

</div>

<?php
$tf_use_datatables = false;  // remplazado por filtrado client-side simple
$tf_use_jquery     = false;
$tf_use_vue        = false;
$tf_use_axios      = true;
$canCreateJs = $canCreate ? 'true' : 'false';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';
$tf_inline_script = <<<JS
    var url = "../api/crud_Requisiciones.php";
    var CAN_CREATE = $canCreateJs;

    function requisicionesApp() {
        return {
            requisiciones: [],
            obras: [{ obras_nombre: '' }],
            filterText: "",
            soloMias: false,
            totalRequisiciones: 0,
            currentPage: 1,
            totalPages: 1,
            limite: 20,
            loadingTable: false,
            searchTimer: null,
            abiertasCount: 0,
            cerradasCount: 0,
            nombreRequisicion: "",
            fechaGeneracion: "",
            clave: "",
            folioReq: "",
            hojaReq: ""
        ,
            get countAbiertas() {
                return this.abiertasCount;
            },
            get countCerradas() {
                return this.cerradasCount;
            },
            get filteredRequisiciones() {
                return this.requisiciones;
            },
            get pageRange() {
                var start = Math.max(1, this.currentPage - 2);
                var end = Math.min(this.totalPages, start + 4);
                start = Math.max(1, end - 4);
                var out = [];
                for (var i = start; i <= end; i++) out.push(i);
                return out;
            },
            formatMoney: function (amount) {
                return Number(amount || 0).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            getSourceIndex: function (index) {
                var row = this.filteredRequisiciones[index];
                var idx = this.requisiciones.indexOf(row);
                return idx === -1 ? index : idx;
            }
        ,
            ConsultarItemRq: function (idRq) {
                localStorage.setItem("idRequisicion", idRq);
                window.location.href = "./hojas_requisicion.php";
            },
            infoObraActiva: function (obrasId) {
                return axios.post(url, { accion: 3, obra: obrasId }).then((r) => {
                    this.obras = r.data && r.data.length ? r.data : [{ obras_nombre: '—', obra_automatico: 0 }];
                });
            },
            listarRequisiciones: function (idObra) {
                this.loadingTable = true;
                return axios.post(url, {
                    accion: 1,
                    obra: idObra,
                    page: this.currentPage,
                    limite: this.limite,
                    search: this.filterText,
                    serverSide: 1,
                    soloMias: this.soloMias ? 1 : 0
                }).then((r) => {
                    var payload = r.data || {};
                    var rows = Array.isArray(payload) ? payload : (payload.rows || []);
                    this.requisiciones = rows.map(function (req) {
                        req.requisicion_EditShow = false;
                        return req;
                    });
                    if (!Array.isArray(payload)) {
                        this.totalRequisiciones = Number(payload.total || 0);
                        this.currentPage = Number(payload.page || this.currentPage || 1);
                        this.totalPages = Number(payload.pages || 1);
                        this.limite = Number(payload.limite || this.limite || 20);
                        this.abiertasCount = Number(payload.abiertas || 0);
                        this.cerradasCount = Number(payload.cerradas || 0);
                    } else {
                        // Fallback legacy
                        this.totalRequisiciones = rows.length;
                        this.totalPages = 1;
                        this.currentPage = 1;
                        this.abiertasCount = rows.filter(function (x) { return x.requisicion_estatus === 'ABIERTO'; }).length;
                        this.cerradasCount = rows.filter(function (x) { return x.requisicion_estatus === 'CERRADO' || x.requisicion_estatus === 'CERRADA'; }).length;
                    }
                }).catch(() => {
                    this.requisiciones = [];
                    this.totalRequisiciones = 0;
                    this.totalPages = 1;
                    this.currentPage = 1;
                    this.abiertasCount = 0;
                    this.cerradasCount = 0;
                }).finally(() => {
                    this.loadingTable = false;
                });
            },
            onSearchInput: function () {
                if (this.searchTimer) clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    this.currentPage = 1;
                    this.listarRequisiciones(localStorage.getItem("obraActiva"));
                }, 350);
            },
            goToPage: function (page) {
                if (this.loadingTable) return;
                var target = Math.max(1, Math.min(this.totalPages, parseInt(page, 10) || 1));
                if (target === this.currentPage) return;
                this.currentPage = target;
                this.listarRequisiciones(localStorage.getItem("obraActiva"));
            },
            addRequisicion: async function () {
                if (!CAN_CREATE) return;
                var auto = (this.obras[0] || {}).obra_automatico == 1;
                var html = auto
                    ? '<div class="text-start">'
                      + '<div class="mb-2"><label class="form-label">Nombre de la Requisicion</label>'
                      + '<input id="nombreRequisicion" class="form-control"></div>'
                      + '<div class="mb-2"><label class="form-label">Fecha</label>'
                      + '<input type="date" id="fechaGeneracion" class="form-control"></div>'
                      + '<div class="mb-2"><label class="form-label">Clave</label>'
                      + '<select id="Clv" class="form-select">'
                      + '<option value="">Selecciona clave</option>'
                      + '<option value="MAT">MAT - Material</option>'
                      + '<option value="EQH">EQH - Equipo/Maquinaria</option>'
                      + '<option value="IND">IND - Indirectos</option>'
                      + '<option value="MO">MO - Mano de Obra</option>'
                      + '</select></div></div>'
                    : '<div class="text-start">'
                      + '<div class="row g-2 mb-2">'
                      + '<div class="col-6"><label class="form-label">Folio</label>'
                      + '<input id="FolioReq" class="form-control"></div>'
                      + '<div class="col-6"><label class="form-label">Hojas</label>'
                      + '<input id="HojaReq" class="form-control"></div></div>'
                      + '<div class="mb-2"><label class="form-label">Nombre</label>'
                      + '<input id="nombreRequisicion" class="form-control"></div>'
                      + '<div class="mb-2"><label class="form-label">Fecha</label>'
                      + '<input type="date" id="fechaGeneracion" class="form-control"></div>'
                      + '<div class="mb-2"><label class="form-label">Clave</label>'
                      + '<select id="Clv" class="form-select">'
                      + '<option value="">Selecciona clave</option>'
                      + '<option value="MAT">MAT - Material</option>'
                      + '<option value="EQH">EQH - Equipo/Maquinaria</option>'
                      + '<option value="IND">IND - Indirectos</option>'
                      + '<option value="MO">MO - Mano de Obra</option>'
                      + '</select></div></div>';

                var self = this;
                var res = await Swal.fire({
                    title: 'Nueva requisicion',
                    html: html,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Crear',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    preConfirm: function () {
                        self.nombreRequisicion = (document.getElementById('nombreRequisicion') || {}).value || '';
                        self.fechaGeneracion   = (document.getElementById('fechaGeneracion')   || {}).value || '';
                        self.clave             = (document.getElementById('Clv')               || {}).value || '';
                        if (!auto) {
                            self.folioReq = (document.getElementById('FolioReq') || {}).value || '';
                            self.hojaReq  = (document.getElementById('HojaReq')  || {}).value || '';
                        }
                        if (!self.nombreRequisicion || !self.fechaGeneracion || !self.clave
                            || (!auto && (!self.folioReq || !self.hojaReq))) {
                            Swal.showValidationMessage('Completa todos los campos');
                            return false;
                        }
                        return true;
                    }
                });
                if (!res.isConfirmed) return;
                if (auto) this.newRequisicionAuto();
                else      this.newRequisicionManual();
            },
            newRequisicionAuto: function () {
                axios.post(url, {
                    accion: 6,
                    nombreReq: this.nombreRequisicion,
                    fechaReq:  this.fechaGeneracion,
                    clave:     this.clave,
                    obra:      localStorage.getItem("obraActiva")
                }).then(() => {
                    Swal.fire({icon:'success', title:'Requisicion creada', timer:1400, showConfirmButton:false, toast:true, position:'top-end'});
                    this.currentPage = 1;
                    this.listarRequisiciones(localStorage.getItem("obraActiva"));
                }).catch(function (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || 'Error al crear';
                    Swal.fire({icon:'error', title:'Error', text: msg});
                });
            },
            newRequisicionManual: function () {
                var hoja = parseInt(this.hojaReq, 10) - 1;
                axios.post(url, {
                    accion: 7,
                    nombreReq: this.nombreRequisicion,
                    fechaReq:  this.fechaGeneracion,
                    clave:     this.clave,
                    folio:     this.folioReq,
                    hoja:      hoja,
                    obra:      localStorage.getItem("obraActiva")
                }).then((r) => {
                    if (r.data && r.data.success === false) {
                        Swal.fire({icon:'warning', title:'Duplicada', text: r.data.message || 'Ya existe'});
                        return;
                    }
                    Swal.fire({icon:'success', title:'Requisicion creada', timer:1400, showConfirmButton:false, toast:true, position:'top-end'});
                    this.currentPage = 1;
                    this.listarRequisiciones(localStorage.getItem("obraActiva"));
                }).catch(function (err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || 'Error al crear';
                    Swal.fire({icon:'error', title:'Error', text: msg});
                });
            },
            editRequisicion: function (index) {
                var idx = this.getSourceIndex(index);
                if (!this.requisiciones[idx]) return;
                this.requisiciones[idx].requisicion_EditShow = true;
                this.requisiciones[idx].requisicion_Numero = this.ultimosDigitos(this.requisiciones[idx].requisicion_Numero);
            },
            saveEditrequisicion: function (index, idReq, numeroReq, nombreReq) {
                var idx = this.getSourceIndex(index);
                if (!this.requisiciones[idx]) return;
                axios.post(url, { accion: 8, idReq: idReq, numeroReq: numeroReq, nombreReq: nombreReq })
                    .then((r) => {
                        this.requisiciones[idx].requisicion_EditShow = false;
                        if (r.data && r.data.numero_nuevo) {
                            this.requisiciones[idx].requisicion_Numero = r.data.numero_nuevo;
                        }
                        Swal.fire({icon:'success', title:'Actualizado', timer:1200, showConfirmButton:false, toast:true, position:'top-end'});
                    })
                    .catch((err) => {
                        this.requisiciones[idx].requisicion_EditShow = false;
                        var msg = (err.response && err.response.data && err.response.data.message) || 'Error al editar';
                        Swal.fire({icon:'error', title:'Error', text: msg});
                    });
            },
            deleteRequisicionShow: function (index, idReq) {
                Swal.fire({
                    title: 'Eliminar requisicion?',
                    text: 'Si tiene hojas integradas tambien se eliminaran. Esta accion no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                }).then((res) => {
                    if (!res.isConfirmed) return;
                    var idx = this.getSourceIndex(index);
                    if (!this.requisiciones[idx]) return;
                    axios.post(url, { accion: 9, idReq: idReq })
                        .then(() => {
                            Swal.fire({icon:'success', title:'Eliminada', timer:1200, showConfirmButton:false, toast:true, position:'top-end'});
                            this.listarRequisiciones(localStorage.getItem("obraActiva"));
                        })
                        .catch(function (err) {
                            var msg = (err.response && err.response.data && err.response.data.message) || 'Error al eliminar';
                            Swal.fire({icon:'error', title:'Error', text: msg});
                        });
                });
            },
            ultimosDigitos: function (folio) {
                if (!folio) return '';
                var partes = folio.split('-');
                var ultima = partes[partes.length - 1];
                var match = ultima.match(/\d+$/);
                return match ? match[0] : ultima;
            },
            init: function () {
                var obraId = localStorage.getItem("obraActiva");
                if (!obraId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin obra seleccionada',
                        text: 'Selecciona una obra primero.',
                        confirmButtonText: 'Ir a obras'
                    }).then(function () { window.location.href = './obras.php'; });
                    return;
                }
                this.infoObraActiva(obraId);
                this.listarRequisiciones(obraId);
                window.tfRequisicionesOpenCreate = this.addRequisicion.bind(this);
            }
        };
    }
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
