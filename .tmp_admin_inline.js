    var url = "../api/crud_admin.php";
    var __canAudit      = $js_canAudit;
    var __canAssignObra = $js_canAssignObra;
    var __canRole       = $js_canRole;

    function adminApp() {
        return {
            // --- pestanas ---
            activeTab: 'usuarios',
            // --- usuarios ---
            users: [],
            roles: [],
            obras: [],
            audit: [],
            auditTotal: 0,
            auditPage: 1,
            auditPages: 1,
            auditFilter: { user: '', accion: '', desde: '', hasta: '' },
            filterText: "",
            editing: false,
            saving: false,
            savingObra: false,
            loadingObras: false,
            obraFilter: '',
            obraAssignedIds: [],
            form: {
                user_id: null,
                user_nameUser: "",
                user_name: "",
                user_email: "",
                user_role_id: null,
                user_password: ""
            },
            obraForm: {
                user_id: null,
                user_obra_id: ""
            },
            obraTarget: {
                user_id: null,
                user_nameUser: ""
            },
            modal: null,
            obraModal: null,
            // --- roles y permisos ---
            permissions: [],
            selectedRole: null,
            rolePerms: [],
            loadingPerms: false,
            roleForm: {
                role_id: null,
                role_codigo: "",
                role_nombre: "",
                role_descripcion: "",
                role_nivel: 30
            },
            editingRole: false,
            savingRole: false,
            roleModal: null,
            get countActivos() { return this.users.filter(function(u){return u.user_estatus === 'ACTIVO';}).length; },
            get countInactivos() { return this.users.filter(function(u){return u.user_estatus !== 'ACTIVO';}).length; },
            get filteredUsers() {
                var q = (this.filterText || "").trim().toLowerCase();
                if (!q) return this.users;
                return this.users.filter(function(u){
                    return (u.user_nameUser || "").toLowerCase().indexOf(q) !== -1
                        || (u.user_name     || "").toLowerCase().indexOf(q) !== -1
                        || (u.role_nombre   || "").toLowerCase().indexOf(q) !== -1
                        || (u.user_email    || "").toLowerCase().indexOf(q) !== -1;
                });
            },
            get filteredObrasModal() {
                var q = (this.obraFilter || "").trim().toLowerCase();
                if (!q) return this.obras;
                return this.obras.filter(function(o){
                    return (o.obras_nombre || "").toLowerCase().indexOf(q) !== -1;
                });
            },
            get assignableRoles() {
                return this.roles.filter(function(r) {
                    return (r.role_estatus || 'ACTIVO') === 'ACTIVO';
                });
            },
            get auditPageRange() {
                var pages = this.auditPages;
                var cur   = this.auditPage;
                var range = [];
                var start = Math.max(1, cur - 2);
                var end   = Math.min(pages, cur + 2);
                for (var i = start; i <= end; i++) range.push(i);
                return range;
            },
            get permsByModule() {
                var map = {};
                this.permissions.forEach(function(p) {
                    var m = p.permission_modulo || 'General';
                    if (!map[m]) map[m] = [];
                    map[m].push(p);
                });
                return map;
            },
            setTab: function (tab) {
                this.activeTab = tab;
                if (tab === 'roles' && this.permissions.length === 0) {
                    axios.post(url, { accion: 10 }).then(function(r){ this.permissions = r.data || []; }.bind(this));
                }
            },
            // --- Roles y permisos ---
            selectRole: function (r) {
                this.selectedRole = r;
                this.loadingPerms = true;
                axios.post(url, { accion: 11, role_id: r.role_id })
                    .then(function(res) { this.rolePerms = (res.data || []).map(Number); }.bind(this))
                    .finally(function() { this.loadingPerms = false; }.bind(this));
            },
            hasPermission: function (permId) {
                return this.rolePerms.indexOf(Number(permId)) !== -1;
            },
            togglePerm: function (p, checked) {
                var self = this;
                axios.post(url, {
                    accion: 12,
                    role_id: self.selectedRole.role_id,
                    permission_id: p.permission_id,
                    grant: checked
                }).then(function() {
                    if (checked) {
                        if (!self.hasPermission(p.permission_id)) self.rolePerms.push(Number(p.permission_id));
                    } else {
                        self.rolePerms = self.rolePerms.filter(function(id){ return id !== Number(p.permission_id); });
                    }
                    Swal.fire({ icon:'success', title: checked ? 'Permiso concedido' : 'Permiso revocado',
                        timer:1000, showConfirmButton:false, toast:true, position:'top-end' });
                }).catch(function(err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || 'Error';
                    Swal.fire({ icon:'error', title:'Error', text: msg });
                });
            },
            openCreateRole: function () {
                this.editingRole = false;
                this.roleForm = { role_id: null, role_codigo: '', role_nombre: '', role_descripcion: '', role_nivel: 30 };
                this.roleModal.show();
            },
            openEditRole: function (r) {
                this.editingRole = true;
                this.roleForm = {
                    role_id: r.role_id,
                    role_codigo: r.role_codigo,
                    role_nombre: r.role_nombre,
                    role_descripcion: r.role_descripcion || '',
                    role_nivel: r.role_nivel
                };
                this.roleModal.show();
            },
            saveRole: function () {
                if (this.savingRole) return;
                this.savingRole = true;
                var f = this.roleForm;
                var req = this.editingRole
                    ? { accion: 14, role_id: f.role_id, role_nombre: f.role_nombre, role_descripcion: f.role_descripcion, role_nivel: f.role_nivel }
                    : { accion: 13, role_codigo: f.role_codigo, role_nombre: f.role_nombre, role_descripcion: f.role_descripcion, role_nivel: f.role_nivel };
                axios.post(url, req)
                    .then(function() {
                        Swal.fire({ icon:'success', title:'Guardado', timer:1200, showConfirmButton:false, toast:true, position:'top-end' });
                        this.roleModal.hide();
                        axios.post(url, { accion: 2 }).then(function(r){ this.roles = r.data || []; }.bind(this));
                        if (this.editingRole && this.selectedRole) {
                            Object.assign(this.selectedRole, { role_nombre: f.role_nombre, role_descripcion: f.role_descripcion, role_nivel: f.role_nivel });
                        }
                    }.bind(this))
                    .catch(function(err) {
                        var msg = (err.response && err.response.data && err.response.data.message) || 'Error';
                        Swal.fire({ icon:'error', title:'Error', text: msg });
                    })
                    .finally(function(){ this.savingRole = false; }.bind(this));
            },
            toggleRoleStatus: function (r) {
                var nuevo = r.role_estatus === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
                Swal.fire({
                    title: (nuevo === 'INACTIVO' ? 'Desactivar' : 'Activar') + ' rol?',
                    text: r.role_nombre, icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: nuevo === 'INACTIVO' ? 'Si, desactivar' : 'Si, activar'
                }).then(function(res) {
                    if (!res.isConfirmed) return;
                    axios.post(url, { accion: 15, role_id: r.role_id, role_estatus: nuevo })
                        .then(function() {
                            r.role_estatus = nuevo;
                            if (this.selectedRole && this.selectedRole.role_id === r.role_id) {
                                this.selectedRole.role_estatus = nuevo;
                            }
                            Swal.fire({ icon:'success', title:'Listo', timer:1000, showConfirmButton:false, toast:true, position:'top-end' });
                        }.bind(this))
                        .catch(function(err) {
                            var msg = (err.response && err.response.data && err.response.data.message) || 'Error';
                            Swal.fire({ icon:'error', title:'Error', text: msg });
                        });
                }.bind(this));
            },
            // --- Usuarios ---
            loadAll: function () {
                var self = this;
                function apiErr(label) {
                    return function(err) {
                        var msg = (err.response && err.response.data && err.response.data.message) || err.message || 'Error desconocido';
                        console.error('Admin API error [' + label + ']:', msg, err);
                        Swal.fire({ icon: 'error', title: 'Error al cargar ' + label, text: msg, toast: true, position: 'top-end', timer: 6000, showConfirmButton: false });
                    };
                }
                axios.post(url, { accion: 1 }).then(function(r){ self.users = r.data || []; }).catch(apiErr('usuarios'));
                axios.post(url, { accion: 2 }).then(function(r){ self.roles = r.data || []; }).catch(apiErr('roles'));
                if (__canAssignObra) {
                    axios.post(url, { accion: 8 }).then(function(r){ self.obras = r.data || []; }).catch(apiErr('obras'));
                }
                if (__canAudit) self.loadAudit();
            },
            loadAudit: function (page) {
                var self = this;
                var p = page || 1;
                self.auditPage = p;
                var payload = {
                    accion: 7,
                    limite: 50,
                    page: p,
                    filter_user:   self.auditFilter.user   || '',
                    filter_accion: self.auditFilter.accion || '',
                    filter_desde:  self.auditFilter.desde  || '',
                    filter_hasta:  self.auditFilter.hasta  || '',
                };
                axios.post(url, payload).then(function(r){
                    var d = r.data || {};
                    self.audit      = Array.isArray(d) ? d : (d.rows  || []);
                    self.auditTotal = d.total  || self.audit.length;
                    self.auditPages = d.pages  || 1;
                    self.auditPage  = d.page   || p;
                }).catch(function(err) {
                    var msg = (err.response && err.response.data && err.response.data.message) || err.message;
                    console.error('Admin API error [audit]:', msg);
                });
            },
            resetAuditFilter: function () {
                this.auditFilter = { user: '', accion: '', desde: '', hasta: '' };
                this.loadAudit(1);
            },
            exportAuditCSV: function () {
                var rows = this.audit;
                if (!rows.length) { alert('No hay datos para exportar.'); return; }
                var headers = ['Fecha','Usuario','Accion','Modulo','Detalle','IP'];
                var csv = headers.join(',') + '\n';
                rows.forEach(function(a) {
                    var row = [
                        a.audit_createdAt || '',
                        a.audit_userName  || '',
                        a.audit_accion    || '',
                        a.audit_modulo    || '',
                        (a.audit_detalle  || '').toString().replace(/"/g,'""'),
                        a.audit_ip        || '',
                    ].map(function(v){ return '"' + v + '"'; });
                    csv += row.join(',') + '\n';
                });
                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'bitacora_' + new Date().toISOString().slice(0,10) + '.csv';
                link.click();
            },
            openCreate: function () {
                this.editing = false;
                this.form = {
                    user_id: null, user_nameUser: "", user_name: "", user_email: "",
                    user_role_id: this.assignableRoles.length ? this.assignableRoles[this.assignableRoles.length - 1].role_id : null,
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
            isObraAssigned: function (obraId) {
                return this.obraAssignedIds.indexOf(Number(obraId)) !== -1;
            },
            openAssignObra: function (u) {
                this.obraTarget = {
                    user_id: u.user_id,
                    user_nameUser: u.user_nameUser || ''
                };
                this.obraFilter = '';
                this.obraAssignedIds = [];
                this.loadingObras = true;
                this.obraModal.show();
                // Cargar obras asignadas del usuario (accion 16)
                var self = this;
                axios.post(url, { accion: 16, user_id: u.user_id })
                    .then(function(res) {
                        self.obraAssignedIds = (res.data || []).map(function(o){ return Number(o.obras_id); });
                    })
                    .finally(function() { self.loadingObras = false; });
            },
            toggleObraAssign: function (obraId, grant) {
                var self = this;
                axios.post(url, { accion: 17, user_id: self.obraTarget.user_id, obras_id: obraId, grant: grant })
                    .then(function(res) {
                        if (grant) {
                            if (self.obraAssignedIds.indexOf(Number(obraId)) === -1)
                                self.obraAssignedIds.push(Number(obraId));
                        } else {
                            self.obraAssignedIds = self.obraAssignedIds.filter(function(id){ return id !== Number(obraId); });
                        }
                        self.loadAll(); // refresca tabla para actualizar obra principal
                    })
                    .catch(function(err) {
                        var msg = (err.response && err.response.data && err.response.data.message) || 'Error al actualizar';
                        Swal.fire({icon:'error', title:'Error', text: msg});
                    });
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
            },
            init: function () {
                this.modal = new bootstrap.Modal(document.getElementById('userModal'));
                this.obraModal = new bootstrap.Modal(document.getElementById('obraModal'));
                if (document.getElementById('roleModal')) {
                    this.roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
                }
                this.loadAll();
                window.tfAdminOpenCreate = this.openCreate.bind(this);
            }
        };
    }
