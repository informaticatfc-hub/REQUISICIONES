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
        initDataTable: function () {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;
            var table = window.jQuery('#example');
            if (!table.length) return;
            if (window.jQuery.fn.dataTable.isDataTable('#example')) {
                table.DataTable().destroy();
            }
            table.DataTable({
                "order": [],
                "responsive": true,
                "autoWidth": false,
                "language": {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sSearch": "Buscar:",
                    "sUrl": "",
                    "sInfoThousands": ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                }
            });
            window.jQuery('[data-toggle="tooltip"]').tooltip();
        },
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
                this.hojas = response.data;
                console.log(this.hojas);
                setTimeout(this.initDataTable.bind(this), 0);
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
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable('#example')) {
                        window.jQuery('#example').DataTable().destroy();
                    }
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
        // C-M3: Duplicar hoja (copia la hoja con todos sus items con estatus NUEVO)
        duplicarHoja: async function (idHoja) {
            var confirm = await Swal.fire({
                title: '¿Duplicar esta hoja?',
                text: 'Se creará una copia de la hoja con todos sus ítems en estado NUEVO.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, duplicar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirm.isConfirmed) return;

            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-start',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            try {
                const resp = await axios.post(url, { accion: 9, idHoja: idHoja });
                if (resp.data && resp.data.status === 'ok') {
                    Toast.fire({ icon: 'success', title: 'Hoja duplicada correctamente' });
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable('#example')) {
                        window.jQuery('#example').DataTable().destroy();
                    }
                    this.listarHojas(localStorage.getItem('idRequisicion'));
                } else {
                    Toast.fire({ icon: 'error', title: 'Error al duplicar la hoja' });
                }
            } catch (e) {
                console.error(e);
                Toast.fire({ icon: 'error', title: 'Error al duplicar la hoja' });
            }
        },
        // R-M2 / R-M3: Historial de estatus de una hoja (abre modal con timeline)
        verHistorialHoja: async function (idHoja, numero) {
            var modal = new bootstrap.Modal(document.getElementById('modalHistorialHoja'));
            document.getElementById('histModalHojaNum').textContent = 'Hoja N° ' + numero;
            document.getElementById('histModalLoading').classList.remove('d-none');
            document.getElementById('histModalContent').classList.add('d-none');
            modal.show();

            try {
                var resp = await axios.post(url, { accion: 10, idHoja: idHoja });
                var rows = resp.data || [];
                var timeline = document.getElementById('histModalTimeline');
                var empty   = document.getElementById('histModalEmpty');

                var colorMap = {
                    'NUEVO': 'secondary', 'PENDIENTE': 'warning', 'REVISION': 'warning',
                    'LIGADA': 'info', 'RECHAZADA': 'danger', 'AUTORIZADA': 'primary', 'PAGADA': 'success'
                };

                if (rows.length === 0) {
                    timeline.innerHTML = '';
                    empty.classList.remove('d-none');
                } else {
                    empty.classList.add('d-none');
                    timeline.innerHTML = rows.map(function (r) {
                        var color = colorMap[r.nuevo] || 'secondary';
                        var fecha = r.fecha ? r.fecha.substring(0, 16).replace('T', ' ') : '—';
                        var antes = r.antes ? ('<span class="badge bg-' + (colorMap[r.antes] || 'secondary') + '">' + r.antes + '</span> → ') : '';
                        return '<div class="d-flex gap-3 mb-3 align-items-start">' +
                            '<div class="d-flex flex-column align-items-center">' +
                            '<span class="badge bg-' + color + ' fs-6 rounded-circle p-2"><i class="bi bi-circle-fill" style="font-size:.5rem"></i></span>' +
                            '</div>' +
                            '<div class="flex-grow-1">' +
                            '<div class="d-flex flex-wrap align-items-center gap-2">' +
                            antes +
                            '<span class="badge bg-' + color + '">' + r.nuevo + '</span>' +
                            '<small class="text-muted ms-auto">' + fecha + '</small>' +
                            '</div>' +
                            (r.usuario ? '<small class="text-muted">Por: <strong>' + r.usuario + '</strong></small>' : '') +
                            (r.comentario ? '<p class="mb-0 mt-1 small fst-italic">' + r.comentario + '</p>' : '') +
                            '</div>' +
                            '</div>';
                    }).join('<hr class="my-1">');
                }
            } catch (e) {
                console.error(e);
                document.getElementById('histModalTimeline').innerHTML =
                    '<p class="text-danger">Error al cargar el historial.</p>';
            } finally {
                document.getElementById('histModalLoading').classList.add('d-none');
                document.getElementById('histModalContent').classList.remove('d-none');
            }
        },
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