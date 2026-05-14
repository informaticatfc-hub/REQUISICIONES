var url = "../api/crud_bancos.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppIndex",
    data: {
        users: [],
        obras: [],
        bancos: [],
        nombreProv: "",
        RFCProv: "",
        claveProv: "",
        cuentaProv: "",
        tarjetaProv: "",
        referenciaProv: "",
        typeProv: "",
        bancoProv: "",
        sucursalProv: "",
        telefonoProv: "",
        correoProv: "",
        NameUser: "",
    },
    methods: {
        consultarUsuario: function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            return axios.post(url, { accion: 1 }).then(response => {
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            }).catch(err => console.error("consultarUsuario:", err));
        },
        listarObras: function () {
            axios.post(url, { accion: 2 }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            }).catch(error => {
                console.error("Error al obtener las obras:", error);
            });
        },
        obtenerBancos: function () {
            axios.post(url, { accion: 3 }).then(response => {
                this.bancos = response.data;
                console.log(this.bancos);
            }).catch(error => {
                console.error("Error al obtener los proveedores:", error);
            }).finally(() => {
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
                        },
                        "autoWidth": false, // Deshabilita el auto ancho
                    });
                });
            });
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function () {
            window.location.href = url2 + "/menu_catalago.php";
        },
        addBanco: async function () {

            const { value: formValues } = await Swal.fire({
                title: "AGREGAR BANCO",
                html: `
                <div style="overflow: hidden; text-align: left;">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Razon Social del Banco:</label>
                            <input type="text" class="form-control" id="razonSocialBanco">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Nombre Comercial del Banco:</label>
                            <input type="text" class="form-control" id="comercialBanco">
                        </div>
                    </div>
                </div>
                `, // Tu contenido aquí
                focusConfirm: false,
                width: '50%',
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    return {
                        razonSocialBanco: document.getElementById('razonSocialBanco')?.value || '',
                        comercialBanco: document.getElementById('comercialBanco')?.value || '',
                    };
                }
            });

            if (formValues) {
                console.log('Datos del banco:', formValues);
                this.addBancoBD(formValues);
            }
        },
        desactivarBanco: function(index, idBanco) {     
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
               
            axios.post(url, { accion: 4, id_banco: idBanco }).then(response => {
                this.bancos.splice(index, 1);
                Toast.fire({
                    icon: "success",
                    title: "Se elimino elemento correctamente"
                });
            }).catch(error =>{
                console.error(error);
                Toast.fire({
                    icon: "error",
                    title: "Fallo al Eliminar elemento"
                });
            });         
        },
        viewBanco: async function(idBanco){
            axios.post(url,{accion: 5, id_banco: idBanco}).then(response => {
                if(response)
                {
                    console.log(response.data);
                    const { value: formValues } = Swal.fire({
                        title: "CONSULTAR BANCO",
                        html: `
                        <div style="overflow: hidden; text-align: left;">
                             <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Razon Social del Banco:</label>
                                    <p id="nombreBanco">`+ response.data[0]['banco_razonSocial'] +`</p>
                                </div>
                            </div>
        
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nombre Comercial del Banco:</label>
                                    <p id="nombreBanco">`+ response.data[0]['banco_nombreComercial'] +`</p>
                                </div>
                            </div>
                        </div>
                        `,
                        focusConfirm: false,
                        width: '50%'
                      });
                }
            });     
        },
        editProveedor: async function(indice, id_banco) {
            try {
                const response = await axios.post(url, { accion: 5, id_banco: id_banco });
                if (response && response.data.length > 0) {
                    const { value: formValues } = await Swal.fire({
                        title: "EDITAR BANCO",
                        html: `
                        <div style="overflow: hidden; text-align: left;">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Razon Social del Banco:</label>
                                    <input type="text" class="form-control" id="razonSocialBanco" value="`+ response.data[0]['banco_razonSocial'] +`">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nombre Comercial del Banco:</label>
                                    <input type="text" class="form-control" id="comercialBanco" value="`+ response.data[0]['banco_nombreComercial'] +`">
                                </div>
                            </div>
                        </div>
                        `, // Tu contenido aquí
                        focusConfirm: false,
                        width: '50%',
                        showCancelButton: true,
                        confirmButtonText: 'Editar',
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#dc3545',
                        preConfirm: () => {
                            return {
                                razonSocialBanco: document.getElementById('razonSocialBanco')?.value || '',
                                comercialBanco: document.getElementById('comercialBanco')?.value || '',
                            };
                        }
                    });
        
                    if (formValues) {
                        console.log('Datos del banco:', formValues);
                        this.editBDprov(indice, id_banco, formValues);
                    }
                }
            } catch (error) {
                console.error("Error en editProveedor:", error);
            }
        },
        editBDprov: function(indice, id_banco, formValues){
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

            axios.post(url,{accion:6, id_banco: id_banco, formValues: formValues}).then(response => {
                this.bancos[indice]['banco_razonSocial'] = formValues['razonSocialBanco'];
                this.bancos[indice]['banco_nombreComercial'] = formValues['comercialBanco'];
                Toast.fire({
                    icon: "success",
                    title: "Elemento editado correctamente"
                });
            }).catch(error => {
                console.error("Error al editar", error);
                Toast.fire({
                    icon: "error",
                    title: "Fallo al editar elemento"
                });
            })
        },
        addBancoBD: function(formValues){
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
            axios.post(url,{accion: 7, formValues: formValues}).then(response =>{
               if(response.data == 1){
                    var table = $('#example').DataTable();
                    // Para reinicializarlo, primero destrúyelo
                    table.destroy();
                    this.obtenerBancos();
                    Toast.fire({
                        icon: "success",
                        title: "Elemento Agregado correctamente"
                    });
               }
            }).catch(error => {
                console.error("Error al editar", error);
                Toast.fire({
                    icon: "error",
                    title: "Fallo al editar elemento"
                });
            })
        }        
    },
    mounted: async function () {
        await this.listarObras();
        await this.consultarUsuario();
        await this.obtenerBancos();
    },
    computed: {

    }
});