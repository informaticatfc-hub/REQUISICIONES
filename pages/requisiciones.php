<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

// Acceso minimo: poder ver requisiciones
if (!tf_has_permission('requisiciones.view', $__user)) {
    tf_abort(403, 'No tienes permisos para ver requisiciones');
}

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
$tf_show_admin     = in_array($usuario_rolCode, ['admin'], true);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

$tf_subbar_extra = $canCreate
    ? '<div class="tf-subbar-actions">
           <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" onclick="document.getElementById(\'AppPresion\').__vue__.addRequisicion()">
               <i class="bi bi-file-earmark-plus"></i> Nueva requisicion
           </button>
       </div>'
    : '';

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppPresion" class="tf-page-inner">

    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Obra <span v-cloak>{{ obras[0] ? obras[0].obras_nombre : '—' }}</span></span>
            <h1 class="tf-page-title">Requisiciones</h1>
            <p class="tf-page-lead">
                Consulta y gestiona las requisiciones de compra de la obra activa.
            </p>
        </div>
        <div class="tf-page-header-actions">
            <?php if ($canCreate): ?>
            <button type="button" class="tf-btn tf-btn-primary" @click="addRequisicion">
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
            <div class="tf-kpi-value" v-cloak>{{ requisiciones.length }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-warning"><i class="bi bi-folder2-open"></i></span>
                <span class="tf-kpi-label">Abiertas</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ countAbiertas }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-success"><i class="bi bi-check-circle-fill"></i></span>
                <span class="tf-kpi-label">Cerradas</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ countCerradas }}</div>
        </article>
        <article class="tf-kpi">
            <div class="tf-kpi-head">
                <span class="tf-kpi-icon tf-kpi-icon-danger"><i class="bi bi-x-octagon-fill"></i></span>
                <span class="tf-kpi-label">Filtradas</span>
            </div>
            <div class="tf-kpi-value" v-cloak>{{ filteredRequisiciones.length }}</div>
        </article>
    </section>

    <!-- Tabla de requisiciones -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div>
                <h2 class="tf-card-title">
                    <i class="bi bi-list-check"></i> Listado
                </h2>
                <p class="tf-card-sub" v-cloak>
                    {{ requisiciones.length }} requisiciones registradas en esta obra
                </p>
            </div>
            <input v-model="filterText"
                   type="search"
                   class="form-control form-control-sm"
                   placeholder="Buscar por numero, nombre o clave..."
                   style="max-width:280px">
        </header>
        <div class="tf-card-body p-0" style="overflow-x:auto">
            <table class="tf-admin-table" v-cloak>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Nombre</th>
                        <th style="width:100px">Clave</th>
                        <th style="width:120px">Estado</th>
                        <th style="width:180px;text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(req, indice) in filteredRequisiciones" :key="req.requisicion_id">
                        <td>
                            <strong v-if="!req.requisicion_EditShow">{{ req.requisicion_Numero }}</strong>
                            <input v-else
                                   type="text"
                                   class="form-control form-control-sm"
                                   v-model="req.requisicion_Numero">
                        </td>
                        <td>
                            <span v-if="!req.requisicion_EditShow">{{ req.requisicion_Nombre }}</span>
                            <input v-else
                                   type="text"
                                   class="form-control form-control-sm"
                                   v-model="req.requisicion_Nombre">
                        </td>
                        <td><code style="font-size:.8rem">{{ req.requisicion_Clave }}</code></td>
                        <td>
                            <span class="tf-status"
                                  :class="req.requisicion_estatus === 'ABIERTO' ? 'tf-status-warning' : 'tf-status-active'">
                                {{ req.requisicion_estatus }}
                            </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    @click="ConsultarItemRq(req.requisicion_id)"
                                    title="Consultar hojas de requisicion">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if ($canEdit): ?>
                            <button type="button" class="tf-btn tf-btn-ghost tf-btn-sm"
                                    v-if="!req.requisicion_EditShow"
                                    @click="editRequisicion(indice)"
                                    title="Editar requisicion">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="tf-btn tf-btn-primary tf-btn-sm"
                                    v-if="req.requisicion_EditShow"
                                    @click="saveEditrequisicion(indice, req.requisicion_id, req.requisicion_Numero, req.requisicion_Nombre)"
                                    title="Guardar cambios">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button type="button" class="tf-btn tf-btn-danger tf-btn-sm"
                                    @click="deleteRequisicionShow(indice, req.requisicion_id)"
                                    title="Eliminar requisicion">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr v-if="!filteredRequisiciones.length">
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:1.5rem"></i>
                            <div>Sin requisiciones para esta obra</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php
