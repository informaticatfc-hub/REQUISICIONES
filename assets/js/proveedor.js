/**
 * @deprecated Fase 4c
 * ------------------------------------------------------------
 * Este archivo dejo de usarse cuando pages/proveedores.php se migro
 * al layout v4 (la logica Vue ahora va inline en el PHP).
 *
 * Se conserva aqui temporalmente como referencia historica.
 * No se carga desde ninguna pagina. Eliminar en una fase posterior.
 * ------------------------------------------------------------
 */
var url = "../api/crud_proveedor.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppIndex",
    data: {
        users: [],
        obras: [],
        proveedores: [],
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
        consultarUsuario: function (user_id) {
            axios.post(url, { accion: 1, id_user: user_id }).then(response => {
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            }).catch(error => {
                console.error("Error al obtener las obras:", error);
            });
        },
        listarObras: function () {
            axios.post(url, { accion: 2 }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            }).catch(error => {
                console.error("Error al obtener las obras:", error);
            });
        },
        obtenerProveedores: function () {
            axios.post(url, { accion: 3 }).then(response => {
                this.proveedores = response.data;
                console.log(this.proveedores);
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
        addProvedores: function () {
            window.location.href = url2 + "/agregar_proveedor.php";
        },
        desactivarProveedor: function(index, idProveedor) {     
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
               
            axios.post(url, { accion: 4, id_prov: idProveedor }).then(response => {
                this.proveedores.splice(index, 1);
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
        viewProveedor: async function(idProveedor){
            axios.post(url,{accion: 5, id_prov: idProveedor}).then(response => {
                if(response)
                {
                    console.log(response.data);
                    const { value: formValues } = Swal.fire({
                        title: "CONSULTAR PROVEEDOR",
                        html: `
                        <div style="overflow: hidden; text-align: left;">
                             <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nombre del Proveedor:</label>
                                    <p id="nombreProveedor">`+ response.data[0]['proveedor_nombre'] +`</p>
                                </div>
                            </div>
        
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">RFC del Proveedor:</label>
                                    <p id="rfcProveedor">`+ response.data[0]['proveedor_rfc'] +`</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Clave Bancaria:</label>
                                    <p id="claveBancaria">`+ response.data[0]['proveedor_clabe'] +`</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Cuenta Bancaria:</label>
                                    <p id="cuentaBancaria">`+ response.data[0]['proveedor_numeroCuenta'] +`</p>
                                </div>
                            </div>
        
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Número de Tarjeta:</label>
                                    <p id="numTarjeta">`+ response.data[0]['presiones_tarjetaBanco'] +`</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Número de Referencia:</label>
                                    <p id="numReferencia">`+ response.data[0]['proveedor_refBanco'] +`</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tipo de Proveedor:</label>
                                    <p id="tipoProveedor">`+ response.data[0]['presiones_type'] +`</p>
                                </div>
                            </div>
        
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Banco del Proveedor:</label>
                                    <p id="bancoProveedor">`+ response.data[0]['proveedor_banco'] +`</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Sucursal Bancaria:</label>
                                    <p id="sucursalBancaria">`+ response.data[0]['proveedor_sucursal'] +`</p>
                                </div>
                            </div>
        
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Teléfono:</label>
                                    <p id="telefonoProveedor">`+ response.data[0]['proveedor_telefono'] +`</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Correo Electrónico:</label>
                                    <p id="correoProveedor">`+ response.data[0]['proveedor_email'] +`</p>
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
        editProveedor: async function(indice, idProveedor) {
            try {
                const response = await axios.post(url, { accion: 5, id_prov: idProveedor });
                if (response && response.data.length > 0) {
                    const { value: formValues } = await Swal.fire({
                        title: "CONSULTAR PROVEEDOR",
                        html: `
                        <div style="overflow: hidden; text-align: left;">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nombre del Proveedor:</label>
                                    <input type="text" class="form-control" id="nombreProveedor" value="`+ response.data[0]['proveedor_nombre'] +`">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">RFC del Proveedor:</label>
                                    <input type="text" class="form-control" id="rfcProveedor" value="`+ response.data[0]['proveedor_rfc'] +`">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Clave Bancaria:</label>
                                    <input type="text" class="form-control" id="claveBancaria" value="`+ response.data[0]['proveedor_clabe'] +`">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Cuenta Bancaria:</label>
                                    <input type="text" class="form-control" id="cuentaBancaria" value="`+ response.data[0]['proveedor_numeroCuenta'] +`">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Número de Tarjeta:</label>
                                    <input type="text" class="form-control" id="numTarjeta" value="`+ response.data[0]['presiones_tarjetaBanco'] +`">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Número de Referencia:</label>
                                    <input type="text" class="form-control" id="numReferencia" value="`+ response.data[0]['proveedor_refBanco'] +`">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tipo de Proveedor:</label>
                                    <input type="text" class="form-control" id="tipoProveedor" value="`+ response.data[0]['presiones_type'] +`">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Banco del Proveedor:</label>
                                    <input type="text" class="form-control" id="bancoProveedor" value="`+ response.data[0]['proveedor_banco'] +`">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Sucursal Bancaria:</label>
                                    <input type="text" class="form-control" id="sucursalBancaria" value="`+ response.data[0]['proveedor_sucursal'] +`">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Teléfono:</label>
                                    <input type="text" class="form-control" id="telefonoProveedor" value="`+ response.data[0]['proveedor_telefono'] +`">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Correo Electrónico:</label>
                                    <input type="email" class="form-control" id="correoProveedor" value="`+ response.data[0]['proveedor_email'] +`">
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
                                nombreProv: document.getElementById('nombreProveedor')?.value || '',
                                RFCProv: document.getElementById('rfcProveedor')?.value || '',
                                claveProv: document.getElementById('claveBancaria')?.value || '',
                                cuentaBancaria: document.getElementById('cuentaBancaria')?.value || '',
                                referenciaProv: document.getElementById('numReferencia')?.value || '',
                                tarjetaProv: document.getElementById('numTarjeta')?.value || '',
                                typeProv: document.getElementById('tipoProveedor')?.value || '',
                                bancoProv: document.getElementById('bancoProveedor')?.value || '',
                                sucursalProv: document.getElementById('sucursalBancaria')?.value || '',
                                telefonoProv: document.getElementById('telefonoProveedor')?.value || '',
                                correoProv: document.getElementById('correoProveedor')?.value || ''
                            };
                        }
                    });
        
                    if (formValues) {
                        console.log('Datos del proveedor:', formValues);
                        this.editBDprov(indice, idProveedor, formValues);
                    }
                }
            } catch (error) {
                console.error("Error en editProveedor:", error);
            }
        },
        editBDprov: function(indice, idProveedor, formValues){
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

            axios.post(url,{accion:6, id_prov: idProveedor, formValues: formValues}).then(response => {
                this.proveedores[indice]['presiones_tarjetaBanco'] = formValues['tarjetaProv'];
                this.proveedores[indice]['presiones_type'] = formValues['typeProv'];
                this.proveedores[indice]['proveedor_banco'] = formValues['bancoProv'];
                this.proveedores[indice]['proveedor_clabe'] = formValues['claveProv'];
                this.proveedores[indice]['proveedor_email'] = formValues['correoProv'];
                this.proveedores[indice]['proveedor_nombre'] = formValues['nombreProv'];
                this.proveedores[indice]['proveedor_numeroCuenta'] = formValues['cuentaBancaria'];
                this.proveedores[indice]['proveedor_refBanco'] = formValues['referenciaProv'];
                this.proveedores[indice]['proveedor_rfc'] = formValues['RFCProv'];
                this.proveedores[indice]['proveedor_sucursal'] = formValues['sucursalProv'];
                this.proveedores[indice]['proveedor_telefono'] = formValues['telefonoProv'];
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
        }        
    },
    mounted: async function () {
        await this.listarObras();
        await this.consultarUsuario(localStorage.getItem("NameUser"));
        await this.obtenerProveedores();
    },
    computed: {

    }
});