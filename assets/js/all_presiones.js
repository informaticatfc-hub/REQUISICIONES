var url = "../api/crud_all_presiones.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppIndex",
    data: {
        users: [],
        obras: [],
        NameUser: "",
        presiones: [],
        adeudo: "",
        comentarios: ""
    },
    methods: {
        consultarUsuario: function (user_id) {
            axios.post(url, { accion: 1, id_user: user_id }).then(response => {
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            });
        },
        listarObras: function () {
            axios.post(url, { accion: 2 }).then(response => {
                this.obras = response.data;
                this.listarPresiones();
                //console.log(this.obras);
            });
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        listarPresiones: function () {
            console.log(this.obras);
            axios.post(url, { accion: 3, obras: JSON.stringify(this.obras) }).then(response => {
                this.presiones = response.data;
                console.log(this.presiones);

                $(document).ready(function () {
                    $('[data-toggle="tooltip"]').tooltip(); // Inicializa los tooltips
                });
            });
        },
        quitarEspacios: function (cadena) {
            if (!cadena) return ''; // Si la cadena es undefined o null, retorna una cadena vacía
            return cadena.replace(/\s+/g, ''); // Elimina todos los espacios
        },
        convertirADecimal: function (cadena) {
            // Verifica si cadena es una cadena de texto
            if (typeof cadena !== 'string') {
                // Si no es una cadena, conviértela a cadena
                cadena = String(cadena);
            }
            // Elimina el símbolo de dólar y convierte la cadena a número
            return parseFloat(cadena.replace('$', '').replace(',', ''));
        },
        autoriar: async function (idHoja, adeudo) {
            const { value: formValues, isConfirmed, isDenied } = await Swal.fire({
                title: "¿Desea Autorizar este Concepto?",
                html: `
                    <div class="col">
                        <div class="row form-group mx-0 my-3">
                            <div class="col">
                                <label for="adeudo" class="form-label">Adeudo a Pagar</label>
                                <input type="number" min="0" class="form-control" id="adeudo" value=`+ adeudo + `>
                                
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col">
                               <label for="comentarios" class="form-label">Comentarios </label>
                               <textarea class="form-control" id="comentarios" rows="3"></textarea>
                            </div>
                        </div>
                        <hr />
                    </div>
                `,
                focusConfirm: false,
                showDenyButton: true,
                confirmButtonText: 'Autorizar',
                confirmButtonColor: '#0d6efd',
                denyButtonText: 'Rechazar', // Cambia el texto del botón de cancelar
                preConfirm: () => {
                    const productoValue = document.getElementById("comentarios").value;
                    console.log(productoValue);
                    this.comentarios = document.getElementById("comentarios").value;;
                    console.log(this.comentarios);
                    this.adeudo = document.getElementById("adeudo").value;
                    if (productoValue.length > 200) {
                        Swal.showValidationMessage('El campo Producto no puede exceder los 200 caracteres.');
                        return false;
                    }
                    return true;
                }
            });
            if (isConfirmed) {
                // Acción para el botón "Autorizar"
                this.autorizado(idHoja, this.adeudo);
                Swal.fire({
                    title: "Autorizado",
                    text: "El articulo fue Aprovado.",
                    icon: "success"
                }).then(() => {
                    this.listarObras();
                });
                // Aquí puedes agregar la lógica para manejar la autorización
            } else if (isDenied) {
                // Acción para el botón "Rechazar"
                this.comentarios = document.getElementById("comentarios").value;
                this.rechazado(idHoja, this.comentarios);
                console.log(this.comentarios);
                Swal.fire({
                    title: "No autorizado",
                    text: "El articulo no se aprobo para pago.",
                    icon: "error"
                }).then(() => {
                    this.listarObras();
                });
                // Aquí puedes agregar la lógica para manejar el rechazo
            }
        },
        autorizado: function (idHoja, adeudo) {
            //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
            var estatus = "AUTORIZADO";
            //this.timeNow = this.getTime();
            axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, status: estatus, autorizado: true }).then(response => {
                console.log(response.data);
            });
        },
        rechazado: function (idHoja, comentarios) {
            //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
            var estatus = "RECHAZADO";
            //this.timeNow = this.getTime();
            axios.post(url, { accion: 4, idHoja: idHoja, coments: comentarios, status: estatus, autorizado: false }).then(response => {
                console.log(response.data);
            });
        },
        formatearMoneda: function (cadena) {
            // Convertir la cadena a un número
            let numero = parseFloat(cadena);
            // Verificar si la conversión fue exitosa
            if (isNaN(numero)) {
                return null; // O puedes lanzar un error si prefieres
            }
            // Formatear el número como moneda en pesos mexicanos
            return "$ " + numero.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        cambiarBooleano: function (valor, indice, index) {
            this.presiones[index].Presion_Obra[indice].showDetail = !valor; // Devuelve el valor opuesto
            if (!valor) {
                this.presiones[index].Presion_Obra[indice].atrClass = "inline-block fs-6";
                this.presiones[index].Presion_Obra[indice].strStyle = ""; //max-width: 150px;
            }
            else {
                this.presiones[index].Presion_Obra[indice].atrClass = "inline-block text-truncate fs-6";
                this.presiones[index].Presion_Obra[indice].strStyle = "max-width: 100px;";//max-width: 100px;
            }

        },
        consultarTotales: async function (totalGlobalProp, TotalGlobalAut, totalEfectivoProp, totalEfectivoAut, totalTransProp, totalTransAut, totalGlobalRechazado, totalEfectivoRechazado, totalTransRechazado, nombreObra) {
            const { value: formValues } = await Swal.fire({
                title: "Consulta de Totales de la Presion de " + nombreObra,
                html: `
                 <table class="table align-middle table-hover w-100">
                    <thead>
                        <tr>
                            <th class="no-border-top-left"></th> <!-- Celda 0,0 sin borde superior ni izquierdo -->
                            <th class="table-dark">Total Global</th>
                            <th class="table-dark">Total Efectivo</th>
                            <th class="table-dark">Total Transferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="table-dark">Propuesto</td> <!-- Primera celda de la segunda fila -->
                            <td class="table-light">`+ this.formatearMoneda(totalGlobalProp) + `</td>
                            <td class="table-light">`+ this.formatearMoneda(totalEfectivoProp) + `</td>
                            <td class="table-light">`+ this.formatearMoneda(totalTransProp) + `</td>
                        </tr>
                        <tr>
                            <td class="table-dark">Autorizado</td> <!-- Primera celda de la tercera fila -->
                            <td class="table-primary">`+ this.formatearMoneda(TotalGlobalAut) + `</td>
                            <td class="table-primary">`+ this.formatearMoneda(totalEfectivoAut) + `</td>
                            <td class="table-primary">`+ this.formatearMoneda(totalTransAut) + `</td>
                        </tr>
                        <tr>
                            <td class="table-dark">Rechazado</td> <!-- Primera celda de la tercera fila -->
                            <td class="table-danger">`+ this.formatearMoneda(totalGlobalRechazado) + `</td>
                            <td class="table-danger">`+ this.formatearMoneda(totalEfectivoRechazado) + `</td>
                            <td class="table-danger">`+ this.formatearMoneda(totalTransRechazado) + `</td>
                        </tr>
                    </tbody>
                </table>
                `,
                customClass: {
                    popup: 'custom-popup' // Clase personalizada
                },
                focusConfirm: false,
            });
        },
        restartAlert: async function (id_Hoja) {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Quieres restablecer este concepto?",
                text: 'Al restablecer el concepto se marcara como "Ligada". Esta operacion no se puede revertir',
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.restart(id_Hoja);
                    swalWithBootstrapButtons.fire({
                        title: "Restablecida",
                        text: "La presion a sido Restablecida con exito.",
                        icon: "success"
                    }).then(() => {
                        this.listarObras();
                    });
                }
            });
        },
        restart: function (id_Hoja) {
            axios.post(url, { accion: 5, idHoja: id_Hoja }).then(response => {
                this.listarObras();
            });
        },
        showEdit: function (index, index2) {
            this.presiones[index2].Presion_Obra[index].atrClass = "inline-block fs-6";
            this.presiones[index2].Presion_Obra[index].strStyle = "max-width: 150px;";
            this.presiones[index2].Presion_Obra[index].edit_Auto = true;
        },
        saveEdit: function (adeudo, observaciones, idHoja) {
            axios.post(url, { accion: 6, idHoja: idHoja, parcial: adeudo, coments: observaciones }).then(response => {
                this.listarObras();
            });
        },
        irMenuCatalago: function () {
            window.location.href = url2 + "/menu_catalago.php";
        },
        changedCheck: function (estatus, adeudo, observaciones, idHoja) {
            if (estatus == "LIGADA") {
                axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, coments: observaciones, autorizado: true }).then(response => {
                    console.log(response.data);
                });
            }
            else {
                axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, coments: observaciones, autorizado: false }).then(response => {
                    console.log(response.data);
                });
            }
        },
        guardarCambios: function (presion) {
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

            if (typeof presion !== 'object' || presion === null) {
                console.error('El parámetro "presion" no es un objeto válido:', presion);
                return;
            }

            console.log(presion);
            axios.post(url, { accion: 7, presion: presion }).then(response => {
                if (response.data["status"] == "success") {
                    Toast.fire({
                        icon: "success",
                        title: response.data["mensaje"]
                    });
                }
            }
            ).catch(error => {
                console.error(error);
                Toast.fire({
                    icon: "error",
                    title: "Fallo al Eliminar elemento"
                });
            });
        },
        openWinPorcentaje: async function (indicePresion, indiceHoja) {
            console.log(indicePresion + " " + indiceHoja);
            const { value: formValues } = await Swal.fire({
                title: "AGREGAR/DESCONTAR PORCENTAJE",
                html: `
                        <div style="overflow: hidden; text-align: left;">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="accion" value="Desc" id="descrementBox" checked>
                                        <label class="form-check-label" for="incrementoBox">
                                           Aplicar Descuento
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="accion" value="Inc" id="incrementoBox">
                                        <label class="form-check-label" for="descrementoBox">
                                            Aplicar Incremento
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Porcentaje a Aplicar:</label>
                                    <div class="input-group mb-3"">
                                        <input type="text" class="form-control" id="porcentaje">
                                        <span class="input-group-text fw-bold" id="basic-addon2"> % </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `, // Tu contenido aquí
                focusConfirm: false,
                width: '30%',
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    return {
                        porcentaje: document.getElementById('porcentaje')?.value || '',
                        accion: document.querySelector('input[name="accion"]:checked').value
                    };
                }
            });
            if (formValues) {
               this.aplicarPorcentaje(indicePresion, indiceHoja, formValues);
            }
        },
        convertirAdecimalEntero: function (numeroEntero) {
            // Verificamos que sea un número
            if (isNaN(numeroEntero)) return 0;

            // Convertimos el entero a decimal dividiendo entre 100
            return parseFloat(numeroEntero) / 100;
        },
        aplicarPorcentaje: function (indicePresion, indiceHoja, formValues) {
            const porcentaje = parseInt(formValues["porcentaje"]);
            const accion = formValues["accion"];
            const cantidadDecimal = this.convertirAdecimalEntero(porcentaje);

            let adeudoActual = parseInt(this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"]);
            let cantidadAccion = adeudoActual * cantidadDecimal;

            // Aplicar operación
            this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"] =
                accion === "Inc"
                    ? adeudoActual + cantidadAccion
                    : adeudoActual - cantidadAccion;
        }
    },
    created: function () {
        this.listarObras();
        this.consultarUsuario(localStorage.getItem("NameUser"));
    },
    computed: {

    }
});