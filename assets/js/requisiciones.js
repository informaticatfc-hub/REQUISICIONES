var url = "../api/crud_Requisiciones.php";
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
        clave: "",
        folioReq: "",
        hojaReq: ""
    },
    methods: {
        ConsultarItemRq: async function (idRq) {
            localStorage.setItem("idRequisicion", idRq);
            window.location.href = url2 + "/hojas_requisicion.php";
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 3, obra: obrasId });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error("Error al consultar la informacion de la Obra:", error);
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
                console.error("Error al listar las Requisiciones", error);
            }
        },
        consultarUsuario: async function (user_id) {
            try {
                const response = await axios.post(url, { accion: 2, id_user: user_id });
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            } catch (error) {
                console.error("Error al consultar el usuario;", error);
            }
        },
        listarObras: async function () {
            try {
                const response = await axios.post(url, { accion: 5 });
                this.obrasLista = response.data;
                console.log(this.obrasLista);
            } catch (error) {
                console.error("Error al Listar las Obras:", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        addRequisicion: async function () {
            if (this.obras[0].obra_automatico == 1) {
                const { value: formValues } = await Swal.fire({
                    title: "Nueva Requisicion",
                    html: `
                        <div class="col">
                            <hr/>
                            <form id="requisicionForm">
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label class="text-start py-2" for="nombreRequisicion">Nombre de la Requisición</label>
                                        <input type="text" class="form-control" id="nombreRequisicion" placeholder="Ingrese el nombre de la requisición" required>
                                    </div>
                                </div>
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label class="text-start py-2" for="fechaGeneracion">Fecha de Generación</label>
                                        <input type="date" class="form-control" id="fechaGeneracion" required>
                                    </div>
                                </div>
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label for="Clv" class="text-start py-2">Clave</label>
                                        <select class="form-select" aria-label="Default select example" id="Clv">
                                            <option>Selecciona Clave</option>
                                            <option value="MAT">MAT -Material</option>
                                            <option value="EQH">EQH -Equipo/Maquinaria</option>
                                            <option value="IND">IND -Indirectos</option>
                                            <option value="MO">MO -Mano de Obra</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <hr/>
                        </div>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    preConfirm: () => {
                        this.nombreRequisicion = document.getElementById("nombreRequisicion").value;
                        this.fechaGeneracion = document.getElementById("fechaGeneracion").value;
                        this.clave = document.getElementById("Clv").value;

                        if (!this.nombreRequisicion || !this.fechaGeneracion || this.clave === "Selecciona Clave") {
                            Swal.showValidationMessage('Por favor completa todos los campos');
                            return false;
                        }
                        return true;
                    }
                });

                if (formValues) {
                    // Lógica para solo "Agregar"
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    this.newRequisicionAuto();
                    Toast.fire({
                        icon: 'success',
                        title: 'Requisicion Agregada'
                    });
                }
            } else {
                const { value: formValues } = await Swal.fire({
                    title: "Nueva Requisicion",
                    html: `
                        <div class="col">
                            <hr/>
                            <form id="requisicionForm">
                                <div class="row form-group mx-0 my-3">
                                    <div class="col-6 d-flex flex-column">
                                        <label class="text-start py-2" for="FolioReq">Folio de la Requisicion:</label>
                                        <input type="text" class="form-control" id="FolioReq" placeholder="Ingrese el Folio de la Requisicion" required>
                                    </div>
                                    <div class="col-6 d-flex flex-column">
                                        <label class="text-start py-2" for="HojaReq">Hoja de la Requisicion:</label>
                                        <input type="text" class="form-control" id="HojaReq" placeholder="Ingrese la Hoja de la Requisicion" required>
                                    </div>
                                </div>
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label class="text-start py-2" for="nombreRequisicion">Nombre de la Requisición</label>
                                        <input type="text" class="form-control" id="nombreRequisicion" placeholder="Ingrese el nombre de la requisición" required>
                                    </div>
                                </div>
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label class="text-start py-2" for="fechaGeneracion">Fecha de Generación</label>
                                        <input type="date" class="form-control" id="fechaGeneracion" required>
                                    </div>
                                </div>
                                <div class="row form-group mx-0 my-3">
                                    <div class="col d-flex flex-column">
                                        <label for="Clv" class="text-start py-2">Clave</label>
                                        <select class="form-select" aria-label="Default select example" id="Clv">
                                            <option>Selecciona Clave</option>
                                            <option value="MAT">MAT -Material</option>
                                            <option value="EQH">EQH -Equipo/Maquinaria</option>
                                            <option value="IND">IND -Indirectos</option>
                                            <option value="MO">MO -Mano de Obra</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <hr/>
                        </div>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    preConfirm: () => {
                        this.nombreRequisicion = document.getElementById("nombreRequisicion").value;
                        this.fechaGeneracion = document.getElementById("fechaGeneracion").value;
                        this.clave = document.getElementById("Clv").value;
                        this.folioReq = document.getElementById("FolioReq").value;
                        this.hojaReq = document.getElementById("HojaReq").value;

                        if (!this.nombreRequisicion || !this.fechaGeneracion || !this.folioReq || !this.hojaReq || this.clave === "Selecciona Clave") {
                            Swal.showValidationMessage('Por favor completa todos los campos');
                            return false;
                        }
                        return true;
                    }
                });

                if (formValues) {
                    // Lógica para solo "Agregar"
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    this.newRequisicionManual();
                    Toast.fire({
                        icon: 'success',
                        title: 'Requisicion Agregada'
                    })
                }
            }
        },
        newRequisicionAuto: function () {
            axios.post(url, { accion: 6, nombreReq: this.nombreRequisicion, fechaReq: this.fechaGeneracion, clave: this.clave, obra: localStorage.getItem("obraActiva") }).then(response => {
                var table = $('#example').DataTable();
                // Para reinicializarlo, primero destrúyelo
                table.destroy();
                this.listarRequisiciones(localStorage.getItem("obraActiva"));
                console.log(response.data);
            });
        },
        newRequisicionManual: function () {
            this.hojaReq--;
            axios.post(url, { accion: 7, nombreReq: this.nombreRequisicion, fechaReq: this.fechaGeneracion, clave: this.clave, folio: this.folioReq, hoja: this.hojaReq, obra: localStorage.getItem("obraActiva") }).then(response => {
                var table = $('#example').DataTable();
                // Para reinicializarlo, primero destrúyelo
                table.destroy();
                this.listarRequisiciones(localStorage.getItem("obraActiva"));
                console.log(response.data);
            });
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        editRequisicion: function (index) {
            this.requisiciones[index]['requisicion_EditShow'] = true;
            this.requisiciones[index]['requisicion_Numero'] = this.ultimosDigitos(this.requisiciones[index]['requisicion_Numero']);
        },
        saveEditrequisicion: function (index, idReq, numeroReq, nombreReq) {
            axios.post(url, { accion: 8, idReq: idReq, numeroReq: numeroReq, nombreReq: nombreReq }).then(response => {
                this.requisiciones[index]['requisicion_EditShow'] = false;
                 this.requisiciones[index]['requisicion_Numero'] = response.data['numero_nuevo'];
                console.log(response.data);
            }).catch(error => {
                this.requisiciones[index]['requisicion_EditShow'] = false;
                console.log(error);
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "error",
                    title: "Cambio de forma de pago fallido"
                });
            });
        },
        deleteRequisicionShow: async function (index, idReq) {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success ms-5 w-auto",
                    cancelButton: "btn btn-danger w-auto"
                },
                buttonsStyling: false
            });
            swalWithBootstrapButtons.fire({
                title: "¿Quieres eliminar esta Requisicion?",
                text: "Si la requisicion tiene hojas integradas tambien se eliminaran y esta accion ya no se puede revertir",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Si, Borralo",
                cancelButtonText: "No",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.eliminarReq(index, idReq);
                }
            });
        },
        eliminarReq: function(index,idReq){
     
            axios.post(url, { accion: 9, idReq: idReq}). then(response => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "success",
                    title: "Eliminado"
                });
                this.requisiciones.splice(index,1);
                console.log(response.data);
            }).catch(error => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "error",
                    title: "Error al Eliminar"
                });
                console.error(error);
            });
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        ultimosDigitos: function(Folio){
            const partes = Folio.split('-');
            const ultimaParte = partes[partes.length - 1];
            const numeros = ultimaParte.match(/\d+$/);
            return numeros ? numeros[0] : null;
        }
    },
    mounted: async function () {
        await this.listarObras();
        await this.consultarUsuario(localStorage.getItem("NameUser"));
        await this.infoObraActiva(localStorage.getItem("obraActiva"));
        await this.listarRequisiciones(localStorage.getItem("obraActiva"));
    },
    computed: {

    }
});