$tf_use_datatables = false;  // remplazado por filtrado client-side simple
$tf_use_jquery     = false;
$canCreateJs = $canCreate ? 'true' : 'false';
$tf_inline_script = <<<JS
    var url = "../api/crud_Requisiciones.php";
    var CAN_CREATE = $canCreateJs;

    var appRequesition = new Vue({
        el: "#AppPresion",
        data: {
            requisiciones: [],
            obras: [{ obras_nombre: '' }],
            filterText: "",
            nombreRequisicion: "",
            fechaGeneracion: "",
            clave: "",
            folioReq: "",
            hojaReq: ""
        },
        computed: {
            countAbiertas: function () {
                return this.requisiciones.filter(function(r){ return r.requisicion_estatus === 'ABIERTO'; }).length;
            },
            countCerradas: function () {
                return this.requisiciones.filter(function(r){ return r.requisicion_estatus === 'CERRADO'; }).length;
            },
            filteredRequisiciones: function () {
                var q = (this.filterText || "").trim().toLowerCase();
                if (!q) return this.requisiciones;
                return this.requisiciones.filter(function(r){
                    return (r.requisicion_Numero || "").toLowerCase().indexOf(q) !== -1
                        || (r.requisicion_Nombre || "").toLowerCase().indexOf(q) !== -1
                        || (r.requisicion_Clave  || "").toLowerCase().indexOf(q) !== -1;
                });
            }
        },
        methods: {
            ConsultarItemRq: function (idRq) {
                localStorage.setItem("idRequisicion", idRq);
                window.location.href = "./hojas_requisicion.php";
            },
            infoObraActiva: function (obrasId) {
                return axios.post(url, { accion: 3, obra: obrasId }).then(function(r){
                    this.obras = r.data && r.data.length ? r.data : [{ obras_nombre: '—', obra_automatico: 0 }];
                }.bind(this));
            },
            listarRequisiciones: function (idObra) {
                return axios.post(url, { accion: 1, obra: idObra }).then(function(r){
                    this.requisiciones = (r.data || []).map(function(req){
                        req.requisicion_EditShow = false;
                        return req;
                    });
                }.bind(this));
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
                }).then(function () {
                    Swal.fire({icon:'success', title:'Requisicion creada', timer:1400, showConfirmButton:false, toast:true, position:'top-end'});
                    this.listarRequisiciones(localStorage.getItem("obraActiva"));
                }.bind(this)).catch(function(err){
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
                }).then(function (r) {
                    if (r.data && r.data.success === false) {
                        Swal.fire({icon:'warning', title:'Duplicada', text: r.data.message || 'Ya existe'});
                        return;
                    }
                    Swal.fire({icon:'success', title:'Requisicion creada', timer:1400, showConfirmButton:false, toast:true, position:'top-end'});
                    this.listarRequisiciones(localStorage.getItem("obraActiva"));
                }.bind(this)).catch(function(err){
                    var msg = (err.response && err.response.data && err.response.data.message) || 'Error al crear';
                    Swal.fire({icon:'error', title:'Error', text: msg});
                });
            },
            editRequisicion: function (index) {
                var idx = this.requisiciones.indexOf(this.filteredRequisiciones[index]);
                if (idx === -1) idx = index;
                this.\$set(this.requisiciones[idx], 'requisicion_EditShow', true);
                this.requisiciones[idx].requisicion_Numero = this.ultimosDigitos(this.requisiciones[idx].requisicion_Numero);
            },
            saveEditrequisicion: function (index, idReq, numeroReq, nombreReq) {
                var idx = this.requisiciones.indexOf(this.filteredRequisiciones[index]);
                if (idx === -1) idx = index;
                axios.post(url, { accion: 8, idReq: idReq, numeroReq: numeroReq, nombreReq: nombreReq })
                    .then(function (r) {
                        this.requisiciones[idx].requisicion_EditShow = false;
                        if (r.data && r.data.numero_nuevo) {
                            this.requisiciones[idx].requisicion_Numero = r.data.numero_nuevo;
                        }
                        Swal.fire({icon:'success', title:'Actualizado', timer:1200, showConfirmButton:false, toast:true, position:'top-end'});
                    }.bind(this))
                    .catch(function (err) {
                        this.requisiciones[idx].requisicion_EditShow = false;
                        var msg = (err.response && err.response.data && err.response.data.message) || 'Error al editar';
                        Swal.fire({icon:'error', title:'Error', text: msg});
                    }.bind(this));
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
                }).then(function (res) {
                    if (!res.isConfirmed) return;
                    var idx = this.requisiciones.indexOf(this.filteredRequisiciones[index]);
                    if (idx === -1) idx = index;
                    axios.post(url, { accion: 9, idReq: idReq })
                        .then(function () {
                            Swal.fire({icon:'success', title:'Eliminada', timer:1200, showConfirmButton:false, toast:true, position:'top-end'});
                            this.requisiciones.splice(idx, 1);
                        }.bind(this))
                        .catch(function (err) {
                            var msg = (err.response && err.response.data && err.response.data.message) || 'Error al eliminar';
                            Swal.fire({icon:'error', title:'Error', text: msg});
                        });
                }.bind(this));
            },
            ultimosDigitos: function (folio) {
                if (!folio) return '';
                var partes = folio.split('-');
                var ultima = partes[partes.length - 1];
                var match = ultima.match(/\d+\$/);
                return match ? match[0] : ultima;
            }
        },
        mounted: function () {
            var obraId = localStorage.getItem("obraActiva");
            if (!obraId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin obra seleccionada',
                    text: 'Selecciona una obra primero.',
                    confirmButtonText: 'Ir a obras'
                }).then(function(){ window.location.href = './obras.php'; });
                return;
            }
            this.infoObraActiva(obraId);
            this.listarRequisiciones(obraId);
        }
    });
JS;

include __DIR__ . '/../includes/layout_bottom.php';
?>
