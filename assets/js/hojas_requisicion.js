var url = "../api/crud_hojas_requisicion.php";
var url2 = ".";

function hojasReqApp() {
    return {
        users: [],
        requisiciones: [],
        hojas: [],
        presiones: [],
        obras: [],
        obrasLista: [],
        requisicion: "",
        NameUser: "",
        gastosTotalPresion: 0,
        nombreRequisicion: "",
        fechaGeneracion: "",
        clave: "",
        canReqEdit: !!(window.TF_LEGACY_PERMS && window.TF_LEGACY_PERMS.canReqEdit),
        canDireccion: !!(window.TF_LEGACY_PERMS && window.TF_LEGACY_PERMS.canDireccion),
        ConsultarItemHoja: async function (idHoja) {
            localStorage.setItem("idHoja", idHoja);
            localStorage.setItem("validate", false);
            window.location.href = url2 + "/items_requisicion.php";
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 3, obra: obrasId });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error("Error al obtener la información de la obra",error);
            }
        },
        listarHojas: async function (idRq) {
            try {
                const response = await axios.post(url, { accion: 1, IdReq: idRq });
                this.hojas = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error("Error al obtener las hojas", error);
            }
        },
        consultarUsuario: async function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            try {
                const response = await axios.post(url, { accion: 2 });
                this.users = response.data || [];
                if (this.users.length > 0 && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            } catch (error) {
                console.error("Error al obtener la información del usuario", error);
            }
        },
        listarObras:async function () {
            try {
                const response = await axios.post(url, { accion: 5 });
                this.obrasLista = response.data;
                console.log(this.obrasLista);
            } catch (error) {
                console.error("Error al obtener las obras", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        addHoja: function () {
            window.location.href = url2 + "/nueva_hoja.php";
        },
        infoReqActiva: async function(ReqId){
            try {
                const response = await axios.post(url, { accion: 7, IdReq: ReqId });
                this.requisiciones = response.data;
                console.log(this.requisiciones);
            } catch (error) {
                console.error("Error al obtener la información de la requisición", error);
            }
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        formatearMoneda: function (cadena, incluirSimbolo) {
            // Convertir la cadena a un número
            let numero = parseFloat(cadena);
            // Verificar si la conversión fue exitosa
            if (isNaN(numero)) {
                return null; // O puedes lanzar un error si prefieres
            }
            // Formatear el número como moneda en pesos mexicanos
            let formato = numero.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Retornar el formato con o sin el símbolo de pesos
            return incluirSimbolo ? "$ " + formato : formato;
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        eliminarHoja: async function(idHoja){
            const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-danger"
            },
            buttonsStyling: false
            });
           
            swalWithBootstrapButtons.fire({
            title: "¿Estas seguro de Eliminar la hoja?",
            text: "Esta accion no se puede revertir",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar",
            reverseButtons: true
            }).then((result) => {
            if (result.isConfirmed) {
                this.eliminarHojaBD(idHoja);
            } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
            ) {
                
            }
            });
        },
        eliminarHojaBD: function(idHoja){
            const Toast = Swal.mixin({
            toast: true,
            position: "bottom-start",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
            });
            axios.post(url, {accion: 8, idHoja: idHoja}).then(response => {
                console.log(response.data['status']);
                if(response.data['status'] == 'ok'){
                   Toast.fire({
                        icon: "success",
                        title: "Elemento Eliminado correctamente"
                    });
                    this.listarHojas(localStorage.getItem("idRequisicion"));
                }
            }).catch(error =>{
                console.error(error);
                Toast.fire({
                    icon: "error",
                    title: "Error al eliminar el elemento"
                });
            });
        },
        // ---- ITF-3: Cotizaciones adjuntas ----
        _cotHojaId: null,
        abrirCotizaciones: function (hojaId, hojaNum) {
            this._cotHojaId = hojaId;
            document.getElementById('cotModalHojaNum').textContent = hojaNum || '';

            // Mostrar zona de upload solo si el usuario puede editar
            var upZone = document.getElementById('cotUploadZone');
            if (upZone) upZone.classList.toggle('d-none', !this.canReqEdit);

            document.getElementById('cotLoadingSpinner').classList.remove('d-none');
            document.getElementById('cotListaContainer').classList.add('d-none');
            document.getElementById('cotUploadMsg') && (document.getElementById('cotUploadMsg').classList.add('d-none'));
            if (document.getElementById('cotNombreInput')) document.getElementById('cotNombreInput').value = '';
            if (document.getElementById('cotFileInput'))  document.getElementById('cotFileInput').value  = '';

            // Exponer referencia al contexto para el botón Subir (fuera de Alpine)
            window._cotApp = this;

            var modal = new bootstrap.Modal(document.getElementById('modalCotizaciones'));
            modal.show();
            this._cargarCotizaciones();
        },
        _cargarCotizaciones: function () {
            var self = this;
            axios.post('../api/crud_cotizaciones.php', { accion: 1, hoja_id: self._cotHojaId })
                .then(function (r) {
                    var lista = r.data || [];
                    var ul = document.getElementById('cotLista');
                    ul.innerHTML = '';
                    if (!lista.length) {
                        document.getElementById('cotEmptyMsg').classList.remove('d-none');
                    } else {
                        document.getElementById('cotEmptyMsg').classList.add('d-none');
                        lista.forEach(function (c) {
                            var kb = c.cotizacion_size ? (c.cotizacion_size / 1024).toFixed(0) + ' KB' : '';
                            var fecha = c.cotizacion_fechaSubida ? c.cotizacion_fechaSubida.slice(0, 10) : '';
                            var li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-center gap-2';
                            var esImagen = /\.(jpe?g|png)$/i.test(c.cotizacion_archivo || '');
                            var iconoClase = esImagen ? 'bi-file-earmark-image text-primary' : 'bi-file-earmark-pdf text-danger';
                            li.innerHTML =
                                '<span class="flex-grow-1">'
                                    + '<i class="bi ' + iconoClase + ' me-1"></i>'
                                    + '<strong>' + self._escHtml(c.cotizacion_nombre) + '</strong>'
                                    + (kb ? ' <span class="text-muted small">(' + kb + ')</span>' : '')
                                    + (fecha ? ' <span class="text-muted small ms-2">' + fecha + '</span>' : '')
                                + '</span>'
                                + '<div class="btn-group btn-group-sm">'
                                    + '<a href="../api/crud_cotizaciones.php?accion=4&cotizacion_id=' + c.cotizacion_id + '" target="_blank" class="btn btn-outline-primary" title="Ver / Imprimir"><i class="bi bi-eye"></i></a>'
                                    + (self.canReqEdit ? '<button class="btn btn-outline-danger" title="Eliminar" onclick="window._cotApp && window._cotApp._eliminarCotizacion(' + c.cotizacion_id + ')"><i class="bi bi-trash"></i></button>' : '')
                                + '</div>';
                            ul.appendChild(li);
                        });
                    }
                    document.getElementById('cotLoadingSpinner').classList.add('d-none');
                    document.getElementById('cotListaContainer').classList.remove('d-none');
                })
                .catch(function (e) {
                    console.error('Cotizaciones load error:', e);
                    document.getElementById('cotLoadingSpinner').classList.add('d-none');
                    document.getElementById('cotListaContainer').classList.remove('d-none');
                    document.getElementById('cotEmptyMsg').textContent = 'Error al cargar cotizaciones.';
                    document.getElementById('cotEmptyMsg').classList.remove('d-none');
                });
        },
        _eliminarCotizacion: function (cotId) {
            var self = this;
            if (!confirm('¿Eliminar esta cotización? Esta acción no se puede deshacer.')) return;
            axios.post('../api/crud_cotizaciones.php', { accion: 3, cotizacion_id: cotId })
                .then(function (r) {
                    if (r.data && r.data.ok) {
                        self._cargarCotizaciones();
                    } else {
                        alert('No se pudo eliminar la cotización.');
                    }
                })
                .catch(function () { alert('Error de conexión al eliminar.'); });
        },
        subirCotizacion: function () {
            var self = this;
            var nombreInput = document.getElementById('cotNombreInput');
            var fileInput   = document.getElementById('cotFileInput');
            var msgDiv      = document.getElementById('cotUploadMsg');
            if (!fileInput || !fileInput.files.length) {
                self._cotMsg('Selecciona un archivo PDF, JPG o PNG.', 'warning');
                return;
            }
            var file = fileInput.files[0];
            if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                self._cotMsg('Solo se aceptan archivos PDF, JPG o PNG.', 'danger');
                return;
            }
            var fd = new FormData();
            fd.append('accion', 2);
            fd.append('hoja_id', self._cotHojaId);
            fd.append('cotizacion', file);
            fd.append('nombre', (nombreInput && nombreInput.value.trim()) || '');
            var subirBtn = document.getElementById('cotSubirBtn');
            if (subirBtn) subirBtn.disabled = true;
            self._cotMsg('Subiendo…', 'info');
            axios.post('../api/crud_cotizaciones.php', fd, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(function (r) {
                if (r.data && r.data.ok) {
                    if (nombreInput) nombreInput.value = '';
                    if (fileInput)   fileInput.value   = '';
                    self._cotMsg('Cotización adjuntada correctamente.', 'success');
                    self._cargarCotizaciones();
                } else {
                    self._cotMsg(r.data && r.data.error ? r.data.error : 'Error al subir.', 'danger');
                }
            }).catch(function (e) {
                var msg = (e.response && e.response.data && e.response.data.error) || 'Error de red al subir.';
                self._cotMsg(msg, 'danger');
            }).finally(function () {
                if (subirBtn) subirBtn.disabled = false;
            });
        },
        _cotMsg: function (texto, tipo) {
            var d = document.getElementById('cotUploadMsg');
            if (!d) return;
            d.className = 'mt-2 small alert alert-' + tipo + ' py-1 px-2';
            d.textContent = texto;
            d.classList.remove('d-none');
        },
        _escHtml: function (str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        },
        // ---- Fin ITF-3 ----
        get montoTotalRequisicion() {
            return this.hojas.reduce(function (acc, h) {
                return acc + parseFloat(h.hojaRequisicion_total || 0);
            }, 0);
        },
        init: async function () {
            this.canReqEdit = !!(window.TF_LEGACY_PERMS && window.TF_LEGACY_PERMS.canReqEdit);
            this.canDireccion = !!(window.TF_LEGACY_PERMS && window.TF_LEGACY_PERMS.canDireccion);
            await this.consultarUsuario();
            var obraId = localStorage.getItem("obraActiva");
            var reqId = localStorage.getItem("idRequisicion");
            if (!obraId) { window.location.href = './obras.php'; return; }
            await this.listarObras();
            await this.infoObraActiva(obraId);
            await this.infoReqActiva(reqId);
            await this.listarHojas(reqId);
        }
    };
}