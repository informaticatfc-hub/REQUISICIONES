var url = "../api/crud_presionDetail.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppPresionDetail",
    data: {
        users: [],
        presiones: [],
        obras: [],
        obraActiva: [],
        NameUser: "",
        semana: "",
        dia: "",
        PagoParcial: "",
        FechaPago: "",
        BancoPago: "",
        timeNow: "",
        estatus: ""
    },
    methods: {
        consultarUsuario: async function (user_id) {
            try {
                const response = await axios.post(url, { accion: 1, id_user: user_id });
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            } catch (error) {
                console.error("Error al consultar usuario", error);
            }
        },
        listarObras: async function () {
            try {
                const response = await axios.post(url, { accion: 2 });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error("Error al listar las Obras:",error);
            }
        },
        cargarDatosPresion: async function (IdPresion) {
            try {
                const response = await axios.post(url, { accion: 3, idPresion: IdPresion, dia: this.dia, semana: this.semana });
                this.presiones = response.data;
                console.log(this.presiones);

                this.$nextTick(() => {
                    $(document).ready(function(){
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
                console.error("Error al cargar las Presiones:",error);
            }
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 4, obra: obrasId });
                this.obraActiva = response.data;
                console.log(this.obraActiva);
            } catch (error) {
                console.error("Error al cargar la Obra Activa:", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        asignarDiaySamana: async function () {
            this.semana = localStorage.getItem("Semana");
            this.dia = localStorage.getItem("Dia");
        },
        ordenarDatosPresion(dataArray) {
            var auxRow = {
                'clave': "",
                'requisicion': "",
                'proveedor': "",
                'concepto': [],
                'adeudo': "",
                'neto': "",
                'observaciones': [],
                'formaPago': ""
            };
            var AuxArray = [];

            for (var i = 0; i < dataArray.length; i++) {

            }
        },
        getWeekNumber: function (date) {
            const onejan = new Date(date.getFullYear(), 0, 1);
            const week = Math.ceil((((date - onejan) / 86400000) + onejan.getDay() + 1) / 7);
            return week;
        },
        getDayOfWeek: function (date) {
            const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            return days[date.getDay() + 1];
        },
        getCurrentDate: function () {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getTime: function () {
            const currentTime = new Date();
            const hours = currentTime.getHours();
            const minutes = currentTime.getMinutes();
            const seconds = currentTime.getSeconds();

            const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            return formattedTime;
        },
        pagarItem: async function (idHoja, fecha, banco) {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Aprobaras este este Concepto para pago?",
                text: "Esta operacion no se puede revertir",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.marcarpagado(idHoja, fecha, banco);
                    swalWithBootstrapButtons.fire({
                        title: "Pagado",
                        text: "El articulo se marco como pagado",
                        icon: "success"
                    });
                } 
            });
        },
        marcarpagado: function(idHoja, fecha, banco){
              //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
              var estatus = "PAGADO";
              axios.post(url, { accion: 5, idHoja:idHoja , fechaPago: fecha, bancoPago: banco, status: estatus}).then(response => {
                var table = $('#example').DataTable();
                // Para reinicializarlo, primero destrúyelo
                table.destroy();
                this.cargarDatosPresion(localStorage.getItem("IdPresion"));  
                console.log(response.data);
              });
        },
        imprimirReq: function(NumReq,clave,id_hoja){
            console.log("Obras: ");
            console.log(this.obraActiva[0]);
             axios.post(url, { accion: 8, idHoja: id_hoja}).then(response => {
                 console.log(response.data);
                  generarPDFRequisicion(
                    NumReq, // Número de la requisición
                    clave, // Clave de la requisición
                    response.data[0]['infoHoja'], // Información de la Hoja
                    this.NameUser, // Nombre del usuario
                    response.data[0]['items'], // Items de la Hoja
                    this.obraActiva[0] // Información de la obra
                ); 
             });
        },
        
        exportarExcel: function () {
            var total = this.sumatoria(this.presiones,"total");
            var adeudo = this.sumatoria(this.presiones,"adeudo");

            axios.post(url, { accion: 6, datos: JSON.stringify(this.presiones), namePres: this.obraActiva[0].obras_nombre ,export: "", total: total, adeudo: adeudo }, { responseType: 'blob' })
                .then(response => {
                    // Crear un objeto URL para el blob
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    // Crear un enlace para descargar el archivo
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', 'PRESIONES DE LA SEMANA "'+this.semana+'" Y DIA "'+this.dia+'" DE LA OBRA "'+this.obraActiva[0].obras_nombre+'".xls'); // Nombre del archivo que se descargará
                    // Agregar el enlace al DOM
                    document.body.appendChild(link);
                    // Hacer clic en el enlace para iniciar la descarga
                    link.click();
                    // Limpiar el DOM
                    link.remove();
                })
                .catch(error => {
                    console.error('Error al descargar el archivo:', error);
                });
        },
        cerrarPresion: async function () {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Quieres cerrar esta presion?",
                text: "Esta operacion no se puede revertir",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closePresion(localStorage.getItem("IdPresion"));
                    swalWithBootstrapButtons.fire({
                        title: "CARRADA",
                        text: "La presion a sido cerrada con exito.",
                        icon: "success"
                    }).then(() => {
                        window.location.reload();
                    });
                } 
            });
        },
        closePresion: function (idPresion) {
            axios.post(url, { accion: 7, idPresion: idPresion, Presiones: JSON.stringify(this.presiones)}).then(response => {
                console.log(response.data);
            });
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        consultarEstatus: async function(idPresion){
            try {
                const response = await   axios.post(url, { accion: 9, idPresion: idPresion});
                this.estatus = response.data[0]['presiones_estatus'];
                console.log(response.data[0]['presiones_estatus']);
            } catch (error) {
                console.error("Error al consultar el estatus de la presion:", error);
            }
        },
        cambiarBooleano: function (valor, indice) {
            this.presiones[indice].showDetail = !valor; // Devuelve el valor opuesto
            if (!valor) {
                this.presiones[indice].atrClass = "text-left align-middle inline-block fs-6";
                this.presiones[indice].strStyle = "max-width: auto;";
            }
            else {
                this.presiones[indice].atrClass = "text-left align-middle inline-block text-truncate fs-6";
                this.presiones[indice].strStyle = "max-width: 100px;";
            } 
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
            return incluirSimbolo ? "$" + formato : formato;
        },
        sumatoria: function(arreglo, indice){
            var totalRetunr = 0;
            arreglo.forEach(function(elemento) {
                totalRetunr += parseFloat(elemento[indice]);
            });
            return totalRetunr;
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        }
    },
    mounted: async function () {
        await this.listarObras();
        await this.asignarDiaySamana();
        await this.infoObraActiva(localStorage.getItem("obraActiva"));
        await this.consultarUsuario(localStorage.getItem("NameUser"));
        await this.consultarEstatus(localStorage.getItem("IdPresion"));
        await this.cargarDatosPresion(localStorage.getItem("IdPresion"));
    },
    computed: {

    }
});