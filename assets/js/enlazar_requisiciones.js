var url = "../api/crud_enlazar_requisiciones.php";
var url2 = ".";

function enlazarReqApp() {
    return {
        users: [],
        requisiciones: [],
        presiones: [],
        obras: [],
        obrasLista: [],
        requisicion: "",
        NameUser: "",
        gastosTotalPresion: 0,
        totalYaLigado: 0,
        presionTotal: 0,
        nombreRequisicion: "",
        fechaGeneracion: "",
        clave: "",
        initDataTable: function () {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;
            var tbl = window.jQuery('#example');
            if (!tbl.length) return;
            if (window.jQuery.fn.dataTable.isDataTable('#example')) {
                tbl.DataTable().destroy();
            }
            tbl.DataTable({
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
        ConsultarItemRq: function (idRq) {
            localStorage.setItem("idRequisicion", idRq);
            window.location.href = url2 + "/hojas_requisicion.php";
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 3, obra: obrasId });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error(error);
            }
        },
        listarRequisiciones: async function (idObra) {
            try {
                const response = await axios.post(url, { accion: 1, obra: idObra });
                this.requisiciones = response.data;
                console.log(this.requisiciones);
                setTimeout(this.initDataTable.bind(this), 0);
            } catch (error) {
                console.error("Error al listar requisiciones:", error);
            }
        },
        consultarUsuario: async function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            try {
                const response = await axios.post(url, { accion: 2 });
                this.users = response.data || [];
                if (this.users.length > 0 && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                } else {
                    console.warn("No se encontraron usuarios.");
                    this.NameUser = null;
                }
            } catch (error) {
                console.error("Error al consultar usuario:", error);
            }
        },
        listarObras: async function () {
            try {
                const response = await axios.post(url, { accion: 5 });
                this.obrasLista = response.data;
                console.log(response.data);
            } catch (error) {
                console.error("Error al listar obras:", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        consultarInfoPresion: async function (idPresion) {
            try {
                const response = await axios.post(url, { accion: 6, idPresion: idPresion });
                this.presiones = response.data;
                if (response.data && response.data[0]) {
                    this.totalYaLigado = parseFloat(response.data[0].totalYaLigado || 0);
                    this.presionTotal   = parseFloat(response.data[0].presiones_total || 0);
                }
                console.log(this.presiones);
            } catch (error) {
                console.error("Error al consultar info de presión:", error);
            }
        },
        enlazarConPresion: async function (idHoja, idReq, totalHoja) {
            var totalHojaNum = parseFloat(totalHoja || 0);
            var totalResultante = this.totalYaLigado + totalHojaNum;
            var montoYa = this.formatearMoneda(this.totalYaLigado, true);
            var montoHoja = this.formatearMoneda(totalHojaNum, true);
            var montoTotal = this.formatearMoneda(totalResultante, true);

            // C-M5: advertencia si el total resultante excede el presupuesto de la presión
            var excede = this.presionTotal > 0 && totalResultante > this.presionTotal;
            var excedenteLbl = excede
                ? '<p class="mb-0 mt-1 text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Excede el presupuesto de la presión por <strong>'
                  + this.formatearMoneda(totalResultante - this.presionTotal, true) + '</strong>.</p>'
                : '';

            var result = await Swal.fire({
                icon: excede ? 'warning' : 'info',
                title: 'Confirmar enlace',
                html: '<p class="mb-1">Esta hoja tiene un total de <strong>' + montoHoja + '</strong>.</p>'
                    + '<p class="mb-1">Total ya ligado a esta presión: <strong>' + montoYa + '</strong>.</p>'
                    + '<p class="mb-0">Total resultante: <strong class="text-primary">' + montoTotal + '</strong>.</p>'
                    + excedenteLbl,
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
            localStorage.setItem("idRequisicion", idReq);
            localStorage.setItem("idHoja", idHoja);
            localStorage.setItem("validate", true);
            window.location.href = url2 + "/items_requisicion.php";
        },
        irDireecion: function () {
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
        init: async function () {
            var obraId = localStorage.getItem("obraActiva");
            var presionId = localStorage.getItem("IdPresion");
            if (!obraId) { window.location.href = './index.php'; return; }
            await this.listarObras();
            await this.consultarUsuario();
            await this.infoObraActiva(obraId);
            await this.consultarInfoPresion(presionId);
            await this.listarRequisiciones(obraId);
        }
    };
}