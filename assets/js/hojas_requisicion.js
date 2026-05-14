var url = "../api/crud_hojas_requisicion.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppHojas",
    data: {
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
        clave: ""
    },
    methods: {
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

                this.$nextTick(() => {
                    $(document).ready(function() {
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
        eleminarHoja: async function(idHoja){
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
                    var table = $('#example').DataTable();
                    // Para reinicializarlo, primero destrúyelo
                    table.destroy();
                    this.listarHojas(localStorage.getItem("idRequisicion"));
                }
            }).catch(error =>{
                console.error(error);
                Toast.fire({
                    icon: "error",
                    title: "Error al eliminar el elemento"
                });
            });
        }
    },
    mounted: async function () {
        await this.consultarUsuario();
        var obraId = localStorage.getItem("obraActiva");
        var reqId = localStorage.getItem("idRequisicion");
        if (!obraId) { window.location.href = './obras.php'; return; }
        await this.listarObras();
        await this.infoObraActiva(obraId);
        await this.infoReqActiva(reqId);
        await this.listarHojas(reqId);
    },
    computed: {

    }
});