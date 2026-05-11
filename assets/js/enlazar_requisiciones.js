var url = "../api/crud_enlazar_requisiciones.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppPresion",
    data: {
        requisiciones: [],
        presiones: [],
        obras: [],
        obrasLista: [],
        requisicion: "",
        NameUser: "",
        gastosTotalPresion: 0,
        nombreRequisicion: "",
        fechaGeneracion: "",
        clave: ""
    },
    methods: {
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

                this.$nextTick(() => {
                    $(document).ready(function () {
                        $('[data-toggle="tooltip"]').tooltip(); // Inicializa los tooltips
                        $('#example').DataTable({
                            "order": [],
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
                    });
                });
            } catch (error) {
                console.error("Error al listar requisiciones:", error);
            }
        },
        consultarUsuario: async function (user_id) {
            try {
                const response = await axios.post(url, { accion: 2, id_user: user_id });
                this.users = response.data;
                // Verifica si hay datos en la respuesta
                if (this.users.length > 0) {
                    this.NameUser = this.users[0].user_name;
                    console.log(this.users);
                } else {
                    console.warn("No se encontraron usuarios.");
                    this.NameUser = null; // O maneja esto de otra manera según tu lógica
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
                console.log(this.presiones);
            } catch (error) {
                console.error("Error al consultar info de presión:", error);
            }
        },
        enlazarConPresion: async function (idHoja, idReq) {
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
        }
    },
    mounted: async function () {
        await this.listarObras();
        await this.consultarUsuario(localStorage.getItem("NameUser"));
        await this.infoObraActiva(localStorage.getItem("obraActiva"));
        await this.consultarInfoPresion(localStorage.getItem("IdPresion"));
        await this.listarRequisiciones(localStorage.getItem("obraActiva"));
    },
    computed: {

    }
});