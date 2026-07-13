var url = "../api/crud_proveedor.php";
var __cfg = window.TF_PROV_CONFIG || {};

function proveedoresApp() {
    return {
        proveedores: [],
        cargando: true,
        filtro: "",
        filtroEstatus: "",
        paginaActual: 1,
        porPagina: 50,
        canManage: !!__cfg.canManage,
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
            axios.post(url, { accion: 6, id_prov: idProveedor, formValues: formValues }).then(function (resp) {
                if (resp.data && resp.data.duplicate) {
                    Swal.fire({
                        icon: "warning",
                        title: "Datos duplicados",
                        text: "La CLABE o cuenta bancaria ya pertenece a: \"" + resp.data.proveedor_nombre + "\". Usa datos distintos."
                    });
                    return;
                }
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
    };
}
